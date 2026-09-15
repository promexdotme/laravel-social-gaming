@extends('liteback.layout')

@section('title', 'Liteback - Lotto Jackpot Management')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0 text-dark">🎯 Lotto Jackpot Zone Control</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <div class="row">
            <!-- Create Lotto Game Card -->
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Create Lotto Draw Rule</h3>
                    </div>
                    <form action="{{ route('liteback.lotto.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Game Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Cedar Mega Lucky 6" required>
                            </div>
                            <div class="form-group">
                                <label>Max Number Pool</label>
                                <input type="number" name="max_number" class="form-control" value="49" min="10" max="99" required>
                            </div>
                            <div class="form-group">
                                <label>Pick Count per Ticket</label>
                                <input type="number" name="pick_count" class="form-control" value="6" min="3" max="10" required>
                            </div>
                            <div class="form-group">
                                <label>Entry Fee (Cedar Coins)</label>
                                <input type="number" name="entry_fee" class="form-control" value="500" min="50" step="50" required>
                            </div>
                            <div class="form-group">
                                <label>Jackpot Pool (Cedar Coins)</label>
                                <input type="number" name="jackpot_pool" class="form-control" value="2500000" min="10000" step="50000" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-block">Add Lotto Game</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Active Lotto Games List -->
            <div class="col-md-8">
                <div class="card card-dark">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-dice mr-1"></i> Active Jackpot Games</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Game Title</th>
                                    <th>Rules</th>
                                    <th>Entry Fee</th>
                                    <th>Jackpot Pool</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($games as $g)
                                    <tr>
                                        <td>#{{ $g->id }}</td>
                                        <td><strong>{{ $g->title }}</strong></td>
                                        <td><span class="badge badge-info">Pick {{ $g->pick_count }} of {{ $g->max_number }}</span></td>
                                        <td><span class="badge badge-secondary">{{ number_format($g->entry_fee, 0) }} C</span></td>
                                        <td><strong class="text-success">{{ number_format($g->jackpot_pool, 0) }} CEDARS</strong></td>
                                        <td>
                                            @if($g->is_active)
                                                <span class="badge badge-success">ACTIVE</span>
                                            @else
                                                <span class="badge badge-danger">INACTIVE</span>
                                            @endif
                                        </td>
                                        <td class="d-flex gap-1">
                                            <!-- Manual Draw Trigger -->
                                            <form action="{{ route('liteback.lotto.draw', $g->id) }}" method="POST" class="mr-1" onsubmit="return confirm('Trigger winning numbers draw and settle tickets for {{ $g->title }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-warning font-weight-bold">
                                                    ⚡ Trigger Draw
                                                </button>
                                            </form>
                                            
                                            <!-- Toggle Active Status -->
                                            <form action="{{ route('liteback.lotto.toggle', $g->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-xs {{ $g->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                    {{ $g->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No Lotto Games defined.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Player Tickets Table -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-ticket-alt mr-1"></i> Recent Player Lotto Tickets</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Player</th>
                                    <th>Game</th>
                                    <th>Selected Numbers</th>
                                    <th>Status</th>
                                    <th>Prize Won</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $t)
                                    <tr>
                                        <td>#{{ $t->id }}</td>
                                        <td><strong>{{ $t->user->username ?? 'Guest' }}</strong></td>
                                        <td>{{ $t->game->title ?? 'Lotto' }}</td>
                                        <td><code>{{ is_array($t->numbers_json) ? implode(', ', $t->numbers_json) : $t->numbers_json }}</code></td>
                                        <td>
                                            @if($t->status === 'jackpot_win')
                                                <span class="badge badge-warning">🏆 JACKPOT WIN</span>
                                            @elseif($t->status === 'win')
                                                <span class="badge badge-success">WINNER</span>
                                            @elseif($t->status === 'lost')
                                                <span class="badge badge-secondary">LOST</span>
                                            @else
                                                <span class="badge badge-info">PENDING DRAW</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ number_format($t->prize_won, 0) }} C</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No recent lotto tickets.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
