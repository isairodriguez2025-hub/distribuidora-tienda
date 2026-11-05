import Link from "next/link";
import { Package, Search, Sparkles, Wrench } from "lucide-react";
import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";
import { AddToCartButton } from "@/components/comercio/cart";
import { Badge, EmptyState, money } from "@/components/ui";

const ICONOS: Record<string, typeof Package> = {
  Abarrotes: Package,
  Limpieza: Sparkles,
  Herrería: Wrench,
};

export default async function CatalogoPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; categoria?: string }>;
}) {
  const session = await auth();
  const { q = "", categoria = "" } = await searchParams;

  const [productos, categorias] = await Promise.all([
    prisma.product.findMany({
      where: {
        active: true,
        ...(categoria ? { category: categoria } : {}),
        ...(q ? { name: { contains: q } } : {}),
      },
      include: { supplier: { select: { businessName: true, city: true } } },
      orderBy: [{ category: "asc" }, { name: "asc" }],
      take: 60,
    }),
    prisma.product.findMany({
      where: { active: true },
      select: { category: true },
      distinct: ["category"],
      orderBy: { category: "asc" },
    }),
  ]);

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Catálogo</h1>
          <p className="mt-1 text-[13.5px] text-muted">
            {session?.user.businessName || "Tu comercio"} · {productos.length} productos de mayoristas verificados
          </p>
        </div>
        <Link
          href="/app/comercio/carrito"
          className="inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-brand-2"
        >
          Ver carrito
        </Link>
      </div>

      <form className="mt-6 flex flex-wrap items-center gap-2" action="/app/comercio">
        <div className="relative min-w-[240px] flex-1">
          <Search size={16} strokeWidth={2} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input
            name="q"
            defaultValue={q}
            placeholder="Buscar producto, marca o presentación"
            className="field pl-9"
          />
        </div>
        {categoria && <input type="hidden" name="categoria" value={categoria} />}
        <button
          type="submit"
          className="rounded-full border border-line-2 bg-white px-4 py-2.5 text-[13px] font-semibold text-ink transition hover:border-brand-2 hover:text-brand"
        >
          Buscar
        </button>
      </form>

      <div className="mt-4 flex flex-wrap gap-2">
        <Link
          href={`/app/comercio${q ? `?q=${encodeURIComponent(q)}` : ""}`}
          className={`rounded-full border px-3 py-1.5 text-[12.5px] font-medium transition ${
            categoria === ""
              ? "border-brand-2 bg-brand-soft text-brand"
              : "border-line bg-white text-ink-2 hover:border-line-2"
          }`}
        >
          Todas
        </Link>
        {categorias.map((c) => (
          <Link
            key={c.category}
            href={`/app/comercio?categoria=${encodeURIComponent(c.category)}${q ? `&q=${encodeURIComponent(q)}` : ""}`}
            className={`rounded-full border px-3 py-1.5 text-[12.5px] font-medium transition ${
              categoria === c.category
                ? "border-brand-2 bg-brand-soft text-brand"
                : "border-line bg-white text-ink-2 hover:border-line-2"
            }`}
          >
            {c.category}
          </Link>
        ))}
      </div>

      {productos.length === 0 ? (
        <div className="mt-8">
          <EmptyState
            title="Sin resultados"
            description="No encontramos productos con esos filtros. Prueba con otra palabra o quita la categoría."
          />
        </div>
      ) : (
        <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {productos.map((p) => {
            const Icon = ICONOS[p.category] ?? Package;
            const bajo = p.stock < 10;
            return (
              <article key={p.id} className="card flex flex-col overflow-hidden">
                <div className="flex h-[120px] items-center justify-center border-b border-line bg-surface">
                  <Icon size={40} strokeWidth={1.4} className="text-[#9aa89f]" />
                </div>
                <div className="flex flex-1 flex-col p-4">
                  <div className="flex items-center justify-between gap-2">
                    <span className="text-[10.5px] font-semibold uppercase tracking-[0.08em] text-muted">
                      {p.category}
                    </span>
                    <Badge tone={bajo ? "accent" : "brand"}>
                      {bajo ? `Quedan ${p.stock}` : `${p.stock} disponibles`}
                    </Badge>
                  </div>
                  <h3 className="mt-1.5 text-[14.5px] font-semibold leading-snug text-ink">{p.name}</h3>
                  <p className="mt-0.5 text-[12px] text-muted">
                    {p.supplier.businessName} · {p.supplier.city}
                  </p>
                  <p className="mt-1 text-[12px] text-muted">{p.unit}</p>

                  <div className="mt-3 flex items-baseline gap-1.5">
                    <b className="text-[17px] font-semibold text-ink">{money(p.price)}</b>
                    <small className="text-[11.5px] text-muted">por {p.unit}</small>
                  </div>

                  <div className="mt-3">
                    <AddToCartButton
                      product={{
                        id: p.id,
                        name: p.name,
                        price: p.price,
                        unit: p.unit,
                        supplierName: p.supplier.businessName,
                      }}
                    />
                  </div>
                </div>
              </article>
            );
          })}
        </div>
      )}
    </div>
  );
}

