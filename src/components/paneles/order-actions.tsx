"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";

async function cambiar(orderId: string, status: string) {
  await fetch(api(`/api/pedidos/${orderId}`), {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ status }),
  });
}

export function MayoristaOrderActions({ orderId, status }: { orderId: string; status: string }) {
  const router = useRouter();
  const [enviando, setEnviando] = useState("");

  async function accion(nuevo: string) {
    setEnviando(nuevo);
    try {
      await cambiar(orderId, nuevo);
      router.refresh();
    } finally {
      setEnviando("");
    }
  }

  return (
    <div className="flex flex-wrap gap-2">
      {status === "PENDIENTE" && (
        <button
          type="button"
          onClick={() => accion("ACEPTADO")}
          disabled={Boolean(enviando)}
          className="rounded-full bg-brand px-3.5 py-1.5 text-[12px] font-semibold text-white transition hover:bg-brand-2 disabled:opacity-60"
        >
          {enviando === "ACEPTADO" ? "Aceptando…" : "Aceptar"}
        </button>
      )}
      {status === "ACEPTADO" && (
        <button
          type="button"
          onClick={() => accion("PREPARANDO")}
          disabled={Boolean(enviando)}
          className="rounded-full bg-brand px-3.5 py-1.5 text-[12px] font-semibold text-white transition hover:bg-brand-2 disabled:opacity-60"
        >
          {enviando === "PREPARANDO" ? "Guardando…" : "Marcar preparando"}
        </button>
      )}
      {(status === "PENDIENTE" || status === "ACEPTADO") && (
        <button
          type="button"
          onClick={() => accion("CANCELADO")}
          disabled={Boolean(enviando)}
          className="rounded-full border border-line-2 bg-white px-3.5 py-1.5 text-[12px] font-semibold text-ink-2 transition hover:border-red-300 hover:text-red-700 disabled:opacity-60"
        >
          Rechazar
        </button>
      )}
    </div>
  );
}

const ESTADOS = ["PENDIENTE", "ACEPTADO", "PREPARANDO", "EN_CAMINO", "ENTREGADO", "CANCELADO"];

export function AdminOrderStatus({ orderId, status }: { orderId: string; status: string }) {
  const router = useRouter();
  const [valor, setValor] = useState(status);
  const [enviando, setEnviando] = useState(false);

  async function cambiarEstado(nuevo: string) {
    setValor(nuevo);
    setEnviando(true);
    try {
      await cambiar(orderId, nuevo);
      router.refresh();
    } finally {
      setEnviando(false);
    }
  }

  return (
    <select
      value={valor}
      disabled={enviando}
      onChange={(e) => cambiarEstado(e.target.value)}
      className="rounded-lg border border-line-2 bg-white px-2 py-1.5 text-[12px] font-medium text-ink"
    >
      {ESTADOS.map((e) => (
        <option key={e} value={e}>{e}</option>
      ))}
    </select>
  );
}
