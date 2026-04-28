<?php
// archivo: acciones_inventario.php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

// CREAR INSUMO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre      = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $cantidad    = (int)$_POST['cantidad'];
    $estado      = $_POST['estado'];

    $sql  = "INSERT INTO insumos (nombre, descripcion, cantidad, estado) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$nombre, $descripcion, $cantidad, $estado])) {
        header("Location: inventario.php?mensaje=Insumo registrado correctamente.");
    } else {
        header("Location: inventario.php?mensaje=Error al registrar el insumo.");
    }
    exit;
}

// ELIMINAR INSUMO
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'eliminar') {
    $id = (int)$_GET['id'];

    $sql  = "DELETE FROM insumos WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$id])) {
        header("Location: inventario.php?mensaje=Insumo eliminado del sistema.");
    } else {
        header("Location: inventario.php?mensaje=Error al eliminar.");
    }
    exit;
}
?>
