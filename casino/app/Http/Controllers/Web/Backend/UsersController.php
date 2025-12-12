<?php

namespace VanguardLTE\Http\Controllers\Web\Backend;

use Illuminate\Http\Request;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Repositories\User\UserRepository;
use VanguardLTE\Repositories\Session\SessionRepository;
use VanguardLTE\User;
use VanguardLTE\ShopUser;
use VanguardLTE\Support\Enum\UserStatus;
use jeremykenedy\LaravelRoles\Models\Role;

class UsersController extends Controller
{
    private $users;
    private $max_users = 10000000;

    public function __construct(UserRepository $users)
    {
        $this->middleware(['auth', '2fa']);
        $this->users = $users;
    }

    public function index(Request $request)
    {
        $statuses = ['' => trans('app.all')] + UserStatus::lists();
        $roles = Role::whereIn('slug', ['admin', 'user'])->pluck('name', 'id');
        $roles->prepend(trans('app.all'), '0');

        $users = User::orderBy('created_at', 'DESC');
        if ($request->search != '') {
            $request->search = str_replace('_', '\_', $request->search);
            $users = $users->where('username', 'like', '%' . $request->search . '%');
        }
        if ($request->status != '') {
            $users = $users->where('status', $request->status);
        }
        if ($request->role) {
            $users = $users->where('role_id', $request->role);
        }
        if ($request->active) {
            if ($request->active == 1) {
                $users = $users->whereHas('sessions');
            } else {
                $users = $users->whereDoesntHave('sessions');
            }
        }
        $activeIds = $users->pluck('id');
        $activeUsers = $activeIds->count() ? User::whereIn('id', $activeIds)->whereHas('sessions')->pluck('id') : collect();
        $users = $users->paginate(20)->withQueryString();
        $happyhour = false;
        return view('backend.user.list', compact('users', 'statuses', 'roles', 'happyhour', 'activeUsers'));
    }

    public function get_balance()
    {
        $users = User::orderBy('created_at', 'DESC');
        if (!auth()->user()->hasRole('admin')) {
            $users = $users->where('id', auth()->user()->id);
        } else {
            $users = $users->where('id', '!=', auth()->user()->id);
        }
        $users = $users->get();
        $data = [];
        foreach ($users as $user) {
            $data[$user->id] = [
                'balance' => number_format(floatval($user->balance), 2, '.', ''),
                'shop_limit' => $user->shop_limit
            ];
        }
        return json_encode($data);
    }

    public function tree()
    {
        return redirect()->route('backend.user.list')->withSuccess('Hierarchy simplified: only admin and users remain.');
    }

    public function view(User $user, \VanguardLTE\Repositories\Activity\ActivityRepository $activities)
    {
        $userActivities = $activities->getLatestActivitiesForUser($user->id, 10);
        return view('backend.user.view', compact('user', 'userActivities'));
    }

    public function create()
    {
        $shop = \VanguardLTE\Shop::find(auth()->user()->shop_id);
        $happyhour = false;
        if ($shop && $shop->happyhours_active) {
            $happyhour = \VanguardLTE\HappyHour::where([
                'shop_id' => auth()->user()->shop_id,
                'time' => date('G')
            ])->first();
        }
        $roles = Role::whereIn('slug', ['admin', 'user'])->pluck('name', 'id');
        $statuses = UserStatus::lists();
        $shops = auth()->user()->shops();
        $availibleUsers = User::get();
        return view('backend.user.add', compact('roles', 'statuses', 'shops', 'availibleUsers', 'happyhour'));
    }

    public function store(\VanguardLTE\Http\Requests\User\CreateUserRequest $request)
    {
        $data = $request->only([
            'email',
            'username',
            'language',
            'status',
            'shop_id',
            'is_blocked',
            'password',
            'password_confirmation'
        ]) + ['status' => UserStatus::ACTIVE];

        if (isset($data['email']) && ($return = \VanguardLTE\Lib\Filter::domain_filtered($data['email']))) {
            return redirect()->back()->withErrors([__('app.blocked_domain_zone', ['zone' => $return['domain']])]);
        }
        if (trim($data['username']) == '') {
            $data['username'] = null;
        }
        if (!$request->parent_id) {
            $data['parent_id'] = auth()->user()->id;
        }
        $role = Role::where('slug', 'user')->first();
        $data['role_id'] = $role ? $role->id : 1;
        $user = $this->users->create($data + ['status' => UserStatus::ACTIVE]);
        $user->detachAllRoles();
        if ($role) {
            $user->attachRole($role);
        }
        if ($request->shop_id && $request->shop_id > 0 && !empty($request->shop_id)) {
            ShopUser::create([
                'shop_id' => $request->shop_id,
                'user_id' => $user->id
            ]);
        }
        if ($request->balance && $request->balance > 0) {
            $user->addBalance('add', $request->balance);
        }
        return redirect()->route('backend.user.list')->withSuccess(trans('app.user_created'));
    }

    public function massadd(Request $request)
    {
        if (isset($request->count) && is_numeric($request->count) && isset($request->balance) && is_numeric($request->balance)) {
            $role = Role::where('slug', 'user')->first();
            for ($i = 0; $i < $request->count; $i++) {
                $number = rand(111111111, 999999999);
                $data = [
                    'username' => $number,
                    'password' => $number,
                    'role_id' => ($role ? $role->id : 1),
                    'status' => UserStatus::ACTIVE,
                    'parent_id' => auth()->user()->id,
                    'shop_id' => auth()->user()->shop_id
                ];
                $newUser = $this->users->create($data);
                if ($role) {
                    $newUser->attachRole($role);
                }
                ShopUser::create([
                    'shop_id' => auth()->user()->shop_id,
                    'user_id' => $newUser->id
                ]);
                if ($request->balance > 0) {
                    $newUser->addBalance('add', $request->balance);
                }
            }
        }
        return redirect()->route('backend.user.list')->withSuccess(trans('app.user_created'));
    }

    public function edit(Request $request, \VanguardLTE\Repositories\Activity\ActivityRepository $activitiesRepo, User $user)
    {
        $edit = true;
        $roles = Role::whereIn('slug', ['admin', 'user'])->pluck('name', 'id');
        $statuses = UserStatus::lists();
        $shops = $user->shops();
        $shop = \VanguardLTE\Shop::find(auth()->user()->shop_id);
        $userActivities = \VanguardLTE\Services\Logging\UserActivity\Activity::where([
            'user_id' => $user->id,
            'type' => 'user'
        ])->orderBy('created_at', 'DESC')->paginate(30)->withQueryString();
        $hasActivities = $this->hasActivities($user);
        $langs = [];
        foreach (glob(resource_path() . '/lang/*', GLOB_ONLYDIR) as $fileinfo) {
            $dirname = basename($fileinfo);
            $langs[$dirname] = $dirname;
        }
        if ($user->sms_token != '') {
            $now = \Carbon\Carbon::now();
            $times = $now->diffInSeconds(\Carbon\Carbon::parse($user->sms_token_date), false);
            if ($times <= 0) {
                $user->update([
                    'phone' => '',
                    'phone_verified' => 0,
                    'sms_token' => ''
                ]);
            }
        }
        $google2fa = app('pragmarx.google2fa');
        $QR_Image = '';
        $secret = $user->google2fa_secret;
        if ($user->google2fa_enable) {
            $secret = $google2fa->generateSecretKey();
            $QR_Image = $google2fa->getQRCodeInline(config('app.name'), $user->email, $secret);
        }
        $happyhour = false;
        if ($shop && $shop->happyhours_active) {
            $happyhour = \VanguardLTE\HappyHour::where([
                'shop_id' => auth()->user()->shop_id,
                'time' => date('G')
            ])->first();
        }
        return view('backend.user.edit', compact('edit', 'user', 'roles', 'statuses', 'shops', 'userActivities', 'hasActivities', 'langs', 'QR_Image', 'secret', 'happyhour'));
    }

    public function send_phone_code()
    {
        $code = rand(11111, 99999);
        $sender = \VanguardLTE\Lib\SMS_sender::send(auth()->user()->phone, 'Verification code: ' . $code, auth()->user()->id);
        if (isset($sender['error'])) {
            if (isset($sender['text'])) {
                return redirect()->back()->withErrors($sender['text']);
            }
            return redirect()->back()->withErrors('Error sending message');
        }
        if (!isset($sender['success'])) {
            return redirect()->back()->withErrors(__('app.something_went_wrong'));
        }
        if (!$sender['success']) {
            return redirect()->back()->withErrors($sender['message']);
        }
        \VanguardLTE\SMS::create([
            'user_id' => auth()->user()->id,
            'message' => $code,
            'message_id' => $sender['message_id'],
            'shop_id' => auth()->user()->shop_id,
            'type' => 'verification',
            'status' => 'Sent'
        ]);
        auth()->user()->update([
            'sms_token' => $code,
            'sms_token_date' => \Carbon\Carbon::now()->addMinutes(settings('smsto_time'))
        ]);
        return redirect()->back()->withSuccess('Code sent');
    }

    public function updateDetails(User $user, \VanguardLTE\Http\Requests\User\UpdateDetailsRequest $request, SessionRepository $sessionRepository)
    {
        $google2fa = app('pragmarx.google2fa');
        $data = $request->only([
            'email',
            'username',
            'language',
            'shop_id',
            'status',
            'is_blocked',
            'password',
            'password_confirmation',
            'google2fa_enable'
        ]);
        if (isset($request->secret_key) && isset($request->google_2fa_code) && $request->google_2fa_code != '') {
            $code = $request->google_2fa_code;
            $key = $user->google2fa_secret;
            if ($user->google2fa_secret == null) {
                $key = $request->secret_key;
            }
            $verify = $google2fa->verifyGoogle2FA($key, $code);
            if ($verify) {
                if ($user->google2fa_enable) {
                    $user->update(['google2fa_secret' => $key]);
                } else {
                    $user->update([
                        'google2fa_secret' => null,
                        'google2fa_enable' => 0
                    ]);
                }
                $google2fa->logout();
            } else {
                return redirect()->route('backend.user.edit', $user->id)->withInput(['google_tab' => true])->withErrors(['Code is wrong']);
            }
        }
        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'username' => 'required|unique:users,username,' . $user->id,
            'email' => 'nullable|unique:users,email,' . $user->id,
            'phone' => 'nullable|unique:users,phone,' . $user->id
        ]);
        if ($validator->fails()) {
            return redirect()->route('backend.user.edit', $user->id)->withErrors($validator)->withInput();
        }
        if (empty($data['password']) || empty($data['password_confirmation'])) {
            unset($data['password']);
            unset($data['password_confirmation']);
        }
        if (isset($data['is_blocked'])) {
            \DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->update([
                'remember_token' => null,
                'is_blocked' => $data['is_blocked']
            ]);
        }
        if ($request->status != $user->status) {
            if ($request->status == UserStatus::ACTIVE && $user->status == UserStatus::BANNED) {
                event(new \VanguardLTE\Events\User\UserUnBanned($user));
            }
            if ($request->status == UserStatus::ACTIVE && $user->status == UserStatus::UNCONFIRMED) {
                event(new \VanguardLTE\Events\User\UserConfirmed($user));
            }
            if ($request->status == UserStatus::BANNED) {
                event(new \VanguardLTE\Events\User\Banned($user));
            }
        }
        if (isset($data['email']) && ($return = \VanguardLTE\Lib\Filter::domain_filtered($data['email']))) {
            return redirect()->route('backend.user.edit', $user->id)->withErrors([__('app.blocked_domain_zone', ['zone' => $return['domain']])]);
        }
        if (isset($request->phone) && $request->phone) {
            $phone = preg_replace('/[^0-9]/', '', $request->phone);
            $code = null;
            if ($phone != '' && !$user->phone) {
                $code = rand(1111, 9999);
                $data['phone'] = $phone;
            }
            if ($user->phone && $user->phone != $phone && !$user->phone_verified) {
                $code = rand(1111, 9999);
                $data['phone'] = $phone;
            }
            if ($user->phone_verified && auth()->user()->hasRole('admin') && $user->phone != $phone) {
                $code = rand(1111, 9999);
                $data['phone'] = $phone;
                $data['phone_verified'] = 0;
            }
            if ($code) {
                $sender = \VanguardLTE\Lib\SMS_sender::send($phone, 'Verification code: ' . $code, $user->id);
                $this->users->update($user->id, [
                    'sms_token' => $code,
                    'sms_token_date' => \Carbon\Carbon::now()->addMinutes(settings('smsto_time'))
                ]);
                if (isset($sender['message_id'])) {
                    \VanguardLTE\SMS::create([
                        'user_id' => $user->id,
                        'message' => $code,
                        'message_id' => $sender['message_id'],
                        'shop_id' => $user->shop_id,
                        'type' => 'verification',
                        'status' => 'Sent'
                    ]);
                }
            }
        } else {
            $data['phone'] = '';
            $data['phone_verified'] = 0;
            $data['sms_token'] = null;
        }
        $this->users->update($user->id, $data);
        if ($request->shops && count($request->shops)) {
            foreach ($request->shops as $shop) {
                ShopUser::create([
                    'shop_id' => $shop,
                    'user_id' => $user->id
                ]);
            }
        }
        if ($request->sms_token) {
            if ($request->sms_token == $user->sms_token) {
                $now = \Carbon\Carbon::now();
                $seconds = $now->diffInSeconds(\Carbon\Carbon::parse($user->sms_token_date), false);
                if ($seconds <= 0) {
                    return redirect()->route('backend.user.edit', $user->id)->withErrors(trans('app.time_is_up'));
                }
                $user->update([
                    'sms_token' => null,
                    'phone_verified' => 1
                ]);
                return redirect()->route('backend.user.edit', $user->id)->withSuccess(trans('app.phone_verified'));
            } else {
                return redirect()->route('backend.user.edit', $user->id)->withErrors(trans('app.phone_verification_code_is_wrong'));
            }
        }
        event(new \VanguardLTE\Events\User\UpdatedByAdmin($user));
        if ($this->userIsBanned($user, $request)) {
            event(new \VanguardLTE\Events\User\Banned($user));
        }
        return redirect()->route('backend.user.edit', $user->id)->withSuccess(trans('app.user_updated'));
    }

    public function updateBalance(Request $request)
    {
        $data = $request->all();
        if (!array_get($data, 'type')) {
            $data['type'] = 'add';
        }
        $user = User::find($request->user_id);
        if (!$user) {
            return redirect()->back()->withErrors([__('app.wrong_user')]);
        }
        $request->summ = floatval($request->summ);
        if ($request->all && $request->all == '1') {
            $request->summ = $user->balance;
        }
        $result = $user->addBalance($data['type'], $request->summ);
        $result = json_decode($result, true);
        if ($data['type'] == 'add') {
            event(new \VanguardLTE\Events\User\MoneyIn($user, $request->summ));
        } else {
            event(new \VanguardLTE\Events\User\MoneyOut($user, $request->summ));
        }
        if ($result['status'] == 'error') {
            return redirect()->back()->withErrors([$result['message']]);
        }
        return redirect()->back()->withSuccess($result['message']);
    }

    public function updateLimit(Request $request)
    {
        $data = $request->all();
        if (!array_get($data, 'type')) {
            $data['type'] = 'add';
        }
        $user = User::find($request->user_id);

        if (!$user) {
            return redirect()->back()->withErrors([__('app.wrong_user')]);
        }
        $request->summ = floatval($request->summ);
        if ($request->all && $request->all == '1') {
            $request->summ = $user->balance;
        }
        $result = $user->addLimit($data['type'], $request->summ);
        $result = json_decode($result, true);
        if ($result['status'] == 'error') {
            return redirect()->back()->withErrors([$result['message']]);
        }
        return redirect()->back()->withSuccess($result['message']);
    }

    public function statistics(User $user, Request $request)
    {
        $statistics = \VanguardLTE\Statistic::where('user_id', $user->id)->orderBy('created_at', 'DESC')->paginate(20)->withQueryString();
        return view('backend.stat.pay_stat', compact('user', 'statistics'));
    }

    private function userIsBanned(User $user, Request $request)
    {
        return $user->status != $request->status && $request->status == UserStatus::BANNED;
    }

    public function specauth(Request $request, User $user)
    {
        if (!$user) {
            return redirect()->route('backend.auth.login')->withErrors([trans('app.wrong_user')]);
        }
        if ($user->auth_token == $request->token && auth()->user()->hasRole('admin') && !$user->hasRole('admin')) {
            if (auth()->user()->shop && auth()->user()->shop->pending) {
                return redirect()->route('backend.dashboard')->withErrors(__('app.shop_is_creating'));
            }
            session(['beforeUser' => auth()->user()->id]);
            \Illuminate\Support\Facades\Auth::loginUsingId($user->id);
            if (!$user->hasRole('user')) {
                if (!auth()->user()->hasPermission('dashboard')) {
                    return redirect()->route('backend.user.list');
                }
                return redirect()->route('backend.dashboard');
            }
            return redirect()->intended();
        }
        return redirect()->route('backend.auth.login')->withErrors([trans('app.wrong_user')]);
    }

    public function back_login(Request $request)
    {
        if ($request->session()->exists('beforeUser')) {
            \Illuminate\Support\Facades\Auth::loginUsingId(session('beforeUser'));
            $request->session()->forget('beforeUser');
            return redirect()->route('backend.dashboard');
        }
        return redirect()->route('backend.dashboard')->withErrors([trans('app.wrong_user')]);
    }

    public function updateAvatar(User $user, \VanguardLTE\Services\Upload\UserAvatarManager $avatarManager, Request $request)
    {
        $this->validate($request, ['avatar' => 'image']);
        $name = $avatarManager->uploadAndCropAvatar($user, $request->file('avatar'), $request->get('points'));
        if ($name) {
            $this->users->update($user->id, ['avatar' => $name]);
            event(new \VanguardLTE\Events\User\UpdatedByAdmin($user));
            return redirect()->route('backend.user.edit', $user->id)->withSuccess(trans('app.avatar_changed'));
        }
        return redirect()->route('backend.user.edit', $user->id)->withErrors(trans('app.avatar_not_changed'));
    }

    public function updateAvatarExternal(User $user, Request $request, \VanguardLTE\Services\Upload\UserAvatarManager $avatarManager)
    {
        $avatarManager->deleteAvatarIfUploaded($user);
        $this->users->update($user->id, ['avatar' => $request->get('url')]);
        event(new \VanguardLTE\Events\User\UpdatedByAdmin($user));
        return redirect()->route('backend.user.edit', $user->id)->withSuccess(trans('app.avatar_changed'));
    }

    public function updateLoginDetails(User $user, \VanguardLTE\Http\Requests\User\UpdateLoginDetailsRequest $request, SessionRepository $sessionRepository)
    {
        $data = $request->all();
        if (trim($data['password']) == '') {
            unset($data['password']);
            unset($data['password_confirmation']);
        }
        $this->users->update($user->id, $data);
        event(new \VanguardLTE\Events\User\UpdatedByAdmin($user));
        return redirect()->route('backend.user.edit', $user->id)->withSuccess(trans('app.login_updated'));
    }

    public function delete(User $user)
    {
        if ($user->id == auth()->user()->id) {
            return redirect()->route('backend.user.list')->withErrors(trans('app.you_cannot_delete_yourself'));
        }
        if ($user->balance > 0) {
            return redirect()->route('backend.user.list')->withErrors([trans('app.balance_not_zero')]);
        }
        $user->detachAllRoles();
        \VanguardLTE\Statistic::where('user_id', $user->id)->delete();
        \VanguardLTE\StatisticAdd::where('user_id', $user->id)->delete();
        \VanguardLTE\ShopUser::where('user_id', $user->id)->delete();
        \VanguardLTE\StatGame::where('user_id', $user->id)->delete();
        \VanguardLTE\GameLog::where('user_id', $user->id)->delete();
        \VanguardLTE\UserActivity::where('user_id', $user->id)->delete();
        \VanguardLTE\Session::where('user_id', $user->id)->delete();
        \VanguardLTE\Info::where('user_id', $user->id)->delete();
        $user->delete();
        return redirect()->route('backend.user.list')->withSuccess(trans('app.user_deleted'));
    }

    public function hard_delete(User $user)
    {
        if ($user->id == auth()->user()->id) {
            return redirect()->route('backend.user.list')->withErrors(trans('app.you_cannot_delete_yourself'));
        }
        $user->detachAllRoles();
        \VanguardLTE\Statistic::where('user_id', $user->id)->delete();
        \VanguardLTE\StatisticAdd::where('user_id', $user->id)->delete();
        \VanguardLTE\ShopUser::where('user_id', $user->id)->delete();
        \VanguardLTE\StatGame::where('user_id', $user->id)->delete();
        \VanguardLTE\GameLog::where('user_id', $user->id)->delete();
        \VanguardLTE\UserActivity::where('user_id', $user->id)->delete();
        \VanguardLTE\Session::where('user_id', $user->id)->delete();
        \VanguardLTE\Info::where('user_id', $user->id)->delete();
        $user->delete();
        return redirect()->route('backend.user.list')->withSuccess(trans('app.user_deleted'));
    }

    public function hasActivities($user)
    {
        $stats = \VanguardLTE\Statistic::where('user_id', $user->id)->count();
        if ($stats) {
            return true;
        }
        $stats = \VanguardLTE\StatGame::where('user_id', $user->id)->count();
        if ($stats) {
            return true;
        }
        $open_shifts = \VanguardLTE\OpenShift::where('user_id', $user->id)->count();
        if ($open_shifts) {
            return true;
        }
        return false;
    }

    public function sessions(User $user, SessionRepository $sessionRepository)
    {
        $adminView = true;
        $sessions = $sessionRepository->getUserSessions($user->id);
        return view('backend.user.sessions', compact('sessions', 'user', 'adminView'));
    }

    public function invalidateSession(User $user, $session, SessionRepository $sessionRepository)
    {
        $sessionRepository->invalidateSession($session->id);
        return redirect()->route('backend.user.sessions', $user->id)->withSuccess(trans('app.session_invalidated'));
    }

    public function action($action)
    {
        abort(404);
    }
}
