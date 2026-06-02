@extends('frontend.Minimal.layouts.clean')

@section('page-title', 'Manual Deposit')

@section('styles')
<style>
    .manual-payment-wrapper {
        max-width: 650px;
        margin: 40px auto;
        padding: 0 15px;
    }
    .payment-card {
        background: var(--panel-2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 30px;
        position: relative;
        overflow: hidden;
    }
    .payment-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--accent) 0%, var(--accent-contrast) 100%);
    }
    .payment-header {
        margin-bottom: 25px;
        text-align: center;
    }
    .payment-header h2 {
        font-family: var(--font);
        font-weight: 700;
        font-size: 24px;
        margin: 0 0 8px;
        letter-spacing: -0.5px;
    }
    .payment-amount-badge {
        display: inline-block;
        background: rgba(236, 19, 128, 0.1);
        border: 1px solid rgba(236, 19, 128, 0.2);
        color: var(--accent);
        font-weight: 700;
        font-size: 20px;
        padding: 6px 16px;
        border-radius: 30px;
        margin-top: 5px;
        font-family: var(--font);
    }
    .instruction-box {
        background: var(--panel-3);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 20px;
        margin-bottom: 25px;
        white-space: pre-line;
        font-size: 15px;
        color: var(--muted);
        line-height: 1.6;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text);
    }
    .form-control {
        width: 100%;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 12px 16px;
        color: var(--text);
        font-family: var(--font);
        font-size: 15px;
        transition: all 0.2s;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--accent);
        background: rgba(255, 255, 255, 0.05);
        box-shadow: 0 0 10px rgba(236, 19, 128, 0.15);
    }
    .file-upload-wrapper {
        position: relative;
        border: 2px dashed var(--border);
        border-radius: var(--radius-sm);
        padding: 24px;
        text-align: center;
        background: rgba(255, 255, 255, 0.01);
        cursor: pointer;
        transition: all 0.2s;
    }
    .file-upload-wrapper:hover {
        border-color: var(--accent);
        background: rgba(236, 19, 128, 0.02);
    }
    .file-upload-wrapper input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    .file-upload-icon {
        font-size: 32px;
        color: var(--muted);
        margin-bottom: 8px;
        display: flex;
        justify-content: center;
    }
    .file-upload-text {
        font-size: 14px;
        color: var(--muted);
    }
    .file-upload-name {
        margin-top: 8px;
        font-size: 14px;
        color: var(--accent-contrast);
        font-weight: 600;
        display: none;
    }
    .submit-btn {
        width: 100%;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%);
        border: none;
        color: #0a0512;
        padding: 14px;
        border-radius: var(--radius-sm);
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: var(--glow);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 45px rgba(236, 19, 128, 0.35);
    }
    .submit-btn:active {
        transform: translateY(0);
    }
    .cancel-link {
        display: block;
        text-align: center;
        margin-top: 15px;
        font-size: 14px;
        color: var(--muted);
        transition: color 0.2s;
    }
    .cancel-link:hover {
        color: var(--text);
    }
</style>
@endsection

@section('content')
<div class="manual-payment-wrapper">
    <div class="payment-card">
        <div class="payment-header">
            <h2>Manual Transfer Proof</h2>
            <p class="text-muted">Follow instructions below to complete your deposit.</p>
            <div class="payment-amount-badge">
                {{ number_format($intent->amount, 2) }} {{ $intent->currency }}
            </div>
        </div>

        <div class="instruction-box">
            <strong>Payment Instructions:</strong><br>
            {{ $instructions }}
        </div>

        <form action="{{ route('payment.manual.submit', $intent->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="account_name">Sender Account Name / Account Holder</label>
                <input type="text" name="account_name" id="account_name" class="form-control" placeholder="e.g. John Doe" required value="{{ old('account_name') }}">
            </div>

            <div class="form-group">
                <label for="transaction_id">Transaction Reference ID / Receipt number</label>
                <input type="text" name="transaction_id" id="transaction_id" class="form-control" placeholder="e.g. TXN987654321" required value="{{ old('transaction_id') }}">
            </div>

            <div class="form-group">
                <label>Screenshot of Receipt / Payment Proof</label>
                <div class="file-upload-wrapper">
                    <div class="file-upload-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-upload-cloud"><polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path><polyline points="16 16 12 12 8 16"></polyline></svg>
                    </div>
                    <div class="file-upload-text">Click or drag screenshot file here to upload (max 5MB)</div>
                    <div class="file-upload-name" id="file-name"></div>
                    <input type="file" name="screenshot" id="screenshot" accept="image/*" required>
                </div>
            </div>

            <button type="submit" class="submit-btn">Submit Payment Proof</button>
            <a href="{{ url('/') }}" class="cancel-link">Cancel and Go Back</a>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('screenshot').addEventListener('change', function(e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : '';
        var nameDiv = document.getElementById('file-name');
        if (fileName) {
            nameDiv.textContent = 'Selected: ' + fileName;
            nameDiv.style.display = 'block';
        } else {
            nameDiv.style.display = 'none';
        }
    });
</script>
@endsection
