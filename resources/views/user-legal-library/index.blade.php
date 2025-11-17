@extends('layouts.app')

@section('page-title', __('Legal Library'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Legal Library') }}</li>
@endsection

@section('content')
    <div class="row p-0">
        @php
            $hasFreePlan = Auth::user()->hasFreePlan();
        @endphp

        @if($hasFreePlan)
        <!-- Free Plan Alert -->
        <div class="col-xl-12 mb-4">
            <div class="alert alert-warning" style="background: linear-gradient(135deg, #fff3cd 0%, #ffe6a8 100%); border: 2px solid #ffc107;">
                <div class="d-flex align-items-center">
                    <div style="font-size: 40px; margin-right: 15px;">🔒</div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1" style="color: #856404;">
                            <i class="ti ti-crown"></i> Accès Limité - Plan Gratuit
                        </h5>
                        <p class="mb-2" style="color: #856404; font-size: 14px;">
                            Vous pouvez consulter les catégories, mais l'accès aux documents nécessite un abonnement premium.
                        </p>
                        <a href="{{ route('plans.index') }}" class="btn btn-sm btn-warning">
                            <i class="ti ti-credit-card"></i> Souscrire à un Plan Premium
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Search Section -->
        <div class="col-xl-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('user.legal-library.index') }}">
                        <div class="row align-items-center">
                            <div class="col-md-10">
                                <input type="text" 
                                       name="search" 
                                       class="form-control" 
                                       placeholder="{{ __('Search legal documents by title or description...') }}"
                                       value="{{ $search ?? '' }}"
                                       @if($hasFreePlan) disabled title="Recherche disponible uniquement pour les plans premium" @endif>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100" @if($hasFreePlan) disabled @endif>
                                    <i class="ti ti-search"></i> {{ __('Search') }}
                                </button>
                            </div>
                        </div>
                        @if($hasFreePlan)
                        <small class="text-muted mt-2 d-block">
                            <i class="ti ti-info-circle"></i> La recherche de documents est disponible uniquement avec un plan premium.
                        </small>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        @if($search && $documents)
            <!-- Search Results -->
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('Search Results for') }}: "{{ $search }}"</h5>
                        <a href="{{ route('user.legal-library.index') }}" class="btn btn-sm btn-secondary">
                            {{ __('Clear Search') }}
                        </a>
                    </div>
                    <div class="card-body">
                        @if($documents->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Document') }}</th>
                                            <th>{{ __('Category') }}</th>
                                            <th>{{ __('Size') }}</th>
                                            <th>{{ __('Downloads') }}</th>
                                            <th width="150px">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($documents as $document)
                                            <tr>
                                                <td>
                                                    <strong>{{ $document->title }}</strong>
                                                    @if($document->description)
                                                        <br><small class="text-muted">{{ Str::limit($document->description, 60) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">{{ $document->category->name }}</span>
                                                </td>
                                                <td>{{ $document->formatted_file_size }}</td>
                                                <td>
                                                    <span class="badge bg-info">{{ $document->downloads_count }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('user.legal-library.view', $document->id) }}" 
                                                       class="btn btn-sm btn-success me-1"
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
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle"></i> {{ __('No documents found matching your search.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <!-- Categories Grid -->
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('Browse by Category') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($categories->count() > 0)
                            <div class="row">
                                @foreach($categories as $category)
                                    <div class="col-md-4 col-sm-6 mb-4">
                                        <div class="card h-100 category-card">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="ti ti-folder text-primary"></i>
                                                    {{ $category->name }}
                                                </h5>
                                                @if($category->description)
                                                    <p class="card-text text-muted">{{ Str::limit($category->description, 100) }}</p>
                                                @endif
                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                    <span class="badge bg-primary">
                                                        {{ $category->documents_count }} {{ __('document(s)') }}
                                                    </span>
                                                    @if($hasFreePlan)
                                                        <button class="btn btn-sm btn-outline-secondary" 
                                                                disabled 
                                                                title="Abonnement premium requis">
                                                            <i class="ti ti-lock"></i> {{ __('Premium') }}
                                                        </button>
                                                    @else
                                                        <a href="{{ route('user.legal-library.category', $category->id) }}" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            {{ __('Browse') }} <i class="ti ti-arrow-right"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle"></i> {{ __('No categories available yet.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('style')
    <style>
        .category-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
    </style>
    @endpush
@endsection
