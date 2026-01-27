@php
    $chatgpt_enable = App\Models\Utility::getChatGPTSettings();
@endphp

@extends('layouts.app')

@section('page-title', __('Reply Ticket'))

@section('action-button')
    <div class="row justify-content-end">
        <div class="col-auto">
            <div class="btn btn-sm btn-info btn-icon m-1 float-end">
                <a href="#ticket-info" class="" type="button" data-bs-toggle="collapse" data-bs-placement="top"
                    title="{{ __('Edit Ticket') }}"><i class="ti ti-pencil text-white"></i>
                </a>
            </div>
        </div>
    </div>
@endsection

@php
    $logo = \App\Models\Utility::get_file('/');
    $setting = App\Models\Utility::settings();
@endphp

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">{{ __('Ticket') }}</a></li>
    <li class="breadcrumb-item">{{ __('Reply') }}</li>
@endsection

@section('content')
    {{ Form::model($ticket, ['route' => ['tickets.update', $ticket->id], 'id' => 'ticket-info', 'class' => 'needs-validation collapse mt-3', 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'autocomplete' => 'off', 'novalidate']) }}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card shadow-none rounded-0 border-bottom">
                <div
                    class="card-header flex-column flex-lg-row  d-flex align-items-lg-center gap-2 justify-content-between">
                    <h6>{{ __('Ticket Information') }}</h6>
                    @if ($chatgpt_enable)
                        <a class="btn btn-primary btn-sm float-end ms-2" href="#" data-size="lg"
                            data-ajax-popup-over="true" data-url="{{ route('generate', ['ticket']) }}"
                            data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                            data-title="{{ __('Generate Content with AI') }}">
                            <i class="fas fa-robot"></i>{{ __('Generate with AI') }}
                        </a>
                    @endif
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Name') }}</label><x-required></x-required>
                            <input class="form-control {{ !empty($errors->first('name')) ? 'is-invalid' : '' }}"
                                type="text" name="name" required="" value="{{ $ticket->name }}"
                                placeholder="{{ __('Name') }}">
                            @if ($errors->has('name'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('name') }}
                                </div>
                            @endif
                        </div>
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Email') }}</label><x-required></x-required>
                            <input class="form-control {{ !empty($errors->first('email')) ? 'is-invalid' : '' }}"
                                type="email" name="email" required="" value="{{ $ticket->email }}"
                                placeholder="{{ __('Email') }}">
                            @if ($errors->has('email'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('email') }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Category') }}</label><x-required></x-required>
                            <select
                                class="form-select {{ !empty($errors->first('category')) ? 'is-invalid' : '' }} multi-select"
                                name="category" required="" id="category">
                                <option value="">{{ __('Select Category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @if ($ticket->category == $category->id) selected @endif>
                                        {{ $category->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('category'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('category') }}
                                </div>
                            @endif
                        </div>
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Status') }}</label><x-required></x-required>
                            <select
                                class="form-select {{ !empty($errors->first('status')) ? 'is-invalid' : '' }} multi-select"
                                name="status" required="" id="status">
                                <option value="New Ticket" @if ($ticket->status == 'New Ticket') selected @endif>
                                    {{ __('New Ticket') }}</option>
                                <option value="In Progress" @if ($ticket->status == 'In Progress') selected @endif>
                                    {{ __('In Progress') }}</option>
                                <option value="On Hold" @if ($ticket->status == 'On Hold') selected @endif>
                                    {{ __('On Hold') }}</option>
                                <option value="Closed" @if ($ticket->status == 'Closed') selected @endif>
                                    {{ __('Closed') }}</option>
                                <option value="Resolved" @if ($ticket->status == 'Resolved') selected @endif>
                                    {{ __('Resolved') }}</option>
                            </select>
                            @if ($errors->has('status'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('status') }}
                                </div>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Subject') }}</label><x-required></x-required>
                            <input class="form-control {{ !empty($errors->first('subject')) ? 'is-invalid' : '' }}"
                                type="text" name="subject" required="" value="{{ $ticket->subject }}"
                                placeholder="{{ __('Subject') }}">
                            @if ($errors->has('subject'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('subject') }}
                                </div>
                            @endif
                        </div>
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Priority') }}</label><x-required></x-required>
                            <select
                                class="form-control {{ !empty($errors->first('priority')) ? 'is-invalid' : '' }} multi-select"
                                name="priority" required="" id="priority">
                                <option value="">{{ __('Select Priority') }}</option>
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->id }}" @if ($ticket->priority == $priority->id) selected @endif>
                                        {{ $priority->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('priority'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('priority') }}
                                </div>
                            @endif
                        </div>
                        @if (Auth::user()->type != 'company')
                            <div class="form-group col-md-6">
                                <label class="require form-label">{{ __('Company') }}</label><x-required></x-required>
                                <select
                                    class="form-control {{ !empty($errors->first('company')) ? 'is-invalid' : '' }} multi-select"
                                    name="company" required="" id="company">
                                    <option value="">{{ __('Select company') }}</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}"
                                            @if ($ticket->company == $company->id) selected @endif>{{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    {{ $errors->first('company') }}
                                </div>
                            </div>
                        @endif
                        <div class="form-group col-md-12">
                            <label class="require form-label">{{ __('Description') }}</label><x-required></x-required>
                            <textarea name="description" id="description"
                                class="form-control summernote {{ !empty($errors->first('description')) ? 'is-invalid' : '' }}">{!! $ticket->description !!}</textarea>
                            @if ($errors->has('description'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('description') }}
                                </div>
                            @endif
                        </div>
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Attachments') }}
                                <small>({{ __('You can select multiple files') }})</small> </label>
                            <div class="choose-file form-group">
                                <label for="file" class="form-label d-block">
                                    <input type="file" name="attachments[]" id="file"
                                        class="form-control mb-2 {{ $errors->has('attachments') ? ' is-invalid' : '' }}"
                                        multiple="" data-filename="multiple_file_selection"
                                        onchange="document.getElementById('blah2').src = window.URL.createObjectURL(this.files[0])">
                                    <img src="" id="blah2" width="20%" />
                                    <div class="invalid-feedback">
                                        {{ $errors->first('attachments') }}
                                    </div>
                                </label>
                            </div>
                            <div class="mx-4">
                                <p class="multiple_file_selection mb-0"></p>
                                <ul class="list-group list-group-flush w-100 attachment_list">
                                    @php $attachments = json_decode($ticket->attachments); @endphp
                                    @if (!empty($attachments))
                                        @foreach ($attachments as $index => $attachment)
                                            <li class="list-group-item px-0 me-3 b-0">
                                                <a download=""
                                                    href="{{ $logo . 'tickets/' . $ticket->ticket_id . '/' . $attachment }}"
                                                    class="btn btn-sm btn-primary d-inline-flex align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Download') }}">
                                                    <i class="ti ti-arrow-bar-to-down me-2"></i> {{ $attachment }}
                                                </a>
                                                <a class="bg-danger ms-2 mx-3 btn btn-sm d-inline-flex align-items-center"
                                                    title="{{ __('Delete') }}"
                                                    onclick="(confirm('Are You Sure?')?(document.getElementById('user-form-{{ $index }}').submit()):'');">
                                                    <i class="ti ti-trash text-white"></i>
                                                </a>
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>
                        @if (!$customFields->isEmpty())
                            @include('customFields.formBuilder')
                        @endif
                    </div>
                    <div class="text-end">
                        <a class="btn btn-secondary mx-1" href="{{ route('tickets.index') }}">{{ __('Cancel') }}</a>
                        <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{ Form::close() }}

    @foreach ($attachments as $index => $attachment)
        <form method="post" id="user-form-{{ $index }}"
            action="{{ route('tickets.attachment.destroy', [$ticket->id, $index]) }}">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    <div class="row mt-3">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card shadow-none rounded-0 border-bottom">
                <div class="card-header">
                    <div class="row justify-content-between align-items-center gap-2">
                        <div class="col-auto">
                            <span class="text-left">
                                <span class="d-block">{{ $ticket->name }}<small>
                                        ({{ $ticket->created_at->diffForHumans() }})</small></span>
                                <span class="d-block"><small>{{ $ticket->email }}</small></span>
                            </span>
                        </div>
                        <div class="col-auto">
                            {{ __('Status') }} :
                            <span
                                class="badge p-2 px-3
                                        @if ($ticket->status == 'New Ticket') bg-secondary
                                        @elseif($ticket->status == 'In Progress')bg-info
                                        @elseif($ticket->status == 'On Hold') bg-warning
                                        @elseif($ticket->status == 'Closed') bg-primary
                                        @else bg-success @endif">
                                {{ __($ticket->status) }}
                            </span>
                            {{ __('Category') }} :
                            <span class="badge p-2 px-3"
                                style="background: {{ $ticket->tcategory ? $ticket->tcategory->color : 'bg-primary' }};">
                                {{ $ticket->tcategory ? $ticket->tcategory->name : '-' }}
                            </span>
                            {{ __('Priority') }} :
                            <span class="badge p-2 px-3"
                                style="background: {{ $ticket->priorities ? $ticket->priorities->color : 'bg-primary' }}">
                                {{ $ticket->priorities ? $ticket->priorities->name : '-' }}
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        @foreach ($customFields as $field)
                            <div class="col-6">
                                <small>
                                    <span class="text-right">
                                        {{ $field->name }} : {!! isset($ticket->customField[$field->id]) && !empty($ticket->customField[$field->id])
                                            ? $ticket->customField[$field->id]
                                            : '-' !!}
                                    </span>
                                </small>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <p>{!! $ticket->description !!}</p>
                    </div>
                    @php $attachments = json_decode($ticket->attachments); @endphp
                    @if (count($attachments))
                        <div class="m-1">
                            <h6>{{ __('Attachments') }} :</h6>
                            <ul class="list-group list-group-flush">
                                @foreach ($attachments as $index => $attachment)
                                    <li class="list-group-item px-0">
                                        {{ $attachment }}
                                        <a download=""
                                            href="{{ $logo . 'tickets/' . $ticket->ticket_id . '/' . $attachment }}"
                                            class="edit-icon py-1 ml-2" title="{{ __('Download') }}">
                                            <i class="fas fa-download ms-2"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
            @foreach ($ticket->conversions as $conversion)
                <div class="card shadow-none rounded-0 border-bottom">
                    <div class="card-header">
                        <h6>{{ $conversion->replyBy()->name }}
                            <small>({{ $conversion->created_at->diffForHumans() }})</small>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div>{!! $conversion->description !!}</div>
                        @php $attachments = json_decode($conversion->attachments); @endphp
                        @if (count($attachments))
                            <div class="m-1">
                                <h6>{{ __('Attachments') }} :</h6>
                                <ul class="list-group list-group-flush">
                                    @foreach ($attachments as $index => $attachment)
                                        <li class="list-group-item px-0">
                                            {{ $attachment }}
                                            <a download=""
                                                href="{{ $logo . 'reply_tickets/' . $ticket->id . '/' . $attachment }}"
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
        </div>
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="row g-0">
                <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    @if ($ticket->status != 'Closed')
                        <div class="card shadow-none rounded-0 border">
                            <div
                                class="card-header flex-column flex-lg-row  d-flex align-items-lg-center gap-2 justify-content-between">
                                <h6>{{ __('Add Reply') }}</h6>
                                @if ($chatgpt_enable)
                                    <div class="text-end">
                                        <a href="#" class="btn btn-sm btn-primary" data-size="medium"
                                            data-ajax-popup-over="true" data-url="{{ route('grammar', ['grammar']) }}"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="{{ __('Grammar check with AI') }}"
                                            data-title="{{ __('Grammar check with AI') }}">
                                            <i class="fas fa-robot"></i>{{ __(' Grammar check with AI') }}
                                        </a>
                                        <a href="#" class="btn btn-sm btn-primary" data-size="medium"
                                            data-ajax-popup-over="true" data-url="{{ route('generate', ['reply']) }}"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="{{ __('Generate') }}"
                                            data-title="{{ __('Generate Content With AI') }}">
                                            <i class="fas fa-robot"></i>{{ __(' Generate With AI') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                            <form method="post" action="{{ route('conversion.store', $ticket->id) }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="require form-label">{{ __('Description') }}</label>
                                        <textarea name="reply_description" id="reply_description" class="form-control summernote " required></textarea>
                                        <div class="invalid-feedback d-block">
                                            {{ $errors->first('reply_description') }}
                                        </div>
                                    </div>
                                    <div class="form-group file-group ">
                                        <label class="require form-label">{{ __('Attachments') }}</label>
                                        <label
                                            class="form-label"><small>({{ __('You can select multiple files') }})</small></label>
                                        <div class="choose-file form-group">
                                            <label for="file" class="form-label d-block">
                                                <div>{{ __('Choose File Here') }}</div>

                                                <input type="file" name="reply_attachments[]" id="file"
                                                    class="form-control mb-2 {{ $errors->has('reply_attachments') ? ' is-invalid' : '' }}"
                                                    multiple="" data-filename="multiple_reply_file_selection"
                                                    onchange="document.getElementById('blah').src = window.URL.createObjectURL(this.files[0])">
                                                <img src="" id="blah" width="20%" />
                                                <div class="invalid-feedback">
                                                    {{ $errors->first('reply_attachments.*') }}
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <p class="multiple_reply_file_selection"></p>
                                    <div class="text-end">
                                        <button class="btn btn-primary btn-block mt-2 btn-submit"
                                            type="submit">{{ __('Submit') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
                <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <div class="card shadow-none rounded-0 border">
                        <div
                            class="card-header flex-column flex-lg-row  d-flex align-items-lg-center gap-2 justify-content-between">
                            <h6>{{ __('Note') }}</h6>
                            @if ($chatgpt_enable)
                                <div class="text-end">
                                    <a href="#" class="btn btn-sm btn-primary" data-size="medium"
                                        data-ajax-popup-over="true" data-url="{{ route('generate', ['note']) }}"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                                        data-title="{{ __('Generate Content With AI') }}">
                                        <i class="fas fa-robot"></i>{{ __(' Generate With AI') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                        <form method="post" action="{{ route('note.store', $ticket->id) }}">
                            @csrf
                            <div class="card-body adjust_card_width">
                                <div class="form-group ckfix_height">
                                    <textarea name="note" class="form-control summernote" id="note">{{ $ticket->note }}</textarea>
                                    <div class="invalid-feedback">
                                        {{ $errors->first('note') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button class="btn btn-primary btn-block mt-2 btn-submit"
                                        type="submit">{{ __('Add Note') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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
                ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                ['list', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'unlink']],
            ]
        });
    </script>
@endpush
