@extends('layouts.app')

@section('page-title', __('Create Knowledge'))

@section('action-button')

@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('knowledge') }}">{{ __('Knowledge') }}</a></li>
<li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@php
$chatgpt_enable = App\Models\Utility::getChatGPTSettings();
@endphp

@section('content')
<div class="col-12">
    <div class="card shadow-none rounded-0 border-bottom">
        <div class="card-body">
            @if ($chatgpt_enable)
            <div class="row">
                <div class="float-end">
                    <a href="#" data-size="md" data-ajax-popup-over="true"
                        data-url="{{ route('generate', ['knowledge']) }}" data-bs-toggle="tooltip"
                        data-bs-placement="top" title="{{ __('Generate') }}"
                        data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                        <i class="fas fa-robot"></i>{{ __('Generate with AI') }}
                    </a>
                </div>
            </div>
            @endif

            <form method="post" class="needs-validation" action="{{ route('knowledge.store') }}" novalidate>
                @csrf
                <div class="row">
                    <div class="form-group col-md-6">
                        <label class="form-label">{{ __('Title') }}</label><x-required></x-required>
                        <div class="col-sm-12 col-md-12">
                            <input type="text" placeholder="{{ __('Title of the Knowledge') }}" name="title"
                                class="form-control {{ $errors->has('title') ? ' is-invalid' : '' }}"
                                value="{{ old('title') }}" autofocus required>
                            <div class="invalid-feedback">
                                {{ $errors->first('title') }}
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="form-label">{{ __('Category') }}</label><x-required></x-required>
                        <div class="col-sm-12 col-md-12">
                            <select class="form-select" name="category" required>
                                @foreach ($category as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                @endforeach
                            </select>
                            <div class="text-xs mt-1">
                                Create Category. <a class="dash-link" href="{{ route('knowledgecategory') }}"><b>Click here</b></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label class="form-label">{{ __('Description') }}</label><x-required></x-required>
                        <div class="col-sm-12 col-md-12">
                            <textarea name="description" class="form-control summernote {{ $errors->has('description') ? ' is-invalid' : '' }}"
                                placeholder="{{ __('Enter Description') }}" required>{{ old('description') }}</textarea>
                            <div class="invalid-feedback">
                                {{ $errors->first('description') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label class="form-label"></label>
                        <div class="col-sm-12 col-md-12 text-end">
                            <button class="btn btn-primary btn-block btn-submit">
                                <span>{{ __('Create') }}</span>
                            </button>
                        </div>
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
            ['font', ['bold', 'italic', 'underline', 'strikethrough']],
            ['list', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'unlink']],
        ]
    });
</script>
@endpush