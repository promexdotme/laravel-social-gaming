<?php
namespace VanguardLTE\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use VanguardLTE\Services\CedarGameService;

class SettleCedarCrash extends Command
{
    protected $signature = 'cedar:settle-crash';
    protected $description = 'Settle due solo Crash rounds, including disconnected players';

    public function handle(): int
    {
        $failed = false;
        DB::table('cedar_rounds')->where('game', 'CedarCrash')->where('status', 'active')->orderBy('id')->chunkById(100, function ($rows) use (&$failed) {
            foreach ($rows as $row) {
                try { (new CedarGameService())->settleCrashFor((int) $row->user_id); }
                catch (\Throwable $e) { report($e); $failed = true; }
            }
        });
        $this->info($failed ? 'Some rounds need review; see application logs.' : 'Due Crash rounds settled.');
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
