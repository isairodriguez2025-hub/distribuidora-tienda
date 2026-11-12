/**
 * Prueba de extremo a extremo de Didi2 B2B.
 * Requiere el servidor en http://localhost:3100 y Google Chrome instalado.
 *
 *   node tests/e2e.mjs
 */
import { chromium } from "playwright";
import { mkdirSync } from "node:fs";

const BASE = process.env.BASE_URL ?? "http://localhost:3100";
// Carpeta de capturas: configurable, para que la prueba corra en cualquier máquina.
const SHOTS = process.env.SHOTS_DIR ?? "./tests/capturas";
mkdirSync(SHOTS, { recursive: true });

const resultados = [];
function ok(nombre, detalle = "") {
  resultados.push({ nombre, estado: "OK", detalle });
  console.log(`  ✓ ${nombre}${detalle ? ` — ${detalle}` : ""}`);
}
function fallo(nombre, error) {
  resultados.push({ nombre, estado: "FALLO", detalle: String(error).slice(0, 200) });
  console.log(`  ✗ ${nombre} — ${String(error).slice(0, 200)}`);
}

// Usa el Chrome del sistema si existe; si no, el Chromium de Playwright o el
// que se indique con CHROME_PATH (útil en Linux y en contenedores).
const browser = await chromium.launch(
  process.env.CHROME_PATH
    ? { executablePath: process.env.CHROME_PATH }
    : { channel: "chrome" },
);
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();

async function login(email, password = "demo1234") {
  await page.goto(`${BASE}/login`, { waitUntil: "networkidle" });
  await page.fill('input[type="email"]', email);
  await page.fill('input[type="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL(/\/app\//, { timeout: 20000 });
  await page.waitForLoadState("networkidle");
}

async function logout() {
  const btn = page.getByRole("button", { name: /cerrar sesión/i });
  if (await btn.count()) {
    await btn.first().click();
    await page.waitForURL((u) => !u.pathname.startsWith("/app"), { timeout: 20000 });
  } else {
    await context.clearCookies();
  }
}

console.log("\n1. Landing pública");
try {
  await page.goto(BASE, { waitUntil: "networkidle" });
  const h1 = (await page.locator("h1").first().innerText()).replace(/\s+/g, " ");
  if (!/Surtes tu negocio/i.test(h1)) throw new Error(`h1 inesperado: ${h1}`);
  const roles = await page.locator("article").count();
  if (roles < 3) throw new Error("faltan las tarjetas de rol");
  await page.screenshot({ path: `${SHOTS}/e2e-01-landing.png` });
  ok("landing carga con hero y roles", h1.slice(0, 60));
} catch (e) {
  fallo("landing", e);
}

console.log("\n2. Registro de un comercio nuevo");
const correo = `e2e-${Date.now()}@didi2.mx`;
try {
  await page.goto(`${BASE}/registro`, { waitUntil: "networkidle" });
  await page.getByRole("button", { name: /Comercio/ }).first().click();
  await page.fill('input[placeholder="María Fernanda Ríos"]', "Comercio de Prueba");
  await page.fill('input[type="email"]', correo);
  await page.fill('input[type="password"]', "demo1234");
  await page.fill('input[placeholder="55 1234 5678"]', "5512340000");
  await page.fill('input[placeholder="Cafetería La Esquina"]', "Abarrotes E2E");
  await page.fill('input[placeholder="CDMX"]', "CDMX");
  await page.fill('input[placeholder="Calle, número, colonia"]', "Calle Prueba 123");
  await page.click('button[type="submit"]');
  await page.waitForSelector("text=Cuenta creada", { timeout: 20000 });
  await page.screenshot({ path: `${SHOTS}/e2e-02-registro.png` });
  ok("registro crea la cuenta", correo);
} catch (e) {
  fallo("registro", e);
}

console.log("\n3. Acceso del comercio y compra");
try {
  await login("comercio@didi2.mx");
  if (!page.url().includes("/app/comercio")) throw new Error(`URL inesperada: ${page.url()}`);
  await page.screenshot({ path: `${SHOTS}/e2e-03-comercio.png` });

  const agregar = page.getByRole("button", { name: /agregar/i }).first();
  await agregar.click();
  await page.waitForTimeout(600);

  await page.goto(`${BASE}/app/comercio/carrito`, { waitUntil: "networkidle" });
  const confirmar = page.getByRole("button", { name: /confirmar pedido/i });
  if (!(await confirmar.count())) throw new Error("no hay botón de confirmar pedido");
  await confirmar.click();
  await page.waitForTimeout(2500);
  await page.screenshot({ path: `${SHOTS}/e2e-04-carrito.png` });
  ok("carrito y confirmación de pedido");
} catch (e) {
  fallo("compra del comercio", e);
}

console.log("\n4. El mayorista acepta el pedido");
try {
  await logout();
  await login("mayorista@didi2.mx");
  await page.goto(`${BASE}/app/mayorista/pedidos`, { waitUntil: "networkidle" });
  const aceptar = page.getByRole("button", { name: /^aceptar$/i }).first();
  if (!(await aceptar.count())) throw new Error("no hay pedidos pendientes por aceptar");
  await aceptar.click();
  await page.waitForTimeout(2000);
  await page.screenshot({ path: `${SHOTS}/e2e-05-mayorista.png` });
  ok("pedido aceptado por el mayorista");
} catch (e) {
  fallo("aceptación del mayorista", e);
}

console.log("\n5. El repartidor toma un viaje");
try {
  await logout();
  await login("repartidor@didi2.mx");
  const tomar = page.getByRole("button", { name: /aceptar viaje/i }).first();
  if (!(await tomar.count())) throw new Error("no hay viajes disponibles");
  await tomar.click();
  await page.waitForTimeout(2000);
  await page.screenshot({ path: `${SHOTS}/e2e-06-repartidor.png` });
  ok("viaje aceptado por el repartidor");
} catch (e) {
  fallo("viaje del repartidor", e);
}

console.log("\n6. Administración aprueba el KYC");
try {
  await logout();
  await login("admin@didi2.mx");
  await page.goto(`${BASE}/app/admin/usuarios`, { waitUntil: "networkidle" });
  const fila = page.locator("tr", { hasText: correo });
  if (!(await fila.count())) throw new Error("el usuario nuevo no aparece en la tabla");
  const aprobar = fila.getByRole("button", { name: /aprobar/i }).first();
  if (await aprobar.count()) await aprobar.click();
  await page.waitForTimeout(1500);
  await page.screenshot({ path: `${SHOTS}/e2e-07-admin.png` });
  ok("KYC aprobado desde administración");
} catch (e) {
  fallo("aprobación de KYC", e);
}

console.log("\n7. Aislamiento por rol");
try {
  await logout();
  await login("comercio@didi2.mx");
  await page.goto(`${BASE}/app/admin/usuarios`, { waitUntil: "networkidle" });
  const url = page.url();
  const texto = await page.locator("body").innerText();
  const bloqueado = !url.includes("/app/admin") || /no autorizado|403|acceso/i.test(texto);
  if (!bloqueado) throw new Error("un comercio pudo abrir el panel de administración");
  ok("un comercio no accede al panel de administración", url.replace(BASE, ""));
} catch (e) {
  fallo("aislamiento por rol", e);
}

await browser.close();

const fallos = resultados.filter((r) => r.estado === "FALLO");
console.log(`\nResultado: ${resultados.length - fallos.length}/${resultados.length} pruebas correctas`);
if (fallos.length) {
  console.log("Fallos:");
  for (const f of fallos) console.log(` - ${f.nombre}: ${f.detalle}`);
  process.exit(1);
}
