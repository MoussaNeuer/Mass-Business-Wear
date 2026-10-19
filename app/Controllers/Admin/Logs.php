<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Repositories\AdminSettingRepository;

/** Journal des actions admin. */
final class Logs extends BaseAdmin
{
    public function index(): never
    {
        $f = [
            'page' => max(1, Request::int('page', 1)),
            'admin_id' => Request::int('admin_id', 0),
        ];
        self::render('admin/logs', AdminSettingRepository::logs($f) + [
            'filters' => $f,
            'admins' => AdminSettingRepository::admins(),
        ], 'Journal');
    }
}
