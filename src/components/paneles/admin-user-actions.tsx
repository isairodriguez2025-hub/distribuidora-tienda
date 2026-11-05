"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";

export function AdminUserActions({
  userId,
  kycStatus,
  active,
}: {
  userId: string;
  kycStatus: string;
  active: boolean;
}) {
  const router = useRouter();
  const [enviando, setEnviando] = useState("");

  async function actualizar(payload: { kycStatus?: string; active?: boolean }, marca: string) {
    setEnviando(marca);
    try {
      await fetch(api(`/api/admin/usuarios/${userId}`), {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      router.refresh();
    } finally {
      setEnviando("");
    }
  }

  return (
    <div className="flex flex-wrap items-center gap-1.5">
      {kycStatus !== "APROBADO" && (
        <button
          type="button"
          onClick={() => actualizar({ kycStatus: "APROBADO" }, "aprobar")}
          disabled={Boolean(enviando)}
          className="rounded-full bg-brand px-3 py-1 text-[11.5px] font-semibold text-white transition hover:bg-brand-2 disabled:opacity-60"
        >
          {enviando === "aprobar" ? "…" : "Aprobar"}
        </button>
      )}
      {kycStatus !== "RECHAZADO" && (
        <button
          type="button"
          onClick={() => actualizar({ kycStatus: "RECHAZADO" }, "rechazar")}
          disabled={Boolean(enviando)}
          className="rounded-full border border-line-2 bg-white px-3 py-1 text-[11.5px] font-semibold text-ink-2 transition hover:border-red-300 hover:text-red-700 disabled:opacity-60"
        >
          {enviando === "rechazar" ? "…" : "Rechazar"}
        </button>
      )}
      <button
        type="button"
        onClick={() => actualizar({ active: !active }, "activo")}
        disabled={Boolean(enviando)}
        className="rounded-full border border-line-2 bg-white px-3 py-1 text-[11.5px] font-semibold text-ink-2 transition hover:border-brand-2 hover:text-brand disabled:opacity-60"
      >
        {enviando === "activo" ? "…" : active ? "Desactivar" : "Activar"}
      </button>
    </div>
  );
}
