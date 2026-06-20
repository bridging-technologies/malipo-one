<?php

namespace MalipoOne;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use MalipoOne\Exceptions\AuthenticationException;
use MalipoOne\Exceptions\MalipoOneException;
use MalipoOne\Exceptions\ValidationException;
use MalipoOne\Resources\Balance;
use MalipoOne\Resources\PaymentLink;
use MalipoOne\Resources\Payment;
use MalipoOne\Resources\Refund;

class Client
{
    private const DEFAULT_BASE_URL = 'https://malipo.one';
    private const TOKEN_PATH       = '/oauth/token';
    private const API_PREFIX       = '/api/v1';

    private HttpClient $http;
    private ?string $accessToken  = null;
    private int $tokenExpiresAt   = 0;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL
    ) {
        $this->http = new HttpClient([
            'base_uri' => rtrim($this->baseUrl, '/'),
            'timeout'  => 30,
            'headers'  => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent'   => 'MalipoOne-PHP-SDK/1.0',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Resource accessors
    // -------------------------------------------------------------------------

    public function paymentLinks(): PaymentLink
    {
        return new PaymentLink($this);
    }

    public function payments(): Payment
    {
        return new Payment($this);
    }

    public function refunds(): Refund
    {
        return new Refund($this);
    }

    public function balance(): Balance
    {
        return new Balance($this);
    }

    // -------------------------------------------------------------------------
    // HTTP helpers (used by resource classes)
    // -------------------------------------------------------------------------

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $body = [], array $headers = []): array
    {
        return $this->request('POST', $path, ['json' => $body, 'headers' => $headers]);
    }

    // -------------------------------------------------------------------------
    // Token management
    // -------------------------------------------------------------------------

    private function token(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiresAt - 60) {
            return $this->accessToken;
        }

        try {
            $response = $this->http->post(self::TOKEN_PATH, [
                'json' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope'         => '',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            $this->accessToken  = $data['access_token'];
            $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 86400);

            return $this->accessToken;
        } catch (ClientException $e) {
            throw new AuthenticationException(
                'Invalid client credentials.',
                $e->getResponse()->getStatusCode(),
                [],
                $e
            );
        }
    }

    private function request(string $method, string $path, array $options = []): array
    {
        $options['headers'] = array_merge(
            $options['headers'] ?? [],
            ['Authorization' => 'Bearer ' . $this->token()]
        );

        try {
            $response = $this->http->request($method, self::API_PREFIX . $path, $options);
            return json_decode((string) $response->getBody(), true);
        } catch (ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            $body   = json_decode((string) $e->getResponse()->getBody(), true) ?? [];

            if ($status === 422) {
                throw new ValidationException($body['message'] ?? 'Validation failed.', $status, $body, $e);
            }

            if ($status === 401) {
                $this->accessToken = null;
                throw new AuthenticationException($body['message'] ?? 'Unauthenticated.', $status, $body, $e);
            }

            throw new MalipoOneException($body['message'] ?? 'API error.', $status, $body, $e);
        } catch (ServerException $e) {
            throw new MalipoOneException('Malipo One server error. Try again later.', 500, [], $e);
        }
    }
}
