@extends('layouts.guest')

@section('page-title')
    {{ __('Two-Factor Authentication') }}
@endsection

@section('content')
    <style>
        .g-recaptcha {
            filter: invert(1) hue-rotate(180deg) !important;
        }

        .grecaptcha-badge {
            z-index: 99999999 !important;
        }
    </style>
    <div class="card-body">
        <div>
            <h2 class="mb-3 f-w-600">{{ __('Two-Factor Authentication') }} </h2>
        </div>
        <div class="custom-login-form">
            <form method="POST" action="{{ route('2faVerify') }}" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="2fa_referrer" value="{{ request()->get('2fa_referrer') ?? URL()->current() }}">
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('One-Time Password') }}</label>
                    <input id="one_time_password" type="password"
                        class="form-control @error('one_time_password') is-invalid @enderror"
                        name="one_time_password" placeholder="{{ __('Enter your OTP') }}" required autofocus>
                    @error('one_time_password')
                        <span class="error invalid-feedback text-danger">
                            <small>{{ $message }}</small>
                        </span>
                    @enderror
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <button class="btn btn-primary w-100" type="submit">
                            {{ __('Verify & Login') }}
                        </button>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('logout') }}" class="btn btn-danger w-100 text-white"
                           onclick="event.preventDefault(); document.getElementById('frm-logout').submit();">
                            {{ __('Cancel & Logout') }}
                        </a>
                    </div>
                </div>
                
            </form>
            <form id="frm-logout" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
@endsection
