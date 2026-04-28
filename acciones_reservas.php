<?php
// archivo: acciones_reservas.php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php?error=Acceso denegado.");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol_id     = $_SESSION['rol_id'];

// ────────────────────────────────────────────
// ACCIÓN: CREAR RESERVA (solo residentes)
// ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['accion'] == 'crear') {

    if ($rol_id != 2) {
        header("Location: reservas.php?error=Solo los residentes pueden solicitar reservas.");
        exit;
    }

    $fecha_evento = $_POST['fecha_evento'];
    $descripcion  = $_POST['descripcion'];

    // Validación 1: mínimo 48 horas
    $ahora      = new DateTime();
    $evento     = new DateTime($fecha_evento);
    $diferencia = $ahora->diff($evento);
    $horas_diff = ($diferencia->days * 24) + $diferencia->h;

    if ($evento <= $ahora || $horas_diff < 48) {
        header("Location: reservas.php?error=La reserva debe hacerse con mínimo 48 horas de anticipación.");
        exit;
    }

    // Validación 2: máximo 90 días
    $max_fecha = new DateTime('+90 days');
    if ($evento > $max_fecha) {
        header("Location: reservas.php?error=No puedes reservar con más de 90 días de anticipación.");
        exit;
    }

    // Validación 3: solo una reserva por día
    $sql_check  = "SELECT id FROM reservas WHERE fecha_evento = ? AND estado != 'rechazada'";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$fecha_evento]);
    if ($stmt_check->fetch()) {
        header("Location: reservas.php?error=Ya existe una reserva para esa fecha. El salón solo puede reservarse una vez por día.");
        exit;
    }

    // Validación 4: cantidades de insumos no superen el stock
    $insumos_post    = $_POST['insumos']    ?? [];
    $cantidades_post = $_POST['cantidades'] ?? [];

    foreach ($insumos_post as $insumo_id) {
        $insumo_id = (int)$insumo_id;
        $cant_sol  = isset($cantidades_post[$insumo_id]) ? (int)$cantidades_post[$insumo_id] : 1;

        $stmt_stock = $conn->prepare("SELECT cantidad FROM insumos WHERE id = ? AND estado = 'disponible'");
        $stmt_stock->execute([$insumo_id]);
        $stock = $stmt_stock->fetch();

        if (!$stock || $cant_sol > $stock['cantidad']) {
            header("Location: reservas.php?error=La cantidad solicitada de uno de los insumos supera el stock disponible.");
            exit;
        }
        if ($cant_sol < 1) {
            header("Location: reservas.php?error=La cantidad de cada insumo debe ser al menos 1.");
            exit;
        }
    }

    // Insertar la reserva
    $sql  = "INSERT INTO reservas (usuario_id, fecha_evento, hora_inicio, hora_fin, descripcion, estado)
             VALUES (?, ?, '12:00:00', '23:59:59', ?, 'pendiente')";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$usuario_id, $fecha_evento, $descripcion])) {
        $reserva_id = $conn->lastInsertId();

        // Insertar insumos seleccionados
        if (!empty($insumos_post)) {
            $sql_ri  = "INSERT INTO reserva_insumos (reserva_id, insumo_id, cantidad_solicitada) VALUES (?, ?, ?)";
            $stmt_ri = $conn->prepare($sql_ri);
            foreach ($insumos_post as $insumo_id) {
                $insumo_id = (int)$insumo_id;
                $cant_sol  = isset($cantidades_post[$insumo_id]) ? (int)$cantidades_post[$insumo_id] : 1;
                $stmt_ri->execute([$reserva_id, $insumo_id, $cant_sol]);
            }
        }

        header("Location: reservas.php?mensaje=Solicitud enviada correctamente. Espera la aprobación del administrador.");
    } else {
        header("Location: reservas.php?error=Error al registrar la solicitud. Intenta de nuevo.");
    }
    exit;
}

// ────────────────────────────────────────────
// ACCIÓN: CANCELAR RESERVA (solo el residente dueño)
// ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'cancelar') {

    if ($rol_id != 2) {
        header("Location: reservas.php?error=Solo el residente puede cancelar sus reservas.");
        exit;
    }

    $id = (int)$_GET['id'];

    // Verificar que la reserva pertenezca a este residente
    $stmt_ver = $conn->prepare("SELECT id, estado FROM reservas WHERE id = ? AND usuario_id = ?");
    $stmt_ver->execute([$id, $usuario_id]);
    $reserva = $stmt_ver->fetch();

    if (!$reserva) {
        header("Location: reservas.php?error=No tienes permiso para cancelar esta reserva.");
        exit;
    }

    // Solo se puede cancelar si está pendiente o aprobada (no rechazada)
    if ($reserva['estado'] == 'rechazada') {
        header("Location: reservas.php?error=Esta reserva ya fue rechazada, no es necesario cancelarla.");
        exit;
    }

    // Eliminar primero los insumos relacionados (por si no hay ON DELETE CASCADE)
    $stmt_ri = $conn->prepare("DELETE FROM reserva_insumos WHERE reserva_id = ?");
    $stmt_ri->execute([$id]);

    // Eliminar la reserva
    $stmt_del = $conn->prepare("DELETE FROM reservas WHERE id = ? AND usuario_id = ?");
    if ($stmt_del->execute([$id, $usuario_id])) {
        header("Location: reservas.php?mensaje=Tu reserva fue cancelada y eliminada correctamente.");
    } else {
        header("Location: reservas.php?error=No se pudo cancelar la reserva. Intenta de nuevo.");
    }
    exit;
}

// ────────────────────────────────────────────
// ACCIÓN: APROBAR RESERVA (solo admin)
// ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] == 'aprobar') {

    if ($rol_id != 1) {
        header("Location: reservas.php?error=Acción no permitida.");
        exit;
    }

    $id   = (int)$_GET['id'];
    $sql  = "UPDATE reservas SET estado = 'aprobada' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    header("Location: reservas.php?mensaje=Reserva aprobada exitosamente.");
    exit;
}

// ────────────────────────────────────────────
// ACCIÓN: RECHAZAR RESERVA (solo admin)
// ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['accion'] == 'rechazar') {

    if ($rol_id != 1) {
        header("Location: reservas.php?error=Acción no permitida.");
        exit;
    }

    $id             = (int)$_POST['id'];
    $motivo_rechazo = $_POST['motivo_rechazo'];

    $sql  = "UPDATE reservas SET estado = 'rechazada', motivo_rechazo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$motivo_rechazo, $id])) {
        header("Location: reservas.php?mensaje=Reserva rechazada. El residente verá el motivo.");
    } else {
        header("Location: reservas.php?error=Error al rechazar la reserva.");
    }
    exit;
}
?>
