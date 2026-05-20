<?php
// archivo: controllers/UsuarioController.php
session_start();
require __DIR__ . '/../models/Database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../views/auth/login.php?error=Acceso denegado.");
    exit;
}

// 1. CREAR USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['accion'] == 'crear') {
    $nombre   = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo   = $_POST['email'];
    $password = $_POST['password'];
    $rol_id   = (int)$_POST['rol_id'];
    $estado   = $_POST['estado'];

    $stmt = $conn->prepare(
        "INSERT INTO usuarios (nombre_completo, apellido_completo, correo, password, rol_id, estado) VALUES (?, ?, ?, ?, ?, ?)"
    );

    if ($stmt->execute([$nombre, $apellido, $correo, $password, $rol_id, $estado])) {
        header("Location: ../views/admin/dashboard.php?mensaje=Usuario creado exitosamente.");
    } else {
        header("Location: ../views/admin/dashboard.php?error=Error al crear usuario. El correo podría ya existir.");
    }
    exit;
}

// 2. ACTUALIZAR USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['accion'] == 'actualizar') {
    $id     = (int)$_POST['id'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $rol_id = (int)$_POST['rol_id'];

    $stmt = $conn->prepare(
        "UPDATE usuarios SET nombre_completo = ?, correo = ?, rol_id = ? WHERE id = ?"
    );

    if ($stmt->execute([$nombre, $correo, $rol_id, $id])) {
        header("Location: ../views/admin/dashboard.php?mensaje=Datos del usuario actualizados correctamente.");
    } else {
        header("Location: ../views/admin/dashboard.php?error=Error al actualizar el usuario.");
    }
    exit;
}

// 3. CAMBIAR ESTADO
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'estado') {
    $id           = (int)$_GET['id'];
    $nuevo_estado = ($_GET['estado_actual'] == 'activo') ? 'inactivo' : 'activo';

    if ($id == 1) {
        header("Location: ../views/admin/dashboard.php?mensaje=No puedes cambiar el estado del Super Administrador.");
        exit;
    }

    $stmt = $conn->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);
    header("Location: ../views/admin/dashboard.php?mensaje=Estado cambiado a: " . ucfirst($nuevo_estado));
    exit;
}

// 4. ELIMINAR USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'eliminar') {
    $id = (int)$_GET['id'];

    if ($id == 1) {
        header("Location: ../views/admin/dashboard.php?mensaje=No puedes eliminar al Super Administrador.");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");

    if ($stmt->execute([$id])) {
        header("Location: ../views/admin/dashboard.php?mensaje=Usuario eliminado del sistema.");
    } else {
        header("Location: ../views/admin/dashboard.php?error=Error al eliminar el usuario.");
    }
    exit;
}

header("Location: ../views/admin/dashboard.php");
exit;
?>
