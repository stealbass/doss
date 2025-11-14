<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerTranz Payment</title>
    <link rel="stylesheet" href="{{ asset('public/assets/css/main-style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/css/responsive.css') }}">
</head>

<body>
    <div class="payment">
        <div class="payment-form">
            <form class="payment-form" method="POST" action="{{ isset($pay['action']) ? $pay['action'] : '#!' }}">
                @csrf

                <input type="hidden" name="data" value="{{ json_encode($pay) }}">

                @isset($pay['plan_id'])
                    <input type="hidden" name="plan_id" value="{{ $pay['plan_id'] }}">
                @endisset

                @isset($pay['order_id'])
                    <input type="hidden" name="order_id" value="{{ $pay['order_id'] }}">
                @endisset

                @isset($pay['bill_id'])
                    <input type="hidden" name="bill_id" value="{{ $pay['bill_id'] }}">
                @endisset

                <input type="hidden" name="amount" value="{{ $pay['amount'] ?? '' }}">
                <input type="hidden" name="currency" value="{{ $pay['currency'] ?? '' }}">
                <div class="row">

                    <div class="col-12">
                        <div class="form-group user d-flex align-items-center justify-content-between">
                            <div class="user-name">
                                <label><small>{{ __('Business') }}</small></label>
                                <h3 class="h6">{{ $pay['title'] ?? '' }}</h3>
                            </div>
                            <div class="amount">
                                <label>{{ __('Amount') }}</label>
                                <div class="Payment-amount">
                                    <span>{{ ($pay['currency'] ?? '') . ' ' . ($pay['amount'] ?? '') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label>{{ __('Card number') }}</label>
                            <input type="text" class="form-control" placeholder="Card Number" name="cardNumber"
                                maxlength="16" pattern="\d{16}" required>
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label>{{ __('Expiry date') }}</label>
                            <input type="text" name="expiryDate" class="form-control" placeholder="MM/YY"
                                pattern="\d{2}/\d{2}" maxlength="5" required>
                        </div>
                    </div>

                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label>{{ __('CVV') }}</label>
                            <input type="text" name="cvv" class="form-control" placeholder="CVV"
                                pattern="\d{3,4}" maxlength="4" required>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label>{{ __('Card Holder Name') }}</label>
                            <input type="text" name="card_name" class="form-control" placeholder="Name" required>
                        </div>
                    </div>
                </div>
                <div class="paybtn-wrp">
                    <a href="{{ isset($pay['back_url']) ? $pay['back_url'] : route('plans.index') }}"
                        class="btn btn-outline-primary px-5 float-end">{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-outline-primary px-5 float-end">{{ __('Pay') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('public/js/custom.js') }}"></script>
</body>

</html>
