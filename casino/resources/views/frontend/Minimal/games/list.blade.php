@extends('frontend.Minimal.layouts.clean')

@section('page-title', $title)

@section('content')
    @php
        $providerCount = is_iterable($categories) ? count($categories) : 0;
        $gamesCount = isset($games) ? count($games) : 0;
        $heroBase = base_path('../minimal/hero');
        $heroVideo = file_exists($heroBase . '/hero.mp4') ? '/minimal/hero/hero.mp4' : null;
        $heroDesktop = file_exists($heroBase . '/hero-desktop.jpg') ? '/minimal/hero/hero-desktop.jpg' : null;
        $heroMobile = file_exists($heroBase . '/hero-mobile.jpg') ? '/minimal/hero/hero-mobile.jpg' : $heroDesktop;
        $showHero = false; // toggle to true to show hero media
    @endphp

    <div class="page-shell">
        @if($showHero)
            <section class="hero-media container">
                <div class="media-frame">
                    @if($heroVideo)
                        <video class="hero-video" autoplay muted loop playsinline poster="{{ $heroDesktop ?? $heroMobile }}">
                            <source src="{{ $heroVideo }}" type="video/mp4">
                        </video>
                    @elseif($heroDesktop || $heroMobile)
                        <picture>
                            @if($heroMobile)
                                <source media="(max-width: 640px)" srcset="{{ $heroMobile }}">
                            @endif
                            <img src="{{ $heroDesktop ?? $heroMobile }}" alt="Hero feature" class="hero-image">
                        </picture>
                    @else
                        <div class="media-placeholder">
                            <span class="pill subtle">Hero placeholder</span>
                            <p>Drop a video at <code>/minimal/hero/hero.mp4</code> or images at <code>/minimal/hero/hero-desktop.jpg</code> and <code>/minimal/hero/hero-mobile.jpg</code>.</p>
                        </div>
                    @endif
                    <div class="media-overlay">
                        <span class="badge">Featured</span>
                        <div class="overlay-copy">
                            <p class="overlay-title">Customizable hero</p>
                            <p class="overlay-sub">Swap media files in <code>/minimal/hero/</code> for desktop/mobile or MP4 video.</p>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="toolbar-panel container" id="providers">
            <div class="toolbar-header">
                <div>
                    <p class="eyebrow">Providers</p>
                    <h3>Browse {{ $gamesCount }} games across {{ $providerCount }} providers</h3>
                </div>
                <label class="search-wrapper toolbar-search">
                    <span class="search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="presentation">
                            <path fill="currentColor"
                                d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 0 0 1.57-5.34A6.5 6.5 0 1 0 9 15.5c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l4.75 4.74 1.49-1.49L15.5 14Zm-6.5 0a4.5 4.5 0 1 1 .01-9.01A4.5 4.5 0 0 1 9 14Z" />
                        </svg>
                    </span>
                    <input type="text" id="game-search" class="search-input" placeholder="Search games or providers">
                </label>
            </div>

            <div class="category-scroller">
                <a href="{{ route('frontend.game.list.category', 'all') }}" class="category-pill {{ $category1 == 'all' ? 'active' : '' }}">All</a>
                @foreach($categories as $cat)
                    @if(is_object($cat))
                        <a href="{{ route('frontend.game.list.category', $cat->href) }}" class="category-pill {{ $category1 == $cat->href ? 'active' : '' }}">{{ $cat->title }}</a>
                    @endif
                @endforeach
            </div>
        </section>

        <section class="games-section container">
            <div class="section-header">
                <div>
                    <p class="eyebrow">Lobby</p>
                    <h3>{{ $title }}</h3>
                </div>
                <span class="muted">Showing {{ $gamesCount }} games</span>
            </div>

            @if(count($games) > 0)
                <div class="games-grid">
                    @foreach($games as $game)
                        <div class="game-card" data-title="{{ $game->title }}">
                            <div class="game-media">
                                <img src="/frontend/Default/ico/{{ $game->name }}.jpg" alt="{{ $game->title }}" class="game-image" loading="lazy">
                                <div class="game-overlay">
                                    <div class="overlay-meta">
                                        <span class="pill subtle">{{ $game->label ?? 'Slots' }}</span>
                                        <span class="game-title">{{ $game->title }}</span>
                                    </div>
                                    <div class="game-actions">
                                        @if(Auth::check())
                                            <a href="{{ route('frontend.game.go', $game->name) }}" class="play-btn">Play now</a>
                                        @else
                                            <a href="#" class="play-btn open-modal" data-target="modal-login">Login to play</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="game-info">
                                <div class="game-name">{{ $game->title }}</div>
                                <div class="game-sub">Instant play | HD ready</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-games">
                    <h4>No games found in this category.</h4>
                    <p class="muted">Try a different provider or clear the search.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
