@extends('layouts.app')

@section('page-title', __('Mobile Users Management'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Mobile Users') }}</li>
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Total Users') }}</h6>
                            <h3 class="mb-0">{{ $stats['total_users'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar-sm bg-primary-light rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-users text-primary" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Active Subscriptions') }}</h6>
                            <h3 class="mb-0">{{ $stats['active_subscriptions'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar-sm bg-success-light rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-credit-card text-success" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('Total Revenue') }}</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_revenue'] ?? 0, 0, ',', ' ') }} CFA</h3>
                        </div>
                        <div class="avatar-sm bg-warning-light rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-currency-dollar text-warning" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">{{ __('New This Month') }}</h6>
                            <h3 class="mb-0">{{ $stats['new_users_this_month'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar-sm bg-info-light rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-user-plus text-info" style="font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Actions -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('mobile-users.index') }}" id="filters-form">
                <div class="row g-3">
                    <!-- Search -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" name="search" 
                                   value="{{ request('search') }}" placeholder="{{ __('Name, email, phone...') }}">
                        </div>
                    </div>

                    <!-- Role Filter -->
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Role') }}</label>
                        <select name="role" class="form-select">
                            <option value="">{{ __('All Roles') }}</option>
                            <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>
                                {{ __('Student') }}
                            </option>
                            <option value="lawyer" {{ request('role') == 'lawyer' ? 'selected' : '' }}>
                                {{ __('Lawyer') }}
                            </option>
                            <option value="enterprise" {{ request('role') == 'enterprise' ? 'selected' : '' }}>
                                {{ __('Enterprise') }}
                            </option>
                        </select>
                    </div>

                    <!-- Plan Filter -->
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Plan') }}</label>
                        <select name="plan" class="form-select">
                            <option value="">{{ __('All Plans') }}</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->slug }}" {{ request('plan') == $plan->slug ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                                {{ __('Active') }}
                            </option>
                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>
                                {{ __('Expired') }}
                            </option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                {{ __('Cancelled') }}
                            </option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="ti ti-filter me-1"></i>{{ __('Filter') }}
                        </button>
                        <a href="{{ route('mobile-users.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="ti ti-x me-1"></i>{{ __('Reset') }}
                        </a>
                        <a href="{{ route('mobile-users.export') }}" class="btn btn-success">
                            <i class="ti ti-download me-1"></i>{{ __('Export') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ti ti-users me-2"></i>{{ __('Mobile Users') }}</h5>
            <span class="badge bg-primary">{{ $users->total() }} {{ __('users') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Expires At') }}</th>
                            <th>{{ __('Payments') }}</th>
                            <th>{{ __('Registered') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $subscription = $user->activeMobileSubscription;
                                $totalPayments = $user->mobilePayments->where('status', 'completed')->sum('amount');
                            @endphp
                            <tr>
                                <!-- User Info -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <span class="text-primary fw-bold">{{ substr($user->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $user->name }}</div>
                                            <small class="text-muted">{{ $user->email }}</small>
                                            @if($user->phone)
                                                <br><small class="text-muted"><i class="ti ti-phone"></i> {{ $user->phone }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td>
                                    @if($user->mobile_role == 'student')
                                        <span class="badge bg-info">
                                            <i class="ti ti-book me-1"></i>{{ __('Student') }}
                                        </span>
                                    @elseif($user->mobile_role == 'lawyer')
                                        <span class="badge bg-warning">
                                            <i class="ti ti-briefcase me-1"></i>{{ __('Lawyer') }}
                                        </span>
                                    @elseif($user->mobile_role == 'enterprise')
                                        <span class="badge bg-primary">
                                            <i class="ti ti-building me-1"></i>{{ __('Enterprise') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('N/A') }}</span>
                                    @endif
                                </td>

                                <!-- Plan -->
                                <td>
                                    @if($subscription && $subscription->plan)
                                        <div class="fw-bold">{{ $subscription->plan->name }}</div>
                                        <small class="text-muted">{{ number_format($subscription->plan->monthly_price, 0) }} CFA/mois</small>
                                    @else
                                        <span class="text-muted">{{ __('No Plan') }}</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td>
                                    @if($subscription)
                                        @if($subscription->status == 'active' && $subscription->expires_at > now())
                                            <span class="badge bg-success">
                                                <i class="ti ti-check me-1"></i>{{ __('Active') }}
                                            </span>
                                        @elseif($subscription->status == 'expired' || $subscription->expires_at < now())
                                            <span class="badge bg-danger">
                                                <i class="ti ti-x me-1"></i>{{ __('Expired') }}
                                            </span>
                                        @elseif($subscription->status == 'cancelled')
                                            <span class="badge bg-warning">
                                                <i class="ti ti-ban me-1"></i>{{ __('Cancelled') }}
                                            </span>
                                        @elseif($subscription->status == 'suspended')
                                            <span class="badge bg-dark">
                                                <i class="ti ti-pause me-1"></i>{{ __('Suspended') }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">{{ $subscription->status }}</span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">{{ __('No Subscription') }}</span>
                                    @endif
                                </td>

                                <!-- Expires At -->
                                <td>
                                    @if($subscription && $subscription->expires_at)
                                        <div>{{ $subscription->expires_at->format('d/m/Y') }}</div>
                                        @if($subscription->expires_at < now()->addDays(7))
                                            <small class="text-danger">
                                                <i class="ti ti-alert-triangle"></i> {{ __('Expires soon') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Payments -->
                                <td>
                                    <div class="fw-bold">{{ number_format($totalPayments, 0) }} CFA</div>
                                    <small class="text-muted">{{ $user->mobilePayments->count() }} {{ __('transactions') }}</small>
                                </td>

                                <!-- Registered -->
                                <td>
                                    <div>{{ $user->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                </td>

                                <!-- Actions -->
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('mobile-users.show', $user->id) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           data-bs-toggle="tooltip" 
                                           title="{{ __('View Details') }}">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info" 
                                                onclick="openChangePlanModal({{ $user->id }})"
                                                data-bs-toggle="tooltip" 
                                                title="{{ __('Change Plan') }}">
                                            <i class="ti ti-repeat"></i>
                                        </button>

                                        @if($subscription && $subscription->status == 'active')
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning" 
                                                    onclick="suspendUser({{ $user->id }})"
                                                    data-bs-toggle="tooltip" 
                                                    title="{{ __('Suspend') }}">
                                                <i class="ti ti-pause"></i>
                                            </button>
                                        @elseif($subscription && $subscription->status == 'suspended')
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-success" 
                                                    onclick="reactivateUser({{ $user->id }})"
                                                    data-bs-toggle="tooltip" 
                                                    title="{{ __('Reactivate') }}">
                                                <i class="ti ti-player-play"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="ti ti-users" style="font-size: 48px; opacity: 0.3;"></i>
                                    <div class="mt-3 text-muted">{{ __('No mobile users found') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Change Plan Modal -->
    <div class="modal fade" id="changePlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Change User Plan') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changePlanForm">
                    <div class="modal-body">
                        <input type="hidden" id="change-plan-user-id">
                        
                        <div class="mb-3">
                            <label class="form-label">{{ __('New Plan') }}</label>
                            <select class="form-select" id="new-plan-id" required>
                                <option value="">{{ __('Select a plan') }}</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" data-price="{{ $plan->monthly_price }}">
                                        {{ $plan->name }} - {{ number_format($plan->monthly_price, 0) }} CFA/mois
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Duration (months)') }}</label>
                            <select class="form-select" id="plan-duration" required>
                                <option value="1">1 {{ __('month') }}</option>
                                <option value="3">3 {{ __('months') }}</option>
                                <option value="6">6 {{ __('months') }}</option>
                                <option value="12">12 {{ __('months') }}</option>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            {{ __('The old subscription will be cancelled and a new one will be created.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i>{{ __('Change Plan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Open Change Plan Modal
    function openChangePlanModal(userId) {
        document.getElementById('change-plan-user-id').value = userId;
        document.getElementById('new-plan-id').value = '';
        document.getElementById('plan-duration').value = '1';
        new bootstrap.Modal(document.getElementById('changePlanModal')).show();
    }

    // Handle Change Plan Form Submit
    document.getElementById('changePlanForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const userId = document.getElementById('change-plan-user-id').value;
        const planId = document.getElementById('new-plan-id').value;
        const duration = document.getElementById('plan-duration').value;

        fetch(`/mobile-users/${userId}/change-plan`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                plan_id: planId,
                duration: duration
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('changePlanModal')).hide();
                showNotification('success', data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            showNotification('error', 'Une erreur est survenue');
            console.error('Error:', error);
        });
    });

    // Suspend User
    function suspendUser(userId) {
        if (!confirm('{{ __("Are you sure you want to suspend this user?") }}')) {
            return;
        }

        fetch(`/mobile-users/${userId}/suspend`, {
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
            showNotification('error', 'Une erreur est survenue');
            console.error('Error:', error);
        });
    }

    // Reactivate User
    function reactivateUser(userId) {
        if (!confirm('{{ __("Are you sure you want to reactivate this user?") }}')) {
            return;
        }

        fetch(`/mobile-users/${userId}/reactivate`, {
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
            showNotification('error', 'Une erreur est survenue');
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

@push('styles')
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

    .table > :not(caption) > * > * {
        padding: 0.75rem;
    }

    .btn-group .btn {
        margin-right: 2px;
    }
</style>
@endpush
