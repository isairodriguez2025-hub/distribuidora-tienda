import { auth } from "@/lib/auth";
import { prisma } from "@/lib/db";
import { ProductForm } from "@/components/paneles/product-form";
import { Badge, EmptyState, money } from "@/components/ui";

export default async function ProductosPage() {
  const session = await auth();
  const productos = await prisma.product.findMany({
    where: { supplierId: session!.user.id },
    orderBy: [{ category: "asc" }, { name: "asc" }],
  });

  return (
    <div>
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-[26px] font-semibold tracking-[-0.01em] text-ink">Productos</h1>
          <p className="mt-1 text-[13.5px] text-muted">
            {productos.length} {productos.length === 1 ? "producto publicado" : "productos publicados"}
          </p>
        </div>
        <ProductForm />
      </div>

      {productos.length === 0 ? (
        <div className="mt-6">
          <EmptyState
            title="Aún no publicas productos"
            description="Agrega tu primer artículo con precio, presentación y existencia para que los comercios lo encuentren."
          />
        </div>
      ) : (
        <div className="card mt-6 overflow-x-auto">
          <table className="w-full min-w-[720px] text-left text-[13px]">
            <thead>
              <tr className="border-b border-line bg-surface text-[11px] uppercase tracking-[0.06em] text-muted">
                <th className="px-4 py-3 font-semibold">Producto</th>
                <th className="px-4 py-3 font-semibold">Categoría</th>
                <th className="px-4 py-3 font-semibold">Presentación</th>
                <th className="px-4 py-3 text-right font-semibold">Precio</th>
                <th className="px-4 py-3 text-right font-semibold">Existencia</th>
                <th className="px-4 py-3 font-semibold">Estado</th>
              </tr>
            </thead>
            <tbody>
              {productos.map((p) => (
                <tr key={p.id} className="border-b border-line last:border-0">
                  <td className="px-4 py-3 font-medium text-ink">{p.name}</td>
                  <td className="px-4 py-3 text-ink-2">{p.category}</td>
                  <td className="px-4 py-3 text-muted">{p.unit}</td>
                  <td className="px-4 py-3 text-right font-semibold text-ink">{money(p.price)}</td>
                  <td className="px-4 py-3 text-right">
                    <span className={p.stock < 10 ? "font-semibold text-accent" : "text-ink-2"}>{p.stock}</span>
                  </td>
                  <td className="px-4 py-3">
                    <Badge tone={p.stock === 0 ? "danger" : p.stock < 10 ? "accent" : "brand"}>
                      {p.stock === 0 ? "Agotado" : p.stock < 10 ? "Stock bajo" : "Disponible"}
                    </Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

