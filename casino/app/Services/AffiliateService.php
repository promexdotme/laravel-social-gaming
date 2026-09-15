<?php

namespace VanguardLTE\Services;

use VanguardLTE\User;
use VanguardLTE\AffiliateCommission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffiliateService
{
    /**
     * Generate or retrieve a unique 8-character invite code for a user
     */
    public static function generateInviteCode(User $user): string
    {
        if (!empty($user->invite_code)) {
            return $user->invite_code;
        }

        $code = strtoupper(Str::random(8));
        while (User::where('invite_code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }

        $user->invite_code = $code;
        $user->save();

        return $code;
    }

    /**
     * Optional signup referral reward handler
     */
    public function processReferralRewards(User $user): void
    {
        // Reserved for instant signup bonuses for uplines or referees if configured
    }

    /**
     * Record 3-tier affiliate commissions for a wager across any vertical
     *
     * @param int|User $user
     * @param float $wagerAmount
     * @param string $gameType ('crash', 'plinko', 'mines', 'dice', 'wheel', 'sportsbook', 'lotto', 'predictions', 'slots', 'table')
     */
    public static function recordWagerCommission($user, float $wagerAmount, string $gameType = 'original', bool $strict = false): void
    {
        if ($wagerAmount <= 0) {
            return;
        }

        $userId = ($user instanceof User) ? $user->id : (int) $user;
        $currentUser = ($user instanceof User) ? $user : User::find($userId);

        if (!$currentUser || empty($currentUser->parent_id) || $currentUser->parent_id <= 0) {
            return;
        }

        // Tier rates (Default: Tier 1 = 0.5%, Tier 2 = 0.3%, Tier 3 = 0.2% -> Total 1.0%)
        $rates = [
            1 => (float) (function_exists('settings') ? settings('affiliate_tier1_rate', 0.0050) : 0.0050),
            2 => (float) (function_exists('settings') ? settings('affiliate_tier2_rate', 0.0030) : 0.0030),
            3 => (float) (function_exists('settings') ? settings('affiliate_tier3_rate', 0.0020) : 0.0020),
        ];

        // 3-Tier cascade lookup
        $tierUplines = [];
        $currentParentId = $currentUser->parent_id;
        $visited = [$userId];

        for ($tier = 1; $tier <= 3; $tier++) {
            if (!$currentParentId || $currentParentId <= 0) {
                break;
            }
            $parent = User::find($currentParentId);
            if (!$parent || in_array((int) $parent->id, $visited, true)) {
                break;
            }
            $visited[] = (int) $parent->id;
            $tierUplines[$tier] = $parent;
            $currentParentId = $parent->parent_id;
        }

        if (empty($tierUplines)) {
            return;
        }

        DB::beginTransaction();
        try {
            // Lock recipient rows before inserting commission records, matching claim lock order.
            User::whereIn('id', array_map(fn ($u) => $u->id, $tierUplines))->orderBy('id')->lockForUpdate()->get();
            foreach ($tierUplines as $tier => $uplineUser) {
                $rate = $rates[$tier] ?? 0.0010;
                $commission = round($wagerAmount * $rate, 4);

                if ($commission > 0) {
                    AffiliateCommission::create([
                        'affiliate_id' => $uplineUser->id,
                        'referred_user_id' => $userId,
                        'tier' => $tier,
                        'game_type' => $gameType,
                        'wager_amount' => $wagerAmount,
                        'commission_rate' => $rate,
                        'commission_amount' => $commission,
                        'status' => 'pending',
                    ]);

                    // Increment unclaimed commissions on upline user
                    $uplineUser->increment('unclaimed_commissions', $commission);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($strict) throw $e;
            Log::error("[Affiliate] Error recording wager commission: " . $e->getMessage());
        }
    }

    /**
     * 1-Click claim unclaimed affiliate commissions to playable cash balance
     */
    public static function claimCommissions(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            $claimable = (float) $lockedUser->unclaimed_commissions;

            if ($claimable <= 0) {
                return [
                    'success' => false,
                    'message' => 'No unclaimed commissions available.',
                ];
            }

            // Transfer to balance
            $lockedUser->balance += $claimable;
            $lockedUser->total_affiliate_earnings += $claimable;
            $lockedUser->unclaimed_commissions = 0.00;
            $lockedUser->save();

            // Mark pending commissions as claimed
            AffiliateCommission::where('affiliate_id', $lockedUser->id)
                ->where('status', 'pending')
                ->update(['status' => 'claimed']);

            Log::info("[Affiliate] User #{$lockedUser->id} claimed {$claimable} coins in affiliate commissions. New balance: {$lockedUser->balance}");

            return [
                'success' => true,
                'amount' => $claimable,
                'balance' => $lockedUser->balance,
                'message' => 'Successfully claimed $' . number_format($claimable, 2) . ' to your playable balance!',
            ];
        });
    }

    /**
     * Get detailed referral statistics, tier breakdowns, and recent logs
     */
    public function getReferralStats(User $user): array
    {
        $inviteCode = self::generateInviteCode($user);
        $referralLink = url('/?ref=' . $inviteCode);

        // Tier 1: Direct recruits
        $tier1Ids = User::where('parent_id', $user->id)->pluck('id')->toArray();
        $tier1Count = count($tier1Ids);

        // Tier 2: Sub recruits
        $tier2Ids = !empty($tier1Ids) ? User::whereIn('parent_id', $tier1Ids)->pluck('id')->toArray() : [];
        $tier2Count = count($tier2Ids);

        // Tier 3: Grand-sub recruits
        $tier3Ids = !empty($tier2Ids) ? User::whereIn('parent_id', $tier2Ids)->pluck('id')->toArray() : [];
        $tier3Count = count($tier3Ids);

        // Commission totals by tier
        $tier1Earnings = (float) AffiliateCommission::where('affiliate_id', $user->id)->where('tier', 1)->sum('commission_amount');
        $tier2Earnings = (float) AffiliateCommission::where('affiliate_id', $user->id)->where('tier', 2)->sum('commission_amount');
        $tier3Earnings = (float) AffiliateCommission::where('affiliate_id', $user->id)->where('tier', 3)->sum('commission_amount');

        // Total wager volume generated by all downlines
        $totalDownlineWagers = (float) AffiliateCommission::where('affiliate_id', $user->id)->sum('wager_amount');

        // Recent 15 commission transactions
        $recentCommissions = AffiliateCommission::with('referredUser:id,username')
            ->where('affiliate_id', $user->id)
            ->latest('id')
            ->limit(15)
            ->get();

        return [
            'invite_code' => $inviteCode,
            'referral_link' => $referralLink,
            'unclaimed_commissions' => (float) $user->unclaimed_commissions,
            'total_affiliate_earnings' => (float) $user->total_affiliate_earnings,
            'total_referrals' => $tier1Count + $tier2Count + $tier3Count,
            'total_volume' => $totalDownlineWagers,
            'tier1_count' => $tier1Count,
            'tier2_count' => $tier2Count,
            'tier3_count' => $tier3Count,
            'tier1' => [
                'count' => $tier1Count,
                'earnings' => $tier1Earnings,
                'rate' => (float) (function_exists('settings') ? settings('affiliate_tier1_rate', 0.0050) : 0.0050) * 100,
            ],
            'tier2' => [
                'count' => $tier2Count,
                'earnings' => $tier2Earnings,
                'rate' => (float) (function_exists('settings') ? settings('affiliate_tier2_rate', 0.0030) : 0.0030) * 100,
            ],
            'tier3' => [
                'count' => $tier3Count,
                'earnings' => $tier3Earnings,
                'rate' => (float) (function_exists('settings') ? settings('affiliate_tier3_rate', 0.0020) : 0.0020) * 100,
            ],
            'recent_commissions' => $recentCommissions,
        ];
    }
}
