@extends('liteback.layout')

@section('title', 'Sportsbook Dashboard')
@section('page_title', 'Sportsbook Dashboard')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info" style="background-color: #17a2b8 !important; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <div class="inner">
                <h3>{{ $stats['total_bets'] }}</h3>
                <p>Total Bets Placed</p>
            </div>
            <div class="icon" style="float: right; margin-top: -60px; font-size: 40px; opacity: 0.3;"><i class="fas fa-ticket-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success" style="background-color: #28a745 !important; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <div class="inner">
                <h3>${{ number_format($stats['total_stakes'], 2) }}</h3>
                <p>Total Stakes</p>
            </div>
            <div class="icon" style="float: right; margin-top: -60px; font-size: 40px; opacity: 0.3;"><i class="fas fa-dollar-sign"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning" style="background-color: #ffc107 !important; color: #212529; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <div class="inner">
                <h3>${{ number_format($stats['total_payouts'], 2) }}</h3>
                <p>Total Payouts</p>
            </div>
            <div class="icon" style="float: right; margin-top: -60px; font-size: 40px; opacity: 0.3;"><i class="fas fa-gift"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger" style="background-color: #dc3545 !important; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <div class="inner">
                <h3>${{ number_format($stats['net_ggr'], 2) }}</h3>
                <p>Net GGR (revenue)</p>
            </div>
            <div class="icon" style="float: right; margin-top: -60px; font-size: 40px; opacity: 0.3;"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Manual Command Runner</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Manually trigger Odds API synchronization or cleanup commands.</p>
                <form action="{{ route('liteback.sports.commands.run') }}" method="POST" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <select name="command" class="form-control" required>
                            <option value="sports:sync:all">sports:sync:all (RUN FULL SEQUENCE - leagues, games, odds, open, cleanup)</option>
                            <option value="sports:sync:upcoming">sports:sync:upcoming (fetch upcoming global pre-match odds - TEST / ONCE)</option>
                            <option value="sports:sync:leagues">sports:sync:leagues (fetch categories/leagues)</option>
                            <option value="sports:sync:games">sports:sync:games (fetch active events)</option>
                            <option value="sports:sync:odds">sports:sync:odds (fetch pre-match odds)</option>
                            <option value="sports:sync:odds-inplay">sports:sync:odds-inplay (fetch live odds)</option>
                            <option value="sports:games:open">sports:games:open (set games open for betting)</option>
                            <option value="sports:events:cleanup">sports:events:cleanup (run cleanup logic)</option>
                        </select>
                        <span class="input-group-append">
                            <button type="submit" class="btn btn-primary">Run Command</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Sync logs</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-valign-middle">
                    <thead>
                        <tr>
                            <th>Job Alias</th>
                            <th>Started At</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cronLogs as $log)
                            <tr>
                                <td><code>{{ $log->job_alias }}</code></td>
                                <td>{{ $log->start_at }}</td>
                                <td>{{ $log->duration }}s</td>
                                <td>
                                    @if($log->error)
                                        <span class="badge badge-danger" title="{{ $log->error }}">Failed</span>
                                    @else
                                        <span class="badge badge-success">Success</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No logs recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
