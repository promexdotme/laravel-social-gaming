@extends('backend.layouts.app')

@section('page-title', trans('app.dashboard'))
@section('page-heading', trans('app.dashboard'))

@section('content')

    <section class="content-header">
        @include('backend.partials.messages')
    </section>

    <section class="content">
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-light-blue">
                    <div class="inner">
                        <h3>{{ number_format($stats['total']) }}</h3>
                        <p>@lang('app.total_users')</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-user"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ number_format($stats['new']) }}</h3>
                        <p>@lang('app.new_users_this_month')</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ number_format($stats['banned']) }}</h3>
                        <p>@lang('app.banned_users')</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-lock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3>{{ number_format($stats['games']) }}</h3>
                        <p>@lang('app.games')</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-gamepad"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('app.latest_pay_stats')</h3>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>@lang('app.user')</th>
                                    <th>@lang('app.money_in')</th>
                                    <th>@lang('app.money_out')</th>
                                    <th>@lang('app.date')</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($statistics as $stat)
                                    <tr>
                                        <td>{{ optional($stat->payeer)->username }}</td>
                                        <td>@if(optional($stat->add)->money_in !== null)<span class="text-green">{{ $stat->add->money_in }}</span>@endif</td>
                                        <td>@if(optional($stat->add)->money_out !== null)<span class="text-red">{{ $stat->add->money_out }}</span>@endif</td>
                                        <td>{{ $stat->created_at->format(config('app.time_format')) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4">@lang('app.no_data')</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xs-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('app.latest_game_stats')</h3>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>@lang('app.game')</th>
                                    <th>@lang('app.user')</th>
                                    <th>@lang('app.balance')</th>
                                    <th>@lang('app.bet')</th>
                                    <th>@lang('app.win')</th>
                                    <th>@lang('app.date')</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($gamestat as $stat)
                                    <tr>
                                        <td>{{ $stat->game }}</td>
                                        <td>{{ optional($stat->user)->username }}</td>
                                        <td>{{ $stat->balance }}</td>
                                        <td>{{ $stat->bet }}</td>
                                        <td>{{ $stat->win }}</td>
                                        <td>{{ date(config('app.time_format'), strtotime($stat->date_time)) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6">@lang('app.no_data')</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@stop
