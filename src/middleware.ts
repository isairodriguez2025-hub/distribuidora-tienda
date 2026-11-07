import { NextResponse } from 'next/server';
import { getToken } from 'next-auth/jwt';

/**
 * Protege las rutas privadas ANTES de que se rendericen.
 *
 * Esconder un botón no protege nada: cualquiera puede escribir la dirección a
 * mano. Este middleware corta el paso en el servidor, en cada petición.
 *
 * Usa getToken (JWT) en lugar de auth() porque el middleware corre en el edge
 * runtime, donde Prisma no puede ejecutarse.
 */
const RUTAS_ADMIN = ['/admin'];
const RUTAS_REPARTIDOR = ['/repartidor'];

export async function middleware(req: Request & { nextUrl: URL; cookies: { get(name: string): { value: string } | undefined } }) {
  const { pathname } = req.nextUrl;

  const token = await getToken({
    req: req as never,
    secret: process.env.AUTH_SECRET,
    salt: 'authjs.session-token',
  });

  if (!token) {
    const url = new URL('/login', req.nextUrl.origin);
    url.searchParams.set('next', pathname);
    return NextResponse.redirect(url);
  }

  const rol = String((token as { role?: string }).role ?? '');

  if (RUTAS_ADMIN.some((r) => pathname.startsWith(r)) && rol !== 'ADMIN') {
    return NextResponse.redirect(new URL('/', req.nextUrl.origin));
  }

  if (RUTAS_REPARTIDOR.some((r) => pathname.startsWith(r)) && !['ADMIN', 'DRIVER'].includes(rol)) {
    return NextResponse.redirect(new URL('/', req.nextUrl.origin));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/admin/:path*', '/repartidor/:path*'],
};
