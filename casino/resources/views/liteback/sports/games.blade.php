@extends('liteback.layout')

@section('title', 'Events & Odds')
@section('page_title', 'Sports Events & Odds')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Add Manual Game</h3>
            </div>
            <form action="{{ route('liteback.sports.games.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="league_id">Select League</label>
                        <select name="league_id" class="form-control" required style="width:100%; padding:8px;">
                            @foreach($leagues as $lg)
                                <option value="{{ $lg->id }}">{{ $lg->category->name }} &raquo; {{ $lg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="title">Event Title</label>
                        <input type="text" name="title" class="form-control" id="title" placeholder="e.g. Arsenal vs Chelsea" required style="width:100%; padding:8px;">
                    </div>
                    <div class="form-group">
                        <label for="team_one">Home Team (Team 1)</label>
                        <input type="text" name="team_one" class="form-control" id="team_one" placeholder="e.g. Arsenal" required style="width:100%; padding:8px;">
                    </div>
                    <div class="form-group">
                        <label for="team_two">Away Team (Team 2)</label>
                        <input type="text" name="team_two" class="form-control" id="team_two" placeholder="e.g. Chelsea" required style="width:100%; padding:8px;">
                    </div>
                    <div class="form-group">
                        <label for="start_time">Start Time (Commence Time)</label>
                        <input type="datetime-local" name="start_time" class="form-control" id="start_time" required style="width:100%; padding:8px;">
                    </div>
                    <div class="form-group">
                        <label for="bet_start_time">Bet Window Opens At</label>
                        <input type="datetime-local" name="bet_start_time" class="form-control" id="bet_start_time" required style="width:100%; padding:8px;">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Events List</h3>
                <form action="{{ route('liteback.sports.games') }}" method="GET" class="card-tools ml-auto">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="q" class="form-control float-right" placeholder="Search events..." value="{{ $term ?? '' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-valign-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category / League</th>
                            <th>Event Details</th>
                            <th>Commence Time</th>
                            <th>Bet Start Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($games as $game)
                            <tr>
                                <td>
                                    <small>{{ $game->league->category->name }}</small><br>
                                    <strong>{{ $game->league->name }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $game->title }}</strong><br>
                                    @if($game->manually_added)
                                        <span class="badge badge-warning">Manual</span>
                                    @else
                                        <span class="badge badge-info">Odds API</span>
                                    @endif
                                </td>
                                <td><small>{{ $game->start_time }}</small></td>
                                <td><small>{{ $game->bet_start_time }}</small></td>
                                <td>
                                    @if($game->status === 1)
                                        <span class="badge badge-success">Open for Betting</span>
                                    @elseif($game->status === 0)
                                        <span class="badge badge-secondary">Not Open Yet</span>
                                    @elseif($game->status === 3)
                                        <span class="badge badge-dark">Closed</span>
                                    @elseif($game->status === 4)
                                        <span class="badge badge-info">Ended</span>
                                    @elseif($game->status === 2)
                                        <span class="badge badge-danger">Cancelled</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('liteback.sports.games.toggle', $game->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs {{ $game->status === 1 ? 'btn-danger' : 'btn-success' }}">
                                            {{ $game->status === 1 ? 'Close Betting' : 'Open Betting' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No games found. Check API key and run sync commands.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
