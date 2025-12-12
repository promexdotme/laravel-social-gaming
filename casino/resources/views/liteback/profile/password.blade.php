@extends('liteback.layout')

@section('title', 'Change Password')
@section('page_title', 'Change Password')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('liteback.profile.password.update') }}">
                @csrf
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
@endsection
