# ⚡ DÉMARRAGE RAPIDE - Correction PHP 8.2

## 🎯 VOTRE PROBLÈME

```
Fatal error: Composer detected issues in your platform: 
Your Composer dependencies require a PHP version ">= 8.2.0". 
You are running 7.4.33.
```

---

## ✅ SOLUTION EN 3 ÉTAPES

### **Étape 1 : Télécharger le script de correction**

Le script `fix-php-composer.sh` est déjà créé. Uploadez-le sur votre serveur dans le répertoire du projet.

### **Étape 2 : Exécuter le script**

```bash
# SSH vers votre serveur
ssh threesixty@votre-domaine.com

# Aller au projet
cd /home/threesixty/yyy/Dossy/legal

# Rendre le script exécutable
chmod +x fix-php-composer.sh

# Exécuter
./fix-php-composer.sh
```

Le script va automatiquement :
- ✅ Détecter PHP 8.2 sur votre serveur
- ✅ Nettoyer les anciennes dépendances
- ✅ Configurer Composer pour PHP 8.2
- ✅ Réinstaller toutes les dépendances

### **Étape 3 : Déployer la bibliothèque juridique**

```bash
# Après succès du script ci-dessus, exécutez :

# 1. Migrations
php artisan migrate

# 2. Créer le stockage
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents
php artisan storage:link

# 3. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

**C'est tout ! ✨**

---

## 🔧 SOLUTION MANUELLE (Si le script ne fonctionne pas)

### **1. Identifier PHP 8.2**

```bash
# Chercher PHP 8.2
which php8.2
# OU
which ea-php82
# OU
ls /usr/bin/php*
```

### **2. Nettoyer et Réinstaller**

```bash
cd /home/threesixty/yyy/Dossy/legal

# Supprimer l'ancien
rm -rf vendor/
rm -f composer.lock

# Configurer pour PHP 8.2
composer config platform.php 8.2.0

# Installer avec PHP 8.2 (adaptez le chemin)
/usr/bin/php8.2 /usr/local/bin/composer install

# OU si cPanel
ea-php82 /usr/local/bin/composer install
```

### **3. Vérifier**

```bash
php artisan --version
```

Si ça affiche la version de Laravel, c'est bon ! ✅

---

## 📋 COMMANDES POUR CPANEL

Si vous utilisez cPanel avec ea-php82 :

```bash
# 1. Nettoyer
cd /home/threesixty/yyy/Dossy/legal
rm -rf vendor/ composer.lock

# 2. Installer avec ea-php82
ea-php82 /usr/local/bin/composer config platform.php 8.2.0
ea-php82 /usr/local/bin/composer install

# 3. Migrer
ea-php82 artisan migrate

# 4. Stockage
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents
ea-php82 artisan storage:link

# 5. Caches
ea-php82 artisan cache:clear
ea-php82 artisan config:clear
```

---

## 🆘 SI VOUS AVEZ DES ERREURS

### **Erreur : "php8.2: command not found"**

```bash
# Chercher où est PHP 8.2
find /usr -name "php8.2" 2>/dev/null
find /usr -name "ea-php82" 2>/dev/null

# Utiliser le chemin complet trouvé
/chemin/complet/vers/php8.2 /usr/local/bin/composer install
```

### **Erreur : "composer: command not found"**

```bash
# Télécharger composer localement
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Utiliser composer.phar
php8.2 composer.phar install
```

### **Erreur : "Cannot allocate memory"**

```bash
# Augmenter la mémoire temporairement
php -d memory_limit=512M /usr/local/bin/composer install
```

---

## 📧 BESOIN D'AIDE ?

Exécutez ces commandes et envoyez-moi le résultat :

```bash
# Version PHP
php -v

# PHP disponibles
ls -la /usr/bin/php*

# Composer version
composer --version

# Chemin projet
pwd

# Contenu composer.json
cat composer.json | grep -A5 '"require"'
```

---

## ✅ VÉRIFICATION FINALE

Après tout ça, vérifiez que tout fonctionne :

```bash
# Laravel doit afficher sa version
php artisan --version

# Migrations doivent être OK
php artisan migrate:status

# Accès web
curl -I https://votre-domaine.com/legal-library
```

**URLs à tester :**
- Admin : `https://votre-domaine.com/legal-library`
- Users : `https://votre-domaine.com/library`

---

## 🚀 VOUS ÊTES PRÊT !

Une fois ces étapes complétées, votre bibliothèque juridique sera opérationnelle ! 🎉
