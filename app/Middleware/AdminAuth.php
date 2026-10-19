<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Security;
use App\Repositories\AdminRepository;

/**
 * Protège les routes /admin/*. Redirige vers la page de connexion
 * si la session ne porte pas d'admin authentifié.
 */
final class AdminAuth
{
    public static function handle(): void
    {
        $adminId = Security::adminId();
        if ($adminId === null) {
            if (Request::wantsJson()) {
                Response::error('Authentification requise.', 401);
            }
            Response::redirect(self::loginUrl());
        }

        $admin = AdminRepository::findActive($adminId);
        if ($admin === null) {
            Security::destroy();
            if (Request::wantsJson()) {
                Response::error('Session invalide.', 401);
            }
            Response::redirect(self::loginUrl());
        }

        // Régénération périodique de l'ID de session (toutes les 30 min)
        $now = time();
        if (empty($_SESSION['admin_last_regen']) || ($now - (int) $_SESSION['admin_last_regen']) > 1800) {
            Security::regenerate();
            $_SESSION['admin_last_regen'] = $now;
        }
    }

    private static function loginUrl(): string
    {
        $next = Request::path();
        $suffix = $next !== '/' && !str_starts_with($next, '/admin/login') ? '?next=' . urlencode($next) : '';
        return \App\Helpers\Url::admin('login') . $suffix;
    }
}
