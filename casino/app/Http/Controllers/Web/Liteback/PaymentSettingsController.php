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

            // XtoPay settings
            'payment_xto_enabled' => 'required|in:0,1',
            'payment_xto_website_name' => 'nullable|string|max:255',
            'payment_xto_token' => 'nullable|string|max:1000',
            'payment_xto_methods' => 'nullable|string|max:1000',
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
            'payment_xto_enabled',
            'payment_xto_website_name',
            'payment_xto_token',
            'payment_xto_methods',
        ]);

        foreach ($settings as $key => $val) {
            settings()->set($key, $val);
        }

        // Save token to .env for security isolation
        $this->updateEnvFile([
            'XTO_PAY_TOKEN' => $request->input('payment_xto_token', ''),
        ]);

        // Keep DB record clean
        settings()->set('payment_xto_token', '');
        settings()->save();

        return redirect()->back()->with('success', 'Payment gateway settings updated successfully.');
    }

    protected function updateEnvFile(array $data): bool
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);
        foreach ($data as $key => $value) {
            $escapedValue = $value;
            if (preg_match('/\s/m', $value)) {
                $escapedValue = '"' . str_replace('"', '\\"', $value) . '"';
            }

            $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
            $line = $key . '=' . $escapedValue;
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content = rtrim($content) . "\n" . $line . "\n";
            }
        }

        return file_put_contents($path, $content) !== false;
    }
}
