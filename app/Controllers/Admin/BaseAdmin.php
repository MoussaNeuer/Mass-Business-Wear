<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Security;
use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\LogRepository;

/**
 * Socle des contrôleurs admin : flash messages, redirections,
 * journalisation automatique des actions.
 */
abstract class BaseAdmin
{
    protected static function render(string $view, array $data = [], ?string $title = null): never
    {
        $data += [
            'title' => $title ?? 'Admin',
            'adminLogin' => Security::adminLogin(),
            'adminId' => Security::adminId(),
            'flash' => self::pullFlash(),
            'currentPath' => Request::path(),
        ];
        View::echo($view, $data, 'layouts/admin');
    }

    protected static function redirect(string $path, ?string $flash = null, string $type = 'ok'): never
    {
        if ($flash !== null) {
            self::flash($flash, $type);
        }
        Response::redirect($path);
    }

    protected static function flash(string $message, string $type = 'ok'): void
    {
        $_SESSION['flash'][] = ['m' => $message, 't' => $type];
    }

    /** @return list<array{m:string,t:string}> */
    protected static function pullFlash(): array
    {
        $all = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return is_array($all) ? $all : [];
    }

    /** Journalise une action admin (non bloquant). */
    protected static function log(string $action, ?string $details = null): void
    {
        LogRepository::write(Security::adminId(), $action, $details, Request::ip());
    }

    /** Redirige vers la liste avec message d'erreur (validation). */
    protected static function backError(string $message): never
    {
        self::flash($message, 'err');
        $ref = Request::header('Referer');
        $url = is_string($ref) && str_contains($ref, '/admin') ? $ref : Url::admin();
        Response::redirect($url);
    }
}
