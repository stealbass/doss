# Legal Library - Configuration pour upload de fichiers 200MB

## Modifications effectuées

### 1. Contrôleur Backend (LegalLibraryController.php)
✅ Limite de validation augmentée à 200MB :
- `storeDocument()` : `max:204800` (200MB)
- `bulkUploadStore()` : `files.*` → `max:204800` (200MB par fichier)
- `updateDocument()` (remplacement optionnel) : `max:204800` (200MB)

### 2. Vues Frontend
✅ Messages mis à jour dans les formulaires :
- `create-document.blade.php` : "Maximum file size: 200MB"
- `edit-document.blade.php` : "Maximum file size: 200MB"
- `bulk-upload.blade.php` : 
  - Message d'information : "200MB per file"
  - Validation JavaScript : 200 * 1024 * 1024 bytes
  - Message d'erreur : "exceed the maximum size of 200MB"

## Configuration PHP requise

⚠️ **IMPORTANT** : Pour que les uploads de 200MB fonctionnent, vous devez configurer PHP :

### Option 1 : Modifier php.ini (Recommandé)

Localisez votre fichier `php.ini` et modifiez ces valeurs :

```ini
upload_max_filesize = 200M
post_max_size = 220M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

**Note** : `post_max_size` doit être légèrement supérieur à `upload_max_filesize`

### Option 2 : Modifier .htaccess (Si Apache)

Ajoutez ces lignes dans votre fichier `.htaccess` :

```apache
php_value upload_max_filesize 200M
php_value post_max_size 220M
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 256M
```

### Option 3 : Dans .user.ini (Hébergement partagé)

Si vous êtes sur un hébergement partagé (comme AlwaysData), créez un fichier `.user.ini` :

```ini
upload_max_filesize = 200M
post_max_size = 220M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

## Vérification de la configuration

Créez un fichier `info.php` à la racine de votre projet :

```php
<?php
phpinfo();
?>
```

Accédez à `https://votre-domaine.com/info.php` et vérifiez :
- `upload_max_filesize` = 200M ou plus
- `post_max_size` = 220M ou plus
- `max_execution_time` = 300 ou plus

⚠️ **Supprimez le fichier info.php après vérification pour des raisons de sécurité !**

## Configuration Nginx (Si applicable)

Si vous utilisez Nginx, ajoutez dans votre configuration :

```nginx
client_max_body_size 220M;
```

## Redémarrage requis

Après modification de la configuration :
- **Apache** : `sudo systemctl restart apache2`
- **Nginx** : `sudo systemctl restart nginx`
- **PHP-FPM** : `sudo systemctl restart php8.2-fpm` (adaptez la version)

## Test

1. Connectez-vous en tant que Super Admin ou SuperAdmin Employee avec permission "manage legal-library"
2. Allez dans Legal Library → Sélectionnez une catégorie
3. Cliquez sur "Upload Single" ou "Import Multiple"
4. Essayez d'uploader un fichier PDF de plus de 100MB (jusqu'à 200MB)
5. Vérifiez que l'upload fonctionne sans erreur

## Permissions utilisateurs

✅ Peuvent uploader des fichiers PDF jusqu'à 200MB :
- **Super Admin** (`type == 'super admin'`)
- **SuperAdmin Employee** avec permission `manage legal-library` (ID 3)
- **Company users** (`type == 'company'`)

## Stockage

Les fichiers sont stockés selon la configuration dans `storage_setting` :
- **Local** : `storage/app/public/legal_documents/`
- **Cloudflare R2** : Bucket configuré dans les paramètres

**Note** : Assurez-vous d'avoir suffisamment d'espace disque ou de quota R2 !

---

**Date de modification** : 16 janvier 2026
