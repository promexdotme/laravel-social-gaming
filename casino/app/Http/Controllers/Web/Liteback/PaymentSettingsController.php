<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use VanguardLTE\Http\Controllers\Controller;

class PaymentSettingsController extends Controller
{
    public function index()
    {
        return view('liteback.payments.settings');
    }

    public function update(Request $request)
    {
        $request->validate([
            // Stripe settings
            'payment_stripe_enabled' => 'required|in:0,1',
            'payment_stripe_public_key' => 'nullable|string|max:255',
            'payment_stripe_secret_key' => 'nullable|string|max:255',
            'payment_stripe_webhook_secret' => 'nullable|string|max:255',

            // PayPal settings
            'payment_paypal_enabled' => 'required|in:0,1',
            'payment_paypal_client_id' => 'nullable|string|max:255',
            'payment_paypal_secret' => 'nullable|string|max:255',
            'payment_paypal_mode' => 'required|in:sandbox,live',

            // BTCPay settings
            'payment_btcpay_enabled' => 'required|in:0,1',
            'payment_btcpay_host' => 'nullable|string|max:255',
            'payment_btcpay_store_id' => 'nullable|string|max:255',
            'payment_btcpay_api_key' => 'nullable|string|max:255',
            'payment_btcpay_webhook_secret' => 'nullable|string|max:255',

            // Manual payment settings
            'payment_manual_enabled' => 'required|in:0,1',
            'payment_manual_instructions' => 'nullable|string|max:2000',
        ]);

        $settings = $request->only([
            'payment_stripe_enabled',
            'payment_stripe_public_key',
            'payment_stripe_secret_key',
            'payment_stripe_webhook_secret',
            'payment_paypal_enabled',
            'payment_paypal_client_id',
            'payment_paypal_secret',
            'payment_paypal_mode',
            'payment_btcpay_enabled',
            'payment_btcpay_host',
            'payment_btcpay_store_id',
            'payment_btcpay_api_key',
            'payment_btcpay_webhook_secret',
            'payment_manual_enabled',
            'payment_manual_instructions',
        ]);

        foreach ($settings as $key => $val) {
            settings()->set($key, $val);
        }
        settings()->save();

        return redirect()->back()->with('success', 'Payment gateway settings updated successfully.');
    }
}
