@extends('layouts.app')

@section('page-title', $category->name)

@section('action-button')
    <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
        <a href="{{ route('user.legal-library.index') }}" class="btn btn-sm btn-secondary mx-1">
            <i class="ti ti-arrow-left"></i> {{ __('Back to Library') }}
        </a>
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('user.legal-library.index') }}">{{ __('Legal Library') }}</a></li>
    <li class="breadcrumb-item">{{ $category->name }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-header">
                    <h5>{{ $category->name }}</h5>
                    @if($category->description)
                        <p class="text-muted mb-0">{{ $category->description }}</p>
                    @endif
                </div>
                <div class="card-body table-border-style">
                    @if($documents->count() > 0)
                        <div class="table-responsive">
                            <table class="table dataTable data-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Document Title') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('File Size') }}</th>
                                        <th>{{ __('Downloads') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th width="150px">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documents as $document)
                                        <tr>
                                            <td>
                                                <i class="ti ti-file-text text-danger"></i>
                                                <strong>{{ $document->title }}</strong>
                                            </td>
                                            <td>{{ Str::limit($document->description ?? '-', 60) }}</td>
                                            <td>{{ $document->formatted_file_size }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ $document->downloads_count }}</span>
                                            </td>
                                            <td>{{ $document->created_at->format('d M Y') }}</td>
                                            <td>
                                                <div class="d-flex">
                                                    <a href="{{ route('user.legal-library.view', $document->id) }}" 
                                                       class="btn btn-sm btn-success me-2" 
                                                       title="{{ __('View') }}"
                                                       data-bs-toggle="tooltip">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                    <a href="{{ route('user.legal-library.download', $document->id) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="{{ __('Download') }}"
                                                       data-bs-toggle="tooltip">
                                                        <i class="ti ti-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i> {{ __('No documents available in this category yet.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
