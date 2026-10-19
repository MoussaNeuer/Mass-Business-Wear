<?php

declare(strict_types=1);

/**
 * Front controller — MASS BUSINESS WEAR
 * Tout le trafic (hors fichiers statiques) entre par ici.
 */

require dirname(__DIR__) . '/app/autoload.php';

use App\Helpers\App;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Router;
use App\Helpers\Security;

$config = require dirname(__DIR__) . '/config/app.php';

App::boot($config);

// Connexion PDO
\App\Helpers\Database::configure($config['db']);

Security::bootSession();
Security::securityHeaders();
Security::cspHeaders();

// Enregistrement du logo dans le flux (évite un render inutile en 404)
try {
    $router = new Router();
    require dirname(__DIR__) . '/routes/web.php';
    require dirname(__DIR__) . '/routes/admin.php';
    require dirname(__DIR__) . '/routes/api.php';

    // CSRF global sur les mutations (sauf exemption future API)
    Security::checkCsrf();

    $router->dispatch();
} catch (Throwable $e) {
    $logDir = dirname(__DIR__) . '/storage/logs';
    if (is_dir($logDir) && is_writable($logDir)) {
        @file_put_contents(
            $logDir . '/error-' . date('Y-m-d') . '.log',
            sprintf("[%s] %s in %s:%d\n%s\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()),
            FILE_APPEND
        );
    }

    if (App::debug()) {
        Response::abort(500, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    }
    Response::abort(500, 'Une erreur interne est survenue.');
}
