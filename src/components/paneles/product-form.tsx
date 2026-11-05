"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Plus } from "lucide-react";
import { Button, Field } from "@/components/ui";
import { api } from "@/lib/api";

const CATEGORIAS = ["Abarrotes", "Limpieza", "Herrería"];

export function ProductForm() {
  const router = useRouter();
  const [abierto, setAbierto] = useState(false);
  const [error, setError] = useState("");
  const [enviando, setEnviando] = useState(false);
  const [form, setForm] = useState({
    name: "",
    category: "Abarrotes",
    unit: "caja con 12",
    price: "",
    stock: "",
    minQty: "1",
    multiple: "1",
  });

  function set(campo: keyof typeof form, valor: string) {
    setForm((prev) => ({ ...prev, [campo]: valor }));
  }

  async function enviar(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setEnviando(true);
    try {
      const res = await fetch(api("/api/productos"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          ...form,
          price: Number(form.price),
          stock: Number(form.stock),
          minQty: Number(form.minQty),
          multiple: Number(form.multiple),
        }),
      });
      const data = await res.json();
      if (!res.ok) {
        setError(data.error ?? "No se pudo guardar el producto");
        return;
      }
      setForm({ name: "", category: "Abarrotes", unit: "caja con 12", price: "", stock: "", minQty: "1", multiple: "1" });
      setAbierto(false);
      router.refresh();
    } catch {
      setError("No se pudo conectar con el servidor");
    } finally {
      setEnviando(false);
    }
  }

  if (!abierto) {
    return (
      <Button onClick={() => setAbierto(true)} size="sm">
        <Plus size={15} strokeWidth={2.4} />
        Nuevo producto
      </Button>
    );
  }

  return (
    <form onSubmit={enviar} className="card mt-4 w-full p-5">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Field label="Nombre">
          <input className="field" value={form.name} onChange={(e) => set("name", e.target.value)} required />
        </Field>
        <Field label="Categoría">
          <select className="field" value={form.category} onChange={(e) => set("category", e.target.value)}>
            {CATEGORIAS.map((c) => (
              <option key={c} value={c}>{c}</option>
            ))}
          </select>
        </Field>
        <Field label="Presentación">
          <input className="field" value={form.unit} onChange={(e) => set("unit", e.target.value)} required />
        </Field>
        <Field label="Precio (MXN)">
          <input className="field" type="number" min="0" step="0.01" value={form.price} onChange={(e) => set("price", e.target.value)} required />
        </Field>
        <Field label="Existencia">
          <input className="field" type="number" min="0" value={form.stock} onChange={(e) => set("stock", e.target.value)} required />
        </Field>
        <Field label="Múltiplo de venta">
          <input className="field" type="number" min="1" value={form.multiple} onChange={(e) => set("multiple", e.target.value)} />
        </Field>
      </div>

      {error && (
        <p className="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[12px] text-red-700">{error}</p>
      )}

      <div className="mt-4 flex gap-2">
        <Button type="submit" size="sm" disabled={enviando}>
          {enviando ? "Guardando…" : "Guardar producto"}
        </Button>
        <Button type="button" variant="ghost" size="sm" onClick={() => setAbierto(false)}>
          Cancelar
        </Button>
      </div>
    </form>
  );
}
