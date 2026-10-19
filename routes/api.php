<?php

declare(strict_types=1);

use App\Controllers\Api\OrderApi;
use App\Controllers\Api\ProductApi;
use App\Helpers\Router;
use App\Helpers\Response;
use App\Middleware\RateLimit;

/** @var Router $router */

$router->get('/api/health', static function (): never {
    Response::json([
        'status' => 'ok',
        'time' => date('c'),
        'php' => PHP_VERSION,
    ], 200, 'Service opérationnel');
}, [RateLimit::class]);

$router->get('/api/products', [ProductApi::class, 'index'], [RateLimit::class]);
$router->get('/api/search', [ProductApi::class, 'search'], [RateLimit::class]);
$router->post('/api/orders', [OrderApi::class, 'store'], [RateLimit::class]);

// Phase 5+ : /api/admin/*
