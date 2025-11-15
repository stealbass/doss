@extends('layouts.app')

@section('page-title', $document->title)

@section('action-button')
    <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
        <a href="{{ route('user.legal-library.download', $document->id) }}" class="btn btn-sm btn-primary mx-1">
            <i class="ti ti-download"></i> {{ __('Download') }}
        </a>
        <a href="{{ route('user.legal-library.category', $document->category_id) }}" class="btn btn-sm btn-secondary mx-1">
            <i class="ti ti-arrow-left"></i> {{ __('Back') }}
        </a>
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('user.legal-library.index') }}">{{ __('Legal Library') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('user.legal-library.category', $document->category_id) }}">{{ $document->category->name }}</a></li>
    <li class="breadcrumb-item">{{ $document->title }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4>{{ $document->title }}</h4>
                    <div class="mt-2">
                        <span class="badge bg-primary me-2">{{ $document->category->name }}</span>
                        <span class="badge bg-info me-2">
                            <i class="ti ti-download"></i> {{ $document->downloads_count }} {{ __('downloads') }}
                        </span>
                        <span class="badge bg-secondary">
                            <i class="ti ti-file"></i> {{ $document->formatted_file_size }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    @if($document->description)
                        <div class="mb-4">
                            <h6>{{ __('Description') }}:</h6>
                            <p class="text-muted">{{ $document->description }}</p>
                        </div>
                        <hr>
                    @endif

                    <!-- Document Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>{{ __('File Name') }}:</strong> {{ $document->file_name }}</p>
                            <p><strong>{{ __('Category') }}:</strong> {{ $document->category->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('Size') }}:</strong> {{ $document->formatted_file_size }}</p>
                            <p><strong>{{ __('Uploaded') }}:</strong> {{ $document->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>

                    <!-- PDF Preview -->
                    <div class="pdf-viewer-container">
                        <h6 class="mb-3">{{ __('Document Preview') }}:</h6>
                        <div class="ratio ratio-16x9" style="min-height: 600px;">
                            <iframe 
                                src="{{ asset('storage/' . $document->file_path) }}" 
                                type="application/pdf" 
                                width="100%" 
                                height="100%"
                                style="border: 1px solid #ddd; border-radius: 5px;">
                                <p>{{ __('Your browser does not support PDF preview.') }} 
                                    <a href="{{ route('user.legal-library.download', $document->id) }}">{{ __('Download the PDF') }}</a>
                                </p>
                            </iframe>
                        </div>
                    </div>

                    <!-- Alternative: Embed with object -->
                    <div class="mt-3 text-center">
                        <a href="{{ route('user.legal-library.download', $document->id) }}" class="btn btn-lg btn-primary">
                            <i class="ti ti-download"></i> {{ __('Download Document') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('style')
    <style>
        .pdf-viewer-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
    </style>
    @endpush
@endsection
