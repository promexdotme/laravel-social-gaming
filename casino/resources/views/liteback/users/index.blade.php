@extends('liteback.layout')

@section('title', 'Liteback - Users')
@section('page_title', 'Users')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Add User</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('liteback.users.store') }}" class="form-inline">
                @csrf
                <div class="form-group mr-2 mb-2">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="email" name="email" class="form-control" placeholder="Email (optional)">
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="number" step="0.01" min="0" name="balance" class="form-control" placeholder="Start balance" value="0">
                </div>
                <button type="submit" class="btn btn-success mb-2"><i class="fas fa-user-plus"></i> Create</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="form-inline" method="get" action="{{ route('liteback.users.index') }}">
                <div class="form-group mr-2 mb-2">
                    <input type="text" name="q" class="form-control" placeholder="Search username" value="{{ $term }}">
                </div>
                <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Username</th>
                        <th style="width: 140px;">Balance</th>
                        <th style="width: 320px;">Adjust</th>
                        <th style="width: 90px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ number_format($user->balance ?? 0, 2) }}</td>
                            <td>
                                <form method="post" action="{{ route('liteback.users.balance', $user->id) }}" class="form-inline">
                                    @csrf
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm mr-2" placeholder="Amount" required>
                                    <input type="text" name="note" class="form-control form-control-sm mr-2" placeholder="Note (optional)">
                                    <input type="hidden" name="direction" value="add">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="submit" class="btn btn-success" onclick="this.form.direction.value='add'"><i class="fas fa-plus"></i> Add</button>
                                        <button type="submit" class="btn btn-danger" onclick="this.form.direction.value='deduct'"><i class="fas fa-minus"></i> Deduct</button>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="{{ route('liteback.users.delete', $user->id) }}" onsubmit="return confirm('Delete this user and related records?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No users found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $users->links() }}
        </div>
    </div>
@endsection
