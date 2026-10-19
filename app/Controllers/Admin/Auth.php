<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Security;
use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\AdminRepository;
use App\Repositories\LogRepository;
use App\Repositories\LoginAttemptRepository;

final class Auth
{
    private const WINDOW = 900; // 15 minutes
    private const DELAY_STEPS = [0, 0, 0, 1, 2, 4, 8]; // délai progressif en secondes

    public function showLogin(): never
    {
        if (Security::isAdmin()) {
            Response::redirect(Url::admin());
        }

        View::echo('admin/login', [
            'title' => 'Connexion — Administration',
            'error' => null,
            'next' => Request::str('next', ''),
        ], 'layouts/guest');
    }

    public function login(): never
    {
        $ip = Request::ip();
        $max = (int) (\App\Helpers\App::config('rate_limit_login') ?: 8);

        $attempts = LoginAttemptRepository::countSince($ip, self::WINDOW);
        if ($attempts >= $max) {
            $this->fail($ip, null, 'Trop de tentatives. Réessayez dans 15 minutes.', 429);
        }

        // Délai progressif : 1s, 2s, 4s, 8s...
        $step = self::DELAY_STEPS[min($attempts, count(self::DELAY_STEPS) - 1)] ?? 0;
        if ($step > 0) {
            sleep($step);
        }

        $login = trim(Request::post('login', ''));
        $password = (string) Request::post('password', '');
        $next = Request::str('next', '');

        if ($login === '' || $password === '') {
            $this->fail($ip, $login, 'Identifiants obligatoires.', 422);
        }

        $admin = AdminRepository::findByLogin($login);
        if ($admin === null || !Security::verifyPassword($password, (string) $admin['password_hash'])) {
            LoginAttemptRepository::record($ip, $login);
            $this->fail($ip, $login, 'Identifiants incorrects.', 401);
        }

        LoginAttemptRepository::clear($ip);
        Security::regenerate();

        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_login'] = (string) $admin['login'];
        $_SESSION['admin_last_regen'] = time();

        LogRepository::write((int) $admin['id'], 'login', 'Connexion réussie', $ip);

        // next doit rester interne (anti open-redirect)
        if ($next !== '' && str_starts_with($next, '/admin') && !str_starts_with($next, '//')) {
            Response::redirect($next);
        }
        Response::redirect(Url::admin());
    }

    public function logout(): never
    {
        $adminId = Security::adminId();
        LogRepository::write($adminId, 'logout', 'Déconnexion', Request::ip());
        Security::destroy();
        Response::redirect(Url::admin('login'));
    }

    private function fail(string $ip, ?string $login, string $message, int $status): never
    {
        if ($status === 401) {
            // déjà enregistré par l'appelant
        } elseif ($status !== 429) {
            LoginAttemptRepository::record($ip, $login);
        }

        if (Request::wantsJson()) {
            Response::error($message, $status);
        }

        // Re-rendu du formulaire avec erreur (conserve la session CSRF)
        http_response_code($status);
        Response::html(View::render('admin/login', [
            'title' => 'Connexion — Administration',
            'error' => $message,
            'next' => Request::str('next', ''),
        ], 'layouts/guest'), $status);
    }
}
