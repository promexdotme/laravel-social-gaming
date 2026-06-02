@extends('liteback.layout')

@section('title', 'Categories & Leagues')
@section('page_title', 'Sports Categories / Leagues')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Add Custom Category</h3>
            </div>
            <form action="{{ route('liteback.sports.categories.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" name="name" class="form-control" id="name" placeholder="e.g. Soccer" required style="width:100%; padding:8px;">
                    </div>
                    <div class="form-group">
                        <label for="odds_api_name">Odds API Group Name</label>
                        <input type="text" name="odds_api_name" class="form-control" id="odds_api_name" placeholder="e.g. Soccer (optional)" style="width:100%; padding:8px;">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Categories & Leagues</h3>
            </div>
            <div class="card-body">
                @forelse($categories as $category)
                    <div class="card card-outline card-secondary mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center;">
                            <h5 class="m-0">
                                <strong>{{ $category->name }}</strong> 
                                <small class="text-muted">(API group: {{ $category->odds_api_name ?? 'None' }})</small>
                            </h5>
                            <form action="{{ route('liteback.sports.categories.toggle', $category->id) }}" method="POST" class="ml-auto d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $category->status ? 'btn-success' : 'btn-danger' }}">
                                    {{ $category->status ? 'Active' : 'Disabled' }}
                                </button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>League Name</th>
                                        <th>Odds API Key</th>
                                        <th>API Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($category->leagues as $league)
                                        <tr>
                                            <td>{{ $league->name }}</td>
                                            <td><code>{{ $league->odds_api_sport_key ?? 'N/A' }}</code></td>
                                            <td>
                                                <form action="{{ route('liteback.sports.leagues.toggle-api', $league->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs {{ $league->api_status ? 'btn-info' : 'btn-secondary' }}">
                                                        {{ $league->api_status ? 'Active in API' : 'Inactive in API' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                @if(!$league->status)
                                                    <form action="{{ route('liteback.sports.leagues.toggle', $league->id) }}" method="POST" class="d-inline toggle-league-form">
                                                        @csrf
                                                        <input type="hidden" name="sync" class="sync-input" value="0">
                                                        <button type="button" class="btn btn-xs btn-danger btn-toggle-league" data-league-name="{{ $league->name }}">
                                                            Disabled
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('liteback.sports.leagues.toggle', $league->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-xs btn-success">
                                                            Enabled
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No leagues synced or added for this category yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted">No categories seeded. Run migrations/seeders first.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-toggle-league').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            let form = this.closest('form');
            let leagueName = this.getAttribute('data-league-name');
            let syncInput = form.querySelector('.sync-input');
            
            if (confirm("Would you like to automatically fetch current matches and odds for '" + leagueName + "' from the Odds API now?\n\n- Click OK to enable and sync active odds immediately.\n- Click Cancel to just enable it without running a sync.")) {
                syncInput.value = "1";
            } else {
                syncInput.value = "0";
            }
            form.submit();
        });
    });
});
</script>
@endsection
