<?php

declare(strict_types=1);

class InventoryModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM insumos');
        return (int) $stmt->fetch()['total'];
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM insumos ORDER BY fecha_registro DESC');
        return $stmt->fetchAll();
    }

    public function getCatalog(): array
    {
        $stmt = $this->db->query('SELECT * FROM catalogo_insumos ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    public function getAvailableItems(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre, cantidad FROM insumos WHERE estado = 'disponible' AND cantidad > 0 ORDER BY nombre ASC"
        );
        return $stmt->fetchAll();
    }

    public function validateRequestedItems(array $insumos, array $cantidades): ?string
    {
        $stmt = $this->db->prepare("SELECT cantidad FROM insumos WHERE id = ? AND estado = 'disponible'");

        foreach ($insumos as $insumoId) {
            $insumoId = (int) $insumoId;
            $cantidadSolicitada = isset($cantidades[$insumoId]) ? (int) $cantidades[$insumoId] : 1;

            $stmt->execute([$insumoId]);
            $stock = $stmt->fetch();

            if (!$stock || $cantidadSolicitada > (int) $stock['cantidad']) {
                return 'La cantidad solicitada de uno de los insumos supera el stock disponible.';
            }

            if ($cantidadSolicitada < 1) {
                return 'La cantidad de cada insumo debe ser al menos 1.';
            }
        }

        return null;
    }

    public function createItem(int $catalogoId, string $descripcion, int $cantidad, string $estado): ?string
    {
        $nombre = $this->findCatalogName($catalogoId);
        if ($nombre === null) {
            return null;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO insumos (nombre, descripcion, cantidad, estado) VALUES (?, ?, ?, ?)'
        );

        return $stmt->execute([$nombre, $descripcion, $cantidad, $estado]) ? $nombre : null;
    }

    public function updateItem(int $id, int $catalogoId, string $descripcion, int $cantidad, string $estado): bool
    {
        $nombre = $this->findCatalogName($catalogoId);
        if ($nombre === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE insumos SET nombre = ?, descripcion = ?, cantidad = ?, estado = ? WHERE id = ?'
        );

        return $stmt->execute([$nombre, $descripcion, $cantidad, $estado, $id]);
    }

    public function deleteItem(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM insumos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function createCatalogItem(string $nombre): bool
    {
        $stmt = $this->db->prepare('INSERT INTO catalogo_insumos (nombre) VALUES (?)');
        return $stmt->execute([$nombre]);
    }

    public function deleteCatalogItem(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM catalogo_insumos WHERE id = ?');
        return $stmt->execute([$id]);
    }

    private function findCatalogName(int $catalogoId): ?string
    {
        $stmt = $this->db->prepare('SELECT nombre FROM catalogo_insumos WHERE id = ?');
        $stmt->execute([$catalogoId]);
        $catalogo = $stmt->fetch();

        return $catalogo['nombre'] ?? null;
    }
}
