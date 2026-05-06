<?php

declare(strict_types=1);

class ReservationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function countPending(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM reservas WHERE estado = 'pendiente'");
        return (int) $stmt->fetch()['total'];
    }

    public function getAllWithUsers(): array
    {
        $stmt = $this->db->query(
            'SELECT r.*, u.nombre_completo
             FROM reservas r
             INNER JOIN usuarios u ON r.usuario_id = u.id
             ORDER BY r.fecha_evento ASC'
        );

        return $this->attachItems($stmt->fetchAll());
    }

    public function getByUser(int $usuarioId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.nombre_completo
             FROM reservas r
             INNER JOIN usuarios u ON r.usuario_id = u.id
             WHERE r.usuario_id = ?
             ORDER BY r.fecha_evento ASC'
        );
        $stmt->execute([$usuarioId]);

        return $this->attachItems($stmt->fetchAll());
    }

    public function existsReservationForDate(string $fechaEvento): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM reservas WHERE fecha_evento = ? AND estado != 'rechazada'");
        $stmt->execute([$fechaEvento]);
        return (bool) $stmt->fetch();
    }

    public function validateReservationDate(string $fechaEvento): ?string
    {
        $ahora = new DateTime();
        $evento = new DateTime($fechaEvento);
        $diferencia = $ahora->diff($evento);
        $horasDiff = ($diferencia->days * 24) + $diferencia->h;

        if ($evento <= $ahora || $horasDiff < 48) {
            return 'La reserva debe hacerse con mínimo 48 horas de anticipación.';
        }

        $maxFecha = new DateTime('+90 days');
        if ($evento > $maxFecha) {
            return 'No puedes reservar con más de 90 días de anticipación.';
        }

        return null;
    }

    public function createWithItems(int $usuarioId, string $fechaEvento, string $descripcion, array $insumos, array $cantidades): bool
    {
        $sql = "INSERT INTO reservas (usuario_id, fecha_evento, hora_inicio, hora_fin, descripcion, estado)
                VALUES (?, ?, '12:00:00', '23:59:59', ?, 'pendiente')";
        $stmt = $this->db->prepare($sql);
        $creada = $stmt->execute([$usuarioId, $fechaEvento, $descripcion]);

        if (!$creada) {
            return false;
        }

        $reservaId = (int) $this->db->lastInsertId();

        if (!empty($insumos)) {
            $stmtReservaInsumos = $this->db->prepare(
                'INSERT INTO reserva_insumos (reserva_id, insumo_id, cantidad_solicitada) VALUES (?, ?, ?)'
            );

            foreach ($insumos as $insumoId) {
                $insumoId = (int) $insumoId;
                $cantidad = isset($cantidades[$insumoId]) ? (int) $cantidades[$insumoId] : 1;
                $stmtReservaInsumos->execute([$reservaId, $insumoId, $cantidad]);
            }
        }

        return true;
    }

    public function findOwnedByUser(int $id, int $usuarioId): array|false
    {
        $stmt = $this->db->prepare('SELECT id, estado FROM reservas WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $usuarioId]);
        return $stmt->fetch();
    }

    public function deleteByUser(int $id, int $usuarioId): bool
    {
        $stmtReservaInsumos = $this->db->prepare('DELETE FROM reserva_insumos WHERE reserva_id = ?');
        $stmtReservaInsumos->execute([$id]);

        $stmt = $this->db->prepare('DELETE FROM reservas WHERE id = ? AND usuario_id = ?');
        return $stmt->execute([$id, $usuarioId]);
    }

    public function updateStatus(int $id, string $estado): bool
    {
        $stmt = $this->db->prepare('UPDATE reservas SET estado = ? WHERE id = ?');
        return $stmt->execute([$estado, $id]);
    }

    public function reject(int $id, string $motivo): bool
    {
        $stmt = $this->db->prepare("UPDATE reservas SET estado = 'rechazada', motivo_rechazo = ? WHERE id = ?");
        return $stmt->execute([$motivo, $id]);
    }

    private function attachItems(array $reservas): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.nombre, ri.cantidad_solicitada
             FROM reserva_insumos ri
             INNER JOIN insumos i ON ri.insumo_id = i.id
             WHERE ri.reserva_id = ?'
        );

        foreach ($reservas as &$reserva) {
            $stmt->execute([$reserva['id']]);
            $reserva['insumos'] = $stmt->fetchAll();
        }

        return $reservas;
    }
}
