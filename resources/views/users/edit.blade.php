@extends('layouts.app')

@if ($user->id == Auth::user()->id)
    @section('page-title', __('Profile'))
@elseif (Auth::user()->type == 'super admin')
    @section('page-title', __('Update Compnay'))
@else
    @section('page-title', __('Edit Member'))
@endif

@php
    $logo = App\Models\Utility::get_file('uploads/profile');
    $settings = App\Models\Utility::settings();
    $file_validation = App\Models\Utility::file_upload_validation();
@endphp

@section('breadcrumb')
    @if ($user->id == Auth::user()->id)
        <li class="breadcrumb-item">{{ __('Update Profile') }}</li>
    @elseif (Auth::user()->type == 'super admin')
        <li class="breadcrumb-item">{{ __('Update Compnay') }}</li>
    @else
        <li class="breadcrumb-item">{{ __('Update Member') }}</li>
    @endif
@endsection

@section('content')
    <div class="row p-0 g-0">
        <div class="col-sm-12">
            <div class="row g-0">

                <div class="col-xl-3 border-end border-bottom">
                    <div class="card shadow-none bg-transparent sticky-top" style="top:70px">
                        <div class="list-group list-group-flush rounded-0" id="useradd-sidenav">
                            <a href="#useradd-1" class="list-group-item list-group-item-action">
                                {{ __('Information') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            @if ($user->id == Auth::user()->id)
                                <a href="#useradd-2" class="list-group-item list-group-item-action">
                                    {{ __('Change Password') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif
                            <a href="#useradd-3" class="list-group-item list-group-item-action">
                                {{ __('Two Factor Authentication') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>

                            @if (Auth::user()->type == 'super admin' && $user->id != Auth::user()->id)
                                <a href="#useradd-3" class="list-group-item list-group-item-action">
                                    {{ __('Usage Statistics') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                                <a href="#useradd-4" class="list-group-item list-group-item-action">
                                    {{ __('Employees') }}
                                    <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-9">
                    <div id="useradd-1" class="card  shadow-none rounded-0 border-bottom">
                        <div class="card-header">
                            @if (Auth::user()->id == $user->id)
                                <h5 class="mb-0">{{ __('Personal Information') }}</h5>
                            @elseif (Auth::user()->type == 'super admin')
                                <h5 class="mb-0">{{ __('Company Information') }}</h5>
                            @else
                                <h5 class="mb-0">{{ __('Member Information') }}</h5>
                            @endif
                        </div>
                        {{ Form::model($user, ['route' => ['users.update', $user->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
                        <div class="card-body">
                            <div class="setting-card">
                                <div class="row">

                                    <div class="col-lg-4 col-sm-6 col-md-6">
                                        <div class="card-body text-center">
                                            <div class="logo-content">
                                                <a href="{{ !empty($user->avatar) ? $logo . '/' . $user->avatar : $logo . '/avatar.png' }}"
                                                    target="_blank">
                                                    <img src="{{ !empty($user->avatar) ? $logo . '/' . $user->avatar : $logo . '/avatar.png' }}"
                                                        width="100" id="profile">
                                                </a>
                                            </div>
                                            <div class="choose-files mt-4">
                                                <label for="profile_pic">
                                                    <div class="bg-primary profile_update"
                                                        style="max-width: 100% !important;">
                                                        <i class="ti ti-upload px-1"></i>{{ __('Choose file here') }}
                                                    </div>
                                                    <input type="file" class="file" name="profile" accept="image/*"
                                                        id="profile_pic"
                                                        onchange="document.getElementById('profile').src = window.URL.createObjectURL(this.files[0])"
                                                        style="width: 0px !important">
                                                    <p style="margin-top: -20px;text-align: center;">
                                                        <span class="text-muted m-0" data-toggle="tooltip"
                                                            title="{{ $file_validation['mimes'] }} ({{ __('Max Size In KB: ') }}{{ $file_validation['max_size'] }})"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="top">{{ __('Allowed file extension') }}
                                                        </span>
                                                    </p>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-8 col-sm-6 col-md-6">
                                        <div class="card-body">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    @if ($user->type == 'company')
                                                        <label class="col-form-label text-dark" id="fullname">
                                                            {{ __('Firm/Advocate Name') }}
                                                        </label><x-required></x-required>
                                                    @else
                                                        <label class="col-form-label text-dark" id="fullname">
                                                            {{ __('Name') }}
                                                        </label><x-required></x-required>
                                                    @endif
                                                    <input class="form-control " name="name" type="text"
                                                        id="fullname" placeholder="{{ __('Enter Your Name') }}"
                                                        value="{{ $user->name }}" required autocomplete="name">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="email" class="col-form-label text-dark">
                                                        {{ __('Email') }}
                                                    </label><x-required></x-required>
                                                    <input class="form-control " name="email" type="email"
                                                        id="email" placeholder="{{ __('Enter Your Email Address') }}"
                                                        value="{{ $user->email }}" required autocomplete="email">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            @if (Auth::user()->type == 'advocate' && $user->id == Auth::user()->id)
                                <div class="row card-body pt-0 pb-0">
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            {{ Form::label('phone_number', __('Phone Number'), ['class' => 'col-form-label']) }}
                                            {{ Form::text('phone_number', null, ['class' => 'form-control', 'placeholder' => __('Enter Phone Number'), 'required' => 'required']) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('age', __('Age'), ['class' => 'col-form-label']) }}
                                            {{ Form::number('age', $advocate->age, ['class' => 'form-control', 'placeholder' => 'Enter Your Age']) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('company_name', __('Company Name'), ['class' => 'col-form-label']) }}
                                            {{ Form::text('company_name', $advocate->company_name, ['class' => 'form-control', 'placeholder' => 'Enter Your Company Name']) }}
                                        </div>
                                    </div>
                                    <div class="card-header">
                                        <div class="row flex-grow-1">
                                            <div class="col-md d-flex align-items-center">
                                                <h5 class="card-header-title">
                                                    {{ __('Office Address') }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('ofc_address_line_1', __('Address Line 1'), ['class' => 'col-form-label']) }}
                                            {{ Form::text('ofc_address_line_1', $advocate->ofc_address_line_1, ['class' => 'form-control', 'placeholder' => 'Enter Your Address Line 1']) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('ofc_address_line_2', __('Address Line 2'), ['class' => 'col-form-label']) }}
                                            {{ Form::text('ofc_address_line_2', $advocate->ofc_address_line_2, ['class' => 'form-control', 'placeholder' => 'Enter Your Address Line 2']) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('country', __('Country'), ['class' => 'col-form-label']) }}
                                            <select class="form-control " id="country" name="ofc_country">
                                                <option value="">{{ __('Select Country') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('state', __('State'), ['class' => 'col-form-label']) }}
                                            <select class="form-control" id="state" name="ofc_state">
                                                <option value="">{{ __('Select State') }}</option>
                                                @foreach ($advocate->getStateByCountry($advocate->ofc_country) as $state)
                                                    <option value="{{ $state->id }}"
                                                        {{ $state->id == $advocate->getSelectedState($advocate->ofc_state) ? 'selected' : '' }}>
                                                        {{ $state->region }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('city', __('City'), ['class' => 'col-form-label']) }}
                                            <select class="form-control" id="city" name="ofc_city">
                                                <option value="">{{ __('Select City') }}</option>
                                                @foreach ($advocate->getCityByState($advocate->ofc_state) as $city)
                                                    <option value="{{ $city->id }}"
                                                        {{ $city->id == $advocate->getSelectedCity($advocate->ofc_city) ? 'selected' : '' }}>
                                                        {{ $city->city }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('ofc_zip_code', __('Zip/Postal Code'), ['class' => 'col-form-label']) }}
                                            {{ Form::number('ofc_zip_code', $advocate->ofc_zip_code, ['class' => 'form-control', 'placeholder' => 'Enter Your Zip/Postal Code']) }}
                                        </div>
                                    </div>
                                    <div class="card-header">
                                        <div class="row flex-grow-1">
                                            <div class="col-md d-flex align-items-center">
                                                <h5 class="card-header-title">
                                                    {{ __('Chamber Address') }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('home_address_line_1', __('Address Line 1'), ['class' => 'col-form-label']) }}
                                            {{ Form::text('home_address_line_1', $advocate->home_address_line_1, ['class' => 'form-control', 'placeholder' => 'Enter Your Address Line 1']) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('home_address_line_2', __('Address Line 2'), ['class' => 'col-form-label']) }}
                                            {{ Form::text('home_address_line_2', $advocate->home_address_line_2, ['class' => 'form-control', 'placeholder' => 'Enter Your Address Line 2']) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('country', __('Country'), ['class' => 'col-form-label']) }}
                                            <select class="form-control" id="home_country" name="home_country">
                                                <option value="">{{ __('Select Country') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('state', __('State'), ['class' => 'col-form-label']) }}
                                            <select class="form-control" id="home_state" name="home_state">
                                                <option value="">{{ __('Select State') }}</option>
                                                @foreach ($advocate->getStateByCountry($advocate->home_country) as $state)
                                                    <option value="{{ $state->id }}"
                                                        {{ $state->id == $advocate->getSelectedState($advocate->home_state) ? 'selected' : '' }}>
                                                        {{ $state->region }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('city', __('City'), ['class' => 'col-form-label']) }}
                                            <select class="form-control" id="home_city" name="home_city">
                                                <option value="">{{ __('Select City') }}</option>
                                                @foreach ($advocate->getCityByState($advocate->home_state) as $city)
                                                    <option value="{{ $city->id }}"
                                                        {{ $city->id == $advocate->getSelectedCity($advocate->home_city) ? 'selected' : '' }}>
                                                        {{ $city->city }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            {{ Form::label('home_zip_code', __('Zip/Postal Code'), ['class' => 'col-form-label']) }}
                                            {{ Form::number('home_zip_code', $advocate->home_zip_code, ['class' => 'form-control', 'placeholder' => 'Enter Your Zip/Postal Code']) }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="row card-body">
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="mobile_number"
                                                class="col-form-label text-dark">{{ __('Mobile Number') }}</label>
                                            <input class="form-control " pattern="^\+\d{1,3}\d{9,13}$"
                                                name="mobile_number" type="text" id="mobile_number"
                                                placeholder="{{ __('Enter Your Mobile Number') }}"
                                                value="{{ !empty($user_detail->mobile_number) ? $user_detail->mobile_number : '' }}"
                                                autocomplete="mobile_number">
                                            <div class=" text-xs text-danger">
                                                {{ __('Please use with country code. (ex. +91)') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="address"
                                                class="col-form-label text-dark">{{ __('Address') }}</label>
                                            <input class="form-control " name="address" type="text" id="address"
                                                placeholder="{{ __('Enter Your Address') }}"
                                                value="{{ !empty($user_detail->address) ? $user_detail->address : '' }}"
                                                autocomplete="address">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="city"
                                                class="col-form-label text-dark">{{ __('City') }}</label>
                                            <input class="form-control " name="city" type="text" id="city"
                                                placeholder="{{ __('Enter Your City') }}"
                                                value="{{ !empty($user_detail->city) ? $user_detail->city : '' }}"
                                                autocomplete="city">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="state"
                                                class="col-form-label text-dark">{{ __('State') }}</label>
                                            <input class="form-control " name="state" type="text" id="state"
                                                placeholder="{{ __('Enter Your State') }}"
                                                value="{{ !empty($user_detail->state) ? $user_detail->state : '' }}"
                                                autocomplete="state">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="zip_code"
                                                class="col-form-label text-dark">{{ __('Zip/Postal Code') }}</label>
                                            <input class="form-control " name="zip_code" type="number" id="zip_code"
                                                placeholder="{{ __('Enter Your Zip/Postal Code') }}"
                                                value="{{ !empty($user_detail->zip_code) ? $user_detail->zip_code : '' }}"
                                                autocomplete="zip_code">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="landmark"
                                                class="col-form-label text-dark">{{ __('Landmark') }}</label>
                                            <input class="form-control " name="landmark" type="text" id="landmark"
                                                placeholder="{{ __('Enter Your Landmark') }}"
                                                value="{{ !empty($user_detail->landmark) ? $user_detail->landmark : '' }}"
                                                autocomplete="landmark">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="about"
                                                class="col-form-label text-dark">{{ __('Brief About Yourself') }}</label>
                                            <input class="form-control " name="about" type="text" id="about"
                                                placeholder="{{ __('Enter Your About Yourself') }}"
                                                value="{{ !empty($user_detail->about) ? $user_detail->about : '' }}"
                                                autocomplete="about">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer">
                            <div class="col-lg-12 text-end">
                                {{ Form::submit(__('Save Changes'), ['class' => 'btn btn-primary']) }}
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>

                    @if ($user->id == Auth::user()->id)
                        <div id="useradd-2" class="card  shadow-none rounded-0 border-bottom">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Change Password') }}</h5>
                                <small> {{ __('Details about your member account password change') }}</small>
                            </div>
                            {{ Form::open(['route' => ['member.change.password', $user->id], 'method' => 'POST', 'class' => 'needs-validation', 'novalidate']) }}
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password"
                                                class="form-label col-form-label text-dark">{{ __('New Password') }}</label><x-required></x-required>
                                            <input class="form-control" name="password" type="password" id="password"
                                                required autocomplete="password"
                                                placeholder="{{ __('Enter New Password') }}" minlength="8">
                                            @error('password')
                                                <span class="invalid-password" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="confirm_password"
                                                class="form-label col-form-label text-dark">{{ __('Confirm Password') }}</label><x-required></x-required>
                                            <input class="form-control" name="confirm_password" type="password"
                                                id="confirm_password" required autocomplete="confirm_password"
                                                placeholder="{{ __('Confirm New Password') }}" minlength="8">
                                            @error('confirm_password')
                                                <span class="invalid-confirm_password" role="alert">
                                                    <strong class="text-danger">{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="col-lg-12 text-end">
                                    {{ Form::submit(__('Save Changes'), ['class' => 'btn btn-primary']) }}
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    @endif

                    {{-- Two Factor Authentication --}}
                    @if (Auth::user()->type === 'super admin' || Auth::user()->type === 'company')
                        <div id="useradd-3" class="card" id="authentication-sidenav">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Two Factor Authentication') }}</h5>
                            </div>
                            <div class="card-body">
                                <p>{{ __('Two factor authentication (2FA) strengthens access security by requiring two methods (also referred to as factors) to verify your identity. Two factor authentication protects against phishing, social engineering and password brute force attacks and secures your logins from attackers exploiting weak or stolen credentials.') }}
                                </p>
                                @if (!empty($data) && isset($data['user']) && $data['user']->google2fa_secret == null)
                                    {{-- Generate Secret Key --}}
                                    <form class="form-horizontal" method="POST"
                                        action="{{ route('generate2faSecret') }}">
                                        @csrf
                                        <div class="col-lg-12 text-center">
                                            <button type="submit" class="btn btn-primary">
                                                {{ __(' Generate Secret Key to Enable 2FA') }}
                                            </button>

                                        </div>
                                    </form>
                                @elseif($data['user']->google2fa_enable == 0 && $data['user']->google2fa_secret != null)
                                    1️.{{ __('Install “Google Authentication App” on your') }}
                                    <a href="https://apps.apple.com/us/app/google-authenticator/id388497605"
                                        target="_black">iOS</a> or
                                    <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"
                                        target="_black">Android</a>.
                                    <br>
                                    2️. {{ __('Open the Google Authentication App and scan the below QR code.') }}
                                    <br>
                                    {{-- QR Code Display --}}
                                    @php
                                        $f = finfo_open();
                                        $mime_type = finfo_buffer($f, $data['google2fa_url'], FILEINFO_MIME_TYPE);
                                    @endphp
                                    @if ($data['google2fa_url'])
                                        {!! $data['google2fa_url'] !!}
                                    @endif
                                    <br>
                                    3️.{{ __('Alternatively, you can use this secret key:') }}<code>{{ $data['secret'] }}</code>
                                    <br>
                                    <p>4️.{{ __('Enter the 6-digit Google Authentication code from the app below:') }}</p>

                                    <form class="form-horizontal needs-validation" novalidate method="POST"
                                        action="{{ route('enable2fa') }}">
                                        @csrf
                                        <div class="form-group">
                                            <label for="secret"
                                                class="col-form-label">{{ __('Authenticator Code') }}</label>
                                            <input id="secret" type="text" class="form-control" name="secret"
                                                required>
                                        </div>
                                        <div class="col-lg-12 text-center">
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('Enable 2FA') }}
                                            </button>
                                        </div>
                                    </form>
                                @elseif($data['user']->google2fa_enable == 1 && $data['user']->google2fa_secret != null)
                                    <div class="alert alert-success text-center">
                                        <strong>{{ __('2FA is Enabled on your account.') }}</strong>
                                    </div>

                                    <p>{{ __('To disable Two-Factor Authentication, please enter your password and click Disable 2FA.') }}
                                    </p>

                                    {{-- Disable 2FA Form --}}
                                    <form class="form-horizontal needs-validation" novalidate method="POST"
                                        action="{{ route('disable2fa') }}">
                                        @csrf
                                        <div class="form-group">
                                            <label for="current-password"
                                                class="col-form-label">{{ __('Current Password') }}</label>
                                            <input id="current-password" type="password" class="form-control"
                                                name="current-password" required>
                                            @error('current-password')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-lg-12 text-center">
                                            <button type="submit" class="btn btn-danger">
                                                {{ __('Disable 2FA') }}
                                            </button>
                                        </div>
                                    </form>

                                @endif
                            </div>
                        </div>
                    @endif



                    @if (Auth::user()->type == 'super admin' && $user->id != Auth::user()->id)
                        <div id="useradd-3" class="card  shadow-none rounded-0 border-bottom">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Usage Statistics') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class=" setting-card">
                                    <div class="row">
                                        <div
                                            class="col-xxl-3 col-lg-4 col-md-6 col-sm-6 plan_card mb-0 border-bottom border-end">
                                            <div class="card shadow-none  price-card price-1 rounded-0">
                                                <div class="card-body ">
                                                    <span class="price-badge bg-primary">{{ $plan->name }}</span>
                                                    <span class="mb-4 f-w-500 p-price">
                                                        {{ $settings['site_currency_symbol'] ? $settings['site_currency_symbol'] : '$' }}
                                                        {{ number_format($plan->price) }} <small class="text-sm">/
                                                            {{ $plan->duration }}</small>
                                                    </span>
                                                    <p class="mb-0">
                                                    </p>
                                                    {{-- <p class="mb-0">
                                                        {{ $plan->description }}
                                                    </p> --}}
                                                    <ul class="list-unstyled">
                                                        <li>
                                                            <span class="theme-avtar">
                                                                <i class="text-primary ti ti-circle-plus"></i></span>
                                                            {{ $plan->max_users < 0 ? __('Unlimited') : $plan->max_users }}
                                                            {{ __('Users') }}
                                                        </li>
                                                        <li>
                                                            <span class="theme-avtar">
                                                                <i class="text-primary ti ti-circle-plus"></i></span>
                                                            {{ $plan->max_advocates < 0 ? __('Unlimited') : $plan->max_advocates }}
                                                            {{ __('Advocates') }}
                                                        </li>
                                                        <li>
                                                            <span class="theme-avtar">
                                                                <i class="text-primary ti ti-circle-plus"></i></span>
                                                            {{ $plan->storage_limit < 0 ? __('Unlimited') : $plan->storage_limit }}
                                                            {{ __('Storage Limit') }}
                                                        </li>
                                                        <li>
                                                            <span class="theme-avtar">
                                                                <i class="text-primary ti ti-circle-plus"></i></span>
                                                            {{ $plan->enable_chatgpt == 'on' ? __('Enable Chat GPT') : __('Disable Chat GPT') }}
                                                        </li>
                                                        @if ($plan->trial != 0)
                                                            <li>
                                                                <span class="theme-avtar">
                                                                    <i class="text-primary ti ti-circle-plus"></i></span>
                                                                {{ $plan->trial_days != null && $plan->trial != 0 ? $plan->trial_days : '0' }}
                                                                {{ __('Trial Days') }}
                                                            </li>
                                                        @endif
                                                    </ul>
                                                    <div class="p-0">
                                                        <a href="#" class="btn btn-sm btn-light-primary"
                                                            data-url="{{ route('plan.upgrade', $user->id) }}"
                                                            data-size="lg" data-ajax-popup="true"
                                                            data-title="{{ __('Upgrade Plan') }}">
                                                            {{ __('Upgrade Plan') }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xxl-9 col-lg-7 col-md-6 col-sm-6">
                                            <div class="row">
                                                <div class="col-xxl-6 col-lg-12 col-md-6 col-sm-6 p-1">
                                                    <div class="col border-end border-bottom">
                                                        <div class="p-4">
                                                            <div class="row justify-content-between mb-3">
                                                                <div class="col-auto">
                                                                    <div class="theme-avtar bg-primary">
                                                                        <i class="ti ti-users"></i>
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <p class="text-muted text-sm mb-0">
                                                                        {{ __('Total') }}
                                                                    </p>
                                                                    <h6 class="mb-0">{{ __('Users') }}
                                                                    </h6>
                                                                </div>
                                                            </div>
                                                            <h3 class="mb-0">{{ count($users) }}</h3>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-6 col-lg-12 col-md-6 col-sm-6 p-1">
                                                    <div class="col border-end border-bottom">
                                                        <div class="p-4">
                                                            <div class="row justify-content-between mb-3">
                                                                <div class="col-auto">
                                                                    <div class="theme-avtar bg-info">
                                                                        <i class="ti ti-users"></i>
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <p class="text-muted text-sm mb-0">
                                                                        {{ __('Total') }}
                                                                    </p>
                                                                    <h6 class="mb-0">{{ __('Clients') }}</h6>
                                                                </div>
                                                            </div>
                                                            <h3 class="mb-0">{{ count($client) }}</h3>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-6 col-lg-12 col-md-6 col-sm-6 p-1">
                                                    <div class="col border-end border-bottom">
                                                        <div class=" p-4 pb-5">
                                                            <div class="row justify-content-between mb-3">
                                                                <div class="col-auto">
                                                                    <div class="theme-avtar bg-warning">
                                                                        <i class="ti ti-report-money"></i>
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto ">
                                                                    <p class="text-muted text-sm mb-0">
                                                                        {{ __('Total') }}
                                                                    </p>
                                                                    <h6 class="mb-0">{{ __('Cases') }}</h6>
                                                                </div>
                                                            </div>
                                                            <h3 class="mb-0">{{ count($cases) }}</h3>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xxl-6 col-lg-12 col-md-6 col-sm-6 p-1">
                                                    <div class="col border-end border-bottom">
                                                        <div class="p-4 pb-5">
                                                            <div class="row justify-content-between mb-3">
                                                                <div class="col-auto">
                                                                    <div class="theme-avtar bg-danger">
                                                                        <i class="ti ti-users"></i>
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <p class="text-muted text-sm mb-0">
                                                                        {{ __('Total') }}
                                                                    </p>
                                                                    <h6 class="mb-0">{{ __('Advocates') }}</h6>
                                                                </div>
                                                            </div>
                                                            <h3 class="mb-0">{{ count($advocates) }}</h3>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="useradd-4" class="card  shadow-none rounded-0 border-bottom">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Employees') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class=" setting-card">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table dataTable data-table ">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('#') }}</th>
                                                            <th>{{ __('Name') }}</th>
                                                            <th>{{ __('Role') }}</th>
                                                            <th>{{ __('Email') }}</th>
                                                            <th width="100px">{{ __('Action') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($users as $key => $employee)
                                                            <tr>
                                                                <td>{{ $key + 1 }}</td>
                                                                <td>{{ $employee->name }}</td>
                                                                <td>{{ $employee->type }}</td>
                                                                <td>{{ $employee->email }}</td>
                                                                <td>
                                                                    <div class="action-btn me-2">
                                                                        <a href="#"
                                                                            data-url="{{ route('company.reset', \Crypt::encrypt($employee->id)) }}"
                                                                            class="mx-3 btn btn-sm btn-light-blue-subtitle align-items-center "
                                                                            data-tooltip="Edit" data-ajax-popup="true"
                                                                            data-title="{{ __('Reset Password') }}"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="{{ __('Reset Password') }}">
                                                                            <i class="ti ti-key text-white"></i>
                                                                        </a>
                                                                    </div>

                                                                    @canany(['delete member', 'delete user'])
                                                                        <div class="action-btn me-2">
                                                                            <a href="#"
                                                                                class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para "
                                                                                data-confirm="{{ __('Are You Sure?') }}"
                                                                                data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                                data-confirm-yes="delete-form-{{ $employee->id }}"
                                                                                title="{{ __('Delete') }}"
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-placement="top">
                                                                                <i class="ti ti-trash"></i>
                                                                            </a>
                                                                        </div>
                                                                        {!! Form::open([
                                                                            'method' => 'DELETE',
                                                                            'route' => ['users.destroy', $employee->id],
                                                                            'id' => 'delete-form-' . $employee->id,
                                                                        ]) !!}
                                                                        {!! Form::close() !!}
                                                                    @endcanany
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('custom-script')
    <script>
        var scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#useradd-sidenav',
            offset: 300
        })

        $(document).ready(function() {
            $(".list-group-item").first().addClass('active');

            $(".list-group-item").on('click', function() {
                $(".list-group-item").removeClass('active')
                $(this).addClass('active');
            });
        })
    </script>

    @if (Auth::user()->type == 'advocate' && $user->id == Auth::user()->id)
        <script>
            $(document).ready(function() {

                var get_selected =
                    '{{ !empty($advocate->ofc_country) ? $advocate->getCountryName($advocate->ofc_country) : $advocate->getCountryName(113) }}';
                var home_selected =
                    '{{ !empty($advocate->home_country) ? $advocate->getCountryName($advocate->home_country) : $advocate->getCountryName(113) }}';

                $.ajax({
                    url: "{{ route('get.country') }}",
                    type: "GET",
                    success: function(result) {

                        $.each(result.data, function(key, value) {
                            if (value.id == get_selected) {
                                var selected = 'selected';
                            } else {
                                var selected = '';
                            }

                            if (value.id == home_selected) {
                                var selected_home = 'selected';
                            } else {
                                var selected_home = '';
                            }

                            $("#country").append('<option value="' + value.id + '" ' + selected +
                                ' >' + value
                                .country + "</option>");

                            $("#home_country").append('<option value="' + value.id + '" ' +
                                selected_home + '>' + value
                                .country + "</option>");
                        });
                    },
                });


                $("#country").on("change", function() {
                    var country_id = this.value;

                    $("#state").html("");
                    $.ajax({
                        url: "{{ route('get.state') }}",
                        type: "POST",
                        data: {
                            country_id: country_id,
                            _token: "{{ csrf_token() }}",
                        },
                        dataType: "json",
                        success: function(result) {
                            $.each(result.data, function(key, value) {
                                $("#state").append('<option value="' + value.id + '">' +
                                    value.region + "</option>");
                            });
                            $("#city").html('<option value="">Select State First</option>');
                        },
                    });
                });

                $("#home_country").on("change", function() {
                    var country_id = this.value;
                    $("#home_state").html("");
                    $.ajax({
                        url: "{{ route('get.state') }}",
                        type: "POST",
                        data: {
                            country_id: country_id,
                            _token: "{{ csrf_token() }}",
                        },
                        dataType: "json",
                        success: function(result) {
                            $.each(result.data, function(key, value) {
                                $("#home_state").append('<option value="' + value.id +
                                    '">' +
                                    value.region + "</option>");
                            });
                            $("#home_city").html('<option value="">Select State First</option>');
                        },
                    });
                });

                $("#state").on("change", function() {
                    var state_id = this.value;
                    $("#city").html("");
                    $.ajax({
                        url: "{{ route('get.city') }}",
                        type: "POST",
                        data: {
                            state_id: state_id,
                            _token: "{{ csrf_token() }}",
                        },
                        dataType: "json",
                        success: function(result) {
                            $.each(result.data, function(key, value) {
                                $("#city").append('<option value="' + value.id + '">' +
                                    value.city + "</option>");
                            });
                        },
                    });
                });

                $("#home_state").on("change", function() {
                    var state_id = this.value;
                    $("#home_city").html("");
                    $.ajax({
                        url: "{{ route('get.city') }}",
                        type: "POST",
                        data: {
                            state_id: state_id,
                            _token: "{{ csrf_token() }}",
                        },
                        dataType: "json",
                        success: function(result) {
                            $.each(result.data, function(key, value) {
                                $("#home_city").append('<option value="' + value.id + '">' +
                                    value.city + "</option>");
                            });
                        },
                    });
                });
            });
        </script>

        <script src="{{ asset('public/assets/js/jquery-ui.js') }}"></script>
        <script src="{{ asset('public/assets/js/repeater.js') }}"></script>
        <script>
            var selector = "body";
            if ($(selector + " .repeater").length) {
                var $dragAndDrop = $("body .repeater tbody").sortable({
                    handle: '.sort-handler'
                });
                var $repeater = $(selector + ' .repeater').repeater({
                    initEmpty: false,
                    defaultValues: {
                        'status': 1
                    },
                    show: function() {
                        $(this).slideDown();
                        var file_uploads = $(this).find('input.multi');
                        if (file_uploads.length) {
                            $(this).find('input.multi').MultiFile({
                                max: 3,
                                accept: 'png|jpg|jpeg',
                                max_size: 2048
                            });
                        }
                        if ($('.select2').length) {
                            $('.select2').select2();
                        }

                    },
                    hide: function(deleteElement) {
                        if (confirm('Are you sure you want to delete this element?')) {
                            if ($('.disc_qty').length < 6) {
                                $(".add-row").show();

                            }
                            $(this).slideUp(deleteElement);
                            $(this).remove();

                            var inputs = $(".amount");
                            var subTotal = 0;
                            for (var i = 0; i < inputs.length; i++) {
                                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                            }
                            $('.subTotal').html(subTotal.toFixed(2));
                            $('.totalAmount').html(subTotal.toFixed(2));
                        }
                    },
                    ready: function(setIndexes) {
                        $dragAndDrop.on('drop', setIndexes);
                    },
                    isFirstItemUndeletable: true
                });
                var value = $(selector + " .repeater").attr('data-value');

                if (typeof value != 'undefined' && value.length != 0) {
                    value = JSON.parse(value);
                    $repeater.setList(value);
                }

            }

            $(".add-row").on('click', function(event) {
                var $length = $('.disc_qty').length;
                if ($length == 5) {
                    $(this).hide();
                }
            });
            $(".desc_delete").on('click', function(event) {

                var $length = $('.disc_qty').length;
            });
        </script>
    @endif
@endpush
