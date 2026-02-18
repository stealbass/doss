@extends('layouts.app')

@section('page-title', __('User Details'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('mobile-users.index') }}">{{ __('Mobile Users') }}</a></li>
    <li class="breadcrumb-item">{{ $user->name }}</li>
@endsection

@section('content')
    <!-- User Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-primary-light rounded-circle d-flex align-items-center justify-content-center me-3">
                            <span class="text-primary fw-bold" style="font-size: 32px;">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h3 class="mb-1">{{ $user->name }}</h3>
                            <div class="text-muted mb-2">
                                <i class="ti ti-mail me-1"></i>{{ $user->email }}
                                @if($user->phone)
                                    <span class="ms-3"><i class="ti ti-phone me-1"></i>{{ $user->phone }}</span>
                                @endif
                            </div>
                            <div>
                                @if($user->mobile_role == 'student')
                                    <span class="badge bg-info me-2">
                                        <i class="ti ti-book me-1"></i>{{ __('Student') }}
                                    </span>
                                @elseif($user->mobile_role == 'lawyer')
                                    <span class="badge bg-warning me-2">
                                        <i class="ti ti-briefcase me-1"></i>{{ __('Lawyer') }}
                                    </span>
                                @elseif($user->mobile_role == 'enterprise')
                                    <span class="badge bg-primary me-2">
                                        <i class="ti ti-building me-1"></i>{{ __('Enterprise') }}
                                    </span>
                                @endif

                                @php
                                    $subscription = $user->activeMobileSubscription;
                                @endphp
                                
                                @if($subscription)
                                    @if($subscription->status == 'active' && $subscription->expires_at > now())
                                        <span class="badge bg-success">
                                            <i class="ti ti-check me-1"></i>{{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="ti ti-x me-1"></i>{{ __('Inactive') }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="openExtendSubscriptionModal()">
                            <i class="ti ti-clock-plus me-1"></i>{{ __('Extend') }}
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="openChangePlanModal()">
                            <i class="ti ti-repeat me-1"></i>{{ __('Change Plan') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="openResetPasswordModal()">
                            <i class="ti ti-key me-1"></i>{{ __('Reset Password') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="ti ti-messages text-primary" style="font-size: 32px;"></i>
                    <h4 class="mt-2 mb-0">{{ $userStats['total_conversations'] }}</h4>
                    <small class="text-muted">{{ __('Conversations') }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="ti ti-file-text text-info" style="font-size: 32px;"></i>
                    <h4 class="mt-2 mb-0">{{ $userStats['total_documents'] }}</h4>
                    <small class="text-muted">{{ __('Documents') }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="ti ti-download text-success" style="font-size: 32px;"></i>
                    <h4 class="mt-2 mb-0">{{ $userStats['total_downloads'] }}</h4>
                    <small class="text-muted">{{ __('Downloads') }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="ti ti-currency-dollar text-warning" style="font-size: 32px;"></i>
                    <h4 class="mt-2 mb-0">{{ number_format($userStats['total_payments'], 0) }}</h4>
                    <small class="text-muted">CFA</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="ti ti-users text-secondary" style="font-size: 32px;"></i>
                    <h4 class="mt-2 mb-0">{{ $userStats['referrals_made'] }}</h4>
                    <small class="text-muted">{{ __('Referrals') }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="ti ti-calendar text-dark" style="font-size: 32px;"></i>
                    <h4 class="mt-2 mb-0">{{ $user->created_at->diffForHumans(null, true) }}</h4>
                    <small class="text-muted">{{ __('Member since') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-xl-8">
            <!-- Current Subscription -->
            @if($subscription)
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-credit-card me-2"></i>{{ __('Current Subscription') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">{{ __('Plan') }}</label>
                                    <div class="fw-bold">{{ $subscription->plan->name }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">{{ __('Status') }}</label>
                                    <div>
                                        @if($subscription->status == 'active' && $subscription->expires_at > now())
                                            <span class="badge bg-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ $subscription->status }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">{{ __('Auto Renew') }}</label>
                                    <div>
                                        @if($subscription->auto_renew)
                                            <span class="badge bg-success">{{ __('Enabled') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">{{ __('Starts At') }}</label>
                                    <div class="fw-bold">{{ $subscription->started_at ? $subscription->started_at->format('d/m/Y H:i') : '-' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">{{ __('Expires At') }}</label>
                                    <div class="fw-bold">{{ $subscription->expires_at ? $subscription->expires_at->format('d/m/Y H:i') : __('Never') }}</div>
                                    @if($subscription->expires_at && $subscription->expires_at < now()->addDays(7))
                                        <small class="text-danger">
                                            <i class="ti ti-alert-triangle"></i> {{ __('Expires soon') }}
                                        </small>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">{{ __('Price') }}</label>
                                    <div class="fw-bold">{{ number_format($subscription->plan->price_monthly, 0) }} CFA/{{ __('month') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Payment History -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-receipt me-2"></i>{{ __('Payment History') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Transaction ID') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->mobilePayments()->latest()->limit(10)->get() as $payment)
                                    <tr>
                                        <td><code>{{ $payment->transaction_id }}</code></td>
                                        <td class="fw-bold">{{ number_format($payment->amount, 0) }} CFA</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ strtoupper($payment->payment_method) }}</span>
                                        </td>
                                        <td>
                                            @if($payment->status == 'completed' || $payment->status == 'successful')
                                                <span class="badge bg-success">{{ __('Completed') }}</span>
                                            @elseif($payment->status == 'pending')
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('Failed') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            {{ __('No payments yet') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Conversations -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-messages me-2"></i>{{ __('Recent Conversations') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Messages') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th>{{ __('Updated At') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->conversations()->latest()->limit(10)->get() as $conversation)
                                    <tr>
                                        <td>{{ Str::limit($conversation->title, 50) }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $conversation->messages()->count() }}</span>
                                        </td>
                                        <td>{{ $conversation->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $conversation->updated_at->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ __('No conversations yet') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-xl-4">
            <!-- User Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-info-circle me-2"></i>{{ __('User Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">{{ __('User ID') }}</label>
                        <div class="fw-bold"><code>{{ $user->id }}</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">{{ __('Referral Code') }}</label>
                        <div class="fw-bold">
                            @if($user->referral_code)
                                <code>{{ $user->referral_code }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">{{ __('Jurisdiction') }}</label>
                        <div class="fw-bold">{{ $user->jurisdiction ?? '-' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">{{ __('Registered At') }}</label>
                        <div class="fw-bold">{{ $user->created_at->format('d/m/Y H:i') }}</div>
                        <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small">{{ __('Last Updated') }}</label>
                        <div class="fw-bold">{{ $user->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Subscription History -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-history me-2"></i>{{ __('Subscription History') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($user->mobileSubscriptions()->latest()->limit(5)->get() as $sub)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold">{{ $sub->plan->name }}</div>
                                        <small class="text-muted">
                                            {{ $sub->started_at ? $sub->started_at->format('d/m/Y') : '-' }} - {{ $sub->expires_at ? $sub->expires_at->format('d/m/Y') : __('Never') }}
                                        </small>
                                    </div>
                                    <div>
                                        @if($sub->status == 'active')
                                            <span class="badge bg-success">{{ __('Active') }}</span>
                                        @elseif($sub->status == 'expired')
                                            <span class="badge bg-danger">{{ __('Expired') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $sub->status }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted">
                                {{ __('No subscription history') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Referrals -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-users me-2"></i>{{ __('Referrals') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">{{ __('Referrals Made') }}</label>
                        <div class="fw-bold">{{ $userStats['referrals_made'] }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small">{{ __('Referred By') }}</label>
                        <div class="fw-bold">
                            @if($user->referralsReceived->count() > 0)
                                {{ $user->referralsReceived->first()->referrer->name }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('mobile-users.modals.extend-subscription')
    @include('mobile-users.modals.change-plan')
    @include('mobile-users.modals.reset-password')
@endsection

@push('custom-script')
<script>
    function openExtendSubscriptionModal() {
        new bootstrap.Modal(document.getElementById('extendSubscriptionModal')).show();
    }

    function openChangePlanModal() {
        new bootstrap.Modal(document.getElementById('changePlanModal')).show();
    }

    function openResetPasswordModal() {
        new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
    }
</script>
@endpush

@push('style')
<style>
    .avatar-lg {
        width: 80px;
        height: 80px;
    }

    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }
</style>
@endpush
