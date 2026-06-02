<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;

class CronLog extends Model
{
    protected $table = 'sports_cron_logs';

    protected $fillable = [
        'job_alias',
        'start_at',
        'end_at',
        'duration',
        'error'
    ];
}
