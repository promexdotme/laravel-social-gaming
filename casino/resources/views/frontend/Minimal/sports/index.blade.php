@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Battle Odds Sportsbook - Casino du Liban')

@section('content')

<!-- Header & Sports Navigation -->
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-2 bg-secondary/10 border border-secondary/25 px-3 py-1 rounded-full mb-1">
                <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                <span class="text-[11px] font-bold text-secondary font-mono-jet uppercase tracking-wider">FREE SOCIAL SPORTSBOOK</span>
            </div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight uppercase">Battle Odds Arena</h1>
            <p class="text-on-surface-muted text-xs sm:text-sm mt-0.5">Wager singles or multi-match parlays on global matches with 100% Free Cedar Coins.</p>
        </div>
        <span class="font-mono-jet text-xs text-primary font-bold bg-primary/10 border border-primary/20 px-3 py-1.5 rounded-xl self-start sm:self-auto">
            {{ (isset($matches) && (is_array($matches) || $matches instanceof \Countable)) ? count($matches) : 0 }} FIXTURES OPEN
        </span>
    </div>

    <!-- Sports Category Tabs -->
    @php $cat = $selectedCategory ?? 'all'; @endphp
    <div class="flex items-center gap-2 overflow-x-auto pb-1.5 custom-scrollbar max-w-full">
        <a href="?sport=all" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ $cat === 'all' ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
            ⚽ All Sports
        </a>
        <a href="?sport=soccer" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ str_contains($cat, 'soccer') ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
            🏆 Football / Soccer
        </a>
        <a href="?sport=basketball" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ str_contains($cat, 'basketball') ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
            🏀 Basketball
        </a>
        <a href="?sport=mma" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap uppercase tracking-wider no-underline {{ str_contains($cat, 'mma') ? 'bg-secondary text-white shadow-md shadow-secondary/25' : 'bg-surface-card text-on-surface-muted hover:text-white border border-white/[0.06]' }}">
            🥊 UFC / MMA
        </a>
    </div>
</div>

<!-- Sportsbook Fixtures Grid & Desktop Betslip -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-2">
    <!-- Upcoming Matches List (Left 2 Columns) -->
    <div class="lg:col-span-2 space-y-3.5">
        @forelse($matches as $m)
            <div class="bg-[#121622] rounded-2xl p-4 sm:p-5 border border-white/[0.07] hover:border-white/15 transition-all shadow-md space-y-3">
                <!-- Match Header Info -->
                <div class="flex justify-between items-center text-xs font-mono-jet">
                    <span class="text-secondary font-bold uppercase tracking-wider text-[11px]">{{ $m->sport_title ?? 'Sports Match' }}</span>
                    <span class="text-on-surface-muted flex items-center gap-1.5 bg-white/[0.04] px-2.5 py-1 rounded-lg border border-white/[0.06]">
                        <span class="material-symbols-outlined text-xs text-primary">schedule</span>
                        <time class="local-time" datetime="{{ $m->start_time->toIso8601String() }}">{{ $m->start_time->format('H:i') }}</time>
                    </span>
                </div>

                <!-- Match Teams Title -->
                <div class="font-extrabold text-sm sm:text-base text-white tracking-tight flex items-center gap-2">
                    <span class="truncate">{{ $m->home_team }}</span>
                    <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded flex-shrink-0">VS</span>
                    <span class="truncate">{{ $m->away_team }}</span>
                </div>

                <!-- "Team - Odds" Buttons Grid: 1 X 2 -->
                <div class="grid grid-cols-3 gap-2 pt-1">
                    <!-- Home Win Button -->
                    <button type="button" class="btn-odd p-2.5 sm:p-3 rounded-xl bg-[#182030] border border-white/[0.08] hover:border-primary/50 text-xs font-bold text-white transition-all flex flex-col sm:flex-row justify-between items-center gap-1 cursor-pointer"
                            data-match-id="{{ $m->match_id }}" data-home="{{ $m->home_team }}" data-away="{{ $m->away_team }}" data-selection="home" data-odds="{{ $m->odds_home }}">
                        <span class="text-on-surface-muted text-[10px] sm:text-xs truncate max-w-[90px]">1 (Home)</span>
                        <span class="text-primary font-mono-jet text-xs sm:text-sm font-bold">{{ number_format($m->odds_home, 2) }}</span>
                    </button>

                    <!-- Draw Button -->
                    <button type="button" class="btn-odd p-2.5 sm:p-3 rounded-xl bg-[#182030] border border-white/[0.08] hover:border-primary/50 text-xs font-bold text-white transition-all flex flex-col sm:flex-row justify-between items-center gap-1 cursor-pointer"
                            data-match-id="{{ $m->match_id }}" data-home="{{ $m->home_team }}" data-away="{{ $m->away_team }}" data-selection="draw" data-odds="{{ $m->odds_draw ?? 3.20 }}">
                        <span class="text-on-surface-muted text-[10px] sm:text-xs">X (Draw)</span>
                        <span class="text-secondary font-mono-jet text-xs sm:text-sm font-bold">{{ number_format($m->odds_draw ?? 3.20, 2) }}</span>
                    </button>

                    <!-- Away Win Button -->
                    <button type="button" class="btn-odd p-2.5 sm:p-3 rounded-xl bg-[#182030] border border-white/[0.08] hover:border-primary/50 text-xs font-bold text-white transition-all flex flex-col sm:flex-row justify-between items-center gap-1 cursor-pointer"
                            data-match-id="{{ $m->match_id }}" data-home="{{ $m->home_team }}" data-away="{{ $m->away_team }}" data-selection="away" data-odds="{{ $m->odds_away }}">
                        <span class="text-on-surface-muted text-[10px] sm:text-xs truncate max-w-[90px]">2 (Away)</span>
                        <span class="text-primary font-mono-jet text-xs sm:text-sm font-bold">{{ number_format($m->odds_away, 2) }}</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-16 bg-[#121622] rounded-3xl border border-white/[0.06]">
                <span class="material-symbols-outlined text-4xl text-on-surface-subtle mb-2">sports_soccer</span>
                <p class="text-on-surface-muted text-sm font-medium">No live fixtures currently scheduled.</p>
                <p class="text-xs text-on-surface-subtle mt-1">Check back shortly or sync fixtures via Liteback admin.</p>
            </div>
        @endforelse
    </div>

    <!-- Desktop Sticky Betslip Sidebar (Right Column) -->
    <div class="hidden lg:block space-y-4">
        <div class="sticky top-6 bg-[#121622] rounded-3xl p-5 border border-white/[0.08] shadow-xl space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-white/[0.08]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">receipt_long</span>
                    <h3 class="font-extrabold text-sm text-white uppercase tracking-tight">Betslip</h3>
                </div>
                <button type="button" id="btn-clear-slip" class="text-xs text-accent-rose hover:underline font-bold uppercase transition-colors">
                    Clear Slip
                </button>
            </div>

            <!-- Mode Switcher Tabs (PARLAY vs SINGLES) -->
            <div class="grid grid-cols-2 gap-1 p-1 bg-black/40 rounded-xl border border-white/[0.08] text-xs font-bold uppercase">
                <button type="button" id="btn-mode-parlay" class="py-2 rounded-lg transition-all text-center bg-primary text-white shadow-sm">
                    🎟️ Parlay
                </button>
                <button type="button" id="btn-mode-singles" class="py-2 rounded-lg transition-all text-center text-on-surface-muted hover:text-white">
                    🎯 Singles
                </button>
            </div>

            <!-- Selected Match Legs List -->
            <div id="betslip-legs" class="space-y-2 text-xs font-mono-jet max-h-[320px] overflow-y-auto custom-scrollbar pr-1">
                <p class="text-on-surface-muted text-center py-8 text-xs">Click any odds on the left to build your betslip.</p>
            </div>

            <!-- Betslip Summary & Controls -->
            <div class="space-y-3 pt-3 border-t border-white/[0.08]">
                <div class="flex justify-between text-xs font-mono-jet">
                    <span class="text-on-surface-muted">Bet Mode:</span>
                    <span id="betslip-type" class="text-primary font-bold uppercase">PARLAY</span>
                </div>
                <div id="row-total-odds" class="flex justify-between text-xs font-mono-jet">
                    <span class="text-on-surface-muted">Combined Odds:</span>
                    <span id="betslip-total-odds" class="text-secondary font-bold text-sm">1.00</span>
                </div>
                <div id="row-parlay-boost" class="hidden justify-between text-xs font-mono-jet text-emerald-400 font-bold bg-emerald-500/10 p-2 rounded-lg border border-emerald-500/20">
                    <span>🔥 Accumulator Boost:</span>
                    <span id="betslip-boost-val">+0%</span>
                </div>

                <div class="space-y-1.5">
                    <label id="lbl-stake" for="betslip-stake" class="text-[11px] font-bold text-on-surface-muted uppercase">Stake per Bet (Cedar Coins)</label>
                    <input type="number" id="betslip-stake" value="1000" min="100" step="100" class="w-full text-sm font-mono-jet text-primary font-bold p-3 bg-black/40 border border-white/10 rounded-xl focus:border-primary focus:outline-none">
                    
                    <!-- Quick Stake Chips -->
                    <div class="grid grid-cols-4 gap-1.5 pt-1">
                        <button type="button" class="btn-quick-stake py-1 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-[11px] font-bold text-on-surface-muted hover:text-white" data-amount="500">+500</button>
                        <button type="button" class="btn-quick-stake py-1 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-[11px] font-bold text-on-surface-muted hover:text-white" data-amount="1000">+1K</button>
                        <button type="button" class="btn-quick-stake py-1 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-[11px] font-bold text-on-surface-muted hover:text-white" data-amount="5000">+5K</button>
                        <button type="button" class="btn-quick-stake py-1 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] text-[11px] font-bold text-accent-gold" data-amount="10000">10K</button>
                    </div>
                </div>

                <div class="flex justify-between text-xs font-mono-jet text-on-surface-muted pt-1">
                    <span>Total Cost:</span>
                    <span id="betslip-total-cost" class="text-white font-bold">1,000 CEDARS</span>
                </div>

                <div class="flex justify-between text-sm font-mono-jet font-bold pt-1">
                    <span class="text-white">Potential Win:</span>
                    <span id="betslip-potential" class="text-primary text-base">0 CEDARS</span>
                </div>

                <button type="button" id="btn-place-wager" class="w-full bg-primary hover:bg-primary-dark text-white py-3 rounded-xl font-bold text-xs uppercase tracking-wider shadow-lg shadow-primary/25 transition-all">
                    Place Social Wager
                </button>
            </div>
        </div>

        <!-- Recent Wagers Card -->
        @if(isset($userBets) && count($userBets) > 0)
            <div class="bg-[#121622] rounded-3xl p-5 border border-white/[0.08] space-y-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-base">history</span> Recent Wagers
                </h3>
                <div class="space-y-2 text-xs font-mono-jet">
                    @foreach($userBets as $b)
                        <div class="p-3 rounded-xl bg-[#161c2b] border border-white/[0.06] space-y-1">
                            <div class="flex justify-between items-center">
                                @php $legsCount = is_array($b->legs_json) ? count($b->legs_json) : (is_string($b->legs_json) ? count(json_decode($b->legs_json, true) ?? []) : 0); @endphp
                                <span class="font-bold uppercase text-[10px] text-secondary">{{ strtoupper($b->type) }} ({{ $legsCount }} LEGS)</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $b->status === 'won' ? 'bg-primary/20 text-primary' : ($b->status === 'lost' ? 'bg-accent-rose/20 text-accent-rose' : 'bg-white/10 text-on-surface-muted') }}">
                                    {{ $b->status }}
                                </span>
                            </div>
                            <div class="flex justify-between text-[11px] pt-1">
                                <span class="text-on-surface-muted">Stake: {{ number_format($b->stake, 0) }}</span>
                                <span class="text-primary font-bold">Odds: {{ number_format($b->total_odds, 2) }}</span>
                            </div>
                            @if($b->status === 'pending')
                                <div class="pt-2 border-t border-white/5 flex items-center justify-between">
                                    <button type="button" class="btn-cashout-action px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-bold text-[10px] border border-amber-500/40 transition-all flex items-center gap-1.5" data-bet-id="{{ $b->id }}" data-quoted="false">
                                        <span>💰 Cash Out</span>
                                        <span class="cashout-offer-val font-mono-jet text-white font-extrabold" id="cashout-label-{{ $b->id }}">Quote</span>
                                    </button>
                                    <span class="text-[9px] text-on-surface-muted">Live Fair Value</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Floating Mobile Betslip Trigger Button (FAB) -->
<button id="btn-mobile-betslip-fab" type="button" class="fixed bottom-20 right-4 z-40 lg:hidden bg-gradient-to-r from-primary to-primary-dark text-white px-4 py-3 rounded-2xl font-bold text-xs tracking-wider shadow-2xl shadow-primary/40 flex items-center gap-2 border border-white/10 transition-transform active:scale-95">
    <span class="material-symbols-outlined text-lg">receipt_long</span>
    <span>SLIP (<span id="mobile-betslip-count">0</span>)</span>
</button>

<!-- Mobile Betslip Slide-Up Drawer Overlay -->
<div id="mobile-betslip-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[90] hidden transition-opacity duration-300 lg:hidden"></div>

<!-- Mobile Betslip Slide-Up Sheet -->
<div id="mobile-betslip-sheet" class="fixed left-0 right-0 bottom-0 max-h-[85vh] bg-[#121622]/98 border-t border-white/10 rounded-t-3xl backdrop-blur-2xl z-[100] transform translate-y-full transition-transform duration-300 flex flex-col p-5 shadow-2xl overflow-y-auto lg:hidden custom-scrollbar">
    <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-4 flex-shrink-0 cursor-pointer" id="betslip-drag-handle"></div>

    <div class="flex justify-between items-center pb-3 border-b border-white/[0.08] mb-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-tight flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-lg">receipt_long</span>
            <span>Betslip (<span id="mobile-sheet-count">0</span> Legs)</span>
        </h3>
        <div class="flex items-center gap-3">
            <button type="button" id="btn-clear-slip-mobile" class="text-xs text-accent-rose font-bold uppercase hover:underline">Clear</button>
            <button id="btn-close-betslip-sheet" type="button" class="text-on-surface-subtle hover:text-white p-1 rounded-xl bg-white/[0.04]">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
    </div>

    <!-- Mode Switcher Tabs (PARLAY vs SINGLES) -->
    <div class="grid grid-cols-2 gap-1 p-1 bg-black/40 rounded-xl border border-white/[0.08] text-xs font-bold uppercase mb-4">
        <button type="button" id="btn-mode-parlay-mobile" class="py-2.5 rounded-lg transition-all text-center bg-primary text-white shadow-sm">
            🎟️ Parlay
        </button>
        <button type="button" id="btn-mode-singles-mobile" class="py-2.5 rounded-lg transition-all text-center text-on-surface-muted hover:text-white">
            🎯 Singles
        </button>
    </div>

    <!-- Selected Legs Container -->
    <div id="betslip-legs-mobile" class="space-y-2 text-xs font-mono-jet min-h-[80px] mb-4">
        <p class="text-on-surface-muted text-center py-6 text-xs">Click any match odds to build your betslip.</p>
    </div>

    <!-- Betslip Summary -->
    <div class="space-y-3 pt-3 border-t border-white/[0.08] mb-2">
        <div class="flex justify-between text-xs font-mono-jet">
            <span class="text-on-surface-muted">Bet Mode:</span>
            <span id="betslip-type-mobile" class="text-primary font-bold uppercase">PARLAY</span>
        </div>
        <div id="row-total-odds-mobile" class="flex justify-between text-xs font-mono-jet">
            <span class="text-on-surface-muted">Combined Odds:</span>
            <span id="betslip-total-odds-mobile" class="text-secondary font-bold">1.00</span>
        </div>
        <div id="row-parlay-boost-mobile" class="hidden justify-between text-xs font-mono-jet text-emerald-400 font-bold bg-emerald-500/10 p-2 rounded-lg border border-emerald-500/20">
            <span>🔥 Accumulator Boost:</span>
            <span id="betslip-boost-val-mobile">+0%</span>
        </div>

        <div class="form-group space-y-1">
            <label id="lbl-stake-mobile" for="betslip-stake-mobile" class="text-xs text-on-surface-muted uppercase">Stake per Bet (Cedar Coins)</label>
            <input type="number" id="betslip-stake-mobile" value="1000" min="100" step="100" class="w-full text-sm font-mono-jet text-primary font-bold p-3 bg-black/40 border border-white/10 rounded-xl focus:border-primary focus:outline-none">
        </div>

        <div class="flex justify-between text-xs font-mono-jet text-on-surface-muted">
            <span>Total Cost:</span>
            <span id="betslip-total-cost-mobile" class="text-white font-bold">1,000 CEDARS</span>
        </div>

        <div class="flex justify-between text-sm font-mono-jet font-bold pt-1">
            <span class="text-white">Est. Payout:</span>
            <span id="betslip-potential-mobile" class="text-primary text-base">0 CEDARS</span>
        </div>

        <button type="button" id="btn-place-wager-mobile" class="w-full bg-primary hover:bg-primary-dark text-white py-3.5 rounded-xl font-bold text-xs uppercase tracking-wider shadow-lg shadow-primary/25 transition-all mt-2">
            Place Social Wager
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let betslip = [];
    let activeMode = 'parlay'; // 'parlay' or 'singles'

    const oddBtns = document.querySelectorAll('.btn-odd');
    
    // Desktop elements
    const legsContainer = document.getElementById('betslip-legs');
    const typeElem = document.getElementById('betslip-type');
    const rowTotalOdds = document.getElementById('row-total-odds');
    const oddsElem = document.getElementById('betslip-total-odds');
    const potentialElem = document.getElementById('betslip-potential');
    const costElem = document.getElementById('betslip-total-cost');
    const stakeLabel = document.getElementById('lbl-stake');
    const stakeInput = document.getElementById('betslip-stake');
    const clearBtn = document.getElementById('btn-clear-slip');
    const placeBtn = document.getElementById('btn-place-wager');

    const btnModeParlay = document.getElementById('btn-mode-parlay');
    const btnModeSingles = document.getElementById('btn-mode-singles');

    // Mobile elements
    const fabBtn = document.getElementById('btn-mobile-betslip-fab');
    const mobileCount = document.getElementById('mobile-betslip-count');
    const mobileSheetCount = document.getElementById('mobile-sheet-count');
    const mobileSheet = document.getElementById('mobile-betslip-sheet');
    const mobileOverlay = document.getElementById('mobile-betslip-overlay');
    const closeSheetBtn = document.getElementById('btn-close-betslip-sheet');
    const dragHandle = document.getElementById('betslip-drag-handle');
    const clearBtnMobile = document.getElementById('btn-clear-slip-mobile');
    const placeBtnMobile = document.getElementById('btn-place-wager-mobile');

    const legsContainerMobile = document.getElementById('betslip-legs-mobile');
    const typeElemMobile = document.getElementById('betslip-type-mobile');
    const rowTotalOddsMobile = document.getElementById('row-total-odds-mobile');
    const oddsElemMobile = document.getElementById('betslip-total-odds-mobile');
    const potentialElemMobile = document.getElementById('betslip-potential-mobile');
    const costElemMobile = document.getElementById('betslip-total-cost-mobile');
    const stakeLabelMobile = document.getElementById('lbl-stake-mobile');
    const stakeInputMobile = document.getElementById('betslip-stake-mobile');

    const btnModeParlayMobile = document.getElementById('btn-mode-parlay-mobile');
    const btnModeSinglesMobile = document.getElementById('btn-mode-singles-mobile');

    function openMobileBetslip() {
        if (mobileOverlay && mobileSheet) {
            mobileOverlay.classList.remove('hidden');
            setTimeout(() => {
                mobileSheet.classList.remove('translate-y-full');
            }, 10);
        }
    }

    function closeMobileBetslip() {
        if (mobileOverlay && mobileSheet) {
            mobileSheet.classList.add('translate-y-full');
            setTimeout(() => {
                mobileOverlay.classList.add('hidden');
            }, 300);
        }
    }

    if (fabBtn) fabBtn.addEventListener('click', openMobileBetslip);
    if (closeSheetBtn) closeSheetBtn.addEventListener('click', closeMobileBetslip);
    if (dragHandle) dragHandle.addEventListener('click', closeMobileBetslip);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileBetslip);

    // Quick Stake Chips
    document.querySelectorAll('.btn-quick-stake').forEach(btn => {
        btn.addEventListener('click', function() {
            const addAmt = parseInt(this.dataset.amount);
            const current = parseInt(stakeInput.value) || 0;
            if (this.dataset.amount === '10000') {
                stakeInput.value = 10000;
            } else {
                stakeInput.value = current + addAmt;
            }
            if (stakeInputMobile) stakeInputMobile.value = stakeInput.value;
            renderBetslip();
        });
    });

    // Mode Switching Handler
    function setMode(mode) {
        activeMode = mode;
        const activeCls = "py-2 sm:py-2.5 rounded-lg transition-all text-center bg-primary text-white shadow-sm";
        const inactiveCls = "py-2 sm:py-2.5 rounded-lg transition-all text-center text-on-surface-muted hover:text-white";

        if (mode === 'parlay') {
            if (btnModeParlay) btnModeParlay.className = activeCls;
            if (btnModeSingles) btnModeSingles.className = inactiveCls;
            if (btnModeParlayMobile) btnModeParlayMobile.className = activeCls;
            if (btnModeSinglesMobile) btnModeSinglesMobile.className = inactiveCls;
            if (typeElem) typeElem.innerText = "PARLAY";
            if (typeElemMobile) typeElemMobile.innerText = "PARLAY";
            if (rowTotalOdds) rowTotalOdds.style.display = "flex";
            if (rowTotalOddsMobile) rowTotalOddsMobile.style.display = "flex";
            if (stakeLabel) stakeLabel.innerText = "Parlay Stake (Cedar Coins)";
            if (stakeLabelMobile) stakeLabelMobile.innerText = "Parlay Stake (Cedar Coins)";
        } else {
            if (btnModeSingles) btnModeSingles.className = activeCls;
            if (btnModeParlay) btnModeParlay.className = inactiveCls;
            if (btnModeSinglesMobile) btnModeSinglesMobile.className = activeCls;
            if (btnModeParlayMobile) btnModeParlayMobile.className = inactiveCls;
            if (typeElem) typeElem.innerText = "SINGLES";
            if (typeElemMobile) typeElemMobile.innerText = "SINGLES";
            if (rowTotalOdds) rowTotalOdds.style.display = "none";
            if (rowTotalOddsMobile) rowTotalOddsMobile.style.display = "none";
            if (stakeLabel) stakeLabel.innerText = "Stake Per Leg (Cedar Coins)";
            if (stakeLabelMobile) stakeLabelMobile.innerText = "Stake Per Leg (Cedar Coins)";
        }
        renderBetslip();
    }

    if (btnModeParlay) btnModeParlay.addEventListener('click', () => setMode('parlay'));
    if (btnModeSingles) btnModeSingles.addEventListener('click', () => setMode('singles'));
    if (btnModeParlayMobile) btnModeParlayMobile.addEventListener('click', () => setMode('parlay'));
    if (btnModeSinglesMobile) btnModeSinglesMobile.addEventListener('click', () => setMode('singles'));

    // Handle Odds Button Click
    oddBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const matchId = this.dataset.matchId;
            const home = this.dataset.home;
            const away = this.dataset.away;
            const selection = this.dataset.selection;
            const odds = parseFloat(this.dataset.odds);

            const existingIdx = betslip.findIndex(item => item.matchId === matchId);
            if (existingIdx !== -1) {
                if (betslip[existingIdx].selection === selection) {
                    betslip.splice(existingIdx, 1);
                } else {
                    betslip[existingIdx].selection = selection;
                    betslip[existingIdx].odds = odds;
                }
            } else {
                betslip.push({
                    matchId: matchId,
                    home: home,
                    away: away,
                    selection: selection,
                    odds: odds
                });
            }

            renderBetslip();
            updateButtonStyles();
        });
    });

    function updateButtonStyles() {
        oddBtns.forEach(btn => {
            const matchId = btn.dataset.matchId;
            const selection = btn.dataset.selection;
            const isSelected = betslip.some(item => item.matchId === matchId && item.selection === selection);

            if (isSelected) {
                btn.classList.add('border-primary', 'bg-primary/20', 'text-primary');
                btn.classList.remove('bg-[#182030]', 'border-white/[0.08]');
            } else {
                btn.classList.remove('border-primary', 'bg-primary/20', 'text-primary');
                btn.classList.add('bg-[#182030]', 'border-white/[0.08]');
            }
        });
    }

    function renderBetslip() {
        const count = betslip.length;
        if (mobileCount) mobileCount.innerText = count;
        if (mobileSheetCount) mobileSheetCount.innerText = count;

        let legsHtml = '';
        if (count === 0) {
            legsHtml = '<p class="text-on-surface-muted text-center py-6 text-xs">Click any match odds to build your betslip.</p>';
        } else {
            betslip.forEach((item, idx) => {
                let pickLabel = item.selection === 'home' ? item.home : (item.selection === 'away' ? item.away : 'Draw');
                legsHtml += `
                    <div class="p-2.5 rounded-xl bg-[#161c2b] border border-white/[0.06] flex justify-between items-center text-xs">
                        <div class="flex-1 pr-2">
                            <span class="text-white font-bold block truncate text-xs">${item.home} vs ${item.away}</span>
                            <span class="text-on-surface-muted text-[11px]">Pick: <strong class="text-primary font-bold">${pickLabel}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-secondary font-mono-jet">${item.odds.toFixed(2)}</span>
                            <button type="button" class="btn-remove-leg text-on-surface-subtle hover:text-accent-rose p-1" data-index="${idx}">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </button>
                        </div>
                    </div>
                `;
            });
        }

        if (legsContainer) legsContainer.innerHTML = legsHtml;
        if (legsContainerMobile) legsContainerMobile.innerHTML = legsHtml;

        // Add remove handlers
        document.querySelectorAll('.btn-remove-leg').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const idx = parseInt(this.dataset.index);
                betslip.splice(idx, 1);
                renderBetslip();
                updateButtonStyles();
            });
        });

        // Calculate Totals & Dynamic Accumulator Boost
        const stake = parseFloat(stakeInput ? stakeInput.value : (stakeInputMobile ? stakeInputMobile.value : 1000)) || 0;

        let boostPercent = 0;
        if (activeMode === 'parlay' && count >= 3) {
            if (count >= 8) boostPercent = 50;
            else if (count === 7) boostPercent = 30;
            else if (count === 6) boostPercent = 20;
            else if (count === 5) boostPercent = 15;
            else if (count === 4) boostPercent = 10;
            else boostPercent = 5;
        }

        const rowBoost = document.getElementById('row-parlay-boost');
        const valBoost = document.getElementById('betslip-boost-val');
        const rowBoostMobile = document.getElementById('row-parlay-boost-mobile');
        const valBoostMobile = document.getElementById('betslip-boost-val-mobile');

        if (boostPercent > 0) {
            if (rowBoost) { rowBoost.classList.remove('hidden'); rowBoost.classList.add('flex'); }
            if (valBoost) valBoost.innerText = `+${boostPercent}% (${count} Legs)`;
            if (rowBoostMobile) { rowBoostMobile.classList.remove('hidden'); rowBoostMobile.classList.add('flex'); }
            if (valBoostMobile) valBoostMobile.innerText = `+${boostPercent}% (${count} Legs)`;
        } else {
            if (rowBoost) { rowBoost.classList.add('hidden'); rowBoost.classList.remove('flex'); }
            if (rowBoostMobile) { rowBoostMobile.classList.add('hidden'); rowBoostMobile.classList.remove('flex'); }
        }

        let totalOdds = 1.0;
        let totalCost = 0;
        let potentialWin = 0;

        if (count > 0) {
            if (activeMode === 'parlay') {
                totalOdds = betslip.reduce((acc, item) => acc * item.odds, 1.0);
                const boostMultiplier = 1 + (boostPercent / 100);
                totalCost = stake;
                potentialWin = Math.floor(totalCost * totalOdds * boostMultiplier);
            } else {
                totalCost = stake * count;
                potentialWin = betslip.reduce((acc, item) => acc + Math.floor(stake * item.odds), 0);
                totalOdds = betslip.length > 0 ? (potentialWin / totalCost) : 1.0;
            }
        }

        if (oddsElem) oddsElem.innerText = totalOdds.toFixed(2);
        if (oddsElemMobile) oddsElemMobile.innerText = totalOdds.toFixed(2);

        if (costElem) costElem.innerText = totalCost.toLocaleString() + ' CEDARS';
        if (costElemMobile) costElemMobile.innerText = totalCost.toLocaleString() + ' CEDARS';

        if (potentialElem) potentialElem.innerText = potentialWin.toLocaleString() + ' CEDARS';
        if (potentialElemMobile) potentialElemMobile.innerText = potentialWin.toLocaleString() + ' CEDARS';
    }

    if (stakeInput) stakeInput.addEventListener('input', () => {
        if (stakeInputMobile) stakeInputMobile.value = stakeInput.value;
        renderBetslip();
    });
    if (stakeInputMobile) stakeInputMobile.addEventListener('input', () => {
        if (stakeInput) stakeInput.value = stakeInputMobile.value;
        renderBetslip();
    });

    if (clearBtn) clearBtn.addEventListener('click', () => {
        betslip = [];
        renderBetslip();
        updateButtonStyles();
    });
    if (clearBtnMobile) clearBtnMobile.addEventListener('click', () => {
        betslip = [];
        renderBetslip();
        updateButtonStyles();
    });

    // Place Bet Action
    function placeBet() {
        if (betslip.length === 0) {
            alert('Your betslip is empty! Select at least one match odd.');
            return;
        }

        const stake = parseFloat(stakeInput ? stakeInput.value : stakeInputMobile.value);
        if (!stake || stake <= 0) {
            alert('Please enter a valid stake amount.');
            return;
        }

        const payload = {
            type: activeMode,
            stake: stake,
            legs: betslip.map(item => ({
                match_id: item.matchId,
                home: item.home,
                away: item.away,
                selection: item.selection,
                odds: item.odds
            }))
        };

        const currentBtn = placeBtn || placeBtnMobile;
        if (currentBtn) currentBtn.disabled = true;

        fetch('/sports/bet', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (currentBtn) currentBtn.disabled = false;
            if (data.success) {
                alert('🎉 ' + data.message);
                betslip = [];
                renderBetslip();
                updateButtonStyles();
                closeMobileBetslip();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            if (currentBtn) currentBtn.disabled = false;
            alert('Error placing bet: ' + err.message);
        });
    }

    if (placeBtn) placeBtn.addEventListener('click', placeBet);
    if (placeBtnMobile) placeBtnMobile.addEventListener('click', placeBet);

    // Early Cashout Handlers
    document.querySelectorAll('.btn-cashout-action').forEach(btn => {
        btn.addEventListener('click', async function() {
            const betId = this.dataset.betId;
            const isQuoted = this.dataset.quoted === 'true';
            const labelEl = document.getElementById(`cashout-label-${betId}`);

            if (!isQuoted) {
                // Step 1: Fetch live quote
                if (labelEl) labelEl.innerText = 'Calculating...';
                this.disabled = true;

                try {
                    const res = await fetch(`/sports/cashout-quote/${betId}`);
                    const data = await res.json();
                    this.disabled = false;

                    if (data.success && data.can_cashout && data.cashout_value > 0) {
                        this.dataset.quoted = 'true';
                        this.dataset.cashoutVal = data.cashout_value;
                        if (labelEl) labelEl.innerText = `${data.cashout_value.toLocaleString()} CED (Confirm)`;
                        this.classList.remove('bg-amber-500/20', 'text-amber-300');
                        this.classList.add('bg-emerald-500', 'text-black', 'animate-pulse');
                    } else {
                        alert(data.message || 'Cashout is not available for this wager.');
                        if (labelEl) labelEl.innerText = 'Unavailable';
                        this.disabled = true;
                    }
                } catch (e) {
                    this.disabled = false;
                    if (labelEl) labelEl.innerText = 'Error';
                }
            } else {
                // Step 2: Confirm cashout execution
                const cashoutVal = parseFloat(this.dataset.cashoutVal);
                if (!confirm(`Confirm Early Cash Out for ${cashoutVal.toLocaleString()} Cedar Coins?`)) {
                    return;
                }

                this.disabled = true;
                if (labelEl) labelEl.innerText = 'Processing...';

                try {
                    const res = await fetch('/sports/cashout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ bet_id: betId })
                    });
                    const data = await res.json();

                    if (data.success) {
                        alert('🎉 ' + data.message);
                        if (labelEl) labelEl.innerText = 'Cashed Out';
                        this.className = 'px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold text-[10px] border border-emerald-500/40 cursor-default';
                        this.innerHTML = '<span>✓ Cashed Out</span>';
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        alert('❌ ' + data.message);
                        this.disabled = false;
                        if (labelEl) labelEl.innerText = 'Retry';
                    }
                } catch (e) {
                    this.disabled = false;
                    alert('Cashout request failed: ' + e.message);
                }
            }
        });
    });
});
</script>

@endsection
