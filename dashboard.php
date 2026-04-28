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

// Contadores rápidos
$total_usuarios  = count($usuarios);
$sql_res         = "SELECT COUNT(*) as total FROM reservas WHERE estado = 'pendiente'";
$pendientes      = $conn->query($sql_res)->fetch()['total'];
$sql_inv         = "SELECT COUNT(*) as total FROM insumos";
$total_insumos   = $conn->query($sql_inv)->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card { border-radius: 12px; }
        .stat-card { border-radius: 16px; transition: transform .2s; }
        .stat-card:hover { transform: translateY(-4px); }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-building"></i> VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-people"></i> Usuarios</a></li>
                <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                <li class="nav-item">
                    <a class="nav-link" href="reservas.php">
                        <i class="bi bi-calendar-check"></i> Reservas
                        <?php if ($pendientes > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $pendientes; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            <span class="navbar-text text-white">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?> &nbsp;|&nbsp;
                <a href="logout.php" class="text-danger text-decoration-none">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">

    <!-- Tarjetas de resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card shadow border-0 bg-primary text-white p-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-people-fill" style="font-size:2.5rem"></i>
                    <div>
                        <div style="font-size:2rem;font-weight:700"><?php echo $total_usuarios; ?></div>
                        <div>Usuarios registrados</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow border-0 bg-warning p-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-clock-history" style="font-size:2.5rem"></i>
                    <div>
                        <div style="font-size:2rem;font-weight:700"><?php echo $pendientes; ?></div>
                        <div>Reservas pendientes</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow border-0 bg-success text-white p-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-box-seam" style="font-size:2.5rem"></i>
                    <div>
                        <div style="font-size:2rem;font-weight:700"><?php echo $total_insumos; ?></div>
                        <div>Insumos en inventario</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-3"><i class="bi bi-people"></i> Administración de Usuarios</h4>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($_GET['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3">
            <div class="card shadow mb-4 border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-person-plus"></i> Crear Nuevo Usuario
                </div>
                <div class="card-body">
                    <form action="acciones_usuario.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Perfil / Rol</label>
                            <select name="rol_id" class="form-select" required>
                                <option value="1">Administrador</option>
                                <option value="2" selected>Residente</option>
                                <option value="3">Supervisor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado Inicial</label>
                            <select name="estado" class="form-select" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-person-plus"></i> Crear Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="bi bi-table"></i> Lista de Usuarios
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-secondary">
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
                                        <span class="badge bg-success px-3 py-2">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-3 py-2">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditar<?php echo $fila['id']; ?>">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <a href="acciones_usuario.php?accion=estado&id=<?php echo $fila['id']; ?>&estado_actual=<?php echo $fila['estado']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="bi bi-toggle-on"></i> Estado
                                    </a>
                                    <a href="acciones_usuario.php?accion=eliminar&id=<?php echo $fila['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
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
                                                <h5 class="modal-title">
                                                    <i class="bi bi-pencil"></i> Editar: <?php echo htmlspecialchars($fila['nombre_completo']); ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Nombre Completo</label>
                                                    <input type="text" name="nombre" class="form-control"
                                                        value="<?php echo htmlspecialchars($fila['nombre_completo']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Correo Electrónico</label>
                                                    <input type="email" name="correo" class="form-control"
                                                        value="<?php echo htmlspecialchars($fila['correo']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Perfil / Rol</label>
                                                    <select name="rol_id" class="form-select" required>
                                                        <option value="1" <?php if($fila['rol_id']==1) echo 'selected'; ?>>Administrador</option>
                                                        <option value="2" <?php if($fila['rol_id']==2) echo 'selected'; ?>>Residente</option>
                                                        <option value="3" <?php if($fila['rol_id']==3) echo 'selected'; ?>>Supervisor</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-info text-white fw-bold">
                                                    <i class="bi bi-save"></i> Guardar Cambios
                                                </button>
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
