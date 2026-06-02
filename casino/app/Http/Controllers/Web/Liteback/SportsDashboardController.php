<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Sports\Bet;
use VanguardLTE\Sports\CronLog;

class SportsDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_bets' => Bet::count(),
            'total_stakes' => (float)Bet::sum('stake_amount'),
            'total_payouts' => (float)Bet::where('status', 1)->sum('return_amount'),
        ];
        $stats['net_ggr'] = $stats['total_stakes'] - $stats['total_payouts'];

        $cronLogs = CronLog::orderBy('id', 'desc')->limit(15)->get();

        return view('liteback.sports.dashboard', compact('stats', 'cronLogs'));
    }
    public function runCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string|in:sports:sync:leagues,sports:sync:games,sports:sync:odds,sports:sync:odds-inplay,sports:games:open,sports:events:cleanup,sports:sync:upcoming,sports:sync:all'
        ]);

        $command = $request->input('command');

        try {
            Artisan::call($command);
            $output = Artisan::output();
            return redirect()->back()->with('success', "Command [{$command}] ran successfully! Output: " . trim($output));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors("Command failed: " . $e->getMessage());
        }
    }
}
