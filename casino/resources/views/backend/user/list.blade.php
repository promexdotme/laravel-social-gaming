@extends('backend.layouts.app')

@section('page-title', trans('app.users'))
@section('page-heading', trans('app.users'))

@section('content')

	<section class="content-header">
		@include('backend.partials.messages')
	</section>



	<section class="content">

			<form action="" method="GET" id="users-form" >
			<div class="box box-danger collapsed-box users_show">

					<div class="box-header with-border">
						<h3 class="box-title">@lang('app.filter')</h3>
						<div class="box-tools pull-right">
							<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
						</div>
					</div>
					<div class="box-body">
						<div class="row">
							<div class="col-md-4">
								<label>@lang('app.search')</label>
								<input type="text" class="form-control" name="search" value="{{ Request::get('search') }}" placeholder="@lang('app.search_for_users')">
							</div>
							<div class="col-md-4">
								<label>@lang('app.status')</label>
								{!! Form::select('status', $statuses, Request::get('status'), ['id' => 'status', 'class' => 'form-control']) !!}
							</div>
							<div class="col-md-4">
								<label>@lang('app.role')</label>
								{!! Form::select('role', $roles, Request::get('role'), ['id' => 'role', 'class' => 'form-control']) !!}
							</div>
						</div>
					</div>
					<div class="box-footer">
						<button type="submit" class="btn btn-primary">
							@lang('app.filter')
						</button>
					</div>

			</div>
			</form>


			<div class="box box-primary">
				<div class="box-header with-border">
					<h3 class="box-title">@lang('app.users')</h3>
					<div class="pull-right box-tools">
                        @if(auth()->user()->hasRole('admin'))
					        <a href="{{ route('backend.user.create') }}" class="btn btn-block btn-primary btn-sm">@lang('app.add')</a>
                        @endif
					</div>
				</div>
				<div class="box-body">
					<div class="table-responsive">
						<table class="table table-bordered table-striped">
							<thead>
							<tr>
								<th>@lang('app.username')</th>
								@if(auth()->user()->hasRole('admin'))
								<th>Log In</th>
							    @endif

								<th>@lang('app.balance')</th>

								<th>@lang('app.rating')</th>
								<th>@lang('app.pb')</th>
								<th>@lang('app.de')</th>
								<th>@lang('app.if')</th>
								<th>@lang('app.hh')</th>
								<th>@lang('app.wb')</th>
								<th>@lang('app.sb')</th>
								<th>@lang('app.refund')</th>
								<th>@lang('app.add')</th>
								<th>@lang('app.out')</th>
	                             
							</tr>
							</thead>
							<tbody>
							@if (count($users))
								@foreach ($users as $user)
									@include('backend.user.partials.row')
								@endforeach
							@else
								<tr><td colspan="{{ auth()->user()->hasRole('admin') ? 13 : 12 }}">@lang('app.no_data')</td></tr>
							@endif
							</tbody>
							<thead>
							<tr>
								<th>@lang('app.username')</th>
								@if(auth()->user()->hasRole('admin'))
								<th>Log In</th>
							    @endif

								<th>@lang('app.balance')</th>

								<th>@lang('app.rating')</th>
								<th>@lang('app.pb')</th>
								<th>@lang('app.de')</th>
								<th>@lang('app.if')</th>
								<th>@lang('app.hh')</th>
								<th>@lang('app.wb')</th>
								<th>@lang('app.sb')</th>
								<th>@lang('app.refund')</th>
								<th>@lang('app.add')</th>
								<th>@lang('app.out')</th>



							</tr>
							</thead>
						</table>
					</div>
					{{ $users->appends(Request::except('page'))->links() }}
				</div>
			</div>
	</section>

		@include('backend.user.partials.modals')

@stop

@section('scripts')
	<script>

		$(function() {

			$('.btn-box-tool').click(function(event){
				if( $('.users_show').hasClass('collapsed-box') ){
					$.cookie('users_show', '1');
				} else {
					$.removeCookie('users_show');
				}
			});

			if( $.cookie('users_show') ){
				$('.users_show').removeClass('collapsed-box');
				$('.users_show .btn-box-tool i').removeClass('fa-plus').addClass('fa-minus');
			}

			$("#view").change(function () {
				$("#shops-form").submit();
			});

			$("#filter").detach().appendTo("div.toolbar");


			$("#status").change(function () {
				$("#users-form").submit();
			});
			$("#role").change(function () {
				$("#users-form").submit();
			});
			$('.addPayment').click(function(event){
				if( $(event.target).is('.newPayment') ){
					var id = $(event.target).attr('data-id');
				}else{
					var id = $(event.target).parents('.newPayment').attr('data-id');
				}
				$('#AddId').val(id);

			});

			$('.outPayment').click(function(event){
				if( $(event.target).is('.newPayment') ){
					var id = $(event.target).attr('data-id');
				}else{
					var id = $(event.target).parents('.newPayment').attr('data-id');
				}
				$('#OutId').val(id);
				$('#outAll').val('');
			});

			$('#doOutAll').click(function () {
				$('#outAll').val('1');
				$('form#outForm').submit();
			});




			setInterval(function() {
				$.getJSON(' {{ route('backend.user.balance.get') }} ', function(data) {
					for (var key in data) {
						$('.balance_' + key).html(data[key].balance);
						$('.bonus_' + key).html(data[key].bonus);
						$('.wager_' + key).html(data[key].wager);
					};
				});
			}, 3000);


		});

	</script>
@stop
