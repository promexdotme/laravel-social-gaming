<?php 
namespace VanguardLTE
{
    class Withdraw extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'withdraw_funds';
        protected $fillable = [
            'user_id', 
            'amount', 
            'coin_amount',
            'fiat_amount',
            'currency', 
            'method',
            'wallet', 
            'status', 
            'admin_note',
            'shop_id',
            'created_at',
            'confirmed_at'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
        public function user()
        {
            return $this->hasOne('VanguardLTE\User', 'id', 'user_id');
        }
        public function shop()
        {
            return $this->hasOne('VanguardLTE\Shop', 'id', 'shop_id');
        }
    }

}
