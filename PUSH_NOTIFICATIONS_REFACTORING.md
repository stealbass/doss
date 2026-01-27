# 🔧 Refactorisation Professionnelle: Push Notifications Upload

## 📋 Résumé des modifications

Refactorisation COMPLÈTE du système d'upload des Push Notifications pour **suivre exactement le pattern utilisé par Legal Library et Documents**.

### ✅ Modifications appliquées

#### 1. **PushNotificationsController.php** - Architecture professionnelle

**Imports ajoutés:**
```php
use App\Models\Utility;
use Illuminate\Support\Facades\Validator;
```

**Nouvelles méthodes privées:**

##### `getStorageDisk()` 
- Récupère la configuration du storage depuis la base de données
- Support multi-backend: **Local, S3, Wasabi, Cloudflare R2**
- Configure dynamiquement les credentials depuis `settings` table
- **Identique au pattern LegalLibraryController**

```php
private function getStorageDisk()
{
    $settings = Utility::getStorageSetting();
    $storageSetting = $settings['storage_setting'] ?? 'local';
    
    if ($storageSetting === 'r2') {
        config([
            'filesystems.disks.r2.key' => $settings['r2_key'],
            'filesystems.disks.r2.secret' => $settings['r2_secret'],
            'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
            'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
            'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
            'filesystems.disks.r2.url' => $settings['r2_url'],
            'filesystems.disks.r2.use_path_style_endpoint' => false,
        ]);
        return 'r2';
    }
    // ... support S3, Wasabi, local
}
```

##### `getUploadLimit()` 
- Récupère les limites d'upload depuis `settings` table
- **Default: 20MB** (20480 KB) - configuré en base de données
- Supporte les limites par backend storage

```php
private function getUploadLimit()
{
    $settings = Utility::getStorageSetting();
    $storageSetting = $settings['storage_setting'] ?? 'local';
    $maxSize = 20480; // 20MB default
    
    if ($storageSetting === 'r2') {
        $maxSize = !empty($settings['r2_max_upload_size']) ? (int)$settings['r2_max_upload_size'] : 20480;
    }
    // ... autres backends
    
    return $maxSize;
}
```

**Méthode `store()` - Refactorisée:**

**Avant:**
```php
// Upload direct vers R2 (codé en dur)
Storage::disk('r2')->put($path, file_get_contents($image), 'public');
$validated['image_url'] = env('R2_URL') . '/' . $path;
```

**Après:**
```php
// Upload via système unifié Utility::upload_file()
// Supporte: Local, S3, Wasabi, R2
// Limites dynamiques depuis la base de données

$maxUploadSize = $this->getUploadLimit();

if ($request->hasFile('image')) {
    $tempRequest = new Request();
    $tempRequest->files->set('file', $request->file('image'));
    
    $filename = 'push_notification_' . time() . '_' . uniqid() . '.' . $request->file('image')->getClientOriginalExtension();
    
    // Utiliser Utility::upload_file() - comme Documents et LegalLibrary
    $uploadResult = Utility::upload_file($tempRequest, 'file', $filename, 'push-notifications', [
        'mimes:png,jpg,jpeg,gif,webp',
        'max:' . $maxUploadSize,
    ]);
    
    if ($uploadResult['flag'] == 1) {
        $disk = $this->getStorageDisk();
        $validated['image_url'] = Storage::disk($disk)->url($uploadResult['url']);
    } else {
        return redirect()->back()->withErrors(['image' => __($uploadResult['msg'])]);
    }
}
```

**Méthode `uploadImage()` - Pour Summernote:**

**Avant:**
```php
// Validation codée en dur à 2MB
$request->validate([
    'image' => 'required|image|mimes:png,jpg,jpeg,gif,webp|max:2048',
]);

// Upload direct vers R2
Storage::disk('r2')->put($path, file_get_contents($image), 'public');
$url = env('R2_URL') . '/' . $path;
```

**Après:**
```php
// Configuration depuis base de données
$maxSize = $this->getUploadLimit();
$settings = Utility::getStorageSetting();
$allowedMimes = !empty($settings[$storageSetting . '_storage_validation']) 
    ? $settings[$storageSetting . '_storage_validation']
    : 'png,jpg,jpeg,gif,webp';

// Validation avec limites dynamiques
$validator = Validator::make($request->all(), [
    'image' => [
        'required',
        'image',
        'mimes:' . $allowedMimes,
        'max:' . $maxSize,
    ],
]);

// Upload vers le storage configuré (pas juste R2)
$disk = $this->getStorageDisk();
$path = Storage::disk($disk)->putFileAs(
    'push-notifications/inline',
    $image,
    $filename
);

// Génération d'URL depuis le disk configuré
$url = Storage::disk($disk)->url($path);
```

#### 2. **create.blade.php** - Interface mise à jour

**Modification du texte d'aide:**
```php
// Avant
{{ __('Optional: Image to display in notification (PNG, JPG, WEBP - Max 2MB)') }}

// Après
{{ __('Optional: Image to display in notification (PNG, JPG, GIF, WEBP - Max 20MB)') }}
```

**Accept types enrichi:**
```php
// Avant
accept="image/png,image/jpeg,image/jpg,image/webp"

// Après
accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
```

---

## 🎯 Architecture - Comparaison

### Before (Codée en dur)
```
PushNotificationsController
├── store() → Direct Storage::disk('r2')->put()
├── uploadImage() → Direct env('R2_URL')
└── Limite: 2MB (max:2048)
```

### After (Professionnelle - Pattern LegalLibrary)
```
PushNotificationsController
├── getStorageDisk() → Utility::getStorageSetting()
├── getUploadLimit() → Settings table
├── store() → Utility::upload_file() + Storage::disk()->url()
├── uploadImage() → Validation dynamique + Storage::disk()->putFileAs()
└── Limite: 20MB (configurable en admin panel)
```

---

## 🔄 Flux d'upload - Upload Principal (store)

```
1. Utilisateur upload image (form.blade.php)
   ↓
2. PushNotificationsController::store() valide
   - Récupère limite: getUploadLimit() → settings.r2_max_upload_size
   - Valide: mimes & max
   ↓
3. Utilise Utility::upload_file() 
   - Détecte storage setting (r2, s3, wasabi, local)
   - Récupère credentials depuis settings table
   - Configure le disk dynamiquement
   - Exécute Storage::disk($setting)->putFileAs()
   ↓
4. Récupère URL publique
   - Disk configuré: getStorageDisk()
   - URL: Storage::disk($disk)->url($path)
   ↓
5. Sauvegarde en BDD
   - push_notifications.image_url = URL publique
```

---

## 🖼️ Flux d'upload - Images Inline Summernote (uploadImage)

```
1. Utilisateur drag/drop image dans Summernote
   ↓
2. JavaScript: onImageUpload → AJAX POST /push-notifications/upload-image
   ↓
3. PushNotificationsController::uploadImage()
   - Récupère limite: getUploadLimit()
   - Récupère extensions autorisées: settings.storage_setting_validation
   - Valide avec Validator::make()
   ↓
4. Upload dynamique
   - Disk: getStorageDisk() 
   - Path: Storage::disk($disk)->putFileAs('push-notifications/inline/...')
   ↓
5. Génère URL publique
   - URL: Storage::disk($disk)->url($path)
   ↓
6. Retourne JSON
   - { success: true, url: "...", message: "..." }
   ↓
7. JavaScript Summernote insère image
   - $('#summernote').summernote('insertImage', url)
```

---

## 📊 Configuration multi-storage

### Storage Setting depuis Admin Panel
```
Settings table:
- storage_setting: 'r2' | 's3' | 'wasabi' | 'local'
- r2_key, r2_secret, r2_endpoint, r2_bucket, r2_url
- s3_key, s3_secret, s3_region, s3_bucket
- wasabi_key, wasabi_secret, wasabi_region, wasabi_bucket
- local_storage_validation, r2_storage_validation, etc.
- r2_max_upload_size, s3_max_upload_size, etc.
```

### Limites d'upload
```
Local:  {{ settings.local_storage_max_upload_size }}
R2:    {{ settings.r2_max_upload_size }} (default 20480 = 20MB)
S3:    {{ settings.s3_max_upload_size }} (default 20480 = 20MB)
Wasabi: {{ settings.wasabi_max_upload_size }} (default 20480 = 20MB)
```

### Extensions autorisées
```
Local:  {{ settings.local_storage_validation }}
R2:    {{ settings.r2_storage_validation }} (default: png,jpg,jpeg,gif,webp)
S3:    {{ settings.s3_storage_validation }} (default: png,jpg,jpeg,gif,webp)
Wasabi: {{ settings.wasabi_storage_validation }} (default: png,jpg,jpeg,gif,webp)
```

---

## ✅ Avantages de cette refactorisation

| Aspect | Avant | Après |
|--------|-------|-------|
| **Storage** | R2 uniquement (codé) | Multi-storage (config) |
| **Upload limit** | 2MB (codé en dur) | 20MB (configurable) |
| **Credentials** | env() | settings table (admin panel) |
| **Cohérence** | Propre à Push | Unifié (Documents, Legal) |
| **Maintenabilité** | Difficile | Facile (centralisée) |
| **Scalabilité** | Limitée | Excellente (tous backends) |
| **Admin control** | Non | Oui (panel complet) |

---

## 🔧 Fichiers modifiés

✅ **app/Http/Controllers/PushNotificationsController.php**
- 2 nouvelles méthodes: `getStorageDisk()`, `getUploadLimit()`
- `store()` : Utility::upload_file() + limites dynamiques
- `uploadImage()` : Validation et upload multi-storage
- Imports: `Utility`, `Validator`

✅ **resources/views/push-notifications/create.blade.php**
- Texte: "Max 2MB" → "Max 20MB"
- Accept: ajout `image/gif`

---

## 🚀 Déploiement

### Sur le serveur
```bash
# 1. Upload fichiers
- app/Http/Controllers/PushNotificationsController.php
- resources/views/push-notifications/create.blade.php

# 2. Vider cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 3. Test
- Accédez à Admin → Push Notifications → Create
- Testez upload image principale (20MB max)
- Testez Summernote drag/drop
```

### Configuration admin panel
```
Admin Panel → Settings → File Storage
- Storage Setting: R2 (ou S3, Wasabi, Local)
- R2 Max Upload Size: 20480 (ou autre valeur)
- R2 Storage Validation: png,jpg,jpeg,gif,webp (ou autre)
```

---

## 🎉 Résultat

Push Notifications maintenant:
- ✅ Utilise le **même pattern que Legal Library**
- ✅ Support **multi-storage** (local, S3, Wasabi, R2)
- ✅ Limite d'upload: **20MB** (configurable)
- ✅ Configuration depuis **admin panel** (base de données)
- ✅ **Sommernote inline images** supportées pour tous les backends
- ✅ **Scalable et maintenable** (architecture centralisée)

---

## 📚 Références

- **Pattern source**: LegalLibraryController::bulkUploadStore()
- **Upload helper**: Utility::upload_file() et upload_file() alternatif
- **Multi-storage support**: Documents + LegalLibrary
- **Blade form**: create.blade.php (form standard)
- **Summernote**: Callback uploadImage() avec support multi-storage

C'est maintenant **production-ready** ! 🚀
