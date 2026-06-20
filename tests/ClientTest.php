<?php

namespace MalipoOne\Tests;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MalipoOne\Client;
use MalipoOne\Exceptions\AuthenticationException;
use MalipoOne\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private function mockClient(array $responses): Client
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $http    = new HttpClient(['handler' => $handler]);

        $client = new Client('test-client-id', 'test-secret', 'https://malipo.one');

        // Inject mock HTTP client via reflection
        $ref = new \ReflectionProperty(Client::class, 'http');
        $ref->setAccessible(true);
        $ref->setValue($client, $http);

        return $client;
    }

    public function test_creates_payment_link(): void
    {
        $tokenResponse = new Response(200, [], json_encode([
            'token_type'   => 'Bearer',
            'expires_in'   => 86400,
            'access_token' => 'test-token',
        ]));

        $linkResponse = new Response(201, [], json_encode([
            'data' => [
                'id'              => 'abc-123',
                'reference'       => 'ABCD1234',
                'title'           => 'Test Payment',
                'amount'          => 50000.0,
                'currency'        => 'TZS',
                'url'             => 'https://malipo.one/pay/ABCD1234',
                'payment_mode'    => 'single',
                'business_id'     => null,
                'invoice_created' => false,
                'is_active'       => true,
                'expires_at'      => null,
                'created_at'      => '2026-06-20T10:00:00+03:00',
            ],
        ]));

        $client = $this->mockClient([$tokenResponse, $linkResponse]);
        $result = $client->paymentLinks()->create(['title' => 'Test Payment', 'amount' => 50000]);

        $this->assertEquals('ABCD1234', $result['data']['reference']);
        $this->assertEquals('https://malipo.one/pay/ABCD1234', $result['data']['url']);
    }

    public function test_throws_authentication_exception_on_invalid_credentials(): void
    {
        $this->expectException(AuthenticationException::class);

        $client = $this->mockClient([
            new Response(401, [], json_encode(['error' => 'invalid_client'])),
        ]);

        $client->paymentLinks()->create(['title' => 'Test']);
    }

    public function test_throws_validation_exception_on_422(): void
    {
        $this->expectException(ValidationException::class);

        $client = $this->mockClient([
            new Response(200, [], json_encode(['access_token' => 'tok', 'expires_in' => 86400])),
            new Response(422, [], json_encode([
                'message' => 'The given data was invalid.',
                'errors'  => ['title' => ['The title field is required.']],
            ])),
        ]);

        $client->paymentLinks()->create([]);
    }
}
