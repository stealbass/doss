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
    {{ __('Search Ticket') }} - {{ $ticket->ticket_id }}
@endsection

@section('title-content')
    <h2 class="text-center p-0 m-5 " style="color: #fff">{{ __('Search Ticket') }} - {{ $ticket->ticket_id }}</h2>
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
                        <a class="nav-link" href="{{ route('user.ticket.search') }}">{{ __('Search Ticket') }}</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">{{ __('FAQ') }}</a>
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
    <div class="col-xl-12 text-start">
        <div class="card">
            @csrf
            <div class="card m-3">
                <div class="card-header">
                    <h6>{{ $ticket->name }} <small>({{ $ticket->created_at->diffForHumans() }})</small></h6>
                </div>
                <div class="card-body w-100">
                    <div>
                        <p>{!! $ticket->description !!}</p>
                    </div>
                    @php
                        $attachments = json_decode($ticket->attachments);
                    @endphp
                    @if (!is_null($attachments) && count($attachments) > 0)
                        <div class="m-1 ml-3">
                            <b>{{ __('Attachments') }} :</b>
                            <ul class="list-group list-group-flush">
                                @foreach ($attachments as $index => $attachment)
                                    <li class="list-group-item px-0">
                                        {{ $attachment }}
                                        <a download=""
                                            href="{{ asset(Storage::url('tickets/' . $ticket->ticket_id . '/' . $attachment)) }}"
                                            class="edit-icon py-1 ml-2" title="{{ __('Download') }}">
                                            <i class="fa fa-download ms-2"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
            @foreach ($ticket->conversions as $conversion)
                <div class="card m-3">
                    <div class="card-header">
                        <h6>{{ $conversion->replyBy()->name }}
                            <small>({{ $conversion->created_at->diffForHumans() }})</small>
                        </h6>
                    </div>
                    <div class="card-body w-100">
                        <div>{!! $conversion->description !!}</div>
                        @php
                            $attachments = json_decode($conversion->attachments);
                        @endphp
                        @if (count($attachments))
                            <div class="m-1">
                                <b>{{ __('Attachments') }} :</b>
                                <ul class="list-group list-group-flush">
                                    @foreach ($attachments as $index => $attachment)
                                        <li class="list-group-item px-0">
                                            {{ $attachment }}
                                            <a download=""
                                                href="@if ($conversion->sender != 'user') {{ asset(Storage::url('reply_tickets/' . $ticket->id . '/' . $attachment)) }} @else {{ asset(Storage::url('tickets/' . $ticket->ticket_id . '/' . $attachment)) }} @endif"
                                                class="edit-icon py-1 ml-2" title="{{ __('Download') }}">
                                                <i class="fa fa-download ms-2"></i>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
            @if ($ticket->status != 'Closed')
                <div class="card m-3">
                    <div class="card-body w-100">
                        <form method="post" action="{{ route('user.ticket.reply', $ticket->ticket_id) }}"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="require form-label">{{ __('Description') }}</label><x-required></x-required>
                                    <textarea name="reply_description"
                                        class="form-control summernote {{ $errors->has('reply_description') ? ' is-invalid' : '' }}">{{ old('reply_description') }}</textarea>
                                    <div class="invalid-feedback">
                                        {{ $errors->first('reply_description') }}
                                    </div>
                                </div>
                                <div class="form-group col-md-12 file-group">
                                    <label class="require form-label">{{ __('Attachments') }}</label>
                                    <label
                                        class="form-label"><small>({{ __('You can select multiple files') }})</small></label>
                                    <div class="choose-file form-group">
                                        <label for="file" class="form-label">
                                            <div>{{ __('Choose File Here') }}</div>
                                            <input type="file"
                                                class="form-control {{ $errors->has('reply_attachments') ? 'is-invalid' : '' }}"
                                                multiple="" name="reply_attachments[]" id="file"
                                                data-filename="multiple_reply_file_selection">
                                            <div class="invalid-feedback">
                                                {{ $errors->first('reply_attachments') }}
                                            </div>
                                        </label>
                                        <p class="multiple_reply_file_selection"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <div class="text-center">
                                    <input type="hidden" name="status" value="New Ticket" />
                                    <button class="btn btn-submit btn-primary btn-block mt-2">{{ __('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <p class="text-blue font-weight-bold text-center mb-0">
                            {{ __('Ticket is closed you cannot replay.') }}</p>
                    </div>
                </div>
            @endif
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
