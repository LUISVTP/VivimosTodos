<?php
// archivo: controllers/InventarioController.php
session_start();
require __DIR__ . '/../models/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php?error=Acceso denegado."); exit;
}

$rol_id = $_SESSION['rol_id'];
$accion = $_POST['accion'] ?? $_GET['accion'] ?? null;

// CREAR INSUMO
if ($accion == 'crear' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $catalogo_id = (int)$_POST['catalogo_id'];
    $cats = sb_get('catalogo_insumos', ['id' => 'eq.' . $catalogo_id]);
    $cat  = !empty($cats) ? $cats[0] : null;

    if (!$cat) { header("Location: ../views/admin/inventario.php?error=Tipo de insumo no válido."); exit; }

    $body = [
        'nombre'      => $cat['nombre'],
        'descripcion' => $_POST['descripcion'],
        'cantidad'    => (int)$_POST['cantidad'],
        'estado'      => $_POST['estado'],
        'marca'       => $_POST['marca'],
    ];
    $ok = sb_insert('insumos', $body);
    if ($ok) {
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
    $cats = sb_get('catalogo_insumos', ['id' => 'eq.' . $catalogo_id]);
    $cat  = !empty($cats) ? $cats[0] : null;

    if (!$cat) { header("Location: ../views/admin/inventario.php?error=Tipo de insumo no válido."); exit; }

    $body = [
        'nombre'      => $cat['nombre'],
        'descripcion' => $_POST['descripcion'],
        'cantidad'    => (int)$_POST['cantidad'],
        'estado'      => $_POST['estado'],
    ];
    $ok = sb_update('insumos', ['id' => 'eq.' . $id], $body);
    if ($ok) {
        header("Location: ../views/admin/inventario.php?mensaje=Insumo actualizado correctamente.");
    } else {
        header("Location: ../views/admin/inventario.php?error=Error al actualizar el insumo.");
    }
    exit;
}

// ELIMINAR INSUMO
if ($accion == 'eliminar' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }
    $id = (int)$_GET['id'];
    $ok = sb_delete('insumos', ['id' => 'eq.' . $id]);
    if ($ok) {
        header("Location: ../views/admin/inventario.php?mensaje=Insumo eliminado del sistema.");
    } else {
        header("Location: ../views/admin/inventario.php?error=Error al eliminar el insumo.");
    }
    exit;
}

header("Location: ../views/admin/inventario.php");
exit;
?>
