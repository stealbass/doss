@extends('layouts.admin')

@section('page-title')
    {{ __('Mobile App Dashboard') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Mobile App') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('mobile-analytics.index') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('View Analytics') }}">
            <i class="ti ti-chart-bar"></i> {{ __('Analytics') }}
        </a>
    </div>
@endsection

@section('content')
    <!-- Mobile App Overview -->
    <div class="row">
        
        <!-- Quick Stats Cards -->
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <h6 class="mb-3 text-muted">{{ __('Total Mobile Users') }}</h6>
                            <h3 class="mb-0" id="total-users">0</h3>
                            <p class="mb-0 text-muted text-sm">
                                <span class="text-success me-2" id="users-growth">+0%</span>
                                <span>{{ __('from last month') }}</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="avatar avatar-lg bg-primary rounded">
                                <i class="ti ti-users text-white" style="font-size: 32px;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <h6 class="mb-3 text-muted">{{ __('Active Subscriptions') }}</h6>
                            <h3 class="mb-0" id="active-subscriptions">0</h3>
                            <p class="mb-0 text-muted text-sm">
                                <span class="text-success me-2" id="subs-growth">+0%</span>
                                <span>{{ __('conversion rate') }}</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="avatar avatar-lg bg-success rounded">
                                <i class="ti ti-credit-card text-white" style="font-size: 32px;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <h6 class="mb-3 text-muted">{{ __('Monthly Revenue') }}</h6>
                            <h3 class="mb-0" id="monthly-revenue">0 FCFA</h3>
                            <p class="mb-0 text-muted text-sm">
                                <span class="text-success me-2" id="revenue-growth">+0%</span>
                                <span>{{ __('from last month') }}</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="avatar avatar-lg bg-info rounded">
                                <i class="ti ti-cash text-white" style="font-size: 32px;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <h6 class="mb-3 text-muted">{{ __('App Version') }}</h6>
                            <h3 class="mb-0" id="app-version">1.0.0</h3>
                            <p class="mb-0 text-muted text-sm">
                                <span class="text-warning me-2" id="update-status">●</span>
                                <span id="update-text">{{ __('Up to date') }}</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="avatar avatar-lg bg-warning rounded">
                                <i class="ti ti-device-mobile text-white" style="font-size: 32px;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Quick Actions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('mobile-app-settings.index') }}" class="btn btn-outline-primary w-100 text-start">
                                <i class="ti ti-settings me-2"></i>
                                {{ __('App Settings') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('push-notifications.create') }}" class="btn btn-outline-success w-100 text-start">
                                <i class="ti ti-bell-plus me-2"></i>
                                {{ __('Send Notification') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('mobile-app-plans.create') }}" class="btn btn-outline-info w-100 text-start">
                                <i class="ti ti-plus me-2"></i>
                                {{ __('Create Plan') }}
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('mobile-users.index') }}" class="btn btn-outline-warning w-100 text-start">
                                <i class="ti ti-users me-2"></i>
                                {{ __('Manage Users') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Charts -->
    <div class="row">
        
        <!-- User Growth Chart -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('User Growth (Last 30 Days)') }}</h5>
                </div>
                <div class="card-body">
                    <div id="userGrowthChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Plan Distribution -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Subscription Distribution') }}</h5>
                </div>
                <div class="card-body">
                    <div id="planDistributionChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Notifications & Users -->
    <div class="row">
        
        <!-- Recent Push Notifications -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Recent Push Notifications') }}</h5>
                    <a href="{{ route('push-notifications.index') }}" class="btn btn-sm btn-primary">{{ __('View All') }}</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="recent-notifications-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Target') }}</th>
                                    <th>{{ __('Sent') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">{{ __('Loading...') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Mobile Users -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Recent Mobile Users') }}</h5>
                    <a href="{{ route('mobile-users.index') }}" class="btn btn-sm btn-primary">{{ __('View All') }}</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="recent-users-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Plan') }}</th>
                                    <th>{{ __('Registered') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">{{ __('Loading...') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('script-page')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Load dashboard data
    $(document).ready(function() {
        loadDashboardStats();
        loadUserGrowthChart();
        loadPlanDistribution();
        loadRecentNotifications();
        loadRecentUsers();
    });

    function loadDashboardStats() {
        // This would typically call an API endpoint
        // For now, using placeholder data
        $('#total-users').text('1,234');
        $('#users-growth').text('+12.5%');
        $('#active-subscriptions').text('456');
        $('#subs-growth').text('37%');
        $('#monthly-revenue').text('4,560,000 FCFA');
        $('#revenue-growth').text('+28%');
        $('#app-version').text('1.0.0');
        $('#update-text').text('{{ __("Up to date") }}');
    }

    function loadUserGrowthChart() {
        var options = {
            series: [{
                name: '{{ __("New Users") }}',
                data: [30, 40, 35, 50, 49, 60, 70, 91, 125, 155, 180, 220, 250, 280, 310, 340, 370, 400, 430, 460, 490, 520, 550, 580, 610, 640, 670, 700, 730, 760]
            }],
            chart: {
                type: 'area',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            colors: ['#6fd943'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.6,
                    opacityTo: 0.1,
                }
            },
            xaxis: {
                categories: Array.from({length: 30}, (_, i) => i + 1)
            }
        };

        var chart = new ApexCharts(document.querySelector("#userGrowthChart"), options);
        chart.render();
    }

    function loadPlanDistribution() {
        var options = {
            series: [20, 35, 30, 15],
            chart: {
                type: 'donut',
                height: 300
            },
            labels: ['{{ __("Free") }}', '{{ __("Student") }}', '{{ __("Professional") }}', '{{ __("Cabinet") }}'],
            colors: ['#95a5a6', '#3498db', '#2ecc71', '#f39c12'],
            legend: {
                position: 'bottom'
            }
        };

        var chart = new ApexCharts(document.querySelector("#planDistributionChart"), options);
        chart.render();
    }

    function loadRecentNotifications() {
        // Placeholder data - replace with actual API call
        setTimeout(function() {
            var html = `
                <tr>
                    <td><strong>{{ __("New Feature Released") }}</strong></td>
                    <td><span class="badge bg-primary">{{ __("All Users") }}</span></td>
                    <td>2,345</td>
                    <td><span class="badge bg-success">{{ __("Sent") }}</span></td>
                </tr>
                <tr>
                    <td><strong>{{ __("Subscription Reminder") }}</strong></td>
                    <td><span class="badge bg-warning">{{ __("Students") }}</span></td>
                    <td>156</td>
                    <td><span class="badge bg-success">{{ __("Sent") }}</span></td>
                </tr>
                <tr>
                    <td><strong>{{ __("Welcome Message") }}</strong></td>
                    <td><span class="badge bg-info">{{ __("New Users") }}</span></td>
                    <td>45</td>
                    <td><span class="badge bg-warning">{{ __("Scheduled") }}</span></td>
                </tr>
            `;
            $('#recent-notifications-table tbody').html(html);
        }, 500);
    }

    function loadRecentUsers() {
        // Placeholder data - replace with actual API call
        setTimeout(function() {
            var html = `
                <tr>
                    <td><strong>Jean Dupont</strong></td>
                    <td>jean.dupont@example.com</td>
                    <td><span class="badge bg-info">{{ __("Student") }}</span></td>
                    <td>{{ __("Today") }}</td>
                </tr>
                <tr>
                    <td><strong>Marie Martin</strong></td>
                    <td>marie.martin@example.com</td>
                    <td><span class="badge bg-success">{{ __("Professional") }}</span></td>
                    <td>{{ __("Today") }}</td>
                </tr>
                <tr>
                    <td><strong>Pierre Dubois</strong></td>
                    <td>pierre.dubois@example.com</td>
                    <td><span class="badge bg-warning">{{ __("Cabinet") }}</span></td>
                    <td>{{ __("Yesterday") }}</td>
                </tr>
            `;
            $('#recent-users-table tbody').html(html);
        }, 500);
    }
</script>
@endpush
