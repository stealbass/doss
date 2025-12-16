@extends('layouts.app')

@section('page-title', __('Mobile Analytics Dashboard'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Mobile Analytics') }}</li>
@endsection

@section('content')
    <!-- Header Actions -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="ti ti-chart-bar me-2"></i>{{ __('Mobile App Analytics') }}
                </h3>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="refreshData()">
                        <i class="ti ti-refresh me-1"></i>{{ __('Refresh') }}
                    </button>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-download me-1"></i>{{ __('Export') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('mobile-analytics.export', ['type' => 'overview']) }}">
                                <i class="ti ti-file-text me-2"></i>{{ __('Overview') }}
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('mobile-analytics.export', ['type' => 'users']) }}">
                                <i class="ti ti-users me-2"></i>{{ __('Top Users') }}
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('mobile-analytics.export', ['type' => 'revenue']) }}">
                                <i class="ti ti-currency-dollar me-2"></i>{{ __('Revenue') }}
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        @foreach($kpis as $key => $kpi)
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="avatar-sm bg-{{ $kpi['color'] }}-light rounded-circle d-flex align-items-center justify-content-center">
                                <i class="ti ti-{{ $kpi['icon'] }} text-{{ $kpi['color'] }}" style="font-size: 24px;"></i>
                            </div>
                            @if($kpi['growth'] != 0)
                                <span class="badge bg-{{ $kpi['growth'] > 0 ? 'success' : 'danger' }}">
                                    <i class="ti ti-{{ $kpi['growth'] > 0 ? 'trending-up' : 'trending-down' }}"></i>
                                    {{ abs($kpi['growth']) }}%
                                </span>
                            @endif
                        </div>
                        <h6 class="text-muted mb-2" style="font-size: 0.75rem;">{{ __($kpi['label']) }}</h6>
                        <h3 class="mb-0">
                            @if($key === 'monthly_revenue')
                                {{ number_format($kpi['value'], 0) }}
                            @elseif($key === 'conversion_rate' || $key === 'churn_rate')
                                {{ $kpi['value'] }}%
                            @else
                                {{ number_format($kpi['value'], is_float($kpi['value']) ? 2 : 0) }}
                            @endif
                        </h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Main Charts Row -->
    <div class="row mb-4">
        <!-- Users Evolution Chart -->
        <div class="col-xl-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-users me-2"></i>{{ __('Users Growth') }}
                    </h5>
                    <span class="badge bg-primary">{{ __('Last 12 Months') }}</span>
                </div>
                <div class="card-body">
                    <canvas id="usersEvolutionChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Subscriptions by Plan (Pie Chart) -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-chart-pie me-2"></i>{{ __('Subscriptions by Plan') }}
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="subscriptionsByPlanChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Charts Row -->
    <div class="row mb-4">
        <!-- Revenue Evolution -->
        <div class="col-xl-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-currency-dollar me-2"></i>{{ __('Revenue Evolution') }}
                    </h5>
                    <span class="badge bg-warning text-dark">CFA</span>
                </div>
                <div class="card-body">
                    <canvas id="revenueEvolutionChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Users by Role -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-chart-donut me-2"></i>{{ __('Users by Role') }}
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="usersByRoleChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Engagement Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-activity me-2"></i>{{ __('User Engagement (Last 7 Days)') }}
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="engagementChart" height="60"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row">
        <!-- Top Users -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-trophy me-2"></i>{{ __('Top 10 Active Users') }}
                    </h5>
                    <a href="{{ route('mobile-users.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('View All') }}
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Role') }}</th>
                                    <th class="text-center">{{ __('Activity') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topUsers as $index => $user)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'bronze') }}">
                                                    {{ $index + 1 }}
                                                </span>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-bold">{{ $user['name'] }}</div>
                                                <small class="text-muted">{{ $user['email'] }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $user['role'] === 'student' ? 'info' : ($user['role'] === 'lawyer' ? 'warning' : 'primary') }}">
                                                {{ ucfirst($user['role']) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <span class="badge bg-light text-dark" data-bs-toggle="tooltip" title="{{ __('Conversations') }}">
                                                    <i class="ti ti-message"></i> {{ $user['conversations'] }}
                                                </span>
                                                <span class="badge bg-light text-dark" data-bs-toggle="tooltip" title="{{ __('Documents') }}">
                                                    <i class="ti ti-file"></i> {{ $user['documents'] }}
                                                </span>
                                                <span class="badge bg-light text-dark" data-bs-toggle="tooltip" title="{{ __('Downloads') }}">
                                                    <i class="ti ti-download"></i> {{ $user['downloads'] }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('mobile-users.show', $user['id']) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            {{ __('No active users yet') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Plans -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-package me-2"></i>{{ __('Top 5 Plans by Revenue') }}
                    </h5>
                    <a href="{{ route('mobile-app-plans.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('Manage Plans') }}
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Plan') }}</th>
                                    <th class="text-center">{{ __('Price') }}</th>
                                    <th class="text-center">{{ __('Subscribers') }}</th>
                                    <th class="text-end">{{ __('Revenue') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topPlans as $plan)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $plan['name'] }}</div>
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($plan['price'], 0) }} CFA
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $plan['subscribers'] }}</span>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($plan['revenue'], 0) }} CFA</strong>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ __('No plans data available') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-clock me-2"></i>{{ __('Recent Activities') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @forelse($recentActivities as $activity)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-{{ $activity['color'] }}">
                                    <i class="ti ti-{{ $activity['icon'] }} text-white"></i>
                                </div>
                                <div class="timeline-content">
                                    <p class="mb-0">{{ $activity['message'] }}</p>
                                    <small class="text-muted">{{ $activity['time'] }}</small>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted">{{ __('No recent activities') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Chart.js default configuration
    Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Roboto';
    Chart.defaults.color = '#6c757d';

    // Users Evolution Chart
    const usersEvolutionCtx = document.getElementById('usersEvolutionChart').getContext('2d');
    new Chart(usersEvolutionCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($charts['users_evolution'], 'month')) !!},
            datasets: [{
                label: '{{ __("New Users") }}',
                data: {!! json_encode(array_column($charts['users_evolution'], 'count')) !!},
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Revenue Evolution Chart
    const revenueEvolutionCtx = document.getElementById('revenueEvolutionChart').getContext('2d');
    new Chart(revenueEvolutionCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_column($charts['revenue_evolution'], 'month')) !!},
            datasets: [{
                label: '{{ __("Revenue (CFA)") }}',
                data: {!! json_encode(array_column($charts['revenue_evolution'], 'amount')) !!},
                backgroundColor: '#ffc107',
                borderColor: '#ffc107',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' CFA';
                        }
                    }
                }
            }
        }
    });

    // Subscriptions by Plan Chart
    const subscriptionsByPlanCtx = document.getElementById('subscriptionsByPlanChart').getContext('2d');
    new Chart(subscriptionsByPlanCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_column($charts['subscriptions_by_plan'], 'name')) !!},
            datasets: [{
                data: {!! json_encode(array_column($charts['subscriptions_by_plan'], 'count')) !!},
                backgroundColor: {!! json_encode(array_column($charts['subscriptions_by_plan'], 'color')) !!}
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Users by Role Chart
    const usersByRoleCtx = document.getElementById('usersByRoleChart').getContext('2d');
    new Chart(usersByRoleCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_column($charts['users_by_role'], 'role')) !!},
            datasets: [{
                data: {!! json_encode(array_column($charts['users_by_role'], 'count')) !!},
                backgroundColor: {!! json_encode(array_column($charts['users_by_role'], 'color')) !!}
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Engagement Chart
    const engagementCtx = document.getElementById('engagementChart').getContext('2d');
    new Chart(engagementCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($charts['engagement_by_day'], 'date')) !!},
            datasets: [
                {
                    label: '{{ __("Conversations") }}',
                    data: {!! json_encode(array_column($charts['engagement_by_day'], 'conversations')) !!},
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4
                },
                {
                    label: '{{ __("Documents") }}',
                    data: {!! json_encode(array_column($charts['engagement_by_day'], 'documents')) !!},
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4
                },
                {
                    label: '{{ __("Downloads") }}',
                    data: {!! json_encode(array_column($charts['engagement_by_day'], 'downloads')) !!},
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Refresh data function
    function refreshData() {
        location.reload();
    }

    // Auto-refresh every 5 minutes
    setTimeout(function() {
        refreshData();
    }, 300000);
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

    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }

    .bg-secondary-light {
        background-color: rgba(108, 117, 125, 0.1) !important;
    }

    .bg-bronze {
        background-color: #cd7f32 !important;
        color: white !important;
    }

    .timeline {
        position: relative;
        padding-left: 40px;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 20px;
    }

    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: -28px;
        top: 40px;
        width: 2px;
        height: calc(100% - 20px);
        background: #e9ecef;
    }

    .timeline-marker {
        position: absolute;
        left: -40px;
        top: 0;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .timeline-content {
        padding-left: 10px;
    }
</style>
@endpush
