<?php

namespace VanguardLTE\Console\Commands\Sports;

use Illuminate\Console\Command;
use VanguardLTE\Sports\CronLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

abstract class BaseSportsCommand extends Command
{
    /**
     * The alias/name of the cron job for log tracking.
     */
    protected $jobAlias;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $error = null;

        $this->info("Starting command [{$this->signature}]...");

        try {
            $this->handleCommand();
            $this->info("Command [{$this->signature}] completed successfully.");
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->error("Command [{$this->signature}] failed: {$error}");
            Log::error("Command [{$this->signature}] error: " . $e->getTraceAsString());
        }

        try {
            $endTime = now();
            $duration = Carbon::parse($startTime)->diffInSeconds(Carbon::parse($endTime));

            CronLog::create([
                'job_alias' => $this->jobAlias ?? $this->signature,
                'start_at' => $startTime,
                'end_at' => $endTime,
                'duration' => $duration,
                'error' => $error,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to write cron log: " . $e->getMessage());
        }

        return $error ? 1 : 0;
    }

    /**
     * Implement this method to perform actual command task logic.
     */
    abstract protected function handleCommand();
}
