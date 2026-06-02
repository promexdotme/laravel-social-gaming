<header class="main-header">
    <div class="container header-bar">
        <a href="{{ route('frontend.game.list') }}" class="brand">
            <span class="brand-mark">
                <img src="/minimal/logo.png" alt="{{ settings('app_name') }} logo" style="max-height: 40px; width: auto; display: block;">
            </span>
            <span class="brand-text">
                <span class="brand-title">{{ settings('app_name') }}</span>
            </span>
        </a>

        <ul class="nav-list" style="display: flex; gap: 30px; list-style: none; margin: 0; padding: 0;">
            <li><a href="{{ route('frontend.game.list') }}" class="{{ Route::is('frontend.game.list*') ? 'active' : '' }}" style="font-weight: 600; font-size: 1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; padding: 5px 0;">Casino</a></li>
            <li><a href="{{ route('frontend.sports.index') }}" class="{{ Route::is('frontend.sports*') ? 'active' : '' }}" style="font-weight: 600; font-size: 1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; padding: 5px 0;">Sports</a></li>
        </ul>

        <div class="header-actions">
            <div class="status-chip">
                <span class="pulse-dot"></span>
                <span>{{ Auth::check() ? 'Secure session' : 'Guest mode' }}</span>
            </div>
            <button class="member-button solid open-modal" data-target="{{ Auth::check() ? 'modal-profile' : 'modal-login' }}">
                <span class="member-label">{{ Auth::check() ? 'Member' : 'Log in' }}</span>
                <span class="member-sub">
                    @if(Auth::check())
                        {{ '@' . (Auth::user()->username ?? Auth::user()->email ?? 'player') }}
                    @else
                        Access your account
                    @endif
                </span>
            </button>
        </div>
    </div>
</header>
