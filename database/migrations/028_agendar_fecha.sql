-- 028 · Fecha deseada en las listas de pedido (Agendar).
-- El comercio elige un día concreto además de la hora; proximo_envio se sigue
-- calculando, pero la fecha que pidió queda guardada tal cual.

ALTER TABLE pedidos_programados
    ADD COLUMN IF NOT EXISTS fecha_deseada DATE NULL AFTER hora;
