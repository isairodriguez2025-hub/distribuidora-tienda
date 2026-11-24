/**
 * Captura pantallas del sitio de Ares por rol, en viewport de escritorio y
 * movil, para la documentacion (docs/images).
 *
 * Uso: node capturas-doc.cjs <BASE_URL> [directorio]
 * Login demo: comercio123 / repartidor123 / admin123.
 * El rate limit de login es 5 por IP: este script hace 3 logins maximo.
 */
const path = require('path');

function cargarPlaywright() {
  const candidatos = [
    'playwright',
    path.join(process.env.HOME || '', 'didi2-b2b/node_modules/playwright'),
    path.join(process.env.HOME || '', 'distribuidora/node_modules/playwright'),
  ];
  for (const c of candidatos) { try { return require(c); } catch (_) {} }
  throw new Error('playwright no encontrado');
}

const { chromium } = cargarPlaywright();
const BASE = process.argv[2] || 'https://riz0308.tail0820b4.ts.net:8443';
const DIR = process.argv[3] || path.join(process.env.HOME || '', 'distribuidora-final/docs/images');

const CAPTURAS = [
  { rol: 'comercio', email: 'comercio@didi2.mx', pass: 'comercio123', rutas: [
    ['/explorar', 'comercio-explorar'],
    ['/buscar', 'comercio-buscar'],
    ['/carrito', 'comercio-carrito'],
    ['/pedidos', 'comercio-pedidos'],
  ]},
  { rol: 'mayorista', email: 'demo-insumos-del-valle@didi2.mx', pass: 'demo1234', rutas: [
    ['/mayorista/pedidos', 'mayorista-pedidos'],
    ['/mayorista/productos', 'mayorista-productos'],
    ['/finanzas', 'mayorista-finanzas'],
  ]},
  { rol: 'repartidor', email: 'repartidor@didi2.mx', pass: 'repartidor123', rutas: [
    ['/repartidor', 'repartidor-panel'],
  ]},
  { rol: 'admin', email: 'admin@didi2.mx', pass: 'admin123', rutas: [
    ['/admin', 'admin-dashboard'],
    ['/admin/usuarios', 'admin-usuarios'],
  ]},
];

(async () => {
  const browser = await chromium.launch({ channel: 'chrome', args: ['--no-sandbox'] });
  for (const c of CAPTURAS) {
    const ctx = await browser.newContext({ viewport: { width: 1366, height: 900 }, deviceScaleFactor: 1.5 });
    const page = await ctx.newPage();
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', c.email);
    await page.fill('input[name="password"]', c.pass);
    await Promise.all([page.waitForLoadState('load'), page.click('button[type="submit"]')]);
    if (page.url().includes('/login')) {
      console.error(`${c.rol}: el login no entro (${page.url()})`);
      await ctx.close();
      continue;
    }
    for (const [ruta, nombre] of c.rutas) {
      try {
        await page.goto(`${BASE}${ruta}`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        await page.screenshot({ path: path.join(DIR, `${nombre}.png`) });
        console.log(`ok ${nombre}`);
      } catch (e) {
        console.error(`fallo ${nombre}: ${e.message}`);
      }
    }
    await ctx.close();
  }
  // Movil: comercio en 390px
  const ctxM = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true });
  const pm = await ctxM.newPage();
  await pm.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await pm.fill('input[name="email"]', 'comercio@didi2.mx');
  await pm.fill('input[name="password"]', 'comercio123');
  await Promise.all([pm.waitForLoadState('load'), pm.click('button[type="submit"]')]);
  if (!pm.url().includes('/login')) {
    for (const [ruta, nombre] of [['/explorar', 'movil-comercio-explorar'], ['/buscar', 'movil-comercio-buscar']]) {
      await pm.goto(`${BASE}${ruta}`, { waitUntil: 'networkidle' });
      await pm.waitForTimeout(500);
      await pm.screenshot({ path: path.join(DIR, `${nombre}.png`) });
      console.log(`ok ${nombre}`);
    }
  } else {
    console.error('movil: el login no entro');
  }
  await ctxM.close();
  await browser.close();
})();
