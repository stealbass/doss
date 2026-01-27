@extends('layouts.app')

@section('page-title', __('Plans Comparison'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('mobile-app-plans.index') }}">{{ __('Mobile App Plans') }}</a></li>
    <li class="breadcrumb-item">{{ __('Compare Plans') }}</li>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">{{ __('Compare Subscription Plans') }}</h3>
                <a href="{{ route('mobile-app-plans.index') }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Plans') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 200px;">{{ __('Feature') }}</th>
                            @foreach($plans as $plan)
                                <th class="text-center {{ $plan->price_monthly > 0 && $plan->price_monthly < 10000 ? 'bg-info bg-opacity-10' : '' }}" style="min-width: 180px;">
                                    <div class="fw-bold">{{ $plan->name_fr }}</div>
                                    @if($plan->price_monthly == 0)
                                        <div class="text-success h5 mb-0 mt-2">{{ __('Free') }}</div>
                                    @else
                                        <div class="h5 mb-0 mt-2">{{ number_format($plan->price_monthly, 0) }} <small>CFA</small></div>
                                        <small class="text-muted">/{{ __('month') }}</small>
                                    @endif
                                    
                                    @if(!$plan->is_active)
                                        <div class="mt-2">
                                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                        </div>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Pricing Row -->
                        <tr>
                            <td class="fw-bold bg-light">{{ __('Yearly Price') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    @if($plan->price_yearly == 0)
                                        <span class="text-success">{{ __('Free') }}</span>
                                    @else
                                        {{ number_format($plan->price_yearly, 0) }} CFA<br>
                                        @php
                                            $savings = ($plan->price_monthly * 12) - $plan->price_yearly;
                                            $savingsPercent = round(($savings / ($plan->price_monthly * 12)) * 100);
                                        @endphp
                                        @if($savings > 0)
                                            <small class="text-success">
                                                {{ __('Save') }} {{ number_format($savings, 0) }} CFA ({{ $savingsPercent }}%)
                                            </small>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        <!-- Limits Section -->
                        <tr class="table-secondary">
                            <td colspan="{{ count($plans) + 1 }}" class="fw-bold">
                                <i class="ti ti-chart-bar me-2"></i>{{ __('Usage Limits') }}
                            </td>
                        </tr>

                        <tr>
                            <td>{{ __('Legal Searches') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    @if($plan->searches_limit == -1)
                                        <span class="badge bg-success">{{ __('Unlimited') }}</span>
                                    @else
                                        <strong>{{ $plan->searches_limit }}</strong> /{{ __('month') }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        <tr>
                            <td>{{ __('AI Analyses') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    @if($plan->ai_analyses_limit == -1)
                                        <span class="badge bg-success">{{ __('Unlimited') }}</span>
                                    @else
                                        <strong>{{ $plan->ai_analyses_limit }}</strong> /{{ __('month') }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        <tr>
                            <td>{{ __('PDF Downloads') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    @if($plan->pdf_downloads_limit == -1)
                                        <span class="badge bg-success">{{ __('Unlimited') }}</span>
                                    @else
                                        <strong>{{ $plan->pdf_downloads_limit }}</strong> /{{ __('month') }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        <!-- Features Section -->
                        <tr class="table-secondary">
                            <td colspan="{{ count($plans) + 1 }}" class="fw-bold">
                                <i class="ti ti-star me-2"></i>{{ __('Features') }}
                            </td>
                        </tr>

                        <tr>
                            <td>{{ __('Full History Access') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    @if($plan->has_full_history)
                                        <i class="ti ti-check text-success" style="font-size: 24px;"></i>
                                    @else
                                        <i class="ti ti-x text-danger" style="font-size: 24px;"></i>
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        <tr>
                            <td>{{ __('Advanced AI Features') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    @if($plan->has_advanced_ai)
                                        <i class="ti ti-check text-success" style="font-size: 24px;"></i>
                                    @else
                                        <i class="ti ti-x text-danger" style="font-size: 24px;"></i>
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        <!-- AI Configuration Section -->
                        <tr class="table-secondary">
                            <td colspan="{{ count($plans) + 1 }}" class="fw-bold">
                                <i class="ti ti-cpu me-2"></i>{{ __('AI Configuration') }}
                            </td>
                        </tr>

                        <tr>
                            <td>{{ __('AI Model') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ strtoupper($plan->ai_model) }}</span>
                                </td>
                            @endforeach
                        </tr>

                        <tr>
                            <td>{{ __('Max Tokens / Request') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    <strong>{{ number_format($plan->max_tokens) }}</strong>
                                </td>
                            @endforeach
                        </tr>

                        <!-- Statistics Section -->
                        <tr class="table-secondary">
                            <td colspan="{{ count($plans) + 1 }}" class="fw-bold">
                                <i class="ti ti-users me-2"></i>{{ __('Statistics') }}
                            </td>
                        </tr>

                        <tr>
                            <td>{{ __('Active Subscriptions') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    @php
                                        $activeCount = $plan->subscriptions()
                                            ->where('status', 'active')
                                            ->where('expires_at', '>', now())
                                            ->count();
                                    @endphp
                                    <strong class="text-success">{{ $activeCount }}</strong>
                                </td>
                            @endforeach
                        </tr>

                        <tr>
                            <td>{{ __('Total Subscriptions') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    <strong>{{ $plan->subscriptions()->count() }}</strong>
                                </td>
                            @endforeach
                        </tr>

                        <!-- Actions Row -->
                        <tr>
                            <td class="fw-bold bg-light">{{ __('Actions') }}</td>
                            @foreach($plans as $plan)
                                <td class="text-center">
                                    <a href="{{ route('mobile-app-plans.edit', $plan->id) }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-edit me-1"></i>{{ __('Edit') }}
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Additional Info -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="ti ti-info-circle me-2"></i>{{ __('About Plans') }}</h6>
                    <ul class="mb-0">
                        <li>{{ __('All prices are in West African CFA Franc (FCFA)') }}</li>
                        <li>{{ __('Yearly plans typically offer 10-20% discount') }}</li>
                        <li>{{ __('Features can be modified at any time') }}</li>
                        <li>{{ __('Users on inactive plans can still use the service until expiration') }}</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="ti ti-help-circle me-2"></i>{{ __('Help') }}</h6>
                    <ul class="mb-0">
                        <li>{{ __('Unlimited = -1 in limit fields') }}</li>
                        <li>{{ __('AI Model determines response quality and cost') }}</li>
                        <li>{{ __('Max Tokens controls response length') }}</li>
                        <li>{{ __('Active subscriptions prevent plan deactivation') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .table th, .table td {
        vertical-align: middle;
    }

    .table-bordered > :not(caption) > * > * {
        border-width: 1px;
    }
</style>
@endpush
