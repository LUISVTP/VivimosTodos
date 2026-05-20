<?php
// archivo: controllers/AuthController.php
session_start();
require __DIR__ . '/../models/Database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $correo   = trim($_POST['correo']);
    $password = $_POST['password'];

    // Buscar usuario por correo
    $usuarios = sb_get('usuarios', ['correo' => 'eq.' . $correo]);
    $usuario  = !empty($usuarios) ? $usuarios[0] : null;

    if ($usuario) {
        if ($usuario['estado'] != 'activo') {
            header("Location: ../views/auth/login.php?error=Tu cuenta está inactiva. Contacta al administrador.");
            exit;
        }
        if ($password === $usuario['password']) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre_completo'];
            $_SESSION['rol_id']     = $usuario['rol_id'];

            if     ($usuario['rol_id'] == 1) header("Location: ../views/admin/dashboard.php");
            elseif ($usuario['rol_id'] == 2) header("Location: ../views/residente/inicio.php");
            elseif ($usuario['rol_id'] == 3) header("Location: ../views/supervisor/inicio.php");
            exit;
        } else {
            header("Location: ../views/auth/login.php?error=Contraseña incorrecta.");
            exit;
        }
    } else {
        header("Location: ../views/auth/login.php?error=El correo no existe.");
        exit;
    }
}
?>
