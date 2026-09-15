<?php

namespace VanguardLTE\Services;

use VanguardLTE\User;
use VanguardLTE\VipClaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VipService
{
    public static $defaultTiers = [
        'Bronze' => [
            'name' => 'Bronze',
            'threshold' => 0,
            'rakeback' => 5.0, // 5% of house edge
            'level_bonus' => 0,
            'badge_color' => '#cd7f32',
            'perks' => ['5% Instant Rakeback', 'Standard Support', 'Daily Social Coin Refills'],
        ],
        'Silver' => [
            'name' => 'Silver',
            'threshold' => 10000,
            'rakeback' => 7.0, // 7% of house edge
            'level_bonus' => 25,
            'badge_color' => '#94a3b8',
            'perks' => ['7% Instant Rakeback', '$25 Level-Up Cash Bonus', 'Priority Email Support'],
        ],
        'Gold' => [
            'name' => 'Gold',
            'threshold' => 50000,
            'rakeback' => 10.0, // 10% of house edge
            'level_bonus' => 100,
            'badge_color' => '#f59e0b',
            'perks' => ['10% Instant Rakeback', '$100 Level-Up Cash Bonus', 'Weekly Cashback Boost', 'VIP Discord Role'],
        ],
        'Platinum' => [
            'name' => 'Platinum',
            'threshold' => 250000,
            'rakeback' => 12.0, // 12% of house edge
            'level_bonus' => 500,
            'badge_color' => '#06b6d4',
            'perks' => ['12% Instant Rakeback', '$500 Level-Up Cash Bonus', 'Daily Reload Boost', '24/7 Dedicated Support'],
        ],
        'Diamond' => [
            'name' => 'Diamond',
            'threshold' => 1000000,
            'rakeback' => 15.0, // 15% of house edge
            'level_bonus' => 2500,
            'badge_color' => '#a855f7',
            'perks' => ['15% Instant Rakeback', '$2,500 Level-Up Cash Bonus', 'Personal VIP Host', 'Bespoke High Roller Limits'],
        ],
        'Cedar Elite' => [
            'name' => 'Cedar Elite',
            'threshold' => 5000000,
            'rakeback' => 20.0, // 20% of house edge
            'level_bonus' => 10000,
            'badge_color' => '#10b981',
            'perks' => ['20% Instant Rakeback', '$10,000 Level-Up Cash Bonus', 'Executive Concierge Service', 'Luxury Physical Gifts & Experiences'],
        ],
    ];

    /**
     * Get all configured tiers with custom admin settings overrides
     */
    public static function getTiers(): array
    {
        $tiers = self::$defaultTiers;
        foreach ($tiers as $k => $t) {
            $slug = strtolower(str_replace(' ', '_', $k));
            if (function_exists('settings')) {
                $tiers[$k]['threshold'] = (int) settings("vip_{$slug}_threshold", $t['threshold']);
                $tiers[$k]['rakeback'] = (float) settings("vip_{$slug}_rakeback", $t['rakeback']);
                $tiers[$k]['level_bonus'] = (float) settings("vip_{$slug}_bonus", $t['level_bonus']);
            }
        }
        return $tiers;
    }

    /**
     * Record wager XP and accumulate rakeback on every bet across all games
     */
    public static function recordWagerXpAndRakeback($user, float $wagerAmount, float $houseEdge = 3.0): void
    {
        if ($wagerAmount <= 0) return;

        DB::transaction(function () use ($user, $wagerAmount, $houseEdge) {
            $u = User::whereKey(($user instanceof User) ? $user->id : (int) $user)->lockForUpdate()->first();
            if (!$u) return;

            $tiers = self::getTiers();

            // 1. Add XP (1 wager = 1 XP)
            $xpEarned = (float) $u->vip_xp_remainder + $wagerAmount;
            $wholeXp = (int) floor($xpEarned + 1e-8);
            $u->vip_xp_remainder = round($xpEarned - $wholeXp, 4);
            $newXp = $u->vip_xp + $wholeXp;
            $u->vip_xp = $newXp;

            // 2. Evaluate Tier Promotion
            $highestTier = 'Bronze';
            foreach ($tiers as $tierName => $tierData) {
                if ($newXp >= $tierData['threshold']) {
                    $highestTier = $tierName;
                }
            }

            if ($u->vip_level !== $highestTier) {
                Log::info("[VIP] User #{$u->id} promoted from {$u->vip_level} to {$highestTier}! (XP: {$newXp})");
                $u->vip_level = $highestTier;
            }

            // 3. Calculate Rakeback: Wager * (HouseEdge / 100) * (RakebackRate / 100)
            $currentTierData = $tiers[$u->vip_level] ?? $tiers['Bronze'];
            $rakebackRate = (float) ($currentTierData['rakeback'] ?? 5.0);
            $edgeRate = max(0.0, min(100.0, $houseEdge));

            $rakebackEarned = round($wagerAmount * ($edgeRate / 100.0) * ($rakebackRate / 100.0), 6);

            if ($rakebackEarned > 0) {
                $accrual = (float) $u->vip_rakeback_remainder + $rakebackEarned;
                $wholeCents = floor(($accrual + 1e-9) * 100) / 100;
                $u->vip_rakeback_remainder = round($accrual - $wholeCents, 6);
                $u->unclaimed_rakeback = round((float) $u->unclaimed_rakeback + $wholeCents, 2);
            }

            $u->save();
        }, 5);
    }

    /**
     * 1-Click claim unclaimed accumulated rakeback to playable cash balance
     */
    public static function claimRakeback(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            $claimable = (float) $lockedUser->unclaimed_rakeback;

            if ($claimable <= 0) {
                return ['success' => false, 'message' => 'No unclaimed rakeback available. Keep playing to earn!'];
            }

            $lockedUser->balance += $claimable;
            $lockedUser->total_rakeback_claimed += $claimable;
            $lockedUser->unclaimed_rakeback = 0.00;
            $lockedUser->save();

            VipClaim::create([
                'user_id' => $lockedUser->id,
                'type' => 'rakeback',
                'tier' => $lockedUser->vip_level,
                'amount' => $claimable,
            ]);

            Log::info("[VIP] User #{$lockedUser->id} claimed {$claimable} rakeback. New balance: {$lockedUser->balance}");

            return [
                'success' => true,
                'amount' => $claimable,
                'balance' => $lockedUser->balance,
                'message' => 'Successfully claimed $' . number_format($claimable, 2) . ' in VIP rakeback to your cash balance!',
            ];
        });
    }

    /**
     * Claim one-time Level-Up Cash Bonus for reaching a tier
     */
    public static function claimLevelBonus(User $user, string $tierName): array
    {
        $tiers = self::getTiers();
        if (!isset($tiers[$tierName])) {
            return ['success' => false, 'message' => 'Invalid VIP tier!'];
        }

        $tierData = $tiers[$tierName];
        $bonusAmount = (float) $tierData['level_bonus'];

        if ($bonusAmount <= 0) {
            return ['success' => false, 'message' => 'This tier does not have a level-up cash bonus.'];
        }

        return DB::transaction(function () use ($user, $tierName, $tierData, $bonusAmount) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            // Check if user has enough XP for this tier
            if ($lockedUser->vip_xp < $tierData['threshold']) {
                return ['success' => false, 'message' => "You have not yet reached the {$tierName} tier threshold!"];
            }

            // Check claimed list
            $claimed = json_decode($lockedUser->claimed_level_bonuses ?? '[]', true) ?: [];
            if (in_array($tierName, $claimed)) {
                return ['success' => false, 'message' => "You have already claimed the {$tierName} Level-Up Bonus!"];
            }

            // Award cash bonus
            $lockedUser->balance += $bonusAmount;
            $claimed[] = $tierName;
            $lockedUser->claimed_level_bonuses = json_encode($claimed);
            $lockedUser->save();

            VipClaim::create([
                'user_id' => $lockedUser->id,
                'type' => 'level_up',
                'tier' => $tierName,
                'amount' => $bonusAmount,
            ]);

            Log::info("[VIP] User #{$lockedUser->id} claimed {$tierName} Level-Up Bonus of {$bonusAmount} coins!");

            return [
                'success' => true,
                'amount' => $bonusAmount,
                'balance' => $lockedUser->balance,
                'tier' => $tierName,
                'message' => "Congratulations! Claimed \${$bonusAmount} {$tierName} Level-Up Cash Bonus!",
            ];
        });
    }

    /**
     * Get VIP profile, progress to next tier, and benefits for user
     */
    public function getVipProfile(User $user): array
    {
        $tiers = self::getTiers();
        $tierKeys = array_keys($tiers);
        $currentTierName = $user->vip_level ?: 'Bronze';
        if (!isset($tiers[$currentTierName])) $currentTierName = 'Bronze';

        $currentTier = $tiers[$currentTierName];
        $currentTierIdx = array_search($currentTierName, $tierKeys);

        $nextTier = null;
        $progressPct = 100.0;
        $xpToNext = 0;

        if ($currentTierIdx < count($tierKeys) - 1) {
            $nextTierName = $tierKeys[$currentTierIdx + 1];
            $nextTier = $tiers[$nextTierName];

            $tierStart = $currentTier['threshold'];
            $tierEnd = $nextTier['threshold'];
            $range = max(1, $tierEnd - $tierStart);
            $userProgress = max(0, $user->vip_xp - $tierStart);

            $progressPct = min(100.0, round(($userProgress / $range) * 100.0, 1));
            $xpToNext = max(0, $tierEnd - $user->vip_xp);
        }

        $claimedBonuses = json_decode($user->claimed_level_bonuses ?? '[]', true) ?: [];

        $recentClaims = VipClaim::where('user_id', $user->id)
            ->latest('id')
            ->limit(15)
            ->get();

        return [
            'vip_level' => $currentTierName,
            'vip_xp' => (int) $user->vip_xp,
            'unclaimed_rakeback' => (float) $user->unclaimed_rakeback,
            'total_rakeback_claimed' => (float) $user->total_rakeback_claimed,
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'progress_pct' => $progressPct,
            'xp_to_next' => $xpToNext,
            'all_tiers' => $tiers,
            'claimed_bonuses' => $claimedBonuses,
            'recent_claims' => $recentClaims,
        ];
    }
}
