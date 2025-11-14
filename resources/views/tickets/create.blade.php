@php
    $chatgpt_enable = App\Models\Utility::getChatGPTSettings();
@endphp

@extends('layouts.app')

@section('page-title', __('Create Ticket'))

@section('action-button')

@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('tickets.index') }}">{{ __('Ticket') }}</a>
    </li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
    {{ Form::open(['route' => 'tickets.store', 'method' => 'post', 'id' => 'frmTarget', 'enctype' => 'multipart/form-data', 'autocomplete' => 'off', 'class' => 'needs-validation', 'novalidate']) }}
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-lg-10">
            @if ($chatgpt_enable)
                <div class="row text-end my-2">
                    <div class="col-12 ">
                        <div class="text-end">
                            <a href="#" class="btn btn-sm btn-primary" data-size="medium" data-ajax-popup-over="true"
                                data-url="{{ route('generate', ['ticket']) }}" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="{{ __('Generate') }}"
                                data-title="{{ __('Generate Content With AI') }}">
                                <i class="fas fa-robot"></i>{{ __(' Generate With AI') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card shadow-none rounded-0 border">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Name') }}</label><x-required></x-required>
                            <input class="form-control {{ !empty($errors->first('name')) ? 'is-invalid' : '' }}"
                                type="text" name="name" required="" placeholder="{{ __('Name') }}">
                            <div class="invalid-feedback">
                                {{ $errors->first('name') }}
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Email') }}</label><x-required></x-required>
                            <input class="form-control {{ !empty($errors->first('email')) ? 'is-invalid' : '' }}"
                                type="email" name="email" required="" placeholder="{{ __('Email') }}">
                            <div class="invalid-feedback">
                                {{ $errors->first('email') }}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Category') }}</label><x-required></x-required>
                            <select
                                class="form-control {{ !empty($errors->first('category')) ? 'is-invalid' : '' }} multi-select"
                                name="category" required="" id="category">
                                <option value="">{{ __('Select Category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">
                                {{ $errors->first('category') }}
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Status') }}</label><x-required></x-required>
                            <select
                                class="form-control {{ !empty($errors->first('status')) ? 'is-invalid' : '' }} multi-select"
                                name="status" required="" id="status">
                                <option value="">{{ __('Select Status') }}</option>
                                <option value="New Ticket">{{ __('New Ticket') }}</option>
                                <option value="In Progress">{{ __('In Progress') }}</option>
                                <option value="On Hold">{{ __('On Hold') }}</option>
                                <option value="Closed">{{ __('Closed') }}</option>
                                <option value="Resolved">{{ __('Resolved') }}</option>
                            </select>
                            <div class="invalid-feedback">
                                {{ $errors->first('status') }}
                            </div>
                        </div>

                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Subject') }}</label><x-required></x-required>
                            <input class="form-control {{ !empty($errors->first('subject')) ? 'is-invalid' : '' }}"
                                type="text" name="subject" required="" placeholder="{{ __('Subject') }}">
                            <div class="invalid-feedback">
                                {{ $errors->first('subject') }}
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Priority') }}</label><x-required></x-required>
                            <select
                                class="form-control {{ !empty($errors->first('priority')) ? 'is-invalid' : '' }} multi-select"
                                name="priority" required="" id="priority">
                                <option value="">{{ __('Select Priority') }}</option>
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">
                                {{ $errors->first('priority') }}
                            </div>
                        </div>
                        @if (Auth::user()->type != 'company')
                            <div class="form-group col-md-6">
                                <label class="require form-label">{{ __('Company') }}</label><x-required></x-required>
                                <select
                                    class="form-control {{ !empty($errors->first('company')) ? 'is-invalid' : '' }} multi-select"
                                    name="company" required="" id="company">
                                    <option value="">{{ __('Select company') }}</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    {{ $errors->first('company') }}
                                </div>
                            </div>
                        @endif
                        <div class="form-group col-md-12">
                            <label class="require form-label">{{ __('Description') }}</label><x-required></x-required>
                            <textarea name="description" id="description" required
                                class="form-control summernote {{ !empty($errors->first('description')) ? 'is-invalid' : '' }}"></textarea>
                            <div class="invalid-feedback">
                                {{ $errors->first('description') }}
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="require form-label">{{ __('Attachments') }}
                                <small>({{ __('You can select multiple files') }})</small> </label>
                            <div class="choose-file form-group">
                                <label for="file" class="form-label d-block">
                                    <input type="file" name="attachments[]" id="file"
                                        class="form-control mb-2 {{ $errors->has('attachments') ? ' is-invalid' : '' }}"
                                        multiple="" data-filename="multiple_file_selection"
                                        onchange="document.getElementById('blah').src = window.URL.createObjectURL(this.files[0])">
                                    <img src="" id="blah" width="20%" />
                                    <div class="invalid-feedback">
                                        {{ $errors->first('attachments.*') }}
                                    </div>
                                </label>
                            </div>
                            <p class="multiple_file_selection mx-4"></p>
                        </div>
                        @if (!$customFields->isEmpty())
                            @include('customFields.formBuilder')
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
        <div class="col-md-1"></div>
        <div class="col-lg-10">
            <div class="card shadow-none rounded-0 border ">
                <div class="card-body p-2">
                    <div class="form-group col-12 d-flex justify-content-end col-form-label mb-0">
                        <a href="{{ route('tickets.index') }}" class="btn btn-secondary mx-1">{{ __('Cancel') }}</a>
                        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{ Form::close() }}
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
