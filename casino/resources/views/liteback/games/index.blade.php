@extends('liteback.layout')

@section('title', 'Liteback - Games')
@section('page_title', 'Games')

@section('content')
    <div class="card">
        <div class="card-header">
            <form class="form-inline" method="get" action="{{ $inactive ? route('liteback.games.inactive') : route('liteback.games.index') }}">
                <div class="form-group mr-2 mb-2">
                    <input type="text" name="q" class="form-control" placeholder="Search title or name" value="{{ $term }}">
                </div>
                <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> Search</button>
                <div class="ml-auto">
                    @if($inactive)
                        <a href="{{ route('liteback.games.index') }}" class="btn btn-sm btn-outline-secondary mb-2">View Active</a>
                    @else
                        <a href="{{ route('liteback.games.inactive') }}" class="btn btn-sm btn-outline-secondary mb-2">View Inactive</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th style="width: 80px;">Icon</th>
                        <th>Title</th>
                        <th>Name</th>
                        <th style="width: 120px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($games as $game)
                        <tr>
                            <td>{{ $game->id }}</td>
                            <td>
                                <img src="{{ asset('frontend/Default/ico/' . $game->name . '.jpg') }}" alt="{{ $game->name }}" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                            </td>
                            <td>{{ $game->title }}</td>
                            <td>{{ $game->name }}</td>
                            <td>
                                @if($inactive)
                                    <form method="post" action="{{ route('liteback.games.activate', $game->id) }}" onsubmit="return confirm('Reactivate this game?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                                    </form>
                                @else
                                    <form method="post" action="{{ route('liteback.games.deactivate', $game->id) }}" onsubmit="return confirm('Move this game to inactive?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">In-activate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No games found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $games->links() }}
        </div>
    </div>
@endsection
