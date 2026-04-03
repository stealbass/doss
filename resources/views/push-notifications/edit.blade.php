@extends('layouts.app')

@section('page-title', __('Edit Push Notification'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('push-notifications.index') }}">{{ __('Push Notifications') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-edit me-2"></i>{{ __('Edit Push Notification') }}</h5>
                </div>
                
                <form action="{{ route('push-notifications.update', $notification->id) }}" method="POST" id="notificationForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
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
                                   value="{{ old('title', $notification->title) }}" 
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
                                      required>{{ old('body', $notification->body) }}</textarea>
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
                                    <option value="general" {{ old('type', $notification->type) == 'general' ? 'selected' : '' }}>{{ __('General') }}</option>
                                    <option value="promotion" {{ old('type', $notification->type) == 'promotion' ? 'selected' : '' }}>{{ __('Promotion') }}</option>
                                    <option value="alert" {{ old('type', $notification->type) == 'alert' ? 'selected' : '' }}>{{ __('Alert') }}</option>
                                    <option value="update" {{ old('type', $notification->type) == 'update' ? 'selected' : '' }}>{{ __('Update') }}</option>
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
                                        <option value="{{ $key }}" {{ old('target_audience', $notification->target_audience) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('target_audience')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4" id="planSelection" style="display: {{ old('target_audience', $notification->target_audience) == 'plan_specific' ? 'block' : 'none' }};">
                            <label class="form-label">{{ __('Select Plan') }}</label>
                            <select class="form-select @error('target_plan') is-invalid @enderror" 
                                    name="target_plan"
                                    id="targetPlan">
                                <option value="">{{ __('Select a plan') }}</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ old('target_plan', $notification->target_plan) == $plan->id ? 'selected' : '' }}>{{ $plan->name_fr }}</option>
                                @endforeach
                            </select>
                            @error('target_plan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Specific Users Selection -->
                        <div class="mb-4" id="specificUsersSelection" style="display: {{ old('target_audience', $notification->target_audience) == 'specific_users' ? 'block' : 'none' }};">
                            <label class="form-label">{{ __('Select Users') }}</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllUsers()">
                                        <i class="ti ti-check-all"></i> {{ __('Select All') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllUsers()">
                                        <i class="ti ti-x"></i> {{ __('Deselect All') }}
                                    </button>
                                </div>
                                <hr>
                                @php
                                    $rawSpecificUsers = old('specific_users', $notification->specific_users);
                                    if (is_array($rawSpecificUsers)) {
                                        $selectedUsers = $rawSpecificUsers;
                                    } elseif (is_string($rawSpecificUsers)) {
                                        $decoded = json_decode($rawSpecificUsers, true);
                                        $selectedUsers = is_array($decoded) ? $decoded : [];
                                    } else {
                                        $selectedUsers = [];
                                    }
                                @endphp
                                @foreach($users as $user)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input user-checkbox" 
                                               type="checkbox" 
                                               name="specific_users[]" 
                                               value="{{ $user->id }}" 
                                               id="user{{ $user->id }}"
                                               {{ in_array($user->id, $selectedUsers) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="user{{ $user->id }}">
                                            <strong>{{ $user->name }}</strong>
                                            <span class="badge bg-info ms-2">
                                                {{ $user->activeMobileSubscription->plan->name_fr ?? __('No plan') }}
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('specific_users')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
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
                            @if($notification->image_url)
                                <div class="mb-2">
                                    <img src="{{ $notification->image_url }}" alt="Current image" class="img-thumbnail" style="max-width: 200px;">
                                    <p class="text-muted small mt-1">{{ __('Current image') }}</p>
                                </div>
                            @endif
                            <input type="file" 
                                   class="form-control @error('image') is-invalid @enderror" 
                                   name="image" 
                                   accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
                                   id="imageUpload">
                            <small class="text-muted">{{ __('Optional: Upload new image (PNG, JPG, GIF, WEBP - Max 20MB)') }}</small>
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
                                   value="{{ old('action_url', $notification->action_url) }}" 
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
                                   value="{{ old('scheduled_at', $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d\TH:i') : '') }}"
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

        // Upload image function for Summernote
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
        
        if (this.value === 'plan_specific') {
            planSelection.style.display = 'block';
            targetPlan.required = true;
            specificUsersSelection.style.display = 'none';
        } else if (this.value === 'specific_users') {
            specificUsersSelection.style.display = 'block';
            planSelection.style.display = 'none';
            targetPlan.required = false;
        } else {
            planSelection.style.display = 'none';
            targetPlan.required = false;
            specificUsersSelection.style.display = 'none';
        }
        
        updateRecipientCount();
    });

    document.getElementById('targetPlan').addEventListener('change', function() {
        updateRecipientCount();
    });

    // Select/Deselect all users
    function selectAllUsers() {
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateRecipientCount();
    }

    function deselectAllUsers() {
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateRecipientCount();
    }

    // Update recipient count
    function updateRecipientCount() {
        const audience = document.getElementById('targetAudience').value;
        const planId = document.getElementById('targetPlan').value;
        
        let selectedUsers = [];
        if (audience === 'specific_users') {
            document.querySelectorAll('.user-checkbox:checked').forEach(checkbox => {
                selectedUsers.push(checkbox.value);
            });
        }

        const params = new URLSearchParams({
            audience: audience,
            plan_id: planId || '',
            user_ids: selectedUsers.join(',')
        });

        fetch(`{{ route('push-notifications.preview-recipients') }}?${params}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('recipientCount').textContent = data.total.toLocaleString();
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // Live preview for title
    document.querySelector('input[name="title"]').addEventListener('input', function() {
        document.getElementById('preview-title').textContent = this.value || '{{ __("Notification Title") }}';
    });

    // Image preview
    document.getElementById('imageUpload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                preview.querySelector('img').src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Initial recipient count
    updateRecipientCount();

    // Listen to checkbox changes for recipient count
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateRecipientCount);
    });
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
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }

    .note-editor.note-frame.is-invalid {
        border-color: #dc3545;
    }
</style>
@endpush
