@extends('liteback.layout')

@section('title', 'Manual Deposits Queue')
@section('page_title', 'Manual Deposits Queue')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pending & Past Manual Bank Transfers</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Holder Name</th>
                        <th>Reference / Txn ID</th>
                        <th>Screenshot</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                        <th>Action / Admin Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $deposit)
                        <tr>
                            <td>{{ $deposit->id }}</td>
                            <td>
                                <strong>{{ $deposit->username }}</strong><br>
                                <span class="text-muted text-sm">{{ $deposit->email }}</span>
                            </td>
                            <td>
                                <strong class="text-success">{{ number_format($deposit->amount, 2) }} {{ $deposit->currency }}</strong>
                            </td>
                            <td>{{ $deposit->account_name }}</td>
                            <td><code>{{ $deposit->transaction_id }}</code></td>
                            <td>
                                @if($deposit->screenshot)
                                    <button class="btn btn-xs btn-outline-primary view-screenshot-btn" 
                                            data-src="{{ asset($deposit->screenshot) }}" 
                                            data-title="Receipt from {{ $deposit->account_name }} ({{ number_format($deposit->amount, 2) }} {{ $deposit->currency }})"
                                            data-toggle="modal" 
                                            data-target="#screenshotModal">
                                        <i class="fas fa-image mr-1"></i> View Receipt
                                    </button>
                                @else
                                    <span class="text-muted">No receipt file</span>
                                @endif
                            </td>
                            <td>
                                @if($deposit->status == 0)
                                    <span class="badge badge-warning"><i class="fas fa-spinner fa-spin mr-1"></i> Pending</span>
                                @elseif($deposit->status == 1)
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Approved</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i> Rejected</span>
                                @endif
                            </td>
                            <td>{{ $deposit->created_at }}</td>
                            <td>
                                @if($deposit->status == 0)
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('liteback.payments.manual.approve', $deposit->id) }}" method="POST" class="mr-2" onsubmit="return confirm('Are you sure you want to approve this deposit and credit the user balance?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <button class="btn btn-sm btn-danger reject-deposit-btn" 
                                                data-id="{{ $deposit->id }}" 
                                                data-action="{{ route('liteback.payments.manual.reject', $deposit->id) }}"
                                                data-toggle="modal" 
                                                data-target="#rejectModal">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted">{{ $deposit->admin_note ?? 'No notes' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <span class="text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i> No manual deposits found in history.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($deposits->hasPages())
        <div class="card-footer clearfix">
            {{ $deposits->links() }}
        </div>
    @endif
</div>

<!-- Screenshot Modal -->
<div class="modal fade" id="screenshotModal" tabindex="-1" role="dialog" aria-labelledby="screenshotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="screenshotModalTitle">Receipt Proof</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center bg-dark">
                <img id="modalScreenshotImg" src="" class="img-fluid" style="max-height: 70vh;" alt="Receipt Screenshot">
            </div>
            <div class="modal-footer">
                <a id="modalScreenshotDownload" href="" download class="btn btn-primary" target="_blank"><i class="fas fa-download mr-1"></i> Download Original</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="rejectForm" action="" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Deposit Request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="admin_note">Reason for Rejection / Internal Note</label>
                        <textarea name="admin_note" id="admin_note" class="form-control" rows="4" placeholder="Enter reason (e.g. Invalid reference number, screenshot is unreadable, funds not received)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Handle viewing screenshots
        $('.view-screenshot-btn').on('click', function() {
            var src = $(this).data('src');
            var title = $(this).data('title');
            $('#modalScreenshotImg').attr('src', src);
            $('#modalScreenshotDownload').attr('href', src);
            $('#screenshotModalTitle').text(title);
        });

        // Handle reject button data-fill
        $('.reject-deposit-btn').on('click', function() {
            var actionUrl = $(this).data('action');
            $('#rejectForm').attr('action', actionUrl);
            $('#admin_note').val('');
        });
    });
</script>
@endsection
