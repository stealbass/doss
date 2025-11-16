@extends('layouts.app')

@section('page-title', __('Legal Library - Categories'))

@section('action-button')
    @if(\Auth::user()->type == 'super admin')
        <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
            <a href="{{ route('legal-library.category.create') }}" class="btn btn-sm btn-primary mx-1">
                <i class="ti ti-plus"></i> {{ __('Create Category') }}
            </a>
        </div>
    @endif
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Legal Library') }}</li>
    <li class="breadcrumb-item">{{ __('Categories') }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table dataTable data-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Category Name') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Documents Count') }}</th>
                                    <th width="200px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $category)
                                    <tr>
                                        <td>
                                            <a href="{{ route('legal-library.documents', $category->id) }}" 
                                               class="btn btn-sm btn-link">
                                                <strong>{{ $category->name }}</strong>
                                            </a>
                                        </td>
                                        <td>{{ Str::limit($category->description ?? '-', 50) }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $category->documents_count }}</span>
                                        </td>
                                        <td>
                                            @if(\Auth::user()->type == 'super admin')
                                                <div class="d-flex">
                                                    <a href="{{ route('legal-library.documents', $category->id) }}" 
                                                       class="btn btn-sm btn-success me-2" 
                                                       title="{{ __('View Documents') }}"
                                                       data-bs-toggle="tooltip">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                    <a href="{{ route('legal-library.category.edit', $category->id) }}" 
                                                       class="btn btn-sm btn-info me-2"
                                                       title="{{ __('Edit Category') }}"
                                                       data-bs-toggle="tooltip">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                    <a href="#"
                                                       class="btn btn-sm btn-danger bs-pass-para"
                                                       data-confirm="{{ __('Are You Sure?') }}"
                                                       data-text="{{ __('This will delete the category and all its documents. This action cannot be undone.') }}"
                                                       data-confirm-yes="delete-form-{{ $category->id }}"
                                                       title="{{ __('Delete') }}"
                                                       data-bs-toggle="tooltip">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                    {!! Form::open([
                                                        'method' => 'DELETE',
                                                        'route' => ['legal-library.category.destroy', $category->id],
                                                        'id' => 'delete-form-' . $category->id,
                                                    ]) !!}
                                                    {!! Form::close() !!}
                                                </div>
                                            @endif
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
@endsection
