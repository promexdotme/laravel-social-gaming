<?php

namespace VanguardLTE\Http\Controllers\Web\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Services\Payments\BtcpayDriver;
use VanguardLTE\Services\Payments\PaymentDriverInterface;
use VanguardLTE\Transaction;
use VanguardLTE\User;

class TopupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('create');
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.5|max:1000000',
            'driver' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $user = auth()->user();
        $amount = (float) $request->input('amount');
        $driverKey = $request->input('driver', 'btcpay');

        $driver = $this->resolveDriver($driverKey);
        if (!$driver) {
            return response()->json(['error' => 'Payment driver not available.'], 422);
        }

        $intentId = DB::table('payment_intents')->insertGetId([
            'user_id' => $user->id,
            'driver' => $driverKey,
            'amount' => $amount,
            'currency' => $user->shop->currency ?? config('payments.default_currency', 'USD'),
            'status' => 'pending',
            'meta' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $meta = [
            'intent_id' => $intentId,
            'return_url' => url('/'),
            'currency' => $user->shop->currency ?? config('payments.default_currency', 'USD'),
        ];

        try {
            $invoice = $driver->createInvoice($user, $amount, $meta);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        DB::table('payment_intents')->where('id', $intentId)->update([
            'external_id' => $invoice['external_id'] ?? null,
            'payment_url' => $invoice['payment_url'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json([
            'payment_url' => $invoice['payment_url'] ?? null,
            'intent_id' => $intentId,
        ]);
    }

    public function webhookBtcpay(Request $request)
    {
        $config = config('payments.drivers.btcpay');
        if (!($config['enabled'] ?? false)) {
            return response()->json(['error' => 'BTCPay disabled'], 404);
        }

        $secret = $config['webhook_secret'] ?? '';
        $signature = $request->header('Btcpay-Signature', '');
        $raw = $request->getContent();

        if (!$this->validateSignature($raw, $signature, $secret)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($raw, true);
        $invoiceId = $payload['invoiceId'] ?? null;
        $status = $payload['type'] ?? '';

        if (!$invoiceId) {
            return response()->json(['error' => 'Missing invoice id'], 422);
        }

        $intent = DB::table('payment_intents')
            ->where('external_id', $invoiceId)
            ->lockForUpdate()
            ->first();

        if (!$intent || $intent->status === 'paid') {
            return response()->json(['ok' => true]);
        }

        // BTCPay event types: InvoiceSettled etc.
        if (strtolower($status) !== 'invoicesettled' && strtolower($status) !== 'invoice_paid') {
            return response()->json(['ok' => true]);
        }

        $user = User::find($intent->user_id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        DB::transaction(function () use ($intent, $user) {
            $newBalance = (float) $user->balance + (float) $intent->amount;

            DB::table('users')->where('id', $user->id)->update([
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'admin_id' => null,
                'direction' => 'payment',
                'amount' => $intent->amount,
                'balance_before' => $user->balance,
                'balance_after' => $newBalance,
                'source' => 'btcpay',
                'note' => 'BTCPay invoice ' . $intent->external_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('payment_intents')->where('id', $intent->id)->update([
                'status' => 'paid',
                'updated_at' => now(),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    private function validateSignature(string $payload, string $signature, string $secret): bool
    {
        if (!$secret || !$signature) {
            return false;
        }

        // Signature format: sha256=...
        $parts = explode('=', $signature, 2);
        $sigHash = $parts[1] ?? '';
        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $sigHash);
    }

    private function resolveDriver(string $driver): ?PaymentDriverInterface
    {
        $driver = strtolower($driver);

        if ($driver === 'btcpay') {
            $cfg = config('payments.drivers.btcpay');
            if (!($cfg['enabled'] ?? false)) {
                return null;
            }
            return new BtcpayDriver($cfg);
        }

        return null;
    }
}
