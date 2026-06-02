<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $table = 'sports_leagues';

    protected $fillable = [
        'odds_api_sport_key',
        'category_id',
        'name',
        'short_name',
        'slug',
        'description',
        'has_outrights',
        'image',
        'status',
        'api_status',
        'manually_added'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function games()
    {
        return $this->hasMany(Game::class, 'league_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 1)->where('api_status', 1);
    }

    public function scopeHasApiSportKey($query)
    {
        return $query->whereNotNull('odds_api_sport_key');
    }

    public function runningActiveGames()
    {
        return $this->hasMany(Game::class, 'league_id')
            ->where('start_time', '<=', now())
            ->whereNotIn('status', [2, 4]); // not cancelled (2) or ended (4)
    }
}
