import { NextResponse, type NextRequest } from "next/server";

/**
 * Comprobación optimista: si no hay cookie de sesión, manda al login.
 * La autorización real (rol y propiedad del recurso) se valida en cada
 * página y en cada ruta de API con `auth()`.
 */
export function proxy(request: NextRequest) {
  const hasSession =
    request.cookies.has("authjs.session-token") ||
    request.cookies.has("__Secure-authjs.session-token");

  if (!hasSession) {
    const base = process.env.NEXT_PUBLIC_BASE_PATH ?? "";
    const url = new URL(`${base}/login`, request.url);
    url.searchParams.set("next", request.nextUrl.pathname);
    return NextResponse.redirect(url);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/app/:path*"],
};
