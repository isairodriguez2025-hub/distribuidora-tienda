import type { Metadata } from 'next';

import { LegalPage } from '@/components/tienda/LegalPage';

export const metadata: Metadata = {
  title: 'Aviso de privacidad · Distribuidora',
  description:
    'Cómo tratamos tus datos personales, para qué los usamos y cómo ejercer tus derechos ARCO.',
};

export default function AvisoDePrivacidad() {
  return (
    <LegalPage
      titulo="Aviso de privacidad"
      resumen="Este aviso explica qué datos personales recabamos, con qué finalidad y cómo puedes ejercer tus derechos. Se emite conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP)."
      actualizado="noviembre de 2026"
    >
      <h2>1. Responsable del tratamiento</h2>
      <p>
        Distribuidora, con domicilio en la Ciudad de México, es responsable del
        tratamiento de los datos personales que nos proporcionas a través de
        este sitio.
      </p>

      <h2>2. Datos que recabamos</h2>
      <ul>
        <li>Datos de identificación y contacto: nombre, teléfono, correo electrónico.</li>
        <li>Datos de entrega: dirección, colonia, código postal y referencias.</li>
        <li>Datos de la compra: productos, cantidades, importes y forma de pago.</li>
        <li>Datos de navegación: páginas visitadas y tipo de dispositivo.</li>
      </ul>
      <p>
        No recabamos datos personales sensibles ni datos financieros completos:
        los pagos con tarjeta, cuando existan, se procesan en la plataforma del
        proveedor correspondiente.
      </p>

      <h2>3. Finalidades del tratamiento</h2>
      <p>Usamos tus datos para las siguientes finalidades primarias:</p>
      <ul>
        <li>Procesar, surtir y entregar tus pedidos.</li>
        <li>Contactarte para confirmar la entrega o resolver incidencias.</li>
        <li>Emitir comprobantes y llevar el control de tus compras.</li>
        <li>Cumplir obligaciones legales y fiscales.</li>
      </ul>
      <p>
        De forma secundaria, y solo si nos das tu consentimiento, podemos
        enviarte promociones y avisos de nuevos productos. Puedes negarte a
        esto sin que afecte el servicio.
      </p>

      <h2>4. Transferencias</h2>
      <p>
        Tus datos no se venden ni se comparten con terceros ajenos a la
        operación. Solo se comunican a los proveedores de mensajería y pago
        estrictamente necesarios para completar tu pedido, y a las autoridades
        cuando exista un requerimiento legal.
      </p>

      <h2>5. Conservación</h2>
      <p>
        Conservamos tus datos mientras exista una relación comercial y durante
        los plazos que exige la legislación fiscal. La ubicación del repartidor
        se conserva un máximo de 30 días y después se elimina.
      </p>

      <h2>6. Tus derechos ARCO</h2>
      <p>
        Puedes solicitar el acceso, rectificación, cancelación u oposición al
        tratamiento de tus datos escribiendo a{' '}
        <a href="mailto:ventas@distribuidora.mx">ventas@distribuidora.mx</a>.
        Responderemos en un plazo máximo de 20 días hábiles.
      </p>

      <h2>7. Cambios a este aviso</h2>
      <p>
        Cualquier modificación se publicará en esta misma página, indicando la
        fecha de actualización.
      </p>
    </LegalPage>
  );
}
