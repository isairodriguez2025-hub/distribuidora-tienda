import type { Metadata } from 'next';

import { LegalPage } from '@/components/tienda/LegalPage';

export const metadata: Metadata = {
  title: 'Términos y condiciones · Distribuidora',
  description: 'Condiciones de uso del sitio, precios, pedidos y entregas.',
};

export default function Terminos() {
  return (
    <LegalPage
      titulo="Términos y condiciones"
      resumen="Al usar este sitio aceptas las condiciones descritas a continuación. Están escritas en lenguaje sencillo a propósito: si algo no queda claro, pregúntanos antes de comprar."
      actualizado="noviembre de 2026"
    >
      <h2>1. Uso del sitio</h2>
      <p>
        Este sitio está dirigido a tiendas, talleres y negocios que compran al
        mayoreo. Al navegar o hacer un pedido te comprometes a proporcionar
        información veraz y a no usar el sitio para fines ilícitos.
      </p>

      <h2>2. Precios</h2>
      <p>
        Los precios se muestran en pesos mexicanos e incluyen IVA. Pueden
        cambiar sin previo aviso; el precio que aplica es el vigente al momento
        de confirmar el pedido y queda registrado en tu comprobante.
      </p>

      <h2>3. Pedidos</h2>
      <p>
        Un pedido se considera confirmado cuando recibes el folio correspondiente.
        Si un producto no está disponible, te contactamos para ofrecerte una
        sustitución o ajustar el importe antes de surtir.
      </p>

      <h2>4. Entregas</h2>
      <p>
        Entregamos en la Ciudad de México y zona metropolitana en un plazo de 24
        a 48 horas hábiles. El envío es gratis en pedidos desde $1,500. En zonas
        fuera de cobertura se cotiza por separado.
      </p>

      <h2>5. Formas de pago</h2>
      <p>
        Aceptamos transferencia bancaria y pago en efectivo contra entrega. En
        compras recurrentes se puede acordar crédito, siempre por escrito.
      </p>

      <h2>6. Responsabilidad</h2>
      <p>
        Hacemos nuestro mejor esfuerzo por mantener la información del catálogo
        actualizada. Las fotografías son ilustrativas y la presentación puede
        variar según el proveedor, sin que esto afecte la cantidad o el contenido
        del producto.
      </p>

      <h2>7. Contacto</h2>
      <p>
        Dudas sobre estos términos:{' '}
        <a href="mailto:ventas@distribuidora.mx">ventas@distribuidora.mx</a> o
        WhatsApp 55 1234 5678.
      </p>
    </LegalPage>
  );
}
