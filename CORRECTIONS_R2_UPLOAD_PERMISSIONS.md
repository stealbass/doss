# ✅ Corrections Appliquées - Téléchargements et Upload R2

## 🔧 Problèmes Identifiés et Corrigés

### Problème 1: Permission Legal Library pour Superadmin ❌ → ✅

**Symptôme**: Superadmin ne pouvait pas télécharger les documents de Legal Library

**Cause**: Le code vérifiait uniquement `Auth::user()->can('view legal library')` dans **4 méthodes différentes**:
1. `index()` - Liste des documents
2. `showCategory()` - Documents par catégorie  
3. `viewDocument()` - Aperçu d'un document
4. `streamDocument()` - Streaming pour prévisualisation
5. `downloadDocument()` - Téléchargement (déjà corrigé)

**Solution Appliquée**: Modifié **TOUTES** les méthodes pour autoriser:
```php
$user->type === 'super admin' || $user->type === 'company' || $user->can('view legal library')
```

#### Fichier Modifié:
- `app/Http/Controllers/UserLegalLibraryController.php`
  - Ligne 17-25: `index()` ✅
  - Ligne 44-53: `showCategory()` ✅
  - Ligne 74-83: `viewDocument()` ✅
  - Ligne 100-109: `streamDocument()` ✅
  - Ligne 149-157: `downloadDocument()` ✅ (déjà fait)

---

### Problème 2: Templates non sauvegardés dans R2 ❌ → ✅

**Symptôme**: Les nouveaux templates sont créés mais ne vont pas dans le dossier `templates/` de Cloudflare R2

**Cause**: Le code utilisait le disk **hardcodé `'public'`** (stockage local) au lieu du disk **configuré** (R2):
```php
// ❌ AVANT
$filePath = $file->storeAs('templates', $fileName, 'public');
```

**Solution Appliquée**: Le code détecte maintenant le `storage_setting` depuis la base de données et utilise le bon disk:
```php
// ✅ APRÈS
$settings = Utility::settings();
$storageSetting = $settings['storage_setting'] ?? 'local';
$disk = ($storageSetting === 'local') ? 'public' : $storageSetting;
$filePath = $file->storeAs('templates', $fileName, $disk);
```

Si `storage_setting = 'r2'`, alors `$disk = 'r2'` et les fichiers vont dans R2 ✅

#### Fichiers Modifiés:
- `app/Http/Controllers/DocumentTemplateController.php`
  - Ligne 152-165: `store()` - Création nouveau template ✅
  - Ligne 251-266: `update()` - Mise à jour template existant ✅

---

### Problème 3: Fiscal Resources non sauvegardés dans R2 ❌ → ✅

**Symptôme**: Même problème que les templates - fichiers vont dans stockage local au lieu de R2

**Solution Appliquée**: Même correction que pour les templates - détection automatique du disk configuré

#### Fichiers Modifiés:
- `app/Http/Controllers/FiscalSocialResourceController.php`
  - Ligne 179-189: `store()` - Création nouvelle ressource ✅
  - Ligne 256-270: `update()` - Mise à jour ressource existante ✅

---

## 📊 Résumé des Modifications

| Fichier | Lignes Modifiées | Correction |
|---------|------------------|------------|
| `UserLegalLibraryController.php` | 17-25 | Permission superadmin dans `index()` |
| `UserLegalLibraryController.php` | 44-53 | Permission superadmin dans `showCategory()` |
| `UserLegalLibraryController.php` | 74-83 | Permission superadmin dans `viewDocument()` |
| `UserLegalLibraryController.php` | 100-109 | Permission superadmin dans `streamDocument()` |
| `DocumentTemplateController.php` | 152-165 | Upload R2 dans `store()` |
| `DocumentTemplateController.php` | 251-266 | Upload R2 dans `update()` |
| `FiscalSocialResourceController.php` | 179-189 | Upload R2 dans `store()` |
| `FiscalSocialResourceController.php` | 256-270 | Upload R2 dans `update()` |

**Total**: 8 corrections dans 3 fichiers

---

## 🧪 Tests à Effectuer

### Test 1: Legal Library - Superadmin ✅

1. **Se connecter en tant que Superadmin**
2. **Aller dans Legal Library** (menu utilisateur, pas admin)
3. **Actions à tester**:
   - ✅ Voir la liste des catégories
   - ✅ Ouvrir une catégorie
   - ✅ Prévisualiser un document
   - ✅ Télécharger un document

**Résultat attendu**: Toutes les actions fonctionnent sans erreur "Permission Denied"

---

### Test 2: Upload Templates dans R2 ✅

1. **Admin → Document Templates**
2. **Créer nouveau template**:
   - Nom: "Test Template R2"
   - Catégorie: n'importe laquelle
   - Fichier: Upload un PDF
   - Cliquer "Save"

3. **Vérifier dans Cloudflare R2**:
   - Aller sur Dashboard R2 → `dossy-pro-documents`
   - Ouvrir le dossier `templates/`
   - ✅ Le fichier doit apparaître: `test-template-r2_1735XXXXXX.pdf`

4. **Vérifier téléchargement**:
   - Cliquer sur "Download" dans l'interface admin
   - ✅ Le fichier doit s'ouvrir depuis R2 (URL: `https://pub-2b54bec5c687409b8b778941280fb43f.r2.dev/templates/...`)

**Résultat attendu**: Fichier uploadé dans R2 et téléchargeable

---

### Test 3: Upload Fiscal Resources dans R2 ✅

1. **Admin → Fiscal Resources**
2. **Créer nouvelle ressource**:
   - Titre: "Test Fiscal R2"
   - Type: Fiscal
   - Pays: CI (ou autre)
   - Année: 2024
   - Fichier: Upload un PDF/Excel
   - Cliquer "Save"

3. **Vérifier dans Cloudflare R2**:
   - Dashboard R2 → `dossy-pro-documents`
   - Ouvrir le dossier `fiscal_resources/`
   - ✅ Le fichier doit apparaître

4. **Vérifier téléchargement**:
   - Cliquer sur "Download" dans l'admin
   - ✅ Fichier s'ouvre depuis R2

**Résultat attendu**: Fichier uploadé dans R2 et téléchargeable

---

### Test 4: Update Template existant

1. **Admin → Document Templates**
2. **Modifier un template existant**
3. **Changer le fichier** (upload nouveau PDF)
4. **Vérifier dans R2**:
   - ✅ Ancien fichier supprimé
   - ✅ Nouveau fichier présent dans `templates/`

---

### Test 5: Flutter Mobile - Téléchargements

Maintenant que les fichiers sont dans R2, tester depuis l'app Flutter:

1. **Templates**: Télécharger un template
2. **Legal Library**: Télécharger un document juridique
3. **Fiscal Resources**: Télécharger une ressource fiscale

**Résultat attendu**: Les 3 types de téléchargements fonctionnent (plus de 404)

---

## ⚙️ Configuration Requise

Pour que tout fonctionne, vérifiez dans la **base de données** table `settings`:

```sql
SELECT * FROM settings WHERE `name` IN (
    'storage_setting',
    'r2_key',
    'r2_secret',
    'r2_bucket',
    'r2_endpoint',
    'r2_url',
    'r2_region'
);
```

**Valeurs attendues**:
- `storage_setting` = `'r2'`
- `r2_bucket` = `'dossy-pro-documents'`
- `r2_endpoint` = `'https://7765e0eb4a2304ee777be0bafc77ecf3.r2.cloudflarestorage.com'`
- `r2_url` = `'https://pub-2b54bec5c687409b8b778941280fb43f.r2.dev'`
- `r2_region` = `'auto'`
- `r2_key` = (votre clé API)
- `r2_secret` = (votre secret API)

Si `storage_setting` n'est pas `'r2'`, les fichiers continueront d'aller dans le stockage local.

---

## 🔄 Après les Modifications

**Actions nécessaires**:

1. **Vider le cache Laravel**:
```bash
php artisan config:clear
php artisan cache:clear
```

2. **Tester immédiatement**:
   - Créer un nouveau template
   - Vérifier qu'il apparaît dans R2 `templates/`
   - Télécharger depuis l'admin
   - Télécharger depuis Flutter

---

## ✅ Checklist Finale

- [x] Permission superadmin Legal Library corrigée (5 méthodes)
- [x] Upload templates vers R2 au lieu de local
- [x] Update templates vers R2
- [x] Upload fiscal resources vers R2
- [x] Update fiscal resources vers R2
- [ ] Test Legal Library en tant que superadmin
- [ ] Test création template → fichier dans R2
- [ ] Test création fiscal resource → fichier dans R2
- [ ] Test téléchargement depuis Flutter

---

## 📝 Notes Importantes

### Disk Configuration

Le code détecte automatiquement le disk à utiliser:

```php
$settings = Utility::settings();
$storageSetting = $settings['storage_setting'] ?? 'local';
$disk = ($storageSetting === 'local') ? 'public' : $storageSetting;
```

**Comportement**:
- Si `storage_setting = 'local'` → utilise disk `'public'` (storage/app/public/)
- Si `storage_setting = 'r2'` → utilise disk `'r2'` (Cloudflare R2)
- Si `storage_setting = 's3'` → utilise disk `'s3'` (AWS S3)
- Si `storage_setting = 'wasabi'` → utilise disk `'wasabi'` (Wasabi)

### Migration Fichiers Existants

Si vous avez déjà des templates/fiscal resources uploadés AVANT cette correction:

1. **Option 1**: Les laisser en local (ils fonctionneront via fallback)
2. **Option 2**: Les migrer manuellement vers R2:
   - Télécharger depuis admin
   - Re-uploader (ira dans R2 automatiquement)
3. **Option 3**: Script de migration (nous pouvons le créer si nécessaire)

---

**Date**: 3 janvier 2026  
**Status**: ✅ Toutes les corrections appliquées et prêtes pour test
