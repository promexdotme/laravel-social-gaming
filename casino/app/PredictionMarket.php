<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class PredictionMarket extends Model
{
    protected $table = 'prediction_markets';

    protected $fillable = [
        'market_id',
        'creator_id',
        'category',
        'title',
        'description',
        'verification_url',
        'pool_yes',
        'pool_no',
        'end_date',
        'status',
        'resolution',
    ];

    protected $casts = [
        'end_date' => 'datetime',
        'pool_yes' => 'float',
        'pool_no' => 'float',
    ];

    protected $appends = [
        'yes_price',
        'no_price',
        'yes_percent',
        'no_percent',
        'total_volume',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function votes()
    {
        return $this->hasMany(PredictionVote::class, 'market_id', 'market_id');
    }

    /**
     * Probability Share Price for YES ($0.01 - $0.99)
     */
    public function getYesPriceAttribute(): float
    {
        $yes = max(1.0, (float)($this->pool_yes ?? 1000));
        $no = max(1.0, (float)($this->pool_no ?? 1000));
        $prob = $yes / ($yes + $no);
        return round(max(0.01, min(0.99, $prob)), 2);
    }

    /**
     * Probability Share Price for NO ($0.01 - $0.99)
     */
    public function getNoPriceAttribute(): float
    {
        return round(max(0.01, min(0.99, 1.00 - $this->yes_price)), 2);
    }

    /**
     * Probability Percentage for YES (1% - 99%)
     */
    public function getYesPercentAttribute(): int
    {
        return (int) round($this->yes_price * 100);
    }

    /**
     * Probability Percentage for NO (1% - 99%)
     */
    public function getNoPercentAttribute(): int
    {
        return 100 - $this->yes_percent;
    }

    /**
     * Total Trading Liquidity Volume in Cedar Coins
     */
    public function getTotalVolumeAttribute(): float
    {
        return (float)(($this->pool_yes ?? 0) + ($this->pool_no ?? 0));
    }

    /**
     * AMM Decimal Multiplier for YES (1 / price)
     */
    public function getYesOddsAttribute(): float
    {
        $price = $this->yes_price;
        if ($price <= 0) return 20.00;
        return round(max(1.01, min(20.00, 1.0 / $price)), 2);
    }

    /**
     * AMM Decimal Multiplier for NO (1 / price)
     */
    public function getNoOddsAttribute(): float
    {
        $price = $this->no_price;
        if ($price <= 0) return 20.00;
        return round(max(1.01, min(20.00, 1.0 / $price)), 2);
    }

    /**
     * Dynamic Simulated 2-Sided Order Book Depth (Bids & Asks Ladder)
     */
    public function getOrderBookAttribute(): array
    {
        $yesPrice = $this->yes_price;
        $pool = max(5000, $this->total_volume);

        $bids = [];
        $asks = [];

        // Generate 5 bid levels (buying YES below current price)
        for ($i = 0; $i < 5; $i++) {
            $price = round(max(0.01, $yesPrice - ($i * 0.01) - ($i === 0 ? 0.00 : 0.01)), 2);
            $qty = round(($pool * (0.05 + $i * 0.04)) / max(0.01, $price), 0);
            $bids[] = [
                'price' => $price,
                'shares' => (int) $qty,
                'total_coins' => (int) round($qty * $price),
            ];
        }

        // Generate 5 ask levels (selling YES above current price)
        for ($i = 0; $i < 5; $i++) {
            $price = round(min(0.99, $yesPrice + ($i * 0.01) + 0.01), 2);
            $qty = round(($pool * (0.05 + $i * 0.04)) / max(0.01, $price), 0);
            $asks[] = [
                'price' => $price,
                'shares' => (int) $qty,
                'total_coins' => (int) round($qty * $price),
            ];
        }

        return [
            'spread' => round(max(0.01, ($asks[0]['price'] ?? $yesPrice) - ($bids[0]['price'] ?? $yesPrice)), 2),
            'bids' => $bids,
            'asks' => $asks,
        ];
    }
}
