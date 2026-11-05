"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowRight, Minus, Plus, ShoppingCart, Trash2 } from "lucide-react";
import { useCart } from "@/components/comercio/cart";
import { Button, EmptyState, money } from "@/components/ui";
import { api } from "@/lib/api";

export default function CarritoPage() {
  const { items, setQty, clear } = useCart();
  const [direccion, setDireccion] = useState("");
  const [enviando, setEnviando] = useState(false);
  const [error, setError] = useState("");
  const [folios, setFolios] = useState<string[] | null>(null);

  useEffect(() => {
    fetch(api("/api/me"))
      .then((r) => (r.ok ? r.json() : null))
      .then((d) => {
        if (d?.user?.address) setDireccion(d.user.address);
      })
      .catch(() => undefined);
  }, []);

  const subtotal = items.reduce((s, i) => s + i.price * i.qty, 0);
  const iva = Math.round(subtotal * 0.16 * 100) / 100;
  const total = Math.round((subtotal + iva) * 100) / 100;

  async function confirmar() {
    setError("");
    setEnviando(true);
    try {
      const res = await fetch(api("/api/pedidos"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          items: items.map((i) => ({ productId: i.productId, qty: i.qty })),
          address: direccion,
        }),
      });
      const data = await res.json();
      if (!res.ok) {
        setError(data.error ?? "No se pudo confirmar el pedido");
        return;
      }
      setFolios((data.orders as { folio: string }[]).map((o) => o.folio));
      clear();
    } catch {
      setError("No se pudo conectar con el servidor");
    } finally {
      setEnviando(false);
    }
  }

  if (folios) {
    return (
      <div className="card mx-auto max-w-lg p-8 text-center">
        <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-soft text-brand">
          <ShoppingCart size={22} strokeWidth={2} />
        </span>
        <h1 className="mt-4 text-[22px] font-semibold text-ink">Pedido confirmado</h1>
        <p className="mt-2 text-[13.5px] text-muted">
          Enviamos tu solicitud al mayorista. Puedes seguir el avance desde tus pedidos.
        </p>
        <div className="mt-4 flex flex-wrap justify-center gap-2">
          {folios.map((f) => (
            <span key={f} className="rounded-full border border-line bg-surface px-3 py-1 text-[12px] font-semibold text-ink">
              {f}
            </span>
          ))}
        </div>
        <Link
          href="/app/comercio/pedidos"
          className="mt-6 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13.5px] font-semibold text-white transition hover:bg-brand-2"
        >
          Ver mis pedidos
          <ArrowRight size={15} strokeWidth={2.2} />
        </Link>
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title="Tu carrito está vacío"
        description="Agrega productos desde el catálogo para armar tu pedido."
        action={
          <Link
            href="/app/comercio"
            className="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-[13.5px] font-semibold text-white transition hover:bg-brand-2"
          >
            Ir al catálogo
          </Link>
        }
      />
    );
  }

  return (
    <div>
      <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Carrito</h1>
      <p className="mt-1 text-[13.5px] text-muted">
        {items.length} {items.length === 1 ? "producto" : "productos"} · el pedido se divide por proveedor
      </p>

      <div className="mt-6 grid gap-5 lg:grid-cols-[1.4fr_0.6fr]">
        <div className="space-y-3">
          {items.map((item) => (
            <div key={item.productId} className="card flex flex-wrap items-center gap-4 p-4">
              <div className="min-w-0 flex-1">
                <p className="text-[14px] font-semibold text-ink">{item.name}</p>
                <p className="text-[12px] text-muted">
                  {item.supplierName} · {item.unit}
                </p>
                <p className="mt-1 text-[12.5px] text-ink-2">{money(item.price)} c/u</p>
              </div>

              <div className="flex items-center gap-1.5">
                <button
                  type="button"
                  aria-label="Quitar uno"
                  onClick={() => setQty(item.productId, item.qty - 1)}
                  className="flex h-8 w-8 items-center justify-center rounded-full border border-line text-ink-2 transition hover:border-brand-2 hover:text-brand"
                >
                  <Minus size={14} strokeWidth={2.2} />
                </button>
                <span className="w-8 text-center text-[13.5px] font-semibold text-ink">{item.qty}</span>
                <button
                  type="button"
                  aria-label="Agregar uno"
                  onClick={() => setQty(item.productId, item.qty + 1)}
                  className="flex h-8 w-8 items-center justify-center rounded-full border border-line text-ink-2 transition hover:border-brand-2 hover:text-brand"
                >
                  <Plus size={14} strokeWidth={2.2} />
                </button>
              </div>

              <div className="w-24 text-right text-[14px] font-semibold text-ink">
                {money(item.price * item.qty)}
              </div>

              <button
                type="button"
                aria-label="Eliminar"
                onClick={() => setQty(item.productId, 0)}
                className="text-muted transition hover:text-red-600"
              >
                <Trash2 size={16} strokeWidth={1.9} />
              </button>
            </div>
          ))}
        </div>

        <aside className="card h-fit p-5">
          <h2 className="text-[15px] font-semibold text-ink">Resumen</h2>

          <dl className="mt-4 space-y-2 text-[13px]">
            <div className="flex justify-between text-ink-2">
              <dt>Subtotal</dt>
              <dd>{money(subtotal)}</dd>
            </div>
            <div className="flex justify-between text-ink-2">
              <dt>IVA 16%</dt>
              <dd>{money(iva)}</dd>
            </div>
            <div className="flex justify-between border-t border-line pt-2 text-[15px] font-semibold text-ink">
              <dt>Total</dt>
              <dd>{money(total)}</dd>
            </div>
          </dl>

          <label className="mt-5 block">
            <span className="label">Dirección de entrega</span>
            <input
              className="field"
              value={direccion}
              onChange={(e) => setDireccion(e.target.value)}
              placeholder="Calle, número, colonia"
            />
          </label>

          {error && (
            <p className="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[12px] text-red-700">
              {error}
            </p>
          )}

          <Button className="mt-4 w-full" size="lg" onClick={confirmar} disabled={enviando || !direccion}>
            {enviando ? "Confirmando…" : "Confirmar pedido"}
          </Button>
          <p className="mt-2 text-center text-[11px] text-muted">
            El mayorista confirma existencia antes de preparar.
          </p>
        </aside>
      </div>
    </div>
  );
}
