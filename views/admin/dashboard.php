<?php
// archivo: views/admin/dashboard.php
session_start();
require __DIR__ . '/../../models/Database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../../views/auth/login.php?error=Acceso denegado. Solo administradores.");
    exit;
}

// ── 1. Usuarios ─────────────────────────────────────────────────────────────
$usuarios_raw = sb_get('usuarios');
$roles_arr    = sb_get('roles');
$roles_map    = [];
foreach ($roles_arr as $ro) $roles_map[$ro['id']] = $ro['nombre'];
foreach ($usuarios_raw as &$u) $u['rol_nombre'] = $roles_map[$u['rol_id']] ?? 'Sin rol';
unset($u);
$usuarios = $usuarios_raw;

// ── 2. Contadores rápidos ────────────────────────────────────────────────────
$total_usuarios = count($usuarios);
$total_insumos  = count(sb_get('insumos', [], ['id']));
$pend_arr       = sb_get('reservas', ['estado' => 'eq.pendiente'], ['id']);
$pendientes     = count($pend_arr);

// ── 3. Semáforo: reservas aprobadas próximas (próximos 7 días) ───────────────
$hoy_str  = date('Y-m-d');
$hoy_dt   = new DateTime($hoy_str);
$en7dias  = date('Y-m-d', strtotime('+7 days'));

$apr_raw = sb_get('reservas', ['estado' => 'eq.aprobada']);
$apr_raw = array_filter($apr_raw, fn($r) =>
    $r['fecha_evento'] >= $hoy_str && $r['fecha_evento'] <= $en7dias
);
usort($apr_raw, fn($a, $b) => strcmp($a['fecha_evento'], $b['fecha_evento']));

// Join manual de usuarios para el semáforo
$usr_map = [];
foreach ($usuarios_raw as $u) $usr_map[$u['id']] = $u['nombre_completo'];
$proximas = [];
foreach ($apr_raw as $r) {
    $r['nombre_completo'] = $usr_map[$r['usuario_id']] ?? 'Desconocido';
    $proximas[] = $r;
}

// ── 4. Manejo de mensajes de acción ─────────────────────────────────────────
$msg_info  = $_GET['mensaje'] ?? '';
$msg_error = $_GET['error']   ?? '';
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
        body { background: #f1f3f5; }
        .stat-card { border-radius: 16px; transition: transform .2s; }
        .stat-card:hover { transform: translateY(-4px); }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        .table th { font-size: .85rem; text-transform: uppercase; letter-spacing: .05em; }
    </style>
</head>
<body>

<!-- ═══ NAVBAR ═══════════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-building"></i> VivimosTodos
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navAdmin">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-people"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="inventario.php">
                        <i class="bi bi-box-seam"></i> Inventario
                    </a>
                </li>
                <li class="nav-item dropdown">
                    
                    <li class="nav-item"><a class="nav-link" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas</a></li>
                    
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="reservas.php"><i class="bi bi-calendar-check me-2"></i>Gestión de Reservas</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="informes.php"><i class="bi bi-bar-chart me-2"></i>Informes</a></li>
                    </ul>
                </li>
            </ul>
            <span class="navbar-text text-white">
                <i class="bi bi-person-circle"></i>
                <?php echo htmlspecialchars($_SESSION['nombre']); ?> &nbsp;|&nbsp;
                <a href="../../controllers/LogoutController.php" class="text-danger text-decoration-none">
                    Cerrar Sesión
                </a>
            </span>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">

    <!-- ═══ TARJETAS RESUMEN ══════════════════════════════════════════════════ -->
    <div class="row mb-4 g-3">
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

    <!-- ═══ SEMÁFORO RESERVAS PRÓXIMAS ═══════════════════════════════════════ -->
    <div class="card shadow border-0 mb-4">
        <div class="card-header fw-bold" style="background:#1a1a2e;color:#fff;">
            <i class="bi bi-traffic-light"></i> Reservas Aprobadas Próximas a Vencer
        </div>
        <div class="card-body p-0">
            <?php if (empty($proximas)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-calendar-x" style="font-size:2rem"></i>
                    <p class="mt-2 mb-0">No hay reservas aprobadas en los próximos 7 días.</p>
                </div>
            <?php else: ?>
            <table class="table table-hover align-middle mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>Estado</th>
                        <th>Residente</th>
                        <th>Fecha Evento</th>
                        <th>Descripción</th>
                        <th>Días Restantes</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($proximas as $p):
                    $evento_dt = new DateTime($p['fecha_evento']);
                    $diff      = $hoy_dt->diff($evento_dt);
                    $dias      = (int)$diff->format('%r%a');
                    if ($dias <= 0) {
                        $clase = 'table-danger';  $icono = '🔴'; $texto = 'HOY';
                    } elseif ($dias <= 3) {
                        $clase = 'table-danger';  $icono = '🔴'; $texto = "En $dias día(s)";
                    } elseif ($dias <= 7) {
                        $clase = 'table-warning'; $icono = '🟡'; $texto = "En $dias día(s)";
                    } else {
                        $clase = 'table-success'; $icono = '🟢'; $texto = "En $dias día(s)";
                    }
                    $badge = ($dias <= 3) ? 'bg-danger' : (($dias <= 7) ? 'bg-warning text-dark' : 'bg-success');
                ?>
                <tr class="<?php echo $clase; ?>">
                    <td style="font-size:1.4rem;text-align:center"><?php echo $icono; ?></td>
                    <td class="fw-bold"><?php echo htmlspecialchars($p['nombre_completo']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($p['fecha_evento'])); ?></td>
                    <td><?php echo htmlspecialchars($p['descripcion'] ?? ''); ?></td>
                    <td>
                        <span class="badge <?php echo $badge; ?> px-3 py-2"><?php echo $texto; ?></span>
                    </td>
                    <td>
                        <a href="reservas.php" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-light small text-muted">
            <span class="me-3">🔴 Menos de 3 días</span>
            <span class="me-3">🟡 Entre 3 y 7 días</span>
            <span>🟢 Más de 7 días</span>
        </div>
    </div>

    <!-- ═══ ADMINISTRACIÓN DE USUARIOS ═══════════════════════════════════════ -->
    <h4 class="mb-3"><i class="bi bi-people"></i> Administración de Usuarios</h4>

    <?php if ($msg_info): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($msg_info); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($msg_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ── Formulario Crear Usuario ── -->
        <div class="col-lg-3">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-person-plus"></i> Crear Nuevo Usuario
                </div>
                <div class="card-body">
                    <form action="../../controllers/UsuarioController.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Juan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Apellido Completo</label>
                            <input type="text" name="apellido" class="form-control" required placeholder="Ej: Pérez">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" required placeholder="correo@ejemplo.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rol</label>
                            <select name="rol_id" class="form-select" required>
                                <?php foreach ($roles_arr as $rol): ?>
                                    <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars($rol['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-person-check"></i> Crear Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Lista de Usuarios ── -->
        <div class="col-lg-9">
            <div class="card shadow border-0">
                <div class="card-header fw-bold" style="background:#1a1a2e;color:#fff;">
                    <i class="bi bi-table"></i> Lista de Usuarios
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-people" style="font-size:2rem"></i>
                                        <p class="mt-2 mb-0">No hay usuarios registrados.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usr): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $usr['id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($usr['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($usr['correo']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($usr['rol_nombre']); ?></span></td>
                                    <td>
                                        <span class="badge <?php echo $usr['estado'] === 'activo' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo ucfirst($usr['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <!-- Editar -->
                                            <button type="button" class="btn btn-sm btn-info text-white"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditar"
                                                data-id="<?php echo $usr['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($usr['nombre_completo']); ?>"
                                                data-correo="<?php echo htmlspecialchars($usr['correo']); ?>"
                                                data-rol="<?php echo $usr['rol_id']; ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                            <!-- Cambiar estado -->
                                            <a href="../../controllers/UsuarioController.php?accion=estado&id=<?php echo $usr['id']; ?>&estado_actual=<?php echo $usr['estado']; ?>"
                                               class="btn btn-sm btn-warning"
                                               onclick="return confirm('¿Cambiar estado de este usuario?')">
                                                <i class="bi bi-toggle-on"></i> Estado
                                            </a>
                                            <!-- Eliminar -->
                                            <?php if ($usr['id'] != 1): ?>
                                            <a href="../../controllers/UsuarioController.php?accion=eliminar&id=<?php echo $usr['id']; ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /row usuarios -->

</div><!-- /container -->

<!-- ═══ MODAL EDITAR USUARIO ══════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/UsuarioController.php" method="POST">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre Completo</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Correo Electrónico</label>
                        <input type="email" name="correo" id="edit_correo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rol</label>
                        <select name="rol_id" id="edit_rol" class="form-select">
                            <?php foreach ($roles_arr as $rol): ?>
                                <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars($rol['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Rellena el modal de edición con los datos del botón
document.getElementById('modalEditar').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('edit_id').value      = btn.dataset.id;
    document.getElementById('edit_nombre').value  = btn.dataset.nombre;
    document.getElementById('edit_correo').value  = btn.dataset.correo;
    document.getElementById('edit_rol').value     = btn.dataset.rol;
});
</script>
</body>
</html>
