<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class LottoGame extends Model
{
    protected $table = 'lotto_games';

    protected $fillable = [
        'title',
        'slug',
        'pick_count',
        'max_number',
        'entry_fee',
        'jackpot_pool',
        'draw_interval',
        'draw_time',
        'is_active',
    ];

    protected $casts = [
        'pick_count' => 'integer',
        'max_number' => 'integer',
        'entry_fee' => 'float',
        'jackpot_pool' => 'float',
        'is_active' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(LottoTicket::class, 'lotto_game_id');
    }

    public function draws()
    {
        return $this->hasMany(LottoDraw::class, 'lotto_game_id');
    }
}
