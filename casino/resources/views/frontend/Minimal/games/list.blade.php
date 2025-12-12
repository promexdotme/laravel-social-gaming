@extends('frontend.Minimal.layouts.clean')

@section('page-title', $title)

@section('content')
    <div class="container">
        <div class="games-toolbar">
            <nav class="category-list">
                <a href="{{ route('frontend.game.list.category', 'all') }}"
                    class="category-pill {{ $category1 == 'all' ? 'active' : '' }}">All</a>
                @foreach($categories as $cat)
                    @if(is_object($cat))
                        <a href="{{ route('frontend.game.list.category', $cat->href) }}"
                            class="category-pill {{ $category1 == $cat->href ? 'active' : '' }}">{{ $cat->title }}</a>
                    @endif
                @endforeach
            </nav>

            <div class="search-wrapper">
                <input type="text" id="game-search" class="search-input" placeholder="Search games...">
            </div>
        </div>

        <div class="games-grid">
            @if(count($games) > 0)
                @foreach($games as $game)
                    <div class="game-card" data-title="{{ $game->title }}">
                        <img src="/frontend/Default/ico/{{ $game->name }}.jpg" alt="{{ $game->title }}" class="game-image"
                            loading="lazy">
                        <div class="game-overlay">
                            @if(Auth::check())
                                <a href="{{ route('frontend.game.go', $game->name) }}" class="play-btn">Play Now</a>
                            @else
                                <a href="#" class="play-btn open-modal" data-target="modal-login">Login to Play</a>
                            @endif
                            <div class="game-title">{{ $game->title }}</div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="no-games">
                    <p>No games found in this category.</p>
                </div>
            @endif
        </div>
    </div>
@endsection