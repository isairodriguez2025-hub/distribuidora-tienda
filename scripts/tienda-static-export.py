#!/usr/bin/env python3
"""
Exporta el sitio publico (/tienda y sus subpaginas) a un sitio estatico para
GitHub Pages.

Por que existe: el proyecto es Next.js (SSR) y GitHub Pages solo sirve archivos
estaticos. Este script toma el HTML ya renderizado del servidor de desarrollo,
le incrusta el CSS compilado de la tienda, quita el JavaScript de Next y agrega
un JS minimo para que sigan funcionando:

  - las pestanas de filtro por seccion
  - el contador del carrito y el aviso flotante
  - el buscador

Paginas exportadas (se descubren solas):
    /tienda                        -> index.html
    /tienda/<seccion>              -> <seccion>/index.html
    /tienda/producto/<slug>        -> producto/<slug>/index.html

Uso:
    npm run dev
    python3 scripts/tienda-static-export.py
    # publicar la carpeta dist-pages/ en la rama gh-pages

Variables opcionales:
    TIENDA_DEV_URL   (default http://localhost:3000)
    TIENDA_OUT       (default ./dist-pages)
    TIENDA_BASE      (default /distribuidora-tienda) prefijo del project site
"""
import os
import re
import shutil
import urllib.request
from pathlib import Path

BASE = os.environ.get("TIENDA_DEV_URL", "http://localhost:3000")
OUT = Path(os.environ.get("TIENDA_OUT", "dist-pages"))
PREFIX = os.environ.get("TIENDA_BASE", "/distribuidora-tienda")
FONTS_SRC = Path("public/fonts")

SECCIONES = ["abarrotes", "limpieza", "herreria"]


def get(path: str, intentos: int = 3) -> str:
    ultimo = None
    for _ in range(intentos):
        try:
            with urllib.request.urlopen(f"{BASE}{path}", timeout=180) as r:
                return r.read().decode("utf-8", "replace")
        except Exception as e:  # noqa: BLE001
            ultimo = e
    raise RuntimeError(f"No se pudo leer {path}: {ultimo}")


def links_internos(html: str) -> set[str]:
    """Rutas /tienda/... que aparecen en la pagina, sin anclas ni query."""
    rutas = set()
    for href in re.findall(r'href="(/tienda[^"#?]*)"', html):
        rutas.add(href.rstrip("/") or "/tienda")
    return rutas


def destino(ruta: str) -> Path:
    """/tienda/abarrotes -> abarrotes/index.html ; /tienda -> index.html"""
    limpia = ruta.replace("/tienda", "", 1).strip("/")
    if not limpia:
        return OUT / "index.html"
    return OUT / limpia / "index.html"


def url_publica(ruta: str) -> str:
    limpia = ruta.replace("/tienda", "", 1).strip("/")
    return f"{PREFIX}/" if not limpia else f"{PREFIX}/{limpia}/"


def procesar(html: str, css: str) -> str:
    # Fuera estilos, precargas y scripts de Next
    html = re.sub(r'<link rel="stylesheet"[^>]*>', "", html)
    html = re.sub(r'<link rel="preload"[^>]*>', "", html)
    html = re.sub(r"<script\b[^>]*>.*?</script>", "", html, flags=re.S)

    # Enlaces internos -> rutas absolutas con el prefijo del project site
    def repl(m: re.Match) -> str:
        ruta = m.group(1).rstrip("/") or "/tienda"
        if ruta == "/tienda":
            return f'href="{PREFIX}/"'
        if ruta in ("/tienda/catalogo", "/tienda/carrito", "/tienda/pedidos", "/tienda/seguimiento"):
            return f'href="{PREFIX}/"'
        return f'href="{url_publica(ruta)}"'

    html = re.sub(r'href="(/tienda[^"#?]*)"', repl, html)
    html = re.sub(r'href="/(login|register|setup)"', 'href="#"', html)

    # hoja de estilos compartida (antes iba incrustada en cada pagina)
    hoja = f'<link rel="stylesheet" href="{PREFIX}/assets/tienda.css">'
    html = html.replace("</head>", hoja + "\n</head>", 1)
    return html


JS = """
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
  Array.prototype.forEach.call(document.querySelectorAll('.t-btn'), function (btn) {
    if (btn.tagName !== 'BUTTON') return;
    if (btn.closest('.t-tabs')) return;
    if (btn.textContent.trim().indexOf('Agregar') === -1) return;
    btn.addEventListener('click', function () {
      var card = btn.closest('.t-card');
      var titulo = document.querySelector('.t-product__info h1');
      var name = card ? card.querySelector('.t-card__name').textContent.trim()
                      : (titulo ? titulo.textContent.trim() : 'Producto');
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
      input.placeholder = 'Buscar producto...';
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


def build() -> None:
    home = get("/tienda")

    # CSS: se descarta la hoja de Tailwind del POS y se conserva la del sitio
    css_urls = re.findall(r'<link rel="stylesheet" href="([^"]+)"', home)
    partes = [get(u) for u in css_urls]
    css = "\n\n".join(p for p in partes if ".t-root" in p) or "\n\n".join(partes)
    css = "html,body{margin:0;padding:0;background:#fff;}\n" + css
    css = f"/* prefijo del project site */\n" + css.replace("url(/fonts/", f"url({PREFIX}/fonts/")

    # Descubrir rutas: portada -> secciones -> fichas de producto
    rutas = ["/tienda"] + [f"/tienda/{s}" for s in SECCIONES]
    htmls = {r: (home if r == "/tienda" else get(r)) for r in rutas}

    productos = set()
    for html in htmls.values():
        productos |= links_internos(html)
    productos = sorted(p for p in productos if p.startswith("/tienda/producto/"))
    for p in productos:
        htmls[p] = get(p)

    # Escribir
    if OUT.exists():
        shutil.rmtree(OUT)
    OUT.mkdir(parents=True)
    (OUT / ".nojekyll").write_text("", encoding="utf-8")

    total = 0
    for ruta, html in htmls.items():
        final = procesar(html, css)
        final = final.replace("</body>", JS + "</body>", 1)
        archivo = destino(ruta)
        archivo.parent.mkdir(parents=True, exist_ok=True)
        archivo.write_text(final, encoding="utf-8")
        total += archivo.stat().st_size

    assets = OUT / "assets"
    assets.mkdir()
    (assets / "tienda.css").write_text(css, encoding="utf-8")

    dst = OUT / "fonts"
    dst.mkdir()
    for f in FONTS_SRC.glob("*.ttf"):
        shutil.copy2(f, dst / f.name)

    print(f"OK -> {len(htmls)} paginas ({total/1024:.0f} KB de HTML) en {OUT}/")
    print(f"     {len(productos)} fichas de producto · prefijo {PREFIX}/")


if __name__ == "__main__":
    build()
