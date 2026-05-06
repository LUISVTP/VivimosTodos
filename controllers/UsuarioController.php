<?php

declare(strict_types=1);

class UsuarioController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function handle(): void
    {
        $this->requireAuth([1]);

        $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

        switch ($accion) {
            case 'crear':
                $this->crear();
                break;
            case 'actualizar':
                $this->actualizar();
                break;
            case 'estado':
                $this->cambiarEstado();
                break;
            case 'eliminar':
                $this->eliminar();
                break;
            default:
                $this->redirect('dashboard.php', ['mensaje' => 'Acción no válida.']);
        }
    }

    private function crear(): void
    {
        $creado = $this->userModel->create([
            'nombre' => trim($_POST['nombre'] ?? ''),
            'correo' => trim($_POST['email'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'rol_id' => (int) ($_POST['rol_id'] ?? 0),
            'estado' => trim($_POST['estado'] ?? ''),
        ]);

        $this->redirect('dashboard.php', [
            'mensaje' => $creado
                ? 'Usuario creado exitosamente.'
                : 'Error al crear usuario. El correo podría ya existir.',
        ]);
    }

    private function actualizar(): void
    {
        $actualizado = $this->userModel->updateBasicData(
            (int) ($_POST['id'] ?? 0),
            trim($_POST['nombre'] ?? ''),
            trim($_POST['correo'] ?? ''),
            (int) ($_POST['rol_id'] ?? 0)
        );

        $this->redirect('dashboard.php', [
            'mensaje' => $actualizado
                ? 'Datos del usuario actualizados correctamente.'
                : 'Error al actualizar el usuario.',
        ]);
    }

    private function cambiarEstado(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $estadoActual = trim($_GET['estado_actual'] ?? '');

        if ($id === 1) {
            $this->redirect('dashboard.php', ['mensaje' => 'No puedes cambiar el estado del Super Administrador principal.']);
        }

        $nuevoEstado = $estadoActual === 'activo' ? 'inactivo' : 'activo';
        $this->userModel->updateStatus($id, $nuevoEstado);

        $this->redirect('dashboard.php', [
            'mensaje' => 'El estado del usuario ha sido cambiado a: ' . ucfirst($nuevoEstado),
        ]);
    }

    private function eliminar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id === 1) {
            $this->redirect('dashboard.php', ['mensaje' => 'Por seguridad, no puedes eliminar al Super Administrador principal.']);
        }

        $eliminado = $this->userModel->delete($id);

        $this->redirect('dashboard.php', [
            'mensaje' => $eliminado
                ? 'Usuario eliminado del sistema de forma permanente.'
                : 'Error al eliminar el usuario.',
        ]);
    }
}
