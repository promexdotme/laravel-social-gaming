<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class LottoTicket extends Model
{
    protected $table = 'lotto_tickets';

    protected $fillable = [
        'lotto_game_id',
        'user_id',
        'numbers_json',
        'draw_date',
        'status',
        'matches_count',
        'payout_amount',
        'prize_won',
    ];

    protected $casts = [
        'numbers_json' => 'array',
        'matches_count' => 'integer',
        'payout_amount' => 'float',
    ];

    public function game()
    {
        return $this->belongsTo(LottoGame::class, 'lotto_game_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
