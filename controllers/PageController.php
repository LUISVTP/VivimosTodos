<?php

declare(strict_types=1);

class PageController extends BaseController
{
    private UserModel $userModel;
    private ReservationModel $reservationModel;
    private InventoryModel $inventoryModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->reservationModel = new ReservationModel();
        $this->inventoryModel = new InventoryModel();
    }

    public function dashboard(): void
    {
        $this->requireAuth([1]);

        $usuarios = $this->userModel->getAllWithRoles();
        $this->render('dashboard', [
            'usuarios' => $usuarios,
            'total_usuarios' => count($usuarios),
            'pendientes' => $this->reservationModel->countPending(),
            'total_insumos' => $this->inventoryModel->countAll(),
        ]);
    }

    public function inventario(): void
    {
        $this->requireAuth([1, 2, 3]);

        $this->render('inventario', [
            'rol_id' => $this->roleId(),
            'insumos' => $this->inventoryModel->getAll(),
            'catalogo' => $this->inventoryModel->getCatalog(),
        ]);
    }

    public function reservas(): void
    {
        $this->requireAuth([1, 2, 3]);

        $rolId = $this->roleId();
        $reservas = $rolId === 2
            ? $this->reservationModel->getByUser($this->userId())
            : $this->reservationModel->getAllWithUsers();

        $this->render('reservas', [
            'rol_id' => $rolId,
            'reservas' => $reservas,
            'insumos_disponibles' => $rolId === 2 ? $this->inventoryModel->getAvailableItems() : [],
            'min_fecha' => date('Y-m-d', strtotime('+48 hours')),
            'max_fecha' => date('Y-m-d', strtotime('+90 days')),
        ]);
    }

    public function residente(): void
    {
        $this->requireAuth([2]);
        $this->render('residente');
    }

    public function supervisor(): void
    {
        $this->requireAuth([3]);
        $this->render('supervisor');
    }
}
