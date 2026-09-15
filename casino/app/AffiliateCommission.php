<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class AffiliateCommission extends Model
{
    protected $table = 'affiliate_commissions';

    protected $fillable = [
        'affiliate_id',
        'referred_user_id',
        'tier',
        'game_type',
        'wager_amount',
        'commission_rate',
        'commission_amount',
        'status',
    ];

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
