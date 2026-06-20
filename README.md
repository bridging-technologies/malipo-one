# Malipo One PHP SDK

Official PHP client for the [Malipo One](https://malipo.one) payments API — mobile money collection, payment links, refunds and balances for Tanzania.

## Requirements

- PHP 8.1+
- Composer

## Installation

```bash
composer require malipo-one/php-sdk
```

## Quick Start

```php
use MalipoOne\Client;

$malipo = new Client(
    clientId:     'your-client-id',
    clientSecret: 'your-client-secret',
);
```

Tokens are fetched and cached automatically. You never call `/oauth/token` yourself.

---

## Payment Links

Create a hosted payment page and share the URL with your customer:

```php
// Fixed-amount link (single payment, auto-closes after paid)
$link = $malipo->paymentLinks()->create([
    'title'       => 'Invoice #1234',
    'amount'      => 50000,
    'business_id' => 12,           // optional — auto-creates invoice
    'description' => 'Web design services',
    'expires_at'  => '2026-12-31T23:59:59+03:00',
]);

echo $link['data']['url'];        // https://malipo.one/pay/ABCD1234
echo $link['data']['reference'];  // ABCD1234
echo $link['data']['invoice_created']; // true

// Open-amount donation link (stays open for multiple payments)
$link = $malipo->paymentLinks()->create([
    'title'        => 'Donate to Our Cause',
    'payment_mode' => 'multiple',
]);

// Retrieve a link by reference
$link = $malipo->paymentLinks()->get('ABCD1234');
```

---

## Payments (USSD Push)

Send a payment prompt directly to a customer's phone:

```php
use Ramsey\Uuid\Uuid;

$payment = $malipo->payments()->initiate(
    params: [
        'amount'    => 50000,
        'currency'  => 'TZS',
        'phone'     => '+255700000001',
        'reference' => 'INV-2025-001',
        'operator'  => 'MPESA',         // MPESA | TIGOPESA | HALOPESA | AIRTEL | CRDB
    ],
    idempotencyKey: Uuid::uuid4()->toString()
);

// Poll until status changes from pending
$payment = $malipo->payments()->get($payment['data']['id']);
echo $payment['data']['status']; // pending | success | failed

// List payments
$payments = $malipo->payments()->list(['status' => 'SUCCESS', 'per_page' => 10]);
```

---

## Refunds

```php
use Ramsey\Uuid\Uuid;

$refund = $malipo->refunds()->create(
    params: [
        'payment_id' => '9d3c8b12-4e6a-4a1f-b2c3-1d2e3f4a5b6c',
        'reason'     => 'Customer requested cancellation.',
        'amount'     => 25000, // omit for full refund
    ],
    idempotencyKey: Uuid::uuid4()->toString()
);
```

---

## Balance

```php
$balance = $malipo->balance()->get();
```

---

## Error Handling

```php
use MalipoOne\Exceptions\AuthenticationException;
use MalipoOne\Exceptions\MalipoOneException;
use MalipoOne\Exceptions\ValidationException;

try {
    $link = $malipo->paymentLinks()->create(['title' => 'Test']);
} catch (ValidationException $e) {
    // 422 — field-level errors
    foreach ($e->getFieldErrors() as $field => $messages) {
        echo "$field: " . implode(', ', $messages) . "\n";
    }
} catch (AuthenticationException $e) {
    // 401 — bad credentials or revoked client
    echo "Auth failed: " . $e->getMessage();
} catch (MalipoOneException $e) {
    // Everything else
    echo "API error {$e->getStatusCode()}: " . $e->getMessage();
}
```

---

## Sandbox / Local Testing

```php
$malipo = new Client(
    clientId:     'your-client-id',
    clientSecret: 'your-client-secret',
    baseUrl:      'http://localhost/malipo-one/public_html',
);
```

---

## License

MIT © [Malipo One](https://malipo.one)
