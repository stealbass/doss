@extends('layouts.app')

@section('page-title', __('Edit Legal Document'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('legal-library.index') }}">{{ __('Legal Library') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('legal-library.documents', $document->category_id) }}">{{ $document->category->name }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Document') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Edit Document') }}: {{ $document->title }}</h5>
                </div>
                {{ Form::model($document, ['route' => ['legal-library.document.update', $document->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
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
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle"></i>
                                {{ __('Current file') }}: <strong>{{ $document->file_name }}</strong> ({{ $document->formatted_file_size }})
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            {!! Form::label('file', __('Replace PDF File (Optional)'), ['class' => 'form-label']) !!}
                            <input type="file" name="file" class="form-control" accept=".pdf">
                            <small class="form-text text-muted">{{ __('Leave empty to keep the current file. Maximum file size: 20MB.') }}</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('legal-library.documents', $document->category_id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update Document') }}</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
