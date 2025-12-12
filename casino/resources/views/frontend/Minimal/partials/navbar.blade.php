<header class="main-header">
    <div class="container header-bar">
        <a href="{{ route('frontend.game.list') }}" class="brand">
            <span class="brand-mark">
                <img src="/minimal/logo.png" alt="{{ settings('app_name') }} logo">
            </span>
            <span class="brand-text">
                <span class="brand-title">{{ settings('app_name') }}</span>
                <span class="brand-sub">Lite lobby</span>
            </span>
        </a>

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
