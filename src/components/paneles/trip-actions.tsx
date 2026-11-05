"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";

export function TripActions({
  deliveryId,
  status,
  modo,
}: {
  deliveryId: string;
  status: string;
  modo: "disponible" | "asignado";
}) {
  const router = useRouter();
  const [enviando, setEnviando] = useState("");

  async function accion(nombre: "aceptar" | "recoger" | "entregar") {
    setEnviando(nombre);
    try {
      await fetch(api(`/api/viajes/${deliveryId}`), {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ accion: nombre }),
      });
      router.refresh();
    } finally {
      setEnviando("");
    }
  }

  if (modo === "disponible") {
    return (
      <button
        type="button"
        onClick={() => accion("aceptar")}
        disabled={Boolean(enviando)}
        className="rounded-full bg-brand px-4 py-2 text-[12.5px] font-semibold text-white transition hover:bg-brand-2 disabled:opacity-60"
      >
        {enviando ? "Aceptando…" : "Aceptar viaje"}
      </button>
    );
  }

  return (
    <div className="flex flex-wrap gap-2">
      {status === "ASIGNADO" && (
        <button
          type="button"
          onClick={() => accion("recoger")}
          disabled={Boolean(enviando)}
          className="rounded-full bg-brand px-4 py-2 text-[12.5px] font-semibold text-white transition hover:bg-brand-2 disabled:opacity-60"
        >
          {enviando === "recoger" ? "Guardando…" : "Marcar recogido"}
        </button>
      )}
      {status === "RECOGIDO" && (
        <button
          type="button"
          onClick={() => accion("entregar")}
          disabled={Boolean(enviando)}
          className="rounded-full bg-brand px-4 py-2 text-[12.5px] font-semibold text-white transition hover:bg-brand-2 disabled:opacity-60"
        >
          {enviando === "entregar" ? "Guardando…" : "Marcar entregado"}
        </button>
      )}
    </div>
  );
}
