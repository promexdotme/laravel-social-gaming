<?php

namespace VanguardLTE\Support;

use Carbon\Carbon;

class TimezoneHelper
{
    /**
     * Convert any date string/timestamp to standard ISO-8601 UTC
     */
    public static function toUtcIso($date = null): string
    {
        if (empty($date)) {
            return Carbon::now('UTC')->toIso8601String();
        }

        if ($date instanceof Carbon) {
            return $date->setTimezone('UTC')->toIso8601String();
        }

        return Carbon::parse($date)->setTimezone('UTC')->toIso8601String();
    }

    /**
     * Get common IANA Timezones list for Admin / User Selection
     */
    public static function getTimezonesList(): array
    {
        return [
            'UTC' => 'UTC (Coordinated Universal Time)',
            'Europe/Beirut' => 'Beirut (GMT+3 / GMT+2)',
            'Asia/Dubai' => 'Dubai (GMT+4)',
            'Europe/London' => 'London (GMT+1 / GMT+0)',
            'Europe/Paris' => 'Paris / Berlin (GMT+2 / GMT+1)',
            'America/New_York' => 'New York (GMT-4 / GMT-5)',
            'America/Los_Angeles' => 'Los Angeles (GMT-7 / GMT-8)',
            'Asia/Riyadh' => 'Riyadh (GMT+3)',
            'Asia/Tokyo' => 'Tokyo (GMT+9)',
        ];
    }
}
