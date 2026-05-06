<?php
// archivo: controllers/ReservaController.php
session_start();
require __DIR__ . '/../models/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php?error=Acceso denegado.");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol_id     = $_SESSION['rol_id'];
$accion     = $_POST['accion'] ?? $_GET['accion'] ?? null;

// CREAR RESERVA (solo residente)
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

    $stmt_check = $conn->prepare("SELECT id FROM reservas WHERE fecha_evento = ? AND estado != 'rechazada'");
    $stmt_check->execute([$fecha_evento]);
    if ($stmt_check->fetch()) {
        header("Location: ../views/residente/reservas.php?error=Ya existe una reserva para esa fecha."); exit;
    }

    $insumos_post    = $_POST['insumos']    ?? [];
    $cantidades_post = $_POST['cantidades'] ?? [];

    foreach ($insumos_post as $insumo_id) {
        $insumo_id = (int)$insumo_id;
        $cant_sol  = isset($cantidades_post[$insumo_id]) ? (int)$cantidades_post[$insumo_id] : 1;
        $stmt_stock = $conn->prepare("SELECT cantidad FROM insumos WHERE id = ? AND estado = 'disponible'");
        $stmt_stock->execute([$insumo_id]);
        $stock = $stmt_stock->fetch();
        if (!$stock || $cant_sol > $stock['cantidad']) {
            header("Location: ../views/residente/reservas.php?error=Stock insuficiente para uno de los insumos."); exit;
        }
    }

    $stmt = $conn->prepare(
        "INSERT INTO reservas (usuario_id, fecha_evento, hora_inicio, hora_fin, descripcion, estado)
         VALUES (?, ?, '12:00:00', '23:59:59', ?, 'pendiente')"
    );
    if ($stmt->execute([$usuario_id, $fecha_evento, $descripcion])) {
        $reserva_id = $conn->lastInsertId();
        if (!empty($insumos_post)) {
            $stmt_ri = $conn->prepare("INSERT INTO reserva_insumos (reserva_id, insumo_id, cantidad_solicitada) VALUES (?, ?, ?)");
            foreach ($insumos_post as $insumo_id) {
                $insumo_id = (int)$insumo_id;
                $cant_sol  = isset($cantidades_post[$insumo_id]) ? (int)$cantidades_post[$insumo_id] : 1;
                $stmt_ri->execute([$reserva_id, $insumo_id, $cant_sol]);
            }
        }
        header("Location: ../views/residente/reservas.php?mensaje=Solicitud enviada. Espera la aprobación del administrador.");
    } else {
        header("Location: ../views/residente/reservas.php?error=Error al registrar la solicitud.");
    }
    exit;
}

// CANCELAR RESERVA (solo residente dueño)
if ($accion == 'cancelar' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 2) { header("Location: ../views/residente/reservas.php?error=Solo el residente puede cancelar sus reservas."); exit; }

    $id       = (int)$_GET['id'];
    $stmt_ver = $conn->prepare("SELECT id, estado FROM reservas WHERE id = ? AND usuario_id = ?");
    $stmt_ver->execute([$id, $usuario_id]);
    $reserva = $stmt_ver->fetch();

    if (!$reserva) { header("Location: ../views/residente/reservas.php?error=No tienes permiso para cancelar esta reserva."); exit; }
    if ($reserva['estado'] == 'rechazada') { header("Location: ../views/residente/reservas.php?error=Esta reserva ya fue rechazada."); exit; }

    $conn->prepare("DELETE FROM reserva_insumos WHERE reserva_id = ?")->execute([$id]);
    $stmt_del = $conn->prepare("DELETE FROM reservas WHERE id = ? AND usuario_id = ?");
    if ($stmt_del->execute([$id, $usuario_id])) {
        header("Location: ../views/residente/reservas.php?mensaje=Reserva cancelada correctamente.");
    } else {
        header("Location: ../views/residente/reservas.php?error=No se pudo cancelar la reserva.");
    }
    exit;
}

// APROBAR RESERVA (solo admin)
if ($accion == 'aprobar' && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($rol_id != 1) { header("Location: ../views/admin/reservas.php?error=Acción no permitida."); exit; }

    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE reservas SET estado = 'aprobada' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: ../views/admin/reservas.php?mensaje=Reserva aprobada exitosamente.");
    exit;
}

// RECHAZAR RESERVA (solo admin)
if ($accion == 'rechazar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($rol_id != 1) { header("Location: ../views/admin/reservas.php?error=Acción no permitida."); exit; }

    $id             = (int)$_POST['id'];
    $motivo_rechazo = $_POST['motivo_rechazo'];

    $stmt = $conn->prepare("UPDATE reservas SET estado = 'rechazada', motivo_rechazo = ? WHERE id = ?");
    if ($stmt->execute([$motivo_rechazo, $id])) {
        header("Location: ../views/admin/reservas.php?mensaje=Reserva rechazada. El residente verá el motivo.");
    } else {
        header("Location: ../views/admin/reservas.php?error=Error al rechazar la reserva.");
    }
    exit;
}

header("Location: ../views/admin/reservas.php");
exit;
?>
