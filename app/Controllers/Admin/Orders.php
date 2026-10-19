<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Url;
use App\Repositories\AdminOrderRepository;
use App\Repositories\SettingRepository;

/** Gestion des commandes admin : liste, détail, changement de statut. */
final class Orders extends BaseAdmin
{
    public function index(): never
    {
        $f = [
            'status' => Request::str('status', ''),
            'q' => trim(Request::str('q', '')),
            'page' => max(1, Request::int('page', 1)),
        ];
        self::render('admin/orders/index', AdminOrderRepository::list($f) + [
            'filters' => $f,
            'statusCounts' => AdminOrderRepository::countByStatus(),
            'statusLabels' => AdminOrderRepository::STATUS_LABELS,
        ], 'Commandes');
    }

    public function show(string $id): never
    {
        $id = (int) $id;
        $order = AdminOrderRepository::find($id);
        if ($order === null) {
            Response::abort(404, 'Commande introuvable.');
        }
        self::render('admin/orders/show', [
            'order' => $order,
            'items' => AdminOrderRepository::items($id),
            'history' => AdminOrderRepository::history($id),
            'statusLabels' => AdminOrderRepository::STATUS_LABELS,
            'currency' => SettingRepository::currency(),
        ], 'Commande ' . $order['reference']);
    }

    public function updateStatus(string $id): never
    {
        $id = (int) $id;
        $status = Request::post('status', '');
        $note = trim(Request::post('note', ''));
        if (!in_array($status, AdminOrderRepository::STATUSES, true)) {
            self::backError('Statut invalide.');
        }
        $order = AdminOrderRepository::find($id);
        if ($order === null) {
            Response::abort(404);
        }
        $ok = AdminOrderRepository::updateStatus($id, $status, $note, $this->adminId());
        if (!$ok) {
            self::backError('Changement de statut impossible.');
        }
        self::log('order.status', $order['reference'] . ' → ' . $status . ($note !== '' ? ' (' . $note . ')' : ''));
        self::redirect(Url::admin('orders/' . $id), 'Statut mis à jour : ' . (AdminOrderRepository::STATUS_LABELS[$status] ?? $status) . '.');
    }

    public function destroy(string $id): never
    {
        $id = (int) $id;
        $order = AdminOrderRepository::find($id);
        if ($order === null) {
            Response::abort(404);
        }
        AdminOrderRepository::delete($id);
        self::log('order.delete', $order['reference']);
        self::redirect(Url::admin('orders'), 'Commande supprimée.');
    }

    private function adminId(): ?int
    {
        return \App\Helpers\Security::adminId();
    }
}
