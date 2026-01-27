@extends('layouts.app')

@section('page-title')
    {{ __('Update Knowledge Category') }} ({{ $knowledge_category->title }})
@endsection

@section('action-button')
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('knowledge') }}">{{ __('Knowledge') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('knowledgecategory') }}">{{ __('Category') }}</a></li>
    <li class="breadcrumb-item">{{ __('Update') }}</li>
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
                                data-url="{{ route('generate', ['knowledge_category']) }}" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="{{ __('Generate') }}"
                                data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                                <i class="fas fa-robot"></i>{{ __('Generate with AI') }}
                            </a>
                        </div>
                    </div>
                @endif
                <form method="post" action="{{ route('knowledgecategory.update', $knowledge_category->id) }}"
                    class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label class="form-label">{{ __('Title') }}</label><x-required></x-required>
                            <div class="col-sm-12 col-md-12">
                                <input type="text" placeholder="{{ __('Title of the Knowledge') }}" name="title"
                                    class="form-control {{ $errors->has('title') ? ' is-invalid' : '' }}"
                                    value="{{ $knowledge_category->title }}" autofocus required>
                                <div class="invalid-feedback">
                                    {{ $errors->first('title') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-12">
                            <label class="form-label"></label>
                            <div class="col-sm-12 col-md-12 text-end">
                                <button class="btn btn-primary btn-block btn-submit">
                                    <span>{{ __('Update') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
