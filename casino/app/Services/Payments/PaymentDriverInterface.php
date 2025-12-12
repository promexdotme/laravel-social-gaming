<?php

namespace VanguardLTE\Services\Payments;

use VanguardLTE\User;

interface PaymentDriverInterface
{
    /**
     * @return array{payment_url:string, external_id:string}
     */
    public function createInvoice(User $user, float $amount, array $meta = []): array;
}
