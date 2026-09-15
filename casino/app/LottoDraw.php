<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class LottoDraw extends Model
{
    protected $table = 'lotto_draws';

    protected $fillable = [
        'lotto_game_id',
        'winning_numbers_json',
        'draw_date',
        'total_tickets',
        'total_winners',
        'total_paid',
        'jackpot_paid',
        'drawn_at',
    ];

    protected $casts = [
        'winning_numbers_json' => 'array',
        'total_tickets' => 'integer',
        'total_winners' => 'integer',
        'total_paid' => 'float',
        'drawn_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(LottoGame::class, 'lotto_game_id');
    }
}
