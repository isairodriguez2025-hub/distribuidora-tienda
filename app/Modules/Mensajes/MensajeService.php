<?php

declare(strict_types=1);

namespace App\Modules\Mensajes;

use App\Core\AuditLogger;
use App\Modules\Notificaciones\NotificacionService;

/**
 * Mensajería interna.
 *
 * Dos tipos de hilo:
 *  - directa: dos participantes cualesquiera.
 *  - soporte: el usuario y el equipo de administración. El usuario nunca elige
 *    a un administrador concreto ni ve a los demás; para él es un solo canal.
 */
final class MensajeService
{
    private const MAX_CARACTERES = 2000;
    private const MAX_POR_VENTANA = 3;
    private const VENTANA_SEGUNDOS = 30;

    public function __construct(
        private readonly MensajeRepository $repo,
        private readonly NotificacionService $notificaciones,
        private readonly AuditLogger $auditoria,
    ) {
    }

    /**
     * Hilos listos para pintar: nombre del interlocutor y avatar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function bandeja(int $usuarioId): array
    {
        $hilos = $this->repo->hilos($usuarioId);

        foreach ($hilos as &$hilo) {
            $participantes = $this->repo->participantes((int) $hilo['id']);
            $otro = null;

            foreach ($participantes as $p) {
                if ($p['id'] !== $usuarioId) {
                    $otro = $p;
                    break;
                }
            }

            $hilo['titulo'] = $hilo['tipo'] === 'soporte'
                ? 'Soporte Didi2'
                : ($otro['nombre'] ?? 'Conversación');
            $hilo['avatar'] = $hilo['tipo'] === 'soporte' ? null : ($otro['avatar'] ?? null);
            $hilo['interlocutor_id'] = $otro['id'] ?? null;
            $hilo['no_leidos'] = (int) ($hilo['no_leidos'] ?? 0);
        }

        unset($hilo);

        return $hilos;
    }

    /**
     * Abre un hilo comprobando participación. Devuelve null si no es suyo.
     *
     * @return array{hilo:array, mensajes:array, participantes:array, titulo:string}|null
     */
    public function abrir(int $usuarioId, string $uuid): ?array
    {
        $hilo = $this->repo->porUuid($uuid);

        if ($hilo === null || !$this->repo->esParticipante((int) $hilo['id'], $usuarioId)) {
            return null;
        }

        $participantes = $this->repo->participantes((int) $hilo['id']);
        $mensajes = $this->repo->mensajes((int) $hilo['id']);

        if ($mensajes !== []) {
            $this->repo->marcarLeido((int) $hilo['id'], $usuarioId, (int) end($mensajes)['id']);
        }

        $titulo = 'Conversación';

        if ($hilo['tipo'] === 'soporte') {
            $titulo = 'Soporte Didi2';
        } else {
            foreach ($participantes as $p) {
                if ($p['id'] !== $usuarioId) {
                    $titulo = $p['nombre'];
                    break;
                }
            }
        }

        return [
            'hilo'          => $hilo,
            'mensajes'      => $mensajes,
            'participantes' => $participantes,
            'titulo'        => $titulo,
        ];
    }

    /**
     * @return array{ok:bool, mensaje:string, id?:int}
     */
    public function enviar(int $usuarioId, string $uuid, string $cuerpo, string $ip): array
    {
        $cuerpo = trim($cuerpo);

        if ($cuerpo === '') {
            return ['ok' => false, 'mensaje' => 'Escribe un mensaje.'];
        }

        if (mb_strlen($cuerpo) > self::MAX_CARACTERES) {
            return ['ok' => false, 'mensaje' => 'El mensaje supera los ' . self::MAX_CARACTERES . ' caracteres.'];
        }

        $hilo = $this->repo->porUuid($uuid);

        if ($hilo === null || !$this->repo->esParticipante((int) $hilo['id'], $usuarioId)) {
            return ['ok' => false, 'mensaje' => 'Conversación no disponible.'];
        }

        if ($this->demasiadosSeguidos($usuarioId)) {
            return ['ok' => false, 'mensaje' => 'Espera unos segundos antes de enviar otro mensaje.'];
        }

        $id = $this->repo->insertar((int) $hilo['id'], $usuarioId, $cuerpo);
        $this->auditoria->log('mensaje_enviado', $usuarioId, 'conversacion', $uuid, [], $ip);

        $this->avisar((int) $hilo['id'], $usuarioId, $cuerpo, (string) $hilo['tipo'], $uuid);

        return ['ok' => true, 'mensaje' => 'Enviado.', 'id' => $id];
    }

    /** Abre (o crea) la conversación directa con otro usuario. */
    public function directo(int $usuarioId, int $otroId, string $ip): array
    {
        if ($otroId <= 0 || $otroId === $usuarioId) {
            return ['ok' => false, 'mensaje' => 'Destinatario no válido.'];
        }

        if (!$this->repo->usuarioActivo($otroId)) {
            return ['ok' => false, 'mensaje' => 'Ese usuario no está disponible.'];
        }

        $existente = $this->repo->directaEntre($usuarioId, $otroId);

        if ($existente !== null) {
            return ['ok' => true, 'uuid' => (string) $existente['uuid']];
        }

        $id = $this->repo->crear('directa', null, $usuarioId, [$usuarioId, $otroId]);
        $hilo = $this->repo->porId($id);

        $this->auditoria->log('conversacion_creada', $usuarioId, 'conversacion', (string) $id, ['otro' => $otroId], $ip);

        return ['ok' => true, 'uuid' => (string) $hilo['uuid']];
    }

    /** Devuelve el hilo de soporte del usuario, creándolo si no existe. */
    public function soporte(int $usuarioId, string $ip): array
    {
        $existente = $this->repo->soporteDe($usuarioId);

        if ($existente !== null) {
            return ['ok' => true, 'uuid' => (string) $existente['uuid']];
        }

        $participantes = array_merge([$usuarioId], $this->repo->admins());
        $id = $this->repo->crear('soporte', 'Canal de soporte', $usuarioId, $participantes);
        $hilo = $this->repo->porId($id);

        $this->auditoria->log('soporte_abierto', $usuarioId, 'conversacion', (string) $id, [], $ip);

        return ['ok' => true, 'uuid' => (string) $hilo['uuid']];
    }

    /**
     * Mensajes nuevos para el polling.
     *
     * @return array<int, array<string, mixed>>
     */
    public function nuevos(int $usuarioId, string $uuid, int $desdeId): array
    {
        $hilo = $this->repo->porUuid($uuid);

        if ($hilo === null || !$this->repo->esParticipante((int) $hilo['id'], $usuarioId)) {
            return [];
        }

        $mensajes = $this->repo->mensajes((int) $hilo['id'], $desdeId);

        if ($mensajes !== []) {
            $this->repo->marcarLeido((int) $hilo['id'], $usuarioId, (int) end($mensajes)['id']);
        }

        return $mensajes;
    }

    /** @return array<int,array<string,mixed>> */
    public function contactos(int $usuarioId): array
    {
        return $this->repo->contactos($usuarioId);
    }

    public function esAdmin(int $usuarioId): bool
    {
        return $this->repo->esAdmin($usuarioId);
    }

    private function demasiadosSeguidos(int $usuarioId): bool
    {
        $stmt = \App\Config\Database::conexion()->prepare(
            'SELECT COUNT(*) FROM mensajes_directos
              WHERE autor_id = ? AND created_at > (NOW() - INTERVAL ? SECOND)'
        );
        $stmt->execute([$usuarioId, self::VENTANA_SEGUNDOS]);

        return (int) $stmt->fetchColumn() >= self::MAX_POR_VENTANA;
    }

    /** Avisa a los demás participantes por bandeja y SMS si es soporte. */
    private function avisar(int $conversacionId, int $autorId, string $cuerpo, string $tipo, string $uuid): void
    {
        $destinatarios = $this->repo->participantes($conversacionId);

        foreach ($destinatarios as $p) {
            if ($p['id'] === $autorId) {
                continue;
            }

            $canales = $tipo === 'soporte' ? ['bandeja', 'sms'] : ['bandeja'];

            $this->notificaciones->encolar(
                $p['id'],
                'mensaje_' . $tipo,
                'Nuevo mensaje en ' . ($tipo === 'soporte' ? 'soporte' : 'Didi2'),
                $cuerpo,
                '/mensajes?hilo=' . $uuid,
                $canales,
            );
        }
    }
}
