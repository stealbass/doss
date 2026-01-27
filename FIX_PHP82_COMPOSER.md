# 🔧 Guide de Résolution - Composer et PHP 8.2

## ❌ Erreur Rencontrée
```
Fatal error: Composer detected issues in your platform: 
Your Composer dependencies require a PHP version ">= 8.2.0". 
You are running 7.4.33.
```

## ✅ Solution Complète

Cette erreur signifie que le dossier `vendor` a été généré avec PHP 7.4. Il faut le régénérer avec PHP 8.2.

---

## 📋 Étape 1 : Vérifier les Versions

Connectez-vous en SSH et exécutez :

```bash
# Vérifier la version PHP CLI
php -v

# Si vous voyez PHP 7.4, vérifier si PHP 8.2 est installé
php8.2 -v

# Vérifier Composer
composer --version
```

---

## 🔧 Étape 2 : Nettoyer et Régénérer

### Option A : Si `php` pointe déjà vers PHP 8.2

```bash
cd /home/threesixty/yyy/Dossy/legal

# 1. Sauvegarder (optionnel)
cp composer.lock composer.lock.backup

# 2. Supprimer vendor et le lock
rm -rf vendor/
rm -f composer.lock
rm -f bootstrap/cache/*.php

# 3. Nettoyer le cache Composer
composer clear-cache

# 4. Réinstaller avec PHP 8.2
composer install --no-interaction --prefer-dist --optimize-autoloader

# 5. Vérifier
php artisan --version
```

### Option B : Si PHP 7.4 est encore par défaut

```bash
cd /home/threesixty/yyy/Dossy/legal

# 1. Nettoyer
rm -rf vendor/
rm -f composer.lock
rm -f bootstrap/cache/*.php

# 2. Utiliser explicitement PHP 8.2
php8.2 /usr/local/bin/composer install --no-interaction --prefer-dist --optimize-autoloader

# OU si composer est local
php8.2 composer.phar install --no-interaction --prefer-dist --optimize-autoloader

# 3. Vérifier
php8.2 artisan --version
```

---

## 🎯 Étape 3 : Configurer PHP 8.2 par Défaut (Recommandé)

### Via cPanel ou Plesk

1. **cPanel** : 
   - Allez dans **MultiPHP Manager**
   - Sélectionnez votre domaine/sous-domaine
   - Choisissez **PHP 8.2**
   - Cliquez sur **Apply**

2. **Plesk** :
   - Allez dans **PHP Settings**
   - Sélectionnez **PHP 8.2**
   - Cliquez sur **OK**

### Via .htaccess (si supporté)

Ajoutez dans le fichier `.htaccess` à la racine :

```apache
# Force PHP 8.2
AddHandler application/x-httpd-php82 .php
```

### Via CLI (serveur dédié/VPS)

```bash
# Mettre à jour alternatives
sudo update-alternatives --set php /usr/bin/php8.2

# Vérifier
php -v
```

---

## 🚀 Étape 4 : Déployer la Bibliothèque Juridique

Une fois Composer réinstallé avec PHP 8.2 :

```bash
cd /home/threesixty/yyy/Dossy/legal

# 1. Créer le répertoire de stockage
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents
chown -R www-data:www-data storage/app/public/legal_documents

# 2. Créer le lien symbolique
php artisan storage:link

# 3. Exécuter les migrations
php artisan migrate --force

# 4. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 5. Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Vérifier les permissions
chmod -R 755 storage bootstrap/cache
```

---

## ✅ Étape 5 : Vérification Finale

```bash
# Vérifier que les migrations sont passées
php artisan migrate:status | grep legal

# Vérifier le lien symbolique
ls -la public/storage

# Vérifier le répertoire de stockage
ls -la storage/app/public/legal_documents

# Tester l'application
php artisan route:list | grep legal
```

---

## 🌐 Étape 6 : Tester dans le Navigateur

1. **Admin** : https://votre-sous-domaine.com/legal-library
2. **Users** : https://votre-sous-domaine.com/library

---

## 🆘 Problèmes Courants

### Erreur : "Permission denied"
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Erreur : "Class not found"
```bash
composer dump-autoload
php artisan clear-compiled
php artisan optimize
```

### Erreur : "Storage link already exists"
```bash
rm public/storage
php artisan storage:link
```

### Vendor toujours en PHP 7.4
```bash
# Forcer la suppression complète
rm -rf vendor/
rm -rf ~/.composer/cache/
composer clear-cache
composer install
```

---

## 📞 Commandes de Diagnostic

Si ça ne marche toujours pas, exécutez et envoyez-moi les résultats :

```bash
# Version PHP
php -v
php8.2 -v

# Version Composer
composer --version

# Contenu de platform_check.php
cat vendor/composer/platform_check.php | head -30

# Extensions PHP installées
php -m | grep -E 'pdo|mysql|mbstring|xml|curl|zip'

# Permissions
ls -la storage/
ls -la bootstrap/cache/
```

---

## 🎯 Script Automatique Complet

Créez un fichier `fix-php82.sh` :

```bash
#!/bin/bash

echo "🔧 Correction PHP 8.2 pour Dossy Pro..."

# Nettoyer
rm -rf vendor/
rm -f composer.lock
rm -rf bootstrap/cache/*.php

# Installer
composer install --no-interaction --prefer-dist --optimize-autoloader

# Migrations
php artisan migrate --force

# Stockage
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents
php artisan storage:link

# Caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache

echo "✅ Terminé !"
```

Puis :
```bash
chmod +x fix-php82.sh
./fix-php82.sh
```

---

## 📝 Notes Importantes

1. **Ne jamais commiter `vendor/`** dans Git (déjà dans `.gitignore`)
2. **Le `composer.lock` doit être regeneré** sur le serveur avec PHP 8.2
3. **Les permissions sont critiques** pour le stockage des fichiers

---

**Après avoir suivi ces étapes, la bibliothèque juridique devrait fonctionner parfaitement !** 🚀
