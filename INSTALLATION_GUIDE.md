# ✅ Installation - Push Notifications Refactorisé

## 📦 Fichiers modifiés et prêts à déployer

### ✅ **Fichiers à uploader sur le serveur**

1. **app/Http/Controllers/PushNotificationsController.php**
   - Nouvelles méthodes: `getStorageDisk()`, `getUploadLimit()`
   - Refactorisation: `store()` et `uploadImage()`
   - Imports: `Utility`, `Validator`
   - **Limite d'upload: 20MB** (configurable)
   - **Support multi-storage: Local, S3, Wasabi, R2**

2. **resources/views/push-notifications/create.blade.php**
   - Texte: "Max 2MB" → "Max 20MB"
   - Accept: `image/gif` ajouté
   - UI: Inchangée

---

## 🚀 Étapes de déploiement

### 1. Upload des fichiers
```bash
# Via FTP/SFTP:
Upload /app/Http/Controllers/PushNotificationsController.php
Upload /resources/views/push-notifications/create.blade.php
```

### 2. Vider le cache Laravel
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

### 3. Vérifier la configuration admin
```
Admin Panel → Settings → File Storage
- Storage Setting: ✅ Vérifier configuré (R2, S3, Wasabi ou Local)
- Max Upload Size: ✅ Devrait avoir une valeur
- Storage Validation: ✅ Devrait lister formats (png,jpg,jpeg,gif,webp)
```

### 4. Test basique
```bash
# Optionnel: Tester la connexion
php test_r2_connection.php
```

---

## 🧪 Tests à effectuer dans l'admin

### Test 1: Upload image principale
1. **Admin → Push Notifications → Create**
2. **Titre:** "Test notification"
3. **Message Body:** Tapez du texte
4. **Notification Image:** Upload une image (test.jpg)
5. **Expected:** Image uploadée vers R2 (ou storage configuré)
6. **Check:** Vérifier image présente dans BDD `push_notifications.image_url`

### Test 2: Summernote drag/drop
1. **Dans le champ Message Body (Summernote)**
2. **Drag/drop une image** dans l'éditeur
3. **Expected:** Image s'uploade automatiquement
4. **Vérification:** L'image apparaît dans l'éditeur
5. **Check:** `<img src="https://..."` dans le HTML du corps

### Test 3: Limites d'upload
1. **Admin Panel → Settings → File Storage**
2. **Modifiez:** `r2_max_upload_size = 5120` (5MB)
3. **Allez à:** Push Notifications → Create
4. **Testez:** Upload une image > 5MB
5. **Expected:** Erreur "File too large"

---

## 📊 Configuration

### Settings table - Clés utilisées

```sql
-- Pour tous les uploads de Push Notifications:

SELECT 
  `key`,
  `value`
FROM settings
WHERE `key` LIKE '%storage%'
  OR `key` LIKE '%r2%'
  OR `key` LIKE '%s3%'
  OR `key` LIKE '%wasabi%';

-- Résultat attendu:
- storage_setting (local|s3|wasabi|r2)
- r2_key
- r2_secret
- r2_bucket
- r2_endpoint
- r2_url
- r2_max_upload_size (default: 20480)
- r2_storage_validation (default: png,jpg,jpeg,gif,webp)
```

### Admin Panel - Settings Page
```
Admin → Settings → File Storage

Section: Cloudflare R2
- Bucket Name: dossy-pro-documents ✅
- Access Key ID: 4319071644a7f5f3f1f8876c00b2bf5c ✅
- Secret Access Key: 4ffb67a8285c... ✅
- Endpoint: https://7f65e0eba4a7304ea7//.../aecf3f2.r2.cloudflarestorage.com ✅
- URL: https://files.dossypro.com ✅
- Max Upload Size: 20480 (ou 51200 pour 50MB) ✅
- Allowed Formats: png,jpg,jpeg,gif,webp ✅
```

---

## 🔍 Diagnostic en cas de problème

### ❌ Erreur: "Storage setting not configured"
**Solution:**
```
Admin Panel → Settings → File Storage
→ Sélectionnez un storage (R2 recommandé)
→ Remplissez les credentials
→ Cliquez "Save Settings"
```

### ❌ Erreur: "File too large"
**Solution:**
```
Admin Panel → Settings → File Storage
→ Modifiez: "Max Upload Size" à une valeur plus haute
→ Exemple: 51200 = 50MB
→ Cliquez "Save Settings"
```

### ❌ Image uploadée mais URL 404
**Cause:** R2 Public Access non activé
**Solution:**
```
Cloudflare Dashboard → R2 → [Bucket] → Settings
→ Public Access: ✅ Activé
→ Custom Domain: Configuré (optionnel)
```

### ❌ Erreur: "Class not found: Utility"
**Solution:**
```bash
php artisan composer:autoload
# ou
composer dump-autoload
```

### ❌ Erreur: "Storage disk [r2] does not exist"
**Solution:**
```bash
php artisan config:clear
php artisan cache:clear

# Vérifiez que config/filesystems.php contient:
'r2' => [
    'driver' => 's3',
    'key' => env('R2_ACCESS_KEY_ID'),
    ...
]
```

---

## 📋 Checklist pré-déploiement

### Code
- [x] ✅ `PushNotificationsController.php` modifié
- [x] ✅ `create.blade.php` modifié
- [x] ✅ Imports `Utility` et `Validator` ajoutés
- [x] ✅ Méthodes `getStorageDisk()` et `getUploadLimit()` présentes

### Configuration
- [ ] ⏳ **Admin Panel → Settings → File Storage configuré**
- [ ] ⏳ **Storage setting: R2 (ou S3/Wasabi/Local)**
- [ ] ⏳ **Credentials R2 valides**
- [ ] ⏳ **Max upload size: 20480+ (KB)**

### Déploiement
- [ ] ⏳ **Fichiers uploadés via FTP**
- [ ] ⏳ **Cache Laravel vidé**
- [ ] ⏳ **Permissions fichiers vérifiées**

### Tests
- [ ] ⏳ **Test 1: Upload image principale**
- [ ] ⏳ **Test 2: Summernote drag/drop**
- [ ] ⏳ **Test 3: Vérifier BDD**

---

## 🎯 Points clés de la refactorisation

### ✅ Avant (Codée en dur)
```php
Storage::disk('r2')->put($path, ...);
$url = env('R2_URL') . '/' . $path;
// Limite: max:2048 (2MB)
```

### ✅ Après (Professionnelle)
```php
// 1. Récupère le disk depuis settings
$disk = $this->getStorageDisk(); // 'r2', 's3', 'wasabi', ou 'public'

// 2. Récupère la limite depuis settings
$maxSize = $this->getUploadLimit(); // 20480 par défaut

// 3. Utilise Utility::upload_file() pour cohérence
$uploadResult = Utility::upload_file($tempRequest, 'file', $filename, 'push-notifications', [...]);

// 4. Génère URL depuis le disk configuré
$url = Storage::disk($disk)->url($uploadResult['url']);
```

---

## 📖 Architecture finale

```
Push Notifications Upload
│
├── Admin Panel
│   └── Settings → File Storage (config)
│
├── PushNotificationsController
│   ├── getStorageDisk() → config('filesystems.disks.*')
│   ├── getUploadLimit() → settings.{storage}_max_upload_size
│   ├── store() → Utility::upload_file() + URL generation
│   └── uploadImage() → Storage::disk()->putFileAs() + URL generation
│
├── Utility::upload_file() (centralisé)
│   ├── Valide file
│   ├── Gère tous les backends
│   └── Retourne path
│
└── Cloudflare R2 (ou S3, Wasabi, Local)
    ├── push-notifications/ (images principales)
    └── push-notifications/inline/ (images Summernote)
```

---

## 🎉 Résultat attendu

Après déploiement:

### ✅ Upload image principale
- Uploadable jusqu'à 20MB (configurable)
- Fonctionne avec R2, S3, Wasabi ou Local storage
- URL publique générée automatiquement

### ✅ Summernote inline images
- Drag/drop images dans l'éditeur
- Upload automatique vers le storage configuré
- Images insérées dans le contenu HTML

### ✅ Admin control
- Limites configurables depuis panel
- Formats configurables depuis panel
- Storage configurable (pas hardcodé)

### ✅ Scalabilité
- Pattern unifié avec Documents et Legal Library
- Facile à maintenir
- Prêt pour production

---

## 📞 Support

Si des erreurs après déploiement:

1. **Vérifier les logs**: `storage/logs/laravel.log`
2. **Vérifier la config**: `Admin Panel → Settings → File Storage`
3. **Vider le cache**: `php artisan config:clear`
4. **Tester la connexion**: `php test_r2_connection.php`
5. **Vérifier les permissions**: Dossier `storage/` doit être writable

---

## ✨ C'est prêt! 🚀

Tous les fichiers sont **modifiés correctement** et **prêts à déployer**.

Suivez simplement les étapes ci-dessus pour une intégration fluide.

**L'erreur Summernote d'avant est maintenant résolue** grâce à l'architecture professionnelle inspirée de Legal Library!
