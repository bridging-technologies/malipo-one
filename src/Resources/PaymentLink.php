<?php

namespace MalipoOne\Resources;

use MalipoOne\Client;

class PaymentLink
{
    public function __construct(private readonly Client $client) {}

    /**
     * Create a hosted payment page.
     *
     * @param  array{
     *     title: string,
     *     amount?: float|int,
     *     business_id?: int,
     *     payment_mode?: 'single'|'multiple',
     *     description?: string,
     *     expires_at?: string,
     *     success_url?: string,
     *     cancel_url?: string,
     * } $params
     */
    public function create(array $params): array
    {
        return $this->client->post('/pay-links', $params);
    }

    /**
     * Retrieve a payment link by its reference code.
     */
    public function get(string $reference): array
    {
        return $this->client->get("/pay-links/{$reference}");
    }
}
