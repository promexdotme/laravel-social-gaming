<?php

namespace VanguardLTE\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use VanguardLTE\User;

class BtcpayDriver implements PaymentDriverInterface
{
    public function __construct(private array $config)
    {
    }

    public function createInvoice(User $user, float $amount, array $meta = []): array
    {
        $host = rtrim($this->config['host'] ?? '', '/');
        $storeId = $this->config['store_id'] ?? '';
        $apiKey = $this->config['api_key'] ?? '';
        $currency = $meta['currency'] ?? config('payments.default_currency', 'USD');

        if (!$host || !$storeId || !$apiKey) {
            throw new \RuntimeException('BTCPay is not configured.');
        }

        $invoiceMeta = [
            'buyerEmail' => $user->email,
            'posData' => json_encode([
                'user_id' => $user->id,
                'intent_id' => $meta['intent_id'] ?? null,
            ]),
        ];

        $payload = [
            'amount' => (string) number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'metadata' => $invoiceMeta,
            'checkout' => [
                'speedPolicy' => 'HighSpeed',
                'redirectURL' => $meta['return_url'] ?? url('/'),
                'defaultLanguage' => 'en',
            ],
        ];

        $endpoint = $host . "/api/v1/stores/{$storeId}/invoices";
        $response = Http::withToken($apiKey)->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('BTCPay invoice creation failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'payment_url' => $data['checkoutLink'] ?? '',
            'external_id' => $data['id'] ?? Str::uuid()->toString(),
        ];
    }
}
