<?php

declare(strict_types=1);

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $correo): array|false
    {
        $stmt = $this->db->prepare('SELECT id, nombre_completo, password, rol_id, estado FROM usuarios WHERE correo = ?');
        $stmt->execute([$correo]);
        return $stmt->fetch();
    }

    public function getAllWithRoles(): array
    {
        $stmt = $this->db->query(
            'SELECT u.id, u.nombre_completo, u.correo, u.estado, u.rol_id, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON u.rol_id = r.id
             ORDER BY u.id ASC'
        );

        return $stmt->fetchAll();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre_completo, correo, password, rol_id, estado) VALUES (?, ?, ?, ?, ?)'
        );

        return $stmt->execute([
            $data['nombre'],
            $data['correo'],
            $data['password'],
            $data['rol_id'],
            $data['estado'],
        ]);
    }

    public function updateBasicData(int $id, string $nombre, string $correo, int $rolId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET nombre_completo = ?, correo = ?, rol_id = ? WHERE id = ?'
        );

        return $stmt->execute([$nombre, $correo, $rolId, $id]);
    }

    public function updateStatus(int $id, string $estado): bool
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET estado = ? WHERE id = ?');
        return $stmt->execute([$estado, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
