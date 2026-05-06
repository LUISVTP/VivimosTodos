<?php

declare(strict_types=1);

class AuthController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function showLogin(): void
    {
        if (isset($_SESSION['usuario_id'])) {
            $this->redirect($this->defaultRouteByRole($this->roleId()));
        }

        $this->render('index');
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php');
        }

        $correo = trim($_POST['correo'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $usuario = $this->userModel->findByEmail($correo);

        if (!$usuario) {
            $this->redirect('index.php', ['error' => 'El correo no existe.']);
        }

        if (($usuario['estado'] ?? '') !== 'activo') {
            $this->redirect('index.php', ['error' => 'Tu cuenta está inactiva. Contacta al administrador.']);
        }

        if ($password !== (string) $usuario['password']) {
            $this->redirect('index.php', ['error' => 'Contraseña incorrecta.']);
        }

        $_SESSION['usuario_id'] = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre_completo'];
        $_SESSION['rol_id'] = (int) $usuario['rol_id'];

        $this->redirect($this->defaultRouteByRole((int) $usuario['rol_id']));
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('index.php');
    }

    private function defaultRouteByRole(int $rolId): string
    {
        return match ($rolId) {
            1 => 'dashboard.php',
            2 => 'residente.php',
            3 => 'supervisor.php',
            default => 'index.php',
        };
    }
}
