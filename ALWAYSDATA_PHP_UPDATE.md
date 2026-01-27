# 🚀 Guide Rapide - Mettre à Jour PHP sur AlwaysData

## 📌 Votre Situation

- **Hébergeur**: AlwaysData
- **Serveur**: mysql-threesixty.alwaysdata.net
- **PHP Actuel**: 7.4.33
- **PHP Requis**: 8.2 ou supérieur

---

## ✅ Solution Simple en 3 Étapes

### **Étape 1 : Se Connecter au Panel AlwaysData**

1. Allez sur : **https://admin.alwaysdata.com/**
2. Connectez-vous avec vos identifiants

---

### **Étape 2 : Changer la Version PHP**

#### **Option A : Via l'Interface Web (Recommandé)**

1. Dans le menu à gauche, cliquez sur **Web** → **Sites**

2. Trouvez votre site (probablement `dossypro.com` ou votre sous-domaine)

3. Cliquez sur le **crayon** (éditer) à côté de votre site

4. Dans la section **Configuration**, cherchez **Version de PHP**

5. Sélectionnez **PHP 8.2** ou **PHP 8.3** dans le menu déroulant

6. Cliquez sur **Soumettre** en bas de page

7. **Attendez 1-2 minutes** que le changement soit appliqué

---

#### **Option B : Via SSH** (Alternative)

Si vous avez accès SSH :

```bash
# Se connecter
ssh votre_compte@ssh-threesixty.alwaysdata.net

# Vérifier les versions PHP disponibles
ls /usr/alwaysdata/php/

# Les versions disponibles sont généralement :
# php-7.4, php-8.0, php-8.1, php-8.2, php-8.3

# Utiliser PHP 8.2 pour les commandes
/usr/alwaysdata/php/php-8.2/bin/php -v
```

---

### **Étape 3 : Vérifier et Migrer**

Une fois PHP mis à jour :

```bash
# 1. Se connecter en SSH
ssh votre_compte@ssh-threesixty.alwaysdata.net

# 2. Aller dans votre projet
cd ~/www/votre-sous-domaine
# OU
cd /home/threesixty/yyy/Dossy/legal

# 3. Vérifier la version PHP
php -v
# Devrait afficher : PHP 8.2.x ou PHP 8.3.x

# 4. Exécuter les migrations
php artisan migrate

# 5. Créer le lien de stockage
php artisan storage:link

# 6. Créer le répertoire pour les PDFs
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents

# 7. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 🔍 **Vérification après Mise à Jour**

### Via SSH :
```bash
cd /home/threesixty/yyy/Dossy/legal
./check-php-version.sh
```

### Via Web :
Créez un fichier `info.php` dans votre répertoire public :

```php
<?php phpinfo(); ?>
```

Puis visitez : `https://votre-sous-domaine.com/info.php`

**⚠️ N'oubliez pas de supprimer ce fichier après !**

---

## 📞 **Support AlwaysData**

Si vous avez des problèmes :

- **Email** : support@alwaysdata.com
- **Chat** : Disponible dans le panel admin
- **Documentation** : https://help.alwaysdata.com/fr/languages/php/
- **Téléphone** : Vérifiez dans votre panel

---

## ⚡ **Commandes Utiles AlwaysData**

### Changer de version PHP temporairement (pour une commande)

```bash
# Utiliser PHP 8.2 pour une commande spécifique
/usr/alwaysdata/php/php-8.2/bin/php artisan migrate

# Créer un alias (temporaire, session courante seulement)
alias php82='/usr/alwaysdata/php/php-8.2/bin/php'
php82 artisan migrate
```

### Créer un alias permanent

```bash
# Ajouter dans ~/.bashrc ou ~/.bash_profile
echo 'alias php82="/usr/alwaysdata/php/php-8.2/bin/php"' >> ~/.bashrc
source ~/.bashrc

# Maintenant vous pouvez utiliser
php82 artisan migrate
```

---

## 🎯 **Checklist Complète**

- [ ] Se connecter au panel AlwaysData
- [ ] Aller dans Web → Sites
- [ ] Sélectionner votre site
- [ ] Changer PHP vers 8.2 ou 8.3
- [ ] Attendre 1-2 minutes
- [ ] Se connecter en SSH
- [ ] Vérifier la version : `php -v`
- [ ] Aller dans le projet
- [ ] Exécuter : `php artisan migrate`
- [ ] Exécuter : `php artisan storage:link`
- [ ] Créer le dossier de stockage
- [ ] Tester : `https://votre-sous-domaine.com/legal-library`

---

## ❓ **Questions Fréquentes AlwaysData**

### Q: Puis-je avoir plusieurs versions PHP ?
**R:** Oui ! AlwaysData permet d'avoir différentes versions PHP pour différents sites/dossiers.

### Q: Est-ce que ça affectera mes autres sites ?
**R:** Non, chaque site peut avoir sa propre version PHP.

### Q: Le changement est-il immédiat ?
**R:** Il faut compter 1-2 minutes pour la propagation.

### Q: Puis-je revenir en arrière ?
**R:** Oui, vous pouvez changer la version à tout moment.

---

## 🚨 **Si Vous Ne Pouvez PAS Mettre à Jour PHP**

Si pour une raison quelconque vous ne pouvez pas changer la version PHP :

**Option 1** : Créer un sous-domaine avec PHP 8.2
- Créez un nouveau site dans AlwaysData
- Configurez-le avec PHP 8.2
- Déployez la bibliothèque juridique là-bas

**Option 2** : Utiliser un dossier avec PHP spécifique
- AlwaysData permet de configurer différents répertoires avec différentes versions PHP
- Contactez le support pour cette configuration

**Option 3** : Downgrade Laravel (NON RECOMMANDÉ)
- Nécessite de refaire tout le projet
- Perte de fonctionnalités
- Problèmes de sécurité

---

## 💡 **Conseil Final**

La mise à jour vers PHP 8.2 sur AlwaysData est **TRÈS SIMPLE** et prend **moins de 5 minutes**.

C'est de loin la meilleure solution ! ✅

---

**Besoin d'aide ? Dites-moi où vous en êtes et je vous guiderai !** 🚀
