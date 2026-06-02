<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;

class BetItem extends Model
{
    protected $table = 'sports_bet_items';

    protected $fillable = [
        'bet_id',
        'market_id',
        'outcome_id',
        'odds',
        'status'
    ];

    public function bet()
    {
        return $this->belongsTo(Bet::class, 'bet_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id');
    }

    public function outcome()
    {
        return $this->belongsTo(Outcome::class, 'outcome_id');
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

    public function scopeHasSingleBet($query)
    {
        return $query->whereHas('bet', function ($q) {
            $q->where('type', 1)->pending();
        });
    }

    public function scopeHasMultiBet($query)
    {
        return $query->whereHas('bet', function ($q) {
            $q->where('type', 2)->pending();
        });
    }

    public function scopeRelationalData($query)
    {
        $query->with([
            'outcome' => function ($outcome) {
                $outcome->active()->with([
                    'market' => function ($market) {
                        $market->active()->with([
                            'game' => function ($game) {
                                $game->with([
                                    'teamOne',
                                    'teamTwo',
                                    'league' => function ($league) {
                                        $league->active()->with('category');
                                    },
                                ]);
                            },
                        ]);
                    },
                ]);
            },
        ]);
    }
}
