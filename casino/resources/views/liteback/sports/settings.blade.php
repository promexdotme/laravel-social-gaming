@extends('liteback.layout')

@section('title', 'Sportsbook Settings')
@section('page_title', 'Sportsbook Settings')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Configuration Control Panel</h3>
            </div>
            <form action="{{ route('liteback.sports.settings.update') }}" method="POST">
                @csrf
                <div class="card-body">
                    <h5 class="border-bottom pb-2"><strong>Feature Flags</strong></h5>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Sports Betting Enabled</label>
                            <select name="sports_feature_betting_enabled" class="form-control">
                                <option value="1" {{ settings('sports_feature_betting_enabled') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ settings('sports_feature_betting_enabled') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Manual Games Creation</label>
                            <select name="sports_feature_manual_games" class="form-control">
                                <option value="1" {{ settings('sports_feature_manual_games') == '1' ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ settings('sports_feature_manual_games') == '0' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Manual Odds Override</label>
                            <select name="sports_feature_manual_odds_override" class="form-control">
                                <option value="1" {{ settings('sports_feature_manual_odds_override') == '1' ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ settings('sports_feature_manual_odds_override') == '0' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>CSV Exports</label>
                            <select name="sports_feature_exports_enabled" class="form-control">
                                <option value="1" {{ settings('sports_feature_exports_enabled') == '1' ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ settings('sports_feature_exports_enabled') == '0' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Admin Odds API Sync</label>
                            <select name="sports_feature_admin_sync_enabled" class="form-control">
                                <option value="1" {{ settings('sports_feature_admin_sync_enabled') == '1' ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ settings('sports_feature_admin_sync_enabled') == '0' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Admin Settlements Control</label>
                            <select name="sports_feature_admin_settlement_enabled" class="form-control">
                                <option value="1" {{ settings('sports_feature_admin_settlement_enabled') == '1' ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ settings('sports_feature_admin_settlement_enabled') == '0' ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="border-bottom pb-2 mt-4" style="border-bottom: 1px solid #dee2e6; padding-bottom: 8px; margin-top: 20px;"><strong>The Odds API Credentials</strong></h5>
                    <div class="form-group">
                        <label for="ods_api_key">API Secret Key</label>
                        <input type="text" name="ods_api_key" class="form-control" id="ods_api_key" value="{{ settings('ods_api_key') }}" placeholder="Enter your Odds API Key">
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="ods_api_regions">Regions (comma-separated)</label>
                            <input type="text" name="ods_api_regions" class="form-control" id="ods_api_regions" value="{{ settings('ods_api_regions', 'us') }}" placeholder="e.g. us,eu,uk">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="ods_api_markets">Markets (comma-separated)</label>
                            <input type="text" name="ods_api_markets" class="form-control" id="ods_api_markets" value="{{ settings('ods_api_markets', 'h2h') }}" placeholder="e.g. h2h,spreads,totals">
                        </div>
                    </div>

                    <h5 class="border-bottom pb-2 mt-4" style="border-bottom: 1px solid #dee2e6; padding-bottom: 8px; margin-top: 20px;"><strong>Bet Slip Limit Controls</strong></h5>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="single_bet_min_limit">Single Bet Min Stake</label>
                            <input type="number" step="0.01" name="single_bet_min_limit" class="form-control" id="single_bet_min_limit" value="{{ settings('single_bet_min_limit', '1.00') }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="single_bet_max_limit">Single Bet Max Stake</label>
                            <input type="number" step="0.01" name="single_bet_max_limit" class="form-control" id="single_bet_max_limit" value="{{ settings('single_bet_max_limit', '10000.00') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="multi_bet_min_limit">Multi Bet Min Stake</label>
                            <input type="number" step="0.01" name="multi_bet_min_limit" class="form-control" id="multi_bet_min_limit" value="{{ settings('multi_bet_min_limit', '1.00') }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="multi_bet_max_limit">Multi Bet Max Stake</label>
                            <input type="number" step="0.01" name="multi_bet_max_limit" class="form-control" id="multi_bet_max_limit" value="{{ settings('multi_bet_max_limit', '10000.00') }}" required>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right" style="text-align: right; padding-top: 15px;">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
