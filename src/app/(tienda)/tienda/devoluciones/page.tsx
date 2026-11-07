import type { Metadata } from 'next';

import { LegalPage } from '@/components/tienda/LegalPage';

export const metadata: Metadata = {
  title: 'Política de devoluciones · Distribuidora',
  description: 'Cómo solicitar una devolución, plazos y reembolsos.',
};

export default function Devoluciones() {
  return (
    <LegalPage
      titulo="Política de devoluciones"
      resumen="Si algo llegó en mal estado o no es lo que pediste, lo resolvemos. Esta política aplica a todos los pedidos hechos en el sitio."
      actualizado="noviembre de 2026"
    >
      <h2>1. Plazo</h2>
      <p>
        Puedes solicitar una devolución dentro de los 30 días naturales
        siguientes a la entrega. Para productos perecederos el plazo es de 48
        horas, contadas desde que recibes el pedido.
      </p>

      <h2>2. Cuándo procede</h2>
      <ul>
        <li>El producto llegó dañado, abierto o en mal estado.</li>
        <li>Recibiste un producto distinto al que pediste.</li>
        <li>La cantidad entregada no coincide con el comprobante.</li>
      </ul>

      <h2>3. Cuándo no procede</h2>
      <ul>
        <li>El producto fue usado, abierto o alterado por el cliente.</li>
        <li>Se trata de mercancía sobre pedido o cortada a medida.</li>
        <li>Pasó el plazo indicado en el punto 1.</li>
      </ul>

      <h2>4. Cómo solicitarla</h2>
      <p>
        Escríbenos a{' '}
        <a href="mailto:ventas@distribuidora.mx">ventas@distribuidora.mx</a> o
        por WhatsApp al 55 1234 5678 con tu folio de pedido y una fotografía del
        producto. Te confirmamos la resolución en un máximo de 2 días hábiles.
      </p>

      <h2>5. Reembolsos</h2>
      <p>
        Si el pago fue por transferencia, el reembolso se hace a la misma cuenta
        en un plazo de 5 a 10 días hábiles. Si fue en efectivo contra entrega, se
        reembolsa en la siguiente visita o por transferencia, según prefieras.
      </p>

      <h2>6. Reposición</h2>
      <p>
        Cuando el producto esté disponible, ofrecemos reponerlo sin costo en la
        siguiente entrega programada, sin necesidad de reembolso.
      </p>
    </LegalPage>
  );
}
