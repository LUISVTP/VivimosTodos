<?php
// archivo: login_process.php
session_start();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $correo   = $_POST['correo'];
    $password = $_POST['password'];

    $sql  = "SELECT id, nombre_completo, password, rol_id, estado FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        if ($usuario['estado'] != 'activo') {
            header("Location: index.php?error=Tu cuenta está inactiva. Contacta al administrador.");
            exit;
        }
        if ($password === $usuario['password']) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre_completo'];
            $_SESSION['rol_id']     = $usuario['rol_id'];

            if ($usuario['rol_id'] == 1) {
                header("Location: dashboard.php");
            } elseif ($usuario['rol_id'] == 2) {
                header("Location: residente.php");
            } elseif ($usuario['rol_id'] == 3) {
                header("Location: supervisor.php");
            }
            exit;
        } else {
            header("Location: index.php?error=Contraseña incorrecta.");
            exit;
        }
    } else {
        header("Location: index.php?error=El correo no existe.");
        exit;
    }
}
?>
