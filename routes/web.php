<?php

declare(strict_types=1);

use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\ManifestController;
use App\Controllers\OrderSuccessController;
use App\Controllers\PageController;
use App\Controllers\ProductController;
use App\Controllers\SeoController;
use App\Controllers\ShopController;
use App\Helpers\Router;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/shop', [ShopController::class, 'index']);
$router->get('/category/{slug:[a-z0-9-]+}', [ShopController::class, 'category']);
$router->get('/product/{slug:[a-z0-9-]+}', [ProductController::class, 'show']);
$router->get('/cart', [CartController::class, 'index']);
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->get('/order/success', [OrderSuccessController::class, 'index']);
$router->get('/contact', [PageController::class, 'contact']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/legal/privacy', [PageController::class, 'privacy']);
$router->get('/legal/terms', [PageController::class, 'terms']);
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/manifest.json', [ManifestController::class, 'shop']);
$router->get('/manifest.admin.json', [ManifestController::class, 'admin']);
