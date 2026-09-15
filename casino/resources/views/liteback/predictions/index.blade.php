@extends('liteback.layout')

@section('title', 'Liteback - Prediction Markets Management')
@section('page_title', 'Prediction Markets Management')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ $errors->first() }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Metric Stat Widgets -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $markets->total() }}</h3>
                    <p>Total Prediction Markets</p>
                </div>
                <div class="icon">
                    <i class="fas fa-poll"></i>
                </div>
                <a href="{{ route('liteback.predictions.index') }}" class="small-box-footer">All Markets <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalActive }}</h3>
                    <p>Active Trading Markets</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="{{ route('liteback.predictions.index', ['status' => 'active']) }}" class="small-box-footer">Active Only <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3>{{ $totalSettled }}</h3>
                    <p>Settled & Resolved</p>
                </div>
                <div class="icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <a href="{{ route('liteback.predictions.index', ['status' => 'settled']) }}" class="small-box-footer">Settled Only <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($totalVolume, 0) }}</h3>
                    <p>Total Pool Liquidity (C)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-coins"></i>
                </div>
                <span class="small-box-footer">AMM Constant Product Liquidity</span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Create Prediction Topic Card -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> Create Prediction Market</h3>
                </div>
                <form action="{{ route('liteback.predictions.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Category</label>
                            <select name="category" class="form-control" required>
                                <option value="geopolitics">Geopolitics & Elections</option>
                                <option value="crypto">Crypto & Economy</option>
                                <option value="tech">Tech & AI</option>
                                <option value="sports">Sports Special</option>
                                <option value="custom">Custom / Entertainment</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Market Question</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Will Bitcoin reach $150k in 2026?" required>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Resolution Criteria</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Exact verified criteria required for YES outcome..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Initial Probability / YES Share Price ($0.01 - $0.99)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" max="0.99" name="yes_price" class="form-control font-weight-bold text-success" value="0.50" required id="input-yes-price">
                            </div>
                            <small class="form-text text-muted" id="price-calc-helper">Initial: 50¢ YES (50%) / 50¢ NO (50%)</small>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Initial Seed Liquidity (Cedar Coins)</label>
                            <input type="number" name="initial_liquidity" class="form-control" value="2000" min="500" step="500" required>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Resolution Deadline (End Date)</label>
                            <input type="datetime-local" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                            <i class="fas fa-check mr-1"></i> Deploy Prediction Market
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Prediction Markets Table -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card card-outline card-secondary shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-poll mr-1"></i> Live Prediction Markets & Orders</h3>
                    <div class="card-tools">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('liteback.predictions.index') }}" class="btn btn-outline-secondary {{ $status === 'all' ? 'active' : '' }}">All</a>
                            <a href="{{ route('liteback.predictions.index', ['status' => 'active']) }}" class="btn btn-outline-success {{ $status === 'active' ? 'active' : '' }}">Active</a>
                            <a href="{{ route('liteback.predictions.index', ['status' => 'settled']) }}" class="btn btn-outline-purple {{ $status === 'settled' ? 'active' : '' }}">Settled</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-dark small">
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Market Question</th>
                                    <th>Share Prices (AMM)</th>
                                    <th>Pool Liquidity</th>
                                    <th style="width: 90px;" class="text-center">Status</th>
                                    <th style="width: 170px;" class="text-right">Settle Outcome</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($markets as $m)
                                    <tr>
                                        <td class="font-weight-bold text-muted">#{{ $m->id }}</td>
                                        <td>
                                            <div class="badge badge-secondary mb-1 uppercase">{{ $m->category }}</div>
                                            <div class="font-weight-bold text-dark">{{ $m->title }}</div>
                                            <div class="text-muted small">Ends: {{ $m->end_date ? $m->end_date->format('M d, Y') : 'Open' }} | {{ $m->votes_count }} Orders</div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold text-success">
                                                YES: ${{ number_format($m->yes_price, 2) }} ({{ $m->yes_percent }}%)
                                            </div>
                                            <div class="font-weight-bold text-danger">
                                                NO:  ${{ number_format($m->no_price, 2) }} ({{ $m->no_percent }}%)
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ number_format($m->total_volume, 0) }} C</strong>
                                            <div class="small text-muted">YES: {{ number_format($m->pool_yes, 0) }} | NO: {{ number_format($m->pool_no, 0) }}</div>
                                        </td>
                                        <td class="text-center">
                                            @if($m->status === 'active')
                                                <span class="badge badge-success px-2 py-1">LIVE</span>
                                            @elseif(str_starts_with($m->status, 'settled_yes'))
                                                <span class="badge badge-primary px-2 py-1">YES WON</span>
                                            @elseif(str_starts_with($m->status, 'settled_no'))
                                                <span class="badge badge-warning px-2 py-1">NO WON</span>
                                            @elseif(str_starts_with($m->status, 'settled_void'))
                                                <span class="badge badge-secondary px-2 py-1">VOID</span>
                                            @else
                                                <span class="badge badge-dark px-2 py-1">{{ strtoupper($m->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($m->status === 'active')
                                                <div class="btn-group btn-group-sm">
                                                    <form action="{{ route('liteback.predictions.settle', $m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Resolve #{{ $m->id }} as YES won and credit payouts?');">
                                                        @csrf
                                                        <input type="hidden" name="outcome" value="yes">
                                                        <button type="submit" class="btn btn-xs btn-success font-weight-bold">YES</button>
                                                    </form>
                                                    <form action="{{ route('liteback.predictions.settle', $m->id) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Resolve #{{ $m->id }} as NO won and credit payouts?');">
                                                        @csrf
                                                        <input type="hidden" name="outcome" value="no">
                                                        <button type="submit" class="btn btn-xs btn-danger font-weight-bold">NO</button>
                                                    </form>
                                                    <form action="{{ route('liteback.predictions.settle', $m->id) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Void market #{{ $m->id }} and refund all stakes?');">
                                                        @csrf
                                                        <input type="hidden" name="outcome" value="void">
                                                        <button type="submit" class="btn btn-xs btn-outline-secondary font-weight-bold" title="Refund All Stakes">VOID</button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="badge badge-light border text-muted small">SETTLED</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No prediction markets recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $markets->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const yesPriceInput = document.getElementById('input-yes-price');
    const helper = document.getElementById('price-calc-helper');
    if (yesPriceInput && helper) {
        yesPriceInput.addEventListener('input', function() {
            let val = parseFloat(this.value);
            if (isNaN(val)) val = 0.50;
            val = Math.max(0.01, Math.min(0.99, val));
            const noVal = (1.00 - val).toFixed(2);
            helper.innerText = `Initial: ${Math.round(val * 100)}¢ YES (${Math.round(val * 100)}%) / ${Math.round(noVal * 100)}¢ NO (${Math.round(noVal * 100)}%)`;
        });
    }
});
</script>
@endsection
