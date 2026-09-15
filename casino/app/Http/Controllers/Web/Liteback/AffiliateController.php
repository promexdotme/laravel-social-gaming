<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VanguardLTE\User;
use VanguardLTE\AffiliateCommission;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Global Statistics
        $totalPaidOut = (float) User::sum('total_affiliate_earnings');
        $totalUnclaimed = (float) User::sum('unclaimed_commissions');
        $totalCommissionsCount = AffiliateCommission::count();
        $totalVolume = (float) AffiliateCommission::sum('wager_amount');

        // Rates
        $rates = [
            'tier1' => (float) (function_exists('settings') ? settings('affiliate_tier1_rate', 0.0050) : 0.0050) * 100,
            'tier2' => (float) (function_exists('settings') ? settings('affiliate_tier2_rate', 0.0030) : 0.0030) * 100,
            'tier3' => (float) (function_exists('settings') ? settings('affiliate_tier3_rate', 0.0020) : 0.0020) * 100,
        ];

        // Top Affiliates
        $topAffiliatesQuery = User::where(function ($q) {
            $q->where('total_affiliate_earnings', '>', 0)
              ->orWhere('count_invite', '>', 0)
              ->orWhere('unclaimed_commissions', '>', 0);
        });

        if (!empty($search)) {
            $topAffiliatesQuery->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('invite_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $topAffiliates = $topAffiliatesQuery->orderBy('total_affiliate_earnings', 'desc')
            ->paginate(15);

        // Recent Commissions Ledger
        $recentCommissions = AffiliateCommission::with(['affiliate:id,username', 'referredUser:id,username'])
            ->latest('id')
            ->paginate(20, ['*'], 'logs_page');

        return view('liteback.affiliates.index', compact(
            'totalPaidOut',
            'totalUnclaimed',
            'totalCommissionsCount',
            'totalVolume',
            'rates',
            'topAffiliates',
            'recentCommissions',
            'search'
        ));
    }

    public function updateRates(Request $request)
    {
        $request->validate([
            'affiliate_tier1_rate' => 'required|numeric|min:0|max:10',
            'affiliate_tier2_rate' => 'required|numeric|min:0|max:10',
            'affiliate_tier3_rate' => 'required|numeric|min:0|max:10',
        ]);

        // Rates saved as decimals (e.g. 0.5% => 0.0050)
        settings()->set('affiliate_tier1_rate', (float)$request->input('affiliate_tier1_rate') / 100);
        settings()->set('affiliate_tier2_rate', (float)$request->input('affiliate_tier2_rate') / 100);
        settings()->set('affiliate_tier3_rate', (float)$request->input('affiliate_tier3_rate') / 100);
        settings()->save();

        return redirect()->back()->with('success', 'Affiliate tier commission rates updated successfully!');
    }
}
