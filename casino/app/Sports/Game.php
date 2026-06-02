<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'sports_games';

    protected $fillable = [
        'ods_api_id',
        'title',
        'team_one_id',
        'team_two_id',
        'league_id',
        'slug',
        'start_time',
        'bet_start_time',
        'status',
        'manually_added',
        'is_outright'
    ];

    public function league()
    {
        return $this->belongsTo(League::class, 'league_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'sports_game_team', 'game_id', 'team_id');
    }

    public function markets()
    {
        return $this->hasMany(Market::class, 'game_id');
    }

    public function teamOne()
    {
        return $this->belongsTo(Team::class, 'team_one_id');
    }

    public function teamTwo()
    {
        return $this->belongsTo(Team::class, 'team_two_id');
    }

    // Scopes
    public function scopeInPlay($query)
    {
        return $query->where('start_time', '<=', now())
            ->where('is_outright', 0)
            ->whereNotIn('status', [2, 4]); // 2 = cancelled, 4 = ended
    }

    public function scopeOpenForBetting($query)
    {
        return $query->where('status', 1);
    }

    public function scopeNotOpenForBetting($query)
    {
        return $query->where('status', 0);
    }

    public function scopeClosedForBetting($query)
    {
        return $query->where('status', 3);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 2);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 4);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())->where('status', '!=', 4);
    }

    public function getIsInPlayAttribute()
    {
        return now()->gte($this->start_time);
    }

    public function scopeHasActiveCategory($query)
    {
        return $query->whereHas('league.category', function ($category) {
            $category->active();
        });
    }

    public function scopeHasActiveLeague($query)
    {
        return $query->whereHas('league', function ($league) {
            $league->active();
        });
    }
}
