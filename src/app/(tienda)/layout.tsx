import type { ReactNode } from 'react';

import { TiendaShell } from '@/components/tienda/TiendaShell';
import './tienda.css';

/**
 * Layout del sitio público.
 * Es deliberadamente independiente del layout del POS: el panel es oscuro y
 * el sitio de venta es claro. Comparten repositorio y base de datos, no
 * sistema visual.
 */
export default function TiendaLayout({ children }: { children: ReactNode }) {
  return <TiendaShell>{children}</TiendaShell>;
}
