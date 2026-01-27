# ⚡ SOLUTION RAPIDE - Erreur PHP 7.4

## 🚨 Votre Erreur

```
Fatal error: Composer detected issues in your platform: 
Your Composer dependencies require a PHP version ">= 8.2.0". 
You are running 7.4.33.
```

## ✅ LA SOLUTION LA PLUS SIMPLE (5 minutes)

### **Mettre à Jour PHP sur AlwaysData**

1. **Allez sur** : https://admin.alwaysdata.com/
2. **Cliquez sur** : Web → Sites
3. **Trouvez votre site** et cliquez sur le crayon ✏️
4. **Changez** : Version PHP → **8.2** ou **8.3**
5. **Cliquez sur** : Soumettre
6. **Attendez** : 1-2 minutes

### **Ensuite en SSH :**

```bash
# Connectez-vous
ssh votre_compte@ssh-threesixty.alwaysdata.net

# Allez dans votre projet
cd /home/threesixty/yyy/Dossy/legal

# Vérifiez PHP (devrait afficher 8.2 ou 8.3)
php -v

# Lancez les migrations
php artisan migrate

# Créez le stockage
php artisan storage:link
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents

# Videz les caches
php artisan cache:clear

# C'est TOUT ! ✅
```

---

## 🔄 ALTERNATIVE : Utiliser PHP 8.2 Sans Changer la Config

Si vous ne voulez pas changer la configuration du site :

```bash
# Sur AlwaysData, PHP 8.2 est généralement disponible à :
/usr/alwaysdata/php/php-8.2/bin/php

# Utilisez cette commande pour migrer :
/usr/alwaysdata/php/php-8.2/bin/php artisan migrate

# Créez un alias pour simplifier :
alias php82='/usr/alwaysdata/php/php-8.2/bin/php'

# Maintenant utilisez :
php82 artisan migrate
php82 artisan storage:link
```

---

## 📋 COMMANDES COMPLÈTES ÉTAPE PAR ÉTAPE

Copiez-collez ces commandes une par une :

```bash
# 1. Connexion SSH
ssh votre_compte@ssh-threesixty.alwaysdata.net

# 2. Navigation vers le projet
cd /home/threesixty/yyy/Dossy/legal

# 3. Vérifier PHP (si < 8.2, changez via le panel)
php -v

# 4. Si vous devez utiliser PHP 8.2 directement
alias php82='/usr/alwaysdata/php/php-8.2/bin/php'

# 5. Créer le répertoire de stockage
mkdir -p storage/app/public/legal_documents

# 6. Permissions
chmod -R 775 storage/app/public/legal_documents
chmod -R 775 storage/logs

# 7. Lien symbolique
php artisan storage:link
# OU si PHP 7.4 : php82 artisan storage:link

# 8. Migrations
php artisan migrate
# OU si PHP 7.4 : php82 artisan migrate

# 9. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 10. Vérifier que ça marche
php artisan migrate:status
ls -la storage/app/public/legal_documents
```

---

## 🎯 TEST FINAL

Visitez :
- **Admin** : `https://votre-sous-domaine.com/legal-library`
- **Users** : `https://votre-sous-domaine.com/library`

---

## 🆘 EN CAS DE PROBLÈME

### Erreur : "php: command not found"
```bash
# Utilisez le chemin complet
/usr/alwaysdata/php/php-8.2/bin/php artisan migrate
```

### Erreur : "Permission denied"
```bash
# Ajoutez sudo si vous avez les droits
sudo chmod -R 775 storage/app/public/legal_documents

# OU contactez le support AlwaysData
```

### Erreur : "Class not found"
```bash
# Régénérez l'autoload
composer dump-autoload
php artisan clear-compiled
php artisan config:clear
```

---

## 📞 AIDE RAPIDE

**Si ça ne marche toujours pas, donnez-moi :**

1. Le résultat de : `php -v`
2. Le résultat de : `pwd` (votre répertoire actuel)
3. L'erreur exacte que vous voyez

Et je vous guiderai étape par étape ! 🚀

---

## ⚡ RÉSUMÉ EN 30 SECONDES

1. **Panel AlwaysData** → Web → Sites → Votre site → PHP 8.2 ✅
2. **SSH** → `cd /home/threesixty/yyy/Dossy/legal`
3. **Commande** → `php artisan migrate`
4. **Commande** → `php artisan storage:link`
5. **Fini !** 🎉

C'est tout ! Simple et rapide.
