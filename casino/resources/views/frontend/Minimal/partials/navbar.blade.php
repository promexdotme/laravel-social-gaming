<!-- Left Sidebar Navigation (Desktop) -->
<aside class="hidden lg:flex flex-col w-sidebar-width h-screen sticky top-0 bg-[#0e121b]/95 border-r border-white/[0.07] backdrop-blur-xl z-50 p-5 flex-shrink-0">
    <!-- Brand Logo -->
    <div class="mb-8 flex items-center gap-3 px-2">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center shadow-lg shadow-primary/20 flex-shrink-0">
            <span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings: 'FILL' 1;">park</span>
        </div>
        <a href="{{ route('frontend.game.list') }}" class="flex flex-col no-underline">
            <span class="text-base font-extrabold tracking-tight text-white leading-tight">CASINO DU LIBAN</span>
            <span class="text-[10px] font-bold tracking-widest text-primary uppercase">SOCIAL GAMING</span>
        </a>
    </div>

    <!-- Main Navigation Links -->
    <nav class="flex-1 space-y-1.5 overflow-y-auto custom-scrollbar pr-1">
        <div class="text-[10px] font-bold uppercase tracking-wider text-on-surface-subtle px-3 py-1">Gaming Hub</div>

        <!-- Casino Lobby -->
        @if(settings('enable_casino_slots', '1') == '1')
        <a class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all group no-underline {{ Route::is('frontend.game.list*') && !request()->is('categories/cedar_games*') && !request()->is('categories/cedar_cards*') ? 'bg-primary/10 text-primary border border-primary/20 shadow-sm' : 'text-on-surface-muted hover:text-white hover:bg-white/[0.04]' }}" href="{{ route('frontend.game.list') }}">
            <span class="material-symbols-outlined text-xl {{ Route::is('frontend.game.list*') && !request()->is('categories/cedar_games*') && !request()->is('categories/cedar_cards*') ? 'text-primary' : 'text-on-surface-subtle group-hover:text-white' }} transition-colors" style="font-variation-settings: 'FILL' 1;">casino</span>
            <span>Casino Slots</span>
        </a>
        @endif

        <!-- CEDAR Originals -->
        @if(settings('enable_cedar_originals', '1') == '1')
        <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all group no-underline {{ request()->is('categories/cedar_games*') ? 'bg-accent-gold/10 text-accent-gold border border-accent-gold/25 shadow-sm' : 'text-on-surface-muted hover:text-white hover:bg-white/[0.04]' }}" href="/categories/cedar_games">
            <div class="flex items-center gap-3.5">
                <span class="material-symbols-outlined text-xl {{ request()->is('categories/cedar_games*') ? 'text-accent-gold' : 'text-accent-gold/80 group-hover:text-accent-gold' }} transition-colors" style="font-variation-settings: 'FILL' 1;">rocket_launch</span>
                <span>CEDAR Games</span>
            </div>
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-accent-gold/20 text-accent-gold uppercase tracking-wider">HOT</span>
        </a>
        <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all group no-underline {{ request()->is('categories/cedar_cards*') ? 'bg-primary/10 text-primary border border-primary/25 shadow-sm' : 'text-on-surface-muted hover:text-white hover:bg-white/[0.04]' }}" href="/categories/cedar_cards">
            <div class="flex items-center gap-3.5">
                <span class="material-symbols-outlined text-xl text-primary" style="font-variation-settings: 'FILL' 1;">style</span>
                <span>CEDAR Cards</span>
            </div>
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-primary/20 text-primary uppercase tracking-wider">NEW</span>
        </a>
        @endif

        <!-- Sportsbook / Battle Odds -->
        @if(settings('enable_sportsbook', '1') == '1')
        <a class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all group no-underline {{ Route::is('frontend.sports*') ? 'bg-secondary/10 text-secondary border border-secondary/20 shadow-sm' : 'text-on-surface-muted hover:text-white hover:bg-white/[0.04]' }}" href="{{ route('frontend.sports.index') }}">
            <span class="material-symbols-outlined text-xl {{ Route::is('frontend.sports*') ? 'text-secondary' : 'text-on-surface-subtle group-hover:text-white' }} transition-colors" style="font-variation-settings: 'FILL' 1;">sports_soccer</span>
            <span>Battle Odds</span>
        </a>
        @endif

        <!-- Lotto Jackpot Zone -->
        @if(settings('enable_lotto', '1') == '1')
        <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all group no-underline {{ Route::is('frontend.lotto*') ? 'bg-primary/10 text-primary border border-primary/20 shadow-sm' : 'text-on-surface-muted hover:text-white hover:bg-white/[0.04]' }}" href="{{ route('frontend.lotto.index') }}">
            <div class="flex items-center gap-3.5">
                <span class="material-symbols-outlined text-xl {{ Route::is('frontend.lotto*') ? 'text-primary' : 'text-on-surface-subtle group-hover:text-white' }} transition-colors" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
                <span>Jackpot Zone</span>
            </div>
            <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
        </a>
        @endif

        <!-- Future Vote Prediction Markets -->
        @if(settings('enable_predictions', '1') == '1')
        <a class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all group no-underline {{ Route::is('frontend.predictions*') ? 'bg-secondary/10 text-secondary border border-secondary/20 shadow-sm' : 'text-on-surface-muted hover:text-white hover:bg-white/[0.04]' }}" href="{{ route('frontend.predictions.index') }}">
            <span class="material-symbols-outlined text-xl {{ Route::is('frontend.predictions*') ? 'text-secondary' : 'text-on-surface-subtle group-hover:text-white' }} transition-colors" style="font-variation-settings: 'FILL' 1;">query_stats</span>
            <span>Future Vote</span>
        </a>
        @endif

        <!-- 3-Tier Affiliate & Referral Program -->
        <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all group no-underline {{ Route::is('frontend.affiliates*') ? 'bg-amber-500/10 text-amber-400 border border-amber-500/25 shadow-sm' : 'text-on-surface-muted hover:text-white hover:bg-white/[0.04]' }}" href="{{ route('frontend.affiliates.index') }}">
            <div class="flex items-center gap-3.5">
                <span class="material-symbols-outlined text-xl {{ Route::is('frontend.affiliates*') ? 'text-amber-400' : 'text-amber-400/80 group-hover:text-amber-400' }} transition-colors" style="font-variation-settings: 'FILL' 1;">groups</span>
                <span>Affiliates</span>
            </div>
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 uppercase tracking-wider">EARN</span>
        </a>

        <!-- VIP Club & Loyalty Vault -->
        <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all group no-underline {{ Route::is('frontend.vip*') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 shadow-sm' : 'text-on-surface-muted hover:text-white hover:bg-white/[0.04]' }}" href="{{ route('frontend.vip.index') }}">
            <div class="flex items-center gap-3.5">
                <span class="material-symbols-outlined text-xl {{ Route::is('frontend.vip*') ? 'text-emerald-400' : 'text-emerald-400/80 group-hover:text-emerald-400' }} transition-colors" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                <span>VIP Club</span>
            </div>
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 uppercase tracking-wider">VAULT</span>
        </a>
    </nav>

    <!-- Bottom Wallet & Profile Card -->
    <div class="mt-auto space-y-3 pt-4 border-t border-white/[0.07]">
        <!-- Coin Wallet Card -->
        <div class="bg-[#121622] p-3.5 rounded-2xl border border-white/[0.08] shadow-inner">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold text-on-surface-muted uppercase tracking-wider">Social Coins</span>
                <span class="text-[10px] font-bold text-accent-gold bg-accent-gold/10 px-2 py-0.5 rounded-full border border-accent-gold/20">FREE</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="font-mono-jet text-lg font-bold text-primary tracking-tight" id="user-coin-balance-sidebar">{{ Auth::check() ? number_format(Auth::user()->balance, 0) : '50,000' }}</span>
                <span class="text-xs font-bold text-primary-light">CEDARS</span>
            </div>
            <div class="mt-3 flex gap-2">
                <button id="btn-sidebar-refill" type="button" class="flex-1 bg-gradient-to-r from-primary to-primary-dark hover:from-primary-light hover:to-primary text-white py-2 rounded-xl font-bold transition-all text-xs tracking-wide uppercase shadow-md shadow-primary/20 flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    <span>Power Up</span>
                </button>
                @if(settings('enable_cashout', '1') == '1')
                <button type="button" class="flex-1 bg-white/[0.05] hover:bg-white/[0.1] text-accent-gold border border-accent-gold/30 py-2 rounded-xl font-bold transition-all text-xs tracking-wide uppercase shadow-sm flex items-center justify-center gap-1 open-modal" data-target="{{ Auth::check() ? 'modal-cashout' : 'modal-login' }}">
                    <span class="material-symbols-outlined text-sm">payments</span>
                    <span>Cashout</span>
                </button>
                @endif
            </div>
        </div>

        <!-- User Profile Chip -->
        <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-white/[0.04] transition-all cursor-pointer open-modal border border-transparent hover:border-white/[0.06]" data-target="{{ Auth::check() ? 'modal-profile' : 'modal-login' }}">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-surface to-surface-elevated border border-white/10 flex items-center justify-center font-bold text-primary text-sm shadow-sm flex-shrink-0">
                    {{ Auth::check() ? strtoupper(substr(Auth::user()->username ?? 'U', 0, 1)) : 'G' }}
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-bold text-xs text-white truncate max-w-[130px]">{{ Auth::check() ? (Auth::user()->username ?? Auth::user()->email) : 'Guest Player' }}</span>
                    <span class="text-[10px] {{ (Auth::check() && (in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE')))) ? 'text-amber-400 font-bold' : 'text-primary' }} flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full {{ (Auth::check() && (in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE')))) ? 'bg-amber-400 animate-ping' : 'bg-primary animate-pulse' }}"></span>
                        {{ Auth::check() ? ((in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE'))) ? 'Admin / Staff' : 'VIP Platinum') : 'Tap to Login' }}
                    </span>
                </div>
            </div>
            <span class="material-symbols-outlined text-on-surface-subtle text-lg">chevron_right</span>
        </div>

        @if(Auth::check() && (in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE'))))
        <!-- Desktop Sidebar Direct Admin Link -->
        <a href="{{ route('liteback.users.index') }}" target="_blank" class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-gradient-to-r from-amber-500/20 via-amber-600/10 to-[#121622] border border-amber-500/40 text-amber-300 hover:text-white hover:border-amber-400 hover:bg-amber-500/25 transition-all text-xs font-bold no-underline shadow-md shadow-amber-500/10 group">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg text-amber-400 group-hover:rotate-12 transition-transform">admin_panel_settings</span>
                <span class="tracking-wide uppercase">Admin Console</span>
            </div>
            <span class="text-[9px] font-mono-jet font-bold px-2 py-0.5 rounded bg-amber-400 text-black uppercase flex items-center gap-0.5">
                <span>LITEBACK</span>
                <span class="material-symbols-outlined text-[11px]">north_east</span>
            </span>
        </a>
        @endif
    </div>
</aside>

<!-- Sleek Mobile Top Header -->
<header class="lg:hidden h-16 px-4 flex items-center justify-between bg-[#0e121b]/95 border-b border-white/[0.08] backdrop-blur-xl w-full sticky top-0 z-40">
    <a href="{{ route('frontend.game.list') }}" class="flex items-center gap-2.5 no-underline min-w-0">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center shadow-md shadow-primary/20 flex-shrink-0">
            <span class="material-symbols-outlined text-white text-lg" style="font-variation-settings: 'FILL' 1;">park</span>
        </div>
        <span class="font-extrabold text-sm tracking-tight text-white truncate">CASINO LIBAN</span>
    </a>
    
    <div class="flex items-center gap-2 flex-shrink-0">
        @if(Auth::check() && (in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE'))))
        <a href="{{ route('liteback.users.index') }}" target="_blank" class="bg-amber-500/20 border border-amber-500/40 hover:bg-amber-500/30 text-amber-400 p-2 rounded-xl flex items-center justify-center no-underline transition-all shadow-sm shadow-amber-500/10" title="Open Liteback Admin Console">
            <span class="material-symbols-outlined text-base">admin_panel_settings</span>
        </a>
        @endif
        <!-- Balance Badge -->
        <div class="flex items-center gap-1 bg-[#161c2b] border border-white/[0.08] px-2.5 py-1.5 rounded-xl">
            <span class="font-mono-jet text-xs font-bold text-primary" id="user-coin-balance-mobile">{{ Auth::check() ? number_format(Auth::user()->balance, 0) : '50,000' }}</span>
            <span class="text-[10px] font-bold text-primary-light">C</span>
        </div>
        <!-- Quick Refill Button -->
        <button id="btn-mobile-refill" type="button" class="bg-gradient-to-r from-primary to-primary-dark text-white text-xs font-bold px-2.5 py-1.5 rounded-xl uppercase tracking-wide shadow-md shadow-primary/20 flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">add</span>
            <span>Refill</span>
        </button>
    </div>
</header>

<!-- Mobile Slide-Up Bottom Sheet Overlay -->
<div id="mobile-sheet-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[90] hidden transition-opacity duration-300"></div>

<!-- Mobile Slide-Up Sheet (Menu & User Profile Hub) -->
<div id="mobile-bottom-sheet" class="fixed left-0 right-0 bottom-0 max-h-[85vh] bg-[#121622]/98 border-t border-white/10 rounded-t-3xl backdrop-blur-2xl z-[100] transform translate-y-full transition-transform duration-300 flex flex-col p-5 shadow-2xl overflow-y-auto custom-scrollbar">
    <!-- Drag Bar -->
    <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-4 flex-shrink-0 cursor-pointer" id="sheet-drag-handle"></div>

    <!-- User Header in Sheet -->
    <div class="flex items-center justify-between mb-4 pb-4 border-b border-white/[0.08]">
        <div class="flex items-center gap-3 cursor-pointer open-modal flex-1" data-target="{{ Auth::check() ? 'modal-profile' : 'modal-login' }}">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-surface to-surface-elevated border border-white/10 flex items-center justify-center font-bold text-primary text-base shadow-sm flex-shrink-0">
                {{ Auth::check() ? strtoupper(substr(Auth::user()->username ?? 'U', 0, 1)) : 'G' }}
            </div>
            <div class="flex flex-col min-w-0">
                <span class="font-bold text-sm text-white truncate max-w-[180px]">{{ Auth::check() ? (Auth::user()->username ?? Auth::user()->email) : 'Guest Player' }}</span>
                <span class="text-xs {{ (Auth::check() && (in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE')))) ? 'text-amber-400 font-bold' : 'text-primary' }} flex items-center gap-1 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full {{ (Auth::check() && (in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE')))) ? 'bg-amber-400 animate-ping' : 'bg-primary animate-pulse' }}"></span>
                    {{ Auth::check() ? ((in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE'))) ? 'Admin / Staff' : 'VIP Platinum Member') : 'Tap to Login / Register' }}
                </span>
            </div>
        </div>

        <button id="btn-close-bottom-sheet" type="button" class="text-on-surface-subtle hover:text-white p-2 rounded-xl bg-white/[0.04] transition-colors">
            <span class="material-symbols-outlined text-xl">close</span>
        </button>
    </div>

    @if(Auth::check() && (in_array((int)Auth::user()->role_id, [2, 3, 4, 5, 6]) || Auth::user()->hasRole('admin') || Auth::user()->hasRole('manager') || (env('ADMIN_PHONE') && Auth::user()->phone == env('ADMIN_PHONE'))))
    <!-- Mobile Sheet Admin Direct Link -->
    <a href="{{ route('liteback.users.index') }}" target="_blank" class="w-full mb-4 flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-amber-500/25 via-amber-600/15 to-[#182030] border border-amber-500/40 text-amber-300 hover:text-white transition-all no-underline shadow-lg shadow-amber-500/10">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-amber-500/30 border border-amber-500/50 flex items-center justify-center text-amber-400">
                <span class="material-symbols-outlined text-lg">admin_panel_settings</span>
            </div>
            <div>
                <div class="text-xs font-bold text-amber-300 uppercase tracking-wide">Operator Backend</div>
                <div class="text-[10px] text-on-surface-subtle">Direct Access to Liteback Console</div>
            </div>
        </div>
        <span class="text-[10px] font-mono-jet font-bold px-2 py-1 rounded-lg bg-amber-400 text-black uppercase flex items-center gap-1">
            <span>OPEN</span>
            <span class="material-symbols-outlined text-xs">open_in_new</span>
        </span>
    </a>
    @endif

    <!-- Wallet Power Up Banner -->
    <div class="bg-[#182030] p-4 rounded-2xl mb-5 flex justify-between items-center border border-white/[0.08]">
        <div>
            <span class="text-[10px] font-bold text-on-surface-subtle uppercase tracking-wider block">Cedar Coins Balance</span>
            <div class="flex items-baseline gap-1 mt-0.5">
                <span class="font-mono-jet text-xl font-bold text-primary" id="user-coin-balance-sheet">{{ Auth::check() ? number_format(Auth::user()->balance, 0) : '50,000' }}</span>
                <span class="text-xs font-bold text-primary-light">CEDARS</span>
            </div>
        </div>
        <div class="flex gap-2">
            <button id="btn-sheet-refill" type="button" class="bg-gradient-to-r from-primary to-primary-dark text-white text-xs font-bold px-3 py-2 rounded-xl uppercase tracking-wider shadow-lg shadow-primary/25 transition-all">
                + Power Up
            </button>
            @if(settings('enable_cashout', '1') == '1')
            <button type="button" class="bg-white/[0.06] hover:bg-white/[0.12] text-accent-gold border border-accent-gold/30 text-xs font-bold px-3 py-2 rounded-xl uppercase tracking-wider transition-all open-modal" data-target="{{ Auth::check() ? 'modal-cashout' : 'modal-login' }}">
                Cashout
            </button>
            @endif
        </div>
    </div>

    <!-- Navigation Hub Grid -->
    <div class="grid grid-cols-2 gap-2.5 mb-5">
        <a href="{{ route('frontend.game.list') }}" class="p-3.5 rounded-xl flex items-center gap-3 no-underline border transition-all {{ Route::is('frontend.game.list*') && !request()->is('categories/cedar_games*') && !request()->is('categories/cedar_cards*') ? 'bg-primary/10 border-primary/30 text-primary' : 'bg-white/[0.02] border-white/[0.06] text-white hover:bg-white/[0.05]' }}">
            <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">casino</span>
            </div>
            <div class="flex flex-col">
                <span class="text-xs font-bold">Casino</span>
                <span class="text-[10px] text-on-surface-subtle">1,000+ Slots</span>
            </div>
        </a>
        <a href="/categories/cedar_cards" class="p-3.5 rounded-xl flex items-center gap-3 no-underline border transition-all {{ request()->is('categories/cedar_cards*') ? 'bg-primary/10 border-primary/30 text-primary' : 'bg-white/[0.02] border-white/[0.06] text-white hover:bg-white/[0.05]' }}">
            <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">style</span>
            </div>
            <div class="flex flex-col"><span class="text-xs font-bold">CEDAR Cards</span><span class="text-[10px] text-primary/80">Hi-Lo & Blackjack</span></div>
        </a>

        <a href="/categories/cedar_games" class="p-3.5 rounded-xl flex items-center gap-3 no-underline border transition-all {{ request()->is('categories/cedar_games*') ? 'bg-accent-gold/10 border-accent-gold/30 text-accent-gold' : 'bg-white/[0.02] border-white/[0.06] text-white hover:bg-white/[0.05]' }}">
            <div class="w-9 h-9 rounded-lg bg-accent-gold/10 flex items-center justify-center text-accent-gold flex-shrink-0">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">rocket_launch</span>
            </div>
            <div class="flex flex-col">
                <span class="text-xs font-bold">CEDAR 🚀</span>
                <span class="text-[10px] text-accent-gold/80">Crash Games</span>
            </div>
        </a>

        <a href="{{ route('frontend.sports.index') }}" class="p-3.5 rounded-xl flex items-center gap-3 no-underline border transition-all {{ Route::is('frontend.sports*') ? 'bg-secondary/10 border-secondary/30 text-secondary' : 'bg-white/[0.02] border-white/[0.06] text-white hover:bg-white/[0.05]' }}">
            <div class="w-9 h-9 rounded-lg bg-secondary/10 flex items-center justify-center text-secondary flex-shrink-0">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">sports_soccer</span>
            </div>
            <div class="flex flex-col">
                <span class="text-xs font-bold">Battle Odds</span>
                <span class="text-[10px] text-on-surface-subtle">Sportsbook</span>
            </div>
        </a>

        <a href="{{ route('frontend.lotto.index') }}" class="p-3.5 rounded-xl flex items-center gap-3 no-underline border transition-all {{ Route::is('frontend.lotto*') ? 'bg-primary/10 border-primary/30 text-primary' : 'bg-white/[0.02] border-white/[0.06] text-white hover:bg-white/[0.05]' }}">
            <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
            </div>
            <div class="flex flex-col">
                <span class="text-xs font-bold">Jackpot Zone</span>
                <span class="text-[10px] text-on-surface-subtle">Multi-Draw</span>
            </div>
        </a>

        <a href="{{ route('frontend.predictions.index') }}" class="col-span-2 p-3.5 rounded-xl flex items-center justify-between no-underline border transition-all {{ Route::is('frontend.predictions*') ? 'bg-secondary/10 border-secondary/30 text-secondary' : 'bg-white/[0.02] border-white/[0.06] text-white hover:bg-white/[0.05]' }}">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-secondary/10 flex items-center justify-center text-secondary flex-shrink-0">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">query_stats</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold">Future Vote Prediction Markets</span>
                    <span class="text-[10px] text-on-surface-subtle">Vote YES / NO on Global Events</span>
                </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-secondary/20 text-secondary uppercase">LIVE</span>
        </a>

        <a href="{{ route('frontend.affiliates.index') }}" class="col-span-2 p-3.5 rounded-xl flex items-center justify-between no-underline border transition-all {{ Route::is('frontend.affiliates*') ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-white/[0.02] border-white/[0.06] text-white hover:bg-white/[0.05]' }}">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-400 flex-shrink-0">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">groups</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold">Affiliate & Referral Network</span>
                    <span class="text-[10px] text-on-surface-subtle">Earn Up to 1.00% Across 3 Tiers</span>
                </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 uppercase">EARN</span>
        </a>

        <a href="{{ route('frontend.vip.index') }}" class="col-span-2 p-3.5 rounded-xl flex items-center justify-between no-underline border transition-all {{ Route::is('frontend.vip*') ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-white/[0.02] border-white/[0.06] text-white hover:bg-white/[0.05]' }}">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-400 flex-shrink-0">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold">VIP Club & Loyalty Vault</span>
                    <span class="text-[10px] text-on-surface-subtle">Instant Rakeback & Level-Up Rewards</span>
                </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 uppercase">VAULT</span>
        </a>
    </div>

    <!-- Quick Action Utilities -->
    <div class="flex gap-2 pt-2 border-t border-white/[0.08]">
        @if(Auth::check())
            <a href="{{ route('frontend.auth.logout') }}" class="flex-1 py-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.06] text-center text-xs font-bold text-accent-rose no-underline border border-white/[0.06] transition-colors">
                Sign Out
            </a>
        @else
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-primary/10 hover:bg-primary/20 text-center text-xs font-bold text-primary border border-primary/20 transition-colors open-modal" data-target="modal-login">
                Log In
            </button>
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-primary text-white text-center text-xs font-bold shadow-md shadow-primary/20 transition-colors open-modal" data-target="modal-register">
                Register Free
            </button>
        @endif
    </div>
</div>

<!-- Floating Mobile App Bottom Dock Navigation -->
<nav class="lg:hidden fixed bottom-3 inset-x-3 sm:inset-x-6 z-40 bg-[#121622]/95 border border-white/[0.12] rounded-2xl shadow-2xl backdrop-blur-2xl px-2 py-1.5 flex items-center justify-around">
    <!-- 1. Casino -->
    @if(settings('enable_casino_slots', '1') == '1')
    <a href="{{ route('frontend.game.list') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-xl transition-all no-underline {{ Route::is('frontend.game.list*') && !request()->is('categories/cedar_games*') && !request()->is('categories/cedar_cards*') ? 'text-primary' : 'text-on-surface-subtle hover:text-white' }}">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' {{ Route::is('frontend.game.list*') && !request()->is('categories/cedar_games*') && !request()->is('categories/cedar_cards*') ? '1' : '0' }};">casino</span>
        <span class="text-[10px] font-bold tracking-tight">Casino</span>
    </a>
    @endif

    <!-- 2. CEDAR Originals -->
    @if(settings('enable_cedar_originals', '1') == '1')
    <a href="/categories/cedar_games" class="flex flex-col items-center gap-1 py-1 px-3 rounded-xl transition-all no-underline {{ request()->is('categories/cedar_games*') ? 'text-accent-gold' : 'text-on-surface-subtle hover:text-white' }}">
        <span class="material-symbols-outlined text-2xl text-accent-gold" style="font-variation-settings: 'FILL' 1;">rocket_launch</span>
        <span class="text-[10px] font-bold tracking-tight text-accent-gold">CEDAR</span>
    </a>
    @endif

    <!-- 3. Battle Odds / Sports -->
    @if(settings('enable_sportsbook', '1') == '1')
    <a href="{{ route('frontend.sports.index') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-xl transition-all no-underline {{ Route::is('frontend.sports*') ? 'text-secondary' : 'text-on-surface-subtle hover:text-white' }}">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' {{ Route::is('frontend.sports*') ? '1' : '0' }};">sports_soccer</span>
        <span class="text-[10px] font-bold tracking-tight">Odds</span>
    </a>
    @endif

    <!-- 4. Jackpot Zone -->
    @if(settings('enable_lotto', '1') == '1')
    <a href="{{ route('frontend.lotto.index') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-xl transition-all no-underline {{ Route::is('frontend.lotto*') ? 'text-primary' : 'text-on-surface-subtle hover:text-white' }}">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' {{ Route::is('frontend.lotto*') ? '1' : '0' }};">auto_awesome</span>
        <span class="text-[10px] font-bold tracking-tight">Jackpot</span>
    </a>
    @endif

    <!-- 5. Hub / Menu Toggle -->
    <button type="button" id="btn-open-mobile-menu" class="flex flex-col items-center gap-1 py-1 px-3 rounded-xl text-on-surface-subtle hover:text-white transition-all">
        <span class="material-symbols-outlined text-2xl">widgets</span>
        <span class="text-[10px] font-bold tracking-tight">Hub</span>
    </button>
</nav>
