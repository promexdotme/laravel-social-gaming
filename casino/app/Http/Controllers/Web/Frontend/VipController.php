<?php

namespace VanguardLTE\Http\Controllers\Web\Frontend;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VanguardLTE\Services\VipService;

class VipController extends Controller
{
    protected $vipService;

    public function __construct(VipService $vipService)
    {
        $this->vipService = $vipService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $vipProfile = null;
        if ($user) {
            $vipProfile = $this->vipService->getVipProfile($user);
        } else {
            $tiers = VipService::getTiers();
            $vipProfile = [
                'vip_level' => 'Bronze',
                'vip_xp' => 0,
                'unclaimed_rakeback' => 0.00,
                'total_rakeback_claimed' => 0.00,
                'current_tier' => $tiers['Bronze'],
                'next_tier' => $tiers['Silver'],
                'progress_pct' => 0.0,
                'xp_to_next' => 10000,
                'all_tiers' => $tiers,
                'claimed_bonuses' => [],
                'recent_claims' => [],
            ];
        }

        return view('frontend.Minimal.vip.index', compact('user', 'vipProfile'));
    }

    public function claimRakeback(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please log in to claim VIP rakeback!'], 401);
        }

        $result = VipService::claimRakeback($user);
        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    public function claimLevelBonus(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please log in to claim Level-Up bonuses!'], 401);
        }

        $tier = (string) $request->input('tier');
        $result = VipService::claimLevelBonus($user, $tier);
        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }
}
