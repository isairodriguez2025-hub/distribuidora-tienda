import type { ReactNode } from 'react';

/**
 * Envoltura común de las páginas legales (aviso de privacidad, términos y
 * devoluciones). Mantiene el mismo ancho de lectura y el mismo ritmo vertical
 * que el resto del sitio, sin repetir el marcado en cada página.
 */
export function LegalPage({
  titulo,
  resumen,
  actualizado,
  children,
}: {
  titulo: string;
  resumen: string;
  actualizado: string;
  children: ReactNode;
}) {
  return (
    <section className="t-section t-section--tight">
      <div className="t-wrap">
        <div className="t-legal">
          <h1>{titulo}</h1>
          <p className="t-legal__lead">{resumen}</p>
          <p className="t-legal__date">Última actualización: {actualizado}</p>
          {children}
        </div>
      </div>
    </section>
  );
}
