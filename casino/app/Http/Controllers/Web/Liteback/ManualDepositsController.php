<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\User;

class ManualDepositsController extends Controller
{
    public function index()
    {
        $deposits = DB::table('manual_deposits')
            ->join('users', 'manual_deposits.user_id', '=', 'users.id')
            ->join('payment_intents', 'manual_deposits.payment_intent_id', '=', 'payment_intents.id')
            ->select('manual_deposits.*', 'users.username', 'users.email', 'payment_intents.amount', 'payment_intents.currency')
            ->orderBy('manual_deposits.created_at', 'desc')
            ->paginate(20);

        return view('liteback.payments.manual', compact('deposits'));
    }

    public function approve($id)
    {
        $deposit = DB::table('manual_deposits')->where('id', $id)->first();
        if (!$deposit) {
            return redirect()->back()->withErrors('Deposit record not found.');
        }

        if ($deposit->status != 0) {
            return redirect()->back()->withErrors('This deposit has already been processed.');
        }

        $intent = DB::table('payment_intents')->where('id', $deposit->payment_intent_id)->first();
        if (!$intent) {
            return redirect()->back()->withErrors('Matching payment intent not found.');
        }

        $user = User::find($deposit->user_id);
        if (!$user) {
            return redirect()->back()->withErrors('User not found.');
        }

        DB::transaction(function () use ($deposit, $intent, $user) {
            $newBalance = (float) $user->balance + (float) $intent->amount;

            DB::table('users')->where('id', $user->id)->update([
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'direction' => 'payment',
                'amount' => $intent->amount,
                'balance_before' => $user->balance,
                'balance_after' => $newBalance,
                'source' => 'manual',
                'note' => 'Manual Bank Deposit approved by admin ' . auth()->user()->username,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('manual_deposits')->where('id', $deposit->id)->update([
                'status' => 1, // Approved
                'admin_note' => 'Approved by ' . auth()->user()->username,
                'updated_at' => now(),
            ]);

            DB::table('payment_intents')->where('id', $intent->id)->update([
                'status' => 'paid',
                'updated_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Deposit approved and user balance updated.');
    }

    public function reject(Request $request, $id)
    {
        $deposit = DB::table('manual_deposits')->where('id', $id)->first();
        if (!$deposit) {
            return redirect()->back()->withErrors('Deposit record not found.');
        }

        if ($deposit->status != 0) {
            return redirect()->back()->withErrors('This deposit has already been processed.');
        }

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        DB::table('manual_deposits')->where('id', $id)->update([
            'status' => 2, // Rejected
            'admin_note' => $request->input('admin_note') ?? 'Rejected by ' . auth()->user()->username,
            'updated_at' => now(),
        ]);

        DB::table('payment_intents')->where('id', $deposit->payment_intent_id)->update([
            'status' => 'rejected',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Deposit request rejected.');
    }
}
