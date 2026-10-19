<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Security;

/**
 * Vérifie le CSRF sur toutes les mutations (POST/PUT/PATCH/DELETE).
 * Accepte le header X-CSRF-Token (fetch) ou le champ _token (formulaires).
 */
final class Csrf
{
    public static function handle(): void
    {
        Security::checkCsrf();
    }
}
