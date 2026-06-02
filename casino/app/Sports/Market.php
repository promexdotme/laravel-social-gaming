<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $table = 'sports_markets';

    protected $fillable = [
        'game_id',
        'market_type',
        'outcome_type',
        'player_props',
        'game_period_market',
        'title',
        'status',
        'locked',
        'result_declared',
        'win_outcome_id',
        'market_updated_at'
    ];

    protected $appends = ['market_title'];

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function outcomes()
    {
        return $this->hasMany(Outcome::class, 'market_id');
    }

    public function winOutcome()
    {
        return $this->belongsTo(Outcome::class, 'win_outcome_id');
    }

    public function betItems()
    {
        return $this->hasMany(BetItem::class, 'market_id');
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

    public function scopeResultDeclared($query)
    {
        return $query->where('result_declared', 1);
    }

    public function scopeResultUndeclared($query)
    {
        return $query->where('result_declared', 0);
    }

    public function scopeFilterByGamePeriod($query)
    {
        return $query->where(function ($q) {
            $q->where('game_period_market', 0)
              ->orWhere(function ($sub) {
                  $sub->where('game_period_market', 1)
                      ->whereHas('game', function ($g) {
                          $g->where('start_time', '<=', now());
                      });
              });
        });
    }

    public function getMarketTitleAttribute()
    {
        if ($this->market_type === 'h2h') {
            return 'Head to Head';
        } elseif ($this->market_type === 'h2h_3way') {
            return 'Head to Head 3 Way';
        } elseif ($this->market_type === 'spreads') {
            return 'Spreads';
        } elseif ($this->market_type === 'totals') {
            return 'Totals';
        } elseif ($this->market_type === 'outrights') {
            return 'Outrights';
        }
        return $this->title;
    }
}
