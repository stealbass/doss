# 📁 Structure des Dossiers Cloudflare R2 pour DossyPro

## ✅ Changements Appliqués

### 1. **Legal Library - Permission Superadmin** ✅
**Problème**: Superadmin ne pouvait pas télécharger les documents de Legal Library
**Solution**: Modifié `UserLegalLibraryController.php` pour autoriser:
- `super admin` (accès complet)
- `company` (accès existant)
- Utilisateurs avec permission `view legal library`

**Code modifié**: `app/Http/Controllers/UserLegalLibraryController.php` ligne 149-197

---

## 📂 Structure des Dossiers à Créer dans Cloudflare R2

D'après l'analyse du code, voici les **3 dossiers** que vous devez créer dans votre bucket `dossy-pro-documents`:

### Dossier 1: `templates/` ⚠️ **MANQUANT - À CRÉER**
**Usage**: Stockage des modèles de documents (contrats, actes, formulaires)
**Contrôleur**: `DocumentTemplateController.php` ligne 157
```php
$filePath = $file->storeAs('templates', $fileName, 'public');
```

**Comment créer**:
1. Aller sur Cloudflare Dashboard → R2 → `dossy-pro-documents`
2. Cliquer sur **"Add directory"** ou **"Upload"**
3. Créer le dossier: `templates/`

**Fichiers attendus**: 
- `templates/contrat-de-bail_1767108603.pdf`
- `templates/acte-de-vente_xxx.docx`
- etc.

---

### Dossier 2: `legal_documents/` ✅ **DÉJÀ CRÉÉ**
**Usage**: Documents juridiques de la bibliothèque légale
**Contrôleur**: `LegalDocument` model + API

**Status**: ✅ Visible dans votre screenshot - déjà fonctionnel

---

### Dossier 3: `fiscal_resources/` ⚠️ **MANQUANT - À CRÉER**
**Usage**: Ressources fiscales et sociales (barèmes, grilles salariales)
**Contrôleur**: `FiscalSocialResourceController.php` ligne 183 et 264
```php
$filePath = $file->storeAs('fiscal_resources', $fileName, 'public');
```

**Comment créer**:
1. Aller sur Cloudflare Dashboard → R2 → `dossy-pro-documents`
2. Cliquer sur **"Add directory"**
3. Créer le dossier: `fiscal_resources/`

---

### Dossier 4 (Optionnel): `uploads/` ✅ **DÉJÀ CRÉÉ**
**Usage**: Uploads généraux (avatars, fichiers temporaires, etc.)
**Status**: ✅ Visible dans votre screenshot

---

## 🚀 Actions Immédiates à Faire

### Étape 1: Créer les Dossiers Manquants dans R2

1. **Se connecter à Cloudflare**:
   - Aller sur https://dash.cloudflare.com
   - Sélectionner **R2 Object Storage**
   - Ouvrir le bucket `dossy-pro-documents`

2. **Créer `templates/`**:
   - Cliquer sur **"Add directory"** (bouton bleu à droite)
   - Nom: `templates`
   - Cliquer sur **"Create directory"**

3. **Créer `fiscal_resources/`**:
   - Même procédure
   - Nom: `fiscal_resources`

### Étape 2: Vérifier la Structure Finale

Après création, votre bucket doit contenir:

```
dossy-pro-documents/
├── dossy-pro-documents/    (dossier existant)
├── legal_documents/        ✅ (déjà créé)
├── templates/              ⚠️ (À CRÉER)
├── fiscal_resources/       ⚠️ (À CRÉER)
└── uploads/                ✅ (déjà créé)
```

---

## 🔧 Migration des Fichiers Existants (Si Nécessaire)

Si vous avez déjà uploadé des templates ou ressources fiscales dans l'interface admin **avant** de créer ces dossiers:

### Option 1: Migrer les Fichiers Manuellement

1. **Identifier les fichiers orphelins**:
   - Dans votre bucket R2, cherchez les fichiers `.pdf`, `.docx` qui ne sont pas dans un dossier

2. **Déplacer vers le bon dossier**:
   - Templates → déplacer vers `templates/`
   - Ressources fiscales → déplacer vers `fiscal_resources/`
   - Documents juridiques → déplacer vers `legal_documents/` (déjà fait)

### Option 2: Re-uploader depuis l'Admin

1. Supprimer l'ancien fichier depuis l'interface admin
2. Re-uploader le document
3. Le système le placera automatiquement dans le bon dossier

---

## 🧪 Test de Validation

### Test 1: Templates

1. **Admin Interface**: Aller dans **Document Templates**
2. **Upload nouveau template**: Upload un PDF de test
3. **Vérifier dans R2**: Le fichier doit apparaître dans `templates/`
4. **Télécharger**: Cliquer sur "Download" → doit ouvrir l'URL R2 sans erreur 404

**URL attendue**: `https://pub-2b54bec5c687409b8b778941280fb43f.r2.dev/templates/nom-fichier.pdf`

### Test 2: Legal Library (Superadmin)

1. **Se connecter en tant que Superadmin**
2. **Aller dans Legal Library** (interface utilisateur, pas admin)
3. **Télécharger un document juridique**
4. **Résultat attendu**: ✅ Téléchargement réussi (plus d'erreur "Permission Denied")

**URL attendue**: `https://pub-2b54bec5c687409b8b778941280fb43f.r2.dev/legal_documents/nom-doc.pdf`

### Test 3: Fiscal Resources

1. **Admin Interface**: Aller dans **Fiscal Resources**
2. **Upload nouvelle ressource**: Upload un fichier Excel/PDF
3. **Vérifier dans R2**: Le fichier doit apparaître dans `fiscal_resources/`
4. **Télécharger**: Cliquer sur "Download" → doit fonctionner

**URL attendue**: `https://pub-2b54bec5c687409b8b778941280fb43f.r2.dev/fiscal_resources/nom-ressource.xlsx`

---

## 📊 Vérification des URLs Générées

### Vérifier dans la Base de Données

Connectez-vous à votre base de données et vérifiez les `file_path`:

```sql
-- Templates
SELECT id, name, file_path FROM document_templates LIMIT 5;
-- Doit retourner: templates/nom-fichier.pdf

-- Legal Documents
SELECT id, title, file_path FROM legal_documents LIMIT 5;
-- Doit retourner: legal_documents/nom-fichier.pdf

-- Fiscal Resources
SELECT id, title, file_path FROM fiscal_social_resources LIMIT 5;
-- Doit retourner: fiscal_resources/nom-fichier.pdf
```

### Tester les URLs Publiques

Dans votre navigateur, testez:

```
https://pub-2b54bec5c687409b8b778941280fb43f.r2.dev/templates/[votre-fichier].pdf
https://pub-2b54bec5c687409b8b778941280fb43f.r2.dev/legal_documents/[votre-fichier].pdf
https://pub-2b54bec5c687409b8b778941280fb43f.r2.dev/fiscal_resources/[votre-fichier].pdf
```

**Résultat attendu**: Le fichier s'ouvre/télécharge directement (pas d'erreur 404)

---

## ❌ Résolution des Erreurs Courantes

### Erreur: "Object not found" (404)

**Causes possibles**:

1. **Le dossier n'existe pas dans R2**
   - ✅ Solution: Créer les dossiers `templates/` et `fiscal_resources/`

2. **Le fichier n'a pas été uploadé dans le bon dossier**
   - ✅ Solution: Re-uploader le fichier depuis l'admin

3. **Public Access désactivé sur R2**
   - ✅ Vérifier: Dashboard R2 → Bucket Settings → **Public Access = Enabled** (déjà fait d'après screenshot)

4. **Le file_path en base ne correspond pas au fichier R2**
   - ✅ Solution: Vérifier la cohérence entre `file_path` en DB et fichier dans R2

### Erreur: "Permission Denied" (Legal Library)

**Status**: ✅ **CORRIGÉ** 

Le code a été modifié pour autoriser:
- Superadmin (accès total)
- Company users
- Utilisateurs avec permission "view legal library"

---

## 📝 Récapitulatif des Modifications Code

### Fichiers Modifiés:

1. ✅ `app/Http/Controllers/UserLegalLibraryController.php`
   - Ajout autorisation superadmin
   - Support R2/S3/Wasabi (pas seulement R2)

2. ✅ `app/Http/Controllers/DocumentTemplateController.php`
   - Ajout gestion cloud storage (R2/S3/Wasabi)
   - Fallback local si URL cloud manquante

3. ✅ `app/Http/Controllers/FiscalSocialResourceController.php`
   - Ajout méthode `download()`
   - Support cloud storage avec fallback local

4. ✅ `app/Models/DocumentTemplate.php`
   - `getFileUrlAttribute()` utilise `Utility::get_file()` pour R2

5. ✅ `app/Models/FiscalSocialResource.php`
   - `getFileUrlAttribute()` utilise `Utility::get_file()` pour R2

6. ✅ `routes/web.php`
   - Ajout route download pour fiscal resources

---

## ✅ Checklist de Validation

- [ ] Dossier `templates/` créé dans R2
- [ ] Dossier `fiscal_resources/` créé dans R2
- [ ] Upload test template depuis admin → fichier dans `templates/`
- [ ] Download test template depuis admin → fichier s'ouvre (pas 404)
- [ ] Superadmin peut télécharger documents Legal Library
- [ ] Upload test ressource fiscale depuis admin → fichier dans `fiscal_resources/`
- [ ] Download test ressource fiscale depuis admin → fichier s'ouvre
- [ ] Test depuis Flutter: Templates téléchargeables
- [ ] Test depuis Flutter: Legal Library téléchargeable
- [ ] Test depuis Flutter: Fiscal Resources téléchargeable

---

## 🎯 Prochaines Étapes

1. **Maintenant**: Créer les 2 dossiers manquants dans R2
2. **Ensuite**: Tester les téléchargements dans l'admin
3. **Puis**: Tester depuis l'application Flutter mobile
4. **Optionnel**: Migrer les fichiers existants vers les bons dossiers

---

**Note Importante**: Après avoir créé les dossiers dans R2, vous pouvez immédiatement tester. **Aucune modification de code n'est nécessaire** - les corrections ont déjà été appliquées côté backend.
