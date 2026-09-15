<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class PredictionVote extends Model
{
    protected $table = 'prediction_votes';

    protected $fillable = [
        'user_id',
        'market_id',
        'choice',
        'odds',
        'share_price',
        'shares_count',
        'stake',
        'potential_win',
        'status',
        'payout_amount',
    ];

    protected $casts = [
        'odds' => 'float',
        'share_price' => 'float',
        'shares_count' => 'float',
        'stake' => 'float',
        'potential_win' => 'float',
        'payout_amount' => 'float',
    ];

    protected $appends = [
        'roi_percent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function market()
    {
        return $this->belongsTo(PredictionMarket::class, 'market_id', 'market_id');
    }

    /**
     * Estimated or Realized Return on Investment %
     */
    public function getRoiPercentAttribute(): float
    {
        if ($this->stake <= 0) return 0.0;
        $profit = ($this->potential_win ?? 0) - $this->stake;
        return round(($profit / $this->stake) * 100, 1);
    }
}
