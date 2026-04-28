<?php
// archivo: acciones_inventario.php
session_start();
require 'conexion.php';

// Validar seguridad
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

// ACCIÓN: CREAR INSUMO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $cantidad = (int)$_POST['cantidad'];
    $estado = $_POST['estado'];

    $sql = "INSERT INTO insumos (nombre, descripcion, cantidad, estado) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssis", $nombre, $descripcion, $cantidad, $estado);
    
    if($stmt->execute()) {
        header("Location: inventario.php?mensaje=Insumo registrado correctamente.");
    } else {
        header("Location: inventario.php?mensaje=Error al registrar el insumo.");
    }
    exit();
}

// ACCIÓN: ELIMINAR INSUMO
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion']) && $_GET['accion'] == 'eliminar') {
    $id = (int)$_GET['id'];

    $sql = "DELETE FROM insumos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if($stmt->execute()) {
        header("Location: inventario.php?mensaje=Insumo eliminado del sistema.");
    } else {
        header("Location: inventario.php?mensaje=Error al eliminar.");
    }
    exit();
}
?>