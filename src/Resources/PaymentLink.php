<?php

namespace MalipoOne\Resources;

use MalipoOne\Client;

class PaymentLink
{
    public function __construct(private readonly Client $client) {}

    /**
     * Create a hosted payment page. This is the only working integration
     * path for CRDB — see the note on Payment::initiate().
     *
     * The payer can cancel from the hosted page itself; there is no
     * merchant-initiated cancel endpoint. Redirects them to `cancel_url`
     * (or, if unset, leaves them on a "cancelled" state on the page) and
     * closes the link (`status` becomes `cancelled`).
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
     * @return array{data: array{id: string, reference: string, title: string, amount: ?float, currency: string, url: string, status: 'pending'|'paid'|'expired'|'cancelled', payment_mode: string, business_id: ?int, invoice_created: bool, is_active: bool, expires_at: ?string, created_at: string}}
     */
    public function create(array $params): array
    {
        return $this->client->post('/pay-links', $params);
    }

    /**
     * Retrieve a payment link by its reference code. Poll `status` here
     * (or use a `payment.success` webhook) to confirm payment server-side —
     * a customer reaching `success_url` in their browser is not proof of
     * payment on its own.
     *
     * @return array{data: array{id: string, reference: string, title: string, amount: ?float, currency: string, url: string, status: 'pending'|'paid'|'expired'|'cancelled', payment_mode: string, business_id: ?int, invoice_created: bool, is_active: bool, expires_at: ?string, created_at: string}}
     */
    public function get(string $reference): array
    {
        return $this->client->get("/pay-links/{$reference}");
    }
}
