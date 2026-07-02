@extends('liteback.layout')

@section('title', 'Payment Gateways Settings')
@section('page_title', 'Payment Gateways Settings')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <form action="{{ route('liteback.payments.settings.update') }}" method="POST">
            @csrf

            <!-- STRIPE CONFIGURATION -->
            <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-stripe text-primary mr-2"></i> Stripe Integration</h3>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Enable Stripe</label>
                        <div class="col-sm-9">
                            <select name="payment_stripe_enabled" class="form-control">
                                <option value="1" {{ settings('payment_stripe_enabled', config('payments.drivers.stripe.enabled') ? '1' : '0') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ settings('payment_stripe_enabled', config('payments.drivers.stripe.enabled') ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Stripe Public Key</label>
                        <div class="col-sm-9">
                            <input type="text" name="payment_stripe_public_key" class="form-control" value="{{ settings('payment_stripe_public_key', config('payments.drivers.stripe.public_key')) }}" placeholder="pk_live_...">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Stripe Secret Key</label>
                        <div class="col-sm-9">
                            <input type="password" name="payment_stripe_secret_key" class="form-control" value="{{ settings('payment_stripe_secret_key', config('payments.drivers.stripe.secret_key')) }}" placeholder="sk_live_...">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Webhook Signing Secret</label>
                        <div class="col-sm-9">
                            <input type="password" name="payment_stripe_webhook_secret" class="form-control" value="{{ settings('payment_stripe_webhook_secret', config('payments.drivers.stripe.webhook_secret')) }}" placeholder="whsec_...">
                            <small class="text-muted">Set up a webhook to endpoint: <code>{{ route('payment.webhook.stripe') }}</code> listening to <code>checkout.session.completed</code> event.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAYPAL CONFIGURATION -->
            <div class="card card-info card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-paypal text-info mr-2"></i> PayPal Integration</h3>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Enable PayPal</label>
                        <div class="col-sm-9">
                            <select name="payment_paypal_enabled" class="form-control">
                                <option value="1" {{ settings('payment_paypal_enabled', config('payments.drivers.paypal.enabled') ? '1' : '0') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ settings('payment_paypal_enabled', config('payments.drivers.paypal.enabled') ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">PayPal Mode</label>
                        <div class="col-sm-9">
                            <select name="payment_paypal_mode" class="form-control">
                                <option value="sandbox" {{ settings('payment_paypal_mode', config('payments.drivers.paypal.mode', 'sandbox')) == 'sandbox' ? 'selected' : '' }}>Sandbox / Test</option>
                                <option value="live" {{ settings('payment_paypal_mode', config('payments.drivers.paypal.mode', 'sandbox')) == 'live' ? 'selected' : '' }}>Live / Production</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Client ID</label>
                        <div class="col-sm-9">
                            <input type="text" name="payment_paypal_client_id" class="form-control" value="{{ settings('payment_paypal_client_id', config('payments.drivers.paypal.client_id')) }}" placeholder="Enter PayPal Client ID">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Client Secret Key</label>
                        <div class="col-sm-9">
                            <input type="password" name="payment_paypal_secret" class="form-control" value="{{ settings('payment_paypal_secret', config('payments.drivers.paypal.secret')) }}" placeholder="Enter PayPal Client Secret">
                        </div>
                    </div>
                </div>
            </div>

            <!-- BTCPAY CONFIGURATION -->
            <div class="card card-danger card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-bitcoin text-danger mr-2"></i> BTCPay Server Integration</h3>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Enable BTCPay</label>
                        <div class="col-sm-9">
                            <select name="payment_btcpay_enabled" class="form-control">
                                <option value="1" {{ settings('payment_btcpay_enabled', config('payments.drivers.btcpay.enabled') ? '1' : '0') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ settings('payment_btcpay_enabled', config('payments.drivers.btcpay.enabled') ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">BTCPay Host URL</label>
                        <div class="col-sm-9">
                            <input type="text" name="payment_btcpay_host" class="form-control" value="{{ settings('payment_btcpay_host', config('payments.drivers.btcpay.host')) }}" placeholder="https://btcpay.yourdomain.com">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Store ID</label>
                        <div class="col-sm-9">
                            <input type="text" name="payment_btcpay_store_id" class="form-control" value="{{ settings('payment_btcpay_store_id', config('payments.drivers.btcpay.store_id')) }}" placeholder="Enter BTCPay Store ID">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">API Key</label>
                        <div class="col-sm-9">
                            <input type="password" name="payment_btcpay_api_key" class="form-control" value="{{ settings('payment_btcpay_api_key', config('payments.drivers.btcpay.api_key')) }}" placeholder="Enter API Key">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Webhook Secret</label>
                        <div class="col-sm-9">
                            <input type="password" name="payment_btcpay_webhook_secret" class="form-control" value="{{ settings('payment_btcpay_webhook_secret', config('payments.drivers.btcpay.webhook_secret')) }}" placeholder="Enter Webhook Signing Secret">
                            <small class="text-muted">Set up a webhook to endpoint: <code>{{ route('payment.webhook.btcpay') }}</code> listening to <code>InvoiceSettled</code> and <code>InvoicePaid</code> events.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MANUAL PAYMENT -->
            <div class="card card-warning card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-university text-warning mr-2"></i> Manual Bank / Mobile Transfers</h3>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Enable Manual Transfer</label>
                        <div class="col-sm-9">
                            <select name="payment_manual_enabled" class="form-control">
                                <option value="1" {{ settings('payment_manual_enabled', config('payments.drivers.manual.enabled') ? '1' : '0') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ settings('payment_manual_enabled', config('payments.drivers.manual.enabled') ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Payment Instructions</label>
                        <div class="col-sm-9">
                            <textarea name="payment_manual_instructions" class="form-control" rows="6" placeholder="Enter instructions for the player...">{{ settings('payment_manual_instructions', config('payments.drivers.manual.instructions')) }}</textarea>
                            <small class="text-muted">This text will be shown to users when they initiate a manual bank transfer deposit, advising them how/where to transfer money before uploading proof.</small>
                    </div>
                </div>
            </div>

            <!-- XTOPAY CONFIGURATION -->
            <div class="card card-success card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-coins text-success mr-2"></i> XtoPay Crypto Integration</h3>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Enable XtoPay</label>
                        <div class="col-sm-9">
                            <select name="payment_xto_enabled" class="form-control">
                                <option value="1" {{ settings('payment_xto_enabled', config('payments.drivers.xtopay.enabled') ? '1' : '0') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ settings('payment_xto_enabled', config('payments.drivers.xtopay.enabled') ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Website Name</label>
                        <div class="col-sm-9">
                            <input type="text" name="payment_xto_website_name" class="form-control" value="{{ settings('payment_xto_website_name', config('payments.drivers.xtopay.website_name')) }}" placeholder="e.g. one">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Merchant JWT Token</label>
                        <div class="col-sm-9">
                            <input type="password" name="payment_xto_token" class="form-control" value="{{ env('XTO_PAY_TOKEN', settings('payment_xto_token', config('payments.drivers.xtopay.token'))) }}" placeholder="Enter Merchant JWT Token">
                            <small class="text-muted">The secret token will be saved securely to the <code>.env</code> file (<code>XTO_PAY_TOKEN</code>) to prevent database leaks.</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Allowed Methods (comma-separated)</label>
                        <div class="col-sm-9">
                            <input type="text" name="payment_xto_methods" class="form-control" value="{{ settings('payment_xto_methods', config('payments.drivers.xtopay.allowed_methods')) }}" placeholder="TRC20_USDT,POLYGON_USDT,BSC_USDT,ERC20_USDT,POLYGON_USDC,BSC_USDC,ERC20_USDC">
                            <small class="text-muted">Comma-separated list of crypto billing methods. Standard: <code>TRC20_USDT,POLYGON_USDT,BSC_USDT,ERC20_USDT,POLYGON_USDC,BSC_USDC,ERC20_USDC</code></small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Webhook Callback URL</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="{{ route('payment.webhook.xtopay') }}" readonly>
                            <small class="text-muted">Configure this webhook URL inside the XtoPay merchant gateway dashboard settings to receive status notifications.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-right pb-5">
                <button type="submit" class="btn btn-success btn-lg px-5">Save Gateways Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
