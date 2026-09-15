<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use VanguardLTE\Http\Controllers\Controller;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $cedarRounds = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('cedar_rounds')) {
                $cedarRounds = \Illuminate\Support\Facades\DB::table('cedar_rounds')
                    ->orderBy('id', 'desc')
                    ->limit(10)
                    ->get();
            }
        } catch (\Throwable $e) {
            $cedarRounds = [];
        }

        return view('liteback.settings.index', compact('cedarRounds'));
    }

    public function update(Request $request)
    {
        $request->validate([
            // Module toggles
            'enable_casino_slots' => 'required|in:0,1',
            'enable_cedar_originals' => 'required|in:0,1',
            'enable_sportsbook' => 'required|in:0,1',
            'enable_lotto' => 'required|in:0,1',
            'enable_predictions' => 'required|in:0,1',
            'enable_whatsapp_otp' => 'required|in:0,1',
            'enable_refill_coins' => 'required|in:0,1',

            // API keys
            'odds_api_key' => 'nullable|string|max:255',
            'odds_api_region' => 'nullable|string|in:eu,us,uk,au',
            'whatsapp_api_token' => 'nullable|string|max:500',
            'whatsapp_api_endpoint' => 'nullable|string|max:255',
            'polymarket_api_url' => 'nullable|string|max:255',

            // Economy / Coin Defaults & Cashout Controls
            'default_refill_amount' => 'nullable|numeric|min:100|max:1000000',
            'default_starting_coins' => 'nullable|numeric|min:0|max:1000000',
            'enable_cashout' => 'required|in:0,1',
            'coins_per_dollar' => 'nullable|numeric|min:1|max:100000',
            'min_cashout_coins' => 'nullable|numeric|min:1|max:10000000',
            'cashout_methods' => 'nullable|string|max:1000',

            // CEDAR Originals Settings
            'cedar_crash_house_edge' => 'nullable|numeric|min:0.5|max:20',
            'cedar_crash_min_bet' => 'nullable|numeric|min:1|max:100000',
            'cedar_crash_max_bet' => 'nullable|numeric|min:10|max:1000000',
            'cedar_crash_max_multiplier' => 'nullable|numeric|min:2|max:10000',
            'cedar_plinko_min_bet' => 'nullable|numeric|min:1|max:100000',
            'cedar_plinko_max_bet' => 'nullable|numeric|min:10|max:1000000',
            'cedar_mines_house_edge' => 'nullable|numeric|min:0.5|max:20',
            'cedar_mines_min_bet' => 'nullable|numeric|min:1|max:100000',
            'cedar_mines_max_bet' => 'nullable|numeric|min:10|max:1000000',
            'cedar_dice_house_edge' => 'nullable|numeric|min:0.1|max:10',
            'cedar_dice_min_bet' => 'nullable|numeric|min:1|max:100000',
            'cedar_dice_max_bet' => 'nullable|numeric|min:10|max:1000000',
            'cedar_wheel_min_bet' => 'nullable|numeric|min:1|max:100000',
            'cedar_wheel_max_bet' => 'nullable|numeric|min:10|max:1000000',
        ]);

        $keys = [
            'enable_casino_slots',
            'enable_cedar_originals',
            'enable_sportsbook',
            'enable_lotto',
            'enable_predictions',
            'enable_whatsapp_otp',
            'enable_refill_coins',
            'odds_api_key',
            'odds_api_region',
            'whatsapp_api_token',
            'whatsapp_api_endpoint',
            'polymarket_api_url',
            'default_refill_amount',
            'default_starting_coins',
            'enable_cashout',
            'coins_per_dollar',
            'min_cashout_coins',
            'cashout_methods',
            'cedar_crash_house_edge',
            'cedar_crash_min_bet',
            'cedar_crash_max_bet',
            'cedar_crash_max_multiplier',
            'cedar_plinko_min_bet',
            'cedar_plinko_max_bet',
            'cedar_mines_house_edge',
            'cedar_mines_min_bet',
            'cedar_mines_max_bet',
            'cedar_dice_house_edge',
            'cedar_dice_min_bet',
            'cedar_dice_max_bet',
            'cedar_wheel_min_bet',
            'cedar_wheel_max_bet',
        ];

        foreach ($keys as $k) {
            settings()->set($k, $request->input($k, ''));
        }
        settings()->save();

        return redirect()->back()->with('success', 'System settings and module controls updated successfully.');
    }

    /**
     * Manual Trigger: Flush all framework caches
     */
    public function clearCache()
    {
        try {
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            return redirect()->back()->with('success', 'All system caches (Views, Application Cache, Routes) cleared successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors('Failed to clear caches: ' . $e->getMessage());
        }
    }

    /**
     * Manual Trigger: Test Odds API key connectivity
     */
    public function testOddsApi(Request $request)
    {
        $apiKey = $request->input('api_key') ?: settings('odds_api_key');

        if (empty($apiKey)) {
            return response()->json(['success' => false, 'message' => 'No Odds API key provided.']);
        }

        try {
            $resp = Http::timeout(6)->get("https://api.the-odds-api.com/v4/sports/?apiKey={$apiKey}");

            if ($resp->successful()) {
                $sports = $resp->json();
                $count = is_array($sports) ? count($sports) : 0;
                return response()->json([
                    'success' => true,
                    'message' => "API Key is VALID! Connected successfully to The Odds API ({$count} active sports available).",
                    'data' => array_slice($sports, 0, 5)
                ]);
            }

            $body = $resp->json();
            $msg = $body['message'] ?? 'Status ' . $resp->status();
            return response()->json(['success' => false, 'message' => "API Error from Odds Provider: {$msg}"]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Manual Trigger: Test Polymarket Gamma API connectivity
     */
    public function testPolymarketApi(Request $request)
    {
        $url = $request->input('api_url') ?: settings('polymarket_api_url', 'https://gamma-api.polymarket.com/events');

        try {
            $resp = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'CasinoDuLiban/SocialGaming'])
                ->get($url, [
                    'limit' => 5,
                    'active' => 'true',
                    'closed' => 'false'
                ]);

            if ($resp->successful()) {
                $events = $resp->json();
                $count = is_array($events) ? count($events) : 0;
                $sample = (!empty($events) && isset($events[0]['title'])) ? '"' . $events[0]['title'] . '"' : 'Active Events';
                return response()->json([
                    'success' => true,
                    'message' => "Polymarket Gamma API is ONLINE! Successfully fetched {$count} live markets. (e.g. {$sample})",
                    'data' => array_slice($events, 0, 5)
                ]);
            }

            return response()->json(['success' => false, 'message' => "API returned HTTP status " . $resp->status()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Manual Trigger: Ingest sports odds immediately
     */
    public function syncOddsNow()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('sports:sync-odds');
            $output = trim(\Illuminate\Support\Facades\Artisan::output());
            return redirect()->back()->with('success', $output ?: 'Sports odds synced successfully!');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors('Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Manual Trigger: Run match settlement immediately
     */
    public function runSettlementNow()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('sports:settle-matches');
            $output = trim(\Illuminate\Support\Facades\Artisan::output());
            return redirect()->back()->with('success', $output ?: 'Sports wagers settlement completed!');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors('Settlement failed: ' . $e->getMessage());
        }
    }

    /**
     * Manual Trigger: Execute Lotto draw immediately
     */
    public function drawLottoNow()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('casino:draw-lotto');
            $output = trim(\Illuminate\Support\Facades\Artisan::output());
            return redirect()->back()->with('success', $output ?: 'Lotto draw executed successfully!');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors('Lotto draw failed: ' . $e->getMessage());
        }
    }
}
