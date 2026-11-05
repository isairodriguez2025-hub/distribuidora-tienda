/**
 * Cliente de autenticación sin depender de next-auth/react.
 *
 * La app se publica bajo un prefijo (/didi2), y el cliente de next-auth
 * resuelve su basePath en tiempo de compilación, lo que rompe las llamadas
 * cuando el sitio no vive en la raíz del dominio. Aquí se arma la URL con
 * NEXT_PUBLIC_BASE_PATH y se usa el flujo estándar de CSRF + callback.
 */

const BASE = process.env.NEXT_PUBLIC_BASE_PATH ?? "";

async function csrfToken(): Promise<string> {
  const res = await fetch(`${BASE}/api/auth/csrf`, { credentials: "include" });
  const data = (await res.json()) as { csrfToken: string };
  return data.csrfToken;
}

export async function login(email: string, password: string): Promise<boolean> {
  const token = await csrfToken();
  const body = new URLSearchParams({
    csrfToken: token,
    email,
    password,
    callbackUrl: "/app",
    json: "true",
  });

  const res = await fetch(`${BASE}/api/auth/callback/credentials`, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body,
    credentials: "include",
    redirect: "follow",
  });

  if (!res.ok) return false;
  const data = (await res.json().catch(() => ({}))) as { url?: string; error?: string };
  return !data.error;
}

export async function logout(): Promise<void> {
  try {
    const token = await csrfToken();
    await fetch(`${BASE}/api/auth/signout`, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ csrfToken: token, callbackUrl: "/" }),
      credentials: "include",
    });
  } finally {
    window.location.assign(`${BASE}/`);
  }
}

export function appUrl(path: string): string {
  return `${BASE}${path}`;
}
