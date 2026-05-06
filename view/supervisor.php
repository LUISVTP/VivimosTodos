<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Supervisor - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-building"></i> VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                <li class="nav-item"><a class="nav-link" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas</a></li>
            </ul>
            <span class="navbar-text text-white">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?> &nbsp;|&nbsp;
                <a href="logout.php" class="text-danger text-decoration-none">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</nav>
<div class="container">
    <div class="row mt-4">
        <div class="col-md-4">
            <a href="inventario.php" class="text-decoration-none">
                <div class="card shadow border-0 text-center p-4 mb-3 bg-info text-white" style="border-radius:16px">
                    <i class="bi bi-box-seam" style="font-size:3rem"></i>
                    <h5 class="mt-3 fw-bold">Inventario</h5>
                    <p class="mb-0 small">Audita el estado de los insumos del salón</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="reservas.php" class="text-decoration-none">
                <div class="card shadow border-0 text-center p-4 mb-3 bg-secondary text-white" style="border-radius:16px">
                    <i class="bi bi-calendar3" style="font-size:3rem"></i>
                    <h5 class="mt-3 fw-bold">Ver Reservas</h5>
                    <p class="mb-0 small">Consulta las reservas programadas</p>
                </div>
            </a>
        </div>
    </div>
    <div class="alert alert-info mt-2">
        <h5>Bienvenido, Supervisor <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong></h5>
        <p class="mb-0">Desde aquí puedes auditar el inventario y visualizar los eventos programados del salón social.</p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
