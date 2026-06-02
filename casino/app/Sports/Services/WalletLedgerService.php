<?php

namespace VanguardLTE\Sports\Services;

use Illuminate\Support\Facades\DB;
use VanguardLTE\User;

class WalletLedgerService
{
    /**
     * Deduct funds from user balance (e.g. placing a bet).
     */
    public function debit(User $user, float $amount, string $note, string $source = 'sports_bet_stake', ?int $sportsBetId = null): bool
    {
        return DB::transaction(function () use ($user, $amount, $note, $source, $sportsBetId) {
            $dbUser = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if (!$dbUser) {
                throw new \Exception("User not found");
            }

            if ($dbUser->balance < $amount) {
                throw new \Exception("Insufficient balance");
            }

            $balanceBefore = (float) $dbUser->balance;
            $newBalance = max(0.0, $balanceBefore - $amount);

            DB::table('users')->where('id', $user->id)->update([
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

            $user->balance = $newBalance;

            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'admin_id' => null,
                'direction' => 'deduct',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'source' => $source,
                'note' => $note . ($sportsBetId ? " (Bet ID: #{$sportsBetId})" : ""),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Credit funds to user balance (e.g. winning or refund).
     */
    public function credit(User $user, float $amount, string $note, string $source = 'sports_bet_settle', ?int $sportsBetId = null): bool
    {
        return DB::transaction(function () use ($user, $amount, $note, $source, $sportsBetId) {
            $dbUser = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if (!$dbUser) {
                throw new \Exception("User not found");
            }

            $balanceBefore = (float) $dbUser->balance;
            $newBalance = $balanceBefore + $amount;
            $newCountBalance = (float) $dbUser->count_balance + $amount;

            DB::table('users')->where('id', $user->id)->update([
                'balance' => $newBalance,
                'count_balance' => $newCountBalance,
                'updated_at' => now(),
            ]);

            $user->balance = $newBalance;
            $user->count_balance = $newCountBalance;

            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'admin_id' => null,
                'direction' => 'add',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'source' => $source,
                'note' => $note . ($sportsBetId ? " (Bet ID: #{$sportsBetId})" : ""),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });
    }
}
