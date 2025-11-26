# 🔧 Fix : Nouveaux Uploads ne vont pas vers R2

## 🔴 Problème Identifié

**Symptômes :**
- ✅ Les fichiers existants dans R2 s'affichent correctement
- ❌ Les nouveaux documents uploadés via Legal Library ne vont **PAS** vers R2
- ❌ Les nouveaux PDFs restent en **local** (storage/app/public/legal_documents)
- ❌ Ces nouveaux fichiers ne s'affichent pas car ils ne sont pas sur R2

**Cause :**
Le paramètre `storage_setting` dans la base de données n'est **PAS** configuré sur `'r2'`

---

## ✅ Solution Rapide (3 étapes)

### **Méthode 1 : Script Automatique (RECOMMANDÉ)**

```bash
# SSH vers AlwaysData
ssh dossypro@ssh-dossypro.alwaysdata.net
cd ~/public_html

# 1. Diagnostic
php diagnose_storage.php

# 2. Correction automatique
php fix_storage_r2.php

# 3. Test
php artisan tinker
```

Dans tinker :
```php
App\Models\Utility::getValByName('storage_setting');
// Doit retourner: "r2"

exit
```

---

### **Méthode 2 : Requête SQL Directe**

```sql
-- Mettre storage_setting à 'r2'
UPDATE settings SET value = 'r2' WHERE `key` = 'storage_setting';

-- Vérifier
SELECT * FROM settings WHERE `key` = 'storage_setting';
```

Puis vider le cache :
```bash
php artisan cache:clear
php artisan config:clear
```

---

### **Méthode 3 : Interface Admin**

1. Connectez-vous en tant que **Super Admin**
2. **Settings → Storage Settings**
3. Sélectionnez **Cloudflare R2** ou **R2**
4. Cliquez sur **Save**
5. Videz le cache via SSH :
```bash
php artisan cache:clear
```

---

## 🧪 Test de Vérification

### **Test 1 : Vérifier la configuration**
```bash
php diagnose_storage.php
```

**Attendu :**
```
storage_setting (DB directe) : r2
storage_setting (Utility::settings()) : r2
Disk qui sera utilisé : 'r2'
✅ Les uploads iront vers R2
```

### **Test 2 : Upload d'un nouveau document**

1. **Super Admin → Legal Library → Catégorie**
2. **Add Document**
3. Uploadez un PDF de test (ex: `test_r2_upload.pdf`)
4. Cliquez sur **Save**

### **Test 3 : Vérifier sur R2**

**Dashboard Cloudflare R2 :**
- Ouvrez votre bucket
- Recherchez : `legal_documents/`
- **Le fichier `test_r2_upload.pdf` doit apparaître** avec un timestamp récent

### **Test 4 : Vérifier l'affichage client**

1. Connectez-vous en tant qu'**utilisateur** (pas admin)
2. **Bibliothèque Juridique**
3. Trouvez le document `test_r2_upload.pdf`
4. Cliquez sur **Télécharger** ou **Voir**
5. **Le PDF doit s'afficher** depuis R2

**URL attendue :**
```
https://files.dossypro.com/legal_documents/1732xxxxx_test_r2_upload.pdf
```

---

## 🔍 Diagnostic Détaillé

### **Que fait le code lors d'un upload ?**

**Fichier :** `app/Http/Controllers/LegalLibraryController.php`

```php
private function getStorageDisk()
{
    $settings = Utility::settings();
    $storageSetting = $settings['storage_setting'] ?? 'local';
    
    // Si R2 configuré, utiliser R2, sinon public (local)
    return ($storageSetting === 'r2') ? 'r2' : 'public';
}

// Upload
$disk = $this->getStorageDisk();  // 'r2' ou 'public'
$filePath = $file->storeAs('legal_documents', $fileName, $disk);
```

**Logique :**
1. Lit `storage_setting` depuis la table `settings`
2. Si `storage_setting === 'r2'` → Upload vers R2
3. Sinon → Upload vers `storage/app/public` (local)

---

## 📊 Vérifications de la Base de Données

### **Requête pour vérifier tous les paramètres storage :**

```sql
SELECT `key`, `value` 
FROM `settings` 
WHERE `key` IN (
    'storage_setting',
    'r2_key',
    'r2_secret', 
    'r2_region',
    'r2_bucket',
    'r2_endpoint',
    'r2_url'
)
ORDER BY `key`;
```

**Valeurs attendues :**

| key | value |
|-----|-------|
| `storage_setting` | `r2` |
| `r2_key` | Votre Access Key ID |
| `r2_secret` | Votre Secret Access Key |
| `r2_region` | `auto` |
| `r2_bucket` | Nom de votre bucket |
| `r2_endpoint` | `https://<account-id>.r2.cloudflarestorage.com` |
| `r2_url` | `https://files.dossypro.com` |

---

## ⚠️ Problèmes Courants

### **1. `storage_setting` est `NULL` ou `'local'`**
**Solution :**
```sql
UPDATE settings SET value = 'r2' WHERE `key` = 'storage_setting';
```

### **2. Cache Laravel**
**Solution :**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### **3. `r2_url` manquant**
Les fichiers sont uploadés sur R2 mais les URLs sont incorrectes.

**Solution :**
```sql
INSERT INTO settings (`key`, `value`, created_by, created_at, updated_at) 
VALUES ('r2_url', 'https://files.dossypro.com', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE `value` = 'https://files.dossypro.com';
```

### **4. Permissions R2**
**Solution :**
- Dashboard Cloudflare R2 → Bucket Settings
- **Public Access → Enable**

---

## 🎯 Après la Correction

### **Ce qui devrait fonctionner :**

✅ **Nouveaux uploads via Legal Library → R2**
```
Upload → storage_setting = 'r2' → Storage::disk('r2')->storeAs(...)
```

✅ **Téléchargement utilisateur → URL R2 publique**
```
Download → Utility::get_file('legal_documents/file.pdf')
         → https://files.dossypro.com/legal_documents/file.pdf
```

✅ **Fichiers visibles sur Cloudflare R2**
```
Dashboard R2 → Bucket → legal_documents/ → Fichiers récents
```

---

## 📋 Checklist de Vérification

- [ ] `php diagnose_storage.php` → storage_setting = 'r2'
- [ ] `php fix_storage_r2.php` exécuté si nécessaire
- [ ] Cache Laravel vidé
- [ ] Upload d'un document de test via Admin
- [ ] Fichier apparaît dans R2 Dashboard
- [ ] Téléchargement client fonctionne
- [ ] URL du fichier est `https://files.dossypro.com/legal_documents/...`

---

## 📞 Si ça ne fonctionne toujours pas

Envoyez-moi :
1. **Sortie de :** `php diagnose_storage.php`
2. **Requête SQL :**
   ```sql
   SELECT `key`, `value` FROM settings WHERE `key` LIKE '%storage%' OR `key` LIKE '%r2%';
   ```
3. **Screenshot de l'erreur** lors du téléchargement client
4. **Logs Laravel :** `storage/logs/laravel.log` (dernières lignes)

---

## 🎉 Résumé

**Problème :** `storage_setting` n'était pas `'r2'` → Les uploads allaient en local

**Solution :**
```bash
php fix_storage_r2.php  # Ou requête SQL manuelle
php artisan cache:clear
```

**Résultat :** Tous les **nouveaux uploads** vont maintenant vers **Cloudflare R2** ✅

---

**Date :** 2025-11-26  
**Projet :** Dossy Pro - Intégration R2  
**Scripts :** `diagnose_storage.php`, `fix_storage_r2.php`
