<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Repositories\SettingRepository;

/** Pages statiques : contact (Phase 2), à propos / légales (Phase 8). */
final class PageController
{
    public function contact(): never
    {
        View::echo('shop/contact', [
            'title' => 'Contact — MASS BUSINESS WEAR',
            'whatsapp' => SettingRepository::whatsapp(),
            'phone' => SettingRepository::get('phone'),
            'email' => SettingRepository::get('email'),
            'address' => SettingRepository::get('address'),
            'hours' => SettingRepository::get('hours'),
            'instagram' => SettingRepository::get('instagram'),
            'facebook' => SettingRepository::get('facebook'),
            'tiktok' => SettingRepository::get('tiktok'),
        ], 'layouts/shop');
    }
}
