<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Support\Enum\UserStatus;
use VanguardLTE\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 20;
        $term = trim((string) $request->input('q', ''));
        // Use base table name; prefix is applied automatically via DB config.
        $query = DB::table('users')->select('id', 'username', 'balance');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('username', 'like', '%' . $term . '%');
            });
        }

        $users = $query->orderByDesc('id')->paginate($perPage)->appends($request->only('q'));

        return view('liteback.users.index', [
            'users' => $users,
            'term' => $term,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|max:64|unique:users,username',
            'password' => 'required|string|min:6|max:64',
            'email' => 'nullable|email|max:150|unique:users,email',
            'balance' => 'nullable|numeric|min:0',
        ]);

        $balance = $request->input('balance', 0);

        $user = new User();
        $user->username = $request->input('username');
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->balance = $balance;
        $user->count_balance = $balance;
        $user->role_id = 1;
        $user->parent_id = auth()->id() ?? 1;
        $user->shop_id = 1;
        $user->status = UserStatus::ACTIVE;
        $user->remember_token = Str::random(60);
        $user->save();

        return redirect()->back()->with('success', 'User created.');
    }

    public function adjustBalance($userId, Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'direction' => 'required|in:add,deduct',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($userId, $request) {
                [$lookupId, $lookupUsername] = $this->normalizeUserLookup($userId);

                $user = DB::table('users')
                    ->where(function ($q) use ($lookupId, $lookupUsername) {
                        if ($lookupId !== null) {
                            $q->orWhere('id', $lookupId);
                        }
                        if ($lookupUsername !== null) {
                            $q->orWhere('username', $lookupUsername);
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    throw new \RuntimeException('User not found (id: ' . ($lookupId ?? 'n/a') . ', username: ' . ($lookupUsername ?? 'n/a') . ').');
                }

                $amount = (float) $request->input('amount');
                $delta = $request->input('direction') === 'deduct' ? -$amount : $amount;

                $newBalance = max(0, (float) $user->balance + $delta);
                $newCountBalance = $user->count_balance;
                if ($delta > 0) {
                    $newCountBalance = max(0, (float) $user->count_balance + $delta);
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'balance' => $newBalance,
                        'count_balance' => $newCountBalance,
                        'updated_at' => now(),
                    ]);

                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'admin_id' => auth()->id(),
                    'direction' => $request->input('direction'),
                    'amount' => $amount,
                    'balance_before' => $user->balance,
                    'balance_after' => $newBalance,
                    'source' => 'manual',
                    'note' => $request->input('note'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors('Balance update failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Balance updated.');
    }

    /**
     * Normalize incoming route param to id/username lookups.
     *
     * @param mixed $userId
     * @return array{0:int|null,1:string|null}
     */
    private function normalizeUserLookup($userId): array
    {
        $lookupId = null;
        $lookupUsername = null;

        if (is_numeric($userId)) {
            $lookupId = (int) $userId;
        } elseif (is_string($userId) && str_starts_with($userId, '{')) {
            $decoded = json_decode($userId, true);
            if (is_array($decoded)) {
                $lookupId = isset($decoded['id']) && is_numeric($decoded['id']) ? (int) $decoded['id'] : null;
                $lookupUsername = $decoded['username'] ?? null;
            } else {
                $lookupUsername = $userId;
            }
        } elseif (is_array($userId)) {
            $lookupId = isset($userId['id']) && is_numeric($userId['id']) ? (int) $userId['id'] : null;
            $lookupUsername = $userId['username'] ?? null;
        } elseif (is_object($userId)) {
            $lookupId = property_exists($userId, 'id') && is_numeric($userId->id) ? (int) $userId->id : null;
            $lookupUsername = $userId->username ?? null;
        } else {
            $lookupUsername = is_string($userId) ? $userId : null;
        }

        return [$lookupId, $lookupUsername];
    }

    public function destroy($userId)
    {
        DB::beginTransaction();
        try {
            $tablesWithUserId = [
                'api_tokens',
                'game_log',
                'info',
                'jpg',
                'messages',
                'notifications',
                'open_shift',
                'payments',
                'pay_tickets',
                'permission_user',
                'pincodes',
                'progress_users',
                'rewards',
                'role_user',
                'sessions',
                'shops',
                'shops_user',
                'sms',
                'sms_bonus_items',
                'sms_mailing_messages',
                'statistics',
                'statistics_add',
                'stat_game',
                'subsessions',
                'tasks',
                'tickets',
                'tickets_answers',
                'tournament_stats',
                'user_activity',
                'withdraw_funds',
            ];

            foreach ($tablesWithUserId as $table) {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'user_id')) {
                    continue;
                }

                DB::table($table)->where('user_id', $userId)->delete();
            }

            User::where('id', $userId)->delete();
            DB::commit();
            return redirect()->back()->with('success', 'User and related records deleted.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors('Delete failed: ' . $e->getMessage());
        }
    }
}
