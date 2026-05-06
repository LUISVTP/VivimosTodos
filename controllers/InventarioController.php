<?php

declare(strict_types=1);

class InventarioController extends BaseController
{
    private InventoryModel $inventoryModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
    }

    public function handle(): void
    {
        $this->requireAuth([1, 2, 3]);

        $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

        switch ($accion) {
            case 'crear':
                $this->crear();
                break;
            case 'actualizar':
                $this->actualizar();
                break;
            case 'eliminar':
                $this->eliminar();
                break;
            case 'nuevo_catalogo':
                $this->nuevoCatalogo();
                break;
            case 'eliminar_catalogo':
                $this->eliminarCatalogo();
                break;
            default:
                $this->redirect('inventario.php', ['error' => 'Acción no válida.']);
        }
    }

    private function assertAdmin(): void
    {
        if ($this->roleId() !== 1) {
            $this->redirect('inventario.php', ['error' => 'Acción no permitida.']);
        }
    }

    private function crear(): void
    {
        $this->assertAdmin();

        $creado = $this->inventoryModel->createItem(
            (int) ($_POST['catalogo_id'] ?? 0),
            trim($_POST['descripcion'] ?? ''),
            (int) ($_POST['cantidad'] ?? 0),
            trim($_POST['estado'] ?? '')
        );

        $this->redirect('inventario.php', [
            'mensaje' => $creado !== null
                ? "Insumo '{$creado}' registrado correctamente."
                : 'Error al registrar el insumo.',
        ]);
    }

    private function actualizar(): void
    {
        $this->assertAdmin();

        $actualizado = $this->inventoryModel->updateItem(
            (int) ($_POST['id'] ?? 0),
            (int) ($_POST['catalogo_id'] ?? 0),
            trim($_POST['descripcion'] ?? ''),
            (int) ($_POST['cantidad'] ?? 0),
            trim($_POST['estado'] ?? '')
        );

        $this->redirect('inventario.php', [
            'mensaje' => $actualizado
                ? 'Insumo actualizado correctamente.'
                : 'Error al actualizar el insumo.',
        ]);
    }

    private function eliminar(): void
    {
        $this->assertAdmin();

        $eliminado = $this->inventoryModel->deleteItem((int) ($_GET['id'] ?? 0));
        $this->redirect('inventario.php', [
            'mensaje' => $eliminado
                ? 'Insumo eliminado del sistema.'
                : 'Error al eliminar. El insumo puede estar asociado a una reserva.',
        ]);
    }

    private function nuevoCatalogo(): void
    {
        $this->assertAdmin();

        $nombreCatalogo = trim($_POST['nombre_catalogo'] ?? '');
        if ($nombreCatalogo === '') {
            $this->redirect('inventario.php', ['error' => 'El nombre del tipo no puede estar vacío.']);
        }

        $creado = $this->inventoryModel->createCatalogItem($nombreCatalogo);
        $this->redirect('inventario.php', [
            'mensaje' => $creado
                ? "'{$nombreCatalogo}' agregado al catálogo exitosamente."
                : 'Ese nombre ya existe en el catálogo.',
        ]);
    }

    private function eliminarCatalogo(): void
    {
        $this->assertAdmin();

        $eliminado = $this->inventoryModel->deleteCatalogItem((int) ($_GET['id'] ?? 0));
        $this->redirect('inventario.php', [
            'mensaje' => $eliminado
                ? 'Tipo eliminado del catálogo.'
                : 'No se pudo eliminar el tipo del catálogo.',
        ]);
    }
}
