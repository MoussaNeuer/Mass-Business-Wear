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
            'archived' => Request::str('archived', '') === '1',
        ];
        self::render('admin/orders/index', AdminOrderRepository::list($f) + [
            'filters' => $f,
            'statusCounts' => AdminOrderRepository::countByStatus(),
            'statusLabels' => AdminOrderRepository::STATUS_LABELS,
            'whatsappNumber' => (string) \App\Repositories\SettingRepository::whatsapp(),
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
            'clientImages' => AdminOrderRepository::images($id),
            'statusLabels' => AdminOrderRepository::STATUS_LABELS,
            'allowedStatuses' => AdminOrderRepository::TRANSITIONS[(string) $order['status']] ?? [],
            'currency' => SettingRepository::currency(),
            'whatsappNumber' => (string) SettingRepository::whatsapp(),
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
        $err = AdminOrderRepository::updateStatus($id, $status, $note, $this->adminId());
        if ($err !== null) {
            self::backError($err);
        }
        self::log('order.status', $order['reference'] . ' → ' . $status . ($note !== '' ? ' (' . $note . ')' : ''));
        self::redirect(Url::admin('orders/' . $id), 'Statut mis à jour : ' . (AdminOrderRepository::STATUS_LABELS[$status] ?? $status) . '.');
    }

    /** Confirmation en 1 tap (B5) : NEW → CONFIRMED. */
    public function confirm(string $id): never
    {
        $id = (int) $id;
        $order = AdminOrderRepository::find($id);
        if ($order === null) {
            Response::abort(404);
        }
        // m13 : message clair si la commande n'est plus en attente
        if ((string) $order['status'] !== 'NEW') {
            self::backError('Seules les commandes en attente (NEW) peuvent être confirmées. Statut actuel : '
                . (AdminOrderRepository::STATUS_LABELS[(string) $order['status']] ?? $order['status']) . '.');
        }
        $err = AdminOrderRepository::updateStatus($id, 'CONFIRMED', 'Confirmée en 1 tap', $this->adminId());
        if ($err !== null) {
            self::backError($err);
        }
        self::log('order.confirm', $order['reference']);
        self::redirect(Url::admin('orders/' . $id), 'Commande ' . $order['reference'] . ' confirmée.');
    }

    /** B7 : archivage au lieu de la suppression définitive. */
    public function archive(string $id): never
    {
        $id = (int) $id;
        $order = AdminOrderRepository::find($id);
        if ($order === null) {
            Response::abort(404);
        }
        if (!empty($order['archived_at'])) {
            self::backError('Cette commande est déjà archivée.');
        }
        AdminOrderRepository::archive($id);
        self::log('order.archive', $order['reference']);
        self::redirect(Url::admin('orders?archived=1'), 'Commande archivée (historique et CA conservés).');
    }

    public function unarchive(string $id): never
    {
        $id = (int) $id;
        $order = AdminOrderRepository::find($id);
        if ($order === null) {
            Response::abort(404);
        }
        AdminOrderRepository::unarchive($id);
        self::log('order.unarchive', $order['reference']);
        self::redirect(Url::admin('orders'), 'Commande restaurée.');
    }

    private function adminId(): ?int
    {
        return \App\Helpers\Security::adminId();
    }
}
