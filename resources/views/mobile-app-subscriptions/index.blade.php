@extends('layouts.app')

@section('page-title', __('Mobile App Subscriptions'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Mobile App') }}</li>
    <li class="breadcrumb-item active">{{ __('Subscriptions') }}</li>
@endsection

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('mobile-app-subscriptions.index') }}" id="filters-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" name="search"
                                   value="{{ request('search') }}" placeholder="{{ __('Name or email...') }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('Role') }}</label>
                        <select name="role" class="form-select">
                            <option value="">{{ __('All Roles') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

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

                    <div class="col-md-2">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>{{ __('Suspended') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('Country') }}</label>
                        <select name="country" class="form-select">
                            <option value="">{{ __('All Countries') }}</option>
                            @foreach($countries as $code => $country)
                                <option value="{{ $code }}" {{ request('country') == $code ? 'selected' : '' }}>
                                    {{ $country['name'] ?? $code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('From') }}</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('To') }}</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>

                    <div class="col-md-5 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="ti ti-filter me-1"></i>{{ __('Filter') }}
                        </button>
                        <a href="{{ route('mobile-app-subscriptions.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="ti ti-x me-1"></i>{{ __('Reset') }}
                        </a>
                        <a href="{{ route('mobile-app-subscriptions.export.csv', request()->query()) }}" class="btn btn-success me-2">
                            <i class="ti ti-download me-1"></i>{{ __('Export CSV') }}
                        </a>
                        <a href="{{ route('mobile-app-subscriptions.export.excel', request()->query()) }}" class="btn btn-outline-success">
                            <i class="ti ti-table me-1"></i>{{ __('Export Excel') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ti ti-credit-card me-2"></i>{{ __('Latest Subscriptions') }}</h5>
            <span class="badge bg-primary">{{ $subscriptions->total() }} {{ __('records') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Expires At') }}</th>
                            <th>{{ __('Payments') }}</th>
                            <th>{{ __('Last Transaction Amount') }}</th>
                            <th>{{ __('Last Transaction Date') }}</th>
                            <th>{{ __('Registered') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $subscription)
                            @php
                                $user = $subscription->user;
                                $plan = $subscription->plan;
                                $status = $subscription->status ?? 'unknown';
                                $statusClass = 'secondary';
                                if ($status === 'active') $statusClass = 'success';
                                if ($status === 'expired') $statusClass = 'warning';
                                if ($status === 'cancelled') $statusClass = 'danger';
                                if ($status === 'suspended') $statusClass = 'dark';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $user->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $user->email ?? '' }}</small>
                                </td>
                                <td>{{ $user->mobile_role ?? 'N/A' }}</td>
                                <td>{{ $plan->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-{{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                                <td>{{ $subscription->expires_at ? $subscription->expires_at->format('d/m/Y') : 'N/A' }}</td>
                                <td>{{ number_format($subscription->total_payments ?? 0, 0, ',', ' ') }} FCFA</td>
                                <td>{{ number_format($subscription->last_payment_amount ?? 0, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    {{ $subscription->last_payment_date ? \Carbon\Carbon::parse($subscription->last_payment_date)->format('d/m/Y H:i') : 'N/A' }}
                                </td>
                                <td>{{ $user && $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">{{ __('No subscriptions found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">{{ __('Total Payments Received') }}</th>
                                <th colspan="4">{{ number_format($totalPayments ?? 0, 0, ',', ' ') }} FCFA</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $subscriptions->links() }}
        </div>
    </div>
@endsection
