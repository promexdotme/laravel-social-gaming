@php
    $licenseStatus = \VanguardLTE\Services\LicenseService::getStatus();
    $licenseState = $licenseStatus['status'] ?? 'unregistered';
    $licenseKey = $licenseStatus['license_key'] ?? '';
    $operatorPortalUrl = !empty($licenseKey) 
        ? 'https://clients.377.live/operator?key=' . urlencode($licenseKey)
        : 'https://clients.377.live/operator';
    $boundDomain = $licenseStatus['domain'] ?? request()->getHost();
    $daysLeft = $licenseStatus['days_left'] ?? 0;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Liteback')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        * { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        .font-mono-jet { font-family: 'JetBrains Mono', monospace !important; }
        .text-xs { font-size: 0.75rem !important; }

        /* Sleek Modern Dark Theme for Liteback Backend */
        body.dark-mode {
            background-color: #090d16 !important;
            color: #ffffff !important;
        }
        .dark-mode .content-wrapper {
            background-color: #0b0f19 !important;
            color: #ffffff !important;
        }
        .dark-mode .content-header h1 {
            color: #ffffff !important;
            font-weight: 800 !important;
        }
        .dark-mode .main-header {
            background-color: #111827 !important;
            border-bottom: 1px solid #1f2937 !important;
        }
        .dark-mode .main-sidebar {
            background-color: #0d121d !important;
            border-right: 1px solid #1f2937 !important;
        }
        .dark-mode .card {
            background-color: #111827 !important;
            border: 1px solid #1f2937 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 24px rgba(0,0,0,0.35) !important;
            border-radius: 14px !important;
        }
        .dark-mode .card-header {
            background-color: #141d30 !important;
            border-bottom: 1px solid #1f2937 !important;
            color: #ffffff !important;
        }
        .dark-mode .card-title, .dark-mode .card-header h1, .dark-mode .card-header h2, 
        .dark-mode .card-header h3, .dark-mode .card-header h4, .dark-mode .card-header h5 {
            color: #ffffff !important;
            font-weight: 700 !important;
        }
        .dark-mode .card-footer {
            background-color: #141d30 !important;
            border-top: 1px solid #1f2937 !important;
            color: #ffffff !important;
        }
        .dark-mode .bg-light, .dark-mode .bg-white {
            background-color: #162238 !important;
            color: #ffffff !important;
            border-color: #1f2937 !important;
        }
        .dark-mode .form-control, .dark-mode .custom-select {
            background-color: #131c2e !important;
            border: 1px solid #283955 !important;
            color: #ffffff !important;
        }
        .dark-mode .form-control:focus, .dark-mode .custom-select:focus {
            background-color: #18233a !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
        }
        .dark-mode .form-control::placeholder {
            color: #64748b !important;
        }
        .dark-mode .input-group-text {
            background-color: #1c273e !important;
            border: 1px solid #283955 !important;
            color: #cbd5e1 !important;
        }
        .dark-mode .table {
            color: #ffffff !important;
            border-color: #1f2937 !important;
        }
        .dark-mode .table th {
            background-color: #141d30 !important;
            color: #ffffff !important;
            border-color: #1f2937 !important;
        }
        .dark-mode .table td {
            border-color: #1f2937 !important;
            color: #e2e8f0 !important;
            background-color: transparent !important;
        }
        .dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.02) !important;
        }
        .dark-mode .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }
        .dark-mode .modal-content {
            background-color: #111827 !important;
            border: 1px solid #1f2937 !important;
            color: #ffffff !important;
        }
        .dark-mode .modal-header, .dark-mode .modal-footer {
            border-color: #1f2937 !important;
        }
        .dark-mode .close {
            color: #ffffff !important;
            text-shadow: none !important;
        }
        .dark-mode .text-muted {
            color: #94a3b8 !important;
        }
        .dark-mode .text-secondary {
            color: #cbd5e1 !important;
        }
        .dark-mode .text-dark {
            color: #ffffff !important;
        }
        .dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode h4, .dark-mode h5, .dark-mode h6 {
            color: #ffffff !important;
        }
        .dark-mode label {
            color: #e2e8f0 !important;
        }
        .dark-mode .border {
            border-color: #1f2937 !important;
        }
        .dark-mode .border-bottom {
            border-bottom-color: #1f2937 !important;
        }
        .dark-mode .border-top {
            border-top-color: #1f2937 !important;
        }
        .dark-mode .page-item .page-link {
            background-color: #131c2e !important;
            border-color: #283955 !important;
            color: #e2e8f0 !important;
        }
        .dark-mode .page-item.active .page-link {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini dark-mode">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-dark px-3" style="background-color: #111827; border-bottom: 1px solid #1f2937;">
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('liteback.users.index') }}" class="nav-link font-weight-bold">Liteback</a>
            </li>
            <li class="nav-item d-none d-md-inline-block">
                <a href="{{ route('frontend.game.list') }}" target="_blank" class="nav-link text-muted font-weight-bold" title="Open Frontend Casino in new tab">
                    <i class="fas fa-gamepad mr-1 text-primary"></i> Player Frontend &nearr;
                </a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto align-items-center">
            <!-- Global License Status Badge & Direct Operator Portal Trigger -->
            <li class="nav-item mr-2">
                @if($licenseState === 'active')
                    <div class="btn-group">
                        <a href="{{ $operatorPortalUrl }}" target="_blank" class="btn btn-sm btn-outline-success font-weight-bold d-flex align-items-center shadow-sm" style="border-radius: 20px; padding: 4px 12px;" title="Promex SaaS Active: Click to open Operator Portal">
                            <span class="badge badge-success mr-1.5 p-1" style="border-radius: 50%;"> </span>
                            <span>PROMEX SAAS: ACTIVE ({{ $daysLeft }}d)</span>
                            <i class="fas fa-external-link-alt ml-1.5 text-xs"></i>
                        </a>
                        <a href="{{ route('liteback.store.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 20px; margin-left: 2px;" title="Store & License Settings">
                            <i class="fas fa-cog"></i>
                        </a>
                    </div>
                @elseif($licenseState === 'suspended' || $licenseState === 'expired')
                    <div class="btn-group">
                        <a href="{{ $operatorPortalUrl }}" target="_blank" class="btn btn-sm btn-danger font-weight-bold d-flex align-items-center shadow-sm" style="border-radius: 20px; padding: 4px 12px;" title="License Suspended / Expired: Click to Renew">
                            <i class="fas fa-exclamation-triangle mr-1.5"></i>
                            <span>LICENSE EXPIRED / SUSPENDED</span>
                            <i class="fas fa-external-link-alt ml-1.5 text-xs"></i>
                        </a>
                        <a href="{{ route('liteback.store.index') }}" class="btn btn-sm btn-outline-danger" style="border-radius: 20px; margin-left: 2px;" title="Manage License Key">
                            <i class="fas fa-key"></i>
                        </a>
                    </div>
                @else
                    <div class="btn-group">
                        <a href="{{ route('liteback.store.index') }}" class="btn btn-sm btn-warning font-weight-bold text-dark d-flex align-items-center shadow-sm" style="border-radius: 20px; padding: 4px 12px;" title="Community Edition: Click to activate full license">
                            <i class="fas fa-shield-alt mr-1.5 text-dark"></i>
                            <span>COMMUNITY EDITION (UNREGISTERED)</span>
                        </a>
                        <a href="https://promex.me/platforms/promex-gaming-suite/" target="_blank" class="btn btn-sm btn-dark font-weight-bold text-white shadow-sm" style="border-radius: 20px; margin-left: 2px;" title="Buy License on Promex.me">
                            <span>BUY &nearr;</span>
                        </a>
                    </div>
                @endif
            </li>

            <li class="nav-item">
                <a href="{{ route('liteback.profile.password') }}" class="nav-link font-weight-bold text-secondary" title="Change Admin Password">
                    <i class="fas fa-user-shield mr-1"></i> Password
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('liteback.users.index') }}" class="brand-link">
            <span class="brand-text font-weight-light">Liteback</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('liteback.users.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Users</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('liteback.games.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-gamepad"></i>
                            <p>Games</p>
                        </a>
                        <ul class="nav nav-treeview ml-3">
                            <li class="nav-item">
                                <a href="{{ route('liteback.games.index') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Active</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('liteback.games.inactive') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Inactive</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-trophy"></i>
                            <p>
                                Sportsbook
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview ml-3">
                            <li class="nav-item">
                                <a href="{{ route('liteback.sports.dashboard') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('liteback.sports.categories') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Categories / Leagues</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('liteback.sports.games') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Events & Odds</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('liteback.sports.settlements') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Settlements</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('liteback.sports.settings') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Settings</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>
                                Payments
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview ml-3">
                            <li class="nav-item">
                                <a href="{{ route('liteback.payments.manual.index') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Manual Deposits</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('liteback.withdrawals.index') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon text-warning"></i>
                                    <p>Cashouts / Withdrawals</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('liteback.payments.settings') }}" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Gateways Settings</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('liteback.lotto.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-dice text-warning"></i>
                            <p>Lotto Jackpot Zone</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('liteback.predictions.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-magic text-purple"></i>
                            <p>Prediction Markets</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('liteback.affiliates.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-users text-success"></i>
                            <p>Affiliate & Referrals</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('liteback.vip.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-gem text-warning"></i>
                            <p>VIP Club & Rakeback</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('liteback.settings.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-cogs text-info"></i>
                            <p>System & API Keys</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('liteback.store.index') }}" class="nav-link {{ Route::is('liteback.store*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-store text-primary"></i>
                            <p>
                                Store & License
                                @if($licenseState === 'active')
                                    <span class="badge badge-success right">Active</span>
                                @elseif($licenseState === 'suspended' || $licenseState === 'expired')
                                    <span class="badge badge-danger right">Expired</span>
                                @else
                                    <span class="badge badge-warning right">Community</span>
                                @endif
                            </p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>@yield('page_title', 'Dashboard')</h1>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <!-- Persistent License Notice Banner -->
                @if($licenseState === 'unregistered')
                    <div class="alert alert-warning border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #241905 0%, #1f1402 100%); border: 1px solid #785a10 !important; border-left: 5px solid #f59e0b !important; border-radius: 12px; color: #ffffff !important;">
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between p-1">
                            <div class="mb-2 mb-md-0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge badge-warning text-dark font-weight-bold px-2 py-0.5 uppercase tracking-wider" style="font-size: 10px; border-radius: 6px;">
                                        COMMUNITY / FREE EDITION
                                    </span>
                                    <span class="text-white-50 small font-mono-jet ml-2">Domain: <strong class="text-white">{{ $boundDomain }}</strong></span>
                                </div>
                                <h6 class="font-weight-bold text-white mb-1">
                                    <i class="fas fa-exclamation-triangle mr-1 text-warning"></i> Running on Unregistered Community Mode
                                </h6>
                                <p class="mb-0 text-white-50 small" style="line-height: 1.5;">
                                    Local slots and core features are open. Cloud perks (Hosted Games CDN, Liteback Store Pack Downloads, Central Sportsbook Odds Feed, and GitHub Cloud Live Upgrades) require an active Promex SaaS license key.
                                </p>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0 ml-md-3">
                                <a href="{{ route('liteback.store.index') }}" class="btn btn-sm btn-warning font-weight-bold text-dark shadow-sm px-3 py-1.5" style="border-radius: 8px;">
                                    <i class="fas fa-key mr-1"></i> Enter License Key
                                </a>
                                <a href="https://promex.me/platforms/promex-gaming-suite/" target="_blank" class="btn btn-sm btn-outline-light font-weight-bold shadow-sm px-3 py-1.5 ml-2" style="border-radius: 8px;">
                                    <i class="fas fa-shopping-cart mr-1 text-warning"></i> Buy License on Promex.me &nearr;
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif($licenseState === 'suspended' || $licenseState === 'expired')
                    <div class="alert alert-danger border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #260c0c 0%, #1f0808 100%); border: 1px solid #7f1d1d !important; border-left: 5px solid #ef4444 !important; border-radius: 12px; color: #ffffff !important;">
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between p-1">
                            <div class="mb-2 mb-md-0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge badge-danger text-white font-weight-bold px-2 py-0.5 uppercase tracking-wider" style="font-size: 10px; border-radius: 6px;">
                                        SUBSCRIPTION EXPIRED
                                    </span>
                                    <span class="text-white-50 small font-mono-jet ml-2">Domain: <strong class="text-white">{{ $boundDomain }}</strong></span>
                                </div>
                                <h6 class="font-weight-bold text-danger mb-1">
                                    <i class="fas fa-ban mr-1"></i> License Expired or Suspended
                                </h6>
                                <p class="mb-0 text-white-50 small" style="line-height: 1.5;">
                                    Your Promex SaaS license for domain <code class="text-warning">{{ $boundDomain }}</code> has expired. Cloud data feeds and remote store downloads are paused. Renew your subscription to restore all verified cloud services.
                                </p>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0 ml-md-3">
                                <a href="{{ $operatorPortalUrl }}" target="_blank" class="btn btn-sm btn-danger font-weight-bold shadow-sm px-3 py-1.5" style="border-radius: 8px;">
                                    <i class="fas fa-sync-alt mr-1"></i> Operator Portal & Renew &nearr;
                                </a>
                                <a href="{{ route('liteback.store.index') }}" class="btn btn-sm btn-outline-light font-weight-bold shadow-sm px-3 py-1.5 ml-2" style="border-radius: 8px;">
                                    Manage Key
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif($licenseState === 'active' && $daysLeft <= 7)
                    <div class="alert alert-info border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #092138 0%, #061726 100%); border: 1px solid #0369a1 !important; border-left: 5px solid #0ea5e9 !important; border-radius: 12px; color: #ffffff !important;">
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between p-1">
                            <div>
                                <h6 class="font-weight-bold text-white mb-1">
                                    <i class="fas fa-clock mr-1 text-info"></i> License Expiring in {{ $daysLeft }} Days
                                </h6>
                                <p class="mb-0 text-white-50 small">
                                    Your Promex SaaS license expires soon. Renew to avoid interruption to cloud odds feeds and game pack updates.
                                </p>
                            </div>
                            <div class="mt-2 mt-md-0 ml-md-3">
                                <a href="{{ $operatorPortalUrl }}" target="_blank" class="btn btn-sm btn-info font-weight-bold shadow-sm px-3 py-1.5" style="border-radius: 8px;">
                                    Renew on Operator Portal &nearr;
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
