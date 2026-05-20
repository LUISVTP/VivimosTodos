<?php
// archivo: reservas.php
session_start();
require __DIR__ . '/../../models/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php?error=Debes iniciar sesión.");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol_id     = $_SESSION['rol_id'];

// Traer reservas del residente via API REST
$reservas_raw = sb_get('reservas', ['usuario_id' => 'eq.' . $usuario_id]);
usort($reservas_raw, fn($a, $b) => strcmp($a['fecha_evento'], $b['fecha_evento']));
// Join manual nombre usuario
$reservas = array_map(function($r) use ($usuario_id) {
    $r['nombre_completo'] = $_SESSION['nombre'];
    return $r;
}, $reservas_raw);

// Insumos disponibles para el formulario del residente
$insumos_disponibles = [];
if ($rol_id == 2) {
    $ins_raw = sb_get('insumos', ['estado' => 'eq.disponible'], ['id','nombre','cantidad']);
    $insumos_disponibles = array_filter($ins_raw, fn($i) => $i['cantidad'] > 0);
    usort($insumos_disponibles, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));
    $insumos_disponibles = array_values($insumos_disponibles);
}

$min_fecha = date('Y-m-d', strtotime('+48 hours'));
$max_fecha = date('Y-m-d', strtotime('+90 days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .semaforo-verde    { background-color: #d1e7dd; border-left: 5px solid #198754; }
        .semaforo-amarillo { background-color: #fff3cd; border-left: 5px solid #ffc107; }
        .semaforo-rojo     { background-color: #f8d7da; border-left: 5px solid #dc3545; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        .card { border-radius: 12px; }
        .insumo-item { border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; background: #f8f9fa; }
        .insumo-item:hover { background: #e9ecef; }
        .cantidad-input { width: 70px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-building"></i> VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <?php if ($rol_id == 1): ?>
                    <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php"><i class="bi bi-people"></i> Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas</a></li>
                <?php elseif ($rol_id == 3): ?>
                    <li class="nav-item"><a class="nav-link" href="../supervisor/inicio.php"><i class="bi bi-house"></i> Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="inicio.php"><i class="bi bi-house"></i> Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reservas.php"><i class="bi bi-calendar-check"></i> Mis Reservas</a></li>
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
    <h2 class="mb-4"><i class="bi bi-calendar3"></i>
        <?php
            if ($rol_id == 1)     echo 'Gestión de Reservas';
            elseif ($rol_id == 3) echo 'Reservas del Salón';
            else                  echo 'Mis Reservas';
        ?>
    </h2>

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

        <!-- Formulario Nueva Reserva (SOLO RESIDENTES) -->
        <?php if ($rol_id == 2): ?>
        <div class="col-md-4">
            <div class="card shadow mb-4 border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-plus-circle"></i> Solicitar Nueva Reserva
                </div>
                <div class="card-body">
                    <form action="../../controllers/ReservaController.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Fecha del Evento</label>
                            <input type="date" name="fecha_evento" class="form-control"
                                min="<?php echo $min_fecha; ?>"
                                max="<?php echo $max_fecha; ?>" required>
                            <small class="text-muted">Mínimo 48 horas · Máximo 90 días</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción del Evento</label>
                            <textarea name="descripcion" class="form-control" rows="2"
                                placeholder="Ej: Cumpleaños, reunión familiar..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-box-seam"></i> Insumos que necesitas
                                <small class="text-muted fw-normal">(opcional)</small>
                            </label>
                            <?php if (empty($insumos_disponibles)): ?>
                                <div class="alert alert-warning py-2 small">No hay insumos disponibles en este momento.</div>
                            <?php else: ?>
                                <?php foreach ($insumos_disponibles as $ins): ?>
                                <div class="insumo-item d-flex align-items-center justify-content-between">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input insumo-check"
                                            type="checkbox"
                                            name="insumos[]"
                                            value="<?php echo $ins['id']; ?>"
                                            id="ins_<?php echo $ins['id']; ?>"
                                            data-id="<?php echo $ins['id']; ?>">
                                        <label class="form-check-label fw-semibold" for="ins_<?php echo $ins['id']; ?>">
                                            <?php echo htmlspecialchars($ins['nombre']); ?>
                                            <small class="text-muted fw-normal">(disponibles: <?php echo $ins['cantidad']; ?>)</small>
                                        </label>
                                    </div>
                                    <input type="number"
                                        name="cantidades[<?php echo $ins['id']; ?>]"
                                        id="cant_<?php echo $ins['id']; ?>"
                                        class="form-control form-control-sm cantidad-input"
                                        min="1" max="<?php echo $ins['cantidad']; ?>"
                                        value="1" disabled>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="alert alert-warning py-2 small">
                            <i class="bi bi-clock"></i> Salón de <strong>12:00 PM</strong> a <strong>12:00 AM</strong>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-send"></i> Enviar Solicitud
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabla de Reservas -->
        <div class="<?php echo ($rol_id == 2) ? 'col-md-8' : 'col-md-12'; ?>">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="bi bi-list-check"></i>
                    <?php echo ($rol_id == 2) ? 'Mis Solicitudes' : 'Todas las Solicitudes'; ?>
                </div>
                <div class="card-body table-responsive p-0">
                    <?php if (empty($reservas)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x" style="font-size:3rem"></i>
                            <p class="mt-3">No hay reservas registradas aún.</p>
                        </div>
                    <?php else: ?>
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <?php if ($rol_id != 2): ?><th>Residente</th><?php endif; ?>
                                <th>Fecha Evento</th>
                                <th>Descripción</th>
                                <th>Insumos</th>
                                <th>Solicitada</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reservas as $r):
                            $hoy      = new DateTime();
                            $evento   = new DateTime($r['fecha_evento']);
                            $diasRest = (int)$hoy->diff($evento)->format('%r%a');

                            if ($r['estado'] == 'aprobada') {
                                if ($diasRest <= 3)     $fila = 'semaforo-rojo';
                                elseif ($diasRest <= 7) $fila = 'semaforo-amarillo';
                                else                    $fila = 'semaforo-verde';
                            } else { $fila = ''; }

                            $badgeColor = match($r['estado']) {
                                'pendiente' => 'bg-warning text-dark',
                                'aprobada'  => 'bg-success',
                                'rechazada' => 'bg-danger',
                                default     => 'bg-secondary'
                            };

                            // Insumos de esta reserva
                            // Traer insumos de esta reserva via API REST + join manual
                            $ri_raw = sb_get('reserva_insumos', ['reserva_id' => 'eq.' . $r['id']], ['insumo_id','cantidad_solicitada']);
                            $insumos_reserva = [];
                            foreach ($ri_raw as $ri) {
                                $ins = sb_get('insumos', ['id' => 'eq.' . $ri['insumo_id']], ['nombre']);
                                $insumos_reserva[] = [
                                    'nombre'               => !empty($ins) ? $ins[0]['nombre'] : 'Desconocido',
                                    'cantidad_solicitada'  => $ri['cantidad_solicitada'],
                                ];
                            }
                        ?>
                        <tr class="<?php echo $fila; ?>">
                            <?php if ($rol_id != 2): ?>
                            <td class="fw-bold"><?php echo htmlspecialchars($r['nombre_completo']); ?></td>
                            <?php endif; ?>
                            <td>
                                <?php echo date('d/m/Y', strtotime($r['fecha_evento'])); ?>
                                <?php if ($r['estado'] == 'aprobada' && $diasRest >= 0): ?>
                                    <br><small class="text-muted">En <?php echo $diasRest; ?> día(s)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($r['descripcion']); ?>
                                <?php if ($r['estado'] == 'rechazada' && $r['motivo_rechazo']): ?>
                                    <br><small class="text-danger"><i class="bi bi-x-circle"></i> <?php echo htmlspecialchars($r['motivo_rechazo']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($insumos_reserva)): ?>
                                    <span class="text-muted small">Ninguno</span>
                                <?php else: ?>
                                    <?php foreach ($insumos_reserva as $ir): ?>
                                        <span class="badge bg-secondary me-1 mb-1">
                                            <?php echo htmlspecialchars($ir['nombre']); ?> (<?php echo $ir['cantidad_solicitada']; ?>)
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo date('d/m/Y H:i', strtotime($r['fecha_solicitud'])); ?></small></td>
                            <td>
                                <span class="badge <?php echo $badgeColor; ?> px-3 py-2">
                                    <?php echo ucfirst($r['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($rol_id == 1): ?>
                                    <!-- Acciones del ADMIN -->
                                    <?php if ($r['estado'] == 'pendiente'): ?>
                                    <a href="../../controllers/ReservaController.php?accion=aprobar&id=<?php echo $r['id']; ?>"
                                        class="btn btn-sm btn-success"
                                        onclick="return confirm('¿Aprobar esta reserva?')">
                                        <i class="bi bi-check-lg"></i> Aprobar
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalRechazar<?php echo $r['id']; ?>">
                                        <i class="bi bi-x-lg"></i> Rechazar
                                    </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin acciones</span>
                                    <?php endif; ?>

                                <?php elseif ($rol_id == 2): ?>
                                    <!-- Acciones del RESIDENTE -->
                                    <?php if ($r['estado'] != 'rechazada'): ?>
                                    <a href="../../controllers/ReservaController.php?accion=cancelar&id=<?php echo $r['id']; ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Estás seguro de cancelar esta reserva? Se eliminará permanentemente.')">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <!-- Supervisor: solo lectura -->
                                    <span class="text-muted small">Solo lectura</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Rechazar (solo admin) -->
                        <?php if ($rol_id == 1 && $r['estado'] == 'pendiente'): ?>
                        <div class="modal fade" id="modalRechazar<?php echo $r['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="../../controllers/ReservaController.php" method="POST">
                                        <input type="hidden" name="accion" value="rechazar">
                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title"><i class="bi bi-x-circle"></i> Rechazar Reserva</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Residente: <strong><?php echo htmlspecialchars($r['nombre_completo']); ?></strong></p>
                                            <p>Fecha: <strong><?php echo date('d/m/Y', strtotime($r['fecha_evento'])); ?></strong></p>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Motivo del rechazo</label>
                                                <textarea name="motivo_rechazo" class="form-control" rows="3"
                                                    placeholder="Explica al residente por qué se rechaza..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-danger fw-bold">Confirmar Rechazo</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Leyenda semáforo -->
            <?php if ($rol_id == 1 || $rol_id == 3): ?>
            <div class="mt-3 d-flex gap-3 flex-wrap">
                <small class="semaforo-verde px-3 py-1 rounded">🟢 Más de 7 días</small>
                <small class="semaforo-amarillo px-3 py-1 rounded">🟡 Entre 3 y 7 días</small>
                <small class="semaforo-rojo px-3 py-1 rounded">🔴 Menos de 3 días</small>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.insumo-check').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        var input = document.getElementById('cant_' + this.dataset.id);
        input.disabled = !this.checked;
        if (!this.checked) input.value = 1;
    });
});
</script>
</body>
</html>
