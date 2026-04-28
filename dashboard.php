<?php
// archivo: dashboard.php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: index.php?error=Acceso denegado. Solo administradores.");
    exit;
}

$sql      = "SELECT u.id, u.nombre_completo, u.correo, u.estado, u.rol_id, r.nombre as rol_nombre
             FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id";
$resultado = $conn->query($sql);
$usuarios  = $resultado->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Usuarios</a></li>
                <li class="nav-item"><a class="nav-link" href="inventario.php">Inventario Salón Social</a></li>
            </ul>
            <span class="navbar-text text-white">
                Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?> &nbsp;|&nbsp;
                <a href="logout.php" class="text-danger text-decoration-none">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <h2 class="mb-4">Administración de Usuarios</h2>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3">
            <div class="card shadow mb-4 border-primary">
                <div class="card-header bg-primary text-white fw-bold">Crear Nuevo Usuario</div>
                <div class="card-body">
                    <form action="acciones_usuario.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Perfil / Rol</label>
                            <select name="rol_id" class="form-select" required>
                                <option value="1">Administrador</option>
                                <option value="2" selected>Residente</option>
                                <option value="3">Supervisor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado Inicial</label>
                            <select name="estado" class="form-select" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Crear Usuario</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th><th>Nombre</th><th>Correo</th>
                                <th>Rol</th><th>Estado</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usuarios as $fila): ?>
                            <tr>
                                <td><?php echo $fila['id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($fila['nombre_completo']); ?></td>
                                <td><?php echo htmlspecialchars($fila['correo']); ?></td>
                                <td><?php echo $fila['rol_nombre']; ?></td>
                                <td>
                                    <?php if ($fila['estado'] == 'activo'): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditar<?php echo $fila['id']; ?>">
                                        Editar
                                    </button>
                                    <a href="acciones_usuario.php?accion=estado&id=<?php echo $fila['id']; ?>&estado_actual=<?php echo $fila['estado']; ?>"
                                        class="btn btn-sm btn-warning">Estado</a>
                                    <a href="acciones_usuario.php?accion=eliminar&id=<?php echo $fila['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</a>
                                </td>
                            </tr>

                            <!-- Modal Editar -->
                            <div class="modal fade" id="modalEditar<?php echo $fila['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="acciones_usuario.php" method="POST">
                                            <input type="hidden" name="accion" value="actualizar">
                                            <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title">Editar: <?php echo htmlspecialchars($fila['nombre_completo']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Nombre Completo</label>
                                                    <input type="text" name="nombre" class="form-control"
                                                        value="<?php echo htmlspecialchars($fila['nombre_completo']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Correo Electrónico</label>
                                                    <input type="email" name="correo" class="form-control"
                                                        value="<?php echo htmlspecialchars($fila['correo']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Perfil / Rol</label>
                                                    <select name="rol_id" class="form-select" required>
                                                        <option value="1" <?php if($fila['rol_id']==1) echo 'selected'; ?>>Administrador</option>
                                                        <option value="2" <?php if($fila['rol_id']==2) echo 'selected'; ?>>Residente</option>
                                                        <option value="3" <?php if($fila['rol_id']==3) echo 'selected'; ?>>Supervisor</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-info text-white fw-bold">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
