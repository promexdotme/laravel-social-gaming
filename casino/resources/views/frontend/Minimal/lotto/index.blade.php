@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Jackpot Zone Lotto - Casino du Liban')

@section('content')

<!-- Header -->
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/25 px-3 py-1 rounded-full mb-1">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                <span class="text-[11px] font-bold text-primary font-mono-jet uppercase tracking-wider">OFFICIAL SCHEDULED LOTTO</span>
            </div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight uppercase">Jackpot Lotto Zone</h1>
            <p class="text-on-surface-muted text-xs sm:text-sm mt-0.5">Pick your lotto game, select lucky numbers, and enter the official Daily Midnight Draw!</p>
        </div>
    </div>

    <!-- Active Lotto Games Selector Cards (Option to choose which game to play) -->
    <div class="space-y-2">
        <div class="flex justify-between items-center text-xs font-bold text-on-surface-muted uppercase tracking-wider">
            <span>Select Active Lotto Game ({{ count($activeGames) }} Available)</span>
            <span class="text-secondary font-mono-jet">Official Daily Midnight Draw</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($activeGames as $g)
                @php
                    $isSelected = ($currentGame && $currentGame->id === $g->id);
                @endphp
                <a href="?game={{ $g->slug }}" class="p-5 rounded-3xl border transition-all no-underline relative flex flex-col justify-between space-y-4 group {{ $isSelected ? 'bg-gradient-to-br from-[#1b253b] to-[#121622] border-primary shadow-xl shadow-primary/20 ring-2 ring-primary/40' : 'bg-[#121622] border-white/[0.08] hover:border-white/20 hover:bg-[#161c2b]' }}">
                    <div class="flex justify-between items-center text-xs font-mono-jet">
                        <span class="px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider text-[10px] {{ $isSelected ? 'bg-primary text-white shadow-sm' : 'bg-white/[0.06] text-on-surface-muted group-hover:text-white' }}">
                            {{ $isSelected ? '✓ CURRENT SELECTION' : 'SWITCH TO GAME' }}
                        </span>
                        <span class="text-[11px] font-bold text-accent-gold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">schedule</span>
                            <span>Daily 00:00 UTC</span>
                        </span>
                    </div>

                    <div>
                        <h3 class="font-extrabold text-base sm:text-lg text-white uppercase tracking-tight group-hover:text-primary transition-all">
                            {{ $g->title }}
                        </h3>
                        <p class="text-xs text-on-surface-muted mt-0.5">
                            Pick <strong class="text-white font-mono-jet">{{ $g->pick_count }}</strong> numbers from <strong class="text-white font-mono-jet">1 to {{ $g->max_number }}</strong>
                        </p>
                    </div>

                    <div class="pt-3 border-t border-white/[0.06] flex justify-between items-end text-xs font-mono-jet">
                        <div>
                            <span class="text-[10px] text-on-surface-subtle uppercase block">Jackpot Pool</span>
                            <span class="text-sm font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-accent-gold to-yellow-300">
                                {{ number_format($g->jackpot_pool, 0) }} C
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-on-surface-subtle uppercase block">Ticket Cost</span>
                            <span class="text-xs font-bold text-primary">
                                {{ number_format($g->entry_fee, 0) }} C
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

@if($currentGame)
<!-- Jackpot Banner for Selected Game with Live Midnight Countdown -->
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#161c2b] via-[#1a2336] to-[#121622] p-6 sm:p-8 border border-white/[0.08] shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6 mt-5">
    <div class="space-y-2 text-center md:text-left">
        <div class="flex items-center justify-center md:justify-start gap-2">
            <span class="px-2.5 py-0.5 rounded bg-primary/20 text-primary border border-primary/30 text-[10px] font-mono-jet font-bold uppercase">
                PLAYING: {{ strtoupper($currentGame->title) }}
            </span>
            <span class="text-xs font-mono-jet text-on-surface-muted">
                Pick {{ $currentGame->pick_count }} of {{ $currentGame->max_number }}
            </span>
        </div>
        
        <div class="font-mono-jet text-3xl sm:text-4xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-accent-gold via-yellow-200 to-yellow-400 tracking-tight">
            {{ number_format($currentGame->jackpot_pool, 0) }} CEDAR COINS
        </div>

        <!-- Countdown Timer Indicator -->
        <div class="flex items-center justify-center md:justify-start gap-2 text-xs font-mono-jet text-on-surface-muted pt-1">
            <span class="material-symbols-outlined text-sm text-accent-gold animate-pulse">timer</span>
            <span>Next Official Midnight Draw in:</span>
            <span id="midnight-countdown-timer" class="text-white font-extrabold bg-black/40 px-2.5 py-1 rounded-lg border border-white/10 font-mono-jet">--h --m --s</span>
            <span class="text-[10px] text-on-surface-subtle">(Daily at 00:00 UTC)</span>
        </div>
    </div>

    <!-- Quick Pick Generator Button -->
    <div class="flex items-center gap-3 flex-wrap justify-center">
        <button type="button" id="btn-quick-pick" class="bg-gradient-to-r from-accent-gold to-accent-amber hover:from-yellow-400 hover:to-accent-gold text-black font-extrabold px-6 py-3.5 rounded-2xl text-xs uppercase tracking-wider shadow-lg shadow-accent-gold/25 transition-all flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined text-lg">auto_awesome</span>
            <span>Quick Pick {{ $currentGame->pick_count }}</span>
        </button>
        <button type="button" id="btn-clear-numbers" class="bg-white/[0.04] hover:bg-white/[0.08] text-on-surface-muted hover:text-white px-5 py-3.5 rounded-2xl text-xs font-bold uppercase tracking-wider transition-all border border-white/[0.06] cursor-pointer">
            Clear
        </button>
    </div>
    
    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-accent-gold/10 rounded-full blur-3xl pointer-events-none"></div>
</div>

<!-- Ticket Picker Grid & Sidebar -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-5">
    <!-- Number Selection Panel -->
    <div class="lg:col-span-2 bg-[#121622] rounded-3xl p-5 sm:p-7 border border-white/[0.08] shadow-md space-y-6">
        <div class="flex justify-between items-center pb-3 border-b border-white/[0.06]">
            <div>
                <h3 class="font-bold text-sm sm:text-base text-white uppercase tracking-tight">
                    Select {{ $currentGame->pick_count }} Lucky Numbers
                </h3>
                <p class="text-xs text-on-surface-muted">Highlight your {{ $currentGame->pick_count }} lucky numbers or use Quick Pick</p>
            </div>
            <span class="font-mono-jet text-xs font-bold text-primary bg-primary/10 border border-primary/20 px-3 py-1.5 rounded-xl">
                Ticket: {{ number_format($currentGame->entry_fee, 0) }} C
            </span>
        </div>

        <!-- Selected Ball Slot Display -->
        <div class="flex justify-center gap-2.5 sm:gap-3 bg-[#0b0e14]/60 p-4 sm:p-5 rounded-2xl border border-white/[0.06] flex-wrap">
            @for($s = 0; $s < $currentGame->pick_count; $s++)
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-full border-2 border-dashed border-white/20 flex items-center justify-center font-mono-jet font-bold text-on-surface-subtle text-base sm:text-lg num-slot transition-all" data-slot="{{ $s }}">?</div>
            @endfor
        </div>

        <!-- Dynamic 1 to max_number Ball Grid -->
        <div class="grid grid-cols-5 sm:grid-cols-7 md:grid-cols-10 gap-2">
            @for ($i = 1; $i <= $currentGame->max_number; $i++)
                <button type="button" class="num-btn p-2.5 sm:p-3 rounded-xl bg-[#182030] border border-white/[0.06] font-mono-jet text-xs sm:text-sm font-bold text-white hover:border-primary/50 transition-all text-center cursor-pointer" data-num="{{ $i }}">
                    {{ sprintf('%02d', $i) }}
                </button>
            @endfor
        </div>

        <button type="button" id="btn-submit-ticket" disabled class="w-full bg-primary hover:bg-primary-dark text-white py-4 rounded-xl font-extrabold text-xs sm:text-sm uppercase tracking-wider transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-primary/25 cursor-pointer">
            Submit Ticket Entry ({{ number_format($currentGame->entry_fee, 0) }} Cedar Coins)
        </button>

        <!-- Prize Tiers Explainer Box -->
        <div class="p-4 rounded-2xl bg-[#0b0e14] border border-white/[0.06] space-y-2 text-xs font-mono-jet">
            <div class="font-bold text-white uppercase text-[11px] pb-1 border-b border-white/[0.06] flex justify-between items-center">
                <span>🏆 Prize Distribution Tiers</span>
                <span class="text-accent-gold">Official Rules</span>
            </div>
            <div class="flex justify-between text-accent-gold font-bold">
                <span>Match All {{ $currentGame->pick_count }}/{{ $currentGame->pick_count }} Numbers:</span>
                <span>100% Grand Jackpot ({{ number_format($currentGame->jackpot_pool, 0) }} C)</span>
            </div>
            @if($currentGame->pick_count >= 4)
                <div class="flex justify-between text-primary">
                    <span>Match {{ $currentGame->pick_count - 1 }}/{{ $currentGame->pick_count }} Numbers:</span>
                    <span>10% Jackpot or 500x Entry</span>
                </div>
            @endif
            @if($currentGame->pick_count >= 5)
                <div class="flex justify-between text-secondary">
                    <span>Match {{ $currentGame->pick_count - 2 }}/{{ $currentGame->pick_count }} Numbers:</span>
                    <span>50x Entry Fee ({{ number_format($currentGame->entry_fee * 50, 0) }} C)</span>
                </div>
            @endif
            <div class="flex justify-between text-on-surface-muted">
                <span>Match Partial Numbers:</span>
                <span>Multi-tier Entry Multipliers</span>
            </div>
        </div>
    </div>

    <!-- Sidebar: Previous Winning Draws & Player Tickets -->
    <div class="space-y-4">
        <!-- Recent Winning Draws -->
        <div class="bg-[#121622] rounded-3xl p-5 border border-white/[0.08] space-y-4 shadow-md">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base">auto_awesome</span>
                <span>Recent Official Draws ({{ $currentGame->title }})</span>
            </h3>
            <div class="space-y-3 pt-1">
                @forelse ($recentDraws as $d)
                    <div class="p-3.5 rounded-2xl bg-[#161c2b] border border-white/[0.06] space-y-2.5">
                        <div class="flex justify-between text-xs font-mono-jet">
                            <span class="text-on-surface-muted">{{ \Carbon\Carbon::parse($d->draw_date)->format('M d, Y') }}</span>
                            <span class="text-primary font-bold">{{ $d->total_winners }} Winners</span>
                        </div>
                        <div class="flex gap-1.5 flex-wrap">
                            @foreach($d->winning_numbers_json ?? [] as $wn)
                                <span class="w-8 h-8 rounded-full bg-primary/20 border border-primary/40 text-primary flex items-center justify-center font-mono-jet text-xs font-bold shadow-sm">{{ $wn }}</span>
                            @endforeach
                        </div>
                        @if($d->total_paid > 0)
                            <div class="text-[11px] font-mono-jet text-accent-gold">
                                Total Paid: +{{ number_format($d->total_paid, 0) }} Cedar Coins
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-on-surface-muted text-center py-6">No historical draws recorded yet for this game.</p>
                @endforelse
            </div>
        </div>

        <!-- Player Ticket History -->
        @if(Auth::check())
            <div class="bg-[#121622] rounded-3xl p-5 border border-white/[0.08] space-y-4 shadow-md">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-base">confirmation_number</span>
                        <span>Your Active Tickets</span>
                    </h3>
                    <span class="text-xs font-mono-jet text-primary font-bold">{{ count($userTickets) }} TICKETS</span>
                </div>
                
                <div class="space-y-2.5 text-xs font-mono-jet max-h-[350px] overflow-y-auto custom-scrollbar">
                    @forelse($userTickets as $t)
                        <div class="p-3 rounded-xl bg-[#161c2b] border border-white/[0.06] space-y-1.5">
                            <div class="flex justify-between items-center">
                                <span class="text-on-surface-muted text-[10px]">{{ \Carbon\Carbon::parse($t->created_at)->format('M d, H:i') }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $t->status === 'won' || $t->status === 'jackpot_win' ? 'bg-primary/20 text-primary border border-primary/30' : ($t->status === 'lost' ? 'bg-accent-rose/20 text-accent-rose border border-accent-rose/30' : 'bg-secondary/20 text-secondary border border-secondary/30') }}">
                                    {{ $t->status }}
                                </span>
                            </div>
                            <div class="flex gap-1.5 flex-wrap">
                                @foreach($t->numbers_json ?? [] as $tn)
                                    <span class="w-6 h-6 rounded-full bg-white/[0.08] flex items-center justify-center font-bold text-[11px] text-white">{{ $tn }}</span>
                                @endforeach
                            </div>
                            @if($t->payout_amount > 0)
                                <div class="text-[11px] text-accent-gold font-bold">
                                    Won: +{{ number_format($t->payout_amount, 0) }} C
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-muted text-center py-6">No tickets purchased for this game yet. Pick your numbers above!</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pickCount = {{ $currentGame->pick_count }};
    const maxNum = {{ $currentGame->max_number }};
    const gameId = {{ $currentGame->id }};

    let selectedNumbers = [];

    // --- Ticket Picker Logic ---
    const numBtns = document.querySelectorAll('.num-btn');
    const slots = document.querySelectorAll('.num-slot');
    const submitBtn = document.getElementById('btn-submit-ticket');
    const quickPickBtn = document.getElementById('btn-quick-pick');
    const clearBtn = document.getElementById('btn-clear-numbers');

    function updateTicketUI() {
        slots.forEach((slot, idx) => {
            if (idx < selectedNumbers.length) {
                slot.innerText = String(selectedNumbers[idx]).padStart(2, '0');
                slot.className = 'w-11 h-11 sm:w-12 sm:h-12 rounded-full border-2 border-primary bg-primary text-white flex items-center justify-center font-mono-jet font-bold text-base sm:text-lg num-slot shadow-lg shadow-primary/30';
            } else {
                slot.innerText = '?';
                slot.className = 'w-11 h-11 sm:w-12 sm:h-12 rounded-full border-2 border-dashed border-white/20 flex items-center justify-center font-mono-jet font-bold text-on-surface-subtle text-base sm:text-lg num-slot';
            }
        });

        numBtns.forEach(btn => {
            const val = parseInt(btn.dataset.num);
            if (selectedNumbers.includes(val)) {
                btn.className = 'num-btn p-2.5 sm:p-3 rounded-xl bg-primary text-white border border-primary font-mono-jet font-extrabold text-xs sm:text-sm shadow-md shadow-primary/30 text-center cursor-pointer';
            } else {
                btn.className = 'num-btn p-2.5 sm:p-3 rounded-xl bg-[#182030] border border-white/[0.06] font-mono-jet text-xs sm:text-sm font-bold text-white hover:border-primary/50 transition-all text-center cursor-pointer';
            }
        });

        if (submitBtn) {
            submitBtn.disabled = selectedNumbers.length !== pickCount;
        }
    }

    numBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const val = parseInt(this.dataset.num);
            if (selectedNumbers.includes(val)) {
                selectedNumbers = selectedNumbers.filter(n => n !== val);
            } else {
                if (selectedNumbers.length < pickCount) {
                    selectedNumbers.push(val);
                    selectedNumbers.sort((a, b) => a - b);
                } else {
                    alert(`You can only select up to ${pickCount} numbers for this game.`);
                }
            }
            updateTicketUI();
        });
    });

    if (quickPickBtn) {
        quickPickBtn.addEventListener('click', function() {
            selectedNumbers = [];
            while (selectedNumbers.length < pickCount) {
                const r = Math.floor(Math.random() * maxNum) + 1;
                if (!selectedNumbers.includes(r)) {
                    selectedNumbers.push(r);
                }
            }
            selectedNumbers.sort((a, b) => a - b);
            updateTicketUI();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            selectedNumbers = [];
            updateTicketUI();
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            if (selectedNumbers.length !== pickCount) return;

            submitBtn.disabled = true;
            submitBtn.innerText = 'PROCESSING TICKET...';

            fetch('/lotto/play', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    lotto_game_id: gameId,
                    game_id: gameId,
                    numbers: selectedNumbers
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('🎉 ' + data.message);
                    selectedNumbers = [];
                    updateTicketUI();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    alert('❌ ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerText = `Submit Ticket Entry ({{ number_format($currentGame->entry_fee, 0) }} Cedar Coins)`;
                }
            })
            .catch(err => {
                alert('Error submitting ticket: ' + err.message);
                submitBtn.disabled = false;
                submitBtn.innerText = `Submit Ticket Entry ({{ number_format($currentGame->entry_fee, 0) }} Cedar Coins)`;
            });
        });
    }

    // Live Midnight Countdown Timer (UTC Midnight)
    function updateMidnightCountdown() {
        const timerEl = document.getElementById('midnight-countdown-timer');
        if (!timerEl) return;

        const now = new Date();
        const nextMidnight = new Date();
        nextMidnight.setUTCHours(24, 0, 0, 0);

        const diffMs = nextMidnight - now;
        if (diffMs <= 0) {
            timerEl.innerText = "DRAWING NOW...";
            return;
        }

        const hrs = String(Math.floor(diffMs / (1000 * 60 * 60))).padStart(2, '0');
        const mins = String(Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
        const secs = String(Math.floor((diffMs % (1000 * 60)) / 1000)).padStart(2, '0');

        timerEl.innerText = `${hrs}h ${mins}m ${secs}s`;
    }

    setInterval(updateMidnightCountdown, 1000);
    updateMidnightCountdown();
});
</script>
@endif

@endsection
