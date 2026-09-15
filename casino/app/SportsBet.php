<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class SportsBet extends Model
{
    protected $table = 'sports_bets';

    protected $fillable = [
        'user_id',
        'type',
        'legs_json',
        'total_odds',
        'stake',
        'potential_win',
        'status',
        'payout_amount',
    ];

    protected $casts = [
        'legs_json' => 'array',
        'total_odds' => 'float',
        'stake' => 'float',
        'potential_win' => 'float',
        'payout_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
