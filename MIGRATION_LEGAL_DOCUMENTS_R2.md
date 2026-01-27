# Migration des Documents Juridiques vers Cloudflare R2

## ✅ Modifications Complétées

### 1. **Script de Migration Mis à Jour**
Le fichier `migrate_local_to_r2.php` a été modifié pour inclure le dossier `legal_documents` :

```php
$directories = [
    'uploads/landing_page_image',
    'uploads/profile', 
    'uploads/logo',
    'uploads/documents',
    'uploads/bill',
    'legal_documents',  // ← AJOUTÉ pour la bibliothèque juridique
    'uploads'
];
```

### 2. **Contrôleur de Bibliothèque Juridique - Gestion des Uploads**
**Fichier modifié :** `app/Http/Controllers/LegalLibraryController.php`

**Avant :**
```php
Storage::disk('public')->storeAs('legal_documents', $fileName, 'public')
```

**Après :**
```php
$disk = Utility::getValByName('storage_setting') ?? 'local';
$disk = ($disk === 'local') ? 'public' : $disk;

// Upload vers le storage configuré (R2 si configuré)
$filePath = Storage::disk($disk)->putFileAs('legal_documents', $file, $fileName);
```

**Résultat :**
- ✅ Les **nouveaux** uploads vont automatiquement vers **R2** si `storage_setting = 'r2'`
- ✅ Support de R2 pour les opérations : **store**, **update**, **delete**
- ✅ Compatibilité avec le storage local si besoin

### 3. **Contrôleur Utilisateur - Gestion des Téléchargements**
**Fichier modifié :** `app/Http/Controllers/UserLegalLibraryController.php`

**Avant :**
```php
$filePath = storage_path('app/public/' . $document->file_path);
return response()->download($filePath, $document->file_name);
```

**Après :**
```php
$disk = Utility::getValByName('storage_setting') ?? 'local';

if ($disk === 'r2') {
    // R2 : Redirection vers URL publique R2
    $r2Url = Utility::get_file($document->file_path);
    return redirect($r2Url);
} else {
    // Local : Téléchargement direct
    $filePath = storage_path('app/public/' . $document->file_path);
    return response()->download($filePath, $document->file_name);
}
```

**Résultat :**
- ✅ Les utilisateurs téléchargent depuis **R2** si configuré
- ✅ URLs R2 publiques générées via `Utility::get_file()`
- ✅ Fallback vers local si R2 non configuré

---

## 🚀 Instructions de Migration - AlwaysData

### **Étape 1 : Connexion SSH**
```bash
ssh dossypro@ssh-dossypro.alwaysdata.net
cd ~/public_html
```

### **Étape 2 : Récupération du Code**
```bash
git pull origin main
```

### **Étape 3 : Vérification de la Configuration R2**
Exécutez le script de test de connexion R2 :
```bash
php test_r2_connection.php
```

**Attendu :**
```
✅ Configuration R2 trouvée dans la base de données
   - Bucket: votre-bucket
   - Endpoint: https://xxxxxxxxx.r2.cloudflarestorage.com
   - URL publique: https://files.dossypro.com
✅ Connexion R2 réussie
```

**Si erreur :**
- Vérifiez les credentials R2 dans la table `settings`
- Assurez-vous que `r2_key`, `r2_secret`, `r2_bucket`, `r2_endpoint`, `r2_url` sont corrects

### **Étape 4 : Migration des Fichiers `legal_documents`**
Exécutez le script de migration :
```bash
php migrate_local_to_r2.php
```

**Ce script va :**
- ✅ Copier **tous les fichiers** de `storage/app/public/legal_documents/` vers R2
- ✅ Préserver la structure des dossiers
- ✅ Garder les fichiers locaux en **backup** (non supprimés)
- ✅ Afficher la progression en temps réel

**Exemple de sortie attendue :**
```
📂 Traitement: legal_documents/
   ✅ legal_documents/contract_model_2024.pdf (1.2 MB)
   ✅ legal_documents/jurisprudence_cameroun.pdf (850 KB)
   ✅ legal_documents/loi_fonciere.pdf (3.5 MB)
   ...

📊 STATISTIQUES FINALES
   Total fichiers: 347
   Succès: 347
   Échecs: 0
   Taille totale: 1.2 GB
   ✅ Migration terminée avec succès!
```

### **Étape 5 : Vérification de la Configuration**
Assurez-vous que le paramètre `storage_setting` est bien configuré :
```bash
php artisan tinker
```

Dans le shell Tinker :
```php
App\Models\Utility::getValByName('storage_setting');
// Doit retourner: "r2"

App\Models\Utility::get_file('legal_documents/test.pdf');
// Doit retourner: "https://files.dossypro.com/legal_documents/test.pdf"

exit
```

### **Étape 6 : Nettoyage du Cache**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### **Étape 7 : Test Utilisateur**
1. **Connexion en tant qu'utilisateur** (pas super admin)
2. **Accéder à la Bibliothèque Juridique**
3. **Cliquer sur "Télécharger"** sur un document
4. **Vérifier que le téléchargement fonctionne** depuis R2

**URL attendue dans le navigateur :**
```
https://files.dossypro.com/legal_documents/nom_du_fichier.pdf
```

---

## 🔧 Configuration R2 dans la Base de Données

Si ce n'est pas déjà fait, vérifiez/ajoutez ces entrées dans la table `settings` :

| `key`                   | `value`                                          |
|-------------------------|--------------------------------------------------|
| `storage_setting`       | `r2`                                              |
| `r2_key`                | Votre Access Key ID R2                            |
| `r2_secret`             | Votre Secret Access Key R2                        |
| `r2_region`             | `auto` (ou votre région)                          |
| `r2_bucket`             | Nom de votre bucket R2                            |
| `r2_endpoint`           | `https://<account-id>.r2.cloudflarestorage.com`   |
| `r2_url`                | `https://files.dossypro.com`                      |

**Requête SQL de vérification :**
```sql
SELECT `key`, `value` 
FROM `settings` 
WHERE `key` LIKE 'r2_%' OR `key` = 'storage_setting';
```

---

## 🛡️ Configuration Cloudflare R2

### **1. Bucket Public Access**
Dans le dashboard Cloudflare R2 :
- Ouvrez votre bucket
- **Settings → Public Access → Enable**
- Notez l'URL publique (ex: `https://pub-xxxxxx.r2.dev`)

### **2. Custom Domain (Recommandé)**
Si vous utilisez `https://files.dossypro.com` :
- **R2 Dashboard → Connect Domain**
- Ajoutez `files.dossypro.com`
- Suivez les instructions DNS de Cloudflare
- Mettez à jour `r2_url` dans la table `settings`

### **3. CORS Configuration**
Pour éviter les erreurs de chargement cross-origin :

```json
[
  {
    "AllowedOrigins": ["https://dossypro.com", "https://www.dossypro.com"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

---

## 📊 Vérification Finale

### **Test 1 : Vérifier qu'un fichier existe sur R2**
```bash
# Via le dashboard Cloudflare R2
# Rechercher : legal_documents/
# Vérifier que les fichiers sont bien listés
```

### **Test 2 : Tester l'URL publique**
Ouvrez dans un navigateur :
```
https://files.dossypro.com/legal_documents/nom_du_fichier.pdf
```
**Attendu :** Le PDF s'affiche correctement

### **Test 3 : Test de téléchargement utilisateur**
1. Connectez-vous en tant qu'utilisateur avec abonnement actif
2. Accédez à **Bibliothèque Juridique**
3. Cliquez sur **Télécharger** sur n'importe quel document
4. **Le PDF doit se télécharger depuis R2** (vérifiez l'URL)

### **Test 4 : Upload d'un nouveau document (Admin)**
1. Connectez-vous en tant que **Super Admin**
2. **Bibliothèque Juridique → Ajouter un document**
3. Uploadez un PDF de test
4. **Vérifiez dans R2** que le fichier apparaît dans `legal_documents/`

---

## ⚠️ Dépannage

### **Problème : "File not found" lors du téléchargement**
**Cause :** Le fichier n'a pas encore été migré vers R2

**Solution :**
```bash
php migrate_local_to_r2.php
```

### **Problème : "Access Denied" sur R2**
**Cause :** Credentials R2 incorrects ou bucket non public

**Solution :**
1. Vérifiez les credentials dans la table `settings`
2. Activez **Public Access** sur le bucket R2
3. Testez la connexion : `php test_r2_connection.php`

### **Problème : Les nouveaux uploads vont encore en local**
**Cause :** `storage_setting` n'est pas `r2`

**Solution :**
```sql
UPDATE `settings` SET `value` = 'r2' WHERE `key` = 'storage_setting';
```
Puis :
```bash
php artisan cache:clear
```

### **Problème : CORS Error dans le navigateur**
**Cause :** Configuration CORS manquante sur R2

**Solution :**
- Dashboard R2 → Bucket Settings → CORS
- Ajoutez la configuration CORS (voir section ci-dessus)

---

## 🎯 Résumé des Changements

| Composant | Avant | Après |
|-----------|-------|-------|
| **Upload de documents** | `Storage::disk('public')` | `Storage::disk($disk)` (R2 si configuré) |
| **Téléchargement** | `storage_path('app/public/')` | `Utility::get_file()` (URL R2 publique) |
| **Migration** | Pas de script | `migrate_local_to_r2.php` avec `legal_documents/` |
| **Stockage** | Local uniquement | R2 + Fallback local |

---

## 📝 Commits GitHub

**Pull Request #10** : https://github.com/stealbass/doss/pull/10

**Commits récents :**
1. ✅ `feat(r2): Add legal_documents support for R2 storage and migration`
2. ✅ `fix(storage): Add Cloudflare R2 support in Utility model methods`
3. ✅ `feat(storage): Add R2 migration scripts and troubleshooting docs`

---

## 📞 Support

**Si vous rencontrez un problème :**
1. Envoyez-moi la sortie de : `php test_r2_connection.php`
2. Envoyez-moi la sortie de : `php migrate_local_to_r2.php`
3. Capture d'écran de l'erreur dans le navigateur
4. Logs Laravel : `storage/logs/laravel.log`

---

## ✅ Checklist de Déploiement

- [ ] `git pull origin main` effectué
- [ ] Configuration R2 vérifiée dans la base de données
- [ ] `php test_r2_connection.php` → Succès
- [ ] `php migrate_local_to_r2.php` → 100% des fichiers migrés
- [ ] `storage_setting = 'r2'` dans la table `settings`
- [ ] Bucket R2 avec **Public Access** activé
- [ ] Custom domain `files.dossypro.com` configuré (optionnel)
- [ ] CORS configuré sur le bucket
- [ ] Cache Laravel vidé : `php artisan cache:clear`
- [ ] Test utilisateur : Téléchargement d'un document → OK
- [ ] Test admin : Upload d'un nouveau document → Apparaît sur R2

---

**Date de création :** 2025-11-25
**Projet :** Dossy IA - Intégration Cloudflare R2
**Pull Request :** https://github.com/stealbass/doss/pull/10
