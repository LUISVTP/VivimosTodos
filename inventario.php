<?php
// archivo: inventario.php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php?error=Debes iniciar sesión.");
    exit;
}

$sql     = "SELECT * FROM insumos ORDER BY fecha_registro DESC";
$stmt    = $conn->query($sql);
$insumos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <?php if ($_SESSION['rol_id'] == 1): ?>
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Usuarios</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link active" href="inventario.php">Inventario Salón Social</a></li>
            </ul>
            <span class="navbar-text text-white">
                Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?> &nbsp;|&nbsp;
                <a href="logout.php" class="text-danger text-decoration-none">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</nav>
<div class="container-fluid px-4">
    <h2 class="mb-4">Inventario del Salón Social</h2>
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?php echo htmlspecialchars($_GET['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row">
        <?php if ($_SESSION['rol_id'] == 1): ?>
        <div class="col-md-3">
            <div class="card shadow mb-4 border-success">
                <div class="card-header bg-success text-white fw-bold">Agregar Nuevo Insumo</div>
                <div class="card-body">
                    <form action="acciones_inventario.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Insumo</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" name="cantidad" class="form-control" min="0" value="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select" required>
                                <option value="disponible">Disponible</option>
                                <option value="dañado">Dañado</option>
                                <option value="en reparacion">En Reparación</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Registrar Insumo</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="<?php echo ($_SESSION['rol_id'] == 1) ? 'col-md-9' : 'col-md-12'; ?>">
            <div class="card shadow">
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th><th>Nombre</th><th>Descripción</th>
                                <th>Cantidad</th><th>Estado</th><th>Fecha Registro</th>
                                <?php if ($_SESSION['rol_id'] == 1): ?><th>Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($insumos)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay insumos registrados aún.</td></tr>
                        <?php else: ?>
                            <?php foreach ($insumos as $insumo): ?>
                            <tr>
                                <td><?php echo $insumo['id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($insumo['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($insumo['descripcion']); ?></td>
                                <td><?php echo $insumo['cantidad']; ?></td>
                                <td>
                                    <?php
                                    $color = match($insumo['estado']) {
                                        'disponible'    => 'bg-success',
                                        'dañado'        => 'bg-danger',
                                        'en reparacion' => 'bg-warning text-dark',
                                        default         => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $color; ?>"><?php echo ucfirst($insumo['estado']); ?></span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($insumo['fecha_registro'])); ?></td>
                                <?php if ($_SESSION['rol_id'] == 1): ?>
                                <td>
                                    <a href="acciones_inventario.php?accion=eliminar&id=<?php echo $insumo['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('¿Eliminar este insumo?')">Eliminar</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
