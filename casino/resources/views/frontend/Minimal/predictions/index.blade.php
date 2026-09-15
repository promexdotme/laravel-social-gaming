@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Future Vote Prediction Markets - Casino du Liban')

@section('content')

<!-- Header & Top Actions -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="space-y-1">
        <div class="inline-flex items-center gap-2 bg-secondary/10 border border-secondary/25 px-3 py-1 rounded-full mb-1">
            <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
            <span class="text-[11px] font-bold text-secondary font-mono-jet uppercase tracking-wider">LIVE PROBABILITY SHARES</span>
        </div>
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight uppercase">Future Vote Arena</h1>
        <p class="text-on-surface-muted text-xs sm:text-sm">Trade YES/NO probability shares (1¢ – 99¢) on real-world events or launch your own market!</p>
    </div>

    <!-- Create Custom Bet Action Button -->
    <button type="button" id="btn-open-create-modal" class="w-full sm:w-auto bg-primary hover:bg-primary-dark text-white px-5 py-3 rounded-xl font-bold text-xs uppercase tracking-wider shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-base">add_circle</span>
        <span>Create Custom Market</span>
    </button>
</div>

<!-- Category Filters -->
@php $cat = $selectedCategory ?? 'all'; @endphp
<div class="flex items-center gap-2 overflow-x-auto pb-1.5 custom-scrollbar max-w-full">
    <a href="?category=all" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ $cat === 'all' ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
        🔥 All Topics
    </a>
    <a href="?category=crypto" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ $cat === 'crypto' ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
        🪙 Crypto & Economy
    </a>
    <a href="?category=geopolitics" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ $cat === 'geopolitics' ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
        🌍 Geopolitics & Elections
    </a>
    <a href="?category=tech" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ $cat === 'tech' ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
        🤖 Tech & AI
    </a>
    <a href="?category=custom" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ $cat === 'custom' ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
        🎨 Player Created
    </a>
</div>

<!-- Search Bar Widget -->
<div class="bg-[#121622] rounded-2xl p-3 sm:p-4 border border-white/[0.08] shadow-md relative">
    <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-secondary text-2xl pl-1">search</span>
        <input type="text" id="poly-search-input" placeholder="Search global topics (e.g. Bitcoin, Trump, AI, SpaceX, Fed rates)..." class="w-full bg-transparent text-white placeholder:text-on-surface-subtle focus:outline-none text-xs sm:text-sm">
        <span id="search-spinner" class="hidden text-xs font-mono-jet text-secondary animate-pulse pr-2">Searching...</span>
    </div>
</div>

<!-- Live Search Results Container (Hidden by default until typing) -->
<div id="search-results-section" class="hidden space-y-4 pt-2">
    <h3 class="text-xs font-bold text-secondary uppercase tracking-wider flex items-center justify-between">
        <span>🔍 Global Search Results</span>
        <span id="search-count" class="font-mono-jet text-xs text-white">0 FOUND</span>
    </h3>
    <div id="search-results-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Dynamic Search Results -->
    </div>
</div>

<!-- Active Local Featured Markets -->
<div class="space-y-4 pt-2">
    <div class="flex justify-between items-center">
        <h3 class="text-base sm:text-lg font-bold text-white uppercase tracking-tight">Active Prediction Markets</h3>
        <span class="font-mono-jet text-xs text-secondary font-bold bg-secondary/10 border border-secondary/20 px-2.5 py-1 rounded-lg">{{ count($activeMarkets) }} MARKETS</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        @forelse($activeMarkets as $m)
            <div class="bg-[#121622] rounded-3xl p-5 sm:p-6 border border-white/[0.08] hover:border-white/15 transition-all shadow-md flex flex-col justify-between space-y-4">
                <div class="space-y-2.5">
                    <div class="flex justify-between items-center text-xs font-mono-jet">
                        <span class="text-secondary font-bold uppercase tracking-wider text-[11px] bg-secondary/10 px-2.5 py-0.5 rounded-full border border-secondary/20">{{ strtoupper($m->category) }}</span>
                        <div class="flex items-center gap-3">
                            <span class="text-on-surface-muted flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs text-primary">event</span>
                                <time class="local-time" datetime="{{ $m->end_date ? $m->end_date->toIso8601String() : '' }}">{{ $m->end_date ? $m->end_date->format('M d, Y') : 'Open' }}</time>
                            </span>
                            <span class="text-accent-gold font-bold font-mono-jet">{{ number_format($m->total_volume, 0) }} C Vol</span>
                        </div>
                    </div>

                    <h4 class="font-bold text-sm sm:text-base text-white leading-snug">{{ $m->title }}</h4>
                    @if($m->description)
                        <p class="text-xs text-on-surface-muted line-clamp-2">{{ $m->description }}</p>
                    @endif

                    @if($m->verification_url)
                        <a href="{{ $m->verification_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-mono-jet text-primary hover:underline pt-0.5">
                            <span class="material-symbols-outlined text-xs">verified</span>
                            <span>Verify Source Criteria</span>
                        </a>
                    @endif
                </div>

                <!-- Polymarket-style Probability Bar & Share Trading Buttons -->
                <div class="space-y-3 pt-3 border-t border-white/[0.06]">
                    <!-- 2-Tone Probability Bar -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs font-mono-jet font-bold">
                            <span class="text-primary flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-primary"></span>
                                <span>YES {{ $m->yes_percent }}%</span>
                            </span>
                            <span class="text-accent-rose flex items-center gap-1">
                                <span>NO {{ $m->no_percent }}%</span>
                                <span class="w-2 h-2 rounded-full bg-accent-rose"></span>
                            </span>
                        </div>
                        <div class="w-full h-2.5 bg-[#0b0e14] rounded-full overflow-hidden flex border border-white/[0.06]">
                            <div class="h-full bg-gradient-to-r from-primary to-emerald-400 transition-all duration-500" style="width: {{ $m->yes_percent }}%;"></div>
                            <div class="h-full bg-gradient-to-r from-accent-rose to-rose-600 transition-all duration-500" style="width: {{ $m->no_percent }}%;"></div>
                        </div>
                    </div>

                    <!-- Share Price Action Buttons -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <button type="button" class="btn-buy-shares p-3 rounded-2xl bg-primary/10 border border-primary/30 hover:bg-primary hover:text-white transition-all text-left flex flex-col justify-between cursor-pointer group"
                                data-market-id="{{ $m->market_id }}" 
                                data-choice="yes" 
                                data-title="{{ $m->title }}" 
                                data-price="{{ $m->yes_price }}"
                                data-initial-price="{{ $m->yes_price }}"
                                data-yes-price="{{ $m->yes_price }}"
                                data-no-price="{{ $m->no_price }}"
                                data-yes-percent="{{ $m->yes_percent }}"
                                data-no-percent="{{ $m->no_percent }}"
                                data-percent="{{ $m->yes_percent }}"
                                data-odds="{{ $m->yes_odds }}"
                                data-category="{{ $m->category }}"
                                data-end-date="{{ $m->end_date }}">
                            <div class="flex justify-between items-center w-full">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-primary group-hover:text-white">BUY YES</span>
                                <span class="text-xs font-mono-jet font-extrabold text-primary group-hover:text-white">{{ number_format($m->yes_price * 100, 0) }}¢</span>
                            </div>
                            <div class="text-[10px] font-mono-jet text-on-surface-muted group-hover:text-white/80 pt-1">
                                Return: <strong class="text-white">{{ number_format($m->yes_odds, 2) }}x</strong> (+{{ round(($m->yes_odds - 1) * 100) }}%)
                            </div>
                        </button>

                        <button type="button" class="btn-buy-shares p-3 rounded-2xl bg-accent-rose/10 border border-accent-rose/30 hover:bg-accent-rose hover:text-white transition-all text-left flex flex-col justify-between cursor-pointer group"
                                data-market-id="{{ $m->market_id }}" 
                                data-choice="no" 
                                data-title="{{ $m->title }}" 
                                data-price="{{ $m->no_price }}"
                                data-initial-price="{{ $m->yes_price }}"
                                data-yes-price="{{ $m->yes_price }}"
                                data-no-price="{{ $m->no_price }}"
                                data-yes-percent="{{ $m->yes_percent }}"
                                data-no-percent="{{ $m->no_percent }}"
                                data-percent="{{ $m->no_percent }}"
                                data-odds="{{ $m->no_odds }}"
                                data-category="{{ $m->category }}"
                                data-end-date="{{ $m->end_date }}">
                            <div class="flex justify-between items-center w-full">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-accent-rose group-hover:text-white">BUY NO</span>
                                <span class="text-xs font-mono-jet font-extrabold text-accent-rose group-hover:text-white">{{ number_format($m->no_price * 100, 0) }}¢</span>
                            </div>
                            <div class="text-[10px] font-mono-jet text-on-surface-muted group-hover:text-white/80 pt-1">
                                Return: <strong class="text-white">{{ number_format($m->no_odds, 2) }}x</strong> (+{{ round(($m->no_odds - 1) * 100) }}%)
                            </div>
                        </button>
                    </div>

                    <!-- Order Book Depth Trigger Link -->
                    <div class="flex justify-between items-center text-[11px] font-mono-jet text-on-surface-subtle pt-1">
                        <button type="button" class="btn-open-orderbook hover:text-secondary inline-flex items-center gap-1 transition-all" data-market-id="{{ $m->market_id }}">
                            <span class="material-symbols-outlined text-xs">bar_chart</span>
                            <span>View Order Book Depth (Ladder)</span>
                        </button>
                        <span>AMM Constant Product</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-2 text-center py-16 bg-[#121622] rounded-3xl border border-white/[0.06]">
                <span class="material-symbols-outlined text-4xl text-on-surface-subtle mb-2">query_stats</span>
                <p class="text-on-surface-muted text-sm font-medium">No active prediction markets in this category.</p>
                <p class="text-xs text-on-surface-subtle mt-1">Use the search bar above to query global events or create your own custom bet!</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Player Active Shares Portfolio -->
@if(Auth::check() && !empty($userVotes))
<div class="mt-8 space-y-4">
    <div class="flex justify-between items-center">
        <h3 class="text-base sm:text-lg font-bold text-white uppercase tracking-tight flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">pie_chart</span>
            <span>Your Active Probability Share Positions</span>
        </h3>
        <span class="text-xs font-mono-jet text-primary font-bold">{{ count($userVotes) }} POSITIONS</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
        @foreach($userVotes as $v)
            <div class="p-4 rounded-2xl bg-[#121622] border border-white/[0.08] shadow-md flex justify-between items-center">
                <div class="space-y-1 max-w-[70%]">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono-jet font-bold uppercase {{ $v->choice === 'yes' ? 'bg-primary/20 text-primary border border-primary/30' : 'bg-accent-rose/20 text-accent-rose border border-accent-rose/30' }}">
                            {{ strtoupper($v->choice) }} SHARES
                        </span>
                        <span class="text-[11px] font-mono-jet text-on-surface-muted">@ {{ number_format(($v->share_price ?? 0.50) * 100, 0) }}¢</span>
                    </div>
                    <div class="text-xs font-bold text-white truncate">{{ $v->market ? $v->market->title : $v->market_id }}</div>
                    <div class="text-[11px] font-mono-jet text-on-surface-muted">
                        Shares: <strong class="text-white">{{ number_format($v->shares_count ?: ($v->stake / max(0.01, $v->share_price ?: 0.5)), 0) }}</strong> | Stake: {{ number_format($v->stake, 0) }} C
                    </div>
                </div>

                <div class="text-right space-y-1">
                    <div class="text-xs font-mono-jet font-bold text-accent-gold">
                        +{{ number_format($v->potential_win, 0) }} C
                    </div>
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono-jet font-bold uppercase {{ $v->status === 'won' ? 'bg-primary/20 text-primary' : ($v->status === 'lost' ? 'bg-accent-rose/20 text-accent-rose' : 'bg-secondary/20 text-secondary') }}">
                        {{ $v->status }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<!-- Modal: Buy Shares Dialog -->
<div id="shares-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-[#121622] max-w-md w-full rounded-3xl p-6 sm:p-7 space-y-5 border border-white/10 shadow-2xl">
        <div class="flex justify-between items-center pb-3 border-b border-white/[0.08]">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-primary" id="modal-color-dot"></span>
                <h3 class="text-base font-extrabold text-white uppercase tracking-tight" id="shares-modal-title">Buy YES Shares</h3>
            </div>
            <button type="button" id="btn-close-shares-modal" class="text-on-surface-muted hover:text-white text-xl font-bold p-1">&times;</button>
        </div>

        <div class="space-y-1">
            <h4 class="text-xs sm:text-sm font-bold text-white leading-snug line-clamp-2" id="shares-market-title">Market Question</h4>
            <div class="flex justify-between text-xs font-mono-jet text-on-surface-muted pt-1">
                <span>Share Price: <strong id="modal-share-price" class="text-white">65¢ ($0.65)</strong></span>
                <span>Implied Chance: <strong id="modal-share-prob" class="text-primary font-bold">65%</strong></span>
            </div>
        </div>

        <!-- Outcome Selector Tabs (Toggle between YES and NO) -->
        <div class="space-y-1.5">
            <div class="flex justify-between text-[11px] font-mono-jet font-bold text-on-surface-muted uppercase">
                <span>Select Outcome</span>
                <span class="text-secondary font-normal">Switch freely anytime</span>
            </div>
            <div class="grid grid-cols-2 gap-2 p-1.5 bg-black/50 rounded-2xl border border-white/10">
                <button type="button" id="modal-toggle-yes" class="py-2.5 px-3 rounded-xl font-mono-jet font-bold text-xs flex items-center justify-center gap-2 border transition-all cursor-pointer">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                    <span>YES</span>
                    <span id="modal-tab-yes-price" class="text-[11px] font-extrabold">50¢</span>
                </button>
                <button type="button" id="modal-toggle-no" class="py-2.5 px-3 rounded-xl font-mono-jet font-bold text-xs flex items-center justify-center gap-2 border transition-all cursor-pointer">
                    <span class="w-2.5 h-2.5 rounded-full bg-accent-rose"></span>
                    <span>NO</span>
                    <span id="modal-tab-no-price" class="text-[11px] font-extrabold">50¢</span>
                </button>
            </div>
        </div>

        <!-- Stake input & quick increments -->
        <div class="space-y-2">
            <div class="flex justify-between text-xs font-bold text-on-surface-muted uppercase">
                <span>Stake Amount (Cedar Coins)</span>
                @if(Auth::check())
                    <span>Balance: {{ number_format(Auth::user()->balance, 0) }} C</span>
                @endif
            </div>
            <div class="relative">
                <input type="number" id="shares-stake-input" value="1000" min="10" step="50" class="w-full text-base font-mono-jet text-white font-extrabold p-3.5 bg-black/40 border border-white/10 rounded-xl focus:border-primary focus:outline-none">
                <span class="absolute right-3.5 top-3.5 text-xs font-mono-jet text-primary font-bold">CEDAR COINS</span>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn-quick-stake px-3 py-1.5 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-xs font-mono-jet font-bold text-on-surface-muted hover:text-white border border-white/[0.06] transition-all" data-add="500">+500</button>
                <button type="button" class="btn-quick-stake px-3 py-1.5 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-xs font-mono-jet font-bold text-on-surface-muted hover:text-white border border-white/[0.06] transition-all" data-add="1000">+1,000</button>
                <button type="button" class="btn-quick-stake px-3 py-1.5 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-xs font-mono-jet font-bold text-on-surface-muted hover:text-white border border-white/[0.06] transition-all" data-add="5000">+5,000</button>
            </div>
        </div>

        <!-- Calculation Summary Box -->
        <div class="p-4 rounded-2xl bg-[#0b0e14] border border-white/[0.06] space-y-2.5 text-xs font-mono-jet">
            <div class="flex justify-between">
                <span class="text-on-surface-muted">Shares to Acquire:</span>
                <span id="calc-shares-count" class="text-white font-extrabold text-sm">1,538.46 Shares</span>
            </div>
            <div class="flex justify-between">
                <span class="text-on-surface-muted">Payout if Outcome Wins:</span>
                <span id="calc-payout-total" class="text-accent-gold font-extrabold text-sm">1,538 Cedar Coins</span>
            </div>
            <div class="flex justify-between pt-1 border-t border-white/[0.06]">
                <span class="text-on-surface-muted">Potential Net Profit:</span>
                <span id="calc-net-profit" class="text-primary font-bold">+538 C (+53.8% ROI)</span>
            </div>
        </div>

        <button type="button" id="btn-confirm-shares" class="w-full bg-primary hover:bg-primary-dark text-white py-4 rounded-xl font-extrabold text-xs sm:text-sm uppercase tracking-wider shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2">
            <span id="btn-confirm-shares-text">Confirm Share Acquisition</span>
        </button>
    </div>
</div>

<!-- Modal: Order Book Depth Ladder -->
<div id="orderbook-modal" class="fixed inset-0 bg-black/85 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-[#121622] max-w-lg w-full rounded-3xl p-6 space-y-4 border border-white/10 shadow-2xl">
        <div class="flex justify-between items-center pb-3 border-b border-white/[0.08]">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">bar_chart</span>
                <h3 class="text-base font-extrabold text-white uppercase tracking-tight">Order Book Depth</h3>
            </div>
            <button type="button" id="btn-close-orderbook-modal" class="text-on-surface-muted hover:text-white text-xl font-bold p-1">&times;</button>
        </div>

        <h4 class="text-xs font-bold text-white truncate" id="orderbook-market-title">Market</h4>

        <!-- Orderbook Ladder Table -->
        <div class="grid grid-cols-2 gap-3 pt-1">
            <!-- Bids Column (YES) -->
            <div class="space-y-2 bg-[#0b0e14] p-3 rounded-2xl border border-primary/20">
                <div class="text-[11px] font-mono-jet font-bold text-primary flex justify-between pb-1 border-b border-white/[0.06]">
                    <span>BIDS (BUY YES)</span>
                    <span>SHARES</span>
                </div>
                <div id="orderbook-bids-list" class="space-y-1 text-xs font-mono-jet">
                    <!-- Dynamic Bids -->
                </div>
            </div>

            <!-- Asks Column (NO / SELL) -->
            <div class="space-y-2 bg-[#0b0e14] p-3 rounded-2xl border border-accent-rose/20">
                <div class="text-[11px] font-mono-jet font-bold text-accent-rose flex justify-between pb-1 border-b border-white/[0.06]">
                    <span>ASKS (SELL YES)</span>
                    <span>SHARES</span>
                </div>
                <div id="orderbook-asks-list" class="space-y-1 text-xs font-mono-jet">
                    <!-- Dynamic Asks -->
                </div>
            </div>
        </div>

        <div class="text-center pt-2">
            <span class="text-[11px] font-mono-jet text-on-surface-subtle" id="orderbook-spread-text">Spread: 1¢ | Constant Product Market Maker (CPMM)</span>
        </div>
    </div>
</div>

<!-- Modal: Create Custom Market -->
<div id="create-bet-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-[#121622] max-w-lg w-full rounded-3xl p-6 sm:p-7 space-y-4 border border-white/10 shadow-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-center pb-3 border-b border-white/[0.08]">
            <h3 class="text-base font-extrabold text-white uppercase tracking-tight">Create Custom Prediction Topic</h3>
            <button type="button" id="btn-close-create-modal" class="text-on-surface-muted hover:text-white text-xl font-bold p-1">&times;</button>
        </div>

        <form id="create-custom-bet-form" class="space-y-3.5">
            @csrf
            <div>
                <label for="custom-title" class="block text-xs font-bold text-on-surface-muted uppercase mb-1">Prediction Question</label>
                <input type="text" id="custom-title" name="title" required placeholder="e.g. Will Ethereum surpass $10,000 before end of 2026?" class="w-full text-xs sm:text-sm bg-black/40 border border-white/10 rounded-xl p-3 text-white focus:border-primary focus:outline-none">
            </div>

            <div>
                <label for="custom-category" class="block text-xs font-bold text-on-surface-muted uppercase mb-1">Category</label>
                <select id="custom-category" name="category" required class="w-full text-xs sm:text-sm bg-black/40 border border-white/10 rounded-xl p-3 text-white focus:border-primary focus:outline-none">
                    <option value="crypto">Crypto & Economy</option>
                    <option value="tech">Tech & AI</option>
                    <option value="geopolitics">Geopolitics & Politics</option>
                    <option value="sports">Sports</option>
                    <option value="custom">Entertainment & Custom</option>
                </select>
            </div>

            <div>
                <label for="custom-description" class="block text-xs font-bold text-on-surface-muted uppercase mb-1">Resolution Criteria</label>
                <textarea id="custom-description" name="description" rows="2" placeholder="Exact conditions required to resolve YES..." class="w-full text-xs sm:text-sm bg-black/40 border border-white/10 rounded-xl p-3 text-white focus:border-primary focus:outline-none"></textarea>
            </div>

            <div>
                <label for="custom-end-date" class="block text-xs font-bold text-on-surface-muted uppercase mb-1">Resolution End Date</label>
                <input type="datetime-local" id="custom-end-date" name="end_date" required class="w-full text-xs sm:text-sm bg-black/40 border border-white/10 rounded-xl p-3 text-white focus:border-primary focus:outline-none">
            </div>

            <div>
                <label for="custom-initial-stake" class="block text-xs font-bold text-on-surface-muted uppercase mb-1">Your Initial Seed Stake (Cedar Coins)</label>
                <input type="number" id="custom-initial-stake" name="initial_stake" value="1000" min="500" step="100" class="w-full text-xs sm:text-sm font-mono-jet bg-black/40 border border-white/10 rounded-xl p-3 text-white focus:border-primary focus:outline-none">
                <small class="text-[11px] text-on-surface-subtle font-mono-jet">Funds your initial probability position and seeds the market pool.</small>
            </div>

            <button type="submit" id="btn-submit-custom-bet" class="w-full bg-primary hover:bg-primary-dark text-white py-3.5 rounded-xl font-extrabold text-xs uppercase tracking-wider shadow-lg shadow-primary/25 transition-all mt-2">
                Deploy Prediction Market
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentMarketId = null;
    let currentChoice = 'yes';
    let currentPrice = 0.50;
    let currentInitialPrice = 0.50;
    let currentTitle = '';
    let currentCategory = 'crypto';
    let currentEndDate = '';
    let currentYesPrice = 0.50;
    let currentNoPrice = 0.50;
    let currentYesPercent = 50;
    let currentNoPercent = 50;

    const sharesModal = document.getElementById('shares-modal');
    const orderbookModal = document.getElementById('orderbook-modal');
    const createModal = document.getElementById('create-bet-modal');
    const stakeInput = document.getElementById('shares-stake-input');
    const confirmBtn = document.getElementById('btn-confirm-shares');
    const confirmBtnText = document.getElementById('btn-confirm-shares-text');
    const tabYes = document.getElementById('modal-toggle-yes');
    const tabNo = document.getElementById('modal-toggle-no');

    function updateCalc() {
        const stake = parseFloat(stakeInput.value) || 0;
        const shares = currentPrice > 0 ? (stake / currentPrice) : 0;
        const payout = shares * 1.0;
        const profit = payout - stake;
        const roi = stake > 0 ? ((profit / stake) * 100).toFixed(1) : 0;

        document.getElementById('calc-shares-count').innerText = `${shares.toFixed(2)} Shares`;
        document.getElementById('calc-payout-total').innerText = `${Math.round(payout).toLocaleString()} Cedar Coins`;
        document.getElementById('calc-net-profit').innerText = `+${Math.round(profit).toLocaleString()} C (+${roi}% ROI)`;
    }

    function setModalChoice(choice) {
        currentChoice = choice;
        const isYes = (currentChoice === 'yes');
        currentPrice = isYes ? currentYesPrice : currentNoPrice;
        const currentPercent = isYes ? currentYesPercent : currentNoPercent;

        document.getElementById('shares-modal-title').innerText = isYes ? 'Buy YES Shares' : 'Buy NO Shares';
        document.getElementById('modal-color-dot').className = isYes ? 'w-3 h-3 rounded-full bg-primary' : 'w-3 h-3 rounded-full bg-accent-rose';
        document.getElementById('modal-share-price').innerText = `${Math.round(currentPrice * 100)}¢ ($${currentPrice.toFixed(2)})`;
        document.getElementById('modal-share-prob').innerText = `${currentPercent}%`;
        document.getElementById('modal-share-prob').className = isYes ? 'text-primary font-bold' : 'text-accent-rose font-bold';

        if (tabYes && tabNo) {
            if (isYes) {
                tabYes.className = 'py-2.5 px-3 rounded-xl font-mono-jet font-bold text-xs flex items-center justify-center gap-2 bg-primary text-white border border-primary shadow-md shadow-primary/25 transition-all cursor-pointer';
                tabNo.className = 'py-2.5 px-3 rounded-xl font-mono-jet font-bold text-xs flex items-center justify-center gap-2 bg-white/[0.04] text-on-surface-muted hover:text-white border border-transparent transition-all cursor-pointer';
            } else {
                tabNo.className = 'py-2.5 px-3 rounded-xl font-mono-jet font-bold text-xs flex items-center justify-center gap-2 bg-accent-rose text-white border border-accent-rose shadow-md shadow-accent-rose/25 transition-all cursor-pointer';
                tabYes.className = 'py-2.5 px-3 rounded-xl font-mono-jet font-bold text-xs flex items-center justify-center gap-2 bg-white/[0.04] text-on-surface-muted hover:text-white border border-transparent transition-all cursor-pointer';
            }
        }

        if (confirmBtn) {
            confirmBtn.className = isYes 
                ? 'w-full bg-primary hover:bg-primary-dark text-white py-4 rounded-xl font-extrabold text-xs sm:text-sm uppercase tracking-wider shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2'
                : 'w-full bg-accent-rose hover:bg-rose-600 text-white py-4 rounded-xl font-extrabold text-xs sm:text-sm uppercase tracking-wider shadow-lg shadow-accent-rose/25 transition-all flex items-center justify-center gap-2';
        }
        if (confirmBtnText) {
            confirmBtnText.innerText = `Acquire ${isYes ? 'YES' : 'NO'} Shares`;
        }

        updateCalc();
    }

    if (tabYes) tabYes.addEventListener('click', () => setModalChoice('yes'));
    if (tabNo) tabNo.addEventListener('click', () => setModalChoice('no'));

    function openBuyModal(btn) {
        currentMarketId = btn.dataset.marketId;
        currentTitle = btn.dataset.title || 'Prediction Market';
        currentCategory = btn.dataset.category || 'crypto';
        currentEndDate = btn.dataset.endDate || '';

        // Extract both prices and percentages
        currentYesPrice = parseFloat(btn.dataset.yesPrice) || parseFloat(btn.dataset.initialPrice) || parseFloat(btn.dataset.price) || 0.50;
        currentNoPrice = parseFloat(btn.dataset.noPrice) || (1.0 - currentYesPrice);
        currentYesPercent = parseInt(btn.dataset.yesPercent) || Math.round(currentYesPrice * 100);
        currentNoPercent = parseInt(btn.dataset.noPercent) || (100 - currentYesPercent);
        currentInitialPrice = currentYesPrice;

        document.getElementById('shares-market-title').innerText = currentTitle;
        const tabYesPrice = document.getElementById('modal-tab-yes-price');
        const tabNoPrice = document.getElementById('modal-tab-no-price');
        if (tabYesPrice) tabYesPrice.innerText = `${Math.round(currentYesPrice * 100)}¢`;
        if (tabNoPrice) tabNoPrice.innerText = `${Math.round(currentNoPrice * 100)}¢`;

        setModalChoice(btn.dataset.choice || 'yes');
        sharesModal.classList.remove('hidden');
    }

    // Attach Buy Shares buttons
    document.querySelectorAll('.btn-buy-shares').forEach(btn => {
        btn.addEventListener('click', function() {
            openBuyModal(this);
        });
    });

    if (stakeInput) {
        stakeInput.addEventListener('input', updateCalc);
    }

    document.querySelectorAll('.btn-quick-stake').forEach(btn => {
        btn.addEventListener('click', function() {
            const add = parseFloat(this.dataset.add) || 0;
            stakeInput.value = (parseFloat(stakeInput.value) || 0) + add;
            updateCalc();
        });
    });

    document.getElementById('btn-close-shares-modal').addEventListener('click', () => {
        sharesModal.classList.add('hidden');
    });

    // Confirm Share Purchase
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const stake = parseFloat(stakeInput.value) || 0;
            if (stake <= 0) {
                alert('Please enter a valid stake amount.');
                return;
            }

            confirmBtn.disabled = true;
            confirmBtn.innerText = 'ACQUIRING SHARES...';

            fetch('/predictions/vote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    market_id: currentMarketId,
                    choice: currentChoice,
                    stake: stake,
                    title: currentTitle,
                    initial_price: currentInitialPrice,
                    category: currentCategory,
                    end_date: currentEndDate
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('🎉 ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + data.message);
                    confirmBtn.disabled = false;
                    confirmBtn.innerText = 'Confirm Share Acquisition';
                }
            })
            .catch(err => {
                alert('Error acquiring shares: ' + err.message);
                confirmBtn.disabled = false;
                confirmBtn.innerText = 'Confirm Share Acquisition';
            });
        });
    }

    // View Order Book Ladder Modal
    document.querySelectorAll('.btn-open-orderbook').forEach(btn => {
        btn.addEventListener('click', function() {
            const marketId = this.dataset.marketId;
            fetch(`/predictions/${marketId}/order-book`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('orderbook-market-title').innerText = data.title;
                        document.getElementById('orderbook-spread-text').innerText = `Spread: ${Math.round(data.order_book.spread * 100)}¢ | Total Pool: ${Math.round(data.total_volume).toLocaleString()} C`;

                        const bidsEl = document.getElementById('orderbook-bids-list');
                        const asksEl = document.getElementById('orderbook-asks-list');

                        bidsEl.innerHTML = (data.order_book.bids || []).map(b => `
                            <div class="flex justify-between text-primary">
                                <span>${Math.round(b.price * 100)}¢</span>
                                <span class="text-white">${b.shares.toLocaleString()}</span>
                            </div>
                        `).join('');

                        asksEl.innerHTML = (data.order_book.asks || []).map(a => `
                            <div class="flex justify-between text-accent-rose">
                                <span>${Math.round(a.price * 100)}¢</span>
                                <span class="text-white">${a.shares.toLocaleString()}</span>
                            </div>
                        `).join('');

                        orderbookModal.classList.remove('hidden');
                    }
                });
        });
    });

    document.getElementById('btn-close-orderbook-modal').addEventListener('click', () => {
        orderbookModal.classList.add('hidden');
    });

    // Create Custom Market Modal
    const openCreateBtn = document.getElementById('btn-open-create-modal');
    if (openCreateBtn) {
        openCreateBtn.addEventListener('click', () => createModal.classList.remove('hidden'));
    }
    document.getElementById('btn-close-create-modal').addEventListener('click', () => {
        createModal.classList.add('hidden');
    });

    const createForm = document.getElementById('create-custom-bet-form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('btn-submit-custom-bet');
            submitBtn.disabled = true;
            submitBtn.innerText = 'DEPLOYING MARKET...';

            const payload = {
                title: document.getElementById('custom-title').value,
                category: document.getElementById('custom-category').value,
                description: document.getElementById('custom-description').value,
                end_date: document.getElementById('custom-end-date').value,
                initial_stake: parseFloat(document.getElementById('custom-initial-stake').value) || 1000
            };

            fetch('/predictions/custom', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('🎉 ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Deploy Prediction Market';
                }
            })
            .catch(err => {
                alert('Error deploying market: ' + err.message);
                submitBtn.disabled = false;
                submitBtn.innerText = 'Deploy Prediction Market';
            });
        });
    }

    // Hybrid Search Input Handler
    const searchInput = document.getElementById('poly-search-input');
    const searchSection = document.getElementById('search-results-section');
    const searchGrid = document.getElementById('search-results-grid');
    const searchCount = document.getElementById('search-count');
    const searchSpinner = document.getElementById('search-spinner');

    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                searchSection.classList.add('hidden');
                return;
            }

            searchSpinner.classList.remove('hidden');

            searchTimer = setTimeout(() => {
                fetch(`/predictions/search?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        searchSpinner.classList.add('hidden');
                        if (data.success && data.data && data.data.length > 0) {
                            searchCount.innerText = `${data.data.length} FOUND`;
                            searchGrid.innerHTML = data.data.map(m => {
                                const cleanTitle = (m.title || 'Market').replace(/"/g, '&quot;');
                                const yesPriceCents = Math.round(m.yes_price * 100);
                                const noPriceCents = Math.round(m.no_price * 100);
                                const formattedDate = m.end_date ? new Date(m.end_date).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'}) : 'Active';
                                const yesRoi = Math.round((m.yes_odds - 1) * 100);
                                const noRoi = Math.round((m.no_odds - 1) * 100);

                                return `
                                <div class="p-4 sm:p-5 rounded-3xl bg-[#121622] border border-white/[0.08] hover:border-white/15 transition-all shadow-md space-y-3.5 flex flex-col justify-between">
                                    <div class="space-y-2.5">
                                        <div class="flex justify-between items-center text-xs font-mono-jet">
                                            <span class="text-secondary font-bold uppercase tracking-wider text-[11px] bg-secondary/10 px-2.5 py-0.5 rounded-full border border-secondary/20">${(m.category || 'TOPIC').toUpperCase()}</span>
                                            <div class="flex items-center gap-3">
                                                <span class="text-on-surface-muted flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs text-primary">event</span>
                                                    <span>${formattedDate}</span>
                                                </span>
                                                ${m.total_volume ? `<span class="text-accent-gold font-bold font-mono-jet">${Math.round(m.total_volume).toLocaleString()} Vol</span>` : ''}
                                            </div>
                                        </div>

                                        <h4 class="font-bold text-sm sm:text-base text-white leading-snug line-clamp-2">${cleanTitle}</h4>
                                    </div>

                                    <!-- 2-Tone Probability Bar & Dual Buy Action Buttons -->
                                    <div class="space-y-3 pt-2 border-t border-white/[0.06]">
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-xs font-mono-jet font-bold">
                                                <span class="text-primary flex items-center gap-1">
                                                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                                                    <span>YES ${m.yes_percent}%</span>
                                                </span>
                                                <span class="text-accent-rose flex items-center gap-1">
                                                    <span>NO ${m.no_percent}%</span>
                                                    <span class="w-2 h-2 rounded-full bg-accent-rose"></span>
                                                </span>
                                            </div>
                                            <div class="w-full h-2 bg-[#0b0e14] rounded-full overflow-hidden flex border border-white/[0.06]">
                                                <div class="h-full bg-gradient-to-r from-primary to-emerald-400 transition-all duration-500" style="width: ${m.yes_percent}%;"></div>
                                                <div class="h-full bg-gradient-to-r from-accent-rose to-rose-600 transition-all duration-500" style="width: ${m.no_percent}%;"></div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2.5">
                                            <button type="button" class="btn-buy-shares p-2.5 sm:p-3 rounded-2xl bg-primary/10 border border-primary/30 hover:bg-primary hover:text-white transition-all text-left flex flex-col justify-between cursor-pointer group"
                                                    data-market-id="${m.market_id}" 
                                                    data-choice="yes" 
                                                    data-title="${cleanTitle}" 
                                                    data-price="${m.yes_price}"
                                                    data-initial-price="${m.yes_price}"
                                                    data-yes-price="${m.yes_price}"
                                                    data-no-price="${m.no_price}"
                                                    data-yes-percent="${m.yes_percent}"
                                                    data-no-percent="${m.no_percent}"
                                                    data-percent="${m.yes_percent}"
                                                    data-odds="${m.yes_odds}"
                                                    data-category="${m.category || 'crypto'}"
                                                    data-end-date="${m.end_date || ''}">
                                                <div class="flex justify-between items-center w-full">
                                                    <span class="text-[11px] font-bold uppercase tracking-wider text-primary group-hover:text-white">BUY YES</span>
                                                    <span class="text-xs font-mono-jet font-extrabold text-primary group-hover:text-white">${yesPriceCents}¢</span>
                                                </div>
                                                <div class="text-[10px] font-mono-jet text-on-surface-muted group-hover:text-white/80 pt-1">
                                                    Return: <strong class="text-white">${m.yes_odds}x</strong> (+${yesRoi}%)
                                                </div>
                                            </button>

                                            <button type="button" class="btn-buy-shares p-2.5 sm:p-3 rounded-2xl bg-accent-rose/10 border border-accent-rose/30 hover:bg-accent-rose hover:text-white transition-all text-left flex flex-col justify-between cursor-pointer group"
                                                    data-market-id="${m.market_id}" 
                                                    data-choice="no" 
                                                    data-title="${cleanTitle}" 
                                                    data-price="${m.no_price}"
                                                    data-initial-price="${m.yes_price}"
                                                    data-yes-price="${m.yes_price}"
                                                    data-no-price="${m.no_price}"
                                                    data-yes-percent="${m.yes_percent}"
                                                    data-no-percent="${m.no_percent}"
                                                    data-percent="${m.no_percent}"
                                                    data-odds="${m.no_odds}"
                                                    data-category="${m.category || 'crypto'}"
                                                    data-end-date="${m.end_date || ''}">
                                                <div class="flex justify-between items-center w-full">
                                                    <span class="text-[11px] font-bold uppercase tracking-wider text-accent-rose group-hover:text-white">BUY NO</span>
                                                    <span class="text-xs font-mono-jet font-extrabold text-accent-rose group-hover:text-white">${noPriceCents}¢</span>
                                                </div>
                                                <div class="text-[10px] font-mono-jet text-on-surface-muted group-hover:text-white/80 pt-1">
                                                    Return: <strong class="text-white">${m.no_odds}x</strong> (+${noRoi}%)
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                `;
                            }).join('');
                            searchSection.classList.remove('hidden');

                            // Re-bind click handlers for dynamic search items
                            searchGrid.querySelectorAll('.btn-buy-shares').forEach(b => {
                                b.addEventListener('click', function() {
                                    openBuyModal(this);
                                });
                            });
                        } else {
                            searchCount.innerText = `0 FOUND`;
                            searchGrid.innerHTML = `<div class="col-span-2 text-center py-6 text-xs text-on-surface-muted">No matching topics found for "${query}". Try creating it!</div>`;
                            searchSection.classList.remove('hidden');
                        }
                    })
                    .catch(() => {
                        searchSpinner.classList.add('hidden');
                    });
            }, 350);
        });
    }
});
</script>

@endsection
