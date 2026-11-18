-- Calificaciones de demostración entre las cuentas demo.
-- Se puede volver a ejecutar: la clave única (evaluador, evaluado) actualiza
-- el puntaje en lugar de duplicarlo.

INSERT INTO calificaciones (evaluador_id, evaluado_id, puntaje, comentario) VALUES
    (4, 2, 5, 'Paga puntual y siempre recibe completo.'),
    (5, 2, 4, 'Buen cliente, a veces pide cambios de última hora.'),
    (6, 2, 5, ''),
    (7, 2, 4, ''),
    (2, 4, 5, 'Surtido completo y a tiempo.'),
    (2, 5, 4, 'Buen precio, la entrega se retrasó un día.'),
    (2, 6, 5, ''),
    (2, 7, 4, '')
ON DUPLICATE KEY UPDATE puntaje = VALUES(puntaje), comentario = VALUES(comentario);
