<?php
// archivo: controllers/ReservaController.php
session_start();
require __DIR__ . '/../models/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php?error=Acceso denegado."); exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol_id     = $_SESSION['rol_id'];
$accion     = $_POST['accion'] ?? $_GET['accion'] ?? null;

// ── CREAR RESERVA (solo residente) ──────────────────────────────────────────
if ($accion == 'crear' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 2) { header("Location: ../views/residente/reservas.php?error=Solo los residentes pueden solicitar reservas."); exit; }

    $fecha_evento = $_POST['fecha_evento'];
    $descripcion  = $_POST['descripcion'];

    $ahora      = new DateTime();
    $evento     = new DateTime($fecha_evento);
    $diferencia = $ahora->diff($evento);
    $horas_diff = ($diferencia->days * 24) + $diferencia->h;

    if ($evento <= $ahora || $horas_diff < 48) {
        header("Location: ../views/residente/reservas.php?error=La reserva debe hacerse con mínimo 48 horas de anticipación."); exit;
    }
    if ($evento > new DateTime('+90 days')) {
        header("Location: ../views/residente/reservas.php?error=No puedes reservar con más de 90 días de anticipación."); exit;
    }

    // Verificar que no haya reserva activa en esa fecha
    $existentes = sb_get('reservas', ['fecha_evento' => 'eq.' . $fecha_evento, 'estado' => 'neq.rechazada']);
    if (!empty($existentes)) {
        header("Location: ../views/residente/reservas.php?error=Ya existe una reserva para esa fecha."); exit;
    }

    $insumos_post    = $_POST['insumos']    ?? [];
    $cantidades_post = $_POST['cantidades'] ?? [];

    // Verificar stock
    foreach ($insumos_post as $insumo_id) {
        $insumo_id = (int)$insumo_id;
        $cant_sol  = isset($cantidades_post[$insumo_id]) ? (int)$cantidades_post[$insumo_id] : 1;
        $insumos_disp = sb_get('insumos', ['id' => 'eq.' . $insumo_id, 'estado' => 'eq.disponible']);
        $stock = !empty($insumos_disp) ? $insumos_disp[0] : null;
        if (!$stock || $cant_sol > $stock['cantidad']) {
            header("Location: ../views/residente/reservas.php?error=Stock insuficiente para uno de los insumos."); exit;
        }
    }

    $nueva_reserva = sb_insert('reservas', [
        'usuario_id'   => $usuario_id,
        'fecha_evento' => $fecha_evento,
        'hora_inicio'  => '12:00:00',
        'hora_fin'     => '23:59:59',
        'descripcion'  => $descripcion,
        'estado'       => 'pendiente',
    ]);

    if ($nueva_reserva) {
        $reserva_id = $nueva_reserva['id'];
        foreach ($insumos_post as $insumo_id) {
            $insumo_id = (int)$insumo_id;
            $cant_sol  = isset($cantidades_post[$insumo_id]) ? (int)$cantidades_post[$insumo_id] : 1;
            sb_insert('reserva_insumos', [
                'reserva_id'          => $reserva_id,
                'insumo_id'           => $insumo_id,
                'cantidad_solicitada' => $cant_sol,
            ]);
        }
        header("Location: ../views/residente/reservas.php?mensaje=Solicitud enviada. Espera la aprobación del administrador.");
    } else {
        header("Location: ../views/residente/reservas.php?error=Error al registrar la solicitud.");
    }
    exit;
}

// ── CANCELAR RESERVA (solo residente dueño) ─────────────────────────────────
if ($accion == 'cancelar' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 2) { header("Location: ../views/residente/reservas.php?error=Solo el residente puede cancelar sus reservas."); exit; }

    $id       = (int)$_GET['id'];
    $reservas = sb_get('reservas', ['id' => 'eq.' . $id, 'usuario_id' => 'eq.' . $usuario_id]);
    $reserva  = !empty($reservas) ? $reservas[0] : null;

    if (!$reserva) { header("Location: ../views/residente/reservas.php?error=No tienes permiso para cancelar esta reserva."); exit; }
    if ($reserva['estado'] == 'rechazada') { header("Location: ../views/residente/reservas.php?error=Esta reserva ya fue rechazada."); exit; }

    sb_delete('reserva_insumos', ['reserva_id' => 'eq.' . $id]);
    $ok = sb_delete('reservas', ['id' => 'eq.' . $id, 'usuario_id' => 'eq.' . $usuario_id]);
    if ($ok) {
        header("Location: ../views/residente/reservas.php?mensaje=Reserva cancelada correctamente.");
    } else {
        header("Location: ../views/residente/reservas.php?error=No se pudo cancelar la reserva.");
    }
    exit;
}

// ── APROBAR RESERVA (solo admin) ────────────────────────────────────────────
if ($accion == 'aprobar' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 1) { header("Location: ../views/admin/reservas.php?error=Acción no permitida."); exit; }
    $id = (int)$_GET['id'];
    sb_update('reservas', ['id' => 'eq.' . $id], ['estado' => 'aprobada']);
    header("Location: ../views/admin/reservas.php?mensaje=Reserva aprobada exitosamente.");
    exit;
}

// ── RECHAZAR RESERVA (solo admin) ───────────────────────────────────────────
if ($accion == 'rechazar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/reservas.php?error=Acción no permitida."); exit; }
    $id             = (int)$_POST['id'];
    $motivo_rechazo = $_POST['motivo_rechazo'];
    $ok = sb_update('reservas', ['id' => 'eq.' . $id], ['estado' => 'rechazada', 'motivo_rechazo' => $motivo_rechazo]);
    if ($ok) {
        header("Location: ../views/admin/reservas.php?mensaje=Reserva rechazada. El residente verá el motivo.");
    } else {
        header("Location: ../views/admin/reservas.php?error=Error al rechazar la reserva.");
    }
    exit;
}

header("Location: ../views/admin/reservas.php");
exit;
?>
