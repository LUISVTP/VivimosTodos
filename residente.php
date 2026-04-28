<?php
// archivo: residente.php
session_start();

// Validar que esté logueado y sea estrictamente Residente (Rol 2)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 2) {
    header("Location: index.php?error=Acceso denegado. Pantalla exclusiva para residentes.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Residente - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="#">VivimosTodos - Residente</a>
        <span class="navbar-text text-white">
            Hola, <?php echo $_SESSION['nombre']; ?> | 
            <a href="logout.php" class="text-warning fw-bold text-decoration-none">Cerrar Sesión</a>
        </span>
    </div>
</nav>

<div class="container text-center mt-5">
    <div class="card shadow border-0 p-5 rounded-4">
        <h1 class="display-5 text-success mb-3">¡Bienvenido a tu portal, <?php echo $_SESSION['nombre']; ?>! 👋</h1>
        <p class="lead text-muted">Has iniciado sesión correctamente como Residente.</p>
        <p>Próximamente desde aquí podrás realizar las solicitudes de reserva para el salón social.</p>
    </div>
</div>

</body>
</html>