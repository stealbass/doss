@extends('layouts.app')

@section('page-title', __('Upload Legal Document'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('legal-library.index') }}">{{ __('Legal Library') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('legal-library.documents', $category->id) }}">{{ $category->name }}</a></li>
    <li class="breadcrumb-item">{{ __('Upload Document') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Upload New Document to') }}: {{ $category->name }}</h5>
                </div>
                {{ Form::open(['route' => ['legal-library.document.store', $category->id], 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            {!! Form::label('title', __('Document Title'), ['class' => 'form-label']) !!}<span class="text-danger">*</span>
                            {!! Form::text('title', null, [
                                'class' => 'form-control',
                                'placeholder' => __('Enter Document Title'),
                                'required' => 'required',
                            ]) !!}
                        </div>
                        <div class="form-group col-md-12">
                            {!! Form::label('description', __('Description'), ['class' => 'form-label']) !!}
                            {!! Form::textarea('description', null, [
                                'class' => 'form-control',
                                'placeholder' => __('Enter Document Description'),
                                'rows' => '4',
                            ]) !!}
                        </div>
                        <div class="form-group col-md-12">
                            {!! Form::label('file', __('PDF File'), ['class' => 'form-label']) !!}<span class="text-danger">*</span>
                            <input type="file" name="file" class="form-control" accept=".pdf" required>
                            <small class="form-text text-muted">{{ __('Maximum file size: 20MB. Only PDF files are allowed.') }}</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('legal-library.documents', $category->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Upload Document') }}</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
