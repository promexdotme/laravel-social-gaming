<aside class="main-sidebar">
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left image">
                <img src="/back/img/12.png" class="img-circle">
            </div>
            <div class="pull-left info">
                <p>Balance: <span class="activeBalance">{{ number_format(auth()->user()->balance, 2, '.', '') }}</span></p>
                <span><i class="fa fa-circle text-success"></i> @lang('app.active')</span>
            </div>
        </div>

        @if( auth()->user()->hasRole('admin') )
            <form action="{{ route('backend.search') }}" method="get" class="sidebar-form">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="@lang('app.search')">
                    <span class="input-group-btn">
                        <button type="submit" name="search" id="search-btn" class="btn btn-flat">
                            <i class="fa fa-search"></i>
                        </button>
                    </span>
                </div>
            </form>
        @endif

        <ul class="sidebar-menu" data-widget="tree">
            <li class="header">@lang('app.main_navigation')</li>
            <li class="{{ Request::is('backend') ? 'active' : '' }}">
                <a href="{{ route('backend.dashboard') }}">
                    <i class="fa fa-home"></i>
                    <span>@lang('app.dashboard')</span>
                </a>
            </li>
            <li class="{{ Request::is('backend/user*') ? 'active' : '' }}">
                <a href="{{ route('backend.user.list') }}">
                    <i class="fa fa-user"></i>
                    <span>@lang('app.users')</span>
                </a>
            </li>
            <li class="{{ Request::is('backend/withdraw*') ? 'active' : '' }}">
                <a href="{{ url('/backend/withdraw') }}">
                    <i class="fa fa-money"></i>
                    <span>@lang('app.pyour_withdraw')</span>
                </a>
            </li>
        </ul>
    </section>
</aside>
