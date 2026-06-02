<?php

namespace VanguardLTE\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use VanguardLTE\User;

class StripeDriver implements PaymentDriverInterface
{
    public function __construct(private array $config)
    {
    }

    public function createInvoice(User $user, float $amount, array $meta = []): array
    {
        $secretKey = $this->config['secret_key'] ?? '';
        if (!$secretKey) {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        $currency = strtolower($meta['currency'] ?? config('payments.default_currency', 'USD'));
        $intentId = $meta['intent_id'] ?? null;
        $returnUrl = $meta['return_url'] ?? url('/');

        $payload = [
            'success_url' => $returnUrl . '?status=success',
            'cancel_url' => $returnUrl . '?status=cancel',
            'mode' => 'payment',
            'client_reference_id' => (string) $intentId,
            'customer_email' => $user->email,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Deposit funds to ' . settings('app_name', 'Casino'),
                        ],
                        'unit_amount' => (int) round($amount * 100),
                    ],
                    'quantity' => 1,
                ]
            ]
        ];

        // PHP's http_build_query handles nested arrays for asForm() automatically
        $response = Http::withToken($secretKey)
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Stripe checkout session creation failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'payment_url' => $data['url'] ?? '',
            'external_id' => $data['id'] ?? Str::uuid()->toString(),
        ];
    }
}
