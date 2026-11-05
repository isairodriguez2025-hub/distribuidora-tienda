"use client";

import { Suspense, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { login as iniciarSesion } from "@/lib/auth-client";
import { ArrowRight, Lock } from "lucide-react";
import { SiteHeader } from "@/components/site-header";
import { SiteFooter } from "@/components/site-footer";
import { Button, Field } from "@/components/ui";

const DEMO = [
  ["comercio@didi2.mx", "Comercio"],
  ["mayorista@didi2.mx", "Mayorista"],
  ["repartidor@didi2.mx", "Repartidor"],
  ["admin@didi2.mx", "Administración"],
];

function LoginForm() {
  const params = useSearchParams();
  const next = params.get("next");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function enviar(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const ok = await iniciarSesion(email, password);
      if (!ok) {
        setError("Correo o contraseña incorrectos");
        return;
      }
      // Navegación completa: garantiza que la cookie de sesión viaje en la
      // siguiente petición (con router.push el proxy puede no verla todavía).
      const base = process.env.NEXT_PUBLIC_BASE_PATH ?? "";
      window.location.assign(base + (next && next.startsWith("/app") ? next : "/app"));
    } catch {
      setError("No se pudo iniciar sesión");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto grid max-w-4xl gap-10 py-6 lg:grid-cols-[1fr_0.9fr] lg:items-center">
      <div>
        <span className="eyebrow">
          <Lock size={12} strokeWidth={2.2} />
          Acceso a tu panel
        </span>
        <h1 className="mt-4 text-[32px] font-semibold tracking-[-0.02em] text-ink">
          Entra a tu cuenta
        </h1>
        <p className="mt-2 max-w-md text-[14px] leading-relaxed text-muted">
          Cada cuenta abre su propio panel: catálogo y pedidos, productos y ventas, viajes o
          administración.
        </p>

        <form onSubmit={enviar} className="card mt-6 p-6">
          <div className="space-y-4">
            <Field label="Correo electrónico">
              <input
                className="field"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="tu@negocio.mx"
                autoComplete="email"
                required
              />
            </Field>
            <Field label="Contraseña">
              <input
                className="field"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                autoComplete="current-password"
                required
              />
            </Field>
          </div>

          {error && (
            <p className="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[12.5px] text-red-700">
              {error}
            </p>
          )}

          <Button type="submit" size="lg" className="mt-5 w-full" disabled={loading}>
            {loading ? "Entrando…" : "Iniciar sesión"}
            {!loading && <ArrowRight size={16} strokeWidth={2.2} />}
          </Button>

          <p className="mt-4 text-center text-[12.5px] text-muted">
            ¿No tienes cuenta?{" "}
            <Link href="/registro" className="font-semibold text-brand hover:underline">
              Crear una
            </Link>
          </p>
        </form>
      </div>

      <aside className="card bg-surface p-6">
        <p className="text-[12px] font-semibold uppercase tracking-[0.08em] text-muted">
          Cuentas de demostración
        </p>
        <p className="mt-1 text-[12.5px] text-muted">
          Contraseña para todas: <b className="text-ink">demo1234</b>
        </p>
        <ul className="mt-4 space-y-2">
          {DEMO.map(([correo, rol]) => (
            <li key={correo}>
              <button
                type="button"
                onClick={() => {
                  setEmail(correo);
                  setPassword("demo1234");
                }}
                className="flex w-full items-center justify-between gap-3 rounded-lg border border-line bg-white px-3 py-2 text-left transition hover:border-brand-2"
              >
                <span className="min-w-0">
                  <span className="block truncate text-[12.5px] font-medium text-ink">{correo}</span>
                  <span className="block text-[11px] text-muted">{rol}</span>
                </span>
                <span className="text-[11px] font-semibold text-brand">Usar</span>
              </button>
            </li>
          ))}
        </ul>
      </aside>
    </div>
  );
}

export default function LoginPage() {
  return (
    <div className="min-h-screen bg-white">
      <SiteHeader />
      <main className="wrap py-12">
        <Suspense fallback={<p className="text-center text-[13px] text-muted">Cargando…</p>}>
          <LoginForm />
        </Suspense>
      </main>
      <SiteFooter />
    </div>
  );
}
