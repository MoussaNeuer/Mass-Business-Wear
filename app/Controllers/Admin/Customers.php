<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Repositories\AdminCustomerRepository;

/** Clients admin : liste, recherche, détail + historique commandes. */
final class Customers extends BaseAdmin
{
    public function index(): never
    {
        $f = [
            'q' => trim(Request::str('q', '')),
            'page' => max(1, Request::int('page', 1)),
        ];
        self::render('admin/customers/index', AdminCustomerRepository::list($f) + [
            'filters' => $f,
        ], 'Clients');
    }

    public function show(string $id): never
    {
        $id = (int) $id;
        $customer = AdminCustomerRepository::find($id);
        if ($customer === null) {
            Response::abort(404, 'Client introuvable.');
        }
        self::render('admin/customers/show', [
            'customer' => $customer,
            'orders' => AdminCustomerRepository::orders($id),
        ], 'Client · ' . $customer['name']);
    }
}
