<?php

namespace VanguardLTE\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use VanguardLTE\User;

class PaypalDriver implements PaymentDriverInterface
{
    public function __construct(private array $config)
    {
    }

    private function getAccessToken(): string
    {
        $clientId = $this->config['client_id'] ?? '';
        $secret = $this->config['secret'] ?? '';
        $mode = $this->config['mode'] ?? 'sandbox';

        if (!$clientId || !$secret) {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }

        $host = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($host . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('PayPal authentication failed: ' . $response->body());
        }

        return $response->json()['access_token'] ?? '';
    }

    public function createInvoice(User $user, float $amount, array $meta = []): array
    {
        $mode = $this->config['mode'] ?? 'sandbox';
        $host = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $accessToken = $this->getAccessToken();
        $currency = strtoupper($meta['currency'] ?? config('payments.default_currency', 'USD'));
        $returnUrl = $meta['return_url'] ?? url('/');

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                    'description' => 'Deposit funds to ' . settings('app_name', 'Casino'),
                    'custom_id' => (string) ($meta['intent_id'] ?? ''),
                ]
            ],
            'application_context' => [
                'return_url' => $returnUrl . '?status=success',
                'cancel_url' => $returnUrl . '?status=cancel',
                'user_action' => 'PAY_NOW',
            ]
        ];

        $response = Http::withToken($accessToken)
            ->post($host . '/v2/checkout/orders', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('PayPal order creation failed: ' . $response->body());
        }

        $data = $response->json();
        
        $checkoutUrl = '';
        if (isset($data['links'])) {
            foreach ($data['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $checkoutUrl = $link['href'];
                    break;
                }
            }
        }

        return [
            'payment_url' => $checkoutUrl,
            'external_id' => $data['id'] ?? Str::uuid()->toString(),
        ];
    }
}
