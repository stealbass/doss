@php
    use App\Models\Utility;
    $logo = App\Models\Utility::get_file('uploads/logo');
    $setting = Utility::colorset();
    $mode_setting = Utility::mode_layout();
    $company_logo = Utility::get_company_logo();
    $company_logos = Utility::getValByName('company_logo_light');
    $settings = Utility::settings();
@endphp

@extends('layouts.custom_guest')

@section('page-title')
    {{ __('Search Ticket') }}
@endsection

@section('title-content')
    <h2 class="text-center p-0 m-5" style="color: #fff">{{ __('Search Ticket') }}</h2>
@endsection

@section('nav-content')
    <nav class="navbar navbar-expand-md navbar-dark default dark_background_color">
        <div class="container-fluid pe-2">
            <a class="navbar-brand" href="{{ route('user.ticket.create') }}">
                @if ($mode_setting['cust_darklayout'] && $mode_setting['cust_darklayout'] == 'on')
                    <img src="{{ $logo . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') . '?' . time() }}"
                        alt="{{ config('app.name', 'Smart Chamber-SaaS') }}" class="logo "
                        style="height: 30px; width: 180px;">
                @else
                    <img src="{{ $logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') . '?' . time() }}"
                        alt="{{ config('app.name', 'Smart Chamber-SaaS') }}" class="logo "
                        style="height: 30px; width: 180px;">
                @endif
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo01"
                aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTogglerDemo01" style="flex-grow: 0;">
                <ul class="navbar-nav align-items-center ms-auto mb-2 mb-lg-0">
                    <li class="nav-item ">
                        <a class="nav-link" href="{{ route('user.ticket.create') }}">{{ __('Create Ticket') }}</a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link" href="#">{{ __('Search Ticket') }}</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('user.faq') }}" class="nav-link">{{ __('FAQ') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('user.knowledge') }}">{{ __('Knowledge') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
@endsection

@section('content')
    <div class="align-items-center col-12 justify-content-center d-flex mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('ticket.search') }}">
                    @csrf
                    @if (session()->has('info'))
                        <div class="alert alert-danger">
                            {{ session()->get('info') }}
                        </div>
                    @endif
                    @if (session()->has('status'))
                        <div class="alert alert-info">
                            {{ session()->get('status') }}
                        </div>
                    @endif
                    <div class="">
                        <div class="form-group mb-3">
                            <label for="ticket_id" class="form-label">{{ __('Ticket Number') }}</label>
                            <input type="number" class="form-control {{ $errors->has('ticket_id') ? 'is-invalid' : '' }}"
                                min="0" id="ticket_id" name="ticket_id"
                                placeholder="{{ __('Enter Ticket Number') }}" required=""
                                value="{{ old('ticket_id') }}" autofocus>
                            <div class="invalid-feedback d-block">
                                {{ $errors->first('ticket_id') }}
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">{{ __('Email') }}</label>
                            <input type="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                id="email" name="email" placeholder="{{ __('Email address') }}" reuired=""
                                value="{{ old('email') }}">
                            <div class="invalid-feedback d-block">
                                {{ $errors->first('email') }}
                            </div>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-primary btn-submit btn-block mt-2">{{ __('Search') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('custom-script')
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
    <script>
        $('.summernote').summernote({
            dialogsInBody: !0,
            minHeight: 250,
            toolbar: [
                ['style', ['style']],
                ["font", ["bold", "italic", "underline", "clear", "strikethrough"]],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ["para", ["ul", "ol", "paragraph"]],
            ]
        });
    </script>
@endpush
