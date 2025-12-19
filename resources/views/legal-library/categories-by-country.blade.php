@extends('layouts.app')

@section('page-title', __('Legal Library - Categories by Country'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Legal Library') }}</li>
    <li class="breadcrumb-item">{{ __('Categories by Country') }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-header">
                    <h5>{{ __('Legal Library Categories by Country') }}</h5>
                    <small class="text-muted">{{ __('Manage category visibility and display order for mobile app by country') }}</small>
                </div>
                <div class="card-body">
                    <!-- Country Filter Tabs -->
                    <ul class="nav nav-pills mb-3" id="country-tabs" role="tablist">
                        @foreach($countries as $index => $country)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                                        id="tab-{{ $country }}" 
                                        data-bs-toggle="pill" 
                                        data-bs-target="#country-{{ $country }}" 
                                        type="button" 
                                        role="tab">
                                    {{ __(ucfirst(str_replace('-', ' ', $country))) }}
                                    @if(isset($categories[$country]))
                                        <span class="badge bg-primary ms-1">{{ $categories[$country]->count() }}</span>
                                    @else
                                        <span class="badge bg-secondary ms-1">0</span>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Country Content Panels -->
                    <div class="tab-content" id="country-tab-content">
                        @foreach($countries as $index => $country)
                            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                                 id="country-{{ $country }}" 
                                 role="tabpanel">
                                
                                @if(isset($categories[$country]) && $categories[$country]->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="50">#</th>
                                                    <th>{{ __('Category Name') }}</th>
                                                    <th width="150" class="text-center">{{ __('Sort Order') }}</th>
                                                    <th width="150" class="text-center">{{ __('Mobile Visible') }}</th>
                                                    <th width="200">{{ __('Action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($categories[$country] as $category)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>
                                                            <strong>{{ $category->name }}</strong>
                                                            <br>
                                                            <small class="text-muted">{{ $category->slug }}</small>
                                                        </td>
                                                        <td class="text-center">
                                                            <input type="number" 
                                                                   class="form-control form-control-sm text-center sort-order-input" 
                                                                   value="{{ $category->sort_order ?? 0 }}" 
                                                                   data-category-id="{{ $category->id }}"
                                                                   min="0"
                                                                   style="width: 80px; display: inline-block;">
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input visibility-toggle" 
                                                                       type="checkbox" 
                                                                       {{ $category->is_mobile_visible ? 'checked' : '' }}
                                                                       data-category-id="{{ $category->id }}"
                                                                       id="visibility-{{ $category->id }}">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('legal-library.documents', $category->id) }}" 
                                                               class="btn btn-sm btn-info" 
                                                               title="{{ __('View Documents') }}"
                                                               data-bs-toggle="tooltip">
                                                                <i class="ti ti-eye"></i> {{ __('Documents') }}
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="ti ti-info-circle"></i>
                                        {{ __('No categories found for') }} {{ __(ucfirst(str_replace('-', ' ', $country))) }}.
                                        <a href="{{ route('legal-library.category.create') }}" class="alert-link">
                                            {{ __('Create a new category') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Toggle visibility
            $('.visibility-toggle').on('change', function() {
                var categoryId = $(this).data('category-id');
                var isVisible = $(this).is(':checked');
                
                $.ajax({
                    url: '{{ url('legal-categories') }}/' + categoryId + '/toggle-visibility',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            show_toastr('{{ __('Success') }}', '{{ __('Category visibility updated successfully') }}', 'success');
                        }
                    },
                    error: function() {
                        show_toastr('{{ __('Error') }}', '{{ __('Failed to update visibility') }}', 'error');
                    }
                });
            });

            // Update sort order on input change (with debounce)
            var sortOrderTimeout;
            $('.sort-order-input').on('input', function() {
                clearTimeout(sortOrderTimeout);
                var categoryId = $(this).data('category-id');
                var sortOrder = $(this).val();
                
                sortOrderTimeout = setTimeout(function() {
                    $.ajax({
                        url: '{{ url('legal-categories') }}/' + categoryId + '/update-sort',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            sort_order: sortOrder
                        },
                        success: function(response) {
                            if (response.success) {
                                show_toastr('{{ __('Success') }}', '{{ __('Sort order updated successfully') }}', 'success');
                            }
                        },
                        error: function() {
                            show_toastr('{{ __('Error') }}', '{{ __('Failed to update sort order') }}', 'error');
                        }
                    });
                }, 500);
            });
        });
    </script>
    @endpush
@endsection
