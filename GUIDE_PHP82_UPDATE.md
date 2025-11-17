# 🔧 Guide de Mise à Jour vers PHP 8.2

## 🎯 Problème Identifié

Votre serveur a PHP 8.2 mais Composer utilise encore l'ancienne configuration PHP 7.4.

**Erreur :**
```
Fatal error: Composer detected issues in your platform: 
Your Composer dependencies require a PHP version ">= 8.2.0". 
You are running 7.4.33.
```

---

## ✅ SOLUTION RAPIDE (Recommandée)

### **Étape 1 : Se connecter en SSH**

```bash
ssh votre_user@votre-sous-domaine.com
cd /home/threesixty/yyy/Dossy/legal
```

### **Étape 2 : Vérifier PHP**

```bash
# Vérifier la version PHP CLI
php -v

# Si c'est PHP 7.4, spécifier PHP 8.2
which php8.2
# ou
which php82
```

### **Étape 3 : Option A - Utiliser PHP 8.2 directement**

Si PHP 8.2 est disponible sous un autre nom :

```bash
# Créer un alias temporaire
alias php='/usr/bin/php8.2'  # Ajustez le chemin

# Ou utiliser le chemin complet
/usr/bin/php8.2 -v
```

### **Étape 4 : Nettoyer et Réinstaller**

```bash
# 1. Supprimer les anciennes dépendances
rm -rf vendor/
rm -f composer.lock

# 2. Réinstaller avec PHP 8.2
php composer.phar install
# OU si composer est global
composer install

# 3. Si erreur persiste, forcer la version PHP
composer config platform.php 8.2.0
composer install
```

---

## 📋 SOLUTION DÉTAILLÉE (Étape par Étape)

### **1. Identifier le PHP utilisé**

```bash
# Version CLI
php -v

# Versions disponibles
ls /usr/bin/php*

# Exemples possibles :
# /usr/bin/php7.4
# /usr/bin/php8.0
# /usr/bin/php8.1
# /usr/bin/php8.2
```

### **2. Configurer PHP 8.2 comme défaut**

**Option A - Via alternatives (Debian/Ubuntu) :**
```bash
sudo update-alternatives --set php /usr/bin/php8.2
php -v
```

**Option B - Via .bashrc :**
```bash
echo 'alias php="/usr/bin/php8.2"' >> ~/.bashrc
source ~/.bashrc
php -v
```

**Option C - Via variable d'environnement :**
```bash
export PATH="/usr/bin/php8.2:$PATH"
```

### **3. Mettre à jour Composer**

```bash
# Mise à jour de Composer lui-même
composer self-update

# Vérifier
composer --version
```

### **4. Nettoyer le projet**

```bash
cd /home/threesixty/yyy/Dossy/legal

# Supprimer cache et dépendances
rm -rf vendor/
rm -f composer.lock
rm -rf bootstrap/cache/*.php

# Nettoyer le cache Composer
composer clear-cache
```

### **5. Configurer platform.php**

```bash
# Forcer Composer à utiliser PHP 8.2
composer config platform.php 8.2.0

# Vérifier composer.json
cat composer.json | grep -A5 "config"
```

### **6. Réinstaller les dépendances**

```bash
# Installation complète
composer install --no-interaction --optimize-autoloader

# Si erreur, mode debug
composer install -vvv
```

### **7. Vérifier Laravel**

```bash
# Version Laravel
php artisan --version

# Liste des commandes
php artisan list
```

---

## 🚨 SI ERREURS PERSISTENT

### **Erreur : "php: command not found"**

```bash
# Utiliser le chemin complet
/usr/bin/php8.2 /usr/local/bin/composer install
```

### **Erreur : "Platform requirements not satisfied"**

```bash
# Ignorer les vérifications de plateforme (temporaire)
composer install --ignore-platform-reqs

# Puis configurer correctement
composer config platform.php 8.2.0
composer update nothing
```

### **Erreur : "Cannot use Composer 2 with PHP 7.4"**

```bash
# Vérifier quelle version PHP Composer utilise
composer diagnose

# Forcer PHP 8.2 pour Composer
/usr/bin/php8.2 $(which composer) install
```

---

## 📝 COMMANDES SPÉCIFIQUES POUR CPANEL

Si vous utilisez cPanel :

### **1. Via Terminal cPanel**

```bash
# cPanel utilise souvent ea-php82
ea-php82 -v

# Utiliser ea-php82 pour Composer
ea-php82 /usr/local/bin/composer install
```

### **2. Via MultiPHP Manager**

1. Connectez-vous à cPanel
2. Cherchez "MultiPHP Manager"
3. Sélectionnez votre domaine
4. Changez vers PHP 8.2
5. Appliquez

### **3. Via .htaccess (pour Apache)**

Ajoutez dans votre `.htaccess` :
```apache
# Force PHP 8.2
AddHandler application/x-httpd-ea-php82 .php
```

---

## 🎯 SOLUTION RAPIDE POUR VOTRE CAS

**Pour votre serveur `/home/threesixty/yyy/Dossy/legal` :**

```bash
# 1. Se connecter
ssh threesixty@votre-domaine.com

# 2. Aller au projet
cd /home/threesixty/yyy/Dossy/legal

# 3. Vérifier PHP disponible
ls -la /usr/bin/php* | grep php8

# 4. Utiliser PHP 8.2 (adaptez le chemin)
# Si c'est ea-php82 (cPanel)
ea-php82 -v
ea-php82 /usr/local/bin/composer install

# OU si c'est php8.2
/usr/bin/php8.2 -v
/usr/bin/php8.2 /usr/local/bin/composer install

# 5. Configurer platform
/usr/bin/php8.2 /usr/local/bin/composer config platform.php 8.2.0

# 6. Nettoyer et installer
rm -rf vendor/ composer.lock
/usr/bin/php8.2 /usr/local/bin/composer install

# 7. Exécuter les migrations
/usr/bin/php8.2 artisan migrate
```

---

## 📦 APRÈS LA MISE À JOUR

Une fois Composer installé avec PHP 8.2 :

```bash
# 1. Vérifier
php artisan --version

# 2. Exécuter les migrations de la bibliothèque juridique
php artisan migrate

# 3. Créer le stockage
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents
php artisan storage:link

# 4. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🆘 BESOIN D'AIDE ?

**Envoyez-moi :**

1. **Résultat de :**
```bash
php -v
ls -la /usr/bin/php*
which composer
composer --version
```

2. **Type d'hébergement :**
- cPanel ?
- Plesk ?
- VPS/Serveur dédié ?

3. **Chemin exact du projet**

Et je vous guiderai précisément ! 🚀

---

## ✅ CHECKLIST

- [ ] Vérifier version PHP CLI : `php -v`
- [ ] Identifier PHP 8.2 : `which php8.2`
- [ ] Nettoyer : `rm -rf vendor/ composer.lock`
- [ ] Configurer : `composer config platform.php 8.2.0`
- [ ] Installer : `composer install`
- [ ] Tester : `php artisan --version`
- [ ] Migrer : `php artisan migrate`
- [ ] Créer stockage : `mkdir -p storage/app/public/legal_documents`
- [ ] Lien symbolique : `php artisan storage:link`
