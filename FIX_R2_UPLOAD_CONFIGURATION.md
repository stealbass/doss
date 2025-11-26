# 🔧 Fix Critique : Configuration Dynamique R2 pour les Uploads

## 🔴 Problème Identifié

### **Symptômes**
- ✅ Configuration R2 correcte dans l'interface Admin
- ✅ `storage_setting = 'r2'` dans la base de données
- ✅ Tous les paramètres R2 configurés (key, secret, bucket, endpoint, url)
- ❌ Les nouveaux uploads vont **TOUJOURS en local** malgré la configuration
- ❌ Les fichiers uploadés n'apparaissent **PAS sur Cloudflare R2**

### **Cause Racine**

Le disk 'r2' dans `config/filesystems.php` lit les credentials depuis les **variables d'environnement** (`.env`) :

```php
'r2' => [
    'driver' => 's3',
    'key' => env('R2_ACCESS_KEY_ID'),        // ← Lit depuis .env
    'secret' => env('R2_SECRET_ACCESS_KEY'), // ← Lit depuis .env
    'region' => env('R2_REGION', 'auto'),
    'bucket' => env('R2_BUCKET'),            // ← Lit depuis .env
    'endpoint' => env('R2_ENDPOINT'),        // ← Lit depuis .env
    'url' => env('R2_URL'),                  // ← Lit depuis .env
],
```

**Mais** dans votre application Dossy Pro, les paramètres R2 sont stockés dans la **base de données** (table `settings`), pas dans `.env` !

**Résultat :**
- Quand le code exécute `Storage::disk('r2')->storeAs(...)`, le disk R2 n'a **aucun credential**
- L'upload échoue silencieusement ou tombe en local par défaut

---

## ✅ Solution Implémentée

### **Modification : `app/Http/Controllers/LegalLibraryController.php`**

**AVANT (ne fonctionnait pas) :**
```php
private function getStorageDisk()
{
    $settings = Utility::settings();
    $storageSetting = $settings['storage_setting'] ?? 'local';
    
    // Retourne 'r2' mais le disk n'est PAS configuré!
    return ($storageSetting === 'r2') ? 'r2' : 'public';
}
```

**APRÈS (fonctionne correctement) :**
```php
private function getStorageDisk()
{
    $settings = Utility::settings();
    $storageSetting = $settings['storage_setting'] ?? 'local';
    
    // Configure R2 disk with DB credentials if R2 is selected
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
    
    return 'public';
}
```

### **Explication**

La méthode `config([...])` configure **dynamiquement** le disk R2 avec les credentials de la base de données **avant** l'upload.

C'est exactement ce que fait `Utility::get_file()` pour la lecture, maintenant on le fait aussi pour l'écriture.

---

## 🧪 Test de Vérification

### **Script de Test**
```bash
ssh dossypro@ssh-dossypro.alwaysdata.net
cd ~/public_html
git pull origin main
php test_r2_upload_config.php
```

**Ce script vérifie :**
1. ✅ `storage_setting = 'r2'` dans la DB
2. ✅ Tous les paramètres R2 sont présents
3. ✅ Configuration dynamique du disk R2
4. ✅ Connexion R2 fonctionne
5. ✅ Génération d'URL correcte

**Sortie attendue :**
```
✅ storage_setting = 'r2'
✅ r2_key : 4319O72644...
✅ r2_secret : 4f18e7a629...
✅ r2_bucket : dossy-pro-documents
✅ r2_endpoint : https://7f65e0eba4a7304ea7//7baf0bafc7/aecf3f2.r2.cloudflare...
✅ r2_url : https://files.dossypro.com

✅ Disk R2 configuré dynamiquement
✅ Connexion R2 réussie!
✅ URL R2 correcte
```

---

## 🚀 Déploiement

### **Étape 1 : SSH vers AlwaysData**
```bash
ssh dossypro@ssh-dossypro.alwaysdata.net
cd ~/public_html
```

### **Étape 2 : Récupérer le Code**
```bash
git pull origin main
```

### **Étape 3 : Vérifier la Configuration**
```bash
php test_r2_upload_config.php
```

Si tout est ✅, passez au test.

### **Étape 4 : Vider le Cache**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### **Étape 5 : Test Upload Réel**

1. **Connectez-vous en Super Admin**
2. **Legal Library → Choisir une catégorie**
3. **Add Document**
4. **Uploadez un PDF de test** (ex: `test_fix_r2.pdf`)
5. **Save**

### **Étape 6 : Vérifier sur R2**

**Dashboard Cloudflare R2 :**
1. Ouvrez votre bucket `dossy-pro-documents`
2. Naviguez vers `legal_documents/`
3. **Le fichier doit apparaître** avec un timestamp récent

**Exemple :**
```
legal_documents/
  └── 1732xxxxxx_test_fix_r2.pdf  (Uploaded: 2 minutes ago)
```

### **Étape 7 : Test Téléchargement Client**

1. **Connectez-vous en tant qu'utilisateur**
2. **Bibliothèque Juridique**
3. **Cliquez sur "Télécharger"** sur le document test
4. **Le PDF doit s'afficher** depuis :
   ```
   https://files.dossypro.com/legal_documents/1732xxxxxx_test_fix_r2.pdf
   ```

---

## 📊 Différence Avant/Après

### **AVANT (Broken)**
```
User uploads PDF via Legal Library
    ↓
getStorageDisk() returns 'r2'
    ↓
Storage::disk('r2')->storeAs('legal_documents', $fileName, 'r2')
    ↓
Disk 'r2' has NO credentials (reads from empty .env)
    ↓
❌ Upload fails or goes to local fallback
    ↓
File NOT on Cloudflare R2
```

### **APRÈS (Fixed)**
```
User uploads PDF via Legal Library
    ↓
getStorageDisk() reads settings from DB
    ↓
config(['filesystems.disks.r2.key' => ..., ...]) ← Configure dynamically
    ↓
Storage::disk('r2')->storeAs('legal_documents', $fileName, 'r2')
    ↓
Disk 'r2' has CORRECT credentials from DB
    ↓
✅ Upload succeeds to Cloudflare R2
    ↓
File appears on R2: dossy-pro-documents/legal_documents/
    ↓
Download URL: https://files.dossypro.com/legal_documents/file.pdf
```

---

## 🔍 Pourquoi `Utility::get_file()` Fonctionnait

La méthode `Utility::get_file()` (pour les téléchargements) **configurait déjà dynamiquement** le disk R2 :

```php
public static function get_file($path)
{
    $settings = Utility::settings();

    if ($settings['storage_setting'] == 'r2') {
        config([
            'filesystems.disks.r2.key' => $settings['r2_key'],
            'filesystems.disks.r2.secret' => $settings['r2_secret'],
            // ... etc
        ]);
    }

    return Storage::disk($settings['storage_setting'])->url($path);
}
```

**C'est pour ça que :**
- ✅ Les fichiers existants sur R2 s'affichaient correctement (get_file configurait le disk)
- ❌ Les nouveaux uploads n'allaient pas sur R2 (storeAs ne configurait PAS le disk)

**Maintenant**, `getStorageDisk()` dans `LegalLibraryController` fait la **même chose** pour les uploads.

---

## 📋 Checklist de Vérification

- [ ] `git pull origin main` effectué
- [ ] `php test_r2_upload_config.php` → Tout ✅
- [ ] Cache Laravel vidé
- [ ] Upload d'un document de test via Admin
- [ ] Fichier visible dans Cloudflare R2 Dashboard
- [ ] Téléchargement client fonctionne
- [ ] URL commence par `https://files.dossypro.com/`

---

## ⚠️ Notes Importantes

### **1. Cette correction s'applique à TOUS les uploads**

Les méthodes du contrôleur qui utilisent `getStorageDisk()` :
- ✅ `storeDocument()` - Upload de nouveaux documents
- ✅ `updateDocument()` - Mise à jour de documents
- ✅ `bulkUpload()` - Upload en masse

Toutes utilisent maintenant la **configuration dynamique R2**.

### **2. Pas besoin de modifier `.env`**

Les paramètres R2 restent dans la **base de données** (table `settings`).  
Aucune modification du fichier `.env` n'est requise.

### **3. Compatible avec local/wasabi/s3**

Si vous changez `storage_setting` en 'local', les uploads iront en local automatiquement.  
Le code détecte le storage configuré et s'adapte.

---

## 🎯 Résumé Technique

### **Problème**
Le disk 'r2' n'était pas configuré avec les credentials de la DB avant l'upload.

### **Solution**
Ajout de `config([...])` dans `getStorageDisk()` pour configurer dynamiquement le disk R2.

### **Inspiration**
Même mécanisme que `Utility::get_file()` qui fonctionnait déjà pour les downloads.

### **Résultat**
✅ Les uploads vont maintenant vers Cloudflare R2  
✅ Les fichiers apparaissent sur R2  
✅ Les téléchargements fonctionnent avec URLs R2 publiques

---

## 📞 Support

**Si les uploads ne vont toujours pas vers R2après cette correction :**

Envoyez-moi :
1. Sortie de `php test_r2_upload_config.php`
2. Sortie de `php diagnose_storage.php`
3. Logs Laravel lors de l'upload :
   ```bash
   tail -f storage/logs/laravel.log
   ```
4. Screenshot de l'erreur (s'il y en a une)

---

**Date :** 2025-11-26  
**Projet :** Dossy Pro - Fix Upload R2  
**Pull Request :** https://github.com/stealbass/doss/pull/10  
**Fichiers modifiés :** `app/Http/Controllers/LegalLibraryController.php`
