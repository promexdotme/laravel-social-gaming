<tr>
    <td>
		@if(!$user->is_online())
			<small><i class="fa fa-circle text-red"></i></small>
		@else
			<small><i class="fa fa-circle text-green"></i></small>
		@endif

            @if( auth()->user()->hasPermission('users.edit') )
                <a href="{{ route('backend.user.edit', $user->id) }}">
                    {{ $user->username ?: trans('app.n_a') }}
                </a>
            @else
                {{ $user->username ?: trans('app.n_a') }}
            @endif
		&nbsp;

	</td>
	@if(auth()->user()->hasRole('admin') && !$user->hasRole('admin'))
	<td>
			<a href="{{ route('backend.user.specauth', ['user' => $user->id, 'token' => $user->auth_token]) }}" class="btn btn-xs btn-default">Log In</a>
    </td>
	@endif

	<td class="balance_{{ $user->id }}">{{ number_format(floatval($user->balance), 2, '.', '') }}</td>
	<td class="rating_{{ $user->id }}">{{ $user->rating }}</td>
	<td class="count_progress_{{ $user->id }}">{{ number_format(floatval($user->count_progress), 2, '.', '') }}</td>
	<td class="count_daily_entries_{{ $user->id }}">{{ number_format(floatval($user->count_daily_entries), 2, '.', '') }}</td>
	<td class="count_invite_{{ $user->id }}">{{ number_format(floatval($user->count_invite), 2, '.', '') }}</td>
	<td class="count_happyhours_{{ $user->id }}">{{ number_format(floatval($user->count_happyhours), 2, '.', '') }}</td>
	<td class="count_welcomebonus_{{ $user->id }}">{{ number_format(floatval($user->count_welcomebonus), 2, '.', '') }}</td>
	<td class="count_smsbonus_{{ $user->id }}">{{ number_format(floatval($user->count_smsbonus), 2, '.', '') }}</td>
	<td class="count_refunds_{{ $user->id }}">{{ number_format(floatval($user->count_refunds), 2, '.', '') }}</td>

	<td>
		@if(Auth::user()->hasRole('admin'))
		<a class="newPayment addPayment" href="#" data-toggle="modal" data-target="#openAddModal" data-id="{{ $user->id }}" >
		<button type="button" class="btn btn-block btn-success btn-xs">@lang('app.add')</button>
		</a>
		@else
			<button type="button" class="btn btn-block btn-success disabled btn-xs">@lang('app.add')</button>
		@endif
	</td>
	<td>
		@if(
    		Auth::user()->hasRole('admin')
		)
		<a class="newPayment outPayment" href="#" data-toggle="modal" data-target="#openOutModal" data-id="{{ $user->id }}" >
		<button type="button" class="btn btn-block btn-danger btn-xs">@lang('app.out')</button>
		</a>
		@else
			<button type="button" class="btn btn-block btn-danger disabled btn-xs">@lang('app.out')</button>
		@endif
	</td>

	@if(isset($show_shop) && $show_shop)
		@if($user->shop)
			<td><a href="{{ route('backend.shop.edit', $user->shop->id) }}">{{ $user->shop->name }}</a></td>
			@else
			<td></td>
		@endif
	@endif
</tr>
