@extends('layouts.app')

@section('page-title', __('Mobile Legal Library Sync'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Mobile Legal Library') }}</li>
@endsection

@section('action-btn')
    <a href="{{ route('mobile-legal-library.export') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-download"></i> {{ __('Export CSV') }}
    </a>
    <button type="button" class="btn btn-sm btn-success" onclick="forceSyncAll()">
        <i class="ti ti-refresh"></i> {{ __('Sync All to Mobile') }}
    </button>
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Total Documents') }}</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_documents']) }}</h3>
                        </div>
                        <div class="avatar bg-primary-lt rounded">
                            <i class="ti ti-files text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Mobile Visible') }}</h6>
                            <h3 class="mb-0 text-success">{{ number_format($stats['mobile_visible']) }}</h3>
                        </div>
                        <div class="avatar bg-success-lt rounded">
                            <i class="ti ti-eye text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        {{ $stats['total_documents'] > 0 ? number_format(($stats['mobile_visible'] / $stats['total_documents']) * 100, 1) : 0 }}% {{ __('of total') }}
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Countries') }}</h6>
                            <h3 class="mb-0 text-primary">{{ number_format($stats['total_countries'] ?? 0) }}</h3>
                        </div>
                        <div class="avatar bg-primary-lt rounded">
                            <i class="ti ti-world text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        {{ count($countries) }} {{ __('supported') }}
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Categories') }}</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_categories']) }}</h3>
                        </div>
                        <div class="avatar bg-info-lt rounded">
                            <i class="ti ti-folders text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        {{ $stats['active_categories'] }} {{ __('active') }}
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Last Sync') }}</h6>
                            <h6 class="mb-0">
                                {{ $stats['last_sync_date'] ? \Carbon\Carbon::parse($stats['last_sync_date'])->diffForHumans() : __('Never') }}
                            </h6>
                        </div>
                        <div class="avatar bg-warning-lt rounded">
                            <i class="ti ti-clock text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        {{ $stats['total_syncs_30days'] }} {{ __('syncs in 30 days') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Countries Distribution (if documents are categorized by country) -->
    @if(isset($stats['documents_per_country']) && count($stats['documents_per_country']) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-world"></i> {{ __('Documents by Country') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($stats['documents_per_country'] as $countryCode => $count)
                            @php
                                $countryInfo = $countries[$countryCode] ?? null;
                            @endphp
                            @if($countryInfo)
                            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                                <div class="border rounded p-2 text-center">
                                    <div style="font-size: 2rem;">{{ $countryInfo['flag'] }}</div>
                                    <div class="fw-bold">{{ $countryInfo['name'] }}</div>
                                    <div class="text-muted small">{{ number_format($count) }} {{ __('docs') }}</div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Categories Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('Categories Overview') }}</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Category') }}</th>
                                    <th class="text-center">{{ __('Total Docs') }}</th>
                                    <th class="text-center">{{ __('Mobile Visible') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-center">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    <tr>
                                        <td>
                                            <strong>{{ $category->name }}</strong>
                                            @if($category->description)
                                                <br><small class="text-muted">{{ Str::limit($category->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $category->documents_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $category->documents_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($category->is_mobile_visible ?? true)
                                                <span class="badge bg-success">{{ __('Synced') }}</span>
                                            @else
                                                <span class="badge bg-warning">{{ __('Not Synced') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary" onclick="syncCategory({{ $category->id }})">
                                                <i class="ti ti-refresh"></i> {{ __('Sync') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('No categories found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('Documents Management') }}</h4>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="{{ __('Search documents...') }}" 
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="country">
                                    <option value="">{{ __('All Countries') }}</option>
                                    @foreach($countries as $code => $country)
                                        <option value="{{ $code }}" {{ request('country') == $code ? 'selected' : '' }}>
                                            {{ $country['flag'] }} {{ $country['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="category_id">
                                    <option value="">{{ __('All Categories') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="mobile_status">
                                    <option value="">{{ __('All Status') }}</option>
                                    <option value="visible" {{ request('mobile_status') === 'visible' ? 'selected' : '' }}>{{ __('Mobile Visible') }}</option>
                                    <option value="hidden" {{ request('mobile_status') === 'hidden' ? 'selected' : '' }}>{{ __('Mobile Hidden') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ti ti-filter"></i> {{ __('Filter') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Documents Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th class="text-center">{{ __('Country') }}</th>
                                    <th class="text-center">{{ __('Type') }}</th>
                                    <th class="text-center">{{ __('Mobile Status') }}</th>
                                    <th class="text-center">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $document)
                                    <tr>
                                        <td>
                                            <strong>{{ $document->title }}</strong>
                                            @if($document->description)
                                                <br><small class="text-muted">{{ Str::limit($document->description, 60) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $document->category->name ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            @if($document->country && isset($countries[$document->country]))
                                                <span class="badge bg-primary" style="font-size: 1rem;">
                                                    {{ $countries[$document->country]['flag'] }}
                                                </span>
                                                <br>
                                                <small class="text-muted">{{ $countries[$document->country]['name'] }}</small>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                        onclick="assignCountry({{ $document->id }})">
                                                    <i class="ti ti-world-plus"></i> {{ __('Assign') }}
                                                </button>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ strtoupper($document->file_type ?? 'PDF') }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($document->is_mobile_visible ?? true)
                                                <span class="badge bg-success">
                                                    <i class="ti ti-eye"></i> {{ __('Visible') }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="ti ti-eye-off"></i> {{ __('Hidden') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-{{ ($document->is_mobile_visible ?? true) ? 'warning' : 'success' }}" 
                                                    onclick="toggleVisibility({{ $document->id }})">
                                                <i class="ti ti-{{ ($document->is_mobile_visible ?? true) ? 'eye-off' : 'eye' }}"></i>
                                                {{ ($document->is_mobile_visible ?? true) ? __('Hide') : __('Show') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('No documents found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $documents->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function toggleVisibility(documentId) {
        if (confirm('{{ __("Toggle mobile visibility for this document?") }}')) {
            fetch(`/mobile-legal-library/${documentId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __("An error occurred. Please try again.") }}');
            });
        }
    }

    function syncCategory(categoryId) {
        if (confirm('{{ __("Sync all documents in this category to mobile?") }}')) {
            fetch(`/mobile-legal-library/category/${categoryId}/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ is_mobile_visible: true })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __("An error occurred. Please try again.") }}');
            });
        }
    }

    function forceSyncAll() {
        if (confirm('{{ __("This will make ALL documents visible on mobile. Continue?") }}')) {
            window.location.href = '{{ route('mobile-legal-library.force-sync') }}';
        }
    }

    function assignCountry(documentId) {
        const countries = @json($countries);
        let options = '';
        for (const [code, country] of Object.entries(countries)) {
            options += `<option value="${code}">${country.flag} ${country.name}</option>`;
        }
        
        const countryCode = prompt('{{ __("Select country code (e.g., SN for Sénégal, CM for Cameroun):") }}\n\n' + 
            Object.entries(countries).map(([code, c]) => `${code} = ${c.flag} ${c.name}`).join('\n'));
        
        if (countryCode && countryCode.length === 2) {
            fetch(`/mobile-legal-library/document/${documentId}/update-country`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ country: countryCode.toUpperCase() })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || '{{ __("An error occurred. Please try again.") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __("An error occurred. Please try again.") }}');
            });
        }
    }
</script>
@endpush
