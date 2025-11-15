@extends('layouts.app')

@section('page-title', __('Create Legal Category'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('legal-library.index') }}">{{ __('Legal Library') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Category') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Create New Category') }}</h5>
                </div>
                {{ Form::open(['route' => 'legal-library.category.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            {!! Form::label('name', __('Category Name'), ['class' => 'form-label']) !!}<span class="text-danger">*</span>
                            {!! Form::text('name', null, [
                                'class' => 'form-control',
                                'placeholder' => __('Enter Category Name'),
                                'required' => 'required',
                            ]) !!}
                        </div>
                        <div class="form-group col-md-12">
                            {!! Form::label('description', __('Description'), ['class' => 'form-label']) !!}
                            {!! Form::textarea('description', null, [
                                'class' => 'form-control',
                                'placeholder' => __('Enter Description'),
                                'rows' => '4',
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('legal-library.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Create Category') }}</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
