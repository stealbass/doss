# 🔧 Fix: Erreur Upload Image Summernote

## ❌ Problème identifié
**Erreur:** "Erreur lors de l'upload de l'image" dans l'éditeur WYSIWYG Summernote

## ✅ Solution appliquée

### 1. Méthode `uploadImage()` ajoutée au contrôleur
**Fichier:** `app/Http/Controllers/PushNotificationsController.php`

La méthode suivante a été ajoutée :

```php
/**
 * Upload d'image pour Summernote (images inline dans le contenu)
 */
public function uploadImage(Request $request)
{
    try {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Uploader vers Cloudflare R2
            $path = 'push-notifications/inline/' . $filename;
            Storage::disk('r2')->put($path, file_get_contents($image), 'public');
            
            // Générer l'URL publique R2
            $url = env('R2_URL') . '/' . $path;

            return response()->json([
                'success' => true,
                'url' => $url,
                'message' => 'Image uploadée avec succès'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucune image reçue'
        ], 400);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Fichier invalide. Formats acceptés: PNG, JPG, JPEG, GIF, WEBP (max 2MB)',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
        ], 500);
    }
}
```

### 2. Configuration R2 vérifiée
Variables présentes dans `.env` :
```env
R2_ACCESS_KEY_ID=4319071644a7f5f3f1f8876c00b2bf5c
R2_SECRET_ACCESS_KEY=4ffb67a8285c06f8Ddfff...
R2_BUCKET=dossy-pro-documents
R2_ENDPOINT=https://7f65e0eba4a7304ea7//7baf0bafc7/aecf3f2.r2.cloudflarestorage.com
R2_URL=https://files.dossypro.com
R2_REGION=auto
```

✅ **Configuration complète et valide**

### 3. Route existante validée
**Fichier:** `routes/web.php` (ligne 399)

```php
Route::post('push-notifications/upload-image', 
    [\App\Http\Controllers\PushNotificationsController::class, 'uploadImage'])
    ->name('push-notifications.upload-image');
```

---

## 🚀 Actions immédiates requises

### Étape 1: Vider le cache Laravel

**Option A - Via script (recommandé):**
```bash
# Double-cliquez sur le fichier
clear-cache-summernote.bat
```

**Option B - Via commandes manuelles:**
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Étape 2: Uploader les fichiers modifiés

Uploadez sur votre serveur:
- ✅ `app/Http/Controllers/PushNotificationsController.php` (méthode uploadImage ajoutée)

### Étape 3: Tester l'upload

1. Allez sur: **Admin → Push Notifications → Create**
2. Dans l'éditeur Summernote, cliquez sur l'icône **📷 Picture**
3. Sélectionnez une image depuis votre ordinateur
4. L'image devrait s'uploader automatiquement vers Cloudflare R2
5. L'image apparaît dans l'éditeur

---

## 🔍 Diagnostic en cas de problème persistant

### Vérification 1: Testez la connexion R2

Créez un fichier `test_r2_connection.php` :

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

try {
    $testContent = 'Test upload - ' . date('Y-m-d H:i:s');
    $path = 'test/test.txt';
    
    Storage::disk('r2')->put($path, $testContent, 'public');
    echo "✅ Upload R2 réussi!\n";
    echo "URL: " . env('R2_URL') . '/' . $path . "\n";
    
    Storage::disk('r2')->delete($path);
    echo "✅ Connexion R2 fonctionnelle!\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
```

Exécutez:
```bash
php test_r2_connection.php
```

### Vérification 2: Consultez les logs Laravel

```bash
# Windows
type storage\logs\laravel.log | Select-String "upload" -Context 5

# Linux/Mac
tail -f storage/logs/laravel.log
```

Cherchez les erreurs liées à l'upload d'images.

### Vérification 3: Testez la route via CURL

```bash
curl -X POST https://dossypro.com/push-notifications/upload-image \
  -H "X-CSRF-TOKEN: VOTRE_TOKEN" \
  -F "image=@/path/to/test-image.jpg"
```

Devrait retourner:
```json
{
  "success": true,
  "url": "https://files.dossypro.com/push-notifications/inline/1736089301_65f8a9ed12345.jpg",
  "message": "Image uploadée avec succès"
}
```

---

## 🐛 Erreurs possibles et solutions

### Erreur 1: "Class 'Storage' not found"

**Cause:** Facade Storage non importée

**Solution:**
Vérifiez que `PushNotificationsController.php` contient en haut du fichier:
```php
use Illuminate\Support\Facades\Storage;
```

### Erreur 2: "Disk [r2] does not exist"

**Cause:** Configuration R2 non chargée

**Solution:**
```bash
php artisan config:clear
php artisan config:cache
```

### Erreur 3: "Error executing 'PutObject'"

**Cause:** Identifiants R2 incorrects ou bucket inexistant

**Solutions:**
1. Vérifiez les credentials dans Cloudflare Dashboard
2. Assurez-vous que le bucket `dossy-pro-documents` existe
3. Vérifiez que Public Access est activé sur le bucket

### Erreur 4: "419 Page Expired"

**Cause:** Token CSRF manquant ou expiré

**Solution:**
Le JavaScript dans `create.blade.php` envoie déjà le token:
```javascript
formData.append('_token', '{{ csrf_token() }}');
```

Si l'erreur persiste, rafraîchissez la page (F5).

### Erreur 5: "The image must be a file of type: png, jpg, jpeg..."

**Cause:** Format de fichier non supporté

**Solution:**
Formats acceptés: PNG, JPG, JPEG, GIF, WEBP (max 2 MB)

### Erreur 6: Image uploadée mais URL 404

**Cause:** Public Access non activé sur R2 ou domaine mal configuré

**Solutions:**
1. Dans Cloudflare Dashboard → R2 → Bucket Settings
2. Activez "Allow Public Access"
3. Vérifiez que `R2_URL` dans `.env` correspond au domaine configuré

---

## ✅ Checklist finale

Avant de tester dans l'admin:

- [x] ✅ Méthode `uploadImage()` ajoutée au contrôleur
- [x] ✅ Facade `Storage` importée dans le contrôleur
- [x] ✅ Variables R2 configurées dans `.env`
- [x] ✅ Route `push-notifications.upload-image` existante
- [ ] ⏳ Cache Laravel vidé (exécutez `clear-cache-summernote.bat`)
- [ ] ⏳ Fichier contrôleur uploadé sur le serveur
- [ ] ⏳ Test d'upload dans l'admin

---

## 🎯 Résultat attendu

Après application de la solution:

1. **Dans l'éditeur Summernote:**
   - Cliquez sur l'icône 📷 Picture
   - Sélectionnez une image
   - L'image s'uploade automatiquement
   - L'image apparaît dans l'éditeur

2. **En base de données:**
   - Le champ `body` contient du HTML avec `<img src="https://files.dossypro.com/...">`

3. **Sur Cloudflare R2:**
   - Images stockées dans `push-notifications/inline/`
   - Accessibles publiquement via `https://files.dossypro.com/`

---

## 📞 Support

Si le problème persiste après avoir suivi toutes ces étapes:

1. Vérifiez les logs Laravel: `storage/logs/laravel.log`
2. Testez la connexion R2 avec le script de test
3. Vérifiez la console JavaScript du navigateur (F12)
4. Partagez le message d'erreur complet

---

## 🎉 Récapitulatif

**Problème:** Méthode `uploadImage()` manquante dans le contrôleur

**Solution:** Méthode ajoutée avec:
- ✅ Validation des images (formats, taille)
- ✅ Upload vers Cloudflare R2
- ✅ Génération d'URL publique
- ✅ Gestion des erreurs complète
- ✅ Réponses JSON pour Summernote

**Prochaine étape:** Videz le cache et testez l'upload ! 🚀
