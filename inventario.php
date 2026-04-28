<?php
// archivo: inventario.php
session_start();
require 'conexion.php';

// Validar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php?error=Debes iniciar sesión.");
    exit();
}

// Obtener la lista de insumos
$sql = "SELECT * FROM insumos ORDER BY fecha_registro DESC";
$resultado = $conn->query($sql);
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
                <?php if($_SESSION['rol_id'] == 1): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Usuarios</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link active" href="inventario.php">Inventario Salón Social</a>
                </li>
            </ul>
            <span class="navbar-text text-white">
                Bienvenido, <?php echo $_SESSION['nombre']; ?> | 
                <a href="logout.php" class="text-danger text-decoration-none">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4">Gestión de Inventario</h2>

    <?php if(isset($_GET['mensaje'])): ?>
        <div class="alert alert-info"><?php echo $_GET['mensaje']; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">Agregar Nuevo Insumo</div>
                <div class="card-body">
                    <form action="acciones_inventario.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre del Artículo</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Silla Rimax" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles del estado o color..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" name="cantidad" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado Físico</label>
                            <select name="estado" class="form-select" required>
                                <option value="disponible" selected>Disponible</option>
                                <option value="dañado">Dañado</option>
                                <option value="en reparacion">En Reparación</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Registrar Insumo</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($fila = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $fila['id']; ?></td>
                                <td>
                                    <strong><?php echo $fila['nombre']; ?></strong><br>
                                    <small class="text-muted"><?php echo $fila['descripcion']; ?></small>
                                </td>
                                <td><?php echo $fila['cantidad']; ?></td>
                                <td>
                                    <?php 
                                        // Darle color al estado con badges de Bootstrap
                                        if($fila['estado'] == 'disponible') echo '<span class="badge bg-success">Disponible</span>';
                                        elseif($fila['estado'] == 'dañado') echo '<span class="badge bg-danger">Dañado</span>';
                                        else echo '<span class="badge bg-warning text-dark">En reparación</span>';
                                    ?>
                                </td>
                                <td>
                                    <a href="acciones_inventario.php?accion=eliminar&id=<?php echo $fila['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar este insumo del inventario?');">Eliminar</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>