<?php
// archivo: controllers/InventarioController.php
session_start();
require __DIR__ . '/../models/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php?error=Acceso denegado."); exit;
}

$rol_id = $_SESSION['rol_id'];
$accion = $_POST['accion'] ?? $_GET['accion'] ?? null;

// ── CREAR INSUMO ─────────────────────────────────────────────────────────────
if ($accion == 'crear' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $catalogo_id = (int)$_POST['catalogo_id'];
    $cats = sb_get('catalogo_insumos', ['id' => 'eq.' . $catalogo_id]);
    $cat  = !empty($cats) ? $cats[0] : null;

    if (!$cat) { header("Location: ../views/admin/inventario.php?error=Tipo de insumo no válido."); exit; }

    $ok = sb_insert('insumos', [
        'nombre'      => $cat['nombre'],
        'descripcion' => $_POST['descripcion'] ?? '',
        'cantidad'    => (int)$_POST['cantidad'],
        'estado'      => $_POST['estado'],
        'marca'       => $_POST['marca'] ?? '',
    ]);
    $msg = $ok ? urlencode("Insumo '{$cat['nombre']}' registrado correctamente.") : "Error al registrar el insumo.";
    $key = $ok ? "mensaje" : "error";
    header("Location: ../views/admin/inventario.php?{$key}={$msg}"); exit;
}

// ── ACTUALIZAR INSUMO ────────────────────────────────────────────────────────
if ($accion == 'actualizar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $id          = (int)$_POST['id'];
    $catalogo_id = (int)$_POST['catalogo_id'];
    $cats = sb_get('catalogo_insumos', ['id' => 'eq.' . $catalogo_id]);
    $cat  = !empty($cats) ? $cats[0] : null;

    if (!$cat) { header("Location: ../views/admin/inventario.php?error=Tipo de insumo no válido."); exit; }

    $ok = sb_update('insumos', ['id' => 'eq.' . $id], [
        'nombre'      => $cat['nombre'],
        'descripcion' => $_POST['descripcion'] ?? '',
        'cantidad'    => (int)$_POST['cantidad'],
        'estado'      => $_POST['estado'],
    ]);
    $msg = $ok ? "Insumo actualizado correctamente." : "Error al actualizar el insumo.";
    $key = $ok ? "mensaje" : "error";
    header("Location: ../views/admin/inventario.php?{$key}=" . urlencode($msg)); exit;
}

// ── ELIMINAR INSUMO ──────────────────────────────────────────────────────────
if ($accion == 'eliminar' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }
    $id = (int)$_GET['id'];
    $ok = sb_delete('insumos', ['id' => 'eq.' . $id]);
    $msg = $ok ? "Insumo eliminado del sistema." : "Error al eliminar. El insumo puede estar asociado a una reserva.";
    $key = $ok ? "mensaje" : "error";
    header("Location: ../views/admin/inventario.php?{$key}=" . urlencode($msg)); exit;
}

// ── AGREGAR AL CATÁLOGO ──────────────────────────────────────────────────────
if ($accion == 'nuevo_catalogo' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $nombre = trim($_POST['nombre_catalogo'] ?? '');
    if (empty($nombre)) {
        header("Location: ../views/admin/inventario.php?error=El nombre no puede estar vacío."); exit;
    }

    // Verificar que no exista ya ese nombre
    $existe = sb_get('catalogo_insumos', ['nombre' => 'eq.' . $nombre]);
    if (!empty($existe)) {
        header("Location: ../views/admin/inventario.php?error=" . urlencode("Ya existe '{$nombre}' en el catálogo.")); exit;
    }

    $ok = sb_insert('catalogo_insumos', ['nombre' => $nombre]);
    $msg = $ok ? urlencode("'{$nombre}' agregado al catálogo exitosamente.") : "Error al agregar al catálogo.";
    $key = $ok ? "mensaje" : "error";
    header("Location: ../views/admin/inventario.php?{$key}={$msg}"); exit;
}

// ── ELIMINAR DEL CATÁLOGO ────────────────────────────────────────────────────
if ($accion == 'eliminar_catalogo' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 1) { header("Location: ../views/admin/inventario.php?error=Acción no permitida."); exit; }

    $id = (int)$_GET['id'];
    // Verificar si hay insumos que usan este tipo (protección de integridad)
    $cats = sb_get('catalogo_insumos', ['id' => 'eq.' . $id], ['nombre']);
    $nombre_cat = !empty($cats) ? $cats[0]['nombre'] : '';

    $ok = sb_delete('catalogo_insumos', ['id' => 'eq.' . $id]);
    $msg = $ok ? urlencode("Tipo '{$nombre_cat}' eliminado del catálogo.") : "No se pudo eliminar el tipo del catálogo.";
    $key = $ok ? "mensaje" : "error";
    header("Location: ../views/admin/inventario.php?{$key}={$msg}"); exit;
}

header("Location: ../views/admin/inventario.php");
exit;
?>