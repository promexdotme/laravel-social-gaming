<header class="main-header">
    <div class="container">
        <div class="logo">
            <a href="{{ route('frontend.game.list') }}">
                <img src="/woocasino/images/logo.png" alt="{{ settings('app_name') }}" style="max-height: 40px;">
            </a>
        </div>
        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="{{ route('frontend.game.list') }}" class="{{ Request::is('/') ? 'active' : '' }}">Games</a>
                </li>
                @if(Auth::check())
                    <li><a href="#" class="open-modal" data-target="modal-profile">Profile</a></li>
                    <li><a href="{{ route('frontend.auth.logout') }}">Logout</a></li>
                @else
                    <li><a href="#" class="open-modal" data-target="modal-login">Login</a></li>
                    <li><a href="#" class="open-modal" data-target="modal-register">Register</a></li>
                @endif
            </ul>
        </nav>
    </div>
</header>