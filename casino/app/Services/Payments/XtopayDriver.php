<?php

namespace VanguardLTE\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use VanguardLTE\User;

class XtopayDriver implements PaymentDriverInterface
{
    public function __construct(private array $config)
    {
    }

    public function createInvoice(User $user, float $amount, array $meta = []): array
    {
        $enabled = $this->config['enabled'] ?? false;
        $token = $this->config['token'] ?? '';
        $websiteName = $this->config['website_name'] ?? 'one';
        $allowedMethodsStr = $this->config['allowed_methods'] ?? 'TRC20_USDT,POLYGON_USDT,BSC_USDT,ERC20_USDT,POLYGON_USDC,BSC_USDC,ERC20_USDC';

        if (!$enabled || !$token) {
            throw new \RuntimeException('XtoPay Crypto Gateway is not configured.');
        }

        // Generate unique merchant reference: one377_{intent_id}_{random_suffix}
        $intentId = $meta['intent_id'] ?? Str::random(10);
        $merchantRef = "one377_" . $intentId . "_" . Str::random(6);

        // Explode methods
        $methods = array_filter(array_map('trim', explode(',', $allowedMethodsStr)));

        $payload = [
            'amount_usd' => (float)$amount,
            'website_name' => $websiteName,
            'merchant_ref' => $merchantRef,
            'allowed_methods' => array_values($methods),
        ];

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post('https://xto.377.live/api/merchant/payment-links', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('XtoPay invoice creation failed: ' . $response->body());
        }

        $data = $response->json();
        $checkoutUrlPath = $data['checkout_url'] ?? '';
        if (empty($checkoutUrlPath)) {
            throw new \RuntimeException('XtoPay checkout URL is missing in gateway response.');
        }

        $paymentUrl = 'https://xto.377.live' . '/' . ltrim($checkoutUrlPath, '/');
        $externalId = $data['payment_id'] ?? $merchantRef;

        return [
            'payment_url' => $paymentUrl,
            'external_id' => (string)$externalId,
        ];
    }
}
