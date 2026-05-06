<?php

declare(strict_types=1);

class ReservaController extends BaseController
{
    private ReservationModel $reservationModel;
    private InventoryModel $inventoryModel;

    public function __construct()
    {
        $this->reservationModel = new ReservationModel();
        $this->inventoryModel = new InventoryModel();
    }

    public function handle(): void
    {
        $this->requireAuth([1, 2, 3]);

        $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

        switch ($accion) {
            case 'crear':
                $this->crear();
                break;
            case 'cancelar':
                $this->cancelar();
                break;
            case 'aprobar':
                $this->aprobar();
                break;
            case 'rechazar':
                $this->rechazar();
                break;
            default:
                $this->redirect('reservas.php', ['error' => 'Acción no válida.']);
        }
    }

    private function crear(): void
    {
        if ($this->roleId() !== 2) {
            $this->redirect('reservas.php', ['error' => 'Solo los residentes pueden solicitar reservas.']);
        }

        $fechaEvento = trim($_POST['fecha_evento'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $insumos = $_POST['insumos'] ?? [];
        $cantidades = $_POST['cantidades'] ?? [];

        $validacion = $this->reservationModel->validateReservationDate($fechaEvento);
        if ($validacion !== null) {
            $this->redirect('reservas.php', ['error' => $validacion]);
        }

        if ($this->reservationModel->existsReservationForDate($fechaEvento)) {
            $this->redirect('reservas.php', ['error' => 'Ya existe una reserva para esa fecha. El salón solo puede reservarse una vez por día.']);
        }

        $errorStock = $this->inventoryModel->validateRequestedItems($insumos, $cantidades);
        if ($errorStock !== null) {
            $this->redirect('reservas.php', ['error' => $errorStock]);
        }

        $creada = $this->reservationModel->createWithItems($this->userId(), $fechaEvento, $descripcion, $insumos, $cantidades);

        $this->redirect('reservas.php', [
            'mensaje' => $creada
                ? 'Solicitud enviada correctamente. Espera la aprobación del administrador.'
                : 'Error al registrar la solicitud. Intenta de nuevo.',
        ]);
    }

    private function cancelar(): void
    {
        if ($this->roleId() !== 2) {
            $this->redirect('reservas.php', ['error' => 'Solo el residente puede cancelar sus reservas.']);
        }

        $id = (int) ($_GET['id'] ?? 0);
        $reserva = $this->reservationModel->findOwnedByUser($id, $this->userId());

        if (!$reserva) {
            $this->redirect('reservas.php', ['error' => 'No tienes permiso para cancelar esta reserva.']);
        }

        if (($reserva['estado'] ?? '') === 'rechazada') {
            $this->redirect('reservas.php', ['error' => 'Esta reserva ya fue rechazada, no es necesario cancelarla.']);
        }

        $eliminada = $this->reservationModel->deleteByUser($id, $this->userId());

        $this->redirect('reservas.php', [
            'mensaje' => $eliminada
                ? 'Tu reserva fue cancelada y eliminada correctamente.'
                : 'No se pudo cancelar la reserva. Intenta de nuevo.',
        ]);
    }

    private function aprobar(): void
    {
        if ($this->roleId() !== 1) {
            $this->redirect('reservas.php', ['error' => 'Acción no permitida.']);
        }

        $this->reservationModel->updateStatus((int) ($_GET['id'] ?? 0), 'aprobada');
        $this->redirect('reservas.php', ['mensaje' => 'Reserva aprobada exitosamente.']);
    }

    private function rechazar(): void
    {
        if ($this->roleId() !== 1) {
            $this->redirect('reservas.php', ['error' => 'Acción no permitida.']);
        }

        $rechazada = $this->reservationModel->reject(
            (int) ($_POST['id'] ?? 0),
            trim($_POST['motivo_rechazo'] ?? '')
        );

        $this->redirect('reservas.php', [
            'mensaje' => $rechazada
                ? 'Reserva rechazada. El residente verá el motivo.'
                : 'Error al rechazar la reserva.',
        ]);
    }
}
