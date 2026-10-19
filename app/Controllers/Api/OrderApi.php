<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
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
            // Repli form-data (test curl sans JSON)
            $payload = [
                'name' => (string) Request::post('name', ''),
                'phone' => (string) Request::post('phone', ''),
                'zone' => (string) Request::post('zone', ''),
                'address' => (string) Request::post('address', ''),
                'note' => (string) Request::post('note', ''),
                'items' => json_decode((string) Request::post('items', '[]'), true) ?: [],
            ];
        }

        $idempotencyKey = Request::header('X-Idempotency-Key')
            ?? (isset($payload['idempotency_key']) ? (string) $payload['idempotency_key'] : null);

        try {
            $result = OrderService::create($payload, $idempotencyKey);
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
        ], $result['duplicate'] ? 200 : 201, $result['duplicate']
            ? 'Commande déjà enregistrée.'
            : 'Commande enregistrée avec succès.');
    }
}
