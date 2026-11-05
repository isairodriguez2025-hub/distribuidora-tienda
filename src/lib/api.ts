/**
 * Prefija las rutas de API con el basePath del sitio.
 * Necesario cuando la app se publica bajo un prefijo (por ejemplo /didi2):
 * las llamadas desde el navegador no heredan el basePath de Next.
 */
const BASE = process.env.NEXT_PUBLIC_BASE_PATH ?? "";

export function api(path: string): string {
  return `${BASE}${path}`;
}
