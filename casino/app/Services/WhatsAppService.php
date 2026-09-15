<?php

namespace VanguardLTE\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Format phone number to E.164 standard
     */
    public static function formatE164(string $phone, string $defaultCountryCode = '961'): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);

        if (empty($digits)) {
            return '';
        }

        // If phone already starts with +, remove + and format
        if (strpos($phone, '+') === 0) {
            return '+' . $digits;
        }

        // If local number without country code (e.g. 70123456 in Lebanon)
        if (strlen($digits) <= 8) {
            return '+' . $defaultCountryCode . $digits;
        }

        return '+' . $digits;
    }

    /**
     * Generate a 6-digit random OTP code
     */
    public static function generateOtp(): string
    {
        return sprintf('%06d', mt_rand(100000, 999999));
    }

    /**
     * Send OTP via WhatsApp Cloud API or Log Mode
     */
    public function sendOtp(string $phoneE164, string $otp): bool
    {
        $mode = env('WHATSAPP_MODE', 'devmode'); // 'devmode', 'cloud', 'webhook', 'log'

        Log::info("[WhatsApp OTP] Sending OTP {$otp} to {$phoneE164} (Mode: {$mode})");

        if ($mode === 'devmode' || $mode === 'log') {
            // Dev testing mode - OTP logged cleanly without API charges
            return true;
        }

        if ($mode === 'cloud') {
            $token = env('WHATSAPP_CLOUD_TOKEN');
            $phoneId = env('WHATSAPP_PHONE_NUMBER_ID');

            if (!$token || !$phoneId) {
                Log::error("[WhatsApp OTP] Missing Meta Cloud API token or Phone Number ID in .env");
                return false;
            }

            $url = "https://graph.facebook.com/v18.0/{$phoneId}/messages";

            $response = Http::withToken($token)->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => str_replace('+', '', $phoneE164),
                'type' => 'template',
                'template' => [
                    'name' => env('WHATSAPP_TEMPLATE_NAME', 'verification_code'),
                    'language' => ['code' => 'en_US'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $otp]
                            ]
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => '0',
                            'parameters' => [
                                ['type' => 'text', 'text' => $otp]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                Log::info("[WhatsApp OTP] Meta Cloud API Success: " . $response->body());
                return true;
            } else {
                Log::error("[WhatsApp OTP] Meta Cloud API Error: " . $response->body());
                return false;
            }
        }

        return true;
    }
}
