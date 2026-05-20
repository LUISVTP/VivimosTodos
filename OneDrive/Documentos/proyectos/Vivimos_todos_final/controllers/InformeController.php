<?php
// archivo: controllers/InformeController.php
session_start();
require __DIR__ . '/../models/Database.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_id'], [1, 3])) {
    header("Location: ../views/auth/login.php?error=Acceso denegado."); exit;
}

$tipo        = $_GET['tipo']        ?? 'reservas';
$estado      = $_GET['estado']      ?? 'todos';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// ── INFORME DE RESERVAS ──────────────────────────────────────────────────────
if ($tipo === 'reservas') {
    // Traer todas las reservas con usuario (join manual via API)
    $filtros = [];
    if ($estado !== 'todos')  $filtros['estado']       = 'eq.' . $estado;
    if ($fecha_desde !== '')  $filtros['fecha_evento']  = 'gte.' . $fecha_desde;
    if ($fecha_hasta !== '')  $filtros['fecha_evento']  = 'lte.' . $fecha_hasta;

    $reservas = sb_get('reservas', $filtros);

    // Traer usuarios para el join manual
    $usuarios_arr = sb_get('usuarios', [], ['id', 'nombre_completo']);
    $usuarios_map = [];
    foreach ($usuarios_arr as $u) $usuarios_map[$u['id']] = $u['nombre_completo'];

    // Filtro de rango combinado (la API solo acepta un filtro por columna, manejamos el rango en PHP)
    if ($fecha_desde !== '' && $fecha_hasta !== '') {
        $reservas = array_filter($reservas, function($r) use ($fecha_desde, $fecha_hasta) {
            return $r['fecha_evento'] >= $fecha_desde && $r['fecha_evento'] <= $fecha_hasta;
        });
    }

    // Ordenar por fecha
    usort($reservas, fn($a, $b) => strcmp($a['fecha_evento'], $b['fecha_evento']));

    $nombre_archivo = 'informe_reservas_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    header('Pragma: no-cache');

    $salida = fopen('php://output', 'w');
    fputs($salida, "\xEF\xBB\xBF");
    fputcsv($salida, ['ID','Residente','Fecha Evento','Hora Inicio','Hora Fin','Descripción','Estado','Motivo Rechazo','Fecha Solicitud'], ';');

    foreach ($reservas as $r) {
        fputcsv($salida, [
            $r['id'],
            $usuarios_map[$r['usuario_id']] ?? 'Desconocido',
            date('d/m/Y', strtotime($r['fecha_evento'])),
            $r['hora_inicio'],
            $r['hora_fin'],
            $r['descripcion'] ?? '',
            ucfirst($r['estado']),
            $r['motivo_rechazo'] ?? '',
            date('d/m/Y H:i', strtotime($r['fecha_solicitud'])),
        ], ';');
    }
    fclose($salida);
    exit;
}

// ── INFORME DE INVENTARIO ────────────────────────────────────────────────────
if ($tipo === 'inventario') {
    $filtros = [];
    if ($estado !== 'todos') $filtros['estado'] = 'eq.' . $estado;

    $insumos = sb_get('insumos', $filtros);
    usort($insumos, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

    $nombre_archivo = 'informe_inventario_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    header('Pragma: no-cache');

    $salida = fopen('php://output', 'w');
    fputs($salida, "\xEF\xBB\xBF");
    fputcsv($salida, ['ID','Nombre','Descripción','Cantidad','Estado','Fecha Registro'], ';');

    foreach ($insumos as $i) {
        fputcsv($salida, [
            $i['id'],
            $i['nombre'],
            $i['descripcion'] ?? '',
            $i['cantidad'],
            ucfirst($i['estado']),
            date('d/m/Y', strtotime($i['fecha_registro'])),
        ], ';');
    }
    fclose($salida);
    exit;
}

header("Location: ../views/admin/informes.php?error=Tipo de informe no válido.");
exit;
?>
