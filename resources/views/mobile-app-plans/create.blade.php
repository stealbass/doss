@extends('layouts.app')

@section('page-title', __('Create New Plan'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('mobile-app-plans.index') }}">{{ __('Mobile App Plans') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Plan') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-plus me-2"></i>{{ __('Create New Subscription Plan') }}</h5>
                </div>
                
                <form action="{{ route('mobile-app-plans.store') }}" method="POST">
                    @csrf
                    
                    <div class="card-body">
                        <!-- Basic Information -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-info-circle me-1"></i>{{ __('Basic Information') }}
                        </h6>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('Plan Name (Slug)') }}</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           placeholder="free, student, pro, cabinet" 
                                           required>
                                    <small class="text-muted">{{ __('Lowercase, no spaces (used internally)') }}</small>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('Plan Name (French)') }}</label>
                                    <input type="text" 
                                           class="form-control @error('name_fr') is-invalid @enderror" 
                                           name="name_fr" 
                                           value="{{ old('name_fr') }}" 
                                           placeholder="Plan Gratuit, Plan Étudiant..." 
                                           required>
                                    <small class="text-muted">{{ __('Displayed to users') }}</small>
                                    @error('name_fr')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-currency-dollar me-1"></i>{{ __('Pricing') }}
                        </h6>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('Monthly Price (CFA)') }}</label>
                                    <input type="number" 
                                           class="form-control @error('price_monthly') is-invalid @enderror" 
                                           name="price_monthly" 
                                           value="{{ old('price_monthly', 0) }}" 
                                           min="0" 
                                           step="1000"
                                           required>
                                    @error('price_monthly')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('Yearly Price (CFA)') }}</label>
                                    <input type="number" 
                                           class="form-control @error('price_yearly') is-invalid @enderror" 
                                           name="price_yearly" 
                                           value="{{ old('price_yearly', 0) }}" 
                                           min="0" 
                                           step="1000"
                                           required>
                                    <small class="text-muted">{{ __('Typically 10-20% discount from 12x monthly') }}</small>
                                    @error('price_yearly')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Feature Limits -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-chart-bar me-1"></i>{{ __('Feature Limits') }}
                        </h6>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('Searches Limit') }}</label>
                                    <input type="number" 
                                           class="form-control @error('searches_limit') is-invalid @enderror" 
                                           name="searches_limit" 
                                           value="{{ old('searches_limit', 5) }}" 
                                           min="-1"
                                           required>
                                    <small class="text-muted">{{ __('-1 for unlimited') }}</small>
                                    @error('searches_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('AI Analyses Limit') }}</label>
                                    <input type="number" 
                                           class="form-control @error('ai_analyses_limit') is-invalid @enderror" 
                                           name="ai_analyses_limit" 
                                           value="{{ old('ai_analyses_limit', 2) }}" 
                                           min="-1"
                                           required>
                                    <small class="text-muted">{{ __('-1 for unlimited') }}</small>
                                    @error('ai_analyses_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('PDF Downloads Limit') }}</label>
                                    <input type="number" 
                                           class="form-control @error('pdf_downloads_limit') is-invalid @enderror" 
                                           name="pdf_downloads_limit" 
                                           value="{{ old('pdf_downloads_limit', 3) }}" 
                                           min="-1"
                                           required>
                                    <small class="text-muted">{{ __('-1 for unlimited') }}</small>
                                    @error('pdf_downloads_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Features -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-star me-1"></i>{{ __('Advanced Features') }}
                        </h6>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="has_full_history" 
                                           id="has_full_history"
                                           {{ old('has_full_history') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="has_full_history">
                                        {{ __('Full History Access') }}
                                    </label>
                                    <br><small class="text-muted">{{ __('Access to complete conversation history') }}</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="has_advanced_ai" 
                                           id="has_advanced_ai"
                                           {{ old('has_advanced_ai') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="has_advanced_ai">
                                        {{ __('Advanced AI Features') }}
                                    </label>
                                    <br><small class="text-muted">{{ __('Access to advanced AI capabilities') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- AI Configuration -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-cpu me-1"></i>{{ __('AI Configuration') }}
                        </h6>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('AI Model') }}</label>
                                    <select class="form-select @error('ai_model') is-invalid @enderror" 
                                            name="ai_model" 
                                            required>
                                        @foreach($aiModels as $value => $label)
                                            <option value="{{ $value }}" {{ old('ai_model', 'gpt-3.5-turbo') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('ai_model')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label required">{{ __('Max Tokens') }}</label>
                                    <input type="number" 
                                           class="form-control @error('max_tokens') is-invalid @enderror" 
                                           name="max_tokens" 
                                           value="{{ old('max_tokens', 1000) }}" 
                                           min="100" 
                                           max="100000"
                                           step="100"
                                           required>
                                    <small class="text-muted">{{ __('Maximum tokens per AI request (100-100,000)') }}</small>
                                    @error('max_tokens')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-settings me-1"></i>{{ __('Status') }}
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="is_active" 
                                           id="is_active"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        {{ __('Plan is Active') }}
                                    </label>
                                    <br><small class="text-muted">{{ __('Users can subscribe to active plans') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('mobile-app-plans.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i>{{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i>{{ __('Create Plan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .form-label.required::after {
        content: ' *';
        color: red;
    }
</style>
@endpush
