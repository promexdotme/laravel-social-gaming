<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;
use VanguardLTE\User;

class Bet extends Model
{
    protected $table = 'sports_bets';

    protected $fillable = [
        'bet_number',
        'user_id',
        'type',
        'stake_amount',
        'return_amount',
        'status',
        'is_settled',
        'result_time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(BetItem::class, 'bet_id');
    }

    public function scopeSingleBet($query)
    {
        return $query->where('type', 1);
    }

    public function scopeMultiBets($query)
    {
        return $query->where('type', 2);
    }

    public function scopePending($query)
    {
        return $query->where('status', 2);
    }

    public function scopeWin($query)
    {
        return $query->where('status', 1);
    }

    public function scopeLoss($query)
    {
        return $query->where('status', 3);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 4);
    }

    public function scopeNotSettled($query)
    {
        return $query->where('is_settled', 0);
    }
}
