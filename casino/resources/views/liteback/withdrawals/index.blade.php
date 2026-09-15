@extends('liteback.layout')

@section('title', 'Player Cashout & Redemption Queue')
@section('page_title', 'Player Cashout & Redemption Queue')

@section('content')
<div class="row mb-3">
    <!-- Pending KPI -->
    <div class="col-md-4">
        <div class="card bg-warning text-dark shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase small font-weight-bold opacity-75">Pending Payouts</div>
                        <div class="h3 font-weight-bold mb-0">{{ $pendingCount }} Requests</div>
                        <div class="small font-weight-bold">{{ number_format($pendingCoins) }} Pts (~${{ number_format($pendingUsd, 2) }} USD)</div>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Approved KPI -->
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase small font-weight-bold opacity-75">Total Paid Out</div>
                        <div class="h3 font-weight-bold mb-0">${{ number_format($approvedUsd, 2) }} USD</div>
                        <div class="small">{{ $approvedCount }} Fulfilled Redemptions</div>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Exchange Rate KPI -->
    <div class="col-md-4">
        <div class="card bg-info text-white shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase small font-weight-bold opacity-75">Active Rate</div>
                        <div class="h3 font-weight-bold mb-0">{{ number_format($rate) }} Pts = $1.00</div>
                        <div class="small">Each cent = 1 point</div>
                    </div>
                    <i class="fas fa-coins fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <!-- Status Filter Tabs -->
        <div class="btn-group btn-group-sm mb-2 mb-md-0">
            <a href="{{ route('liteback.withdrawals.index', ['status' => 'pending']) }}" class="btn btn-{{ $statusFilter === 'pending' ? 'warning font-weight-bold' : 'outline-secondary' }}">
                Pending ({{ $pendingCount }})
            </a>
            <a href="{{ route('liteback.withdrawals.index', ['status' => 'approved']) }}" class="btn btn-{{ $statusFilter === 'approved' ? 'success font-weight-bold' : 'outline-secondary' }}">
                Approved
            </a>
            <a href="{{ route('liteback.withdrawals.index', ['status' => 'rejected']) }}" class="btn btn-{{ $statusFilter === 'rejected' ? 'danger font-weight-bold' : 'outline-secondary' }}">
                Rejected
            </a>
            <a href="{{ route('liteback.withdrawals.index', ['status' => 'all']) }}" class="btn btn-{{ $statusFilter === 'all' ? 'primary font-weight-bold' : 'outline-secondary' }}">
                All Requests
            </a>
        </div>

        <!-- Search Form -->
        <form action="{{ route('liteback.withdrawals.index') }}" method="GET" class="form-inline">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Search user or wallet..." value="{{ $search }}">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Player</th>
                        <th>Amount (Points)</th>
                        <th>Cash Worth (USD)</th>
                        <th>Method</th>
                        <th>Destination Account / Wallet</th>
                        <th>Status</th>
                        <th>Requested At</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $w)
                        <tr>
                            <td>#{{ $w->id }}</td>
                            <td>
                                @if($w->user)
                                    <strong class="text-dark">{{ $w->user->username }}</strong><br>
                                    <small class="text-muted"><i class="fas fa-phone-alt mr-1"></i>{{ $w->user->phone ?? 'No phone' }}</small>
                                    @if($w->user->email)
                                        <br><small class="text-muted"><i class="fas fa-envelope mr-1"></i>{{ $w->user->email }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">User #{{ $w->user_id }} (Deleted)</span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-primary font-weight-bold">{{ number_format($w->coin_amount ?: $w->amount) }}</strong> Pts
                            </td>
                            <td>
                                <strong class="text-success font-weight-bold">
                                    ${{ number_format($w->fiat_amount ?: (($w->coin_amount ?: $w->amount) / ($rate ?: 100)), 2) }}
                                </strong>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $w->method ?: 'Manual' }}</span>
                            </td>
                            <td>
                                <code class="p-1 rounded bg-light border text-dark font-weight-bold" style="font-size: 11px; word-break: break-all;">
                                    {{ $w->wallet }}
                                </code>
                                @if($w->admin_note)
                                    <div class="mt-1 small text-muted">
                                        <i class="fas fa-info-circle mr-1"></i> {{ $w->admin_note }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($w->status == 0)
                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> Pending</span>
                                @elseif($w->status == 1)
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Approved</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i> Rejected</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $w->created_at ? date('M d, Y H:i', strtotime($w->created_at)) : '-' }}</small>
                            </td>
                            <td>
                                @if($w->status == 0)
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-success btn-sm btn-approve" 
                                                data-id="{{ $w->id }}"
                                                data-user="{{ $w->user->username ?? 'User' }}"
                                                data-amount="${{ number_format($w->fiat_amount ?: (($w->coin_amount ?: $w->amount) / ($rate ?: 100)), 2) }}"
                                                data-wallet="{{ $w->wallet }}"
                                                data-method="{{ $w->method ?: 'Manual' }}">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm btn-reject" 
                                                data-id="{{ $w->id }}"
                                                data-user="{{ $w->user->username ?? 'User' }}"
                                                data-coins="{{ number_format($w->coin_amount ?: $w->amount) }}">
                                            <i class="fas fa-undo mr-1"></i> Reject
                                        </button>
                                    </div>
                                @else
                                    <span class="small text-muted font-italic">Resolved</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-2 d-block opacity-50"></i>
                                No withdrawal requests found matching this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($withdrawals->hasPages())
    <div class="card-footer bg-white py-2">
        <div class="d-flex justify-content-between align-items-center">
            <span class="small text-muted">Showing {{ $withdrawals->firstItem() }} to {{ $withdrawals->lastItem() }} of {{ $withdrawals->total() }}</span>
            {{ $withdrawals->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Modal: Approve Cashout -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="approveForm" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Approve & Fulfill Cashout</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to mark this withdrawal as paid to <strong id="approveModalUser"></strong>?</p>
                    <div class="alert alert-light border p-2 mb-3">
                        <div><strong>Amount:</strong> <span id="approveModalAmount" class="text-success font-weight-bold"></span></div>
                        <div><strong>Method:</strong> <span id="approveModalMethod" class="badge badge-info"></span></div>
                        <div><strong>Destination:</strong> <code id="approveModalWallet" class="text-dark font-weight-bold"></code></div>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Transaction Reference / TXID (Optional)</label>
                        <input type="text" name="txid" class="form-control form-control-sm" placeholder="e.g. 0x8f... or receipt number">
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Admin Note (Optional)</label>
                        <input type="text" name="admin_note" class="form-control form-control-sm" placeholder="Sent via Whish agent / Binance TRC20">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold">Confirm & Mark Paid</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reject & Refund Cashout -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-undo mr-1"></i> Reject & Refund Player Coins</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning p-2 small mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Rejecting will immediately <strong>refund <span id="rejectModalCoins"></span> coins</strong> back to <strong id="rejectModalUser"></strong>'s balance!
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Reason for Rejection (Required)</label>
                        <textarea name="admin_note" class="form-control form-control-sm" rows="3" required placeholder="e.g. Invalid USDT TRC20 address, minimum deposit turnover requirement not met, duplicate request."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm font-weight-bold">Reject & Refund Balance</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Approve Modal triggers
    document.querySelectorAll('.btn-approve').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            document.getElementById('approveModalUser').innerText = this.getAttribute('data-user');
            document.getElementById('approveModalAmount').innerText = this.getAttribute('data-amount');
            document.getElementById('approveModalMethod').innerText = this.getAttribute('data-method');
            document.getElementById('approveModalWallet').innerText = this.getAttribute('data-wallet');
            document.getElementById('approveForm').action = '/liteback/withdrawals/' + id + '/approve';
            $('#approveModal').modal('show');
        });
    });

    // Reject Modal triggers
    document.querySelectorAll('.btn-reject').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            document.getElementById('rejectModalUser').innerText = this.getAttribute('data-user');
            document.getElementById('rejectModalCoins').innerText = this.getAttribute('data-coins');
            document.getElementById('rejectForm').action = '/liteback/withdrawals/' + id + '/reject';
            $('#rejectModal').modal('show');
        });
    });
});
</script>
@endsection
