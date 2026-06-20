<?php

namespace MalipoOne\Resources;

use MalipoOne\Client;

class Refund
{
    public function __construct(private readonly Client $client) {}

    /**
     * Issue a full or partial refund on a successful payment.
     *
     * Requires a unique Idempotency-Key header per attempt.
     *
     * @param  array{
     *     payment_id: string,
     *     reason: string,
     *     amount?: float|int,
     * } $params
     * @param  string $idempotencyKey  A unique UUID v4 per refund attempt.
     */
    public function create(array $params, string $idempotencyKey): array
    {
        return $this->client->post('/refunds', $params, [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }
}
