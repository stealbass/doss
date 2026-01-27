#!/bin/bash

# Script de mise à jour vers PHP 8.2
# Usage: ./UPDATE_TO_PHP82.sh

set -e

echo "🚀 Mise à jour du projet Dossy Pro vers PHP 8.2..."
echo ""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# 1. Vérifier la version PHP
echo -e "${YELLOW}📋 Vérification de la version PHP...${NC}"
php -v
echo ""

# 2. Sauvegarder l'ancien composer.lock
echo -e "${YELLOW}💾 Sauvegarde de composer.lock...${NC}"
if [ -f "composer.lock" ]; then
    cp composer.lock composer.lock.backup
    echo -e "${GREEN}✅ Sauvegarde créée${NC}"
else
    echo -e "${YELLOW}⚠️  Aucun composer.lock trouvé${NC}"
fi
echo ""

# 3. Supprimer vendor et composer.lock
echo -e "${YELLOW}🗑️  Nettoyage des anciennes dépendances...${NC}"
rm -rf vendor/
rm -f composer.lock
rm -f vendor/composer/platform_check.php
echo -e "${GREEN}✅ Nettoyage terminé${NC}"
echo ""

# 4. Mettre à jour Composer lui-même
echo -e "${YELLOW}⬆️  Mise à jour de Composer...${NC}"
composer self-update
composer --version
echo ""

# 5. Modifier composer.json pour forcer PHP 8.2
echo -e "${YELLOW}📝 Configuration pour PHP 8.2...${NC}"
# Ceci sera fait manuellement si nécessaire
echo ""

# 6. Installer les dépendances avec PHP 8.2
echo -e "${YELLOW}📦 Installation des dépendances...${NC}"
composer install --no-interaction --prefer-dist --optimize-autoloader
echo ""

# 7. Vérifier que tout fonctionne
echo -e "${YELLOW}🔍 Vérification...${NC}"
php artisan --version
echo ""

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ MISE À JOUR TERMINÉE !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Prochaines étapes :${NC}"
echo "1. Exécuter les migrations : php artisan migrate"
echo "2. Déployer la bibliothèque juridique"
echo ""
