@extends('layouts.app')

@section('page-title', __('Assign Countries to Categories'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('legal-library.index') }}">{{ __('Legal Library') }}</a></li>
    <li class="breadcrumb-item">{{ __('Assign Countries') }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">{{ __('Assign Countries to Existing Categories') }}</h5>
                            <small class="text-muted">
                                {{ __('Update all categories at once by selecting the country for each category') }}
                            </small>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-info">
                                {{ $categories->count() }} {{ __('categories') }}
                            </span>
                        </div>
                    </div>
                </div>
                
                {{ Form::open(['route' => 'legal-library.bulk-assign-countries', 'method' => 'post', 'id' => 'bulkAssignForm']) }}
                <div class="card-body">
                    @if($categories->count() > 0)
                        <div class="alert alert-info mb-4">
                            <i class="ti ti-info-circle"></i>
                            <strong>{{ __('Instructions') }}:</strong> 
                            {{ __('For each category below, select the appropriate country. Categories without a country will not appear in the mobile app by country filter.') }}
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>{{ __('Category Name') }}</th>
                                        <th width="150">{{ __('Documents') }}</th>
                                        <th width="250">{{ __('Assign to Country') }}</th>
                                        <th width="100" class="text-center">{{ __('Mobile Visible') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $category)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $category->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $category->slug }}</small>
                                                @if($category->country)
                                                    <br>
                                                    <span class="badge bg-success mt-1">
                                                        {{ ucfirst(str_replace('-', ' ', $category->country)) }}
                                                    </span>
                                                @else
                                                    <br>
                                                    <span class="badge bg-warning mt-1">{{ __('No country assigned') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">
                                                    {{ $category->documents_count }} {{ __('docs') }}
                                                </span>
                                            </td>
                                            <td>
                                                {!! Form::select("categories[{$category->id}][country]", [
                                                    '' => __('-- No Country --'),
                                                    'benin' => __('Benin'),
                                                    'burkina-faso' => __('Burkina Faso'),
                                                    'cote-divoire' => __('Côte d\'Ivoire'),
                                                    'mali' => __('Mali'),
                                                    'niger' => __('Niger'),
                                                    'senegal' => __('Sénégal'),
                                                    'togo' => __('Togo'),
                                                    'gabon' => __('Gabon'),
                                                    'congo' => __('Congo'),
                                                    'cameroun' => __('Cameroun'),
                                                ], $category->country, [
                                                    'class' => 'form-select form-select-sm country-select',
                                                    'data-category-id' => $category->id
                                                ]) !!}
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-inline-block">
                                                    {!! Form::checkbox("categories[{$category->id}][is_mobile_visible]", 1, $category->is_mobile_visible, [
                                                        'class' => 'form-check-input',
                                                        'id' => 'mobile_visible_' . $category->id
                                                    ]) !!}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="alert alert-warning">
                                    <i class="ti ti-alert-triangle"></i>
                                    <strong>{{ __('Bulk Actions') }}:</strong>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectCountryForAll('benin')">
                                            {{ __('Set All to Benin') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectCountryForAll('burkina-faso')">
                                            {{ __('Set All to Burkina Faso') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectCountryForAll('cote-divoire')">
                                            {{ __('Set All to Côte d\'Ivoire') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="selectCountryForAll('')">
                                            {{ __('Clear All Countries') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i>
                            {{ __('No categories found. Please create categories first.') }}
                        </div>
                    @endif
                </div>

                @if($categories->count() > 0)
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-6">
                                <a href="{{ route('legal-library.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-arrow-left"></i> {{ __('Cancel') }}
                                </a>
                            </div>
                            <div class="col-6 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-check"></i> {{ __('Save All Assignments') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                {{ Form::close() }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function selectCountryForAll(country) {
            $('.country-select').val(country);
            
            if (country) {
                show_toastr('{{ __('Success') }}', '{{ __('All categories set to') }} ' + country, 'success');
            } else {
                show_toastr('{{ __('Success') }}', '{{ __('All country assignments cleared') }}', 'success');
            }
        }

        $(document).ready(function() {
            $('#bulkAssignForm').on('submit', function(e) {
                // Show confirmation
                if (!confirm('{{ __('Are you sure you want to update all category assignments?') }}')) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading
                show_toastr('{{ __('Processing') }}', '{{ __('Updating categories...') }}', 'info');
            });

            // Track changes
            let changesCount = 0;
            $('.country-select').on('change', function() {
                changesCount++;
                if (changesCount > 0) {
                    $('#bulkAssignForm button[type="submit"]').addClass('btn-warning').removeClass('btn-primary');
                }
            });
        });
    </script>
    @endpush
@endsection
