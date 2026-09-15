<?php

namespace VanguardLTE\Http\Controllers\Web\Frontend;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VanguardLTE\User;
use VanguardLTE\Services\AffiliateService;

class AffiliateController extends Controller
{
    protected $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Display Affiliate & Referral Portal
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // If guest, provide demo context or prompt login
        $stats = null;
        if ($user) {
            $stats = $this->affiliateService->getReferralStats($user);
        }

        return view('frontend.Minimal.affiliates.index', compact('user', 'stats'));
    }

    /**
     * 1-Click Claim Unclaimed Commissions to Playable Balance
     */
    public function claim(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to claim affiliate commissions!',
            ], 401);
        }

        $result = AffiliateService::claimCommissions($user);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }
}
