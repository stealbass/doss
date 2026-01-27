@php
    // Ne pas afficher le popup sur les pages de plans/paiement
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $paymentRoutes = [
        'plan.', 'plans.', 'stripe', 'paypal', 'mercado', 'mollie', 
        'skrill', 'coingate', 'paystack', 'flaterwave', 'razorpay', 
        'paytm', 'toyyibpay', 'sspay', 'bank.transfer', 'error.plan'
    ];
    
    $isPlansPage = false;
    foreach ($paymentRoutes as $prefix) {
        if (str_starts_with($currentRoute, $prefix)) {
            $isPlansPage = true;
            break;
        }
    }
@endphp

@if(session('subscription_expired') && !$isPlansPage)
<div class="modal fade show" id="subscriptionExpiredModal" tabindex="-1" aria-labelledby="subscriptionExpiredModalLabel" 
     aria-modal="true" role="dialog" style="display: block; background-color: rgba(0,0,0,0.7);" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border: 3px solid #dc3545; box-shadow: 0 10px 30px rgba(220, 53, 69, 0.4);">
            
            <!-- Header -->
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-bottom: none;">
                <div class="w-100 text-center">
                    <div style="font-size: 60px; margin-bottom: 10px;">⚠️</div>
                    <h3 class="modal-title text-white mb-0" style="font-size: 24px; font-weight: 700;">
                        ABONNEMENT EXPIRÉ
                    </h3>
                    <p class="text-white-50 mb-0 mt-2" style="font-size: 14px;">
                        Votre abonnement a expiré le {{ date('d/m/Y', strtotime(session('expiration_date'))) }}
                    </p>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body p-4" style="background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);">
                
                <!-- Message principal -->
                <div class="alert alert-warning mb-4" style="border-left: 4px solid #ffc107;">
                    <div class="d-flex align-items-center">
                        <div style="font-size: 40px; margin-right: 15px;">⏰</div>
                        <div>
                            <h5 class="mb-1" style="color: #856404;">Accès limité</h5>
                            <p class="mb-0" style="color: #856404; font-size: 14px;">
                                Votre abonnement a expiré. Pour continuer à utiliser toutes les fonctionnalités de Dossy Pro, 
                                veuillez renouveler votre abonnement.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Avantages du renouvellement -->
                <div class="card mb-3" style="background: linear-gradient(to right, #e8f5e9, #f8fff9); border: 1px solid #28a745;">
                    <div class="card-body">
                        <h6 class="text-success mb-3">
                            <i class="ti ti-star-filled"></i> En renouvelant, vous retrouvez :
                        </h6>
                        <ul class="mb-0" style="list-style: none; padding-left: 0;">
                            <li class="mb-2"><i class="ti ti-check text-success"></i> Accès complet à toutes vos affaires</li>
                            <li class="mb-2"><i class="ti ti-check text-success"></i> Gestion de vos clients et documents</li>
                            <li class="mb-2"><i class="ti ti-check text-success"></i> Bibliothèque juridique complète</li>
                            <li class="mb-2"><i class="ti ti-check text-success"></i> Calendrier et rappels automatiques</li>
                            <li class="mb-0"><i class="ti ti-check text-success"></i> Support prioritaire</li>
                        </ul>
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="text-center mb-3">
                    <a href="{{ route('plans.index') }}" class="btn btn-lg btn-success" 
                       style="background: linear-gradient(135deg, #28a745 0%, #20923d 100%); 
                              border: none; padding: 12px 40px; font-size: 16px; font-weight: 600; 
                              box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3); border-radius: 25px;">
                        <i class="ti ti-credit-card"></i> Renouveler Mon Abonnement
                    </a>
                </div>

                <!-- Info supplémentaire -->
                <div class="text-center">
                    <small class="text-muted">
                        <i class="ti ti-info-circle"></i> Vous pouvez toujours accéder à cette page depuis n'importe où
                    </small>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-outline-secondary" onclick="closeExpiredModal()">
                    Fermer
                </button>
            </div>

        </div>
    </div>
</div>

<script>
function closeExpiredModal() {
    document.getElementById('subscriptionExpiredModal').style.display = 'none';
    document.body.classList.remove('modal-open');
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

// Add modal-open class to body
document.body.classList.add('modal-open');

// Add backdrop
if (!document.querySelector('.modal-backdrop')) {
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    document.body.appendChild(backdrop);
}
</script>

<style>
#subscriptionExpiredModal {
    z-index: 9999 !important;
}
.modal-backdrop {
    z-index: 9998 !important;
}
</style>
@endif
