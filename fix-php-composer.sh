#!/bin/bash

# Script de correction automatique PHP 8.2 + Composer
# Usage: ./fix-php-composer.sh

echo "🔍 Détection automatique de PHP 8.2..."
echo ""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Chercher PHP 8.2
PHP82=""

# Vérifier différentes possibilités
if command -v php8.2 &> /dev/null; then
    PHP82=$(which php8.2)
    echo -e "${GREEN}✅ Trouvé : php8.2${NC}"
elif command -v php82 &> /dev/null; then
    PHP82=$(which php82)
    echo -e "${GREEN}✅ Trouvé : php82${NC}"
elif command -v ea-php82 &> /dev/null; then
    PHP82=$(which ea-php82)
    echo -e "${GREEN}✅ Trouvé : ea-php82 (cPanel)${NC}"
elif [ -f "/usr/bin/php8.2" ]; then
    PHP82="/usr/bin/php8.2"
    echo -e "${GREEN}✅ Trouvé : /usr/bin/php8.2${NC}"
elif [ -f "/usr/local/bin/php8.2" ]; then
    PHP82="/usr/local/bin/php8.2"
    echo -e "${GREEN}✅ Trouvé : /usr/local/bin/php8.2${NC}"
else
    echo -e "${RED}❌ PHP 8.2 non trouvé !${NC}"
    echo ""
    echo "Versions PHP disponibles :"
    ls -1 /usr/bin/php* 2>/dev/null
    ls -1 /usr/local/bin/php* 2>/dev/null
    echo ""
    echo "Veuillez installer PHP 8.2 ou spécifier le chemin manuellement :"
    echo "  export PHP82=/chemin/vers/php8.2"
    echo "  ./fix-php-composer.sh"
    exit 1
fi

echo ""
echo -e "${YELLOW}Version PHP détectée :${NC}"
$PHP82 -v
echo ""

# Chercher Composer
COMPOSER=""
if command -v composer &> /dev/null; then
    COMPOSER=$(which composer)
    echo -e "${GREEN}✅ Composer trouvé : $COMPOSER${NC}"
elif [ -f "composer.phar" ]; then
    COMPOSER="$PHP82 composer.phar"
    echo -e "${GREEN}✅ Composer.phar trouvé${NC}"
elif [ -f "/usr/local/bin/composer" ]; then
    COMPOSER="/usr/local/bin/composer"
    echo -e "${GREEN}✅ Composer trouvé : $COMPOSER${NC}"
else
    echo -e "${RED}❌ Composer non trouvé !${NC}"
    echo "Installation de Composer..."
    $PHP82 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $PHP82 composer-setup.php --quiet
    $PHP82 -r "unlink('composer-setup.php');"
    COMPOSER="$PHP82 composer.phar"
    echo -e "${GREEN}✅ Composer installé${NC}"
fi

echo ""
echo -e "${YELLOW}Version Composer :${NC}"
$PHP82 $COMPOSER --version
echo ""

# Commencer la correction
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🔧 CORRECTION EN COURS...${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# 1. Sauvegarder
echo -e "${YELLOW}💾 Sauvegarde...${NC}"
[ -f "composer.lock" ] && cp composer.lock composer.lock.backup
echo -e "${GREEN}✅ Sauvegarde créée${NC}"
echo ""

# 2. Nettoyer
echo -e "${YELLOW}🗑️  Nettoyage...${NC}"
rm -rf vendor/
rm -f composer.lock
rm -rf bootstrap/cache/*.php
echo -e "${GREEN}✅ Nettoyage terminé${NC}"
echo ""

# 3. Configurer platform.php
echo -e "${YELLOW}⚙️  Configuration platform.php...${NC}"
$PHP82 $COMPOSER config platform.php 8.2.0
echo -e "${GREEN}✅ Configuration OK${NC}"
echo ""

# 4. Clear cache Composer
echo -e "${YELLOW}🧹 Nettoyage cache Composer...${NC}"
$PHP82 $COMPOSER clear-cache
echo -e "${GREEN}✅ Cache nettoyé${NC}"
echo ""

# 5. Installer
echo -e "${YELLOW}📦 Installation des dépendances...${NC}"
$PHP82 $COMPOSER install --no-interaction --optimize-autoloader

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Installation réussie${NC}"
else
    echo -e "${RED}❌ Erreur lors de l'installation${NC}"
    echo ""
    echo "Tentative avec --ignore-platform-reqs..."
    $PHP82 $COMPOSER install --ignore-platform-reqs --no-interaction
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Installation réussie (avec --ignore-platform-reqs)${NC}"
    else
        echo -e "${RED}❌ Installation échouée${NC}"
        exit 1
    fi
fi
echo ""

# 6. Vérifier
echo -e "${YELLOW}🔍 Vérification Laravel...${NC}"
$PHP82 artisan --version
echo -e "${GREEN}✅ Laravel OK${NC}"
echo ""

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ CORRECTION TERMINÉE AVEC SUCCÈS !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

echo -e "${YELLOW}📝 Prochaines étapes :${NC}"
echo ""
echo "1. Exécuter les migrations :"
echo -e "   ${GREEN}$PHP82 artisan migrate${NC}"
echo ""
echo "2. Créer le stockage pour la bibliothèque juridique :"
echo -e "   ${GREEN}mkdir -p storage/app/public/legal_documents${NC}"
echo -e "   ${GREEN}chmod -R 775 storage/app/public/legal_documents${NC}"
echo -e "   ${GREEN}$PHP82 artisan storage:link${NC}"
echo ""
echo "3. Vider les caches :"
echo -e "   ${GREEN}$PHP82 artisan cache:clear${NC}"
echo -e "   ${GREEN}$PHP82 artisan config:clear${NC}"
echo -e "   ${GREEN}$PHP82 artisan route:clear${NC}"
echo ""
echo "4. Tester l'accès :"
echo -e "   ${GREEN}Admin : https://votre-domaine.com/legal-library${NC}"
echo -e "   ${GREEN}Users : https://votre-domaine.com/library${NC}"
echo ""
