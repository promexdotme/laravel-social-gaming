@extends('liteback.layout')

@section('title', 'Settlements Console')
@section('page_title', 'Results & Settlements')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Unsettled Events</h3>
            </div>
            <div class="card-body">
                @forelse($games as $game)
                    <div class="card card-outline card-info mb-4">
                        <div class="card-header" style="background-color: rgba(23, 162, 184, 0.1);">
                            <div class="d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span class="badge badge-secondary">{{ $game->league->category->name }} &raquo; {{ $game->league->name }}</span>
                                    <h4 class="m-0 mt-1"><strong>{{ $game->title }}</strong></h4>
                                    <small class="text-muted">Commenced: {{ $game->start_time }} (UTC)</small>
                                </div>
                                <span class="badge badge-info">Game status: {{ $game->status === 4 ? 'Ended' : 'Running / Active' }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            @foreach($game->markets as $market)
                                <div class="border-bottom pb-3 mb-3" style="border-bottom: 1px solid #dee2e6; padding-bottom: 15px; margin-bottom: 15px;">
                                    <h5><strong>Market: {{ $market->market_title }}</strong> <small class="text-muted">({{ $market->market_type }})</small></h5>
                                    <div class="d-flex flex-wrap gap-2 mt-2" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                                        @foreach($market->outcomes as $outcome)
                                            <div class="outcome-pill p-2 border rounded bg-light d-flex align-items-center" style="border: 1px solid #ccc; padding: 10px; border-radius: 5px; background: #f8f9fa; display: flex; align-items: center; gap: 10px;">
                                                <span><strong>{{ $outcome->name }}</strong> (Odds: {{ number_format($outcome->odds, 2) }})</span>
                                                <form action="{{ route('liteback.sports.settlements.settle', $outcome->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-success ml-2">Declare Winner</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted p-5" style="padding: 50px; text-align: center;">
                        <i class="fas fa-check-circle text-success" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <h4>All synced sports events have been fully settled!</h4>
                        <p class="text-muted">Check back later or run odds sync commands to import new matches.</p>
                    </div>
                @endforelse
            </div>
            @if($games->hasPages())
                <div class="card-footer clearfix">
                    {{ $games->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
