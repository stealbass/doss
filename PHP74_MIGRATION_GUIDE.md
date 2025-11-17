# 🔧 Guide de Migration PHP 7.4 - Bibliothèque Juridique

## ⚠️ Problème Détecté

Votre serveur utilise **PHP 7.4.33** mais le projet Dossy Pro est configuré pour Laravel 11 qui nécessite **PHP 8.2+**.

## ✅ Solutions

Vous avez **2 options** :

---

## 🎯 **OPTION 1 : Mettre à Jour PHP (RECOMMANDÉ)**

### Pourquoi ?
- Laravel 11 nécessite PHP 8.2+
- Meilleure performance et sécurité
- Toutes les fonctionnalités modernes disponibles

### Comment faire ?

**Sur cPanel :**
1. Allez dans **cPanel → MultiPHP Manager**
2. Sélectionnez votre domaine/sous-domaine
3. Changez la version PHP à **8.2** ou **8.3**
4. Cliquez sur "Apply"

**Via SSH (si accès root) :**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl

# CentOS/AlmaLinux
sudo yum install php82 php82-php-fpm php82-php-mysqlnd php82-php-mbstring
```

**Via Plesk :**
1. Allez dans **Domaines → votre-domaine**
2. Cliquez sur **PHP Settings**
3. Sélectionnez **PHP 8.2** ou supérieur
4. Enregistrez

Après la mise à jour :
```bash
cd /home/threesixty/yyy/Dossy/legal
php artisan migrate
php artisan storage:link
```

---

## 🔄 **OPTION 2 : Adapter le Code pour PHP 7.4**

Si vous ne pouvez pas mettre à jour PHP, voici les fichiers modifiés pour PHP 7.4 :

### ⚠️ ATTENTION
Le projet Dossy Pro utilise Laravel 11 qui **N'EST PAS COMPATIBLE** avec PHP 7.4.
Vous devrez downgrade tout le projet Laravel, ce qui n'est **PAS RECOMMANDÉ**.

### Si vous voulez quand même continuer :

#### Étape 1 : Remplacer les fichiers de migration

**Supprimez les anciennes migrations :**
```bash
cd /home/threesixty/yyy/Dossy/legal
rm database/migrations/2024_11_15_000001_create_legal_categories_table.php
rm database/migrations/2024_11_15_000002_create_legal_documents_table.php
rm database/migrations/2024_11_15_000003_add_legal_library_permissions.php
```

**Renommez les nouvelles migrations PHP 7.4 :**
```bash
mv database/migrations/2024_11_15_000001_create_legal_categories_table_php74.php \
   database/migrations/2024_11_15_000001_create_legal_categories_table.php

mv database/migrations/2024_11_15_000002_create_legal_documents_table_php74.php \
   database/migrations/2024_11_15_000002_create_legal_documents_table.php

mv database/migrations/2024_11_15_000003_add_legal_library_permissions_php74.php \
   database/migrations/2024_11_15_000003_add_legal_library_permissions.php
```

#### Étape 2 : Problème avec vendor/composer

Le vrai problème est dans **vendor/composer/platform_check.php**. 

**Solution temporaire (DANGEREUX) :**
```bash
# Désactiver temporairement la vérification de plateforme
cd /home/threesixty/yyy/Dossy/legal
composer config platform-check false
```

**OU modifier composer.json :**
```json
{
    "config": {
        "platform-check": false
    }
}
```

Puis :
```bash
composer dump-autoload
```

#### Étape 3 : Problèmes potentiels

Même avec ces modifications, vous aurez des problèmes car :

1. **Laravel 11 nécessite PHP 8.2+**
2. **Les dépendances Composer nécessitent PHP 8.2+**
3. **Beaucoup de fonctionnalités ne fonctionneront pas**

---

## 🎯 **MA RECOMMANDATION FORTE**

### ✅ Mettre à Jour PHP vers 8.2 ou 8.3

**Pourquoi c'est mieux :**
- ✅ Pas de problèmes de compatibilité
- ✅ Meilleures performances (2-3x plus rapide)
- ✅ Meilleures sécurité
- ✅ Support à long terme
- ✅ Toutes les fonctionnalités fonctionnent
- ✅ Pas de modifications du code nécessaires

**Comment vérifier votre version PHP actuelle :**
```bash
php -v
```

**Si vous avez plusieurs versions PHP installées :**
```bash
# Lister les versions disponibles
ls /usr/bin/php*

# Utiliser une version spécifique
/usr/bin/php8.2 -v
/usr/bin/php8.2 artisan migrate
```

---

## 📋 **Procédure Complète après Mise à Jour PHP**

Une fois PHP 8.2+ installé :

```bash
# 1. Vérifier la version
php -v

# 2. Aller dans le projet
cd /home/threesixty/yyy/Dossy/legal

# 3. Réinstaller les dépendances (si nécessaire)
composer install --no-dev

# 4. Créer le répertoire de stockage
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents

# 5. Créer le lien symbolique
php artisan storage:link

# 6. Exécuter les migrations
php artisan migrate

# 7. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 8. Optimiser
php artisan config:cache
php artisan route:cache
```

---

## 🆘 **Aide Spécifique à Votre Hébergeur**

### Chez AlwaysData (votre hébergeur actuel)

D'après votre configuration (mysql-threesixty.alwaysdata.net), vous êtes chez **AlwaysData**.

**Pour changer la version PHP chez AlwaysData :**

1. Connectez-vous à votre **panel AlwaysData**
2. Allez dans **Web → Sites**
3. Cliquez sur votre site
4. Dans **Configuration**, changez la version PHP à **8.2** ou **8.3**
5. Enregistrez

**OU via SSH :**
```bash
# AlwaysData permet de choisir la version PHP par site
# Contactez le support AlwaysData pour activer PHP 8.2
```

---

## ❓ **Questions Fréquentes**

### Q: Est-ce que mettre à jour PHP va casser mon site ?
**R:** Non, si votre projet est Laravel 11, il est conçu pour PHP 8.2+. C'est PHP 7.4 qui pose problème.

### Q: Puis-je avoir plusieurs versions PHP ?
**R:** Oui ! Vous pouvez avoir PHP 7.4 pour d'autres sites et PHP 8.2 pour ce projet.

### Q: Combien de temps prend la mise à jour ?
**R:** Généralement 5-10 minutes via le panel d'hébergement.

---

## 📞 **Besoin d'Aide ?**

Si vous avez besoin d'aide pour :
- Mettre à jour PHP chez AlwaysData
- Configurer PHP 8.2
- Résoudre des erreurs après migration

**Dites-moi et je vous guiderai étape par étape !**

---

## 🎯 **Résumé : Que Faire Maintenant ?**

1. **MEILLEURE OPTION** : Mettez à jour PHP vers 8.2 ou 8.3
2. Contactez le support AlwaysData si besoin
3. Une fois PHP mis à jour, exécutez les migrations
4. Testez la fonctionnalité

**La mise à jour de PHP est BEAUCOUP plus simple que d'adapter tout le code !** ✅
