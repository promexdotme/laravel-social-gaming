<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Withdraw;
use VanguardLTE\User;

class WithdrawalController extends Controller
{
    /**
     * Display a listing of all player cashout / withdrawal requests
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'pending');
        $search = $request->input('search', '');

        $query = Withdraw::with('user')->orderBy('id', 'desc');

        if ($statusFilter === 'pending') {
            $query->where('status', 0);
        } elseif ($statusFilter === 'approved') {
            $query->where('status', 1);
        } elseif ($statusFilter === 'rejected') {
            $query->where('status', 2);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('wallet', 'like', "%{$search}%")
                  ->orWhere('method', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $withdrawals = $query->paginate(20)->appends($request->all());

        // Overview KPI metrics
        $pendingCount = Withdraw::where('status', 0)->count();
        $pendingCoins = (float) Withdraw::where('status', 0)->sum('coin_amount');
        $rate = (float) (function_exists('settings') ? settings('coins_per_dollar', 100) : 100);
        $pendingUsd = $rate > 0 ? ($pendingCoins / $rate) : 0;

        $approvedCount = Withdraw::where('status', 1)->count();
        $approvedUsd = (float) Withdraw::where('status', 1)->sum('fiat_amount');

        return view('liteback.withdrawals.index', compact(
            'withdrawals',
            'statusFilter',
            'search',
            'pendingCount',
            'pendingCoins',
            'pendingUsd',
            'approvedCount',
            'approvedUsd',
            'rate'
        ));
    }

    /**
     * Approve and mark withdrawal as paid / fulfilled
     */
    public function approve(Request $request, $id)
    {
        $withdrawal = Withdraw::find($id);
        if (!$withdrawal) {
            return redirect()->back()->withErrors('Withdrawal record not found.');
        }

        if ((int) $withdrawal->status !== 0) {
            return redirect()->back()->withErrors('This withdrawal has already been processed.');
        }

        $adminNote = $request->input('admin_note', '');
        $txid = $request->input('txid', '');
        $combinedNote = trim(($txid ? "TXID: {$txid}. " : '') . ($adminNote ? "Note: {$adminNote}. " : '') . "Approved by " . (auth()->user()->username ?? 'admin'));

        $withdrawal->status = 1; // Approved / Fulfilled
        $withdrawal->admin_note = $combinedNote;
        $withdrawal->confirmed_at = now();
        $withdrawal->save();

        return redirect()->back()->with('success', "Withdrawal #{$withdrawal->id} of {$withdrawal->coin_amount} coins (\${$withdrawal->fiat_amount}) approved and marked paid.");
    }

    /**
     * Reject withdrawal and refund coins back to the player
     */
    public function reject(Request $request, $id)
    {
        $withdrawal = Withdraw::find($id);
        if (!$withdrawal) {
            return redirect()->back()->withErrors('Withdrawal record not found.');
        }

        if ((int) $withdrawal->status !== 0) {
            return redirect()->back()->withErrors('This withdrawal has already been processed.');
        }

        $request->validate([
            'admin_note' => 'required|string|max:500',
        ]);

        $reason = $request->input('admin_note');
        $user = User::find($withdrawal->user_id);
        if (!$user) {
            return redirect()->back()->withErrors('Player account not found.');
        }

        DB::transaction(function () use ($withdrawal, $user, $reason) {
            $refundCoins = (float) ($withdrawal->coin_amount ?: $withdrawal->amount);
            $newBalance = (float) $user->balance + $refundCoins;

            // 1. Refund player balance
            DB::table('users')->where('id', $user->id)->update([
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

            // 2. Transaction log for refund
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'direction' => 'refund',
                'amount' => $refundCoins,
                'balance_before' => $user->balance,
                'balance_after' => $newBalance,
                'source' => 'withdrawal_refund',
                'note' => 'Withdrawal #' . $withdrawal->id . ' rejected & refunded. Reason: ' . $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. Update withdrawal record
            $withdrawal->status = 2; // Rejected
            $withdrawal->admin_note = 'Rejected: ' . $reason . ' (Refunded by ' . (auth()->user()->username ?? 'admin') . ')';
            $withdrawal->confirmed_at = now();
            $withdrawal->save();
        });

        return redirect()->back()->with('success', "Withdrawal #{$withdrawal->id} rejected. {$withdrawal->coin_amount} coins have been refunded to {$user->username}'s balance.");
    }
}
