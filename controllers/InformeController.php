<?php
// archivo: controllers/InformeController.php
// Genera el informe de reservas en formato CSV descargable
session_start();
require __DIR__ . '/../models/Database.php';

// Solo admin (rol 1) y supervisor (rol 3) pueden descargar informes
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_id'], [1, 3])) {
    header("Location: ../views/auth/login.php?error=Acceso denegado.");
    exit;
}

$tipo       = $_GET['tipo']        ?? 'reservas';  // reservas | inventario
$estado     = $_GET['estado']      ?? 'todos';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// ── INFORME DE RESERVAS ──────────────────────────────────────────
if ($tipo === 'reservas') {

    $sql    = "SELECT r.id,
                      u.nombre_completo   AS residente,
                      r.fecha_evento,
                      r.hora_inicio,
                      r.hora_fin,
                      r.descripcion,
                      r.estado,
                      COALESCE(r.motivo_rechazo, '') AS motivo_rechazo,
                      r.fecha_solicitud
               FROM reservas r
               INNER JOIN usuarios u ON r.usuario_id = u.id
               WHERE 1=1";
    $params = [];

    if ($estado !== 'todos') {
        $sql     .= " AND r.estado = ?";
        $params[] = $estado;
    }
    if ($fecha_desde !== '') {
        $sql     .= " AND r.fecha_evento >= ?";
        $params[] = $fecha_desde;
    }
    if ($fecha_hasta !== '') {
        $sql     .= " AND r.fecha_evento <= ?";
        $params[] = $fecha_hasta;
    }

    $sql .= " ORDER BY r.fecha_evento ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    // Cabecera CSV
    $nombre_archivo = 'informe_reservas_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    header('Pragma: no-cache');

    $salida = fopen('php://output', 'w');
    // BOM para que Excel abra bien tildes
    fputs($salida, "\xEF\xBB\xBF");

    // Encabezados
    fputcsv($salida, [
        'ID', 'Residente', 'Fecha Evento', 'Hora Inicio',
        'Hora Fin', 'Descripción', 'Estado', 'Motivo Rechazo', 'Fecha Solicitud'
    ], ';');

    foreach ($filas as $f) {
        fputcsv($salida, [
            $f['id'],
            $f['residente'],
            date('d/m/Y', strtotime($f['fecha_evento'])),
            $f['hora_inicio'],
            $f['hora_fin'],
            $f['descripcion'],
            ucfirst($f['estado']),
            $f['motivo_rechazo'],
            date('d/m/Y H:i', strtotime($f['fecha_solicitud']))
        ], ';');
    }

    fclose($salida);
    exit;
}

// ── INFORME DE INVENTARIO ────────────────────────────────────────
if ($tipo === 'inventario') {

    $sql    = "SELECT i.id,
                      i.nombre,
                      i.descripcion,
                      i.cantidad,
                      i.estado,
                      i.fecha_registro
               FROM insumos i
               WHERE 1=1";
    $params = [];

    if ($estado !== 'todos') {
        $sql     .= " AND i.estado = ?";
        $params[] = $estado;
    }

    $sql .= " ORDER BY i.nombre ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    $nombre_archivo = 'informe_inventario_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    header('Pragma: no-cache');

    $salida = fopen('php://output', 'w');
    fputs($salida, "\xEF\xBB\xBF");

    fputcsv($salida, ['ID', 'Nombre', 'Descripción', 'Cantidad', 'Estado', 'Fecha Registro'], ';');

    foreach ($filas as $f) {
        fputcsv($salida, [
            $f['id'],
            $f['nombre'],
            $f['descripcion'] ?? '',
            $f['cantidad'],
            ucfirst($f['estado']),
            date('d/m/Y', strtotime($f['fecha_registro']))
        ], ';');
    }

    fclose($salida);
    exit;
}

// Si tipo no coincide, redirige
header("Location: ../views/admin/informes.php?error=Tipo de informe no válido.");
exit;
?>
