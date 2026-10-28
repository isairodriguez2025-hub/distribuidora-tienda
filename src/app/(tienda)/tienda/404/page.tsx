import type { Metadata } from 'next';

import { NoEncontrado } from '@/components/tienda/NoEncontrado';

/**
 * Ruta real para exportar la 404 estática (GitHub Pages sirve 404.html y no
 * ejecuta React). El 404 de Next usa el mismo componente.
 */
export const metadata: Metadata = { title: 'Página no encontrada · Distribuidora' };

export default function Pagina404() {
  return <NoEncontrado />;
}
