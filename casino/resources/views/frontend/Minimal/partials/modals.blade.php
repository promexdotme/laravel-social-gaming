<!-- Auth Modals -->
<div id="modal-login" class="modal">
    <div class="modal-content modal-surface">
        <span class="close-modal" aria-label="Close">&times;</span>
        <div class="modal-heading">
            <p class="eyebrow">Member</p>
            <h2>Log in</h2>
            <p class="muted">Access the member hub to launch games, manage profile, and top up.</p>
        </div>
        <form id="login-form">
            @csrf
            <div class="form-group">
                <label for="login-username">Username</label>
                <input type="text" id="login-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Login</button>
            <div class="form-message error"></div>
        </form>
        <p class="muted switcher">No account? <a href="#" class="open-modal" data-target="modal-register">Create one</a></p>
    </div>
</div>

<div id="modal-register" class="modal">
    <div class="modal-content modal-surface">
        <span class="close-modal" aria-label="Close">&times;</span>
        <div class="modal-heading">
            <p class="eyebrow">Member</p>
            <h2>Create account</h2>
            <p class="muted">Register to keep balance, history, and personalized picks.</p>
        </div>
        <form id="register-form">
            @csrf
            <div class="form-group">
                <label for="register-email">Email</label>
                <input type="email" id="register-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="register-username">Username</label>
                <input type="text" id="register-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="register-password">Password</label>
                <input type="password" id="register-password" name="password" required>
            </div>
            <div class="form-group">
                <label for="register-password-confirm">Confirm Password</label>
                <input type="password" id="register-password-confirm" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn-primary">Register</button>
            <div class="form-message error"></div>
        </form>
        <p class="muted switcher">Already registered? <a href="#" class="open-modal" data-target="modal-login">Log in</a></p>
    </div>
</div>

<!-- Profile / Member Hub Modal -->
<div id="modal-profile" class="modal">
    <div class="modal-content modal-wide">
        <span class="close-modal" aria-label="Close">&times;</span>
        <div class="member-header">
            <div>
                <p class="eyebrow">Member hub</p>
                <h2>{{ Auth::check() ? (Auth::user()->username ?? Auth::user()->email ?? 'Member') : 'Guest' }}</h2>
                <p class="muted">Profile, transactions, and top-up live here. More coming soon.</p>
            </div>
            <div class="member-actions">
                @if(Auth::check())
                    <a href="{{ route('frontend.auth.logout') }}" class="ghost-link">Logout</a>
                @else
                    <a href="#" class="ghost-link open-modal" data-target="modal-login">Login</a>
                @endif
            </div>
        </div>

        <div class="member-tabs">
            <button class="member-tab active" data-tab="member-profile">Profile</button>
            <button class="member-tab" data-tab="member-transactions">Transactions</button>
            <button class="member-tab" data-tab="member-topup">Top up</button>
        </div>

        <div class="member-panels">
            <div class="member-panel active" id="member-profile">
                <div id="profile-data" class="panel-surface">
                    <div class="loading">Loading...</div>
                </div>
            </div>
            <div class="member-panel" id="member-transactions">
                <div class="panel-surface" id="transactions-panel">
                    <div class="loading">Loading...</div>
                </div>
            </div>
            <div class="member-panel" id="member-topup">
                <div class="panel-surface">
                    <form id="topup-form">
                        @csrf
                        <div class="form-group">
                            <label for="topup-amount">Amount</label>
                            <input type="number" step="0.01" min="0.5" id="topup-amount" name="amount" placeholder="Enter amount" required>
                        </div>
                        <div class="form-group">
                            <label for="topup-driver">Gateway</label>
                            <select id="topup-driver" name="driver">
                                <option value="btcpay">BTC Pay</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">Pay</button>
                        <p class="muted" style="margin-top:8px;">You will be redirected to the gateway. Balance updates on successful payment.</p>
                        <div class="form-message" id="topup-message"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
