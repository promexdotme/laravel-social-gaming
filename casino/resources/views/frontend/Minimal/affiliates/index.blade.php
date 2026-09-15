@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Affiliate & Referral Rewards - Casino du Liban')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 bg-secondary/10 border border-secondary/25 px-3 py-1 rounded-full mb-2">
                <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                <span class="text-[11px] font-bold text-secondary font-mono-jet uppercase tracking-wider">LIFETIME 3-TIER PASSIVE INCOME</span>
            </div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight uppercase">Multi-Tier Affiliate Program</h1>
            <p class="text-on-surface-muted text-xs sm:text-sm mt-1">
                Earn automated cash commissions across 3 tiers of referrals on every single bet placed across Casino, Sportsbook, Lotto & Prediction Markets.
            </p>
        </div>

        @if(Auth::check())
        <div class="bg-[#121622] border border-amber-500/30 rounded-2xl p-4 flex items-center justify-between gap-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-amber-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div>
                <span class="text-[10px] uppercase font-bold text-amber-400/80 tracking-widest block">CLAIMABLE COMMISSIONS</span>
                <div class="text-2xl font-black text-amber-300 font-mono-jet mt-0.5">
                    $<span id="unclaimed-amount">{{ number_format($stats['unclaimed_commissions'] ?? 0, 2) }}</span>
                </div>
            </div>
            <button id="btn-claim-commissions" class="bg-gradient-to-r from-amber-500 to-yellow-400 hover:from-amber-400 hover:to-yellow-300 text-black font-extrabold text-xs uppercase px-5 py-3 rounded-xl shadow-lg shadow-amber-500/25 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" {{ ($stats['unclaimed_commissions'] ?? 0) <= 0 ? 'disabled' : '' }}>
                <span class="material-symbols-outlined text-sm font-bold">savings</span>
                <span>Claim to Cash</span>
            </button>
        </div>
        @else
        <div>
            <a href="{{ route('frontend.auth.login') }}" class="bg-secondary hover:bg-secondary-dark text-white px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-wider shadow-lg shadow-secondary/25 transition-all inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">login</span>
                <span>Login to View Stats</span>
            </a>
        </div>
        @endif
    </div>

    <!-- Referral Link & Code Box (Authenticated) -->
    @if(Auth::check())
    <div class="bg-gradient-to-r from-[#141a29] to-[#0e1320] border border-white/[0.08] rounded-2xl p-4 sm:p-6 shadow-xl">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
            <div class="lg:col-span-2 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-xl">share</span>
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider">Your Personal Referral Link</h3>
                </div>
                <p class="text-xs text-on-surface-muted">
                    Share this unique link or code with your friends, community, or audience. Anyone who signs up is permanently assigned as your Tier 1 referral.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-2">
                    <div class="relative w-full">
                        <input type="text" id="ref-link-input" readonly value="{{ $stats['referral_link'] ?? '' }}" class="w-full bg-[#0b0e17] border border-white/[0.12] rounded-xl px-4 py-3 text-xs sm:text-sm text-secondary font-mono-jet focus:outline-none select-all pr-24">
                        <button type="button" onclick="copyRefLink()" class="absolute right-1.5 top-1.5 bottom-1.5 bg-secondary hover:bg-secondary-dark text-white px-4 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">content_copy</span>
                            <span id="copy-btn-text">Copy</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-[#0b0e17] border border-white/[0.06] rounded-xl p-4 flex flex-col justify-center items-center text-center space-y-1">
                <span class="text-[10px] font-bold text-on-surface-muted uppercase tracking-widest">YOUR INVITE CODE</span>
                <div class="text-xl sm:text-2xl font-black text-white font-mono-jet tracking-widest selection:bg-secondary">
                    {{ $stats['invite_code'] ?? '------' }}
                </div>
                <span class="text-[10px] text-secondary font-medium">Auto-applied via link or manual input</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Top KPI Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-5 relative overflow-hidden">
            <span class="text-[10px] sm:text-xs font-bold text-on-surface-muted uppercase tracking-wider block mb-1">Total Lifetime Earned</span>
            <div class="text-xl sm:text-2xl md:text-3xl font-black text-white font-mono-jet">
                $<span id="total-earned">{{ number_format($stats['total_affiliate_earnings'] ?? 0, 2) }}</span>
            </div>
            <div class="text-[11px] text-emerald-400 mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">payments</span> Instant 1-Click Payouts
            </div>
        </div>

        <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-5 relative overflow-hidden">
            <span class="text-[10px] sm:text-xs font-bold text-on-surface-muted uppercase tracking-wider block mb-1">Network Referrals</span>
            <div class="text-xl sm:text-2xl md:text-3xl font-black text-white font-mono-jet">
                {{ number_format($stats['total_referrals'] ?? 0) }}
            </div>
            <div class="text-[11px] text-secondary mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">group</span> Across 3 Full Tiers
            </div>
        </div>

        <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-5 relative overflow-hidden">
            <span class="text-[10px] sm:text-xs font-bold text-on-surface-muted uppercase tracking-wider block mb-1">Total Downline Volume</span>
            <div class="text-xl sm:text-2xl md:text-3xl font-black text-amber-300 font-mono-jet">
                $<span id="total-volume">{{ number_format($stats['total_volume'] ?? 0, 2) }}</span>
            </div>
            <div class="text-[11px] text-amber-400/80 mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">trending_up</span> Cumulative Bets Placed
            </div>
        </div>

        <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-5 relative overflow-hidden">
            <span class="text-[10px] sm:text-xs font-bold text-on-surface-muted uppercase tracking-wider block mb-1">Max Commission Rate</span>
            <div class="text-xl sm:text-2xl md:text-3xl font-black text-secondary font-mono-jet">
                1.00%
            </div>
            <div class="text-[11px] text-on-surface-muted mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">verified</span> 0.5% / 0.3% / 0.2% Split
            </div>
        </div>
    </div>

    <!-- 3-Tier Multi-Level Hierarchy Breakdown -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base sm:text-lg font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary text-xl">account_tree</span>
                <span>3-Tier Referral Hierarchy</span>
            </h2>
            <span class="text-xs text-on-surface-muted font-mono-jet">ALL VERTICALS ELIGIBLE</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Tier 1 -->
            <div class="bg-gradient-to-b from-[#172033] to-[#0f1422] border border-amber-500/30 rounded-2xl p-5 relative overflow-hidden shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <span class="px-2.5 py-1 rounded-md bg-amber-500/20 text-amber-300 font-bold text-[10px] font-mono-jet uppercase tracking-wider">
                        TIER 1 (DIRECT)
                    </span>
                    <span class="text-xs font-extrabold text-amber-400 font-mono-jet">
                        {{ $stats['tier1']['rate'] ?? 0.50 }}%
                    </span>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-on-surface-muted">Direct Recruits:</span>
                        <span class="font-bold text-white font-mono-jet text-sm">{{ $stats['tier1']['count'] ?? 0 }} Users</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-on-surface-muted">Total Earned:</span>
                        <span class="font-bold text-amber-300 font-mono-jet text-sm">${{ number_format($stats['tier1']['earnings'] ?? 0, 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-white/[0.06] text-[11px] text-on-surface-muted leading-relaxed">
                        Earned instantly every time any of your direct invitees wagers on Crash, Mines, Plinko, Sports, Lotto, or Slots.
                    </div>
                </div>
            </div>

            <!-- Tier 2 -->
            <div class="bg-gradient-to-b from-[#151c2e] to-[#0e1320] border border-blue-500/30 rounded-2xl p-5 relative overflow-hidden shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-300 font-bold text-[10px] font-mono-jet uppercase tracking-wider">
                        TIER 2 (SUB-NETWORK)
                    </span>
                    <span class="text-xs font-extrabold text-blue-400 font-mono-jet">
                        {{ $stats['tier2']['rate'] ?? 0.30 }}%
                    </span>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-on-surface-muted">Sub Recruits:</span>
                        <span class="font-bold text-white font-mono-jet text-sm">{{ $stats['tier2']['count'] ?? 0 }} Users</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-on-surface-muted">Total Earned:</span>
                        <span class="font-bold text-blue-300 font-mono-jet text-sm">${{ number_format($stats['tier2']['earnings'] ?? 0, 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-white/[0.06] text-[11px] text-on-surface-muted leading-relaxed">
                        Earned automatically when friends invited by your Tier 1 members play and wager.
                    </div>
                </div>
            </div>

            <!-- Tier 3 -->
            <div class="bg-gradient-to-b from-[#141828] to-[#0c101c] border border-purple-500/30 rounded-2xl p-5 relative overflow-hidden shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <span class="px-2.5 py-1 rounded-md bg-purple-500/20 text-purple-300 font-bold text-[10px] font-mono-jet uppercase tracking-wider">
                        TIER 3 (COMMUNITY)
                    </span>
                    <span class="text-xs font-extrabold text-purple-400 font-mono-jet">
                        {{ $stats['tier3']['rate'] ?? 0.20 }}%
                    </span>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-on-surface-muted">Grand-Sub Recruits:</span>
                        <span class="font-bold text-white font-mono-jet text-sm">{{ $stats['tier3']['count'] ?? 0 }} Users</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-on-surface-muted">Total Earned:</span>
                        <span class="font-bold text-purple-300 font-mono-jet text-sm">${{ number_format($stats['tier3']['earnings'] ?? 0, 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-white/[0.06] text-[11px] text-on-surface-muted leading-relaxed">
                        Passive commissions flowing from 3rd-generation referrals with zero extra effort.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Commission History Table -->
    <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm sm:text-base font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary text-lg">receipt_long</span>
                <span>Recent Commission Activity</span>
            </h3>
            <span class="text-[11px] font-mono-jet text-on-surface-muted">LAST 15 TRANSACTIONS</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-on-surface-muted">
                <thead class="bg-[#0c101c] text-[10px] uppercase font-bold text-on-surface-subtle font-mono-jet">
                    <tr>
                        <th class="p-3 rounded-l-lg">Time</th>
                        <th class="p-3">Player</th>
                        <th class="p-3">Tier</th>
                        <th class="p-3">Vertical</th>
                        <th class="p-3 text-right">Wager</th>
                        <th class="p-3 text-right">Commission</th>
                        <th class="p-3 text-center rounded-r-lg">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @forelse($stats['recent_commissions'] ?? [] as $comm)
                    <tr class="hover:bg-white/[0.02] transition-colors">
                        <td class="p-3 font-mono-jet text-[11px] text-white">
                            {{ $comm->created_at ? $comm->created_at->format('M d, H:i') : 'Just now' }}
                        </td>
                        <td class="p-3 font-medium text-white">
                            {{ $comm->referredUser ? substr($comm->referredUser->username, 0, 3) . '***' : 'User #' . $comm->referred_user_id }}
                        </td>
                        <td class="p-3">
                            @if($comm->tier == 1)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 font-mono-jet">Tier 1</span>
                            @elseif($comm->tier == 2)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-300 font-mono-jet">Tier 2</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300 font-mono-jet">Tier 3</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-white/[0.06] text-white font-mono-jet">
                                {{ $comm->game_type }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-mono-jet text-white">
                            ${{ number_format($comm->wager_amount, 2) }}
                        </td>
                        <td class="p-3 text-right font-mono-jet font-bold text-amber-300">
                            +${{ number_format($comm->commission_amount, 4) }}
                        </td>
                        <td class="p-3 text-center">
                            @if($comm->status === 'claimed')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400">Claimed</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-500/20 text-yellow-300 animate-pulse">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-on-surface-subtle">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <span class="material-symbols-outlined text-4xl text-on-surface-subtle">groups</span>
                                <p class="text-xs">No referral commission events recorded yet.</p>
                                <p class="text-[11px] text-on-surface-muted">Share your referral link above to start earning passive commissions!</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyRefLink() {
    const input = document.getElementById('ref-link-input');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btnText = document.getElementById('copy-btn-text');
        btnText.innerText = 'Copied!';
        setTimeout(() => {
            btnText.innerText = 'Copy';
        }, 2000);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const claimBtn = document.getElementById('btn-claim-commissions');
    if (claimBtn) {
        claimBtn.addEventListener('click', function () {
            claimBtn.disabled = true;
            claimBtn.innerText = 'Claiming...';

            fetch('{{ route("frontend.affiliates.claim") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update UI amounts
                    document.getElementById('unclaimed-amount').innerText = '0.00';
                    const curEarned = parseFloat(document.getElementById('total-earned').innerText.replace(/,/g, '')) || 0;
                    document.getElementById('total-earned').innerText = (curEarned + parseFloat(data.amount)).toFixed(2);
                    
                    // Update header balance if present
                    const headerBal = document.querySelector('[data-user-balance]');
                    if (headerBal) {
                        headerBal.innerText = Number(data.balance).toLocaleString();
                    }

                    claimBtn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span><span>Claimed!</span>';
                    claimBtn.classList.remove('from-amber-500', 'to-yellow-400');
                    claimBtn.classList.add('bg-emerald-500', 'text-white');
                    
                    alert(data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert(data.message || 'Error claiming commissions');
                    claimBtn.disabled = false;
                    claimBtn.innerHTML = '<span class="material-symbols-outlined text-sm">savings</span><span>Claim to Cash</span>';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error claiming commissions.');
                claimBtn.disabled = false;
                claimBtn.innerHTML = '<span class="material-symbols-outlined text-sm">savings</span><span>Claim to Cash</span>';
            });
        });
    }
});
</script>
@endsection
