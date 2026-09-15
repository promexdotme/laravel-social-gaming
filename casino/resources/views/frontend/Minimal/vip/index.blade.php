@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'VIP Club & Rakeback Vault - Casino du Liban')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/25 px-3 py-1 rounded-full mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[11px] font-bold text-emerald-400 font-mono-jet uppercase tracking-wider">RETENTION & HIGH-ROLLER SUITE</span>
            </div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight uppercase">VIP Club & Loyalty Vault</h1>
            <p class="text-on-surface-muted text-xs sm:text-sm mt-1">
                Wager on any game to earn XP, advance through 6 prestigious tiers, and collect automated instant rakeback and cash rewards.
            </p>
        </div>

        @if(Auth::check())
        <!-- Vault Quick Pill -->
        <div class="bg-[#121622] border border-emerald-500/30 rounded-2xl p-4 flex items-center justify-between gap-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div>
                <span class="text-[10px] uppercase font-bold text-emerald-400/80 tracking-widest block">UNCLAIMED RAKEBACK</span>
                <div class="text-2xl font-black text-emerald-300 font-mono-jet mt-0.5">
                    $<span id="unclaimed-rakeback">{{ number_format($vipProfile['unclaimed_rakeback'] ?? 0, 2) }}</span>
                </div>
            </div>
            <button id="btn-claim-rakeback" class="bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-black font-extrabold text-xs uppercase px-5 py-3 rounded-xl shadow-lg shadow-emerald-500/25 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" {{ ($vipProfile['unclaimed_rakeback'] ?? 0) <= 0 ? 'disabled' : '' }}>
                <span class="material-symbols-outlined text-sm font-bold">payments</span>
                <span>Claim Rakeback</span>
            </button>
        </div>
        @else
        <div>
            <a href="{{ route('frontend.auth.login') }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-wider shadow-lg shadow-primary/25 transition-all inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">login</span>
                <span>Login to View Tier</span>
            </a>
        </div>
        @endif
    </div>

    <!-- Active Tier & Progress Card -->
    <div class="bg-gradient-to-r from-[#141a29] to-[#0e1320] border border-white/[0.08] rounded-3xl p-5 sm:p-7 shadow-xl relative overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <!-- Tier Badge & Level Info (4 cols) -->
            <div class="lg:col-span-4 flex items-center gap-4 border-b lg:border-b-0 lg:border-r border-white/[0.08] pb-5 lg:pb-0 lg:pr-6">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex items-center justify-center font-black text-2xl sm:text-3xl shadow-2xl flex-shrink-0 border-2" style="background: {{ $vipProfile['current_tier']['badge_color'] }}20; border-color: {{ $vipProfile['current_tier']['badge_color'] }}; color: {{ $vipProfile['current_tier']['badge_color'] }};">
                    @if($vipProfile['vip_level'] === 'Cedar Elite') 🌲 @elseif($vipProfile['vip_level'] === 'Diamond') 💎 @elseif($vipProfile['vip_level'] === 'Platinum') 👑 @elseif($vipProfile['vip_level'] === 'Gold') 🥇 @elseif($vipProfile['vip_level'] === 'Silver') 🥈 @else 🥉 @endif
                </div>
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] font-bold text-on-surface-muted uppercase tracking-widest block">CURRENT VIP STATUS</span>
                    <h2 class="text-xl sm:text-2xl font-black text-white uppercase tracking-tight truncate" style="color: {{ $vipProfile['current_tier']['badge_color'] }}">
                        {{ $vipProfile['vip_level'] }}
                    </h2>
                    <div class="text-xs text-on-surface-muted font-mono-jet">
                        {{ number_format($vipProfile['vip_xp']) }} TOTAL XP
                    </div>
                </div>
            </div>

            <!-- Progress Bar & Next Tier Target (8 cols) -->
            <div class="lg:col-span-8 space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-on-surface-muted">Progress to:</span>
                        <strong class="text-white uppercase font-mono-jet">{{ $vipProfile['next_tier']['name'] ?? 'MAX TIER REACHED' }}</strong>
                    </div>
                    <span class="font-bold text-emerald-400 font-mono-jet text-sm">{{ $vipProfile['progress_pct'] }}%</span>
                </div>

                <!-- Glow Progress Bar -->
                <div class="w-full h-3.5 bg-black/60 rounded-full overflow-hidden border border-white/[0.08] p-0.5">
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 shadow-md shadow-emerald-500/50 transition-all duration-500" style="width: {{ $vipProfile['progress_pct'] }}%;"></div>
                </div>

                <div class="flex justify-between items-center text-[11px] text-on-surface-muted font-mono-jet">
                    <span>Active Rate: <strong class="text-white">{{ $vipProfile['current_tier']['rakeback'] }}% Rakeback</strong></span>
                    @if($vipProfile['next_tier'])
                        <span>{{ number_format($vipProfile['xp_to_next']) }} XP needed for {{ $vipProfile['next_tier']['name'] }}</span>
                    @else
                        <span class="text-emerald-400">Peak Loyalty Milestone Unlocked!</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-5 relative overflow-hidden">
            <span class="text-[10px] sm:text-xs font-bold text-on-surface-muted uppercase tracking-wider block mb-1">Total Rakeback Claimed</span>
            <div class="text-xl sm:text-2xl md:text-3xl font-black text-white font-mono-jet">
                $<span id="total-claimed-stat">{{ number_format($vipProfile['total_rakeback_claimed'] ?? 0, 2) }}</span>
            </div>
            <div class="text-[11px] text-emerald-400 mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">savings</span> Instant Zero-Fee Payouts
            </div>
        </div>

        <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-5 relative overflow-hidden">
            <span class="text-[10px] sm:text-xs font-bold text-on-surface-muted uppercase tracking-wider block mb-1">Current Rakeback Rate</span>
            <div class="text-xl sm:text-2xl md:text-3xl font-black text-emerald-300 font-mono-jet">
                {{ $vipProfile['current_tier']['rakeback'] }}%
            </div>
            <div class="text-[11px] text-on-surface-muted mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">percent</span> Of House Edge Returned
            </div>
        </div>

        <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-5 relative overflow-hidden">
            <span class="text-[10px] sm:text-xs font-bold text-on-surface-muted uppercase tracking-wider block mb-1">Max Elite Rakeback</span>
            <div class="text-xl sm:text-2xl md:text-3xl font-black text-amber-300 font-mono-jet">
                20.0%
            </div>
            <div class="text-[11px] text-amber-400/80 mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">workspace_premium</span> Cedar Elite Tier
            </div>
        </div>

        <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-5 relative overflow-hidden">
            <span class="text-[10px] sm:text-xs font-bold text-on-surface-muted uppercase tracking-wider block mb-1">XP Conversion Rate</span>
            <div class="text-xl sm:text-2xl md:text-3xl font-black text-secondary font-mono-jet">
                1 Coin = 1 XP
            </div>
            <div class="text-[11px] text-secondary mt-1 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">military_tech</span> All Games Count
            </div>
        </div>
    </div>

    <!-- Level-Up Milestone Cash Rewards -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base sm:text-lg font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-400 text-xl">redeem</span>
                <span>Level-Up Cash Rewards</span>
            </h2>
            <span class="text-xs text-on-surface-muted font-mono-jet">ONE-TIME INSTANT CASH</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4">
            @foreach($vipProfile['all_tiers'] as $tierName => $tier)
                @if($tier['level_bonus'] > 0)
                @php
                    $isUnlocked = ($vipProfile['vip_xp'] ?? 0) >= $tier['threshold'];
                    $isClaimed = in_array($tierName, $vipProfile['claimed_bonuses'] ?? []);
                @endphp
                <div class="bg-surface-card border {{ $isUnlocked ? 'border-amber-500/30 shadow-lg shadow-amber-500/10' : 'border-white/[0.06] opacity-75' }} rounded-2xl p-4 flex flex-col justify-between text-center relative overflow-hidden">
                    <div class="space-y-1 mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-widest block" style="color: {{ $tier['badge_color'] }}">{{ $tierName }}</span>
                        <div class="text-xl font-black text-white font-mono-jet">${{ number_format($tier['level_bonus']) }}</div>
                        <span class="text-[10px] text-on-surface-muted block font-mono-jet">{{ number_format($tier['threshold']) }} XP</span>
                    </div>

                    @if(Auth::check())
                        @if($isClaimed)
                            <button disabled class="w-full py-2 rounded-xl bg-white/[0.04] text-on-surface-subtle text-[11px] font-bold uppercase tracking-wider">
                                Claimed
                            </button>
                        @elseif($isUnlocked)
                            <button onclick="claimLevelBonus('{{ $tierName }}')" class="w-full py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black text-[11px] font-extrabold uppercase tracking-wider shadow-md shadow-amber-500/25 transition-all">
                                Claim ${{ number_format($tier['level_bonus']) }}
                            </button>
                        @else
                            <button disabled class="w-full py-2 rounded-xl bg-white/[0.03] text-on-surface-subtle text-[11px] font-bold uppercase tracking-wider">
                                Locked
                            </button>
                        @endif
                    @else
                        <a href="{{ route('frontend.auth.login') }}" class="w-full py-2 rounded-xl bg-white/[0.05] text-white text-[11px] font-bold uppercase tracking-wider no-underline">
                            Login
                        </a>
                    @endif
                </div>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Full 6-Tier VIP Privilege Comparison Matrix -->
    <div class="bg-surface-card border border-white/[0.06] rounded-2xl p-4 sm:p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm sm:text-base font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary text-lg">workspace_premium</span>
                <span>VIP Loyalty Tier Privileges</span>
            </h3>
            <span class="text-[11px] font-mono-jet text-on-surface-muted">6 LUXURY LEVELS</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-on-surface-muted">
                <thead class="bg-[#0c101c] text-[10px] uppercase font-bold text-on-surface-subtle font-mono-jet">
                    <tr>
                        <th class="p-3 rounded-l-lg">Tier Level</th>
                        <th class="p-3">XP Requirement</th>
                        <th class="p-3">Rakeback %</th>
                        <th class="p-3">Level-Up Cash</th>
                        <th class="p-3 rounded-r-lg">Exclusive Perks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @foreach($vipProfile['all_tiers'] as $tName => $t)
                    <tr class="hover:bg-white/[0.02] transition-colors {{ $vipProfile['vip_level'] === $tName ? 'bg-emerald-500/5' : '' }}">
                        <td class="p-3 font-bold font-mono-jet text-sm" style="color: {{ $t['badge_color'] }}">
                            <div class="flex items-center gap-2">
                                <span>{{ $tName }}</span>
                                @if($vipProfile['vip_level'] === $tName)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-400 uppercase tracking-widest">YOU</span>
                                @endif
                            </div>
                        </td>
                        <td class="p-3 font-mono-jet text-white">
                            {{ number_format($t['threshold']) }} XP
                        </td>
                        <td class="p-3 font-mono-jet font-bold text-emerald-400">
                            {{ $t['rakeback'] }}%
                        </td>
                        <td class="p-3 font-mono-jet font-bold text-amber-300">
                            {{ $t['level_bonus'] > 0 ? '$' . number_format($t['level_bonus']) : 'None' }}
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($t['perks'] as $perk)
                                    <span class="px-2 py-0.5 rounded-md bg-white/[0.04] text-[11px] text-gray-300 border border-white/[0.04]">
                                        {{ $perk }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const claimBtn = document.getElementById('btn-claim-rakeback');
    if (claimBtn) {
        claimBtn.addEventListener('click', function () {
            claimBtn.disabled = true;
            claimBtn.innerText = 'Claiming...';

            fetch('{{ route("frontend.vip.claim_rakeback") }}', {
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
                    document.getElementById('unclaimed-rakeback').innerText = '0.00';
                    const statEl = document.getElementById('total-claimed-stat');
                    if (statEl) {
                        const cur = parseFloat(statEl.innerText.replace(/,/g, '')) || 0;
                        statEl.innerText = (cur + parseFloat(data.amount)).toFixed(2);
                    }
                    claimBtn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span><span>Claimed!</span>';
                    claimBtn.classList.remove('from-emerald-500', 'to-teal-400');
                    claimBtn.classList.add('bg-emerald-500', 'text-white');
                    alert(data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert(data.message || 'Error claiming rakeback');
                    claimBtn.disabled = false;
                    claimBtn.innerHTML = '<span class="material-symbols-outlined text-sm">payments</span><span>Claim Rakeback</span>';
                }
            })
            .catch(() => {
                alert('Connection error claiming rakeback.');
                claimBtn.disabled = false;
                claimBtn.innerHTML = '<span class="material-symbols-outlined text-sm">payments</span><span>Claim Rakeback</span>';
            });
        });
    }
});

function claimLevelBonus(tierName) {
    if (!confirm(`Claim your Level-Up bonus for ${tierName}?`)) return;

    fetch('{{ route("frontend.vip.claim_level_bonus") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ tier: tierName })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Error claiming bonus');
        }
    })
    .catch(() => {
        alert('Network error claiming bonus');
    });
}
</script>
@endsection
