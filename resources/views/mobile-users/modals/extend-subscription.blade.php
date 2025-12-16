<!-- Extend Subscription Modal -->
<div class="modal fade" id="extendSubscriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Extend Subscription') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="extendSubscriptionForm" action="{{ route('mobile-users.extend-subscription', $user->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Extend Duration (months)') }}</label>
                        <select class="form-select" name="months" required>
                            <option value="1">1 {{ __('month') }}</option>
                            <option value="2">2 {{ __('months') }}</option>
                            <option value="3" selected>3 {{ __('months') }}</option>
                            <option value="6">6 {{ __('months') }}</option>
                            <option value="12">12 {{ __('months') }}</option>
                        </select>
                    </div>

                    @if($subscription)
                        <div class="alert alert-info">
                            <strong>{{ __('Current Expiration') }}:</strong> {{ $subscription->expires_at->format('d/m/Y') }}<br>
                            <small>{{ __('The subscription will be extended from this date') }}</small>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-clock-plus me-1"></i>{{ __('Extend Subscription') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('extendSubscriptionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = this.action;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('extendSubscriptionModal')).hide();
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