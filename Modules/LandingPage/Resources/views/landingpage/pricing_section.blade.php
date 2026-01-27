@php
    $plans = \App\Models\Plan::where('status', 1)->get();
    $settings = \App\Models\Utility::settings();
@endphp

<section class="pricing-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="text-center mb-5">
                    <h2 class="mb-3">{{ $settings['plan_title'] ?? __('Nos Plans Tarifaires') }}</h2>
                    <p class="text-muted">{{ $settings['plan_description'] ?? __('Choisissez le plan qui correspond à vos besoins') }}</p>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            @foreach($plans as $plan)
            <div class="col-lg-3 col-md-6">
                <div class="card pricing-card h-100 shadow-sm {{ $plan->price == 0 ? 'border-success' : '' }}">
                    <div class="card-header text-center bg-dark text-white py-3">
                        <h5 class="mb-0">{{ $plan->name }}</h5>
                    </div>
                    <div class="card-body text-center p-4">
                        <!-- Prix -->
                        <div class="pricing-amount mb-4">
                            <h2 class="mb-0" style="font-size: 3rem; font-weight: bold;">
                                {{ number_format($plan->price, 0, '', ' ') }}
                            </h2>
                            <div class="pricing-currency" style="font-size: 1.5rem;">
                                FCFA<sub>/{{ __('year') }}</sub>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-muted mb-4">{{ $plan->description }}</p>

                        <!-- Features List -->
                        <ul class="list-unstyled text-start mb-4">
                            <li class="mb-3">
                                <i class="ti ti-check text-success me-2"></i>
                                {{ __('Bibliothèque juridique gratuite') }}
                            </li>
                            <li class="mb-3">
                                <i class="ti ti-check text-success me-2"></i>
                                {{ __('IA juridique gratuite') }}
                            </li>
                            <li class="mb-3">
                                <i class="ti ti-check text-success me-2"></i>
                                @if($plan->storage_limit < 0)
                                    {{ __('Stockage illimité') }}
                                @else
                                    {{ number_format($plan->storage_limit / 1024, 0) }}GB {{ __('Stockage') }}
                                @endif
                            </li>
                            @if($plan->enable_chatgpt == 'on')
                            <li class="mb-3">
                                <i class="ti ti-check text-success me-2"></i>
                                {{ __('ChatGPT Activé') }}
                            </li>
                            @endif
                        </ul>

                        <!-- CTA Button -->
                        <div class="d-grid">
                            <a href="{{ route('register') }}" 
                               class="btn {{ $plan->price == 0 ? 'btn-success' : 'btn-primary' }} btn-lg">
                                <i class="ti ti-arrow-right me-2"></i>
                                {{ $plan->price == 0 ? __('Commencer Gratuitement') : __('Commencer par Plan ' . $plan->name) }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Features Comparison Table Toggle -->
        <div class="row mt-5">
            <div class="col-lg-12 text-center">
                <button class="btn btn-outline-primary btn-lg" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#featuresComparison" 
                        aria-expanded="false" 
                        aria-controls="featuresComparison"
                        id="toggleFeaturesBtn">
                    {{ __('Découvrir toutes les fonctionnalités') }}
                    <i class="ti ti-chevron-down ms-2" id="toggleIcon"></i>
                </button>
            </div>
        </div>

        <!-- Features Comparison Table (Hidden by default) -->
        <div class="collapse mt-4" id="featuresComparison">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">{{ __('Comparaison Détaillée des Fonctionnalités') }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40%;">{{ __('Fonctionnalités') }}</th>
                                            @foreach($plans as $plan)
                                            <th class="text-center" style="width: {{ 60 / count($plans) }}%;">
                                                <strong>{{ $plan->name }}</strong>
                                            </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Utilisateurs -->
                                        <tr>
                                            <td><strong>{{ __('Nombre d\'utilisateurs') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                @if($plan->max_users < 0)
                                                    <i class="ti ti-infinity text-success" style="font-size: 1.5rem;"></i>
                                                @else
                                                    {{ $plan->max_users }}
                                                @endif
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- Avocats/Juristes -->
                                        <tr>
                                            <td><strong>{{ __('Avocats/Juristes') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                @if($plan->max_advocates < 0)
                                                    <i class="ti ti-infinity text-success" style="font-size: 1.5rem;"></i>
                                                @else
                                                    {{ $plan->max_advocates }}
                                                @endif
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- Stockage -->
                                        <tr>
                                            <td><strong>{{ __('Espace de stockage') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                @if($plan->storage_limit < 0)
                                                    <i class="ti ti-infinity text-success" style="font-size: 1.5rem;"></i>
                                                @else
                                                    {{ number_format($plan->storage_limit / 1024, 0) }}GB
                                                @endif
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- Bibliothèque Juridique -->
                                        <tr>
                                            <td><strong>{{ __('Bibliothèque juridique') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                <i class="ti ti-check text-success" style="font-size: 1.5rem;"></i>
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- IA Juridique -->
                                        <tr>
                                            <td><strong>{{ __('IA juridique (ChatGPT)') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                @if($plan->enable_chatgpt == 'on')
                                                    <i class="ti ti-check text-success" style="font-size: 1.5rem;"></i>
                                                @else
                                                    <i class="ti ti-x text-danger" style="font-size: 1.5rem;"></i>
                                                @endif
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- Gestion des dossiers -->
                                        <tr>
                                            <td><strong>{{ __('Gestion des dossiers') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                <i class="ti ti-check text-success" style="font-size: 1.5rem;"></i>
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- Calendrier & Audiences -->
                                        <tr>
                                            <td><strong>{{ __('Calendrier & Audiences') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                <i class="ti ti-check text-success" style="font-size: 1.5rem;"></i>
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- Facturation -->
                                        <tr>
                                            <td><strong>{{ __('Facturation') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                <i class="ti ti-check text-success" style="font-size: 1.5rem;"></i>
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- Support -->
                                        <tr>
                                            <td><strong>{{ __('Support technique') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                @if($plan->price == 0)
                                                    {{ __('Email') }}
                                                @elseif($plan->price > 0 && $plan->price < 500000)
                                                    {{ __('Email + Chat') }}
                                                @else
                                                    {{ __('Prioritaire 24/7') }}
                                                @endif
                                            </td>
                                            @endforeach
                                        </tr>

                                        <!-- Formation -->
                                        <tr>
                                            <td><strong>{{ __('Formation gratuite') }}</strong></td>
                                            @foreach($plans as $plan)
                                            <td class="text-center">
                                                @if($plan->price > 0)
                                                    <i class="ti ti-check text-success" style="font-size: 1.5rem;"></i>
                                                @else
                                                    <i class="ti ti-x text-danger" style="font-size: 1.5rem;"></i>
                                                @endif
                                            </td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('custom-script')
<script>
    // Toggle icon rotation when features table is shown/hidden
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleFeaturesBtn');
        const toggleIcon = document.getElementById('toggleIcon');
        const featuresTable = document.getElementById('featuresComparison');
        
        if (toggleBtn && toggleIcon && featuresTable) {
            featuresTable.addEventListener('shown.bs.collapse', function () {
                toggleIcon.classList.remove('ti-chevron-down');
                toggleIcon.classList.add('ti-chevron-up');
                toggleBtn.innerHTML = '{{ __("Masquer les fonctionnalités") }} <i class="ti ti-chevron-up ms-2" id="toggleIcon"></i>';
            });
            
            featuresTable.addEventListener('hidden.bs.collapse', function () {
                toggleIcon.classList.remove('ti-chevron-up');
                toggleIcon.classList.add('ti-chevron-down');
                toggleBtn.innerHTML = '{{ __("Découvrir toutes les fonctionnalités") }} <i class="ti ti-chevron-down ms-2" id="toggleIcon"></i>';
            });
        }
    });
</script>
@endpush

@push('style')
<style>
    .pricing-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .pricing-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
    }
    
    .pricing-card.border-success {
        border: 3px solid #28a745 !important;
    }
    
    .pricing-card .card-header {
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }
    
    .pricing-amount {
        padding: 20px 0;
    }
    
    .pricing-currency sub {
        font-size: 0.6em;
        color: #6c757d;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    #toggleFeaturesBtn {
        border-radius: 50px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    #toggleFeaturesBtn:hover {
        transform: scale(1.05);
    }
    
    #toggleIcon {
        transition: transform 0.3s ease;
    }
</style>
@endpush
