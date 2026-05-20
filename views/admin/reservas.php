<?php
// archivo: views/admin/reservas.php
// Interfaz unificada: Gestión de Reservas + Estadísticas + Exportar (v10)
session_start();
require __DIR__ . '/../../models/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../views/auth/login.php?error=Debes iniciar sesión.");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol_id     = $_SESSION['rol_id'];

// ── Reservas según rol ────────────────────────────────────────────
if ($rol_id == 1 || $rol_id == 3) {
    $stmt = $conn->query(
        "SELECT r.*, u.nombre_completo FROM reservas r
         INNER JOIN usuarios u ON r.usuario_id = u.id
         ORDER BY r.fecha_evento ASC"
    );
} else {
    $stmt = $conn->prepare(
        "SELECT r.*, u.nombre_completo FROM reservas r
         INNER JOIN usuarios u ON r.usuario_id = u.id
         WHERE r.usuario_id = ?
         ORDER BY r.fecha_evento ASC"
    );
    $stmt->execute([$usuario_id]);
}
$reservas = $stmt->fetchAll();

// ── Insumos disponibles (solo residente) ─────────────────────────
$insumos_disponibles = [];
if ($rol_id == 2) {
    $stmt_ins = $conn->query(
        "SELECT id, nombre, cantidad FROM insumos
         WHERE estado = 'disponible' AND cantidad > 0
         ORDER BY nombre ASC"
    );
    $insumos_disponibles = $stmt_ins->fetchAll();
}

$min_fecha = date('Y-m-d', strtotime('+48 hours'));
$max_fecha = date('Y-m-d', strtotime('+90 days'));

// ── Estadísticas (solo admin y supervisor) ────────────────────────
if ($rol_id == 1 || $rol_id == 3) {
    $total_reservas  = $conn->query("SELECT COUNT(*) FROM reservas")->fetchColumn();
    $pendientes_cnt  = $conn->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
    $aprobadas_cnt   = $conn->query("SELECT COUNT(*) FROM reservas WHERE estado = 'aprobada'")->fetchColumn();
    $rechazadas_cnt  = $conn->query("SELECT COUNT(*) FROM reservas WHERE estado = 'rechazada'")->fetchColumn();
    $total_insumos   = $conn->query("SELECT COUNT(*) FROM insumos")->fetchColumn();
    $insumos_danados = $conn->query("SELECT COUNT(*) FROM insumos WHERE estado = 'dañado'")->fetchColumn();

    // Gráfica 1: reservas por mes (últimos 6 meses)
    $datos_mes = $conn->query(
        "SELECT TO_CHAR(fecha_evento,'Mon YYYY') AS mes,
                COUNT(*) AS total
         FROM reservas
         WHERE fecha_evento >= CURRENT_DATE - INTERVAL '6 months'
         GROUP BY TO_CHAR(fecha_evento,'Mon YYYY'), DATE_TRUNC('month', fecha_evento)
         ORDER BY DATE_TRUNC('month', fecha_evento) ASC"
    )->fetchAll();
    $labels_mes  = json_encode(array_column($datos_mes, 'mes'));
    $values_mes  = json_encode(array_map('intval', array_column($datos_mes, 'total')));

    // Gráfica 2: torta de estados
    $labels_estados = json_encode(['Pendientes','Aprobadas','Rechazadas']);
    $values_estados = json_encode([(int)$pendientes_cnt,(int)$aprobadas_cnt,(int)$rechazadas_cnt]);

    // Gráfica 3: inventario por estado
    $datos_inv = $conn->query(
        "SELECT estado, COUNT(*) AS total FROM insumos GROUP BY estado"
    )->fetchAll();
    $labels_inv = json_encode(array_column($datos_inv, 'estado'));
    $values_inv = json_encode(array_map('intval', array_column($datos_inv, 'total')));

    // Filtros para pestaña Exportar (vista previa)
    $filtro_estado      = $_GET['filtro_estado']      ?? 'todos';
    $filtro_fecha_desde = $_GET['filtro_fecha_desde'] ?? '';
    $filtro_fecha_hasta = $_GET['filtro_fecha_hasta'] ?? '';
    $where  = "WHERE 1=1";
    $params = [];
    if ($filtro_estado !== 'todos') { $where .= " AND r.estado = :est"; $params[':est'] = $filtro_estado; }
    if ($filtro_fecha_desde)        { $where .= " AND r.fecha_evento >= :desde"; $params[':desde'] = $filtro_fecha_desde; }
    if ($filtro_fecha_hasta)        { $where .= " AND r.fecha_evento <= :hasta"; $params[':hasta'] = $filtro_fecha_hasta; }
    $stmt_prev = $conn->prepare(
        "SELECT r.id, u.nombre_completo, r.fecha_evento, r.descripcion, r.estado
         FROM reservas r INNER JOIN usuarios u ON r.usuario_id = u.id
         $where ORDER BY r.fecha_evento DESC LIMIT 10"
    );
    $stmt_prev->execute($params);
    $preview = $stmt_prev->fetchAll();
}

// ── Determinar pestaña activa desde URL ──────────────────────────
$tab = $_GET['tab'] ?? 'gestion';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <?php if ($rol_id == 1 || $rol_id == 3): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    <style>
        body { background: #f2f4f7; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        .card  { border-radius: 14px; border: none; }

        /* Semáforo de filas */
        .semaforo-verde    { background-color: #d1e7dd; border-left: 5px solid #198754; }
        .semaforo-amarillo { background-color: #fff3cd; border-left: 5px solid #ffc107; }
        .semaforo-rojo     { background-color: #f8d7da; border-left: 5px solid #dc3545; }

        /* Insumos en formulario */
        .insumo-item { border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; background: #f8f9fa; }
        .insumo-item:hover { background: #e9ecef; }
        .cantidad-input { width: 70px; }

        /* Tabs personalizados */
        .nav-tabs-custom { border-bottom: 2px solid #dee2e6; margin-bottom: 1.5rem; }
        .nav-tabs-custom .nav-link {
            color: #6c757d; font-weight: 600; border: none;
            padding: .6rem 1.2rem; border-radius: 0;
            border-bottom: 3px solid transparent; margin-bottom: -2px;
        }
        .nav-tabs-custom .nav-link:hover { color: #1a1a2e; }
        .nav-tabs-custom .nav-link.active { color: #1a1a2e; border-bottom-color: #1a1a2e; }

        /* Tarjetas stat */
        .stat-card { border-radius: 16px; transition: transform .2s; }
        .stat-card:hover { transform: translateY(-4px); }
        .chart-wrapper { position: relative; height: 250px; }
        .section-title {
            font-size: 1rem; font-weight: 700;
            border-left: 4px solid #1a1a2e;
            padding-left: 10px; margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background:#1a1a2e;">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="#"><i class="bi bi-building"></i> VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <?php if ($rol_id == 1): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-people"></i> Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas e Informes</a></li>
                <?php elseif ($rol_id == 3): ?>
                    <li class="nav-item"><a class="nav-link" href="../supervisor/inicio.php"><i class="bi bi-house"></i> Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas e Informes</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="../residente/inicio.php"><i class="bi bi-house"></i> Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
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

    <!-- Título dinámico -->
    <h2 class="mb-3 fw-bold">
        <i class="bi bi-calendar3"></i>
        <?php
            if ($rol_id == 1)     echo 'Reservas e Informes';
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

    <!-- ══ TABS (solo para admin y supervisor) ══ -->
    <?php if ($rol_id == 1 || $rol_id == 3): ?>
    <ul class="nav nav-tabs-custom" id="reservasTabs">
        <li class="nav-item">
            <a class="nav-link <?php echo ($tab !== 'estadisticas' && $tab !== 'exportar') ? 'active' : ''; ?>"
               href="reservas.php?tab=gestion">
                <i class="bi bi-list-check"></i> Gestión
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($tab === 'estadisticas') ? 'active' : ''; ?>"
               href="reservas.php?tab=estadisticas">
                <i class="bi bi-bar-chart-line"></i> Estadísticas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($tab === 'exportar') ? 'active' : ''; ?>"
               href="reservas.php?tab=exportar">
                <i class="bi bi-download"></i> Exportar
            </a>
        </li>
    </ul>
    <?php endif; ?>


    <!-- ══════════════════════════════════════════════
         PESTAÑA 1: GESTIÓN
    ══════════════════════════════════════════════ -->
    <?php if ($tab !== 'estadisticas' && $tab !== 'exportar'): ?>
    <div class="row">

        <!-- Formulario Nueva Reserva (SOLO RESIDENTES) -->
        <?php if ($rol_id == 2): ?>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header fw-bold text-white" style="background:#1a1a2e;">
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
                                <div class="alert alert-warning py-2 small">No hay insumos disponibles.</div>
                            <?php else: ?>
                                <?php foreach ($insumos_disponibles as $ins): ?>
                                <div class="insumo-item d-flex align-items-center justify-content-between">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input insumo-check"
                                            type="checkbox" name="insumos[]"
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
        <div class="<?php echo ($rol_id == 2) ? 'col-md-8' : 'col-12'; ?>">
            <div class="card shadow">
                <div class="card-header fw-bold text-white" style="background:#1a1a2e;">
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

                            $stmt_ri = $conn->prepare(
                                "SELECT i.nombre, ri.cantidad_solicitada
                                 FROM reserva_insumos ri
                                 INNER JOIN insumos i ON ri.insumo_id = i.id
                                 WHERE ri.reserva_id = ?"
                            );
                            $stmt_ri->execute([$r['id']]);
                            $insumos_reserva = $stmt_ri->fetchAll();
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
                            <td><?php echo htmlspecialchars($r['descripcion']); ?>
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
                                    <?php if ($r['estado'] != 'rechazada'): ?>
                                    <a href="../../controllers/ReservaController.php?accion=cancelar&id=<?php echo $r['id']; ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Cancelar esta reserva? Se eliminará permanentemente.')">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                <?php else: ?>
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
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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

            <!-- Leyenda semáforo (solo admin/supervisor) -->
            <?php if ($rol_id == 1 || $rol_id == 3): ?>
            <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                <small class="fw-semibold text-muted">Semáforo reservas aprobadas:</small>
                <small class="semaforo-verde px-3 py-1 rounded">🟢 Más de 7 días</small>
                <small class="semaforo-amarillo px-3 py-1 rounded">🟡 Entre 3 y 7 días</small>
                <small class="semaforo-rojo px-3 py-1 rounded">🔴 Menos de 3 días</small>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /row gestión -->
    <?php endif; ?>


    <!-- ══════════════════════════════════════════════
         PESTAÑA 2: ESTADÍSTICAS
    ══════════════════════════════════════════════ -->
    <?php if ($tab === 'estadisticas' && ($rol_id == 1 || $rol_id == 3)): ?>

    <!-- Tarjetas resumen -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-primary text-white p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $total_reservas; ?></div>
                <small>Total Reservas</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-warning p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $pendientes_cnt; ?></div>
                <small>Pendientes</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-success text-white p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $aprobadas_cnt; ?></div>
                <small>Aprobadas</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-danger text-white p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $rechazadas_cnt; ?></div>
                <small>Rechazadas</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-info text-white p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $total_insumos; ?></div>
                <small>Insumos</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-secondary text-white p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $insumos_danados; ?></div>
                <small>Insumos Dañados</small>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow p-3">
                <h6 class="section-title"><i class="bi bi-bar-chart-line"></i> Reservas por Mes (últimos 6 meses)</h6>
                <div class="chart-wrapper"><canvas id="chartMes"></canvas></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow p-3">
                <h6 class="section-title"><i class="bi bi-pie-chart"></i> Estados de Reservas</h6>
                <div class="chart-wrapper"><canvas id="chartEstados"></canvas></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow p-3">
                <h6 class="section-title"><i class="bi bi-boxes"></i> Estado del Inventario</h6>
                <div class="chart-wrapper"><canvas id="chartInventario"></canvas></div>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <!-- ══════════════════════════════════════════════
         PESTAÑA 3: EXPORTAR
    ══════════════════════════════════════════════ -->
    <?php if ($tab === 'exportar' && ($rol_id == 1 || $rol_id == 3)): ?>
    <div class="row g-4 mb-5">

        <!-- Columna izquierda: formularios descarga -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header fw-bold text-white" style="background:#1a1a2e;">
                    <i class="bi bi-calendar-check"></i> Exportar Reservas (CSV)
                </div>
                <div class="card-body">
                    <p class="text-muted small">Filtra y descarga un archivo <strong>.CSV</strong> con las reservas.</p>
                    <form action="../../controllers/InformeController.php" method="GET" target="_blank">
                        <input type="hidden" name="tipo" value="reservas">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="todos">Todos los estados</option>
                                <option value="pendiente">Pendientes</option>
                                <option value="aprobada">Aprobadas</option>
                                <option value="rechazada">Rechazadas</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-download"></i> Descargar CSV de Reservas
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header fw-bold text-white bg-success">
                    <i class="bi bi-box-seam"></i> Exportar Inventario (CSV)
                </div>
                <div class="card-body">
                    <p class="text-muted small">Descarga un archivo <strong>.CSV</strong> con todos los insumos.</p>
                    <form action="../../controllers/InformeController.php" method="GET" target="_blank">
                        <input type="hidden" name="tipo" value="inventario">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado del insumo</label>
                            <select name="estado" class="form-select">
                                <option value="todos">Todos</option>
                                <option value="disponible">Disponibles</option>
                                <option value="dañado">Dañados</option>
                                <option value="en reparacion">En Reparación</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="bi bi-download"></i> Descargar CSV de Inventario
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna derecha: vista previa filtrada -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header fw-bold text-white" style="background:#1a1a2e;">
                    <i class="bi bi-funnel"></i> Vista Previa de Reservas
                </div>
                <div class="card-body">
                    <form method="GET" action="reservas.php" class="row g-2 mb-3 align-items-end">
                        <input type="hidden" name="tab" value="exportar">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Estado</label>
                            <select name="filtro_estado" class="form-select form-select-sm">
                                <option value="todos"     <?php echo ($filtro_estado==='todos')     ? 'selected':''; ?>>Todos</option>
                                <option value="pendiente" <?php echo ($filtro_estado==='pendiente') ? 'selected':''; ?>>Pendientes</option>
                                <option value="aprobada"  <?php echo ($filtro_estado==='aprobada')  ? 'selected':''; ?>>Aprobadas</option>
                                <option value="rechazada" <?php echo ($filtro_estado==='rechazada') ? 'selected':''; ?>>Rechazadas</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Desde</label>
                            <input type="date" name="filtro_fecha_desde" class="form-control form-control-sm"
                                   value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Hasta</label>
                            <input type="date" name="filtro_fecha_hasta" class="form-control form-control-sm"
                                   value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary flex-fill fw-bold">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            <a href="reservas.php?tab=exportar" class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="bi bi-x"></i> Limpiar
                            </a>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th><th>Residente</th><th>Fecha</th>
                                    <th>Descripción</th><th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($preview)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No hay reservas con los filtros aplicados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($preview as $p):
                                    $bc = match($p['estado']) {
                                        'aprobada'  => 'bg-success',
                                        'rechazada' => 'bg-danger',
                                        default     => 'bg-warning text-dark'
                                    };
                                ?>
                                <tr>
                                    <td class="text-muted small">#<?php echo $p['id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($p['nombre_completo']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_evento'])); ?></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($p['descripcion'],0,50,'...')); ?></td>
                                    <td><span class="badge <?php echo $bc; ?> px-3"><?php echo ucfirst($p['estado']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        Mostrando <?php echo count($preview); ?> resultado(s). Para exportar usa los botones CSV.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Habilitar input cantidad al marcar checkbox (formulario residente)
document.querySelectorAll('.insumo-check').forEach(function(chk) {
    chk.addEventListener('change', function() {
        var inp = document.getElementById('cant_' + this.dataset.id);
        inp.disabled = !this.checked;
        if (!this.checked) inp.value = 1;
    });
});

<?php if ($tab === 'estadisticas' && ($rol_id == 1 || $rol_id == 3)): ?>
// ── Gráfica 1: Barras — Reservas por mes ─────────────────────────
new Chart(document.getElementById('chartMes'), {
    type: 'bar',
    data: {
        labels: <?php echo $labels_mes; ?>,
        datasets: [{
            label: 'Reservas',
            data: <?php echo $values_mes; ?>,
            backgroundColor: '#1a1a2e',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#e9ecef' } },
            x: { grid: { display: false } }
        }
    }
});

// ── Gráfica 2: Dona — Estados ─────────────────────────────────────
new Chart(document.getElementById('chartEstados'), {
    type: 'doughnut',
    data: {
        labels: <?php echo $labels_estados; ?>,
        datasets: [{
            data: <?php echo $values_estados; ?>,
            backgroundColor: ['#ffc107','#198754','#dc3545'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});

// ── Gráfica 3: Barras horizontales — Inventario ───────────────────
new Chart(document.getElementById('chartInventario'), {
    type: 'bar',
    data: {
        labels: <?php echo $labels_inv; ?>,
        datasets: [{
            label: 'Insumos',
            data: <?php echo $values_inv; ?>,
            backgroundColor: ['#198754','#dc3545','#ffc107','#0dcaf0'],
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#e9ecef' } },
            y: { grid: { display: false } }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>
