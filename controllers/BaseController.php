<?php

declare(strict_types=1);

class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/view/' . $view . '.php';
    }

    protected function redirect(string $path, array $params = []): void
    {
        $query = http_build_query($params);
        header('Location: ' . $path . ($query !== '' ? '?' . $query : ''));
        exit;
    }

    protected function requireAuth(?array $roles = null): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect('index.php', ['error' => 'Debes iniciar sesión.']);
        }

        if ($roles !== null && !in_array((int) $_SESSION['rol_id'], $roles, true)) {
            $this->redirect('index.php', ['error' => 'Acceso denegado.']);
        }
    }

    protected function userName(): string
    {
        return $_SESSION['nombre'] ?? '';
    }

    protected function userId(): int
    {
        return (int) ($_SESSION['usuario_id'] ?? 0);
    }

    protected function roleId(): int
    {
        return (int) ($_SESSION['rol_id'] ?? 0);
    }
}
