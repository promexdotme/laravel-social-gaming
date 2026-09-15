@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Admin Prediction Control Desk')

@section('content')

<!-- Header -->
<div class="space-y-2">
    <div class="flex items-center gap-3">
        <span class="bg-primary/20 text-primary border border-primary/30 px-3 py-1 rounded-full font-mono-jet text-xs uppercase tracking-widest inline-block">ADMIN CONTROL DESK</span>
        <span class="text-xs text-on-surface-variant font-mono-jet">PREDICTION MARKETS & LIQUIDITY MANAGEMENT</span>
    </div>
    <h1 class="font-syne text-4xl md:text-5xl font-extrabold tracking-tighter text-on-surface uppercase">Prediction Desk</h1>
    <p class="text-on-surface-variant font-body-md">Mute markets, inject bot liquidity, execute cancellations with 100% refunds, or resolve market outcomes.</p>
</div>

<!-- Admin Markets Table -->
<div class="glass-card rounded-2xl p-6 space-y-4 pt-4">
    <div class="flex justify-between items-center pb-2 border-b border-white/10">
        <h3 class="font-syne text-xl font-bold text-white uppercase tracking-tight">All Local Prediction Markets</h3>
        <span class="font-mono-jet text-xs text-primary font-bold">{{ $markets->total() }} MARKETS</span>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse text-xs font-mono-jet">
            <thead>
                <tr class="border-b border-white/10 text-on-surface-variant uppercase text-[10px]">
                    <th class="p-3">ID / Category</th>
                    <th class="p-3">Market Title & Verification</th>
                    <th class="p-3">Pool YES / NO</th>
                    <th class="p-3">Current Odds</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Admin Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($markets as $m)
                    <tr class="hover:bg-white/5 transition-all">
                        <td class="p-3">
                            <span class="block font-bold text-white text-[11px] truncate max-w-[120px]" title="{{ $m->market_id }}">{{ $m->market_id }}</span>
                            <span class="text-[10px] text-secondary uppercase">{{ $m->category }}</span>
                        </td>

                        <td class="p-3 max-w-[280px]">
                            <span class="block font-syne font-bold text-white text-xs leading-snug">{{ $m->title }}</span>
                            @if($m->verification_url)
                                <a href="{{ $m->verification_url }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-primary hover:underline pt-0.5">
                                    <span class="material-symbols-outlined text-[11px]">verified</span> Verify Source Link
                                </a>
                            @endif
                        </td>

                        <td class="p-3">
                            <span class="block text-primary">YES: {{ number_format($m->pool_yes, 0) }} C</span>
                            <span class="block text-secondary">NO: {{ number_format($m->pool_no, 0) }} C</span>
                        </td>

                        <td class="p-3 font-bold">
                            <span class="block text-primary">YES {{ number_format($m->yes_odds, 2) }}x</span>
                            <span class="block text-secondary">NO {{ number_format($m->no_odds, 2) }}x</span>
                        </td>

                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $m->status === 'active' ? 'bg-primary/20 text-primary' : ($m->status === 'cancelled' ? 'bg-error/20 text-error' : ($m->status === 'resolved' ? 'bg-secondary/20 text-secondary' : 'bg-white/10 text-on-surface-variant')) }}">
                                {{ $m->status }} {{ $m->resolution ? '('.strtoupper($m->resolution).')' : '' }}
                            </span>
                        </td>

                        <td class="p-3 text-right space-x-1 whitespace-nowrap">
                            <!-- Toggle Mute -->
                            <button type="button" class="btn-admin-mute px-2.5 py-1.5 rounded glass-card border-white/10 text-on-surface-variant hover:text-white text-[10px] uppercase" data-id="{{ $m->id }}">
                                {{ $m->status === 'muted' ? 'Unmute' : 'Mute' }}
                            </button>

                            <!-- Inject Liquidity -->
                            <button type="button" class="btn-admin-inject px-2.5 py-1.5 rounded bg-secondary/20 text-secondary border border-secondary/30 hover:bg-secondary hover:text-white text-[10px] uppercase font-bold" data-id="{{ $m->id }}">
                                + Inject
                            </button>

                            @if($m->status === 'active' || $m->status === 'muted')
                                <!-- Cancel & Refund -->
                                <button type="button" class="btn-admin-cancel px-2.5 py-1.5 rounded bg-error/20 text-error border border-error/30 hover:bg-error hover:text-white text-[10px] uppercase font-bold" data-id="{{ $m->id }}">
                                    Cancel & Refund
                                </button>

                                <!-- Settle YES -->
                                <button type="button" class="btn-admin-settle px-2.5 py-1.5 rounded bg-primary/20 text-primary border border-primary/30 hover:bg-primary hover:text-white text-[10px] uppercase font-bold" data-id="{{ $m->id }}" data-res="yes">
                                    Settle YES
                                </button>

                                <!-- Settle NO -->
                                <button type="button" class="btn-admin-settle px-2.5 py-1.5 rounded bg-secondary/20 text-secondary border border-secondary/30 hover:bg-secondary hover:text-white text-[10px] uppercase font-bold" data-id="{{ $m->id }}" data-res="no">
                                    Settle NO
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-on-surface-variant">No local prediction markets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pt-2">
        {{ $markets->links() }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mute Handler
    document.querySelectorAll('.btn-admin-mute').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch(`/admin/predictions/${id}/mute`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                window.location.reload();
            });
        });
    });

    // Cancel & Refund Handler
    document.querySelectorAll('.btn-admin-cancel').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to CANCEL this market and REFUND 100% of all player wagers?')) return;
            const id = this.dataset.id;
            fetch(`/admin/predictions/${id}/cancel`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                window.location.reload();
            });
        });
    });

    // Inject Liquidity Handler
    document.querySelectorAll('.btn-admin-inject').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const side = prompt('Inject liquidity into YES or NO pool? (Enter "yes" or "no")', 'yes');
            if (!side || !['yes', 'no'].includes(side.toLowerCase())) return;

            const amount = prompt('Enter Cedar Coins amount to inject:', '5000');
            if (!amount || isNaN(amount) || parseFloat(amount) <= 0) return;

            fetch(`/admin/predictions/${id}/inject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ side: side.toLowerCase(), amount: parseFloat(amount) })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                window.location.reload();
            });
        });
    });

    // Settle Handler
    document.querySelectorAll('.btn-admin-settle').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const res = this.dataset.res;

            if (!confirm(`Are you sure you want to SETTLE this market as ${res.toUpperCase()}? Winning bets will be paid out.`)) return;

            fetch(`/admin/predictions/${id}/settle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ resolution: res })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                window.location.reload();
            });
        });
    });
});
</script>
@endsection
