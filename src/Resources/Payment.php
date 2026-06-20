<?php

namespace MalipoOne\Resources;

use MalipoOne\Client;

class Payment
{
    public function __construct(private readonly Client $client) {}

    /**
     * List payments, newest first.
     *
     * @param  array{status?: 'PENDING'|'SUCCESS'|'FAILED', per_page?: int} $filters
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/payments', $filters);
    }

    /**
     * Retrieve a single payment by its Malipo UUID.
     */
    public function get(string $uuid): array
    {
        return $this->client->get("/payments/{$uuid}");
    }

    /**
     * Initiate a mobile-money USSD push to a customer's phone.
     *
     * Requires a unique Idempotency-Key header per attempt.
     *
     * @param  array{
     *     amount: float|int,
     *     currency: string,
     *     phone: string,
     *     reference: string,
     *     operator: 'MPESA'|'TIGOPESA'|'HALOPESA'|'AIRTEL'|'CRDB',
     *     description?: string,
     * } $params
     * @param  string $idempotencyKey  A unique UUID v4 per payment attempt.
     */
    public function initiate(array $params, string $idempotencyKey): array
    {
        return $this->client->post('/payments', $params, [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }
}
