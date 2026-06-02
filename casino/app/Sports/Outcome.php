<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;

class Outcome extends Model
{
    protected $table = 'sports_outcomes';

    protected $fillable = [
        'market_id',
        'name',
        'odds',
        'point',
        'status',
        'locked',
        'winner'
    ];

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id');
    }

    public function bets()
    {
        return $this->hasMany(BetItem::class, 'outcome_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeLocked($query)
    {
        return $query->where('locked', 1);
    }

    public function scopeUnLocked($query)
    {
        return $query->where('locked', 0);
    }

    public function scopeAvailableForBet($query)
    {
        return $query->active()->unLocked()->whereHas('market', function ($m) {
            $m->active()->unLocked()->resultUndeclared()->filterByGamePeriod()
                ->whereHas('game', function ($g) {
                    $g->openForBetting()->hasActiveCategory()->hasActiveLeague();
                });
        });
    }
}
