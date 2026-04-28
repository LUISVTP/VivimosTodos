<?php
// archivo: acciones_inventario.php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$rol_id = $_SESSION['rol_id'];

// ─────────────────────────────────────────
// CREAR INSUMO (desde catálogo)
// ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['accion'] == 'crear') {
    if ($rol_id != 1) {
        header("Location: inventario.php?error=Acción no permitida.");
        exit;
    }

    $catalogo_id = (int)$_POST['catalogo_id'];
    $descripcion = $_POST['descripcion'];
    $cantidad    = (int)$_POST['cantidad'];
    $estado      = $_POST['estado'];

    // Obtener el nombre del catálogo
    $stmt_cat = $conn->prepare("SELECT nombre FROM catalogo_insumos WHERE id = ?");
    $stmt_cat->execute([$catalogo_id]);
    $cat = $stmt_cat->fetch();

    if (!$cat) {
        header("Location: inventario.php?error=Tipo de insumo no válido.");
        exit;
    }

    $nombre = $cat['nombre'];
    $sql    = "INSERT INTO insumos (nombre, descripcion, cantidad, estado) VALUES (?, ?, ?, ?)";
    $stmt   = $conn->prepare($sql);

    if ($stmt->execute([$nombre, $descripcion, $cantidad, $estado])) {
        header("Location: inventario.php?mensaje=Insumo '{$nombre}' registrado correctamente.");
    } else {
        header("Location: inventario.php?error=Error al registrar el insumo.");
    }
    exit;
}

// ─────────────────────────────────────────
// ACTUALIZAR INSUMO
// ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['accion'] == 'actualizar') {
    if ($rol_id != 1) {
        header("Location: inventario.php?error=Acción no permitida.");
        exit;
    }

    $id          = (int)$_POST['id'];
    $catalogo_id = (int)$_POST['catalogo_id'];
    $descripcion = $_POST['descripcion'];
    $cantidad    = (int)$_POST['cantidad'];
    $estado      = $_POST['estado'];

    // Obtener el nombre del catálogo
    $stmt_cat = $conn->prepare("SELECT nombre FROM catalogo_insumos WHERE id = ?");
    $stmt_cat->execute([$catalogo_id]);
    $cat = $stmt_cat->fetch();

    if (!$cat) {
        header("Location: inventario.php?error=Tipo de insumo no válido.");
        exit;
    }

    $nombre = $cat['nombre'];
    $sql    = "UPDATE insumos SET nombre = ?, descripcion = ?, cantidad = ?, estado = ? WHERE id = ?";
    $stmt   = $conn->prepare($sql);

    if ($stmt->execute([$nombre, $descripcion, $cantidad, $estado, $id])) {
        header("Location: inventario.php?mensaje=Insumo actualizado correctamente.");
    } else {
        header("Location: inventario.php?error=Error al actualizar el insumo.");
    }
    exit;
}

// ─────────────────────────────────────────
// ELIMINAR INSUMO
// ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'eliminar') {
    if ($rol_id != 1) {
        header("Location: inventario.php?error=Acción no permitida.");
        exit;
    }

    $id   = (int)$_GET['id'];
    $sql  = "DELETE FROM insumos WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$id])) {
        header("Location: inventario.php?mensaje=Insumo eliminado del sistema.");
    } else {
        header("Location: inventario.php?error=Error al eliminar. El insumo puede estar asociado a una reserva.");
    }
    exit;
}

// ─────────────────────────────────────────
// AGREGAR NUEVO TIPO AL CATÁLOGO
// ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['accion'] == 'nuevo_catalogo') {
    if ($rol_id != 1) {
        header("Location: inventario.php?error=Acción no permitida.");
        exit;
    }

    $nombre_catalogo = trim($_POST['nombre_catalogo']);

    if (empty($nombre_catalogo)) {
        header("Location: inventario.php?error=El nombre del tipo no puede estar vacío.");
        exit;
    }

    $sql  = "INSERT INTO catalogo_insumos (nombre) VALUES (?)";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$nombre_catalogo])) {
        header("Location: inventario.php?mensaje='{$nombre_catalogo}' agregado al catálogo exitosamente.");
    } else {
        header("Location: inventario.php?error=Ese nombre ya existe en el catálogo.");
    }
    exit;
}

// ─────────────────────────────────────────
// ELIMINAR TIPO DEL CATÁLOGO
// ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'eliminar_catalogo') {
    if ($rol_id != 1) {
        header("Location: inventario.php?error=Acción no permitida.");
        exit;
    }

    $id   = (int)$_GET['id'];
    $sql  = "DELETE FROM catalogo_insumos WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$id])) {
        header("Location: inventario.php?mensaje=Tipo eliminado del catálogo.");
    } else {
        header("Location: inventario.php?error=No se pudo eliminar el tipo del catálogo.");
    }
    exit;
}
?>
