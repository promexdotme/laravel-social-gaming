<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class SportsMatch extends Model
{
    protected $table = 'sports_matches';

    protected $fillable = [
        'sport_key',
        'sport_title',
        'match_id',
        'home_team',
        'away_team',
        'start_time',
        'odds_home',
        'odds_draw',
        'odds_away',
        'status',
        'winner',
        'home_score',
        'away_score',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'odds_home' => 'float',
        'odds_draw' => 'float',
        'odds_away' => 'float',
        'home_score' => 'integer',
        'away_score' => 'integer',
    ];
}
