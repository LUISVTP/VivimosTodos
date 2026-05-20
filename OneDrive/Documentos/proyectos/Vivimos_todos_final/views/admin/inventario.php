<?php
// archivo: inventario.php
session_start();
require __DIR__ . '/../../models/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../views/auth/login.php?error=Debes iniciar sesión.");
    exit;
}

$rol_id  = $_SESSION['rol_id'];

// Traer insumos y catálogo via API REST
$insumos  = sb_get('insumos');
usort($insumos, fn($a, $b) => strcmp($b['fecha_registro'] ?? '', $a['fecha_registro'] ?? ''));
$catalogo = sb_get('catalogo_insumos');
usort($catalogo, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card { border-radius: 12px; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-building"></i> VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <?php if ($rol_id == 1): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-people"></i> Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link active" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas</a></li>

                <?php elseif ($rol_id == 3): ?>
                    <li class="nav-item"><a class="nav-link" href="../supervisor/inicio.php"><i class="bi bi-house"></i> Inicio</a></li>
                    <li class="nav-item"><a class="nav-link active" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas</a></li>

                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="../residente/inicio.php"><i class="bi bi-house"></i> Inicio</a></li>
                    <li class="nav-item"><a class="nav-link active" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservas.php"><i class="bi bi-calendar-check"></i> Mis Reservas</a></li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text text-white">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?> &nbsp;|&nbsp;
                <a href="../../controllers/LogoutController.php" class="text-danger text-decoration-none">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <h2 class="mb-4"><i class="bi bi-box-seam"></i> Inventario del Salón Social</h2>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($_GET['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($rol_id == 1): ?>
        <div class="col-md-3">

            <!-- Formulario agregar insumo desde catálogo -->
            <div class="card shadow mb-4 border-0">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="bi bi-plus-circle"></i> Agregar Insumo
                </div>
                <div class="card-body">
                    <form action="../../controllers/InventarioController.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de Insumo</label>
                            <select name="catalogo_id" class="form-select" required>
                                <option value="">-- Selecciona un insumo --</option>
                                <?php foreach ($catalogo as $c): ?>
                                    <option value="<?php echo $c['id']; ?>">
                                        <?php echo htmlspecialchars($c['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción <small class="text-muted fw-normal">(opcional)</small></label>
                            <textarea name="descripcion" class="form-control" rows="2"
                                placeholder="Ej: Sillas plásticas azules..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Cantidad</label>
                            <input type="number" name="cantidad" class="form-control" min="0" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="estado" class="form-select" required>
                                <option value="disponible">Disponible</option>
                                <option value="dañado">Dañado</option>
                                <option value="en reparacion">En Reparación</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">marca <small class="text-muted fw-normal">(opcional)</small></label>
                            <textarea name="marca" class="form-control" rows="2"
                                placeholder="Ej: Rimax,"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="bi bi-plus-lg"></i> Registrar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Formulario para agregar nuevo tipo al catálogo -->
            <div class="card shadow mb-4 border-0">
                <div class="card-header bg-secondary text-white fw-bold">
                    <i class="bi bi-list-ul"></i> Agregar al Catálogo
                </div>
                <div class="card-body">
                    <p class="small text-muted">¿No encuentras el insumo en la lista? Agrégalo aquí.</p>
                    <form action="../../controllers/InventarioController.php" method="POST">
                        <input type="hidden" name="accion" value="nuevo_catalogo">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre del nuevo tipo</label>
                            <input type="text" name="nombre_catalogo" class="form-control"
                                placeholder="Ej: Mantelería, Pantalla LED..." required>
                        </div>
                        <button type="submit" class="btn btn-secondary w-100 fw-bold">
                            <i class="bi bi-plus-lg"></i> Agregar al Catálogo
                        </button>
                    </form>

                    <!-- Lista actual del catálogo -->
                    <hr>
                    <p class="small fw-semibold mb-2">Catálogo actual:</p>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($catalogo as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                            <small><?php echo htmlspecialchars($c['nombre']); ?></small>
                            <a href="../../controllers/InventarioController.php?accion=eliminar_catalogo&id=<?php echo $c['id']; ?>"
                                class="btn btn-sm btn-outline-danger py-0 px-1"
                                onclick="return confirm('¿Eliminar este tipo del catálogo?')"
                                title="Eliminar del catálogo">
                                <i class="bi bi-x"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
        <?php endif; ?>

        <!-- Tabla de insumos -->
        <div class="<?php echo ($rol_id == 1) ? 'col-md-9' : 'col-md-12'; ?>">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="bi bi-table"></i> Lista de Insumos Registrados
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID</th><th>Nombre</th><th>Descripción</th>
                                <th>Cantidad</th><th>Estado</th><th>Fecha Registro</th>
                                <?php if ($rol_id == 1): ?><th>Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($insumos)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size:2rem"></i>
                                <p class="mt-2">No hay insumos registrados aún.</p>
                            </td></tr>
                        <?php else: ?>
                        <?php foreach ($insumos as $insumo):
                            $color = match($insumo['estado']) {
                                'disponible'    => 'bg-success',
                                'dañado'        => 'bg-danger',
                                'en reparacion' => 'bg-warning text-dark',
                                default         => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <td><?php echo $insumo['id']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($insumo['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($insumo['descripcion'] ?? '—'); ?></td>
                            <td><strong><?php echo $insumo['cantidad']; ?></strong></td>
                            <td><span class="badge <?php echo $color; ?> px-3 py-2"><?php echo ucfirst($insumo['estado']); ?></span></td>
                            <td><small><?php echo date('d/m/Y', strtotime($insumo['fecha_registro'])); ?></small></td>
                            <?php if ($rol_id == 1): ?>
                            <td>
                                <button class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarInsumo<?php echo $insumo['id']; ?>">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <a href="../../controllers/InventarioController.php?accion=eliminar&id=<?php echo $insumo['id']; ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('¿Eliminar este insumo del inventario?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Modal Editar Insumo -->
                        <?php if ($rol_id == 1): ?>
                        <div class="modal fade" id="modalEditarInsumo<?php echo $insumo['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="../../controllers/InventarioController.php" method="POST">
                                        <input type="hidden" name="accion" value="actualizar">
                                        <input type="hidden" name="id" value="<?php echo $insumo['id']; ?>">
                                        <div class="modal-header bg-warning">
                                            <h5 class="modal-title fw-bold">
                                                <i class="bi bi-pencil"></i> Editar Insumo
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Tipo de Insumo</label>
                                                <select name="catalogo_id" class="form-select" required>
                                                    <?php foreach ($catalogo as $c): ?>
                                                        <option value="<?php echo $c['id']; ?>"
                                                            <?php if($insumo['nombre'] == $c['nombre']) echo 'selected'; ?>>
                                                            <?php echo htmlspecialchars($c['nombre']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Descripción</label>
                                                <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($insumo['descripcion'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Cantidad</label>
                                                <input type="number" name="cantidad" class="form-control"
                                                    value="<?php echo $insumo['cantidad']; ?>" min="0" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Estado</label>
                                                <select name="estado" class="form-select" required>
                                                    <option value="disponible" <?php if($insumo['estado']=='disponible') echo 'selected'; ?>>Disponible</option>
                                                    <option value="dañado" <?php if($insumo['estado']=='dañado') echo 'selected'; ?>>Dañado</option>
                                                    <option value="en reparacion" <?php if($insumo['estado']=='en reparacion') echo 'selected'; ?>>En Reparación</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-warning fw-bold">
                                                <i class="bi bi-save"></i> Guardar Cambios
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

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
