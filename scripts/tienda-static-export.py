#!/usr/bin/env python3
"""
Exporta la portada publica (/tienda) a un sitio estatico para GitHub Pages.

Por que existe: el proyecto es Next.js (SSR) y GitHub Pages solo sirve archivos
estaticos. Este script toma el HTML ya renderizado del servidor de desarrollo,
le incrusta el CSS compilado de la tienda, quita el JavaScript de Next y agrega
un JS minimo para que sigan funcionando:

  - las pestanas de filtro por seccion
  - el contador del carrito y el aviso flotante
  - el buscador

Uso:
    npm run dev                     # en otra terminal, en el puerto 3000
    python3 scripts/tienda-static-export.py
    git subtree push --prefix dist-pages origin gh-pages

Variables opcionales:
    TIENDA_DEV_URL   (default http://localhost:3000)
    TIENDA_OUT       (default ./dist-pages)
"""
import os
import re
import shutil
import urllib.request
from pathlib import Path

BASE = os.environ.get("TIENDA_DEV_URL", "http://localhost:3000")
OUT = Path(os.environ.get("TIENDA_OUT", "dist-pages"))
FONTS_SRC = Path("public/fonts")


def get(path: str) -> str:
    with urllib.request.urlopen(f"{BASE}{path}", timeout=120) as r:
        return r.read().decode("utf-8", "replace")


def build() -> None:
    html = get("/tienda")

    # CSS: se descarta la hoja de Tailwind del POS (~110 KB) y se conserva solo
    # la del sitio publico, mas un reset minimo.
    css_urls = re.findall(r'<link rel="stylesheet" href="([^"]+)"', html)
    partes = [get(u) for u in css_urls]
    css = "\n\n".join(p for p in partes if ".t-root" in p) or "\n\n".join(partes)
    css = "html,body{margin:0;padding:0;background:#fff;}\n" + css

    # Rutas de fuentes: absolutas -> relativas (Pages sirve bajo /repo/)
    css = css.replace("url(/fonts/", "url(./fonts/").replace('url("/fonts/', 'url("./fonts/')

    # Fuera estilos, precargas y scripts de Next
    html = re.sub(r'<link rel="stylesheet"[^>]*>', "", html)
    html = re.sub(r'<link rel="preload"[^>]*>', "", html)
    html = re.sub(r"<script\b[^>]*>.*?</script>", "", html, flags=re.S)

    # Enlaces internos que en el estatico no existen
    enlaces = {
        "/tienda/abarrotes": "#secciones",
        "/tienda/limpieza": "#secciones",
        "/tienda/herreria": "#secciones",
        "/tienda/catalogo": "#destacados",
        "/tienda/carrito": "#",
        "/tienda/pedidos": "#",
        "/tienda/seguimiento": "#",
        "/login": "#",
        "/tienda": "./index.html",
    }
    for viejo, nuevo in enlaces.items():
        html = html.replace(f'href="{viejo}"', f'href="{nuevo}"')

    html = html.replace(
        '<section class="t-section t-section--tight"',
        '<section id="destacados" class="t-section t-section--tight"',
        1,
    )

    html = html.replace("</head>", f"<style>\n{css}\n</style>\n</head>", 1)

    js = """
<script>
(function () {
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.t-tab'));
  var cards = Array.prototype.slice.call(document.querySelectorAll('.t-card'));
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('is-active'); t.setAttribute('aria-selected', 'false'); });
      tab.classList.add('is-active');
      tab.setAttribute('aria-selected', 'true');
      var nombre = tab.textContent.trim();
      cards.forEach(function (card) {
        var cat = card.querySelector('.t-card__cat');
        var texto = cat ? cat.textContent.trim() : '';
        card.style.display = (nombre === 'Todos' || texto === nombre) ? '' : 'none';
      });
    });
  });

  var items = 0;
  var toast = document.querySelector('.t-toast');
  var cartBtn = document.querySelector('[aria-label^="Carrito"]');
  var timer = null;
  function badge() {
    if (!cartBtn) return;
    var b = cartBtn.querySelector('.t-badge');
    if (!b) { b = document.createElement('span'); b.className = 't-badge'; cartBtn.appendChild(b); }
    b.textContent = items;
  }
  Array.prototype.forEach.call(document.querySelectorAll('.t-card .t-btn'), function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('.t-card');
      var name = card ? card.querySelector('.t-card__name').textContent.trim() : 'Producto';
      items += 1;
      badge();
      if (toast) {
        toast.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m4 12.5 5 5L20 6.5"/></svg><span></span>';
        toast.querySelector('span').textContent = 'Agregado al carrito: ' + name;
        toast.classList.add('is-on');
        if (timer) clearTimeout(timer);
        timer = setTimeout(function () { toast.classList.remove('is-on'); }, 2400);
      }
    });
  });

  var lupa = document.querySelector('button[aria-label="Buscar"]');
  if (lupa) {
    lupa.addEventListener('click', function () {
      if (document.querySelector('.t-search')) return;
      var label = document.createElement('label');
      label.className = 't-search';
      label.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>';
      var input = document.createElement('input');
      input.type = 'search';
      input.placeholder = 'Buscar producto o codigo...';
      label.appendChild(input);
      lupa.parentNode.insertBefore(label, lupa);
      input.focus();
      input.addEventListener('input', function () {
        var q = input.value.toLowerCase();
        cards.forEach(function (card) {
          var n = card.querySelector('.t-card__name');
          card.style.display = (!q || (n && n.textContent.toLowerCase().indexOf(q) !== -1)) ? '' : 'none';
        });
      });
      input.addEventListener('blur', function () { if (!input.value) label.remove(); });
    });
  }
})();
</script>
"""
    html = html.replace("</body>", js + "</body>", 1)

    if OUT.exists():
        shutil.rmtree(OUT)
    OUT.mkdir(parents=True)
    (OUT / "index.html").write_text(html, encoding="utf-8")
    (OUT / ".nojekyll").write_text("", encoding="utf-8")

    dst = OUT / "fonts"
    dst.mkdir()
    for f in FONTS_SRC.glob("*.ttf"):
        shutil.copy2(f, dst / f.name)

    kb = len((OUT / "index.html").read_bytes()) / 1024
    print(f"OK -> {OUT}/index.html ({kb:.0f} KB) + {len(list(dst.glob('*.ttf')))} fuentes")


if __name__ == "__main__":
    build()
