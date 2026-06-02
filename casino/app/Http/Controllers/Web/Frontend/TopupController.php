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
        $this->middleware('auth')->only(['create', 'showManualPayment', 'submitManualDeposit']);
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
            'return_url' => $driverKey === 'paypal' ? route('payment.paypal.return') : url('/'),
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
        $enabled = settings('payment_btcpay_enabled', config('payments.drivers.btcpay.enabled'));
        if (!$enabled) {
            return response()->json(['error' => 'BTCPay disabled'], 404);
        }

        $secret = settings('payment_btcpay_webhook_secret', config('payments.drivers.btcpay.webhook_secret'));
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

    public function webhookStripe(Request $request)
    {
        $enabled = settings('payment_stripe_enabled', config('payments.drivers.stripe.enabled'));
        if (!$enabled) {
            return response()->json(['error' => 'Stripe disabled'], 404);
        }

        $secret = settings('payment_stripe_webhook_secret', config('payments.drivers.stripe.webhook_secret'));
        $signature = $request->header('Stripe-Signature', '');
        $payload = $request->getContent();

        if (!$this->validateStripeSignature($payload, $signature, $secret)) {
            return response()->json(['error' => 'Invalid Stripe signature'], 401);
        }

        $data = json_decode($payload, true);
        if (($data['type'] ?? '') !== 'checkout.session.completed') {
            return response()->json(['ok' => true]);
        }

        $session = $data['data']['object'] ?? null;
        if (!$session) {
            return response()->json(['error' => 'Missing session object'], 422);
        }

        $intentId = $session['client_reference_id'] ?? null;
        if (!$intentId) {
            return response()->json(['error' => 'Missing client_reference_id'], 422);
        }

        $intent = DB::table('payment_intents')
            ->where('id', $intentId)
            ->lockForUpdate()
            ->first();

        if (!$intent || $intent->status === 'paid') {
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
                'source' => 'stripe',
                'note' => 'Stripe session ' . ($intent->external_id ?? ''),
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

    public function paypalReturn(Request $request)
    {
        $status = $request->input('status');
        $orderId = $request->input('token');

        if ($status !== 'success' || !$orderId) {
            return redirect('/')->with('error', 'PayPal deposit was cancelled or failed.');
        }

        $intent = DB::table('payment_intents')
            ->where('external_id', $orderId)
            ->first();

        if (!$intent) {
            return redirect('/')->with('error', 'PayPal transaction not found.');
        }

        if ($intent->status === 'paid') {
            return redirect('/')->with('success', 'Deposit of ' . $intent->amount . ' credited successfully.');
        }

        try {
            $clientId = settings('payment_paypal_client_id', config('payments.drivers.paypal.client_id'));
            $secret = settings('payment_paypal_secret', config('payments.drivers.paypal.secret'));
            $mode = settings('payment_paypal_mode', config('payments.drivers.paypal.mode', 'sandbox'));

            if (!$clientId || !$secret) {
                throw new \RuntimeException('PayPal credentials not configured.');
            }

            $host = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

            $authResponse = \Illuminate\Support\Facades\Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->post($host . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$authResponse->successful()) {
                throw new \RuntimeException('PayPal authentication failed: ' . $authResponse->body());
            }

            $accessToken = $authResponse->json()['access_token'] ?? '';

            $captureResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($host . "/v2/checkout/orders/{$orderId}/capture");

            if (!$captureResponse->successful()) {
                $showResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                    ->get($host . "/v2/checkout/orders/{$orderId}");

                if ($showResponse->successful() && ($showResponse->json()['status'] ?? '') === 'COMPLETED') {
                    // Already completed
                } else {
                    throw new \RuntimeException('PayPal order capture failed: ' . $captureResponse->body());
                }
            }

            $user = User::find($intent->user_id);
            if (!$user) {
                throw new \RuntimeException('User not found.');
            }

            DB::transaction(function () use ($intent, $user) {
                $currentIntent = DB::table('payment_intents')->where('id', $intent->id)->lockForUpdate()->first();
                if ($currentIntent->status === 'paid') {
                    return;
                }

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
                    'source' => 'paypal',
                    'note' => 'PayPal order ' . $intent->external_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('payment_intents')->where('id', $intent->id)->update([
                    'status' => 'paid',
                    'updated_at' => now(),
                ]);
            });

            return redirect('/')->with('success', 'Deposit of ' . $intent->amount . ' credited successfully.');

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PayPal Capture Error: ' . $e->getMessage());
            return redirect('/')->with('error', 'PayPal validation error: ' . $e->getMessage());
        }
    }

    public function showManualPayment(Request $request, $intentId)
    {
        $intent = DB::table('payment_intents')->where('id', $intentId)->first();
        if (!$intent) {
            abort(404, 'Payment intent not found.');
        }

        if ($intent->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        $instructions = settings('payment_manual_instructions', config('payments.drivers.manual.instructions'));

        return view('frontend.Minimal.payment.manual', compact('intent', 'instructions'));
    }

    public function submitManualDeposit(Request $request, $intentId)
    {
        $intent = DB::table('payment_intents')->where('id', $intentId)->first();
        if (!$intent) {
            abort(404, 'Payment intent not found.');
        }

        if ($intent->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        $exists = DB::table('manual_deposits')->where('payment_intent_id', $intentId)->exists();
        if ($exists) {
            return redirect()->back()->withErrors('You have already submitted a proof of payment for this transaction.');
        }

        $request->validate([
            'account_name' => 'required|string|max:255',
            'transaction_id' => 'required|string|max:255',
            'screenshot' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $file = $request->file('screenshot');
            $filename = 'manual_' . $intentId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/receipts'), $filename);
            $screenshotPath = 'uploads/receipts/' . $filename;
        }

        DB::table('manual_deposits')->insert([
            'user_id' => auth()->id(),
            'payment_intent_id' => $intentId,
            'account_name' => $request->input('account_name'),
            'transaction_id' => $request->input('transaction_id'),
            'screenshot' => $screenshotPath,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payment_intents')->where('id', $intentId)->update([
            'status' => 'submitted',
            'updated_at' => now(),
        ]);

        return redirect('/')->with('success', 'Your manual deposit proof has been submitted successfully and is pending admin approval.');
    }

    private function validateStripeSignature(string $payload, string $signature, string $secret): bool
    {
        if (!$secret || !$signature) {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        $pairs = explode(',', $signature);
        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                if (trim($parts[0]) === 't') {
                    $timestamp = trim($parts[1]);
                } elseif (trim($parts[0]) === 'v1') {
                    $signatures[] = trim($parts[1]);
                }
            }
        }

        if ($timestamp === null || empty($signatures)) {
            return false;
        }

        if (abs(time() - (int)$timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }

    private function resolveDriver(string $driver): ?PaymentDriverInterface
    {
        $driver = strtolower($driver);

        if ($driver === 'btcpay') {
            $cfg = [
                'enabled' => settings('payment_btcpay_enabled', config('payments.drivers.btcpay.enabled')),
                'host' => settings('payment_btcpay_host', config('payments.drivers.btcpay.host')),
                'store_id' => settings('payment_btcpay_store_id', config('payments.drivers.btcpay.store_id')),
                'api_key' => settings('payment_btcpay_api_key', config('payments.drivers.btcpay.api_key')),
                'webhook_secret' => settings('payment_btcpay_webhook_secret', config('payments.drivers.btcpay.webhook_secret')),
            ];
            if (!$cfg['enabled']) {
                return null;
            }
            return new BtcpayDriver($cfg);
        }

        if ($driver === 'stripe') {
            $cfg = [
                'enabled' => settings('payment_stripe_enabled', config('payments.drivers.stripe.enabled')),
                'secret_key' => settings('payment_stripe_secret_key', config('payments.drivers.stripe.secret_key')),
                'public_key' => settings('payment_stripe_public_key', config('payments.drivers.stripe.public_key')),
                'webhook_secret' => settings('payment_stripe_webhook_secret', config('payments.drivers.stripe.webhook_secret')),
            ];
            if (!$cfg['enabled']) {
                return null;
            }
            return new \VanguardLTE\Services\Payments\StripeDriver($cfg);
        }

        if ($driver === 'paypal') {
            $cfg = [
                'enabled' => settings('payment_paypal_enabled', config('payments.drivers.paypal.enabled')),
                'client_id' => settings('payment_paypal_client_id', config('payments.drivers.paypal.client_id')),
                'secret' => settings('payment_paypal_secret', config('payments.drivers.paypal.secret')),
                'mode' => settings('payment_paypal_mode', config('payments.drivers.paypal.mode', 'sandbox')),
            ];
            if (!$cfg['enabled']) {
                return null;
            }
            return new \VanguardLTE\Services\Payments\PaypalDriver($cfg);
        }

        if ($driver === 'manual') {
            $cfg = [
                'enabled' => settings('payment_manual_enabled', config('payments.drivers.manual.enabled')),
                'instructions' => settings('payment_manual_instructions', config('payments.drivers.manual.instructions')),
            ];
            if (!$cfg['enabled']) {
                return null;
            }
            return new \VanguardLTE\Services\Payments\ManualPaymentDriver($cfg);
        }

        return null;
    }
}
