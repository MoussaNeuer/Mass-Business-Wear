<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Url;
use App\Repositories\AdminSettingRepository;

/** Paramètres boutique + zones de livraison. */
final class Settings extends BaseAdmin
{
    private const KEYS = [
        'shop_name', 'whatsapp', 'phone', 'email', 'address',
        'instagram', 'tiktok', 'facebook', 'hours', 'currency', 'shipping_fee',
    ];

    public function index(): never
    {
        self::render('admin/settings/index', [
            'settings' => AdminSettingRepository::all(),
            'zones' => AdminSettingRepository::zones(),
        ], 'Paramètres');
    }

    public function update(): never
    {
        $pairs = [];
        foreach (self::KEYS as $key) {
            $pairs[$key] = trim(Request::post($key, ''));
        }
        if ($pairs['currency'] === '') {
            $pairs['currency'] = 'FCFA';
        }
        // L6 : le WhatsApp boutique est obligatoire (liens de confirmation commande)
        $waDigits = preg_replace('/\D+/', '', $pairs['whatsapp']) ?? '';
        if ($waDigits === '') {
            self::backError('Le numéro WhatsApp est obligatoire : sans lui, les clients ne peuvent pas confirmer leur commande.');
        }
        if (preg_match('/^(?:221)?[237]\d{8}$/', $waDigits) !== 1) {
            self::backError('Numéro WhatsApp invalide : 9 chiffres attendus (ex. 771234567).');
        }
        $pairs['whatsapp'] = $waDigits;
        AdminSettingRepository::saveMany($pairs);
        self::log('settings.update', 'Paramètres boutique');
        self::redirect(Url::admin('settings'), 'Paramètres enregistrés.');
    }

    public function zoneStore(): never
    {
        $name = trim(Request::post('name', ''));
        if ($name === '') {
            self::backError('Nom de zone obligatoire.');
        }
        $id = AdminSettingRepository::createZone([
            'name' => $name,
            'price' => max(0, Request::int('price', 0)),
            'is_active' => Request::post('is_active') !== null,
            'sort_order' => Request::int('sort_order', 0),
        ]);
        self::log('zone.create', $name . ' (#' . $id . ')');
        self::redirect(Url::admin('settings') . '#zones', 'Zone ajoutée.');
    }

    public function zoneUpdate(string $id): never
    {
        $id = (int) $id;
        $name = trim(Request::post('name', ''));
        if ($name === '') {
            self::backError('Nom de zone obligatoire.');
        }
        AdminSettingRepository::updateZone($id, [
            'name' => $name,
            'price' => max(0, Request::int('price', 0)),
            'is_active' => Request::post('is_active') !== null,
            'sort_order' => Request::int('sort_order', 0),
        ]);
        self::log('zone.update', $name . ' (#' . $id . ')');
        self::redirect(Url::admin('settings') . '#zones', 'Zone mise à jour.');
    }

    public function zoneDestroy(string $id): never
    {
        AdminSettingRepository::deleteZone((int) $id);
        self::log('zone.delete', '#' . $id);
        self::redirect(Url::admin('settings') . '#zones', 'Zone supprimée.');
    }
}
