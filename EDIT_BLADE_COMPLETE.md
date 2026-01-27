# EDIT.BLADE.PHP - MODIFICATIONS COMPLÈTES ✅

**Date**: 2025-01-14
**Fichier**: `resources/views/push-notifications/edit.blade.php`
**État**: TOUTES LES MODIFICATIONS APPLIQUÉES

---

## ✅ MODIFICATIONS APPLIQUÉES AU FICHIER EDIT.BLADE.PHP

### 1. **Enctype pour Upload de Fichiers** ✅
**Ligne 18**
```html
<form action="{{ route('push-notifications.update', $notification->id) }}" 
      method="POST" 
      id="notificationForm" 
      enctype="multipart/form-data">
```

### 2. **Textarea → Summernote WYSIWYG Editor** ✅
**Lignes 43-52**
```html
<textarea class="form-control @error('body') is-invalid @enderror" 
          name="body" 
          id="summernote" 
          required>{{ old('body', $notification->body) }}</textarea>
```

### 3. **Title - Valeur Existante** ✅
**Ligne 32**
```html
<input type="text" 
       value="{{ old('title', $notification->title) }}">
```

### 4. **Type - Valeur Existante** ✅
**Lignes 65-71**
```html
<option value="general" {{ old('type', $notification->type) == 'general' ? 'selected' : '' }}>
<option value="reminder" {{ old('type', $notification->type) == 'reminder' ? 'selected' : '' }}>
<option value="alert" {{ old('type', $notification->type) == 'alert' ? 'selected' : '' }}>
<option value="promotion" {{ old('type', $notification->type) == 'promotion' ? 'selected' : '' }}>
```

### 5. **Target Audience - Valeur Existante** ✅
**Lignes 80-87**
```html
<option value="all" {{ old('target_audience', $notification->target_audience) == 'all' ? 'selected' : '' }}>
<option value="subscribed" {{ old('target_audience', $notification->target_audience) == 'subscribed' ? 'selected' : '' }}>
<option value="plan_specific" {{ old('target_audience', $notification->target_audience) == 'plan_specific' ? 'selected' : '' }}>
<option value="specific_users" {{ old('target_audience', $notification->target_audience) == 'specific_users' ? 'selected' : '' }}>
```

### 6. **Plan Selection - Affichage Conditionnel** ✅
**Lignes 96-106**
```html
<div class="mb-4" id="planSelection" 
     style="display: {{ old('target_audience', $notification->target_audience) == 'plan_specific' ? 'block' : 'none' }};">
    <label class="form-label required">{{ __('Select Plan') }}</label>
    <select class="form-control" id="targetPlan" name="target_plan">
        @foreach($plans as $plan)
            <option value="{{ $plan->id }}" 
                    {{ old('target_plan', $notification->target_plan) == $plan->id ? 'selected' : '' }}>
                {{ $plan->name_fr }} ({{ $plan->subscriptions_count }} subscribers)
            </option>
        @endforeach
    </select>
</div>
```

### 7. **Specific Users Selection - SECTION COMPLÈTE** ✅
**Lignes 107-145**
```html
<div class="mb-4" id="specificUsersSelection" 
     style="display: {{ old('target_audience', $notification->target_audience) == 'specific_users' ? 'block' : 'none' }};">
    <label class="form-label">{{ __('Select Users') }}</label>
    <div class="mb-2">
        <button type="button" onclick="selectAllUsers()">Select All</button>
        <button type="button" onclick="deselectAllUsers()">Deselect All</button>
    </div>
    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
        @php
            $selectedUsers = old('specific_users', json_decode($notification->specific_users, true) ?? []);
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
</div>
```

### 8. **Image URL → File Upload avec Preview** ✅
**Lignes 151-181**
```html
<div class="col-md-6">
    <div class="mb-4">
        <label for="imageUpload" class="form-label">
            {{ __('Notification Image') }}
            <span class="text-muted">(Max 20MB - PNG, JPG, JPEG, GIF, WebP)</span>
        </label>
        
        <!-- Current Image Preview -->
        @if($notification->image_url)
        <div class="mb-2">
            <img src="{{ $notification->image_url }}" 
                 alt="Current Image" 
                 style="max-width: 200px; max-height: 150px; border-radius: 8px;">
            <p class="text-muted small">{{ __('Current image (upload a new one to replace)') }}</p>
        </div>
        @endif
        
        <!-- New Image Upload -->
        <input type="file" 
               class="form-control @error('file') is-invalid @enderror" 
               id="imageUpload" 
               name="file" 
               accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
        
        <!-- New Image Preview -->
        <div id="imagePreview" style="display: none; margin-top: 10px;">
            <img src="" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 8px;">
        </div>
        
        @error('file')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
```

### 9. **Action URL - Valeur Existante** ✅
**Lignes 191-200**
```html
<input type="url" 
       class="form-control" 
       name="action_url" 
       value="{{ old('action_url', $notification->action_url) }}" 
       placeholder="https://example.com/page">
```

### 10. **Scheduled At - Valeur Existante** ✅
**Lignes 208-217**
```html
<input type="datetime-local" 
       class="form-control" 
       name="scheduled_at" 
       value="{{ old('scheduled_at', $notification->scheduled_at ? \Carbon\Carbon::parse($notification->scheduled_at)->format('Y-m-d\TH:i') : '') }}">
```

---

## ✅ JAVASCRIPT COMPLET AJOUTÉ

### 11. **Summernote Initialization** ✅
**Lignes 264-302**
```javascript
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
                // Update live preview
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = contents;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';
                document.getElementById('preview-body').textContent = textContent.substring(0, 100) + 
                    (textContent.length > 100 ? '...' : '');
            }
        }
    });
});
```

### 12. **uploadImage() AJAX Function** ✅
**Lignes 303-325**
```javascript
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
```

### 13. **Toggle Plan & Specific Users Selection** ✅
**Lignes 328-351**
```javascript
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
```

### 14. **Select/Deselect All Users Functions** ✅
**Lignes 352-363**
```javascript
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
```

### 15. **Update Recipient Count** ✅
**Lignes 365-389**
```javascript
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
```

### 16. **Live Preview & Image Preview** ✅
**Lignes 391-414**
```javascript
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
```

---

## ✅ CSS AJOUTÉ

### 17. **Summernote CSS + Custom Styles** ✅
**Lignes 422-447**
```html
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
```

---

## 📊 COMPARAISON CREATE.BLADE.PHP vs EDIT.BLADE.PHP

| Fonctionnalité | create.blade.php | edit.blade.php | État |
|----------------|-----------------|----------------|------|
| enctype="multipart/form-data" | ✅ | ✅ | IDENTIQUE |
| Summernote textarea | ✅ | ✅ | IDENTIQUE |
| File upload | ✅ | ✅ | IDENTIQUE |
| Specific users selection | ✅ | ✅ | IDENTIQUE |
| Summernote JS initialization | ✅ | ✅ | IDENTIQUE |
| uploadImage() AJAX function | ✅ | ✅ | IDENTIQUE |
| selectAllUsers() function | ✅ | ✅ | IDENTIQUE |
| deselectAllUsers() function | ✅ | ✅ | IDENTIQUE |
| Toggle plan/users | ✅ | ✅ | IDENTIQUE |
| Image preview | ✅ | ✅ | IDENTIQUE |
| Summernote CSS | ✅ | ✅ | IDENTIQUE |
| old() values | N/A | ✅ | EDIT UNIQUEMENT |
| Current image preview | N/A | ✅ | EDIT UNIQUEMENT |
| Pre-checked users | N/A | ✅ | EDIT UNIQUEMENT |

---

## ✅ CONCLUSION

**TOUTES LES MODIFICATIONS ONT ÉTÉ APPLIQUÉES AU FICHIER EDIT.BLADE.PHP**

Le fichier `edit.blade.php` possède maintenant :
1. ✅ Formulaire avec enctype pour upload de fichiers
2. ✅ Éditeur WYSIWYG Summernote avec toolbar complète
3. ✅ Upload d'images inline dans le texte (drag & drop + paste)
4. ✅ Upload de fichier image (remplacement de Image URL)
5. ✅ Sélection de users spécifiques avec checkboxes
6. ✅ Boutons Select All / Deselect All
7. ✅ Limite d'upload 20MB (au lieu de 2MB)
8. ✅ Support GIF en plus de PNG/JPG/JPEG/WebP
9. ✅ Valeurs old() pour tous les champs (persistence en cas d'erreur validation)
10. ✅ Preview de l'image actuelle si elle existe
11. ✅ JavaScript complet pour toutes les fonctionnalités
12. ✅ CSS Summernote + styles custom

**Les fichiers create.blade.php et edit.blade.php sont maintenant IDENTIQUES en fonctionnalités !**

---

## 📋 PROCHAINES ÉTAPES

1. **Tester le formulaire edit en local** :
   - Accéder à Admin → Push Notifications → Edit (notification existante)
   - Vérifier que Summernote charge correctement
   - Vérifier que les champs sont pré-remplis avec les valeurs de la notification
   - Tester le drag & drop d'image dans Summernote
   - Tester le changement de fichier image
   - Tester la sélection de users spécifiques

2. **Uploader sur le serveur** :
   - app/Http/Controllers/PushNotificationsController.php
   - resources/views/push-notifications/create.blade.php
   - resources/views/push-notifications/edit.blade.php

3. **Exécuter la migration** :
   ```sql
   ALTER TABLE `push_notifications` ADD COLUMN `specific_users` JSON NULL AFTER `target_plan`;
   ```

4. **Clear cache sur le serveur** :
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```

---

**Fichier de documentation généré le 2025-01-14**
