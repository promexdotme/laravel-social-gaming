@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Admin System Documentation Desk')

@section('content')

<!-- Header -->
<div class="space-y-2">
    <div class="flex items-center gap-3">
        <span class="bg-primary/20 text-primary border border-primary/30 px-3 py-1 rounded-full font-mono-jet text-xs uppercase tracking-widest inline-block">ADMIN ONLY</span>
        <span class="text-xs text-on-surface-variant font-mono-jet">VERSION 2.5 (SYNTHWAVE CORE)</span>
    </div>
    <h1 class="font-syne text-4xl md:text-5xl font-extrabold tracking-tighter text-on-surface uppercase">System Documentation Desk</h1>
    <p class="text-on-surface-variant font-body-md">Comprehensive architectural, API, Cron, and Feature reference manual for administrators.</p>
</div>

<!-- Documentation Workspace Layout -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-8 pt-4">
    <!-- Side Nav Tabs -->
    <div class="space-y-2">
        <button type="button" class="doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all bg-primary text-white shadow-[0_0_15px_rgba(255,0,255,0.4)]" data-target="doc-arch">
            🚀 1. System Architecture
        </button>
        <button type="button" class="doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all glass-card text-on-surface-variant hover:text-white" data-target="doc-auth">
            📱 2. Multi-Method Auth & WhatsApp
        </button>
        <button type="button" class="doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all glass-card text-on-surface-variant hover:text-white" data-target="doc-affiliate">
            🏆 3. 2-Tier Affiliate Referrals
        </button>
        <button type="button" class="doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all glass-card text-on-surface-variant hover:text-white" data-target="doc-lotto">
            🎟️ 4. Multi-Draw Lotto Engine
        </button>
        <button type="button" class="doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all glass-card text-on-surface-variant hover:text-white" data-target="doc-sports">
            ⚽ 5. Social Sportsbook & Parlays
        </button>
        <button type="button" class="doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all glass-card text-on-surface-variant hover:text-white" data-target="doc-time">
            ⏱️ 6. Timezone Standardization
        </button>
        <button type="button" class="doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all glass-card text-on-surface-variant hover:text-white" data-target="doc-crons">
            ⏰ 7. Complete Cron Job Registry
        </button>
    </div>

    <!-- Documentation Content Area -->
    <div class="lg:col-span-3 glass-card rounded-2xl p-8 space-y-6">
        
        <!-- Tab 1: System Architecture -->
        <div id="doc-arch" class="doc-content space-y-4">
            <h2 class="font-syne text-2xl font-bold text-white uppercase border-b border-white/10 pb-3">🚀 1. Monolith Core & HOT Games Category</h2>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                The platform is built as a unified, high-performance Laravel 13 monolith running 1,061 games offline without external Node.js socket servers or Redis dependencies.
            </p>
            <div class="glass-card p-4 rounded-xl space-y-2 text-xs font-mono-jet">
                <div class="text-primary font-bold">🔥 Default HOT Games Category:</div>
                <div>• <strong>Instant Page Load:</strong> The main casino homepage (<code>/</code>) defaults to the <strong>🔥 HOT Games</strong> category, serving 20 featured games for ultra-fast DOM rendering.</div>
                <div>• <strong>Admin Promotion:</strong> Admins can manage hot games via the admin panel, with an automatic 20-game fallback seed from <code>GetHotNewMyGames::get_hot_games()</code>.</div>
                <div>• <strong>PHP Readiness:</strong> PHP 8.2, 8.3, 8.4, and 8.5 compatibility with <code>#[\AllowDynamicProperties]</code> annotated across 2,389 game engine classes.</div>
            </div>
        </div>

        <!-- Tab 2: Multi-Auth & WhatsApp -->
        <div id="doc-auth" class="doc-content space-y-4 hidden">
            <h2 class="font-syne text-2xl font-bold text-white uppercase border-b border-white/10 pb-3">📱 2. Multi-Method Auth & WhatsApp Dev Mode</h2>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Supports phone-first WhatsApp OTP verification, user auto-creation, and standard credentials.
            </p>
            <div class="glass-card p-4 rounded-xl space-y-2 text-xs font-mono-jet">
                <div class="text-primary font-bold">Environment Settings (.env):</div>
                <div><code>WHATSAPP_MODE=devmode</code> — Displays 6-digit OTP in UI and pre-fills input without consuming Meta credits.</div>
                <div><code>WHATSAPP_MODE=cloud</code> — Dispatches real WhatsApp messages via Meta Cloud API.</div>
                <div><code>WHATSAPP_CLOUD_TOKEN</code> — Meta System User Bearer Token.</div>
                <div><code>WHATSAPP_PHONE_NUMBER_ID</code> — Meta Phone ID.</div>
            </div>
        </div>

        <!-- Tab 3: 2-Tier Affiliate Referrals -->
        <div id="doc-affiliate" class="doc-content space-y-4 hidden">
            <h2 class="font-syne text-2xl font-bold text-white uppercase border-b border-white/10 pb-3">🏆 3. 1-Tier & 2-Tier Affiliate Referral System</h2>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Every user receives a unique invite code (e.g. <code>REF78A1</code>) and shareable link (<code>?ref=REF78A1</code>).
            </p>
            <div class="glass-card p-4 rounded-xl space-y-2 text-xs font-mono-jet">
                <div class="text-primary font-bold">Reward Distribution Rules:</div>
                <div>• <strong>Tier 1 (Direct Referral):</strong> Credits inviter with Tier 1 reward (default: 1,000 Coins) upon recruit signup.</div>
                <div>• <strong>Tier 2 (Sub-Referral):** Credits grandparent inviter with Tier 2 reward (default: 250 Coins) when sub-recruits invite new players.</div>
                <div>• <strong>Member Hub Copy Box:</strong> Profile modal embeds 1-click invite link copy box and live recruit counters.</div>
            </div>
        </div>

        <!-- Tab 4: Multi-Draw Lotto Engine -->
        <div id="doc-lotto" class="doc-content space-y-4 hidden">
            <h2 class="font-syne text-2xl font-bold text-white uppercase border-b border-white/10 pb-3">🎟️ 4. Multi-Draw Dynamic Lotto Engine</h2>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Supports multiple concurrent lotto configurations managed directly from the database or admin panel.
            </p>
            <div class="glass-card p-4 rounded-xl space-y-2 text-xs font-mono-jet">
                <div class="text-primary font-bold">Active Lotto Configurations:</div>
                <div>1. <strong>Daily Lucky 4:</strong> Pick 4 numbers out of 20 | Entry: 500 Coins | Jackpot: 250,000 Coins</div>
                <div>2. <strong>Grand Cedar 6:</strong> Pick 6 numbers out of 49 | Entry: 1,000 Coins | Jackpot: 1,000,000 Coins</div>
                <div>3. <strong>Hourly Mini 3:</strong> Pick 3 numbers out of 10 | Entry: 100 Coins | Jackpot: 25,000 Coins</div>
            </div>
        </div>

        <!-- Tab 5: Social Sportsbook & Parlays -->
        <div id="doc-sports" class="doc-content space-y-4 hidden">
            <h2 class="font-syne text-2xl font-bold text-white uppercase border-b border-white/10 pb-3">⚽ 5. Free Social Sportsbook & Predictions</h2>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Integrates <strong>The Odds API</strong> for sports & <strong>Polymarket Gamma API</strong> for live keyword searches, AMM liquidity pools, player custom bets, and admin controls.
            </p>
            <div class="glass-card p-4 rounded-xl space-y-2 text-xs font-mono-jet">
                <div class="text-primary font-bold">🔮 Prediction Engine Specs:</div>
                <div>• <strong>Polymarket Live Search:</strong> 100% free search via <code>gamma-api.polymarket.com</code> (no API key required).</div>
                <div>• <strong>Auto-Cloning & Prioritization:</strong> External search results auto-clone to DB on first wager; local markets are featured at top of search results.</div>
                <div>• <strong>AMM Dynamic Pools:</strong> Odds automatically calculate from $\frac{\text{Total Pool}}{\text{Pool}_{\text{YES}}}$ ratio.</div>
                <div>• <strong>✨ Custom Bet Creator:</strong> Players can create custom prediction topics with verification URLs and initial seed stakes.</div>
                <div>• <strong>Admin Desk (<code>/admin/predictions</code>):</strong> Mute markets, inject bot liquidity, cancel with 100% refunds, or manually settle YES/NO.</div>
            </div>
        </div>

        <!-- Tab 6: Timezone Standardization -->
        <div id="doc-time" class="doc-content space-y-4 hidden">
            <h2 class="font-syne text-2xl font-bold text-white uppercase border-b border-white/10 pb-3">⏱️ 6. Timezone & Date Standardization Engine</h2>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Guarantees complete alignment between external API timestamps, server schedules, and player local timezones.
            </p>
            <div class="glass-card p-4 rounded-xl space-y-2 text-xs font-mono-jet">
                <div class="text-primary font-bold">Standardization Protocol:</div>
                <div>• <strong>Database Storage:</strong> All event timestamps stored in <strong>UTC ISO-8601 format</strong> (e.g. <code>2026-07-22T20:45:00Z</code>).</div>
                <div>• <strong>Client Auto-Detection:</strong> HTML output uses <code>&lt;time class="local-time" datetime="..."&gt;</code>. JavaScript auto-formats timestamps into player's phone/browser local timezone.</div>
            </div>
        </div>

        <!-- Tab 7: Cron Job Registry -->
        <div id="doc-crons" class="doc-content space-y-4 hidden">
            <h2 class="font-syne text-2xl font-bold text-white uppercase border-b border-white/10 pb-3">⏰ 7. Complete Cron Job Registry</h2>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Add these Artisan commands to your cPanel / Server crontab scheduler.
            </p>
            <div class="glass-card p-4 rounded-xl space-y-3 text-xs font-mono-jet">
                <div>
                    <div class="text-primary font-bold">1. Pre-Match Sports Fixture Sync (Every 3 Hours)</div>
                    <code class="block bg-black/40 p-2 rounded text-white mt-1">php casino/artisan sports:sync-fixtures</code>
                </div>
                <div>
                    <div class="text-primary font-bold">2. Targeted Sports Bet Settlement (Every 15 Mins)</div>
                    <code class="block bg-black/40 p-2 rounded text-white mt-1">php casino/artisan sports:settle-bets</code>
                </div>
                <div>
                    <div class="text-primary font-bold">3. Multi-Draw Lotto Evaluation (Daily / Scheduled)</div>
                    <code class="block bg-black/40 p-2 rounded text-white mt-1">php casino/artisan casino:draw-lotto</code>
                </div>
                <div>
                    <div class="text-primary font-bold">4. Master Laravel Task Scheduler (Every Minute)</div>
                    <code class="block bg-black/40 p-2 rounded text-white mt-1">* * * * * php casino/artisan schedule:run >> /dev/null 2>&1</code>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.doc-tab');
    const contents = document.querySelectorAll('.doc-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = this.dataset.target;

            tabs.forEach(t => {
                t.className = "doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all glass-card text-on-surface-variant hover:text-white";
            });

            this.className = "doc-tab w-full text-left p-4 rounded-xl font-syne text-xs font-bold uppercase tracking-wider transition-all bg-primary text-white shadow-[0_0_15px_rgba(255,0,255,0.4)]";

            contents.forEach(c => {
                if (c.id === targetId) {
                    c.classList.remove('hidden');
                } else {
                    c.classList.add('hidden');
                }
            });
        });
    });
});
</script>
@endsection
