# 🔧 FIX PERMISSIONS COMPOSER

## ❌ ERREUR DÉTECTÉE

```
/home/threesixty/yyy/Dossy/vendor/php-http does not exist and could not be created
```

**Cause**: Permissions insuffisantes sur le dossier `vendor`

---

## ✅ SOLUTIONS

### Solution 1: Fix Permissions (RECOMMANDÉ)

```bash
# 1. Aller dans le dossier du projet
cd ~/yyy/Dossy

# 2. Vérifier le propriétaire actuel
ls -la | grep vendor

# 3. Prendre possession du dossier vendor
sudo chown -R threesixty_genspark:threesixty_genspark vendor

# 4. Donner les bonnes permissions
chmod -R 755 vendor

# 5. Réessayer l'installation
composer require smalot/pdfparser
```

### Solution 2: Recréer vendor depuis zéro

```bash
cd ~/yyy/Dossy

# 1. Backup composer.lock
cp composer.lock composer.lock.backup

# 2. Supprimer vendor complètement
rm -rf vendor

# 3. Créer vendor avec bonnes permissions
mkdir -p vendor
chmod 755 vendor

# 4. Réinstaller tout
composer install

# 5. Installer pdfparser
composer require smalot/pdfparser
```

### Solution 3: Utiliser composer sans sudo

```bash
cd ~/yyy/Dossy

# 1. Vérifier que vous N'utilisez PAS sudo
whoami  # Devrait afficher: threesixty_genspark

# 2. Si vous étiez root, sortir et recommencer
exit  # Si nécessaire
ssh threesixty_genspark@ssh1.alwaysdata.net

# 3. Installer en tant qu'utilisateur normal
cd ~/yyy/Dossy
composer require smalot/pdfparser
```

### Solution 4: Fix permissions récursif complet

```bash
cd ~/yyy/Dossy

# 1. Fix ownership de tout le projet
chown -R threesixty_genspark:threesixty_genspark .

# 2. Fix permissions dossiers
find . -type d -exec chmod 755 {} \;

# 3. Fix permissions fichiers
find . -type f -exec chmod 644 {} \;

# 4. Permissions spéciales pour storage et bootstrap
chmod -R 775 storage bootstrap/cache

# 5. Réessayer
composer require smalot/pdfparser
```

---

## 🧪 DIAGNOSTIC

### Vérifier permissions actuelles

```bash
cd ~/yyy/Dossy

# Afficher permissions vendor
ls -la | grep vendor

# Afficher propriétaire
stat vendor | grep "Access:"

# Afficher l'utilisateur actuel
whoami
id
```

**Output attendu:**
```
drwxr-xr-x  threesixty_genspark  threesixty_genspark  vendor/
User: threesixty_genspark
```

### Vérifier espace disque

```bash
# Vérifier quota/espace disponible
df -h ~/yyy/Dossy

# Vérifier taille vendor
du -sh vendor
```

---

## 🚀 PROCÉDURE COMPLÈTE RECOMMANDÉE

```bash
#!/bin/bash

echo "🔧 Fix permissions Composer + Installation PdfParser"
echo ""

# 1. Aller dans le projet
cd ~/yyy/Dossy || exit 1
echo "✅ Dans le dossier: $(pwd)"
echo ""

# 2. Vérifier utilisateur actuel
echo "👤 Utilisateur: $(whoami)"
echo ""

# 3. Sauvegarder composer.lock
if [ -f composer.lock ]; then
    cp composer.lock composer.lock.backup.$(date +%Y%m%d_%H%M%S)
    echo "✅ Backup composer.lock créé"
fi
echo ""

# 4. Fix ownership complet
echo "📝 Fix ownership..."
chown -R threesixty_genspark:threesixty_genspark .
echo "✅ Ownership fixé"
echo ""

# 5. Fix permissions
echo "📝 Fix permissions..."
find . -type d -exec chmod 755 {} \; 2>/dev/null
find . -type f -exec chmod 644 {} \; 2>/dev/null
chmod -R 775 storage bootstrap/cache 2>/dev/null
echo "✅ Permissions fixées"
echo ""

# 6. Supprimer vendor si corrompu
if [ -d vendor ]; then
    echo "🗑️ Suppression ancien vendor..."
    rm -rf vendor
    echo "✅ Ancien vendor supprimé"
fi
echo ""

# 7. Réinstaller dépendances
echo "📦 Installation dépendances..."
composer install --no-interaction
echo "✅ Dépendances installées"
echo ""

# 8. Installer PdfParser
echo "📦 Installation PdfParser..."
composer require smalot/pdfparser --no-interaction
echo "✅ PdfParser installé"
echo ""

# 9. Vérifier installation
echo "🔍 Vérification..."
composer show | grep pdfparser
echo ""

# 10. Test PdfParser
echo "🧪 Test PdfParser..."
php -r "require 'vendor/autoload.php'; use Smalot\PdfParser\Parser; \$p = new Parser(); echo '✅ PdfParser fonctionne!\n';"
echo ""

echo "=========================================="
echo "✅ INSTALLATION TERMINÉE AVEC SUCCÈS"
echo "=========================================="
echo ""
echo "Prochaines étapes:"
echo "1. Tester upload document via app mobile"
echo "2. Vérifier logs: tail -f storage/logs/laravel.log"
echo "3. Appliquer patch SSL si erreur Pinecone"
```

**Copier ce script dans un fichier:**

```bash
# 1. Créer le script
nano ~/fix_composer_install_pdfparser.sh

# 2. Copier le contenu ci-dessus

# 3. Rendre exécutable
chmod +x ~/fix_composer_install_pdfparser.sh

# 4. Exécuter
~/fix_composer_install_pdfparser.sh
```

---

## ⚠️ SI ERREUR PERSISTE

### Vérifier quota AlwaysData

```bash
# Vérifier espace disque utilisé
du -sh ~/yyy/Dossy

# Vérifier quota
quota -s
```

Si quota dépassé:
1. Supprimer fichiers temporaires:
   ```bash
   rm -rf ~/yyy/Dossy/storage/logs/*.log.old
   rm -rf ~/yyy/Dossy/storage/framework/cache/*
   rm -rf ~/yyy/Dossy/storage/framework/sessions/*
   rm -rf ~/yyy/Dossy/storage/framework/views/*
   ```

2. Augmenter quota via panel AlwaysData

### Vérifier SELinux/AppArmor (rare)

```bash
# Vérifier si SELinux actif
getenforce 2>/dev/null

# Si "Enforcing", désactiver temporairement
sudo setenforce 0

# Réessayer installation
composer require smalot/pdfparser
```

---

## 🎯 SOLUTION RAPIDE (90% des cas)

```bash
cd ~/yyy/Dossy
rm -rf vendor
composer install
composer require smalot/pdfparser
```

Si ça échoue encore:

```bash
cd ~/yyy/Dossy
chown -R $(whoami):$(whoami) .
chmod -R 755 .
rm -rf vendor
composer install
composer require smalot/pdfparser
```

---

## ✅ VÉRIFICATION FINALE

Après installation réussie:

```bash
# 1. Vérifier PdfParser installé
composer show | grep pdfparser
# ✅ Attendu: smalot/pdfparser v2.12.3

# 2. Tester en ligne de commande
php -r "require 'vendor/autoload.php'; use Smalot\PdfParser\Parser; echo 'OK';"
# ✅ Attendu: OK

# 3. Vérifier permissions finales
ls -la vendor | head -5
# ✅ Attendu: threesixty_genspark propriétaire

# 4. Lancer test complet
php test_pdfparser_after_php_update.php
```

---

## 📊 RÉSUMÉ ERREURS COMPOSER

| Erreur | Cause | Solution |
|--------|-------|----------|
| "could not be created" | Permissions | `chown -R` + `chmod 755` |
| "Permission denied" | Sudo/Root | Ne pas utiliser sudo |
| "Disk quota exceeded" | Espace disque | Nettoyer ou augmenter quota |
| "corrupted" | Vendor corrompu | `rm -rf vendor` |

---

**CONSEIL**: Si AlwaysData, essayez via le File Manager web pour voir les permissions réelles.
