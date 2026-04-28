<?php
// archivo: supervisor.php
session_start();

// Validar que esté logueado y sea estrictamente Supervisor (Rol 3)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) {
    header("Location: index.php?error=Acceso denegado. Pantalla exclusiva para supervisores.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Supervisor - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-info mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand text-dark fw-bold" href="#">VivimosTodos - Supervisor</a>
        <span class="navbar-text text-dark">
            Usuario: <?php echo $_SESSION['nombre']; ?> | 
            <a href="logout.php" class="text-danger fw-bold text-decoration-none">Cerrar Sesión</a>
        </span>
    </div>
</nav>

<div class="container text-center mt-5">
    <div class="card shadow border-0 p-5 rounded-4">
        <h1 class="display-5 text-info mb-3">Panel de Supervisión</h1>
        <p class="lead text-muted">Sesión iniciada como Supervisor.</p>
        <p>En el futuro, aquí podrás visualizar los eventos programados y auditar el estado del inventario del salón social.</p>
    </div>
</div>

</body>
</html>