<?php
// archivo: views/admin/informes.php
session_start();
require __DIR__ . '/../../models/Database.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_id'], [1, 3])) {
    header("Location: ../auth/login.php?error=Acceso denegado.");
    exit;
}

$rol_id = $_SESSION['rol_id'];

// ── Contadores tarjetas de resumen ─────────────────────────────────
$total_reservas  = $conn->query("SELECT COUNT(*) FROM reservas")->fetchColumn();
$pendientes      = $conn->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
$aprobadas       = $conn->query("SELECT COUNT(*) FROM reservas WHERE estado = 'aprobada'")->fetchColumn();
$rechazadas      = $conn->query("SELECT COUNT(*) FROM reservas WHERE estado = 'rechazada'")->fetchColumn();
$total_insumos   = $conn->query("SELECT COUNT(*) FROM insumos")->fetchColumn();
$insumos_danados = $conn->query("SELECT COUNT(*) FROM insumos WHERE estado = 'dañado'")->fetchColumn();

// ── Datos para gráfica 1: reservas por mes (últimos 6 meses) ──────
$stmt_mes = $conn->query(
    "SELECT TO_CHAR(fecha_evento, 'Mon YYYY') AS mes,
            COUNT(*) AS total
     FROM reservas
     WHERE fecha_evento >= CURRENT_DATE - INTERVAL '6 months'
     GROUP BY TO_CHAR(fecha_evento, 'Mon YYYY'), DATE_TRUNC('month', fecha_evento)
     ORDER BY DATE_TRUNC('month', fecha_evento) ASC"
);
$datos_mes = $stmt_mes->fetchAll();
$labels_mes  = json_encode(array_column($datos_mes, 'mes'));
$values_mes  = json_encode(array_map('intval', array_column($datos_mes, 'total')));

// ── Datos para gráfica 2: torta de estados ────────────────────────
$labels_estados = json_encode(['Pendientes', 'Aprobadas', 'Rechazadas']);
$values_estados = json_encode([(int)$pendientes, (int)$aprobadas, (int)$rechazadas]);

// ── Datos para gráfica 3: estado del inventario ───────────────────
$stmt_inv = $conn->query(
    "SELECT estado, COUNT(*) AS total FROM insumos GROUP BY estado"
);
$datos_inv = $stmt_inv->fetchAll();
$labels_inv = json_encode(array_column($datos_inv, 'estado'));
$values_inv = json_encode(array_map('intval', array_column($datos_inv, 'total')));

// ── Filtros para vista previa ─────────────────────────────────────
$filtro_estado      = $_GET['filtro_estado']      ?? 'todos';
$filtro_fecha_desde = $_GET['filtro_fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['filtro_fecha_hasta'] ?? '';

$where  = "WHERE 1=1";
$params = [];
if ($filtro_estado !== 'todos') {
    $where  .= " AND r.estado = :estado";
    $params[':estado'] = $filtro_estado;
}
if ($filtro_fecha_desde) {
    $where  .= " AND r.fecha_evento >= :desde";
    $params[':desde'] = $filtro_fecha_desde;
}
if ($filtro_fecha_hasta) {
    $where  .= " AND r.fecha_evento <= :hasta";
    $params[':hasta'] = $filtro_fecha_hasta;
}

$stmt_preview = $conn->prepare(
    "SELECT r.id, u.nombre_completo, r.fecha_evento, r.descripcion, r.estado
     FROM reservas r
     INNER JOIN usuarios u ON r.usuario_id = u.id
     $where
     ORDER BY r.fecha_evento DESC
     LIMIT 10"
);
$stmt_preview->execute($params);
$preview = $stmt_preview->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body          { background: #f2f4f7; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        .stat-card    { border-radius: 16px; transition: transform .2s; cursor: default; }
        .stat-card:hover { transform: translateY(-4px); }
        .card         { border-radius: 14px; border: none; }
        .section-title {
            font-size: 1.05rem; font-weight: 700;
            border-left: 4px solid #0d6efd;
            padding-left: 10px; margin-bottom: 1rem;
        }
        .chart-wrapper { position: relative; height: 260px; }
        .badge-pendiente  { background: #ffc107; color: #000; }
        .badge-aprobada   { background: #198754; color: #fff; }
        .badge-rechazada  { background: #dc3545; color: #fff; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background:#1a1a2e;">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="#"><i class="bi bi-building"></i> VivimosTodos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <?php if ($rol_id == 1): ?>
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-people"></i> Usuarios</a></li>
                <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                <li class="nav-item"><a class="nav-link" href="reservas.php"><i class="bi bi-calendar-check"></i> Reservas</a></li>
                <li class="nav-item"><a class="nav-link active" href="informes.php"><i class="bi bi-file-earmark-bar-graph"></i> Informes</a></li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="../supervisor/inicio.php"><i class="bi bi-house"></i> Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="../admin/inventario.php"><i class="bi bi-box-seam"></i> Inventario</a></li>
                <li class="nav-item"><a class="nav-link" href="../admin/reservas.php"><i class="bi bi-calendar-check"></i> Reservas</a></li>
                <li class="nav-item"><a class="nav-link active" href="informes.php"><i class="bi bi-file-earmark-bar-graph"></i> Informes</a></li>
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

    <h2 class="mb-4 fw-bold"><i class="bi bi-file-earmark-bar-graph"></i> Sección de Informes</h2>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ══ TARJETAS DE RESUMEN ══ -->
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-primary text-white p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $total_reservas; ?></div>
                <small>Total Reservas</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-warning p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $pendientes; ?></div>
                <small>Pendientes</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-success text-white p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $aprobadas; ?></div>
                <small>Aprobadas</small>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card shadow bg-danger text-white p-3 text-center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $rechazadas; ?></div>
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

    <!-- ══ GRÁFICAS (PUNTO 11) ══ -->
    <div class="row g-4 mb-4">

        <!-- Gráfica 1: Reservas por mes — Barra -->
        <div class="col-md-6">
            <div class="card shadow p-3">
                <h6 class="section-title"><i class="bi bi-bar-chart-line"></i> Reservas por Mes (últimos 6 meses)</h6>
                <div class="chart-wrapper">
                    <canvas id="chartMes"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfica 2: Torta de estados -->
        <div class="col-md-3">
            <div class="card shadow p-3">
                <h6 class="section-title"><i class="bi bi-pie-chart"></i> Estados de Reservas</h6>
                <div class="chart-wrapper">
                    <canvas id="chartEstados"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfica 3: Estado del inventario -->
        <div class="col-md-3">
            <div class="card shadow p-3">
                <h6 class="section-title"><i class="bi bi-boxes"></i> Estado del Inventario</h6>
                <div class="chart-wrapper">
                    <canvas id="chartInventario"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ FILA INFERIOR: DESCARGAS + VISTA PREVIA CON FILTROS ══ -->
    <div class="row g-4 mb-5">

        <!-- Panel izquierdo: Formularios de descarga -->
        <div class="col-md-4">

            <!-- Informe de Reservas -->
            <div class="card shadow mb-4">
                <div class="card-header fw-bold text-white" style="background:#1a1a2e;">
                    <i class="bi bi-calendar-check"></i> Descargar Informe de Reservas
                </div>
                <div class="card-body">
                    <p class="text-muted small">Archivo <strong>.CSV</strong> con todas las reservas. Filtra por estado y fechas.</p>
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

            <!-- Informe de Inventario -->
            <div class="card shadow">
                <div class="card-header fw-bold text-white bg-success">
                    <i class="bi bi-box-seam"></i> Descargar Informe de Inventario
                </div>
                <div class="card-body">
                    <p class="text-muted small">Archivo <strong>.CSV</strong> con todos los insumos del salón.</p>
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

        <!-- Panel derecho: Vista previa con filtros (PUNTO 11) -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header fw-bold" style="background:#1a1a2e; color:#fff;">
                    <i class="bi bi-funnel"></i> Vista Previa de Reservas — Filtros
                </div>
                <div class="card-body">

                    <!-- Formulario de filtros GET -->
                    <form method="GET" action="informes.php" class="row g-2 mb-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Estado</label>
                            <select name="filtro_estado" class="form-select form-select-sm">
                                <option value="todos"     <?php echo $filtro_estado === 'todos'     ? 'selected' : ''; ?>>Todos</option>
                                <option value="pendiente" <?php echo $filtro_estado === 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                                <option value="aprobada"  <?php echo $filtro_estado === 'aprobada'  ? 'selected' : ''; ?>>Aprobadas</option>
                                <option value="rechazada" <?php echo $filtro_estado === 'rechazada' ? 'selected' : ''; ?>>Rechazadas</option>
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
                            <a href="informes.php" class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="bi bi-x"></i> Limpiar
                            </a>
                        </div>
                    </form>

                    <!-- Tabla de resultados filtrados -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Residente</th>
                                    <th>Fecha Evento</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
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
                                <?php foreach ($preview as $r): ?>
                                <?php
                                    $badgeClass = match($r['estado']) {
                                        'aprobada'  => 'bg-success',
                                        'rechazada' => 'bg-danger',
                                        default     => 'bg-warning text-dark'
                                    };
                                ?>
                                <tr>
                                    <td class="text-muted small">#<?php echo $r['id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($r['nombre_completo']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($r['fecha_evento'])); ?></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($r['descripcion'], 0, 50, '...')); ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?> px-3"><?php echo ucfirst($r['estado']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        Mostrando <?php echo count($preview); ?> resultado(s) más recientes.
                        Para exportar usa los botones de descarga CSV.
                    </p>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Paleta de colores consistente ────────────────────────────────
const COLORS = {
    primary:   '#0d6efd',
    success:   '#198754',
    warning:   '#ffc107',
    danger:    '#dc3545',
    info:      '#0dcaf0',
    secondary: '#6c757d',
    purple:    '#6f42c1',
};

// ── Gráfica 1: Barras — Reservas por mes ─────────────────────────
new Chart(document.getElementById('chartMes'), {
    type: 'bar',
    data: {
        labels: <?php echo $labels_mes; ?>,
        datasets: [{
            label: 'Reservas',
            data: <?php echo $values_mes; ?>,
            backgroundColor: COLORS.primary,
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

// ── Gráfica 2: Torta — Estados de reservas ───────────────────────
new Chart(document.getElementById('chartEstados'), {
    type: 'doughnut',
    data: {
        labels: <?php echo $labels_estados; ?>,
        datasets: [{
            data: <?php echo $values_estados; ?>,
            backgroundColor: [COLORS.warning, COLORS.success, COLORS.danger],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        }
    }
});

// ── Gráfica 3: Barras horizontales — Inventario por estado ───────
new Chart(document.getElementById('chartInventario'), {
    type: 'bar',
    data: {
        labels: <?php echo $labels_inv; ?>,
        datasets: [{
            label: 'Insumos',
            data: <?php echo $values_inv; ?>,
            backgroundColor: [COLORS.success, COLORS.danger, COLORS.warning, COLORS.info],
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
</script>
</body>
</html>
