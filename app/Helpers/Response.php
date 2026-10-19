<?php

declare(strict_types=1);

namespace App\Helpers;

use Throwable;

final class Response
{
    public static function html(string $body, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $body;
        exit;
    }

    public static function json(mixed $data, int $status = 200, string $message = ''): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $payload = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
            'data' => $data,
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $status = 400, mixed $data = null): never
    {
        self::json($data, $status, $message);
    }

    public static function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        if (Request::wantsJson()) {
            $default = match ($status) {
                403 => 'Accès refusé.',
                404 => 'Ressource introuvable.',
                405 => 'Méthode non autorisée.',
                419 => 'Session expirée, veuillez réessayer.',
                429 => 'Trop de tentatives, réessayez plus tard.',
                default => 'Une erreur est survenue.',
            };
            self::error($message !== '' ? $message : $default, $status);
        }

        try {
            self::html(View::render('errors/error', [
                'status' => $status,
                'message' => $message,
                'title' => 'Erreur ' . $status,
            ], 'layouts/guest'), $status);
        } catch (Throwable) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="fr"><meta charset="utf-8"><title>Erreur ' . (int) $status
                . '</title><body style="font-family:system-ui;background:#0A0A0A;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0">'
                . '<div style="text-align:center;padding:2rem"><h1 style="font-size:4rem;margin:0;color:#D40000">' . (int) $status . '</h1>'
                . '<p>' . htmlspecialchars($message !== '' ? $message : 'Une erreur est survenue.', ENT_QUOTES) . '</p>'
                . '<a href="/" style="color:#D40000">Retour à l\'accueil</a></div></body></html>';
            exit;
        }
    }
}
