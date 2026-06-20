<?php

namespace MalipoOne\Resources;

use MalipoOne\Client;

class Balance
{
    public function __construct(private readonly Client $client) {}

    /**
     * Retrieve the merchant's current balance and settlement summary.
     */
    public function get(): array
    {
        return $this->client->get('/balance');
    }
}
