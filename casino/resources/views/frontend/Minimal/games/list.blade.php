@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Casino du Liban - Premier Social Gaming Lobby')

@section('content')

<!-- Hero Feature Banner Carousel -->
<div class="relative overflow-hidden rounded-2xl md:rounded-3xl bg-gradient-to-r from-[#121826] via-[#162032] to-[#111624] border border-white/[0.08] shadow-2xl p-6 sm:p-8 md:p-10">
    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2.5 max-w-xl">
            <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/25 px-3 py-1 rounded-full">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                <span class="text-[11px] font-bold text-primary font-mono-jet uppercase tracking-wider">OFFICIAL SOCIAL CASINO</span>
            </div>
            <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-white tracking-tight leading-tight">
                Play 1,000+ Slots & <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary">CEDAR Originals</span>
            </h1>
            <p class="text-xs sm:text-sm text-on-surface-muted leading-relaxed">
                Experience high-energy arcade slots, the thrilling Cedar Space Crash, live sports wagering, and multi-draw jackpots with 100% Free Cedar Coins.
            </p>
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <a href="/categories/cedar_games" class="bg-gradient-to-r from-accent-gold to-accent-amber hover:from-yellow-400 hover:to-accent-gold text-black font-extrabold px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-accent-gold/25 transition-all no-underline flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">rocket_launch</span>
                    <span>Play CEDAR Crash</span>
                </a>
                <button type="button" class="open-modal bg-white/[0.06] hover:bg-white/[0.12] text-white border border-white/10 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all" data-target="{{ Auth::check() ? 'modal-profile' : 'modal-login' }}">
                    {{ Auth::check() ? 'Claim Free Refill' : 'Sign In / Register' }}
                </button>
            </div>
        </div>

        <!-- Featured Crash Multiplier Display -->
        <div class="hidden sm:flex flex-col items-center justify-center p-6 rounded-2xl bg-[#0b0e14]/60 border border-white/[0.08] backdrop-blur-md text-center min-w-[200px]">
            <span class="text-[10px] font-bold text-on-surface-subtle uppercase tracking-widest mb-1">CEDAR CRASH MULTIPLIER</span>
            <span class="font-mono-jet text-4xl font-extrabold text-accent-gold tracking-tighter animate-pulse">48.72x</span>
            <span class="text-[10px] text-primary font-bold mt-1">LAST TOP ROUND</span>
            <a href="/game/CedarCrash" class="mt-3 text-xs font-bold text-white bg-primary hover:bg-primary-dark px-4 py-1.5 rounded-lg no-underline transition-colors uppercase">
                Launch
            </a>
        </div>
    </div>
    
    <!-- Ambient Background Lighting -->
    <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -left-10 -top-10 w-72 h-72 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>
</div>

<!-- Live Social Winners Marquee -->
<div class="flex items-center gap-3 overflow-hidden bg-[#121622] border border-white/[0.06] rounded-xl px-4 py-2.5 text-xs">
    <div class="flex items-center gap-1.5 text-accent-gold font-bold flex-shrink-0 uppercase tracking-wider text-[11px]">
        <span class="material-symbols-outlined text-base">military_tech</span>
        <span>Live Wins:</span>
    </div>
    <div class="flex items-center gap-6 overflow-x-auto no-scrollbar font-mono-jet text-on-surface-muted whitespace-nowrap text-[11px]">
        <div class="flex items-center gap-1.5"><span class="text-white font-bold">Ahmad_K</span> won <span class="text-primary font-bold">142,000 C</span> on <span class="text-white">Book of Ra</span></div>
        <span class="text-white/20">•</span>
        <div class="flex items-center gap-1.5"><span class="text-white font-bold">Maya_L</span> hit <span class="text-accent-gold font-bold">38.4x</span> on <span class="text-white">Cedar Space Crash</span></div>
        <span class="text-white/20">•</span>
        <div class="flex items-center gap-1.5"><span class="text-white font-bold">Charbel99</span> won <span class="text-primary font-bold">88,500 C</span> on <span class="text-white">Beetle Mania</span></div>
        <span class="text-white/20">•</span>
        <div class="flex items-center gap-1.5"><span class="text-white font-bold">Georges_B</span> hit <span class="text-secondary font-bold">3-Match</span> in <span class="text-white">Jackpot Lotto</span></div>
    </div>
</div>

<!-- Games Grid Section -->
<section class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-2xl" style="font-variation-settings: 'FILL' 1;">grid_view</span>
                <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight text-white uppercase">Game Arena</h2>
            </div>
            <p class="text-on-surface-muted text-xs sm:text-sm mt-1">Select any game to launch instant demo mode with 10,000 free coins.</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="font-mono-jet text-xs text-primary font-bold bg-primary/10 border border-primary/20 px-3 py-1 rounded-lg">
                {{ count($games) }} TITLES ONLINE
            </span>
        </div>
    </div>

    <!-- Category Filter Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1.5 custom-scrollbar max-w-full">
        <a href="{{ route('frontend.game.list.category', 'all') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ ($category1 ?? 'all') == 'all' ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06] hover:border-white/15' }}">
            All Games
        </a>
        <a href="/categories/cedar_games" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ request()->is('categories/cedar_games*') ? 'bg-accent-gold text-black shadow-md shadow-accent-gold/25' : 'bg-surface-card text-accent-gold hover:text-yellow-300 border border-accent-gold/20' }}">
            🚀 CEDAR Games
        </a>
        <a href="/categories/cedar_cards" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ request()->is('categories/cedar_cards*') ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-surface-card text-primary hover:text-primary-light border border-primary/20' }}">
            ♠ CEDAR Cards
        </a>
        @if(is_iterable($categories))
            @foreach($categories as $cat)
                @continue(in_array($cat->href, ['cedar_games', 'cedar_cards'], true))
                <a href="{{ route('frontend.game.list.category', $cat->href) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ ($category1 ?? '') == $cat->href ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06] hover:border-white/15' }}">
                    {{ $cat->title }}
                </a>
            @endforeach
        @endif
    </div>

    <!-- Games Grid: 2-col mobile, 3-col tablet, 4-col laptop, 5-col wide -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
        @forelse($games as $game)
            @php($isCedarProvider = str_starts_with($game->name, 'Cedar') || $game->name === 'RoyalSteps')
            <div class="group relative aspect-[3/4] rounded-2xl overflow-hidden bg-[#161c2b] border border-white/[0.07] hover:border-primary/50 shadow-md hover:shadow-xl hover:shadow-primary/10 transition-all duration-300 flex flex-col justify-end">
                <!-- Cover Image -->
                <img src="/frontend/Default/ico/{{ $game->name }}.jpg{{ $isCedarProvider ? '?v=cedar-provider-1' : '' }}"
                     onerror="this.src='/frontend/Default/ico/DayofDead.jpg'"
                     alt="{{ $game->title }}" 
                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                     loading="lazy">
                
                <!-- Dark Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-[#0b0e14] via-[#0b0e14]/40 to-transparent opacity-90 group-hover:opacity-95 transition-opacity"></div>
                
                <!-- Game Info & Action -->
                <div class="relative z-10 p-3 sm:p-4 space-y-1.5">
                    <span class="text-[9px] text-primary font-mono-jet font-bold uppercase tracking-wider block">
                        {{ $isCedarProvider ? (in_array($game->name, ['CedarHiLo', 'CedarBlackjack'], true) ? 'CEDAR CARDS' : 'CEDAR ORIGINAL') : strtoupper(substr($game->name, -2) === 'AM' ? 'AMATIC' : (substr($game->name, -3) === 'PGD' ? 'PGD' : 'SLOT')) }}
                    </span>
                    <h4 class="text-xs sm:text-sm font-bold text-white leading-tight truncate" title="{{ $game->title }}">
                        {{ $game->title }}
                    </h4>
                    
                    <a href="{{ route('frontend.game.go', $game->name) }}" class="block text-center bg-primary hover:bg-primary-dark text-white py-1.5 sm:py-2 rounded-xl font-bold text-[11px] sm:text-xs uppercase tracking-wider transition-all no-underline shadow-md shadow-primary/20">
                        Play
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16 bg-[#121622] rounded-3xl border border-white/[0.06]">
                <span class="material-symbols-outlined text-4xl text-on-surface-subtle mb-2">sentiment_dissatisfied</span>
                <p class="text-on-surface-muted text-sm font-medium">No games found in this category.</p>
                <a href="{{ route('frontend.game.list') }}" class="mt-3 inline-block bg-primary text-white text-xs font-bold px-4 py-2 rounded-xl no-underline uppercase">
                    View All Games
                </a>
            </div>
        @endforelse
    </div>
</section>

<!-- Sports & Prediction Highlights Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 pt-4">
    <!-- Battle Odds Card -->
    <div class="lg:col-span-2 bg-[#121622] rounded-3xl p-6 border border-white/[0.08] shadow-lg space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-secondary/10 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">sports_soccer</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-tight">Battle Odds Arena</h3>
                    <span class="text-[11px] text-on-surface-muted">Live Global Match Fixtures</span>
                </div>
            </div>
            <a href="{{ route('frontend.sports.index') }}" class="text-xs font-bold text-secondary hover:text-secondary-light no-underline uppercase flex items-center gap-1">
                <span>All Odds</span>
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="bg-[#161c2b] rounded-2xl p-4 border border-white/[0.06] flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-center sm:text-left flex-1">
                <div class="flex-1">
                    <span class="text-[10px] text-on-surface-subtle uppercase tracking-wider block">CHAMPIONS LEAGUE</span>
                    <span class="text-sm font-bold text-white">REAL MADRID</span>
                </div>
                <span class="text-xs font-bold text-secondary bg-secondary/10 px-2.5 py-1 rounded-lg">VS</span>
                <div class="flex-1">
                    <span class="text-[10px] text-on-surface-subtle uppercase tracking-wider block">CHAMPIONS LEAGUE</span>
                    <span class="text-sm font-bold text-white">MANCHESTER CITY</span>
                </div>
            </div>
            <a href="{{ route('frontend.sports.index') }}" class="w-full sm:w-auto bg-secondary hover:bg-secondary-dark text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-colors no-underline text-center">
                Wager Free
            </a>
        </div>
    </div>

    <!-- Future Vote Card -->
    <div class="bg-[#121622] rounded-3xl p-6 border border-white/[0.08] shadow-lg space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">query_stats</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-tight">Future Vote</h3>
                    <span class="text-[11px] text-on-surface-muted">Prediction Markets</span>
                </div>
            </div>
            <a href="{{ route('frontend.predictions.index') }}" class="text-xs font-bold text-primary hover:text-primary-light no-underline uppercase flex items-center gap-1">
                <span>Vote</span>
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="space-y-3">
            <h4 class="text-xs sm:text-sm font-bold text-white leading-snug">Will Commercial Humanoid AI Robots enter consumer homes before 2027?</h4>
            <div class="space-y-1.5">
                <div class="flex justify-between font-mono-jet text-xs font-bold">
                    <span class="text-primary">YES 42%</span>
                    <span class="text-on-surface-muted">NO 58%</span>
                </div>
                <div class="w-full h-2 bg-black/40 rounded-full overflow-hidden flex">
                    <div class="h-full bg-primary" style="width: 42%"></div>
                    <div class="h-full bg-white/10" style="width: 58%"></div>
                </div>
            </div>
            <a href="{{ route('frontend.predictions.index') }}" class="block text-center w-full bg-primary hover:bg-primary-dark text-white py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all no-underline shadow-md shadow-primary/20">
                Vote Free Prediction
            </a>
        </div>
    </div>
</div>

@endsection
