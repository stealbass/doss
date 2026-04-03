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
                
                <form action="{{ route('push-notifications.store') }}" method="POST" id="notificationForm" enctype="multipart/form-data">
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
                                      id="summernote"
                                      required>{{ old('body') }}</textarea>
                            <small class="text-muted">{{ __('Utilisez l\'éditeur pour formater votre texte et ajouter des images') }}</small>
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
                        <!-- Specific Users Selection -->
                        <div class="mb-4" id="specificUsersSelection" style="display: none;">
                            <label class="form-label">{{ __('Select Users') }}</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                @foreach($users as $user)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="specific_users[]" 
                                               value="{{ $user->id }}" 
                                               id="user{{ $user->id }}">
                                        <label class="form-check-label" for="user{{ $user->id }}">
                                            <strong>{{ $user->name }}</strong> <small class="text-muted">({{ $user->email }})</small>
                                            @if($user->activeMobileSubscription)
                                                <span class="badge bg-success">{{ $user->activeMobileSubscription->plan->name }}</span>
                                            @endif
                                            <span class="badge bg-info">{{ ucfirst($user->mobile_role ?? 'N/A') }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllUsers(true)">
                                    <i class="ti ti-checkbox"></i> {{ __('Select All') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllUsers(false)">
                                    <i class="ti ti-square"></i> {{ __('Deselect All') }}
                                </button>
                            </div>
                            @error('specific_users')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Specific Users Selection -->
                        <div class="mb-4" id="specificUsersSelection" style="display: none;">
                            <label class="form-label">{{ __('Select Users') }}</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                @foreach($users as $user)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="specific_users[]" 
                                               value="{{ $user->id }}" 
                                               id="user{{ $user->id }}">
                                        <label class="form-check-label" for="user{{ $user->id }}">
                                            <strong>{{ $user->name }}</strong> <small class="text-muted">({{ $user->email }})</small>
                                            @if($user->activeMobileSubscription)
                                                <span class="badge bg-success">{{ $user->activeMobileSubscription->plan->name }}</span>
                                            @endif
                                            <span class="badge bg-info">{{ ucfirst($user->mobile_role ?? 'N/A') }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllUsers(true)">
                                    <i class="ti ti-check-all"></i> {{ __('Select All') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllUsers(false)">
                                    <i class="ti ti-x"></i> {{ __('Deselect All') }}
                                </button>
                            </div>
                            @error('specific_users')
                                <div class="text-danger mt-2">{{ $message }}</div>
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
                            <label class="form-label">{{ __('Notification Image') }}</label>
                            <input type="file" 
                                   class="form-control @error('image') is-invalid @enderror" 
                                   name="image" 
                                   accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
                                   id="imageUpload">
                            <small class="text-muted">{{ __('Optional: Image to display in notification (PNG, JPG, GIF, WEBP - Max 20MB)') }}</small>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <img src="" alt="Preview" class="img-thumbnail" style="max-width: 200px;">
                            </div>
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

@push('custom-script')
<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Summernote
        $('#summernote').summernote({
            height: 300,
            minHeight: 200,
            maxHeight: 500,
            focus: false,
            placeholder: 'Rédigez votre message ici. Vous pouvez formater le texte et ajouter des images...',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    uploadImage(files[0]);
                },
                onChange: function(contents) {
                    // Update live preview (strip HTML for preview)
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = contents;
                    const textContent = tempDiv.textContent || tempDiv.innerText || '';
                    document.getElementById('preview-body').textContent = textContent.substring(0, 100) + 
                        (textContent.length > 100 ? '...' : '');
                }
            }
        });

        // Upload image function
        function uploadImage(file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("push-notifications.upload-image") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#summernote').summernote('insertImage', data.url);
                } else {
                    alert('Erreur lors de l\'upload de l\'image: ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de l\'upload de l\'image');
            });
        }
    });

    // Toggle plan and specific users selection
    document.getElementById('targetAudience').addEventListener('change', function() {
        const planSelection = document.getElementById('planSelection');
        const targetPlan = document.getElementById('targetPlan');
        const specificUsersSelection = document.getElementById('specificUsersSelection');
        
        // Hide all first
        planSelection.style.display = 'none';
        specificUsersSelection.style.display = 'none';
        targetPlan.required = false;
        
        // Show relevant section
        if (this.value === 'plan_specific') {
            planSelection.style.display = 'block';
            targetPlan.required = true;
        } else if (this.value === 'specific_users') {
            specificUsersSelection.style.display = 'block';
        }
        
        updateRecipientCount();
    });

    document.getElementById('targetPlan').addEventListener('change', function() {
        updateRecipientCount();
    });

    // Select/Deselect all users
    function selectAllUsers(select) {
        const checkboxes = document.querySelectorAll('input[name="specific_users[]"]');
        checkboxes.forEach(cb => cb.checked = select);
        updateRecipientCount();
    }

    // Update recipient count
    function updateRecipientCount() {
        const audience = document.getElementById('targetAudience').value;
        const planId = document.getElementById('targetPlan').value;
        
        if (audience === 'specific_users') {
            const checked = document.querySelectorAll('input[name="specific_users[]"]:checked').length;
            document.getElementById('recipientCount').textContent = checked;
            return;
        }

        fetch(`{{ route('push-notifications.preview-recipients') }}?audience=${audience}&plan_id=${planId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('recipientCount').textContent = data.total.toLocaleString();
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // Image preview
    document.getElementById('imageUpload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('imagePreview');
                preview.querySelector('img').src = event.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Live preview - Title only (body handled by Summernote callback)
    document.querySelector('input[name="title"]').addEventListener('input', function() {
        document.getElementById('preview-title').textContent = this.value || '{{ __("Notification Title") }}';
    });

    // Update count on checkbox change
    document.addEventListener('change', function(e) {
        if (e.target.name === 'specific_users[]') {
            updateRecipientCount();
        }
    });

    // Initial recipient count
    updateRecipientCount();
</script>
@endpush

@push('style')
<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
<style>
    .form-label.required::after {
        content: ' *';
        color: red;
    }

    .notification-preview {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .note-editor.note-frame {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }

    .note-editor.note-frame .note-editing-area .note-editable {
        min-height: 200px;
    }
</style>
@endpush
