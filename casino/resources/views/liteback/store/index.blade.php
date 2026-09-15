@extends('liteback.layout')

@section('title', 'Extensions, Add-ons & License')

@section('content')
<style>
    .store-container {
        padding-bottom: 40px;
    }
    .store-panel {
        background: #111827 !important;
        border: 1px solid #1f2937 !important;
        border-radius: 18px !important;
        padding: 24px 28px !important;
        margin-bottom: 24px !important;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35) !important;
        position: relative;
        overflow: hidden;
    }
    .store-heading {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        color: #ffffff !important;
        letter-spacing: -0.02em;
    }
    .store-sub {
        color: #94a3b8 !important;
        font-size: 0.82rem !important;
        font-family: 'JetBrains Mono', monospace !important;
    }
    .store-label-title {
        color: #64748b !important;
        font-size: 0.72rem !important;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.05em;
        font-family: 'JetBrains Mono', monospace !important;
        display: block;
        margin-bottom: 3px;
    }
    .store-val {
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.95rem !important;
        font-family: 'JetBrains Mono', monospace !important;
    }
    .store-stat-pill {
        background: #141e30 !important;
        border: 1px solid #23334d !important;
        border-radius: 12px !important;
        padding: 12px 16px !important;
    }
    .store-grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
        gap: 18px;
    }
    .store-grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
        gap: 18px;
    }
    .module-item {
        background: #141e30 !important;
        border: 1px solid #23334d !important;
        border-radius: 14px !important;
        padding: 20px !important;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.2s ease-in-out;
    }
    .module-item:hover {
        border-color: #3b82f6 !important;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        transform: translateY(-2px);
    }
    .module-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(59, 130, 246, 0.15);
        border: 1px solid rgba(59, 130, 246, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #38bdf8;
    }
    .gamepack-item {
        background: #141e30 !important;
        border: 1px solid #23334d !important;
        border-radius: 14px !important;
        padding: 22px !important;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.2s ease-in-out;
    }
    .gamepack-item:hover {
        border-color: #10b981 !important;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    }
    .key-input-box {
        background: #090d16 !important;
        border: 1px solid #283955 !important;
        border-radius: 10px !important;
        color: #ffffff !important;
        font-family: 'JetBrains Mono', monospace !important;
        font-size: 0.85rem !important;
        padding: 10px 14px !important;
    }
    .key-input-box:focus {
        border-color: #3b82f6 !important;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    }
    .badge-status-active {
        background: rgba(16, 185, 129, 0.2) !important;
        border: 1px solid #10b981 !important;
        color: #34d399 !important;
        font-weight: 800;
        font-size: 0.72rem;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-status-warn {
        background: rgba(245, 158, 11, 0.2) !important;
        border: 1px solid #f59e0b !important;
        color: #fbbf24 !important;
        font-weight: 800;
        font-size: 0.72rem;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-status-danger {
        background: rgba(239, 68, 68, 0.2) !important;
        border: 1px solid #ef4444 !important;
        color: #f87171 !important;
        font-weight: 800;
        font-size: 0.72rem;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>

<div class="store-container">

    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2">
        <div class="mb-3 mb-md-0">
            <h1 class="store-heading d-flex align-items-center gap-2 mb-1">
                <i class="fas fa-store text-primary mr-2"></i>
                Extensions, Add-ons & License
            </h1>
            <p class="store-sub">
                Manage your domain subscription, live service feeds, and install modular game packages.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('liteback.store.refresh_license') }}" method="POST" class="mr-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light font-weight-bold d-flex align-items-center shadow-sm" style="border-radius: 10px; padding: 7px 16px;">
                    <i class="fas fa-sync-alt mr-1.5 text-info"></i>
                    Check License Now
                </button>
            </form>
            <a href="https://promex.me" target="_blank" class="btn btn-sm btn-success font-weight-bold d-flex align-items-center shadow-sm" style="border-radius: 10px; padding: 7px 16px;">
                <i class="fas fa-external-link-alt mr-1.5"></i>
                Promex Store
            </a>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4" style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; border-radius: 12px;">
            <i class="fas fa-check-circle mr-2 fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning d-flex align-items-center mb-4" style="background: rgba(245, 158, 11, 0.15); border: 1px solid #f59e0b; color: #fbbf24; border-radius: 12px;">
            <i class="fas fa-exclamation-triangle mr-2 fs-5"></i>
            <div>{{ session('warning') }}</div>
        </div>
    @endif
    @if(session('danger'))
        <div class="alert alert-danger d-flex align-items-center mb-4" style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; border-radius: 12px;">
            <i class="fas fa-times-circle mr-2 fs-5"></i>
            <div>{{ session('danger') }}</div>
        </div>
    @endif

    <!-- 1. License Card -->
    <div class="store-panel">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="store-label-title mb-0 mr-2">License Status:</span>
                    @if($license['status'] === 'active')
                        <span class="badge-status-active">
                            <i class="fas fa-circle text-success" style="font-size: 8px;"></i> ACTIVE (ENTERPRISE)
                        </span>
                    @elseif($license['status'] === 'grace_period')
                        <span class="badge-status-warn">
                            <i class="fas fa-clock text-warning" style="font-size: 8px;"></i> GRACE PERIOD ({{ $license['days_left'] ?? 3 }} DAYS REMAINING)
                        </span>
                    @elseif($license['status'] === 'suspended')
                        <span class="badge-status-danger">
                            <i class="fas fa-ban text-danger" style="font-size: 8px;"></i> SUSPENDED
                        </span>
                    @else
                        <span class="badge-status-warn">
                            <i class="fas fa-shield-alt text-warning" style="font-size: 8px;"></i> COMMUNITY / TRIAL
                        </span>
                    @endif
                </div>

                <div class="h2 font-weight-bold text-white mb-3" style="letter-spacing: -0.02em;">
                    {{ $license['plan'] ?? 'Community Edition' }}
                </div>

                <div class="row mt-2">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <div class="store-stat-pill">
                            <span class="store-label-title">Bound Domain</span>
                            <span class="store-val">{{ $license['domain'] ?? request()->getHost() }}</span>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0">
                        <div class="store-stat-pill">
                            <span class="store-label-title">Valid Until</span>
                            <span class="store-val text-info">{{ $license['valid_until'] ? date('M d, Y', strtotime($license['valid_until'])) : 'Lifetime / Unlimited' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="store-stat-pill">
                            <span class="store-label-title">License Key</span>
                            <span class="store-val text-warning">{{ !empty($license['license_key']) ? substr($license['license_key'], 0, 8) . '••••••••' . substr($license['license_key'], -4) : 'Unassigned' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Verified Cloud Perks & Portal Link -->
                <div class="mt-3 pt-2 d-flex align-items-center flex-wrap" style="gap: 8px;">
                    <a href="https://clients.377.live/operator?key={{ urlencode($license['license_key'] ?? '') }}" target="_blank" class="btn btn-sm btn-outline-info font-weight-bold d-inline-flex align-items-center shadow-sm" style="border-radius: 8px; font-size: 11px; padding: 4px 10px;">
                        <i class="fas fa-shield-check mr-1.5"></i>
                        Tamper-Proof Cloud Portal &nearr;
                    </a>
                    <span class="badge badge-dark px-2.5 py-1.5 font-mono-jet" style="background: #162238; border: 1px solid #283955; color: #cbd5e1; font-size: 11px;">
                        <i class="fas fa-check-circle text-success mr-1"></i> Games CDN
                    </span>
                    <span class="badge badge-dark px-2.5 py-1.5 font-mono-jet" style="background: #162238; border: 1px solid #283955; color: #cbd5e1; font-size: 11px;">
                        <i class="fas fa-check-circle text-success mr-1"></i> Central Odds Feed
                    </span>
                    <span class="badge badge-dark px-2.5 py-1.5 font-mono-jet" style="background: #162238; border: 1px solid #283955; color: #cbd5e1; font-size: 11px;">
                        <i class="fas fa-check-circle text-success mr-1"></i> Store Packs
                    </span>
                </div>
            </div>

            <!-- License Key Action Form Box -->
            <div class="col-lg-5">
                <div class="p-3" style="background: #0d1422; border: 1px solid #1e2b40; border-radius: 14px;">
                    <span class="store-label-title mb-2 text-white d-flex align-items-center">
                        <i class="fas fa-key text-warning mr-1.5"></i> Update License Key
                    </span>
                    <form action="{{ route('liteback.store.update_license') }}" method="POST">
                        @csrf
                        <div class="form-group mb-2">
                            <input type="text" name="license_key" value="{{ $license['license_key'] }}" placeholder="PROMEX-XXXX-XXXX-XXXX" class="form-control key-input-box w-100">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-primary font-weight-bold flex-fill" style="border-radius: 8px; font-size: 12px; padding: 8px 14px;">
                                <i class="fas fa-save mr-1"></i> Save & Activate
                            </button>
                            <a href="https://promex.me" target="_blank" class="btn btn-outline-light font-weight-bold ml-2" style="border-radius: 8px; font-size: 12px; padding: 8px 14px;">
                                Get Key &nearr;
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Live GitHub & Cloud Updater Card -->
    <div class="store-panel">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
            <div class="mb-2 mb-md-0">
                <h2 class="h5 font-weight-bold text-white mb-1 d-flex align-items-center">
                    <i class="fas fa-cloud-download-alt text-purple mr-2"></i>
                    GitHub Cloud Live Updater
                </h2>
                <p class="store-sub mb-0">
                    Synchronize core code and database migrations directly from central verified releases.
                </p>
            </div>
            <div class="d-flex align-items-center font-mono-jet" style="gap: 8px;">
                <span class="badge badge-dark px-3 py-2" style="background: #162238; border: 1px solid #283955; color: #ffffff; font-size: 12px;">
                    Installed: <strong class="text-success">{{ $versionInfo['current_version'] ?? 'v2.5.0' }}</strong>
                </span>
                <span class="badge badge-dark px-3 py-2 ml-1" style="background: #162238; border: 1px solid #283955; color: #ffffff; font-size: 12px;">
                    Latest: <strong class="text-info">{{ $versionInfo['latest_version'] ?? 'v2.5.0' }}</strong>
                </span>
            </div>
        </div>

        <!-- Operator Risk Notice -->
        <div class="alert alert-warning d-flex align-items-start mb-3" style="background: #241905; border: 1px solid #785a10; color: #fef08a; border-radius: 12px; padding: 12px 16px;">
            <i class="fas fa-exclamation-triangle mr-2 text-warning fs-5 mt-1"></i>
            <div style="font-size: 13px; line-height: 1.5;">
                <strong class="text-white">Operator Risk Notice:</strong> Live updating applies core schema changes and updates files in-place. Always make a full database and code backup before proceeding. Custom modifications should be decoupled via custom modules.
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center pt-1">
            <div class="store-sub mb-2 mb-md-0">
                Release Notes: <span class="text-white font-weight-bold">{{ $versionInfo['release_notes'] ?? 'System is on the latest stable build.' }}</span>
            </div>
            <div>
                <form action="{{ route('liteback.store.apply_update') }}" method="POST" onsubmit="return confirm('APPLY UPDATE WARNING:\n\nThis will download and apply verified update files and execute database migrations.\n\nProceed at your own risk?');">
                    @csrf
                    <button type="submit" class="btn btn-purple font-weight-bold shadow-sm" style="background: #7c3aed; color: #ffffff; border-radius: 10px; padding: 8px 18px; font-size: 12px;">
                        <i class="fas fa-download mr-1.5"></i>
                        Apply Live Update
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Modular Extension Suite -->
    <div class="mb-4">
        <div class="mb-3">
            <h2 class="h5 font-weight-bold text-white mb-1 d-flex align-items-center">
                <i class="fas fa-puzzle-piece text-info mr-2"></i>
                Modular Add-ons & Service Hub
            </h2>
            <p class="store-sub">
                Pre-configured core modules powered by your central service feeds.
            </p>
        </div>

        <div class="store-grid-3">
            @foreach($catalog['modules'] as $mod)
                <div class="module-item">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="module-icon-wrap">
                                <span class="material-symbols-outlined">{{ $mod['icon'] }}</span>
                            </div>
                            <span class="badge badge-success px-2 py-1 font-mono-jet" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; font-size: 10px;">
                                {{ $mod['status'] }}
                            </span>
                        </div>
                        <h3 class="h6 font-weight-bold text-white mb-1">{{ $mod['name'] }}</h3>
                        <p class="store-sub" style="font-size: 12px; line-height: 1.5; color: #94a3b8 !important;">
                            {{ $mod['description'] }}
                        </p>
                    </div>

                    <div class="pt-3 mt-3 border-top border-secondary d-flex justify-content-between align-items-center" style="border-color: #23334d !important;">
                        <span class="font-mono-jet" style="font-size: 11px; color: #64748b;">v{{ $mod['version'] }} • {{ $mod['category'] }}</span>
                        <span class="text-success font-weight-bold font-mono-jet d-flex align-items-center" style="font-size: 11px;">
                            <i class="fas fa-check mr-1"></i> Installed
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 3. Game Packages Downloader -->
    <div class="mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
            <div class="mb-2 mb-md-0">
                <h2 class="h5 font-weight-bold text-white mb-1 d-flex align-items-center">
                    <i class="fas fa-gamepad text-success mr-2"></i>
                    Game Packs & Content Repository
                </h2>
                <p class="store-sub">
                    Download and extract high-definition slot bundles and provably fair mini-games.
                </p>
            </div>
            <div class="store-sub px-3 py-1.5 rounded" style="background: #141e30; border: 1px solid #23334d; font-size: 11px;">
                Storage: <strong class="text-white">Domain/games/</strong>
            </div>
        </div>

        <div class="store-grid-2">
            @foreach($catalog['game_packs'] as $pack)
                <div class="gamepack-item">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="font-mono-jet text-info font-weight-bold text-uppercase" style="font-size: 11px;">
                                {{ $pack['count'] }} Games • {{ $pack['size'] }}
                            </span>
                            @if(!empty($pack['installed']))
                                <span class="badge badge-success px-2 py-1 font-mono-jet" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; font-size: 10px;">
                                    <i class="fas fa-check-circle mr-1"></i> Installed
                                </span>
                            @else
                                <span class="badge badge-secondary px-2 py-1 font-mono-jet" style="background: #23334d; color: #cbd5e1; font-size: 10px;">
                                    Available
                                </span>
                            @endif
                        </div>
                        <h3 class="h6 font-weight-bold text-white mb-1">{{ $pack['name'] }}</h3>
                        <p class="store-sub" style="font-size: 12px; line-height: 1.5; color: #94a3b8 !important;">
                            {{ $pack['description'] }}
                        </p>
                    </div>

                    <div class="pt-3 mt-3 border-top border-secondary d-flex justify-content-between align-items-center" style="border-color: #23334d !important;">
                        <span class="font-mono-jet" style="font-size: 11px; color: #64748b;">
                            {{ $pack['id'] }}
                        </span>
                        <form action="{{ route('liteback.store.install_pack') }}" method="POST">
                            @csrf
                            <input type="hidden" name="pack_id" value="{{ $pack['id'] }}">
                            <button type="submit" class="btn btn-sm {{ !empty($pack['installed']) ? 'btn-outline-light' : 'btn-success' }} font-weight-bold shadow-sm d-flex align-items-center" style="border-radius: 8px; font-size: 11px; padding: 6px 14px;">
                                <i class="fas {{ !empty($pack['installed']) ? 'fa-redo' : 'fa-download' }} mr-1.5"></i>
                                {{ !empty($pack['installed']) ? 'Verify Assets' : 'Download Pack' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection