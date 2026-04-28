<?php
// archivo: acciones_usuario.php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    die("Acceso denegado. Función exclusiva para administradores.");
}

// 1. CREAR USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre   = $_POST['nombre'];
    $correo   = $_POST['email'];
    $password = $_POST['password'];
    $rol_id   = (int)$_POST['rol_id'];
    $estado   = $_POST['estado'];

    $sql  = "INSERT INTO usuarios (nombre_completo, correo, password, rol_id, estado) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$nombre, $correo, $password, $rol_id, $estado])) {
        header("Location: dashboard.php?mensaje=Usuario creado exitosamente.");
    } else {
        header("Location: dashboard.php?mensaje=Error al crear usuario. El correo podría ya existir.");
    }
    exit;
}

// 2. ACTUALIZAR USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'actualizar') {
    $id     = (int)$_POST['id'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $rol_id = (int)$_POST['rol_id'];

    $sql  = "UPDATE usuarios SET nombre_completo = ?, correo = ?, rol_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$nombre, $correo, $rol_id, $id])) {
        header("Location: dashboard.php?mensaje=Datos del usuario actualizados correctamente.");
    } else {
        header("Location: dashboard.php?mensaje=Error al actualizar el usuario.");
    }
    exit;
}

// 3. CAMBIAR ESTADO
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'estado') {
    $id           = (int)$_GET['id'];
    $nuevo_estado = ($_GET['estado_actual'] == 'activo') ? 'inactivo' : 'activo';

    if ($id == 1) {
        header("Location: dashboard.php?mensaje=No puedes cambiar el estado del Super Administrador principal.");
        exit;
    }

    $sql  = "UPDATE usuarios SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nuevo_estado, $id]);
    header("Location: dashboard.php?mensaje=El estado del usuario ha sido cambiado a: " . ucfirst($nuevo_estado));
    exit;
}

// 4. ELIMINAR USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'eliminar') {
    $id = (int)$_GET['id'];

    if ($id == 1) {
        header("Location: dashboard.php?mensaje=Por seguridad, no puedes eliminar al Super Administrador principal.");
        exit;
    }

    $sql  = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$id])) {
        header("Location: dashboard.php?mensaje=Usuario eliminado del sistema de forma permanente.");
    } else {
        header("Location: dashboard.php?mensaje=Error al eliminar el usuario.");
    }
    exit;
}
?>
