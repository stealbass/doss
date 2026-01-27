#!/bin/bash

# Script de régénération Composer pour PHP 8.2
# Usage: ./regenerate-composer-php82.sh

set -e

echo "╔═══════════════════════════════════════════════════════╗"
echo "║     🔧 Régénération Composer pour PHP 8.2           ║"
echo "╚═══════════════════════════════════════════════════════╝"
echo ""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# 1. Vérifier la version PHP
echo -e "${YELLOW}📋 Vérification de la version PHP...${NC}"
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "Version PHP détectée : $PHP_VERSION"

if [[ ! "$PHP_VERSION" =~ ^8\.2 ]]; then
    echo -e "${RED}❌ ERREUR: PHP 8.2 requis, version actuelle: $PHP_VERSION${NC}"
    echo ""
    echo "Solutions possibles :"
    echo "1. Utilisez: php8.2 regenerate-composer-php82.sh"
    echo "2. Ou configurez PHP 8.2 par défaut via cPanel/Plesk"
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ PHP 8.2+ détecté${NC}"
echo ""

# 2. Sauvegarder les fichiers importants
echo -e "${YELLOW}💾 Sauvegarde des fichiers...${NC}"
if [ -f "composer.lock" ]; then
    cp composer.lock composer.lock.php74.backup
    echo -e "${GREEN}✅ composer.lock sauvegardé${NC}"
fi
echo ""

# 3. Nettoyer complètement
echo -e "${YELLOW}🧹 Nettoyage complet...${NC}"
rm -rf vendor/
rm -f composer.lock
rm -rf bootstrap/cache/*.php
echo -e "${GREEN}✅ Nettoyage terminé${NC}"
echo ""

# 4. Nettoyer le cache Composer
echo -e "${YELLOW}🗑️  Nettoyage du cache Composer...${NC}"
composer clear-cache
echo -e "${GREEN}✅ Cache Composer nettoyé${NC}"
echo ""

# 5. Réinstaller avec PHP 8.2
echo -e "${YELLOW}📦 Installation des dépendances avec PHP 8.2...${NC}"
echo "Cela peut prendre plusieurs minutes..."
echo ""

composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev

echo ""
echo -e "${GREEN}✅ Dépendances installées${NC}"
echo ""

# 6. Vérifier l'installation
echo -e "${YELLOW}🔍 Vérification de l'installation...${NC}"

# Vérifier platform_check.php
if [ -f "vendor/composer/platform_check.php" ]; then
    PLATFORM_PHP=$(grep "PHP_VERSION_ID" vendor/composer/platform_check.php | head -1)
    echo "Platform check: $PLATFORM_PHP"
    echo -e "${GREEN}✅ vendor/composer/platform_check.php généré${NC}"
else
    echo -e "${RED}❌ Erreur: platform_check.php non trouvé${NC}"
    exit 1
fi

# Vérifier Laravel
php artisan --version
echo ""

# 7. Optimiser
echo -e "${YELLOW}⚡ Optimisation...${NC}"
composer dump-autoload --optimize
echo -e "${GREEN}✅ Autoload optimisé${NC}"
echo ""

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ RÉGÉNÉRATION COMPOSER TERMINÉE !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}📝 Prochaines étapes :${NC}"
echo "1. Exécuter les migrations : php artisan migrate"
echo "2. Configurer le stockage : php artisan storage:link"
echo "3. Déployer la bibliothèque juridique"
echo ""
echo -e "${GREEN}Le projet est maintenant compatible avec PHP 8.2 ! 🚀${NC}"
echo ""
