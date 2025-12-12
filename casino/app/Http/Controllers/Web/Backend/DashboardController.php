<?php

namespace VanguardLTE\Http\Controllers\Web\Backend;

use Carbon\Carbon;
use Illuminate\Http\Request;
use VanguardLTE\Events\User\Banned;
use VanguardLTE\Game;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Repositories\Activity\ActivityRepository;
use VanguardLTE\Repositories\User\UserRepository;
use VanguardLTE\Security;
use VanguardLTE\StatGame;
use VanguardLTE\Statistic;
use VanguardLTE\Support\Enum\UserStatus;
use VanguardLTE\User;
use VanguardLTE\Withdraw;

class DashboardController extends Controller
{
    private $users;
    private $activities;

    public function __construct(UserRepository $users, ActivityRepository $activities)
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:access.admin.panel');
        $this->users = $users;
        $this->activities = $activities;
    }

    public function index()
    {
        if (!auth()->user()->hasPermission('dashboard')) {
            return redirect()->route('backend.user.list');
        }

        $ids = auth()->user()->hierarchyUsers();
        $stats = [
            'total'  => $this->users->count($ids),
            'new'    => $this->users->newUsersCount($ids),
            'banned' => $this->users->countByStatus(UserStatus::BANNED, $ids),
            'games'  => Game::count(),
        ];

        $statistics = Statistic::whereHas('add', function ($query) {
            $query->whereNotNull('money_in')->orWhereNotNull('money_out');
        })->orderByDesc('id')->take(5)->get();

        $gamestat = StatGame::whereIn('user_id', $ids)->orderByDesc('date_time')->take(5)->get();

        return view('backend.dashboard.admin', compact('stats', 'statistics', 'gamestat'));
    }

    public function transactions(Request $request)
    {
        if (!auth()->user()->hasPermission('dashboard')) {
            return redirect()->route('backend.user.list');
        }

        $transactions = Statistic::whereHas('add', function ($query) {
            $query->whereNotNull('money_in')->orWhereNotNull('money_out');
        })->orderByDesc('id')->paginate(50)->withQueryString();

        $stats = [
            'money_in'  => $transactions->sum(fn ($t) => optional($t->add)->money_in ?? 0),
            'money_out' => $transactions->sum(fn ($t) => optional($t->add)->money_out ?? 0),
        ];
        $stats['pay_out'] = $stats['money_in'] > 0 ? ($stats['money_out'] / $stats['money_in']) * 100 : 0;

        return view('backend.stat.transactions', compact('transactions', 'stats'));
    }

    public function game_stat(Request $request)
    {
        if (!auth()->user()->hasPermission('dashboard')) {
            return redirect()->route('backend.user.list');
        }

        $statistics = StatGame::query()->orderByDesc('id');

        if ($request->game) {
            $statistics->where('game', 'like', '%' . $request->game . '%');
        }
        if ($request->user) {
            $statistics->whereHas('user', function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->user . '%');
            });
        }
        if ($request->dates) {
            $dates = explode(' - ', $request->dates);
            if (count($dates) === 2) {
                $statistics->whereBetween('date_time', [$dates[0], $dates[1]]);
            }
        }

        $count = $statistics->count();
        $page = $request->page ?: 1;
        $perPage = 50;
        $offset = $page * $perPage - $perPage;
        $ids = $statistics->offset($offset)->take($perPage)->pluck('id');
        $game_stat = StatGame::whereIn('id', $ids)->orderByDesc('id')->get();

        return view('backend.stat.game_stat', compact('game_stat', 'count', 'page', 'perPage'));
    }

    public function securities(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }
        $securities = Security::where('securities.view', 1);
        if ($request->type != '') {
            $securities = $securities->where('securities.type', 'LIKE', $request->type . '%');
        }
        if ($request->dates != '') {
            $dates = explode(' - ', $request->dates);
            if (count($dates) === 2) {
                $securities = $securities->whereBetween('securities.created_at', [$dates[0], $dates[1]]);
            }
        }
        $securities = $securities->orderBy('securities.created_at', 'DESC')->paginate(25)->withQueryString();
        return view('backend.dashboard.security', compact('securities'));
    }

    public function securities_delete(Request $request, Security $item)
    {
        if ($item) {
            $item->update(['view' => 0]);
        }
        return redirect()->back()->withSuccess(__('app.security_deleted'));
    }

    public function securities_block(Request $request, Security $item)
    {
        if ($item) {
            if ($item->type == 'user' && $item->user) {
                $item->user->update(['status' => UserStatus::BANNED, 'remember_token' => null]);
                \DB::table('sessions')->where('user_id', $item->user->id)->delete();
                $item->update(['view' => 0]);
                event(new Banned($item->user));
                return redirect()->back()->withSuccess(__('app.user_blocked'));
            }
            if ($item->type == 'game' && $item->game) {
                $item->game->update(['view' => 0]);
                $item->update(['view' => 0]);
                return redirect()->back()->withSuccess(__('app.game_blocked'));
            }
        }
        return redirect()->back()->withSuccess(__('app.security_not_found'));
    }

    public function search(Request $request)
    {
        if (!$request->q) {
            return redirect()->back()->withErrors(['Empty query']);
        }

        $query = $request->q;
        $users = User::where('username', 'like', '%' . $query . '%')->limit(50)->get();
        $games = Game::where('name', 'like', '%' . $query . '%')->limit(50)->get();

        return view('backend.dashboard.search', compact('users', 'games', 'query'));
    }

    public function withdraw(Request $request)
    {
        $withdraws = Withdraw::orderByDesc('created_at')->get();

        return view('backend.withdraw.list', compact('withdraws'));
    }
}
