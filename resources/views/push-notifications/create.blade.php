@extends('layouts.app')

@section('page-title', __('Create Push Notification'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('push-notifications.index') }}">{{ __('Push Notifications') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-bell-plus me-2"></i>{{ __('New Push Notification') }}</h5>
                </div>
                
                <form action="{{ route('push-notifications.store') }}" method="POST" id="notificationForm">
                    @csrf
                    
                    <div class="card-body">
                        <!-- Content Section -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-message me-1"></i>{{ __('Content') }}
                        </h6>
                        
                        <div class="mb-3">
                            <label class="form-label required">{{ __('Title') }}</label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   name="title" 
                                   value="{{ old('title') }}" 
                                   maxlength="255"
                                   required>
                            <small class="text-muted">{{ __('Maximum 255 characters') }}</small>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label required">{{ __('Message Body') }}</label>
                            <textarea class="form-control @error('body') is-invalid @enderror" 
                                      name="body" 
                                      rows="4" 
                                      maxlength="1000"
                                      required>{{ old('body') }}</textarea>
                            <small class="text-muted">{{ __('Maximum 1000 characters') }}</small>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Type & Targeting -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-target me-1"></i>{{ __('Type & Targeting') }}
                        </h6>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('Notification Type') }}</label>
                                <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                                    <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>{{ __('General') }}</option>
                                    <option value="promotion" {{ old('type') == 'promotion' ? 'selected' : '' }}>{{ __('Promotion') }}</option>
                                    <option value="alert" {{ old('type') == 'alert' ? 'selected' : '' }}>{{ __('Alert') }}</option>
                                    <option value="update" {{ old('type') == 'update' ? 'selected' : '' }}>{{ __('Update') }}</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">{{ __('Target Audience') }}</label>
                                <select class="form-select @error('target_audience') is-invalid @enderror" 
                                        name="target_audience" 
                                        id="targetAudience"
                                        required>
                                    @foreach($targetAudiences as $key => $label)
                                        <option value="{{ $key }}" {{ old('target_audience') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('target_audience')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4" id="planSelection" style="display: none;">
                            <label class="form-label">{{ __('Select Plan') }}</label>
                            <select class="form-select @error('target_plan') is-invalid @enderror" 
                                    name="target_plan"
                                    id="targetPlan">
                                <option value="">{{ __('Select a plan') }}</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name_fr }}</option>
                                @endforeach
                            </select>
                            @error('target_plan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Recipient Preview -->
                        <div class="alert alert-info" id="recipientPreview">
                            <i class="ti ti-users me-2"></i>
                            <strong>{{ __('Recipients') }}:</strong> <span id="recipientCount">0</span>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="updateRecipientCount()">
                                <i class="ti ti-refresh"></i> {{ __('Update') }}
                            </button>
                        </div>

                        <!-- Advanced Options -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-settings me-1"></i>{{ __('Advanced Options') }}
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Image URL') }}</label>
                            <input type="url" 
                                   class="form-control @error('image_url') is-invalid @enderror" 
                                   name="image_url" 
                                   value="{{ old('image_url') }}" 
                                   placeholder="https://example.com/image.jpg">
                            <small class="text-muted">{{ __('Optional: Image to display in notification') }}</small>
                            @error('image_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">{{ __('Action URL') }}</label>
                            <input type="text" 
                                   class="form-control @error('action_url') is-invalid @enderror" 
                                   name="action_url" 
                                   value="{{ old('action_url') }}" 
                                   placeholder="/screen/detail">
                            <small class="text-muted">{{ __('Optional: Deep link in app when notification is clicked') }}</small>
                            @error('action_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Scheduling -->
                        <h6 class="mb-3 text-primary">
                            <i class="ti ti-clock me-1"></i>{{ __('Scheduling') }}
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Schedule For Later') }}</label>
                            <input type="datetime-local" 
                                   class="form-control @error('scheduled_at') is-invalid @enderror" 
                                   name="scheduled_at" 
                                   value="{{ old('scheduled_at') }}"
                                   min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                            <small class="text-muted">{{ __('Leave empty to save as draft or send immediately') }}</small>
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('push-notifications.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i>{{ __('Cancel') }}
                        </a>
                        <div>
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="ti ti-file me-1"></i>{{ __('Save as Draft') }}
                            </button>
                            <button type="submit" name="send_now" value="1" class="btn btn-success">
                                <i class="ti ti-send me-1"></i>{{ __('Send Now') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Panel -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ti ti-eye me-2"></i>{{ __('Preview') }}</h6>
                </div>
                <div class="card-body">
                    <div class="notification-preview border rounded p-3 bg-light">
                        <div class="d-flex align-items-start">
                            <div class="me-2">
                                <i class="ti ti-bell text-primary" style="font-size: 24px;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold mb-1" id="preview-title">{{ __('Notification Title') }}</div>
                                <div class="small text-muted" id="preview-body">{{ __('Notification message will appear here...') }}</div>
                                <small class="text-muted">{{ __('Just now') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="ti ti-info-circle me-1"></i>
                            {{ __('This is how your notification will appear on mobile devices') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Toggle plan selection
    document.getElementById('targetAudience').addEventListener('change', function() {
        const planSelection = document.getElementById('planSelection');
        const targetPlan = document.getElementById('targetPlan');
        
        if (this.value === 'plan_specific') {
            planSelection.style.display = 'block';
            targetPlan.required = true;
        } else {
            planSelection.style.display = 'none';
            targetPlan.required = false;
        }
        
        updateRecipientCount();
    });

    // Update recipient count
    function updateRecipientCount() {
        const audience = document.getElementById('targetAudience').value;
        const planId = document.getElementById('targetPlan').value;

        fetch(`{{ route('push-notifications.preview-recipients') }}?audience=${audience}&plan_id=${planId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('recipientCount').textContent = data.total.toLocaleString();
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // Live preview
    document.querySelector('input[name="title"]').addEventListener('input', function() {
        document.getElementById('preview-title').textContent = this.value || '{{ __("Notification Title") }}';
    });

    document.querySelector('textarea[name="body"]').addEventListener('input', function() {
        document.getElementById('preview-body').textContent = this.value || '{{ __("Notification message will appear here...") }}';
    });

    // Initial recipient count
    updateRecipientCount();
</script>
@endpush

@push('styles')
<style>
    .form-label.required::after {
        content: ' *';
        color: red;
    }

    .notification-preview {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
</style>
@endpush
