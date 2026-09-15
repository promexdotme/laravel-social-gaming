@extends('liteback.layout')

@section('title', 'Liteback - System Controls & API Keys')
@section('page_title', 'System Controls & API Integrations')

@section('content')
    <div class="row">
        <!-- Main Settings Form -->
        <div class="col-lg-8">
            <form method="post" action="{{ route('liteback.settings.update') }}">
                @csrf

                <!-- Module Killswitches & Feature Toggles -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 font-weight-bold">
                            <i class="fas fa-toggle-on mr-2 text-success"></i> Module Killswitches & Public Visibility
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Instantly enable or disable modules across the frontend lobby, navigation menus, and mobile bottom dock.</p>
                        
                        <div class="row">
                            <!-- Casino Slots -->
                            <div class="col-md-6 mb-3">
                                <div class="custom-control custom-switch border p-3 rounded">
                                    <input type="hidden" name="enable_casino_slots" value="0">
                                    <input type="checkbox" class="custom-control-input" id="switchSlots" name="enable_casino_slots" value="1" {{ settings('enable_casino_slots', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="switchSlots">
                                        Casino Slots Grid
                                    </label>
                                    <small class="text-muted d-block mt-1">Show/hide 1,000+ arcade & video slots.</small>
                                </div>
                            </div>

                            <!-- CEDAR Originals -->
                            <div class="col-md-6 mb-3">
                                <div class="custom-control custom-switch border p-3 rounded">
                                    <input type="hidden" name="enable_cedar_originals" value="0">
                                    <input type="checkbox" class="custom-control-input" id="switchCedar" name="enable_cedar_originals" value="1" {{ settings('enable_cedar_originals', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="switchCedar">
                                        CEDAR Originals (Crash, Plinko, Mines, Dice, Wheel)
                                    </label>
                                    <small class="text-muted d-block mt-1">Show/hide custom proprietary games.</small>
                                </div>
                            </div>

                            <!-- Sportsbook -->
                            <div class="col-md-6 mb-3">
                                <div class="custom-control custom-switch border p-3 rounded">
                                    <input type="hidden" name="enable_sportsbook" value="0">
                                    <input type="checkbox" class="custom-control-input" id="switchSports" name="enable_sportsbook" value="1" {{ settings('enable_sportsbook', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="switchSports">
                                        Battle Odds Sportsbook
                                    </label>
                                    <small class="text-muted d-block mt-1">Match cards, 1X2 odds & betslip.</small>
                                </div>
                            </div>

                            <!-- Lotto -->
                            <div class="col-md-6 mb-3">
                                <div class="custom-control custom-switch border p-3 rounded">
                                    <input type="hidden" name="enable_lotto" value="0">
                                    <input type="checkbox" class="custom-control-input" id="switchLotto" name="enable_lotto" value="1" {{ settings('enable_lotto', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="switchLotto">
                                        Cedar Lotto Jackpot Zone
                                    </label>
                                    <small class="text-muted d-block mt-1">3D ball selector & multi-draw jackpot.</small>
                                </div>
                            </div>

                            <!-- Predictions -->
                            <div class="col-md-6 mb-3">
                                <div class="custom-control custom-switch border p-3 rounded">
                                    <input type="hidden" name="enable_predictions" value="0">
                                    <input type="checkbox" class="custom-control-input" id="switchPredictions" name="enable_predictions" value="1" {{ settings('enable_predictions', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="switchPredictions">
                                        Future Vote Prediction Markets
                                    </label>
                                    <small class="text-muted d-block mt-1">Polymarket-style YES/NO voting cards.</small>
                                </div>
                            </div>

                            <!-- WhatsApp OTP -->
                            <div class="col-md-6 mb-3">
                                <div class="custom-control custom-switch border p-3 rounded">
                                    <input type="hidden" name="enable_whatsapp_otp" value="0">
                                    <input type="checkbox" class="custom-control-input" id="switchWhatsapp" name="enable_whatsapp_otp" value="1" {{ settings('enable_whatsapp_otp', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="switchWhatsapp">
                                        WhatsApp Phone OTP Auth
                                    </label>
                                    <small class="text-muted d-block mt-1">Allow 1-click passwordless phone logins.</small>
                                </div>
                            </div>

                            <!-- Free Refill Button -->
                            <div class="col-md-12 mb-2">
                                <div class="custom-control custom-switch border p-3 rounded bg-light">
                                    <input type="hidden" name="enable_refill_coins" value="0">
                                    <input type="checkbox" class="custom-control-input" id="switchRefill" name="enable_refill_coins" value="1" {{ settings('enable_refill_coins', '1') == '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="switchRefill">
                                        Public Coin Refill Button (+50,000)
                                    </label>
                                    <small class="text-muted d-block mt-1">Allow guest and logged-in players to self-refill free coins from the topbar and bottom dock.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- External API Integrations -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0 font-weight-bold">
                            <i class="fas fa-key mr-2 text-warning"></i> External API Keys & Providers
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Odds API -->
                        <div class="border-bottom pb-3 mb-3">
                            <h6 class="font-weight-bold text-primary"><i class="fas fa-satellite-dish mr-1"></i> The Odds API (Sportsbook Automation)</h6>
                            <p class="text-muted small mb-2">Automates real-time football, basketball, and tennis odds syncing.</p>
                            <div class="form-row align-items-center">
                                <div class="form-group col-md-7 mb-2">
                                    <label class="small font-weight-bold">API Key</label>
                                    <input type="password" id="oddsApiKeyInput" name="odds_api_key" class="form-control" placeholder="Enter your Odds API Key" value="{{ settings('odds_api_key', '') }}">
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small font-weight-bold">Default Region</label>
                                    <select name="odds_api_region" class="form-control">
                                        <option value="eu" {{ settings('odds_api_region', 'eu') == 'eu' ? 'selected' : '' }}>Europe (eu)</option>
                                        <option value="us" {{ settings('odds_api_region') == 'us' ? 'selected' : '' }}>United States (us)</option>
                                        <option value="uk" {{ settings('odds_api_region') == 'uk' ? 'selected' : '' }}>United Kingdom (uk)</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2 mb-2 d-flex align-items-end">
                                    <button type="button" id="btnTestOddsApi" class="btn btn-outline-info btn-block" title="Verify API Key Connectivity">
                                        <i class="fas fa-plug mr-1"></i> Test
                                    </button>
                                </div>
                            </div>
                            <div id="oddsApiStatus" class="small mt-1 d-none"></div>
                        </div>

                        <!-- WhatsApp Gateway -->
                        <div class="border-bottom pb-3 mb-3">
                            <h6 class="font-weight-bold text-success"><i class="fab fa-whatsapp mr-1"></i> WhatsApp OTP Gateway Token</h6>
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-2">
                                    <label class="small font-weight-bold">Gateway Endpoint URL</label>
                                    <input type="text" name="whatsapp_api_endpoint" class="form-control" placeholder="https://api.gateway.com/send" value="{{ settings('whatsapp_api_endpoint', '') }}">
                                </div>
                                <div class="form-group col-md-6 mb-2">
                                    <label class="small font-weight-bold">Auth Token / Bearer Secret</label>
                                    <input type="password" name="whatsapp_api_token" class="form-control" placeholder="Bearer token or API Secret" value="{{ settings('whatsapp_api_token', '') }}">
                                </div>
                            </div>
                        </div>

                                                <!-- Polymarket Gamma API (Prediction Markets) -->
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="font-weight-bold text-info mb-0">
                                    <i class="fas fa-chart-pie mr-1"></i> Polymarket Gamma API (Prediction Markets)
                                </h6>
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> 100% Free Public API (No Key Required)</span>
                            </div>
                            <p class="text-muted small mb-2">Fetches real-time prediction markets, YES/NO probabilities, and order books directly from official Polymarket oracle feeds.</p>
                            <div class="form-row align-items-center">
                                <div class="form-group col-md-9 mb-2">
                                    <label class="small font-weight-bold">Endpoint URL (Preset Default)</label>
                                    <input type="text" id="polyApiUrlInput" name="polymarket_api_url" class="form-control font-monospace" value="{{ settings('polymarket_api_url', 'https://gamma-api.polymarket.com/events') }}" placeholder="https://gamma-api.polymarket.com/events">
                                </div>
                                <div class="form-group col-md-3 mb-2 d-flex align-items-end">
                                    <button type="button" id="btnTestPolyApi" class="btn btn-outline-info btn-block" title="Test Polymarket Gamma Feed">
                                        <i class="fas fa-satellite mr-1"></i> Test Feed
                                    </button>
                                </div>
                            </div>
                            <div id="polyApiStatus" class="small mt-1 d-none"></div>
                        </div>

                        <!-- Coin Economy Defaults -->
                        <div class="mb-4">
                            <h6 class="font-weight-bold text-dark"><i class="fas fa-coins mr-1 text-warning"></i> Virtual Economy & Coin Defaults</h6>
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-2">
                                    <label class="small font-weight-bold">Single Refill Amount (Coins)</label>
                                    <input type="number" step="100" name="default_refill_amount" class="form-control" value="{{ settings('default_refill_amount', '1000') }}">
                                </div>
                                <div class="form-group col-md-6 mb-2">
                                    <label class="small font-weight-bold">New Registered Player Starting Balance</label>
                                    <input type="number" step="100" name="default_starting_coins" class="form-control" value="{{ settings('default_starting_coins', '1000') }}">
                                    <small class="text-muted">e.g. 1,000 Coins = $10.00 play balance at 100 points/$1</small>
                                </div>
                            </div>
                        </div>

                        <!-- Prize Redemption & Cashout Controls -->
                        <div class="mb-4 p-3 rounded border bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-hand-holding-usd mr-1 text-success"></i> Prize Redemption & Cashout Controls</h6>
                                <span class="badge badge-{{ settings('enable_cashout', '1') == '1' ? 'success' : 'secondary' }}">
                                    {{ settings('enable_cashout', '1') == '1' ? 'Active' : 'Disabled' }}
                                </span>
                            </div>
                            <p class="text-muted small mb-3">Control whether players can request manual prize cashouts, exchange rates, and limits.</p>

                            <div class="form-row">
                                <div class="form-group col-md-4 mb-2">
                                    <label class="small font-weight-bold">Cashout Module Enabled</label>
                                    <select name="enable_cashout" class="form-control">
                                        <option value="1" {{ settings('enable_cashout', '1') == '1' ? 'selected' : '' }}>Enabled (Show in User Profile)</option>
                                        <option value="0" {{ settings('enable_cashout', '1') == '0' ? 'selected' : '' }}>Disabled (Hidden Platform-Wide)</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4 mb-2">
                                    <label class="small font-weight-bold">Exchange Rate (Points per $1.00 USD)</label>
                                    <div class="input-group">
                                        <input type="number" step="1" min="1" name="coins_per_dollar" class="form-control font-weight-bold" value="{{ settings('coins_per_dollar', '100') }}">
                                        <div class="input-group-append"><span class="input-group-text">Pts = $1.00</span></div>
                                    </div>
                                    <small class="text-muted">100 points = $1.00 (1 cent = 1 point)</small>
                                </div>
                                <div class="form-group col-md-4 mb-2">
                                    <label class="small font-weight-bold">Minimum Cashout Limit (Points)</label>
                                    <div class="input-group">
                                        <input type="number" step="100" min="100" name="min_cashout_coins" class="form-control" value="{{ settings('min_cashout_coins', '2000') }}">
                                        <div class="input-group-append"><span class="input-group-text">Points</span></div>
                                    </div>
                                    <small class="text-muted">2,000 Pts = $20.00 USD minimum</small>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">Allowed Withdrawal Methods (Comma-separated)</label>
                                <input type="text" name="cashout_methods" class="form-control" value="{{ settings('cashout_methods', 'USDT (TRC-20), Whish Money, OMT, Bank Transfer, PayPal, Cash Agent') }}">
                                <small class="text-muted">Shown in player withdrawal method dropdown</small>
                            </div>
                        </div>

                        <hr>

                        <!-- CEDAR Originals Settings -->
                        <div>
                            <h6 class="font-weight-bold text-dark"><i class="fas fa-rocket mr-1 text-danger"></i> CEDAR Originals & Provably Fair Controls</h6>
                            <p class="text-muted small mb-3">Tune the cryptographic mathematical engine, house edge, and wagering limits for Crash, Plinko, and Mines.</p>
                            
                            <div class="form-group">
                                <label class="font-weight-bold">Maximum payout per Cedar round (Coins)</label>
                                <input type="number" name="cedar_max_payout" min="1" max="100000000" step="0.01" required class="form-control" value="{{ settings('cedar_max_payout', '1000000') }}">
                                <small class="text-muted">Applies to new rounds. Existing rounds retain their original rules. Wheel tables return at most 99% before payout rounding, caps and rewards. Crash is a solo game.</small>
                            </div>
                            <!-- CedarCrash -->
                            <div class="mb-3 p-3 bg-light rounded border">
                                <div class="font-weight-bold text-primary mb-2"><i class="fas fa-chart-line mr-1"></i> CedarCrash</div>
                                <div class="form-row">
                                    <div class="form-group col-md-3 mb-2">
                                        <label class="small font-weight-bold">House Edge %</label>
                                        <div class="input-group">
                                            <input type="number" step="0.1" min="0.5" max="20" name="cedar_crash_house_edge" class="form-control" value="{{ settings('cedar_crash_house_edge', '3.0') }}">
                                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-3 mb-2">
                                        <label class="small font-weight-bold">Min Bet</label>
                                        <input type="number" step="10" min="1" max="100000" name="cedar_crash_min_bet" class="form-control" value="{{ settings('cedar_crash_min_bet', '10') }}">
                                    </div>
                                    <div class="form-group col-md-3 mb-2">
                                        <label class="small font-weight-bold">Max Bet</label>
                                        <input type="number" step="100" min="10" max="1000000" name="cedar_crash_max_bet" class="form-control" value="{{ settings('cedar_crash_max_bet', '50000') }}">
                                    </div>
                                    <div class="form-group col-md-3 mb-2">
                                        <label class="small font-weight-bold">Max Multiplier Cap</label>
                                        <div class="input-group">
                                            <input type="number" step="10" min="2" max="10000" name="cedar_crash_max_multiplier" class="form-control" value="{{ settings('cedar_crash_max_multiplier', '1000.0') }}">
                                            <div class="input-group-append"><span class="input-group-text">x</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CedarPlinko -->
                            <div class="mb-3 p-3 bg-light rounded border">
                                <div class="font-weight-bold text-success mb-2"><i class="fas fa-circle-notch mr-1"></i> CedarPlinko</div>
                                <div class="form-row">
                                    <div class="form-group col-md-6 mb-2">
                                        <label class="small font-weight-bold">Min Bet (Coins)</label>
                                        <input type="number" step="10" min="1" max="100000" name="cedar_plinko_min_bet" class="form-control" value="{{ settings('cedar_plinko_min_bet', '10') }}">
                                    </div>
                                    <div class="form-group col-md-6 mb-2">
                                        <label class="small font-weight-bold">Max Bet (Coins)</label>
                                        <input type="number" step="100" min="10" max="1000000" name="cedar_plinko_max_bet" class="form-control" value="{{ settings('cedar_plinko_max_bet', '50000') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- CedarMines -->
                            <div class="mb-3 p-3 bg-light rounded border">
                                <div class="font-weight-bold text-warning mb-2"><i class="fas fa-gem mr-1"></i> CedarMines</div>
                                <div class="form-row">
                                    <div class="form-group col-md-4 mb-2">
                                        <label class="small font-weight-bold">House Edge %</label>
                                        <div class="input-group">
                                            <input type="number" step="0.1" min="0.5" max="20" name="cedar_mines_house_edge" class="form-control" value="{{ settings('cedar_mines_house_edge', '3.0') }}">
                                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 mb-2">
                                        <label class="small font-weight-bold">Min Bet (Coins)</label>
                                        <input type="number" step="10" min="1" max="100000" name="cedar_mines_min_bet" class="form-control" value="{{ settings('cedar_mines_min_bet', '10') }}">
                                    </div>
                                    <div class="form-group col-md-4 mb-2">
                                        <label class="small font-weight-bold">Max Bet (Coins)</label>
                                        <input type="number" step="100" min="10" max="1000000" name="cedar_mines_max_bet" class="form-control" value="{{ settings('cedar_mines_max_bet', '50000') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- CedarDice -->
                            <div class="mb-3 p-3 bg-light rounded border">
                                <div class="font-weight-bold text-info mb-2"><i class="fas fa-dice-d20 mr-1"></i> CedarDice (Slider 0.00 – 99.99)</div>
                                <div class="form-row">
                                    <div class="form-group col-md-4 mb-2">
                                        <label class="small font-weight-bold">House Edge %</label>
                                        <div class="input-group">
                                            <input type="number" step="0.1" min="0.1" max="10" name="cedar_dice_house_edge" class="form-control" value="{{ settings('cedar_dice_house_edge', '1.0') }}">
                                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 mb-2">
                                        <label class="small font-weight-bold">Min Bet (Coins)</label>
                                        <input type="number" step="10" min="1" max="100000" name="cedar_dice_min_bet" class="form-control" value="{{ settings('cedar_dice_min_bet', '10') }}">
                                    </div>
                                    <div class="form-group col-md-4 mb-2">
                                        <label class="small font-weight-bold">Max Bet (Coins)</label>
                                        <input type="number" step="100" min="10" max="1000000" name="cedar_dice_max_bet" class="form-control" value="{{ settings('cedar_dice_max_bet', '50000') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- CedarWheel -->
                            <div class="p-3 bg-light rounded border">
                                <div class="font-weight-bold text-danger mb-2"><i class="fas fa-dharmachakra mr-1"></i> CedarWheel (Fortune Multipliers up to 49.5x)</div>
                                <div class="form-row">
                                    <div class="form-group col-md-6 mb-2">
                                        <label class="small font-weight-bold">Min Bet (Coins)</label>
                                        <input type="number" step="10" min="1" max="100000" name="cedar_wheel_min_bet" class="form-control" value="{{ settings('cedar_wheel_min_bet', '10') }}">
                                    </div>
                                    <div class="form-group col-md-6 mb-2">
                                        <label class="small font-weight-bold">Max Bet (Coins)</label>
                                        <input type="number" step="100" min="10" max="1000000" name="cedar_wheel_max_bet" class="form-control" value="{{ settings('cedar_wheel_max_bet', '50000') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-right">
                        <button type="submit" class="btn btn-success font-weight-bold px-4">
                            <i class="fas fa-save mr-1"></i> Save System Settings
                        </button>
                    </div>
                </div>
            </form>
            <div class="card mt-3">
                <div class="card-header font-weight-bold">Recent Cedar rounds</div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Round</th><th>Player</th><th>Game</th><th>Status</th><th>Wager</th><th>Paid</th></tr></thead>
                    <tbody>@forelse($cedarRounds ?? [] as $round)
                    <tr><td><small>{{ $round->id }}</small></td><td>{{ $round->user_id }}</td><td>{{ $round->game }}</td><td>{{ $round->status }}</td><td>{{ $round->wager }}</td><td>{{ $round->win }}</td></tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-2">No recent Cedar rounds found.</td></tr>
                    @endforelse</tbody>
                </table></div>
                <div class="card-footer text-muted small">Crash disconnects are settled by the Laravel scheduler every minute. Active rounds retain the settings accepted at bet time.</div>
            </div>
        </div>

        <!-- Sidebar Actions & System Utilities -->
        <div class="col-lg-4">
            <!-- Manual Operations & Sync Card -->
            <div class="card mb-4 shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0 font-weight-bold"><i class="fas fa-bolt mr-2"></i> Manual Operations</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Execute live server routines immediately without waiting for background cron schedules.</p>
                    
                    <!-- Flush Cache -->
                    <form method="post" action="{{ route('liteback.settings.clear_cache') }}" class="mb-3" onsubmit="return confirm('Flush all application caches now?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-block font-weight-bold">
                            <i class="fas fa-trash-alt mr-1"></i> Flush System Caches
                        </button>
                        <small class="text-muted d-block mt-1">Clears Blade templates, route caches, and Redis/file cache.</small>
                    </form>

                    <hr>

                    <!-- Manual Odds Sync -->
                    <form method="post" action="{{ route('liteback.settings.sync_odds_now') }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-block font-weight-bold">
                            <i class="fas fa-sync mr-1"></i> Pull Sports Odds Now
                        </button>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted">Auto: Every 30 min</small>
                            <span class="badge badge-light border text-primary">
                                <i class="far fa-clock mr-1"></i> {{ settings('last_odds_sync_at') ? \Carbon\Carbon::parse(settings('last_odds_sync_at'))->diffForHumans() : 'Never' }}
                            </span>
                        </div>
                    </form>

                    <!-- Manual Settle Bets -->
                    <form method="post" action="{{ route('liteback.settings.settle_matches_now') }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-block font-weight-bold">
                            <i class="fas fa-check-double mr-1"></i> Run Bet Auto-Settlement
                        </button>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted">Auto: Every 5 min</small>
                            <span class="badge badge-light border text-success">
                                <i class="far fa-clock mr-1"></i> {{ settings('last_sports_settlement_at') ? \Carbon\Carbon::parse(settings('last_sports_settlement_at'))->diffForHumans() : 'Never' }}
                            </span>
                        </div>
                    </form>

                    <!-- Manual Trigger Lotto Draw -->
                    <form method="post" action="{{ route('liteback.settings.draw_lotto_now') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-block font-weight-bold">
                            <i class="fas fa-ticket-alt mr-1"></i> Trigger Lotto Draw Now
                        </button>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted">Auto: Hourly</small>
                            <span class="badge badge-light border text-warning">
                                <i class="far fa-clock mr-1"></i> {{ settings('last_lotto_draw_at') ? \Carbon\Carbon::parse(settings('last_lotto_draw_at'))->diffForHumans() : 'Never' }}
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Info Widget -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0 font-weight-bold text-dark"><i class="fas fa-info-circle mr-1 text-info"></i> Server Environment</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0 small">
                        <tr>
                            <td class="font-weight-bold">PHP Version</td>
                            <td class="text-right">{{ phpversion() }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Laravel Core</td>
                            <td class="text-right">{{ app()->version() }}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Database</td>
                            <td class="text-right">{{ config('database.default') }} ({{ config('database.connections.' . config('database.default') . '.database') }})</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Server Host</td>
                            <td class="text-right">{{ request()->getHost() }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#btnTestOddsApi').on('click', function() {
        const apiKey = $('#oddsApiKeyInput').val();
        const statusDiv = $('#oddsApiStatus');
        
        statusDiv.removeClass('d-none alert-success alert-danger').addClass('alert alert-info py-2').text('Testing connection to Odds API...');

        $.post('{{ route('liteback.settings.test_odds_api') }}', {
            _token: '{{ csrf_token() }}',
            api_key: apiKey
        }, function(res) {
            if (res.success) {
                statusDiv.removeClass('alert-info alert-danger').addClass('alert-success').html('<i class="fas fa-check-circle mr-1"></i> ' + res.message);
            } else {
                statusDiv.removeClass('alert-info alert-success').addClass('alert-danger').html('<i class="fas fa-exclamation-triangle mr-1"></i> ' + res.message);
            }
        }).fail(function() {
            statusDiv.removeClass('alert-info alert-success').addClass('alert-danger').text('Network failure while testing API endpoint.');
        });
    });
});
</script>
@endsection
