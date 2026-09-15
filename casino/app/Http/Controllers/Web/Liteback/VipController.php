<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VanguardLTE\User;
use VanguardLTE\VipClaim;
use VanguardLTE\Services\VipService;

class VipController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Global VIP Metrics
        $totalRakebackPaid = (float) User::sum('total_rakeback_claimed');
        $totalUnclaimedRakeback = (float) User::sum('unclaimed_rakeback');
        $totalBonusesPaid = (float) VipClaim::where('type', 'level_up')->sum('amount');
        $totalCirculatingXp = (int) User::sum('vip_xp');

        $tiers = VipService::getTiers();

        // Tier distribution counts
        $tierDistribution = [];
        foreach ($tiers as $tierName => $data) {
            $tierDistribution[$tierName] = User::where('vip_level', $tierName)->count();
        }

        // High Rollers query
        $usersQuery = User::where('vip_xp', '>', 0)
            ->orWhere('unclaimed_rakeback', '>', 0)
            ->orWhere('total_rakeback_claimed', '>', 0);

        if (!empty($search)) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('vip_level', 'like', "%{$search}%");
            });
        }

        $vipUsers = $usersQuery->orderBy('vip_xp', 'desc')->paginate(15);

        // Recent claims activity
        $recentClaims = VipClaim::with('user:id,username')
            ->latest('id')
            ->paginate(20, ['*'], 'claims_page');

        return view('liteback.vip.index', compact(
            'totalRakebackPaid',
            'totalUnclaimedRakeback',
            'totalBonusesPaid',
            'totalCirculatingXp',
            'tiers',
            'tierDistribution',
            'vipUsers',
            'recentClaims',
            'search'
        ));
    }

    public function updateSettings(Request $request)
    {
        $tiers = VipService::$defaultTiers;

        foreach ($tiers as $name => $t) {
            $slug = strtolower(str_replace(' ', '_', $name));
            if ($request->has("vip_{$slug}_threshold")) {
                settings()->set("vip_{$slug}_threshold", (int) $request->input("vip_{$slug}_threshold"));
            }
            if ($request->has("vip_{$slug}_rakeback")) {
                settings()->set("vip_{$slug}_rakeback", (float) $request->input("vip_{$slug}_rakeback"));
            }
            if ($request->has("vip_{$slug}_bonus")) {
                settings()->set("vip_{$slug}_bonus", (float) $request->input("vip_{$slug}_bonus"));
            }
        }
        settings()->save();

        return redirect()->back()->with('success', 'VIP Club loyalty tier configurations updated successfully!');
    }

    public function setUserTier(Request $request, $user)
    {
        $u = ($user instanceof User) ? $user : User::findOrFail((int) $user);
        $newTier = (string) $request->input('vip_level');

        $tiers = VipService::getTiers();
        if (!isset($tiers[$newTier])) {
            return redirect()->back()->withErrors('Invalid VIP Tier selected!');
        }

        $u->vip_level = $newTier;
        // Optionally set minimum threshold XP if user is below it
        if ($u->vip_xp < $tiers[$newTier]['threshold']) {
            $u->vip_xp = $tiers[$newTier]['threshold'];
        }
        $u->save();

        return redirect()->back()->with('success', "User #{$u->id} ({$u->username}) successfully promoted to {$newTier}!");
    }
}
