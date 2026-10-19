<?php

declare(strict_types=1);

use App\Controllers\Admin\Auth;
use App\Controllers\Admin\Categories;
use App\Controllers\Admin\Customers;
use App\Controllers\Admin\Dashboard;
use App\Controllers\Admin\Logs;
use App\Controllers\Admin\Orders;
use App\Controllers\Admin\Products;
use App\Controllers\Admin\Settings;
use App\Controllers\Admin\Stats;
use App\Helpers\Router;
use App\Middleware\AdminAuth;

/** @var Router $router */

$router->group('/admin', function (Router $r) {
    // Auth (hors protection)
    $r->get('/login', [Auth::class, 'showLogin']);
    $r->post('/login', [Auth::class, 'login']);
    $r->post('/logout', [Auth::class, 'logout']);

    // Dashboard
    $r->get('', [Dashboard::class, 'index'], [AdminAuth::class]);
    $r->get('/', [Dashboard::class, 'index'], [AdminAuth::class]);

    // Statistiques
    $r->get('/stats', [Stats::class, 'index'], [AdminAuth::class]);

    // Clients
    $r->get('/customers', [Customers::class, 'index'], [AdminAuth::class]);
    $r->get('/customers/{id:\d+}', [Customers::class, 'show'], [AdminAuth::class]);

    // Produits
    $r->get('/products', [Products::class, 'index'], [AdminAuth::class]);
    $r->get('/products/create', [Products::class, 'create'], [AdminAuth::class]);
    $r->post('/products', [Products::class, 'store'], [AdminAuth::class]);
    $r->get('/products/{id:\d+}/edit', [Products::class, 'edit'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}', [Products::class, 'update'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/delete', [Products::class, 'destroy'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/featured', [Products::class, 'toggleFeatured'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/status', [Products::class, 'setStatus'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/variants', [Products::class, 'variantStore'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/variants/{vid:\d+}', [Products::class, 'variantUpdate'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/variants/{vid:\d+}/delete', [Products::class, 'variantDestroy'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/images', [Products::class, 'imageUpload'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/images/{iid:\d+}/delete', [Products::class, 'imageDestroy'], [AdminAuth::class]);
    $r->post('/products/{id:\d+}/images/{iid:\d+}/primary', [Products::class, 'imagePrimary'], [AdminAuth::class]);

    // Catégories
    $r->get('/categories', [Categories::class, 'index'], [AdminAuth::class]);
    $r->post('/categories', [Categories::class, 'store'], [AdminAuth::class]);
    $r->post('/categories/{id:\d+}', [Categories::class, 'update'], [AdminAuth::class]);
    $r->post('/categories/{id:\d+}/delete', [Categories::class, 'destroy'], [AdminAuth::class]);

    // Commandes
    $r->get('/orders', [Orders::class, 'index'], [AdminAuth::class]);
    $r->get('/orders/{id:\d+}', [Orders::class, 'show'], [AdminAuth::class]);
    $r->post('/orders/{id:\d+}/status', [Orders::class, 'updateStatus'], [AdminAuth::class]);
    $r->post('/orders/{id:\d+}/confirm', [Orders::class, 'confirm'], [AdminAuth::class]);
    $r->post('/orders/{id:\d+}/archive', [Orders::class, 'archive'], [AdminAuth::class]);
    $r->post('/orders/{id:\d+}/unarchive', [Orders::class, 'unarchive'], [AdminAuth::class]);

    // Paramètres
    $r->get('/settings', [Settings::class, 'index'], [AdminAuth::class]);
    $r->post('/settings', [Settings::class, 'update'], [AdminAuth::class]);
    $r->post('/settings/zones', [Settings::class, 'zoneStore'], [AdminAuth::class]);
    $r->post('/settings/zones/{id:\d+}', [Settings::class, 'zoneUpdate'], [AdminAuth::class]);
    $r->post('/settings/zones/{id:\d+}/delete', [Settings::class, 'zoneDestroy'], [AdminAuth::class]);

    // Journal
    $r->get('/logs', [Logs::class, 'index'], [AdminAuth::class]);
});
