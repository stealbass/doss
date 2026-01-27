@extends('layouts.app')

@section('page-title', __('Mobile App Plans'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Mobile App Plans') }}</li>
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Total Plans') }}</h6>
                            <h3 class="mb-0">{{ $stats['total_plans'] }}</h3>
                        </div>
                        <div class="avatar-sm bg-primary-light rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-package text-primary" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Active Plans') }}</h6>
                            <h3 class="mb-0">{{ $stats['active_plans'] }}</h3>
                        </div>
                        <div class="avatar-sm bg-success-light rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-check text-success" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Total Subscriptions') }}</h6>
                            <h3 class="mb-0">{{ $stats['total_subscriptions'] }}</h3>
                        </div>
                        <div class="avatar-sm bg-info-light rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-users text-info" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Monthly Revenue') }}</h6>
                            <h3 class="mb-0">{{ number_format($stats['monthly_revenue'], 0) }}</h3>
                            <small class="text-muted">CFA</small>
                        </div>
                        <div class="avatar-sm bg-warning-light rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-currency-dollar text-warning" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ __('Subscription Plans') }}</h4>
                <div class="btn-group" role="group">
                    <a href="{{ route('mobile-app-plans.comparison') }}" class="btn btn-outline-info">
                        <i class="ti ti-layout-grid me-1"></i>{{ __('Compare Plans') }}
                    </a>
                    <a href="{{ route('mobile-app-plans.export') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-download me-1"></i>{{ __('Export CSV') }}
                    </a>
                    <a href="{{ route('mobile-app-plans.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>{{ __('Create Plan') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Plans Cards -->
    <div class="row">
        @forelse($plans as $plan)
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm h-100 {{ $plan->is_active ? 'border-primary' : 'border-secondary' }}" style="border-width: 2px;">
                    <!-- Plan Header -->
                    <div class="card-header text-center {{ $plan->is_active ? 'bg-primary text-white' : 'bg-secondary text-white' }}">
                        <h5 class="mb-0">{{ $plan->name_fr }}</h5>
                        @if(!$plan->is_active)
                            <span class="badge bg-dark mt-2">{{ __('Inactive') }}</span>
                        @endif
                    </div>

                    <!-- Pricing -->
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <h2 class="mb-0">
                                @if($plan->price_monthly == 0)
                                    <span class="text-success">{{ __('Free') }}</span>
                                @else
                                    {{ number_format($plan->price_monthly, 0) }} <small class="text-muted">CFA</small>
                                @endif
                            </h2>
                            <small class="text-muted">/{{ __('month') }}</small>
                            
                            @if($plan->price_yearly > 0)
                                <div class="mt-2">
                                    <small class="text-info">
                                        {{ number_format($plan->price_yearly, 0) }} CFA/{{ __('year') }}
                                        <br><span class="badge bg-info">{{ __('Save') }} {{ round((1 - $plan->price_yearly / ($plan->price_monthly * 12)) * 100) }}%</span>
                                    </small>
                                </div>
                            @endif
                        </div>

                        <!-- Features -->
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                <strong>{{ $plan->searches_limit == -1 ? __('Unlimited') : $plan->searches_limit }}</strong> 
                                {{ __('searches/month') }}
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                <strong>{{ $plan->ai_analyses_limit == -1 ? __('Unlimited') : $plan->ai_analyses_limit }}</strong> 
                                {{ __('AI analyses') }}
                            </li>
                            <li class="mb-2">
                                <i class="ti ti-check text-success me-2"></i>
                                <strong>{{ $plan->pdf_downloads_limit == -1 ? __('Unlimited') : $plan->pdf_downloads_limit }}</strong> 
                                {{ __('PDF downloads') }}
                            </li>
                            <li class="mb-2">
                                @if($plan->has_full_history)
                                    <i class="ti ti-check text-success me-2"></i>
                                    {{ __('Full history access') }}
                                @else
                                    <i class="ti ti-x text-danger me-2"></i>
                                    <span class="text-muted">{{ __('Limited history') }}</span>
                                @endif
                            </li>
                            <li class="mb-2">
                                @if($plan->has_advanced_ai)
                                    <i class="ti ti-check text-success me-2"></i>
                                    {{ __('Advanced AI features') }}
                                @else
                                    <i class="ti ti-x text-danger me-2"></i>
                                    <span class="text-muted">{{ __('Basic AI') }}</span>
                                @endif
                            </li>
                        </ul>

                        <!-- AI Configuration -->
                        <div class="border-top pt-3 mb-3">
                            <small class="text-muted">
                                <strong>{{ __('AI Model') }}:</strong> {{ strtoupper($plan->ai_model) }}<br>
                                <strong>{{ __('Max Tokens') }}:</strong> {{ number_format($plan->max_tokens) }}
                            </small>
                        </div>

                        <!-- Subscriptions Count -->
                        <div class="alert alert-light mb-3">
                            <i class="ti ti-users me-1"></i>
                            <strong>{{ $plan->active_subscriptions_count }}</strong> {{ __('active subscriptions') }}
                            <br>
                            <small class="text-muted">{{ $plan->total_subscriptions_count }} {{ __('total') }}</small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="btn-group w-100 mb-2" role="group">
                            <a href="{{ route('mobile-app-plans.edit', $plan->id) }}" 
                               class="btn btn-sm btn-outline-primary"
                               data-bs-toggle="tooltip" 
                               title="{{ __('Edit') }}">
                                <i class="ti ti-edit"></i>
                            </a>
                            
                            <button type="button" 
                                    class="btn btn-sm btn-outline-info" 
                                    onclick="duplicatePlan({{ $plan->id }})"
                                    data-bs-toggle="tooltip" 
                                    title="{{ __('Duplicate') }}">
                                <i class="ti ti-copy"></i>
                            </button>

                            <button type="button" 
                                    class="btn btn-sm {{ $plan->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" 
                                    onclick="togglePlanActive({{ $plan->id }})"
                                    data-bs-toggle="tooltip" 
                                    title="{{ $plan->is_active ? __('Deactivate') : __('Activate') }}">
                                <i class="ti ti-{{ $plan->is_active ? 'eye-off' : 'eye' }}"></i>
                            </button>

                            @if($plan->total_subscriptions_count == 0)
                                <form action="{{ route('mobile-app-plans.destroy', $plan->id) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('Are you sure you want to delete this plan?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="tooltip" 
                                            title="{{ __('Delete') }}">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            @else
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger" 
                                        disabled
                                        data-bs-toggle="tooltip" 
                                        title="{{ __('Cannot delete: has subscriptions') }}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            @endif
                        </div>

                        <button type="button" 
                                class="btn btn-sm btn-secondary w-100" 
                                onclick="viewStatistics({{ $plan->id }}, '{{ $plan->name_fr }}')">
                            <i class="ti ti-chart-bar me-1"></i>{{ __('View Statistics') }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="ti ti-package" style="font-size: 64px; opacity: 0.3;"></i>
                        <h4 class="mt-3 text-muted">{{ __('No plans created yet') }}</h4>
                        <p class="text-muted">{{ __('Create your first subscription plan to get started.') }}</p>
                        <a href="{{ route('mobile-app-plans.create') }}" class="btn btn-primary mt-3">
                            <i class="ti ti-plus me-1"></i>{{ __('Create First Plan') }}
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Statistics Modal -->
    <div class="modal fade" id="statisticsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statisticsModalLabel">{{ __('Plan Statistics') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="statistics-content">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('custom-script')
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Toggle Plan Active/Inactive
    function togglePlanActive(planId) {
        if (!confirm('{{ __("Are you sure you want to change the status of this plan?") }}')) {
            return;
        }

        fetch(`/mobile-app-plans/${planId}/toggle-active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            showNotification('error', '{{ __("An error occurred") }}');
            console.error('Error:', error);
        });
    }

    // Duplicate Plan
    function duplicatePlan(planId) {
        if (!confirm('{{ __("Do you want to duplicate this plan?") }}')) {
            return;
        }

        window.location.href = `/mobile-app-plans/${planId}/duplicate`;
    }

    // View Statistics
    function viewStatistics(planId, planName) {
        document.getElementById('statisticsModalLabel').textContent = `{{ __('Statistics') }}: ${planName}`;
        
        const modal = new bootstrap.Modal(document.getElementById('statisticsModal'));
        modal.show();

        // Load statistics
        fetch(`/mobile-app-plans/${planId}/statistics`)
            .then(response => response.json())
            .then(data => {
                const content = `
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0 text-primary">${data.total_subscriptions}</h3>
                                    <small class="text-muted">{{ __('Total Subscriptions') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0 text-success">${data.active_subscriptions}</h3>
                                    <small class="text-muted">{{ __('Active Subscriptions') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0 text-warning">${data.monthly_revenue.toLocaleString()} CFA</h3>
                                    <small class="text-muted">{{ __('Monthly Revenue') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0 text-info">${data.total_revenue.toLocaleString()} CFA</h3>
                                    <small class="text-muted">{{ __('Total Revenue') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">${Math.round(data.avg_subscription_duration || 0)} {{ __('days') }}</h3>
                                    <small class="text-muted">{{ __('Avg Duration') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">${data.new_subscriptions_this_month}</h3>
                                    <small class="text-muted">{{ __('New This Month') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0 text-danger">${data.churn_rate}%</h3>
                                    <small class="text-muted">{{ __('Churn Rate') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('statistics-content').innerHTML = content;
            })
            .catch(error => {
                document.getElementById('statistics-content').innerHTML = `
                    <div class="alert alert-danger">
                        {{ __('Error loading statistics') }}
                    </div>
                `;
                console.error('Error:', error);
            });
    }

    // Notification Helper
    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                bootstrap.Alert.getInstance(alert)?.close();
            }
        }, 3000);
    }
 </script>
@endpush

@push('style')
<style>
    .avatar-sm {
        width: 48px;
        height: 48px;
    }

    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }

    .bg-success-light {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }

    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }

    .bg-info-light {
        background-color: rgba(13, 202, 240, 0.1) !important;
    }

    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endpush
