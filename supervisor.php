<?php
// archivo: supervisor.php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) {
    header("Location: index.php?error=Acceso denegado.");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Supervisor - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="inventario.php">Ver Inventario</a></li>
            </ul>
            <span class="navbar-text text-white">
                Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?> &nbsp;|&nbsp;
                <a href="logout.php" class="text-danger text-decoration-none">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</nav>
<div class="container">
    <div class="alert alert-info mt-4">
        <h4>👁️ Sesión iniciada como <strong>Supervisor</strong>.</h4>
        <p>En el futuro, aquí podrás visualizar los eventos programados y auditar el estado del inventario del salón social.</p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
