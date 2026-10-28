import type { Metadata } from 'react';
import type { ReactNode } from 'react';

import { TiendaShell } from '@/components/tienda/TiendaShell';
import './tienda.css';

export const metadata: Metadata = {
  metadataBase: new URL('https://isairodriguez2025-hub.github.io/distribuidora-tienda'),
  title: {
    default: 'Distribuidora · Abarrotes, limpieza y herrería al mayoreo',
    template: '%s',
  },
  description:
    'Abarrotes, productos de limpieza y herrería al mayoreo para tiendas y talleres. Entrega en 24 a 48 horas y seguimiento del repartidor.',
  openGraph: {
    type: 'website',
    locale: 'es_MX',
    siteName: 'Distribuidora',
    title: 'Distribuidora · Abarrotes, limpieza y herrería al mayoreo',
    description:
      'Un solo proveedor para surtir tu tienda y tu taller. Pedido en línea y seguimiento de entrega.',
  },
  robots: { index: true, follow: true },
};

/**
 * Layout del sitio público.
 * Es deliberadamente independiente del layout del POS: el panel es oscuro y
 * el sitio de venta es claro. Comparten repositorio y base de datos, no
 * sistema visual.
 */
export default function TiendaLayout({ children }: { children: ReactNode }) {
  return <TiendaShell>{children}</TiendaShell>;
}
