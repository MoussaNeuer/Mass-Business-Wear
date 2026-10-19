<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Security;
use App\Middleware\RateLimit;
use App\Services\OrderService;
use RuntimeException;

/**
 * API commandes — Phase 4.
 * POST /api/orders  — crée une commande (transaction, idempotence, stock).
 */
final class OrderApi
{
    public function store(): never
    {
        if (!Request::isMethod('POST')) {
            Response::error('Méthode non autorisée.', 405);
        }

        $payload = Request::json();
        if ($payload === []) {
            // Repli form-data (multipart ou test curl sans JSON)
            $payload = [
                'name' => (string) Request::post('name', ''),
                'phone' => (string) Request::post('phone', ''),
                'zone' => (string) Request::post('zone', ''),
                'address' => (string) Request::post('address', ''),
                'note' => (string) Request::post('note', ''),
                'items' => json_decode((string) Request::post('items', '[]'), true) ?: [],
            ];
        }

        // S2 : limite dédiée — 5 commandes / heure par IP (le quota global ne suffit pas)
        if (RateLimit::tooMany('orders_ip:' . Request::ip(), 5, 3600)) {
            Response::error('Trop de commandes depuis cette adresse. Réessayez dans une heure.', 429);
        }
        // ... et 3 / heure par numéro de téléphone (fausses commandes en masse)
        $phoneCheck = Security::normalizePhone((string) ($payload['phone'] ?? ''));
        if ($phoneCheck !== null && RateLimit::tooMany('orders_tel:' . $phoneCheck, 3, 3600)) {
            Response::error('Trop de commandes depuis ce numéro. Réessayez dans une heure.', 429);
        }

        $idempotencyKey = Request::header('X-Idempotency-Key')
            ?? (isset($payload['idempotency_key']) ? (string) $payload['idempotency_key'] : null);

        try {
            $result = OrderService::create($payload, $idempotencyKey, self::collectFiles());
        } catch (\PDOException $e) {
            // L9 : une erreur de base de données n'est jamais une erreur de validation (422)
            error_log('[order] db ' . $e->getMessage());
            Response::error('Une erreur est survenue lors de la commande. Réessayez.', 500);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            error_log('[order] ' . $e->getMessage());
            Response::error('Une erreur est survenue lors de la commande. Réessayez.', 500);
        }

        Response::json([
            'reference' => $result['reference'],
            'subtotal' => $result['subtotal'],
            'shipping' => $result['shipping'],
            'total' => $result['total'],
            'whatsapp_url' => $result['whatsapp_url'],
            'duplicate' => !empty($result['duplicate']),
            'images_count' => (int) ($result['images_count'] ?? 0),
        ], $result['duplicate'] ? 200 : 201, $result['duplicate']
            ? 'Commande déjà enregistrée.'
            : 'Commande enregistrée avec succès.');
    }

    /**
     * Photos de référence (Sprint C) : `images[]` ou `image` en multipart.
     *
     * @return list<array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    private static function collectFiles(): array
    {
        $out = [];
        foreach (['images', 'image'] as $key) {
            if (empty($_FILES[$key])) {
                continue;
            }
            $f = $_FILES[$key];
            if (is_array($f['name'] ?? null)) {
                foreach ($f['name'] as $i => $name) {
                    $err = (int) ($f['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                    if ($err === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $out[] = [
                        'name' => (string) $name,
                        'type' => (string) ($f['type'][$i] ?? ''),
                        'tmp_name' => (string) ($f['tmp_name'][$i] ?? ''),
                        'error' => $err,
                        'size' => (int) ($f['size'][$i] ?? 0),
                    ];
                }
            } elseif ((int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $out[] = [
                    'name' => (string) ($f['name'] ?? ''),
                    'type' => (string) ($f['type'] ?? ''),
                    'tmp_name' => (string) ($f['tmp_name'] ?? ''),
                    'error' => (int) $f['error'],
                    'size' => (int) ($f['size'] ?? 0),
                ];
            }
        }
        return $out;
    }
}
