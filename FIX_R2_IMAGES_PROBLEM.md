# 🔧 FIX: Problème d'affichage des images avec Cloudflare R2

## 🎯 Problème identifié

Les images ne s'affichent pas sur le site depuis que Cloudflare R2 a été configuré comme système de stockage.

## ✅ Cause du problème

Le modèle `app/Models/Utility.php` ne gérait **PAS** le cas Cloudflare R2 dans ses méthodes:
- `get_file()` - Récupération URL des fichiers
- `upload_file()` - Upload des fichiers
- `fetchSettings()` - Valeurs par défaut
- `getStorageSetting()` - Récupération config stockage

Résultat: Quand R2 était configuré, Laravel ne savait pas comment récupérer les URLs des images.

## ✅ Corrections appliquées

### 1. Méthode `get_file()` (ligne 152)
**Ajouté:**
```php
elseif ($settings['storage_setting'] == 'r2') {
    config([
        'filesystems.disks.r2.key' => $settings['r2_key'],
        'filesystems.disks.r2.secret' => $settings['r2_secret'],
        'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
        'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
        'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
        'filesystems.disks.r2.url' => $settings['r2_url'],
        'filesystems.disks.r2.use_path_style_endpoint' => false,
    ]);
}
```

### 2. Méthode `upload_file()` (ligne 293)
**Ajouté (configuration):**
```php
else if ($settings['storage_setting'] == 'r2') {
    config([
        'filesystems.disks.r2.key' => $settings['r2_key'],
        'filesystems.disks.r2.secret' => $settings['r2_secret'],
        'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
        'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
        'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
        'filesystems.disks.r2.url' => $settings['r2_url'],
        'filesystems.disks.r2.use_path_style_endpoint' => false,
    ]);
    $max_size = !empty($settings['r2_max_upload_size']) ? $settings['r2_max_upload_size'] : '2048';
    $mimes = !empty($settings['r2_storage_validation']) ? $settings['r2_storage_validation'] : '';
}
```

**Ajouté (upload réel):**
```php
else if ($settings['storage_setting'] == 'r2') {
    $path = Storage::disk('r2')->putFileAs(
        $path,
        $file,
        $name
    );
}
```

### 3. Méthode `fetchSettings()` (ligne 32)
**Ajouté valeurs par défaut:**
```php
"r2_key" => "",
"r2_secret" => "",
"r2_region" => "auto",
"r2_bucket" => "",
"r2_endpoint" => "",
"r2_url" => "",
"r2_max_upload_size" => "",
"r2_storage_validation" => "",
```

### 4. Méthode `getStorageSetting()` (ligne 485)
**Ajouté les mêmes valeurs par défaut**

## 📋 Actions à effectuer sur le serveur

### 1. Déployer le fix

```bash
# Se connecter au serveur AlwaysData
ssh dossypro@ssh-dossypro.alwaysdata.net

# Aller dans le répertoire
cd ~/public_html

# Pull les changements
git pull origin main

# Vider le cache Laravel
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 2. Vérifier la configuration R2 dans la base de données

Connectez-vous à PhpMyAdmin et vérifiez que ces paramètres existent dans la table `settings`:

```sql
SELECT * FROM settings WHERE name LIKE 'r2_%' OR name = 'storage_setting';
```

**Valeurs attendues:**
- `storage_setting` = `r2`
- `r2_key` = Votre Access Key ID Cloudflare
- `r2_secret` = Votre Secret Access Key
- `r2_region` = `auto`
- `r2_bucket` = Nom de votre bucket
- `r2_endpoint` = `https://<account-id>.r2.cloudflarestorage.com`
- `r2_url` = `https://<custom-domain>` ou `https://pub-xxxxx.r2.dev`
- `r2_max_upload_size` = `51200` (50MB en KB)
- `r2_storage_validation` = `jpg,jpeg,png,pdf,doc,docx,zip`

### 3. Vérifier les fichiers existants

Si vous avez déjà des fichiers uploadés **AVANT** le fix:

**Option A: Les fichiers sont déjà sur R2**
→ Ils vont maintenant s'afficher correctement après le fix

**Option B: Les fichiers sont encore en local**
→ Il faut les migrer vers R2. Deux solutions:

#### Solution 1: Re-upload via l'interface admin
- Aller dans Paramètres → Entreprise
- Re-uploader le logo, favicon, etc.

#### Solution 2: Migration manuelle (plus rapide)
```bash
# Sur le serveur, installer AWS CLI si pas déjà fait
pip3 install awscli --user

# Configurer AWS CLI pour R2
aws configure --profile r2
# AWS Access Key ID: <votre R2 Access Key>
# AWS Secret Access Key: <votre R2 Secret Key>
# Default region name: auto
# Default output format: json

# Migrer les fichiers
aws s3 sync storage/app/public/ s3://<votre-bucket>/ \
  --profile r2 \
  --endpoint-url https://<account-id>.r2.cloudflarestorage.com
```

### 4. Test de vérification

**Test 1: Vérifier qu'une image s'affiche**
- Aller sur le site
- Vérifier que le logo s'affiche
- Inspecter l'élément (F12)
- L'URL devrait être: `https://<custom-domain>/uploads/logo-light.png`

**Test 2: Upload un nouveau fichier**
- Aller dans Paramètres → Profil
- Changer votre avatar
- Vérifier qu'il s'affiche
- Inspecter → l'URL doit pointer vers R2

**Test 3: Vérifier les logs Laravel**
```bash
tail -f storage/logs/laravel.log
```
Si erreur R2, vous verrez des messages ici.

## 🔍 Diagnostic des problèmes restants

### Problème: Images toujours pas affichées après le fix

**Vérification 1: R2_URL correct?**
```bash
# Dans PhpMyAdmin
SELECT value FROM settings WHERE name = 'r2_url';
```
L'URL doit être:
- Soit votre domaine personnalisé: `https://cdn.dossypro.cm`
- Soit l'URL publique R2: `https://pub-xxxxxxxxxxxxx.r2.dev`

**⚠️ ATTENTION:** L'URL **NE DOIT PAS** être l'endpoint API:
- ❌ MAUVAIS: `https://xxxxx.r2.cloudflarestorage.com`
- ✅ BON: `https://pub-xxxxx.r2.dev` ou domaine personnalisé

**Vérification 2: Bucket public?**
```bash
# Sur Cloudflare dashboard
# Aller dans R2 → Votre bucket → Settings
# Vérifier "Public Access" = Enabled
```

**Vérification 3: Permissions R2**
Vérifiez que votre Access Key a les permissions:
- Object Read
- Object Write
- Object List

### Problème: Upload fonctionne mais affichage non

→ Vérifier CORS sur le bucket R2:

```json
[
  {
    "AllowedOrigins": ["https://dossypro.cm", "https://www.dossypro.cm"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3000
  }
]
```

### Problème: 403 Forbidden sur les images

→ Le bucket n'est pas public. Solution:

1. Sur Cloudflare Dashboard
2. R2 → Votre bucket → Settings
3. Activer "Allow Access" sous Public Access
4. Copier l'URL publique (pub-xxxxx.r2.dev)
5. Mettre à jour `r2_url` dans la BDD

## 📊 Vérification finale

Après déploiement, exécutez:

```bash
# Test manuel depuis le serveur
php artisan tinker

# Dans Tinker:
>>> $settings = App\Models\Utility::settings();
>>> $settings['storage_setting']; // Doit afficher: "r2"
>>> $settings['r2_url']; // Doit afficher votre URL publique
>>> $settings['r2_bucket']; // Doit afficher nom du bucket

# Test get_file
>>> App\Models\Utility::get_file('uploads/logo-light.png');
// Doit retourner: "https://<votre-r2-url>/uploads/logo-light.png"
```

## ✅ Fichiers modifiés

- `app/Models/Utility.php` - Ajout support R2 dans 4 méthodes
- `FIX_R2_IMAGES_PROBLEM.md` - Ce document

## 🚀 Prochaines étapes

1. ✅ Commiter le fix
2. ✅ Créer PR
3. ✅ Merger dans main
4. ✅ Déployer sur serveur
5. ✅ Vérifier configuration BDD
6. ✅ Tester upload et affichage
7. ✅ Migrer fichiers existants si besoin

---

**Date:** 2024-11-22  
**Développeur:** Claude AI  
**Statut:** ✅ Fix prêt pour déploiement
