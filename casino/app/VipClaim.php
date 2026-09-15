<?php

namespace VanguardLTE;

use Illuminate\Database\Eloquent\Model;

class VipClaim extends Model
{
    protected $table = 'vip_claims';

    protected $fillable = [
        'user_id',
        'type',
        'tier',
        'amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
