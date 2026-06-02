@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Sportsbook')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
    html, body {
        overflow-x: clip !important;
    }
    .sports-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
        margin-top: 20px;
        width: 100%;
        max-width: 100%;
    }
    @media (min-width: 992px) {
        .sports-layout {
            grid-template-columns: 280px 1fr;
        }
    }

    /* Sidebar Column */
    .sports-sidebar {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    @media (min-width: 992px) {
        .sports-sidebar {
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            align-self: start;
            scrollbar-width: none; /* Hide scrollbar Firefox */
            -ms-overflow-style: none; /* Hide scrollbar IE/Edge */
            padding-right: 0;
        }
        .sports-sidebar::-webkit-scrollbar {
            display: none; /* Hide scrollbar Chrome/Safari */
        }
    }

    .sidebar-widget {
        background-color: var(--panel-2);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 18px;
    }
    
    .sidebar-label {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        color: var(--muted);
        font-weight: 600;
        margin-bottom: 12px;
        display: block;
    }

    /* Search widget */
    .sidebar-search-wrapper {
        position: relative;
    }
    .sidebar-search-input {
        width: 100%;
        background-color: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px 14px;
        color: white;
        font-family: var(--font);
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    .sidebar-search-input:focus {
        outline: none;
        border-color: var(--accent);
        background-color: rgba(255, 255, 255, 0.05);
    }

    /* Time filter options */
    .sports-tabs-vertical {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .sports-tab-vertical-link {
        display: flex;
        align-items: center;
        gap: 10px;
        background-color: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
        color: var(--muted);
        padding: 10px 16px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .sports-tab-vertical-link:hover {
        border-color: rgba(236, 19, 128, 0.3);
        color: white;
    }
    .sports-tab-vertical-link.active {
        background-image: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%) !important;
        background-color: transparent !important;
        color: #0a0512 !important;
        border-color: transparent !important;
        font-weight: 600;
        box-shadow: var(--glow);
    }
    .sports-tab-vertical-link.active i {
        color: #0a0512 !important;
    }

    /* Category list vertical */
    .category-list-vertical {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .category-list-link {
        display: flex;
        align-items: center;
        gap: 12px;
        background-color: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
        color: var(--muted);
        padding: 11px 16px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .category-list-link:hover {
        border-color: rgba(236, 19, 128, 0.3);
        background-color: rgba(255, 255, 255, 0.04);
        color: white;
    }
    .category-list-link.active {
        background-image: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%) !important;
        background-color: transparent !important;
        color: #0a0512 !important;
        border-color: transparent !important;
        font-weight: 600;
        box-shadow: var(--glow);
    }
    .category-list-link.active .category-icon i {
        color: #0a0512 !important;
    }
    .category-icon {
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
    }

    /* Middle List Styling */
    .sports-card {
        background-color: var(--panel-2);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
    }
    .odds-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 15px;
        width: 100%;
    }
    .odds-row.h2h-3way {
        grid-template-columns: 1fr 1fr 1fr;
    }
    @media (max-width: 576px) {
        .odds-row, .odds-row.h2h-3way {
            grid-template-columns: 1fr;
        }
    }
    .outcome-chip {
        flex: 1;
        min-width: 0;
        background-color: rgba(255,255,255,0.03);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 11px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .outcome-chip:hover {
        border-color: rgba(236, 19, 128, 0.4);
        background-color: rgba(236, 19, 128, 0.05);
    }
    .outcome-chip.selected {
        border-color: transparent !important;
        background-image: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%) !important;
        background-color: transparent !important;
        color: #0a0512 !important;
        box-shadow: var(--glow);
    }
    .outcome-chip.selected span,
    .outcome-chip.selected strong {
        color: #0a0512 !important;
    }

    /* Floating Betslip Toggle Button */
    .floating-betslip-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-image: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%);
        border: none;
        color: #0a0512;
        font-size: 22px;
        cursor: pointer;
        box-shadow: var(--glow);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease-in-out;
    }
    .floating-betslip-btn:hover {
        transform: scale(1.08);
        box-shadow: 0 20px 45px rgba(236, 19, 128, 0.4);
    }
    .betslip-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background-color: var(--accent-contrast);
        color: #0a0512;
        font-size: 11px;
        font-weight: 700;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--bg);
    }

    /* Backdrop overlay */
    .betslip-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 1001;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .betslip-backdrop.open {
        display: block;
        opacity: 1;
    }

    /* Betslip Drawer */
    .betslip-drawer {
        position: fixed;
        top: 0;
        right: -420px;
        width: 400px;
        height: 100vh;
        background-color: var(--panel-2);
        border-left: 1px solid var(--border);
        box-shadow: var(--shadow);
        z-index: 1002;
        transition: right 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
    }
    .betslip-drawer.open {
        right: 0;
    }
    .betslip-drawer-content {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        padding: 24px;
    }
    .betslip-drawer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
        padding-bottom: 12px;
    }
    .betslip-drawer-header h4 {
        margin: 0;
        font-size: 1.25rem;
        color: white;
        font-weight: 700;
    }
    .close-betslip {
        background: transparent;
        border: none;
        color: var(--muted);
        font-size: 28px;
        cursor: pointer;
        line-height: 1;
        padding: 0;
    }
    .close-betslip:hover {
        color: white;
    }
    .betslip-drawer-body {
        flex: 1;
        overflow-y: auto;
        margin: 15px 0;
        padding-right: 5px;
    }
    .betslip-drawer-footer {
        border-top: 1px solid var(--border);
        padding-top: 15px;
    }

    .betslip-items {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .betslip-tabs {
        display: flex;
        border-bottom: 1px solid var(--border);
        margin-bottom: 15px;
    }
    .betslip-tab {
        flex: 1;
        padding: 10px;
        text-align: center;
        cursor: pointer;
        color: var(--muted);
        font-weight: 600;
        border-bottom: 2px solid transparent;
    }
    .betslip-tab.active {
        color: var(--accent);
        border-bottom-color: var(--accent);
    }

    /* Mobile Adaptations */
    @media (max-width: 991px) {
        .sports-sidebar {
            flex-direction: column;
            gap: 15px;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        .sidebar-widget {
            padding: 15px;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        .sidebar-label {
            margin-bottom: 8px;
        }
        .floating-betslip-btn {
            bottom: 20px;
            right: 20px;
            width: 54px;
            height: 54px;
        }
    }

    @media (max-width: 576px) {
        .betslip-drawer {
            right: -100%;
            width: 100%;
            border-left: none;
        }
        .betslip-drawer.open {
            right: 0;
        }
    }

    /* Sports-specific display utilities to avoid global conflicts */
    .sports-desktop-only {
        display: flex !important;
    }
    .sports-mobile-only {
        display: none !important;
    }
    @media (max-width: 991px) {
        .sports-desktop-only {
            display: none !important;
        }
        .sports-mobile-only {
            display: block !important;
        }
    }

    /* Premium Styled Dropdowns for Mobile */
    .sidebar-select {
        width: 100%;
        background-color: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px 14px;
        color: white;
        font-family: var(--font);
        font-size: 0.95rem;
        transition: all 0.2s;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23a6a1b7' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 16px;
        padding-right: 40px;
    }
    .sidebar-select:focus {
        outline: none;
        border-color: var(--accent);
        background-color: rgba(255, 255, 255, 0.05);
        box-shadow: 0 0 10px rgba(236, 19, 128, 0.15);
    }
    .sidebar-select option {
        background-color: var(--panel-2);
        color: white;
    }
</style>
@endsection

@section('content')
<div class="container page-shell" style="padding-top: calc(var(--header-height) + 20px);">
    
    <div class="sports-layout">
        <!-- Sticky Sidebar with Search and Filters -->
        <aside class="sports-sidebar">
            
            <!-- Search Widget -->
            <div class="sidebar-widget">
                <label class="sidebar-label">Search Match</label>
                <div class="sidebar-search-wrapper">
                    <form action="{{ route('frontend.sports.index') }}" method="GET">
                        <input type="text" name="q" class="sidebar-search-input" placeholder="Search events..." value="{{ $term ?? '' }}">
                    </form>
                </div>
            </div>

            <!-- Time Filters Widget -->
            <div class="sidebar-widget">
                <label class="sidebar-label">Filter Time</label>
                <!-- Desktop View -->
                <div class="sports-tabs-vertical sports-desktop-only">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'open']) }}" class="sports-tab-vertical-link {{ $tab === 'open' ? 'active' : '' }}">
                        <i class="far fa-clock"></i> Upcoming
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'inplay']) }}" class="sports-tab-vertical-link {{ $tab === 'inplay' ? 'active' : '' }}">
                        <i class="fas fa-play"></i> Live / In-Play
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'today']) }}" class="sports-tab-vertical-link {{ $tab === 'today' ? 'active' : '' }}">
                        <i class="far fa-calendar-alt"></i> Today
                    </a>
                </div>
                <!-- Mobile View -->
                <div class="sports-mobile-only">
                    <select class="sidebar-select" onchange="location = this.value;">
                        <option value="{{ request()->fullUrlWithQuery(['tab' => 'open']) }}" {{ $tab === 'open' ? 'selected' : '' }}>Upcoming</option>
                        <option value="{{ request()->fullUrlWithQuery(['tab' => 'inplay']) }}" {{ $tab === 'inplay' ? 'selected' : '' }}>Live / In-Play</option>
                        <option value="{{ request()->fullUrlWithQuery(['tab' => 'today']) }}" {{ $tab === 'today' ? 'selected' : '' }}>Today</option>
                    </select>
                </div>
            </div>

            <!-- Sports Category Filter Widget -->
            <div class="sidebar-widget">
                <label class="sidebar-label">Sports Lobby</label>
                <!-- Desktop View -->
                <div class="category-list-vertical sports-desktop-only">
                    <a href="{{ route('frontend.sports.category', 'all') }}" class="category-list-link {{ $categorySlug === 'all' ? 'active' : '' }}">
                        <span class="category-icon"><i class="fas fa-globe"></i></span>
                        All Sports
                    </a>
                    @foreach($categories as $cat)
                        @php
                            $iconClass = 'fa-trophy';
                            $slug = strtolower($cat->slug);
                            if ($slug === 'soccer' || $slug === 'football') {
                                $iconClass = 'fa-futbol';
                            } elseif ($slug === 'basketball') {
                                $iconClass = 'fa-basketball-ball';
                            } elseif ($slug === 'tennis') {
                                $iconClass = 'fa-baseball-ball';
                            } elseif ($slug === 'baseball') {
                                $iconClass = 'fa-baseball-ball';
                            } elseif ($slug === 'cricket') {
                                $iconClass = 'fa-cricket';
                            } elseif ($slug === 'ice-hockey' || $slug === 'ice_hockey') {
                                $iconClass = 'fa-hockey-puck';
                            }
                        @endphp
                        <a href="{{ route('frontend.sports.category', $cat->slug) }}" class="category-list-link {{ $categorySlug === $cat->slug ? 'active' : '' }}">
                            <span class="category-icon"><i class="fas {{ $iconClass }}"></i></span>
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
                <!-- Mobile View -->
                <div class="sports-mobile-only">
                    <select class="sidebar-select" onchange="location = this.value;">
                        <option value="{{ route('frontend.sports.category', 'all') }}" {{ $categorySlug === 'all' ? 'selected' : '' }}>All Sports</option>
                        @foreach($categories as $cat)
                            <option value="{{ route('frontend.sports.category', $cat->slug) }}" {{ $categorySlug === $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </aside>

        <!-- Events List Content -->
        <div>
            <div class="events-list">
                @forelse($games as $game)
                    <div class="sports-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="font-size: 0.8rem; color: var(--accent-contrast); text-transform: uppercase;">{{ $game->league->category->name }} &raquo; {{ $game->league->name }}</span>
                                <h4 style="margin: 5px 0; color: white; font-weight: 700;">{{ $game->title }}</h4>
                                <small style="color: var(--muted);">Starts: {{ \Carbon\Carbon::parse($game->start_time)->timezone(config('app.timezone', 'UTC'))->format('M d, H:i') }} (Local Time)</small>
                            </div>
                            @if($game->is_in_play)
                                <span style="background: #ff4757; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; animation: pulse 1.5s infinite;">Live</span>
                            @endif
                        </div>

                        @foreach($game->markets as $market)
                            @if($market->market_type === 'h2h' || $market->market_type === 'h2h_3way')
                                <div class="odds-row">
                                    @foreach($market->outcomes as $outcome)
                                        <div class="outcome-chip {{ isset($sessionSlip[$outcome->id]) ? 'selected' : '' }}" data-outcome-id="{{ $outcome->id }}">
                                            <span style="font-weight: 500;">{{ $outcome->name }}</span>
                                            <strong style="color: inherit;">{{ number_format($outcome->odds, 2) }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                @empty
                    <div style="background-color: var(--panel-2); border: 1px solid var(--border); border-radius: 12px; padding: 60px; text-align: center; color: var(--muted);">
                        <h4>No events available.</h4>
                        <p>Check back later or try changing filters.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Floating Betslip Trigger Button -->
<button id="floating-betslip-btn" class="floating-betslip-btn">
    <i class="fas fa-receipt"></i>
    <span class="betslip-badge">0</span>
</button>

<!-- Backdrop Blur overlay -->
<div id="betslip-backdrop" class="betslip-backdrop"></div>

<!-- Betslip Slide-out Drawer -->
<div id="betslip-drawer" class="betslip-drawer">
    <div class="betslip-drawer-content">
        <div class="betslip-drawer-header">
            <div style="display: flex; align-items: baseline; gap: 15px;">
                <h4>Betslip</h4>
                <a href="#" id="clear-betslip-btn" style="color: var(--muted); font-size: 0.85rem; font-weight: 500; text-decoration: underline;">Clear All</a>
            </div>
            <button id="close-betslip-drawer" class="close-betslip">&times;</button>
        </div>
        
        <div class="betslip-tabs">
            <div class="betslip-tab active" data-type="1">Single</div>
            <div class="betslip-tab" data-type="2">Parlay</div>
        </div>

        <div class="betslip-drawer-body">
            <div class="betslip-items">
                @forelse($sessionSlip as $item)
                    @include('frontend.Minimal.sports.betslip_item', ['item' => $item])
                @empty
                    <div class="empty-slip-msg text-center text-muted" style="padding: 30px 0;">No selections made.</div>
                @endforelse
            </div>
        </div>

        <div class="betslip-drawer-footer">
            <div class="parlay-multi-wrapper" style="display: none; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="color: var(--muted);">Total Odds:</span>
                <strong id="parlay-total-odds" style="color: var(--accent-contrast); font-size: 1.2rem;">1.00</strong>
            </div>

            <div class="parlay-stake-wrapper" style="display: none; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="color: var(--muted);">Stake ($):</span>
                <input type="number" id="multi-stake-input" value="10" style="width: 80px; padding: 6px; background: #121212; border: 1px solid var(--border); border-radius: 4px; color: white; text-align: right;" min="1">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span style="color: white; font-weight: 600;">Est. Payout:</span>
                <strong id="betslip-est-payout" style="color: var(--accent-contrast); font-size: 1.3rem;">$0.00</strong>
            </div>

            @if(Auth::check())
                <button id="btn-place-bet" class="btn-primary" style="width: 100%; border: none; padding: 12px; border-radius: 8px;">Place Bet</button>
            @else
                <button class="btn-primary open-modal" data-target="modal-login" style="width: 100%; border: none; padding: 12px; border-radius: 8px; text-align: center;">Log in to bet</button>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        let currentType = 1; // 1 = Single, 2 = Parlay

        // Drawer toggle controllers
        $('#floating-betslip-btn').click(function() {
            $('#betslip-drawer').addClass('open');
            $('#betslip-backdrop').addClass('open');
        });
        $('#close-betslip-drawer, #betslip-backdrop').click(function() {
            $('#betslip-drawer').removeClass('open');
            $('#betslip-backdrop').removeClass('open');
        });

        // Clear Betslip
        $('#clear-betslip-btn').click(function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('frontend.sports.betslip.clear') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    $('.betslip-items').empty().append('<div class="empty-slip-msg text-center text-muted" style="padding: 30px 0;">No selections made.</div>');
                    $('.outcome-chip').removeClass('selected');
                    calculateTotals();
                    updateBadge();
                }
            });
        });

        // Switch betslip type
        $('.betslip-tab').click(function() {
            $('.betslip-tab').removeClass('active');
            $(this).addClass('active');
            currentType = parseInt($(this).data('type'));

            if (currentType === 2) {
                $('.parlay-multi-wrapper, .parlay-stake-wrapper').css('display', 'flex');
                $('.betslip-stake').parent().hide();
            } else {
                $('.parlay-multi-wrapper, .parlay-stake-wrapper').hide();
                $('.betslip-stake').parent().css('display', 'flex');
            }
            calculateTotals();
        });

        // Add to Betslip
        $(document).on('click', '.outcome-chip', function() {
            let chip = $(this);
            if (chip.hasClass('selected')) {
                $('#betslip-drawer').addClass('open');
                $('#betslip-backdrop').addClass('open');
                return;
            }

            let outcomeId = chip.data('outcome-id');

            $.ajax({
                url: "{{ route('frontend.sports.betslip.add') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    outcome_id: outcomeId
                },
                success: function(res) {
                    chip.addClass('selected');
                    $('.empty-slip-msg').hide();
                    $('.betslip-items').append(res.html);
                    
                    if (currentType === 2) {
                        $('.betslip-stake').parent().hide();
                    }
                    calculateTotals();
                    updateBadge();
                    
                    // Auto-open drawer when a selection is added so user gets instant visual confirmation
                    $('#betslip-drawer').addClass('open');
                    $('#betslip-backdrop').addClass('open');
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.error || 'Failed to add selection.');
                }
            });
        });

        // Remove from Betslip
        $(document).on('click', '.remove-betslip-item', function() {
            let btn = $(this);
            let outcomeId = btn.data('outcome-id');

            $.ajax({
                url: "{{ route('frontend.sports.betslip.remove') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    outcome_id: outcomeId
                },
                success: function(res) {
                    $(`.betslip-item[data-outcome-id="${outcomeId}"]`).remove();
                    $(`.outcome-chip[data-outcome-id="${outcomeId}"]`).removeClass('selected');

                    if (res.slipCount === 0) {
                        $('.empty-slip-msg').show();
                    }
                    calculateTotals();
                    updateBadge();
                }
            });
        });

        // Update Parlay totals when stakes change
        $(document).on('input', '.betslip-stake, #multi-stake-input', function() {
            calculateTotals();
        });

        function calculateTotals() {
            let items = $('.betslip-item');
            if (items.length === 0) {
                $('#parlay-total-odds').text('1.00');
                $('#betslip-est-payout').text('$0.00');
                return;
            }

            if (currentType === 1) {
                // Singles
                let totalPayout = 0.0;
                items.each(function() {
                    let stake = parseFloat($(this).find('.betslip-stake').val()) || 0.0;
                    let odds = parseFloat($(this).find('.outcome-odds').text());
                    totalPayout += stake * odds;
                });
                $('#betslip-est-payout').text('$' + totalPayout.toFixed(2));
            } else {
                // Parlay
                let totalOdds = 1.0;
                items.each(function() {
                    let odds = parseFloat($(this).find('.outcome-odds').text());
                    totalOdds *= odds;
                });
                let stake = parseFloat($('#multi-stake-input').val()) || 0.0;
                let payout = totalOdds * stake;

                $('#parlay-total-odds').text(totalOdds.toFixed(2));
                $('#betslip-est-payout').text('$' + payout.toFixed(2));
            }
        }

        function updateBadge() {
            let count = $('.betslip-item').length;
            $('.betslip-badge').text(count);
            if (count > 0) {
                $('.betslip-badge').css('display', 'flex');
            } else {
                $('.betslip-badge').hide();
            }
        }

        // Place Bet
        $('#btn-place-bet').click(function() {
            let stakes = {};
            $('.betslip-stake').each(function() {
                stakes[$(this).data('outcome-id')] = $(this).val();
            });

            let data = {
                _token: "{{ csrf_token() }}",
                type: currentType,
                multi_stake: $('#multi-stake-input').val(),
                stakes: stakes
            };

            $.ajax({
                url: "{{ route('frontend.sports.bet.place') }}",
                method: "POST",
                data: data,
                success: function(res) {
                    alert('Bet placed successfully!');
                    $('.betslip-items').empty().append('<div class="empty-slip-msg text-center text-muted" style="padding: 30px 0;">No selections made.</div>');
                    $('.outcome-chip').removeClass('selected');
                    updateBadge();
                    $('#betslip-drawer').removeClass('open');
                    $('#betslip-backdrop').removeClass('open');
                    
                    if ($('.member-sub').length) {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.error || 'Failed to place bet.');
                }
            });
        });

        // Initialize payouts & badges
        calculateTotals();
        updateBadge();
    });
</script>
@endsection
