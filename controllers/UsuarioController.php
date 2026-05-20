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
    $body = [
        'nombre_completo'    => $_POST['nombre'],
        'apellido_completo'  => $_POST['apellido'],
        'correo'             => $_POST['email'],
        'password'           => $_POST['password'],
        'rol_id'             => (int)$_POST['rol_id'],
        'estado'             => $_POST['estado'],
    ];
    $resultado = sb_insert('usuarios', $body);
    if ($resultado) {
        header("Location: ../views/admin/dashboard.php?mensaje=Usuario creado exitosamente.");
    } else {
        header("Location: ../views/admin/dashboard.php?error=Error al crear usuario. El correo podría ya existir.");
    }
    exit;
}

// 2. ACTUALIZAR USUARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['accion'] == 'actualizar') {
    $id   = (int)$_POST['id'];
    $body = [
        'nombre_completo' => $_POST['nombre'],
        'correo'          => $_POST['correo'],
        'rol_id'          => (int)$_POST['rol_id'],
    ];
    $ok = sb_update('usuarios', ['id' => 'eq.' . $id], $body);
    if ($ok) {
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
    sb_update('usuarios', ['id' => 'eq.' . $id], ['estado' => $nuevo_estado]);
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
    $ok = sb_delete('usuarios', ['id' => 'eq.' . $id]);
    if ($ok) {
        header("Location: ../views/admin/dashboard.php?mensaje=Usuario eliminado del sistema.");
    } else {
        header("Location: ../views/admin/dashboard.php?error=Error al eliminar el usuario.");
    }
    exit;
}

header("Location: ../views/admin/dashboard.php");
exit;
?>
