<?php

namespace VanguardLTE\Services\Payments;

use Illuminate\Support\Str;
use VanguardLTE\User;

class ManualPaymentDriver implements PaymentDriverInterface
{
    public function __construct(private array $config)
    {
    }

    public function createInvoice(User $user, float $amount, array $meta = []): array
    {
        $intentId = $meta['intent_id'] ?? null;
        if (!$intentId) {
            throw new \RuntimeException('Manual payment intent ID is missing.');
        }

        $paymentUrl = url("/payment/manual/{$intentId}");

        return [
            'payment_url' => $paymentUrl,
            'external_id' => 'MANUAL_' . $intentId . '_' . Str::random(8),
        ];
    }
}
