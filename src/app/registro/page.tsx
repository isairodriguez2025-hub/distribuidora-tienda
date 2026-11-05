"use client";

import { Suspense, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { ArrowRight, Boxes, Check, Store, Truck } from "lucide-react";
import { SiteHeader } from "@/components/site-header";
import { SiteFooter } from "@/components/site-footer";
import { Button, Field } from "@/components/ui";
import { api } from "@/lib/api";

type Rol = "COMERCIO" | "MAYORISTA" | "REPARTIDOR";

const OPCIONES: { id: Rol; label: string; desc: string; icon: typeof Store }[] = [
  { id: "COMERCIO", label: "Comercio", desc: "Compro insumos para mi negocio", icon: Store },
  { id: "MAYORISTA", label: "Mayorista", desc: "Vendo al mayoreo y surto pedidos", icon: Boxes },
  { id: "REPARTIDOR", label: "Repartidor", desc: "Hago entregas y quiero viajes", icon: Truck },
];

function RegistroForm() {
  const params = useSearchParams();
  const inicial = params.get("rol");
  const [rol, setRol] = useState<Rol>(
    inicial === "MAYORISTA" || inicial === "REPARTIDOR" ? inicial : "COMERCIO",
  );
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    phone: "",
    businessName: "",
    rfc: "",
    city: "",
    address: "",
  });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [listo, setListo] = useState(false);

  function set(campo: keyof typeof form, valor: string) {
    setForm((prev) => ({ ...prev, [campo]: valor }));
  }

  async function enviar(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const res = await fetch(api("/api/registro"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...form, role: rol }),
      });
      const data = await res.json();
      if (!res.ok) {
        setError(data.error ?? "No se pudo crear la cuenta");
        return;
      }
      setListo(true);
    } catch {
      setError("No se pudo conectar con el servidor");
    } finally {
      setLoading(false);
    }
  }

  if (listo) {
    return (
      <div className="card mx-auto max-w-lg p-8 text-center">
        <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-soft text-brand">
          <Check size={22} strokeWidth={2.4} />
        </span>
        <h1 className="mt-4 text-[22px] font-semibold text-ink">Cuenta creada</h1>
        <p className="mt-2 text-[13.5px] leading-relaxed text-muted">
          Registramos <b className="text-ink">{form.email}</b>. Tu cuenta queda en revisión de
          verificación (KYC) y podrás operar en cuanto se apruebe.
        </p>
        <Link
          href="/login"
          className="mt-6 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13.5px] font-semibold text-white transition hover:bg-brand-2"
        >
          Ir a iniciar sesión
          <ArrowRight size={15} strokeWidth={2.2} />
        </Link>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-3xl">
      <div className="text-center">
        <span className="eyebrow">Registro</span>
        <h1 className="mt-4 text-[32px] font-semibold tracking-[-0.02em] text-ink">
          Crea tu cuenta
        </h1>
        <p className="mx-auto mt-2 max-w-xl text-[14px] text-muted">
          Elige el tipo de cuenta. Cada una tiene su propio panel y sus propias herramientas.
        </p>
      </div>

      <div className="mt-8 grid gap-3 sm:grid-cols-3">
        {OPCIONES.map((op) => {
          const Icon = op.icon;
          const activo = rol === op.id;
          return (
            <button
              key={op.id}
              type="button"
              onClick={() => setRol(op.id)}
              aria-pressed={activo}
              className={`rounded-[14px] border p-4 text-left transition ${
                activo
                  ? "border-brand-2 bg-brand-soft ring-2 ring-[#cfe3d8]"
                  : "border-line bg-white hover:border-line-2"
              }`}
            >
              <span className={`flex h-9 w-9 items-center justify-center rounded-lg ${activo ? "bg-brand text-white" : "bg-surface-2 text-ink-2"}`}>
                <Icon size={18} strokeWidth={1.9} />
              </span>
              <p className="mt-3 text-[14px] font-semibold text-ink">{op.label}</p>
              <p className="mt-0.5 text-[12px] leading-snug text-muted">{op.desc}</p>
            </button>
          );
        })}
      </div>

      <form onSubmit={enviar} className="card mt-6 p-6">
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Nombre completo">
            <input
              className="field"
              value={form.name}
              onChange={(e) => set("name", e.target.value)}
              placeholder="María Fernanda Ríos"
              required
            />
          </Field>
          <Field label="Correo electrónico">
            <input
              className="field"
              type="email"
              value={form.email}
              onChange={(e) => set("email", e.target.value)}
              placeholder="tu@negocio.mx"
              required
            />
          </Field>
          <Field label="Contraseña" hint="Mínimo 8 caracteres">
            <input
              className="field"
              type="password"
              value={form.password}
              onChange={(e) => set("password", e.target.value)}
              minLength={8}
              required
            />
          </Field>
          <Field label="Teléfono">
            <input
              className="field"
              value={form.phone}
              onChange={(e) => set("phone", e.target.value)}
              placeholder="55 1234 5678"
              required
            />
          </Field>
          <Field label={rol === "REPARTIDOR" ? "Nombre o razón social" : "Nombre del negocio"}>
            <input
              className="field"
              value={form.businessName}
              onChange={(e) => set("businessName", e.target.value)}
              placeholder={rol === "REPARTIDOR" ? "Transportes LM" : "Cafetería La Esquina"}
              required
            />
          </Field>
          <Field label="RFC" hint="Opcional para comercio">
            <input
              className="field"
              value={form.rfc}
              onChange={(e) => set("rfc", e.target.value.toUpperCase())}
              placeholder="XAXX010101000"
            />
          </Field>
          <Field label="Ciudad">
            <input
              className="field"
              value={form.city}
              onChange={(e) => set("city", e.target.value)}
              placeholder="CDMX"
              required
            />
          </Field>
          <Field label="Dirección">
            <input
              className="field"
              value={form.address}
              onChange={(e) => set("address", e.target.value)}
              placeholder="Calle, número, colonia"
              required
            />
          </Field>
        </div>

        {error && (
          <p className="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[12.5px] text-red-700">
            {error}
          </p>
        )}

        <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
          <p className="max-w-md text-[11.5px] leading-relaxed text-muted">
            Al crear tu cuenta aceptas el aviso de privacidad y los términos del servicio. La
            verificación de datos se realiza antes de operar.
          </p>
          <Button type="submit" size="lg" disabled={loading}>
            {loading ? "Creando cuenta…" : "Crear cuenta"}
            {!loading && <ArrowRight size={16} strokeWidth={2.2} />}
          </Button>
        </div>
      </form>

      <p className="mt-5 text-center text-[13px] text-muted">
        ¿Ya tienes cuenta?{" "}
        <Link href="/login" className="font-semibold text-brand hover:underline">
          Inicia sesión
        </Link>
      </p>
    </div>
  );
}

export default function RegistroPage() {
  return (
    <div className="min-h-screen bg-white">
      <SiteHeader />
      <main className="wrap py-14">
        <Suspense fallback={<p className="text-center text-[13px] text-muted">Cargando…</p>}>
          <RegistroForm />
        </Suspense>
      </main>
      <SiteFooter />
    </div>
  );
}
