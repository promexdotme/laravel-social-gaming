<?php 
namespace VanguardLTE
{
    class User extends \Illuminate\Foundation\Auth\User implements \PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject
    {
        use \Laracasts\Presenter\PresentableTrait, 
            \Illuminate\Notifications\Notifiable, 
            \jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;
        protected $presenter = 'VanguardLTE\Presenters\UserPresenter';
        protected $table = 'users';
        protected $dates = [
            'last_login', 
            'birthday'
        ];
        protected $fillable = [
            'password', 
            'email', 
            'username', 
            'currency',
            'avatar', 
            'balance', 
            'shop_limit',
            'last_login', 
            'confirmation_token', 
            'status', 
            'is_demo_agent', 
            'google2fa_secret', 
            'google2fa_enable', 
            'rating', 
            'agreed', 
            'free_demo', 
            'count_tournaments', 
            'count_happyhours', 
            'count_refunds', 
            'count_progress', 
            'count_daily_entries', 
            'count_invite', 
            'tournaments', 
            'happyhours', 
            'refunds', 
            'progress', 
            'daily_entries', 
            'invite', 
            'welcomebonus', 
            'count_welcomebonus', 
            'smsbonus', 
            'count_smsbonus', 
            'wheelfortune', 
            'count_wheelfortune', 
            'total_in', 
            'total_out', 
            'language', 
            'phone', 
            'phone_verified', 
            'sms_token', 
            'inviter_id', 
            'remember_token', 
            'role_id', 
            'count_balance', 
            'parent_id', 
            'shop_id', 
            'session', 
            'is_blocked', 
            'auth_token', 
            'last_online', 
            'created_at', 
            'sms_token_date', 
            'last_daily_entry', 
            'last_bid', 
            'last_progress', 
            'last_wheelfortune'
        ];
        protected $hidden = [
            'password', 
            'remember_token'
        ];
        public static function boot()
        {
            parent::boot();
            self::created(function($model)
            {
                event(new Events\User\Created($model));
                User::where('id', $model->id)->update(['last_daily_entry' => \Carbon\Carbon::now()->subDays(2)]);
            });
            self::saved(function($model)
            {
                User::where('id', $model->id)->update(['username' => Lib\Functions::remove_emoji($model->username)]);
            });
            self::updated(function($model)
            {
                if( $model->refunds <= 0 ) 
                {
                    User::where('id', $model->id)->update(['refunds' => 0]);
                }
                if( $model->count_balance < 0 ) 
                {
                    User::where('id', $model->id)->update(['count_balance' => 0]);
                }
                if( $model->balance <= 0 && $model->refunds <= 0 ) 
                {
                    User::where('id', $model->id)->update([
                        'count_balance' => 0, 
                        'count_tournaments' => 0, 
                        'count_happyhours' => 0, 
                        'count_refunds' => 0, 
                        'count_progress' => 0, 
                        'count_daily_entries' => 0, 
                        'count_invite' => 0, 
                        'count_welcomebonus' => 0, 
                        'count_smsbonus' => 0, 
                        'count_wheelfortune' => 0, 
                        'tournaments' => 0, 
                        'happyhours' => 0, 
                        'refunds' => 0, 
                        'progress' => 0, 
                        'daily_entries' => 0, 
                        'invite' => 0, 
                        'welcomebonus' => 0, 
                        'smsbonus' => 0, 
                        'wheelfortune' => 0
                    ]);
                }
            });
            self::deleting(function($model)
            {
                $model->detachAllRoles();
                event(new Events\User\Deleted($model));
                ShopUser::where('user_id', $model->id)->delete();
                StatGame::where('user_id', $model->id)->delete();
                Statistic::where('user_id', $model->id)->delete();
                StatisticAdd::where('user_id', $model->id)->delete();
                GameLog::where('user_id', $model->id)->delete();
                UserActivity::where('user_id', $model->id)->delete();
                Session::where('user_id', $model->id)->delete();
                Info::where('user_id', $model->id)->delete();
                OpenShift::where('user_id', $model->id)->delete();
                SMS::where('new_user_id', $model->id)->delete();
                ProgressUser::where('user_id', $model->id)->delete();
                Reward::where('user_id', $model->id)->delete();
                Reward::where('referral_id', $model->id)->delete();
                SMS::where('user_id', $model->id)->delete();
                Services\Logging\UserActivity\Activity::where('user_id', $model->id)->delete();
                Ticket::where('user_id', $model->id)->delete();
                TicketAnswer::where('user_id', $model->id)->delete();
                Payment::where('user_id', $model->id)->delete();
                Subsession::where('user_id', $model->id)->delete();
                UserActivity::where('user_id', $model->id)->delete();
                SMSBonusItem::where('user_id', $model->id)->delete();
                Message::where('user_id', $model->id)->delete();
                SMSMailingMessage::where('user_id', $model->id)->delete();
                Security::where([
                    'type' => 'user', 
                    'item_id' => $model->id
                ])->delete();
                JPG::where('user_id', $model->id)->update(['user_id' => null]);
            });
        }
        protected static function booted()
        {
            static::addGlobalScope(new Scopes\DemoAgent());
        }
        public function setPasswordAttribute($value)
        {
            $this->attributes['password'] = bcrypt($value);
        }
        public function setBirthdayAttribute($value)
        {
            $this->attributes['birthday'] = (trim($value) ?: null);
        }
        public function gravatar()
        {
            $hash = hash('md5', strtolower(trim($this->attributes['username'])));
            return sprintf('https://www.gravatar.com/avatar/%s?size=150', $hash);
        }
        public function isActive()
        {
            return $this->status == Support\Enum\UserStatus::ACTIVE;
        }
        public function availableUsers()
        {
            if( $this->hasRole(['admin']) ) 
            {
                return User::pluck('id')->toArray();
            }
            return [$this->id];
        }
        public function hierarchyUsers($shop_id = false, $clear = false)
        {
            if( !$shop_id ) 
            {
                $shop_id = $this->shop_id;
            }
            if( $clear ) 
            {
                \Illuminate\Support\Facades\Cache::forget('hierarchyUsers:' . $this->id . ':' . $shop_id);
            }
            return \Illuminate\Support\Facades\Cache::remember('hierarchyUsers:' . $this->id . ':' . $shop_id, 300, function() use ($shop_id)
            {
                if( $this->hasRole('admin') ) 
                {
                    return User::where('id', '!=', $this->id)->pluck('id')->toArray();
                }
                return [];
            });
        }
        public function isAvailable($user)
        {
            if( !$user ) 
            {
                return false;
            }
            if( in_array($user->id, $this->availableUsers()) ) 
            {
                return true;
            }
            return false;
        }
        public function emptyShops()
        {
            $count = 0;
            if( $shops = $this->rel_shops ) 
            {
                foreach( $shops as $shop ) 
                {
                    if( $shop->shop && count($shop->shop->getUsersByRole('user')) == 0 ) 
                    {
                        $count++;
                    }
                }
            }
            return $count;
        }
        public function availableUsersByRole($roleName)
        {
            $users = $this->availableUsers();
            $role = \jeremykenedy\LaravelRoles\Models\Role::where('slug', $roleName)->first();
            if( !$role ) 
            {
                return [];
            }
            if( $this->hasRole('admin') ) 
            {
                return User::where('role_id', $role->id)->pluck('id')->toArray();
            }
            return $this->hasRole($roleName) ? $users : [];
        }
        public function availableShops($showZero = false)
        {
            $shops = [$this->shop_id];
            if( $this->hasRole([
                'admin', 
                'agent', 
                'distributor'
            ]) ) 
            {
                if( !$this->shop_id ) 
                {
                    $shops = array_merge([0], $this->shops_array(true));
                }
                else if( $showZero ) 
                {
                    $shops = [
                        0, 
                        $this->shop_id
                    ];
                }
                else
                {
                    $shops = [$this->shop_id];
                }
            }
            return $shops;
        }
        public function getInnerUsers()
        {
            $role = \jeremykenedy\LaravelRoles\Models\Role::where('id', $this->role_id - 1)->first();
            $_obf_0D06103B293F142A1D3023302D022E332B0408101E3E01 = $this->availableUsersByRole($role->slug);
            if( count($_obf_0D06103B293F142A1D3023302D022E332B0408101E3E01) ) 
            {
                return User::whereIn('id', $_obf_0D06103B293F142A1D3023302D022E332B0408101E3E01)->get();
            }
            return false;
        }
        public function getInnerUsersMinusTwo()
        {
             $role = \jeremykenedy\LaravelRoles\Models\Role::where('id', $this->role_id - 2)->first();
            $_obf_0D06103B293F142A1D3023302D022E332B0408101E3E01 = $this->availableUsersByRole($role->slug);
            if( count($_obf_0D06103B293F142A1D3023302D022E332B0408101E3E01) ) 
            {
                return User::whereIn('id', $_obf_0D06103B293F142A1D3023302D022E332B0408101E3E01)->get();
            }
            return false;
        }
        public function getRowspan()
        {
            $rowspan = 0;
            if( $this->hasRole('agent') ) 
            {
                $_obf_0D5C2A095B283E2B38321C1325321C07250A3B07043201 = User::where('parent_id', $this->id)->get();
                if( $_obf_0D5C2A095B283E2B38321C1325321C07250A3B07043201 ) 
                {
                    foreach( $_obf_0D5C2A095B283E2B38321C1325321C07250A3B07043201 as $distributor ) 
                    {
                        $rowspan += $distributor->getRowspan();
                    }
                }
            }
            if( $this->hasRole('distributor') && ($shops = $this->rel_shops) ) 
            {
                foreach( $shops as $shop ) 
                {
                    if( $shop = $shop->shop ) 
                    {
                        $cashiers = User::where([
                            'shop_id' => $shop->id, 
                            'role_id' => 2
                        ])->count();
                        if( $cashiers > 0 ) 
                        {
                            $rowspan += $cashiers;
                        }
                        else
                        {
                            $rowspan++;
                        }
                    }
                }
            }
            if( $this->hasRole('manager') ) 
            {
                $rowspan = User::where('parent_id', $this->id)->count();
            }
            return ($rowspan > 0 ? $rowspan : 1);
        }
        public function isBanned()
        {
            return $this->status == Support\Enum\UserStatus::BANNED;
        }
        public function role()
        {
            return $this->belongsTo('jeremykenedy\LaravelRoles\Models\Role', 'role_id');
        }
        public function badge()
        {
            if( strlen($this->rating) == 1 ) 
            {
                return '0' . $this->rating;
            }
            else
            {
                return $this->rating;
            }
        }
        public function formatted_phone()
        {
            if( preg_match('/^\+(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})$/', $this->phone, $matches) ) 
            {
                $result = '+' . $matches[1] . '(' . $matches[2] . ') ' . $matches[3] . '-' . $matches[4] . '-' . $matches[5];
                return $result;
            }
            return $this->phone;
        }
        public function activities()
        {
            return $this->hasMany('VanguardLTE\Services\Logging\UserActivity\Activity', 'user_id');
        }
        public function referral()
        {
            return $this->belongsTo('VanguardLTE\User', 'parent_id');
        }
        public function rel_shops()
        {
            return $this->hasMany('VanguardLTE\ShopUser', 'user_id');
        }
        public function sessions()
        {
            return $this->hasMany('VanguardLTE\Session');
        }
        public function invites()
        {
            return $this->hasMany('VanguardLTE\User', 'inviter_id', 'id');
        }
        public function rewards()
        {
            $user = $this;
            return Reward::where(function($query) use ($user)
            {
                $query->where([
                    'user_id' => $user->id, 
                    'user_received' => 0
                ]);
            })->orWhere(function($query) use ($user)
            {
                $query->where([
                    'referral_id' => $user->id, 
                    'referral_received' => 0
                ]);
            })->get();
        }
        public function is_online()
        {
            $_obf_0D030D21060933253B12060506241A3E131625061A5C22 = time();
            $_obf_0D0F3916063216313B1638021017290B103C3016403611 = strtotime($this->last_online);
            if( round(abs($_obf_0D030D21060933253B12060506241A3E131625061A5C22 - $_obf_0D0F3916063216313B1638021017290B103C3016403611) / 60, 2) <= 5 ) 
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        public function isBlocked()
        {
            if( $this->role_id == 6 ) 
            {
                return false;
            }
            if( settings('siteisclosed') ) 
            {
                return true;
            }
            if( !$this->shop ) 
            {
            }
            if( $this->is_blocked ) 
            {
                return true;
            }
            if( $this->referral && $this->referral->is_blocked ) 
            {
                return true;
            }
            if( $this->hasRole([
                1, 
                2, 
                3
            ]) ) 
            {
                if( !$this->shop ) 
                {
                    return true;
                }
                if( $this->shop->blocked() ) 
                {
                    return true;
                }
            }
            return false;
        }
        public function shops($onlyId = false)
        {
            return [];
        }
        public function shops_array($onlyId = false)
        {
            return [];
        }
        public function available_roles($withMe = false)
        {
            $query = Role::whereIn('slug', ['admin', 'user']);

            if (!$withMe && !$this->hasRole('admin')) {
                // Non-admins can only assign user role
                $query = $query->where('slug', 'user');
            }

            return $query->pluck('name', 'id');
        }
        public function shop()
        {
            return $this->belongsTo('VanguardLTE\Shop', 'shop_id');
        }
        public function getJWTIdentifier()
        {
            return $this->id;
        }
        public function getJWTCustomClaims()
        {
            $_obf_0D2B05231A38042E03250E09081D011D213D012A2B0B01 = app('VanguardLTE\Services\Auth\Api\TokenFactory')->forUser($this);
            return ['jti' => $_obf_0D2B05231A38042E03250E09081D011D213D012A2B0B01->id];
        }
        public function addBalance($type, $summ, $payeer = false, $refund = true, $system = 'handpay', $update_level = true, $model = false)
        {
            if( !in_array($type, ['add', 'out']) ) 
            {
                $type = 'add';
            }
            if( $type == 'out' && $system == 'handpay' && ($this->count_tournaments > 0 || $this->count_happyhours > 0 || $this->count_refunds > 0 || $this->count_progress > 0 || $this->count_daily_entries > 0 || $this->count_invite > 0 || /* $this->count_welcomebonus > 0 || */ $this->count_smsbonus > 0 || $this->count_wheelfortune > 0 || $this->status == Support\Enum\UserStatus::BANNED) ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.money_withdrawal_is_denied', ['name' => $this->username])
                ]);
            }
            $summ = floatval($summ);
            $titles = [
                'handpay' => 'HP', 
                'invite' => 'IF', 
                'progress' => 'PB', 
                'tournament' => 'TB', 
                'daily_entry' => 'DE', 
                'refund' => 'Refund', 
                'interkassa' => 'IK', 
                'coinbase' => 'CB', 
                'btcpayserver' => 'BP', 
                'pincode' => 'PN', 
                'welcome_bonus' => 'WB', 
                'sms_bonus' => 'SB', 
                'wheelfortune' => 'WH'
            ];
            $bonusFields = [
                'invite' => 'invite', 
                'progress' => 'progress', 
                'tournament' => 'tournaments', 
                'daily_entry' => 'daily_entries', 
                'happyhour' => 'happyhours', 
                'refund' => 'refunds', 
                'welcome_bonus' => 'welcomebonus', 
                'sms_bonus' => 'smsbonus', 
                'wheelfortune' => 'wheelfortune'
            ];
            if( !$payeer ) 
            {
                if( \Illuminate\Support\Facades\Auth::check() ) 
                {
                    $payeer = User::find(auth()->user()->id);
                }
                else
                {
                    $payeer = User::find($this->parent_id);
                }
            }
            if( !$payeer ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.wrong_user')
                ]);
            }
            // allow admin or self or parent to act; no other restrictions
            if( !$summ || $summ <= 0 ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.wrong_sum')
                ]);
            }
            if( $type == 'out' && $this->balance < abs($summ) ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.not_enough_money_in_the_user_balance', [
                        'name' => $this->username, 
                        'balance' => $this->balance
                    ])
                ]);
            }
            $happyhour = false;
            $summ = ($type == 'out' ? -1 * $summ : $summ);
            $balance = $summ;
            if( $type == 'add' && $happyhour && $system == 'handpay' ) 
            {
                $multiplier = intval(str_replace('x', '', $happyhour->multiplier));
                $balance = $summ * $multiplier;
                Statistic::create([
                    'user_id' => $this->id, 
                    'payeer_id' => $payeer->id, 
                    'title' => 'HH ' . $happyhour->multiplier, 
                    'system' => 'happyhour', 
                    'type' => $type, 
                    'sum' => $balance, 
                    'hh_multiplier' => $multiplier, 
                    'sum2' => $summ, 
                    'shop_id' => ($this->hasRole('user') ? $this->shop_id : 0)
                ]);
                $this->increment('balance', $balance);
                $this->increment('happyhours', $summ * $multiplier);
                $this->increment('count_happyhours', $summ * $multiplier * $happyhour->wager);
                $this->increment('address', $summ * $multiplier - $summ);
                if( $type == 'out' ) 
                {
                    $this->increment('total_out', abs($summ));
                }
                else
                {
                    $this->increment('total_in', abs($summ));
                }
            }
            else
            {
                $title = (isset($titles[$system]) ? $titles[$system] : $system);
                if( $system == 'welcome_bonus' && $model ) 
                {
                    $title .= (' ' . mb_strtoupper($model->pay));
                }
                if( $system == 'sms_bonus' && $model ) 
                {
                    $title .= (' ' . mb_strtoupper($model->days));
                }
                if( $system == 'refund' && $model ) 
                {
                    $title .= (' ' . mb_strtoupper($model->percent) . '%');
                }
                Statistic::create([
                    'user_id' => $this->id, 
                    'payeer_id' => $payeer->id, 
                    'system' => $system, 
                    'title' => $title, 
                    'type' => $type, 
                    'sum' => abs($summ), 
                    'item_id' => ($model ? $model->id : null), 
                    'shop_id' => ($this->hasRole('user') ? $this->shop_id : 0)
                ]);
                $this->increment('balance', $balance);
                if( isset($bonusFields[$system]) ) 
                {
                    if( $model ) 
                    {
                        if( $system == 'refund' ) 
                        {
                            $this->update(['refunds' => 0]);
                        }
                        else
                        {
                            $this->increment($bonusFields[$system], $summ);
                        }
                        $this->increment('count_' . $bonusFields[$system], $summ * $model->wager);
                        $this->increment('address', $summ);
                    }
                }
                else
                {
                    $this->increment('count_balance', abs($summ));
                    if( $type == 'out' ) 
                    {
                        $this->increment('total_out', abs($summ));
                    }
                    else
                    {
                        $this->increment('total_in', abs($summ));
                    }
                }
                if( $this->hasRole('user') && $update_level ) 
                {
                    $this->update_level('balance', $summ);
                }
            }
            if( $this->hasRole('user') && !isset($bonusFields[$system]) ) 
            {
                if( $type == 'out' ) 
                {
                    $this->update(['refunds' => 0]);
                }
                else if( $refund ) 
                {
                    $refundSum = Lib\Functions::refunds($summ, $this->shop_id, $this->rating);
                    if( is_numeric($refund) ) 
                    {
                        $refundSum = ($summ * $refund) / 100;
                    }
                    $this->increment('refunds', $refundSum);
                }
            }
            if( $this->hasRole('user') ) 
            {
                Lib\WBLib::action($this->id);
            }
            return json_encode([
                'status' => 'success', 
                'message' => trans('app.balance_updated')
            ]);
        }


        public function addLimit($type, $summ, $payeer = false,  $system = 'handpay', $update_level = true, $model = false)
        {
            if( !in_array($type, [
                'add', 
                'out'
            ]) ) 
            {
                $type = 'add';
            }
            $summ = floatval($summ);
            if( !$payeer ) 
            {
                if( \Illuminate\Support\Facades\Auth::check() ) 
                {
                    $payeer = User::find(auth()->user()->id);
                }
                else
                {
                    $payeer = User::find($this->parent_id);
                }
            }
            if( !$payeer ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.wrong_user')
                ]);
            }
            // allow admin or self or parent to act; no other restrictions
            if( !$summ || $summ <= 0 ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.wrong_sum')
                ]);
            }
            if( $type == 'out' && $this->shop_limit < abs($summ) ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.not_enough_limit', [
                        'limit' => $this->shop_limit
                    ])
                ]);
            }
            $summ = ($type == 'out' ? -1 * $summ : $summ);
            if( $this->hasRole('user') ) 
            {
                Lib\WBLib::action($this->id);
            }
            $currentLimit = ($this->shop_limit === null ? 0 : $this->shop_limit);
            $this->update(['shop_limit' => $currentLimit + $summ]);
            return json_encode([
                'status' => 'success', 
                'message' => 'Limit updated'
            ]);
        }


        
        public function updateCountBalance($sum, $count_balance)
        {
            $_obf_0D331B22252306215B3F223237402E2B5B2C38210A0732 = $_obf_0D055C1F132D2A3F3B221F1C1A041C3534052303391F11 = $sum = abs(floatval($sum));
            if( $sum > 0 ) 
            {
                if( $count_balance > 0 ) 
                {
                    if( $count_balance < $_obf_0D331B22252306215B3F223237402E2B5B2C38210A0732 ) 
                    {
                        $_obf_0D055C1F132D2A3F3B221F1C1A041C3534052303391F11 = $_obf_0D331B22252306215B3F223237402E2B5B2C38210A0732 - $count_balance;
                        $this->decrement('count_balance', $count_balance);
                    }
                    else
                    {
                        $this->decrement('count_balance', $_obf_0D331B22252306215B3F223237402E2B5B2C38210A0732);
                    }
                }
                if( $count_balance == 0 || $count_balance < $_obf_0D331B22252306215B3F223237402E2B5B2C38210A0732 ) 
                {
                    foreach( [
                        'count_tournaments', 
                        'count_happyhours', 
                        'count_refunds', 
                        'count_progress', 
                        'count_daily_entries', 
                        'count_invite', 
                        'count_welcomebonus', 
                        'count_smsbonus', 
                        'count_wheelfortune'
                    ] as $field ) 
                    {
                        $value = floatval($this->$field);
                        if( $value > 0 ) 
                        {
                            if( $_obf_0D055C1F132D2A3F3B221F1C1A041C3534052303391F11 == 0 ) 
                            {
                                break;
                            }
                            else
                            {
                                if( $value < $_obf_0D055C1F132D2A3F3B221F1C1A041C3534052303391F11 ) 
                                {
                                    $_obf_0D055C1F132D2A3F3B221F1C1A041C3534052303391F11 = $_obf_0D055C1F132D2A3F3B221F1C1A041C3534052303391F11 - $value;
                                    $this->decrement($field, $value);
                                }
                                else
                                {
                                    $this->decrement($field, $_obf_0D055C1F132D2A3F3B221F1C1A041C3534052303391F11);
                                    $_obf_0D055C1F132D2A3F3B221F1C1A041C3534052303391F11 = 0;
                                }
                            }
                        }
                    }
                }
            }
            return $this->count_balance;
        }
        public function update_level($type, $sum)
        {
            if( !($this->shop && $this->shop->progress_active) ) 
            {
                return false;
            }
            $progress = Progress::where([
                'rating' => $this->rating + 1, 
                'shop_id' => $this->shop_id
            ])->first();
            if( $progress ) 
            {
                $_obf_0D2C1707150E2E382C0B292C181C0D0703112607020301 = ProgressUser::where([
                    'user_id' => $this->id, 
                    'rating' => $progress->rating
                ])->orderBy('id', 'DESC')->first();
                if( !$_obf_0D2C1707150E2E382C0B292C181C0D0703112607020301 ) 
                {
                    $_obf_0D2C1707150E2E382C0B292C181C0D0703112607020301 = ProgressUser::create([
                        'user_id' => $this->id, 
                        'progress_id' => $progress->id, 
                        'rating' => $progress->rating
                    ]);
                }
                if( $type == 'balance' ) 
                {
                    if( $progress->type == 'one_pay' && $progress->sum <= $sum ) 
                    {
                        $_obf_0D2C1707150E2E382C0B292C181C0D0703112607020301->update(['sum' => $sum]);
                    }
                    if( $progress->type == 'sum_pay' ) 
                    {
                        $_obf_0D2C1707150E2E382C0B292C181C0D0703112607020301->increment('sum', $sum);
                    }
                }
                if( $type == 'bet' && (double)$progress->bet <= (double)$sum ) 
                {
                    $_obf_0D2C1707150E2E382C0B292C181C0D0703112607020301->increment('spins');
                }
                if( $progress->spins <= $_obf_0D2C1707150E2E382C0B292C181C0D0703112607020301->spins && $progress->sum <= $_obf_0D2C1707150E2E382C0B292C181C0D0703112607020301->sum ) 
                {
                    $this->increment('rating');
                    if( $progress->bonus > 0 ) 
                    {
                        $payeer = User::find($this->parent_id);
                        $this->addBalance('add', $progress->bonus, $payeer, false, 'progress', false, $progress);
                        Message::create([
                            'user_id' => $this->id, 
                            'type' => 'progress', 
                            'value' => $progress->bonus, 
                            'shop_id' => $this->shop_id
                        ]);
                    }
                }
            }
            return true;
        }
        public function distributor()
        {
            $distributor = User::find($this->id);
            if( !$distributor->hasRole('distributor') ) 
            {
                return [];
            }
            $return = [
                'href' => route('backend.user.edit', ['user' => $distributor->id], false), 
                'text' => ($distributor->username ?: trans('app.n_a')), 
                'balance' => $distributor->balance, 
                'rowspan' => $distributor->getRowspan(), 
                'shops' => []
            ];
            if( count($distributor->shops()) && ($shops = $distributor->rel_shops) ) 
            {
                foreach( $shops as $shop ) 
                {
                    if( $shop = $shop->shop ) 
                    {
                        $return['shops'][$shop->id] = [
                            'href' => route('backend.shop.edit', ['shop' => $shop->id], false), 
                            'text' => $shop->name, 
                            'balance' => $shop->balance, 
                            'rowspan' => $shop->getRowspan(), 
                            'managers' => []
                        ];
                        if( ($managers = $shop->getUsersByRole('manager')) && count($managers) ) 
                        {
                            foreach( $managers as $manager ) 
                            {
                                $return['shops'][$shop->id]['managers'][$manager->id] = [
                                    'href' => route('backend.user.edit', ['user' => $manager->id], false), 
                                    'text' => ($manager->username ?: trans('app.n_a')), 
                                    'rowspan' => $manager->getRowspan(), 
                                    'cashiers' => []
                                ];
                                if( $cashiers = $manager->getInnerUsers() ) 
                                {
                                    foreach( $cashiers as $cashier ) 
                                    {
                                        $return['shops'][$shop->id]['managers'][$manager->id]['cashiers'][$cashier->id] = [
                                            'href' => route('backend.user.edit', ['user' => $cashier->id], false), 
                                            'text' => ($cashier->username ?: trans('app.n_a')), 
                                            'balance' => User::where([
                                                'role_id' => 1, 
                                                'shop_id' => $cashier->shop_id
                                            ])->sum('balance'), 
                                            'href2' => route('backend.profile.setshop', [
                                                'shop_id' => $shop->id, 
                                                'to' => route('backend.user.list', ['role' => 1], false)
                                            ], false), 
                                            'text2' => '>> ' . __('app.users')
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $return;
        }
    }

}
