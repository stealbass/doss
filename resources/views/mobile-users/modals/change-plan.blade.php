<!-- Change Plan Modal -->
<div class="modal fade" id="changePlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Change User Plan') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePlanForm" action="{{ route('mobile-users.change-plan', $user->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('New Plan') }}</label>
                        <select class="form-select" name="plan_id" required>
                            <option value="">{{ __('Select a plan') }}</option>
                            @foreach(\App\Models\MobileAppPlan::where('is_active', true)->get() as $plan)
                                <option value="{{ $plan->id }}" 
                                        @if($subscription && $subscription->plan_id == $plan->id) selected @endif>
                                    {{ $plan->name }} - {{ number_format($plan->price_monthly, 0) }} CFA/mois
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Duration (months)') }}</label>
                        <select class="form-select" name="duration" required>
                            <option value="1">1 {{ __('month') }}</option>
                            <option value="3" selected>3 {{ __('months') }}</option>
                            <option value="6">6 {{ __('months') }}</option>
                            <option value="12">12 {{ __('months') }}</option>
                        </select>
                    </div>

                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        {{ __('The current subscription will be cancelled and replaced by the new one.') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-repeat me-1"></i>{{ __('Change Plan') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('changePlanForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = this.action;
    
    const data = {
        plan_id: formData.get('plan_id'),
        duration: formData.get('duration')
    };

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
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
</script>