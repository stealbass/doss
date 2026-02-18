@extends('layouts.app')

@section('page-title', __('Legal Documents'))

@section('action-button')
    @php
        // Allow Super Admin, Company users, and SuperAdmin Employees with permission
        $canManageLegalLibrary = \Auth::user()->type == 'super admin' || \Auth::user()->type == 'company';
        if (\Auth::user()->type == 'superAdminEmployee' || (int) \Auth::user()->super_admin_employee === 1) {
            $permissions = json_decode(\Auth::user()->permission_json, true) ?? [];
            $canManageLegalLibrary = in_array('manage legal-library', $permissions) || in_array(3, $permissions);
        }
    @endphp
    @if($canManageLegalLibrary)
        <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
            <a href="{{ route('legal-library.bulk-upload.form', $category->id) }}" 
               class="btn btn-sm btn-success mx-1"
               data-bs-toggle="tooltip"
               title="{{ __('Upload multiple PDF files at once') }}">
                <i class="ti ti-file-upload"></i> {{ __('Import Multiple') }}
            </a>
            <a href="{{ route('legal-library.document.create', $category->id) }}" class="btn btn-sm btn-primary mx-1">
                <i class="ti ti-plus"></i> {{ __('Upload Single') }}
            </a>
            <a href="{{ route('legal-library.index') }}" class="btn btn-sm btn-secondary mx-1">
                <i class="ti ti-arrow-left"></i> {{ __('Back to Categories') }}
            </a>
        </div>
    @endif
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('legal-library.index') }}">{{ __('Legal Library') }}</a></li>
    <li class="breadcrumb-item">{{ $category->name }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-header">
                    <h5>{{ __('Documents in') }}: {{ $category->name }}</h5>
                    @if($category->description)
                        <p class="text-muted">{{ $category->description }}</p>
                    @endif
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary">
                            {{ __('Total Documents') }}: {{ $totalDocuments ?? 0 }}
                        </span>
                        <span class="badge bg-secondary">
                            {{ __('Total Categories') }}: {{ $totalCategories ?? 0 }}
                        </span>
                    </div>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table dataTable data-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('File Size') }}</th>
                                    <th>{{ __('Downloads') }}</th>
                                    <th>{{ __('Uploaded') }}</th>
                                    <th width="200px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($documents as $document)
                                    <tr>
                                        <td>
                                            <strong>{{ $document->title }}</strong>
                                        </td>
                                        <td>{{ Str::limit($document->description ?? '-', 50) }}</td>
                                        <td>{{ $document->formatted_file_size }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ $document->downloads_count }}</span>
                                        </td>
                                        <td>{{ $document->created_at->format('d M Y') }}</td>
                                        <td>
                                            @php
                                                // Allow management (edit/delete) for Super Admin, Company users, and SuperAdmin Employees with permission
                                                $canManageLegalLibrary = \Auth::user()->type == 'super admin' || \Auth::user()->type == 'company';
                                                if (\Auth::user()->type == 'superAdminEmployee' || (int) \Auth::user()->super_admin_employee === 1) {
                                                    $permissions = json_decode(\Auth::user()->permission_json, true) ?? [];
                                                    $canManageLegalLibrary = in_array('manage legal-library', $permissions) || in_array(3, $permissions);
                                                }
                                                
                                                // Check if user can delete
                                                $canDelete = \Auth::user()->type == 'super admin';
                                                if (\Auth::user()->type == 'superAdminEmployee' || (int) \Auth::user()->super_admin_employee === 1) {
                                                    $permissions = json_decode(\Auth::user()->permission_json, true) ?? [];
                                                    $canDelete = in_array('manage legal-library', $permissions) || in_array(3, $permissions);
                                                }
                                            @endphp
                                            <div class="d-flex">
                                                {{-- Download button: available to all authenticated users --}}
                                                <a href="{{ route('legal-library.document.download', $document->id) }}" 
                                                   class="btn btn-sm btn-primary me-2" 
                                                   title="{{ __('Download') }}"
                                                   data-bs-toggle="tooltip">
                                                    <i class="ti ti-download"></i>
                                                </a>
                                                {{-- Edit button: only for Super Admin and Company users --}}
                                                @if($canManageLegalLibrary)
                                                    <a href="{{ route('legal-library.document.edit', $document->id) }}" 
                                                       class="btn btn-sm btn-info me-2"
                                                       title="{{ __('Edit') }}"
                                                       data-bs-toggle="tooltip">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                    {{-- Delete button: for Super Admin and SuperAdmin Employees with permission --}}
                                                    @if($canDelete)
                                                        <a href="#"
                                                           class="btn btn-sm btn-danger bs-pass-para"
                                                           data-confirm="{{ __('Are You Sure?') }}"
                                                           data-text="{{ __('This action cannot be undone. Do you want to continue?') }}"
                                                           data-confirm-yes="delete-form-{{ $document->id }}"
                                                           title="{{ __('Delete') }}"
                                                           data-bs-toggle="tooltip">
                                                            <i class="ti ti-trash"></i>
                                                        </a>
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['legal-library.document.destroy', $document->id],
                                                            'id' => 'delete-form-' . $document->id,
                                                        ]) !!}
                                                        {!! Form::close() !!}
                                                    @endif
                                                @endif
                                            </div>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('No documents uploaded yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
