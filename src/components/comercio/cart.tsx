"use client";

import { useCallback, useEffect, useState } from "react";
import { Check, Plus } from "lucide-react";

export type CartItem = {
  productId: string;
  name: string;
  price: number;
  unit: string;
  qty: number;
  supplierName: string;
};

const KEY = "didi2-cart";
const EVENT = "didi2-cart-change";

export function readCart(): CartItem[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(KEY);
    return raw ? (JSON.parse(raw) as CartItem[]) : [];
  } catch {
    return [];
  }
}

export function writeCart(items: CartItem[]) {
  window.localStorage.setItem(KEY, JSON.stringify(items));
  window.dispatchEvent(new Event(EVENT));
}

export function useCart() {
  const [items, setItems] = useState<CartItem[]>([]);

  const sync = useCallback(() => setItems(readCart()), []);

  useEffect(() => {
    sync();
    window.addEventListener(EVENT, sync);
    window.addEventListener("storage", sync);
    return () => {
      window.removeEventListener(EVENT, sync);
      window.removeEventListener("storage", sync);
    };
  }, [sync]);

  const add = useCallback((item: Omit<CartItem, "qty">, qty = 1) => {
    const actual = readCart();
    const existente = actual.find((i) => i.productId === item.productId);
    if (existente) existente.qty += qty;
    else actual.push({ ...item, qty });
    writeCart(actual);
  }, []);

  const setQty = useCallback((productId: string, qty: number) => {
    const actual = readCart()
      .map((i) => (i.productId === productId ? { ...i, qty: Math.max(0, qty) } : i))
      .filter((i) => i.qty > 0);
    writeCart(actual);
  }, []);

  const clear = useCallback(() => writeCart([]), []);

  return { items, add, setQty, clear };
}

export function AddToCartButton({
  product,
}: {
  product: { id: string; name: string; price: number; unit: string; supplierName: string };
}) {
  const { add } = useCart();
  const [listo, setListo] = useState(false);

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => {
          add({
            productId: product.id,
            name: product.name,
            price: product.price,
            unit: product.unit,
            supplierName: product.supplierName,
          });
          setListo(true);
          window.setTimeout(() => setListo(false), 1600);
        }}
        className="inline-flex w-full items-center justify-center gap-1.5 rounded-full border border-line-2 bg-white px-3.5 py-2 text-[12.5px] font-semibold text-ink transition hover:border-brand-2 hover:text-brand active:scale-[0.98]"
      >
        {listo ? <Check size={14} strokeWidth={2.4} className="text-brand" /> : <Plus size={14} strokeWidth={2.4} />}
        {listo ? "Agregado" : "Agregar"}
      </button>
    </div>
  );
}
