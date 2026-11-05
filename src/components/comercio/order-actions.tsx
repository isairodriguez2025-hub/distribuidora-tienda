"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";

export function CancelOrderButton({ orderId }: { orderId: string }) {
  const router = useRouter();
  const [enviando, setEnviando] = useState(false);
  const [confirmando, setConfirmando] = useState(false);

  async function cancelar() {
    setEnviando(true);
    try {
      await fetch(api(`/api/pedidos/${orderId}`), {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status: "CANCELADO" }),
      });
      router.refresh();
    } finally {
      setEnviando(false);
      setConfirmando(false);
    }
  }

  if (!confirmando) {
    return (
      <button
        type="button"
        onClick={() => setConfirmando(true)}
        className="rounded-full border border-line-2 bg-white px-3.5 py-1.5 text-[12px] font-semibold text-ink-2 transition hover:border-red-300 hover:text-red-700"
      >
        Cancelar pedido
      </button>
    );
  }

  return (
    <span className="inline-flex items-center gap-2">
      <button
        type="button"
        onClick={cancelar}
        disabled={enviando}
        className="rounded-full bg-red-600 px-3.5 py-1.5 text-[12px] font-semibold text-white transition hover:bg-red-700 disabled:opacity-60"
      >
        {enviando ? "Cancelando…" : "Confirmar cancelación"}
      </button>
      <button
        type="button"
        onClick={() => setConfirmando(false)}
        className="text-[12px] font-medium text-muted hover:text-ink"
      >
        No
      </button>
    </span>
  );
}
