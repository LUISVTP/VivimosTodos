<?php
// archivo: controllers/InventarioController.php
session_start();
require __DIR__ . '/../models/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php?error=Acceso denegado.");
    exit;
}

$rol_id = $_SESSION['rol_id'];
$accion = $_POST['accion'] ?? $_GET['accion'] ?? null;

// CREAR INSUMO
if ($accion == 'crear' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $catalogo_id = (int)$_POST['catalogo_id'];
    $descripcion = $_POST['descripcion'];
    $cantidad    = (int)$_POST['cantidad'];
    $estado      = $_POST['estado'];

    $stmt_cat = $conn->prepare("SELECT nombre FROM catalogo_insumos WHERE id = ?");
    $stmt_cat->execute([$catalogo_id]);
    $cat = $stmt_cat->fetch();

    if (!$cat) { header("Location: ../views/admin/inventario.php?error=Tipo de insumo no válido."); exit; }

    $stmt = $conn->prepare("INSERT INTO insumos (nombre, descripcion, cantidad, estado) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$cat['nombre'], $descripcion, $cantidad, $estado])) {
        header("Location: ../views/admin/inventario.php?mensaje=Insumo '{$cat['nombre']}' registrado correctamente.");
    } else {
        header("Location: ../views/admin/inventario.php?error=Error al registrar el insumo.");
    }
    exit;
}

// ACTUALIZAR INSUMO
if ($accion == 'actualizar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $id          = (int)$_POST['id'];
    $catalogo_id = (int)$_POST['catalogo_id'];
    $descripcion = $_POST['descripcion'];
    $cantidad    = (int)$_POST['cantidad'];
    $estado      = $_POST['estado'];

    $stmt_cat = $conn->prepare("SELECT nombre FROM catalogo_insumos WHERE id = ?");
    $stmt_cat->execute([$catalogo_id]);
    $cat = $stmt_cat->fetch();

    if (!$cat) { header("Location: ../views/admin/inventario.php?error=Tipo de insumo no válido."); exit; }

    $stmt = $conn->prepare("UPDATE insumos SET nombre = ?, descripcion = ?, cantidad = ?, estado = ? WHERE id = ?");
    if ($stmt->execute([$cat['nombre'], $descripcion, $cantidad, $estado, $id])) {
        header("Location: ../views/admin/inventario.php?mensaje=Insumo actualizado correctamente.");
    } else {
        header("Location: ../views/admin/inventario.php?error=Error al actualizar el insumo.");
    }
    exit;
}

// ELIMINAR INSUMO
if ($accion == 'eliminar' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM insumos WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: ../views/admin/inventario.php?mensaje=Insumo eliminado del sistema.");
    } else {
        header("Location: ../views/admin/inventario.php?error=Error al eliminar. El insumo puede estar asociado a una reserva.");
    }
    exit;
}

// AGREGAR AL CATÁLOGO
if ($accion == 'nuevo_catalogo' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $nombre_catalogo = trim($_POST['nombre_catalogo']);
    if (empty($nombre_catalogo)) { header("Location: ../views/admin/inventario.php?error=El nombre no puede estar vacío."); exit; }

    $stmt = $conn->prepare("INSERT INTO catalogo_insumos (nombre) VALUES (?)");
    if ($stmt->execute([$nombre_catalogo])) {
        header("Location: ../views/admin/inventario.php?mensaje='{$nombre_catalogo}' agregado al catálogo.");
    } else {
        header("Location: ../views/admin/inventario.php?error=Ese nombre ya existe en el catálogo.");
    }
    exit;
}

// ELIMINAR DEL CATÁLOGO
if ($accion == 'eliminar_catalogo' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM catalogo_insumos WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: ../views/admin/inventario.php?mensaje=Tipo eliminado del catálogo.");
    } else {
        header("Location: ../views/admin/inventario.php?error=No se pudo eliminar el tipo del catálogo.");
    }
    exit;
}

header("Location: ../views/admin/inventario.php");
exit;
?>
