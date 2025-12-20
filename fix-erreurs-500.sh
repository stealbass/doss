#!/bin/bash

echo "════════════════════════════════════════════════════════════════"
echo "      🔧 CORRECTION AUTOMATIQUE - Erreurs 500"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}ÉTAPE 1/5 : Vérification de la branche Git${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

CURRENT_BRANCH=$(git branch --show-current)
echo -e "Branche actuelle : ${YELLOW}$CURRENT_BRANCH${NC}"

if [ "$CURRENT_BRANCH" != "main" ]; then
    echo -e "${YELLOW}⚠️  Vous n'êtes pas sur la branche 'main'${NC}"
    echo -e "Basculer vers 'main' ? (y/n)"
    read -r response
    if [ "$response" = "y" ]; then
        git checkout main
        echo -e "${GREEN}✅ Basculé vers 'main'${NC}"
    fi
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}ÉTAPE 2/5 : Récupération des dernières modifications${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

echo "Exécution de : git pull origin main"
git pull origin main

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Modifications récupérées avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors du git pull${NC}"
    echo -e "${YELLOW}Vérifiez votre connexion Git et réessayez${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}ÉTAPE 3/5 : Installation/Mise à jour des dépendances${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

if [ -f "composer.json" ]; then
    echo "Exécution de : composer install --no-dev --optimize-autoloader"
    composer install --no-dev --optimize-autoloader
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Dépendances installées${NC}"
    else
        echo -e "${YELLOW}⚠️  Erreur composer (non bloquant)${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  composer.json non trouvé${NC}"
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}ÉTAPE 4/5 : Nettoyage de TOUS les caches${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

echo "1. Nettoyage du cache..."
php artisan cache:clear
echo -e "${GREEN}✅ Cache vidé${NC}"

echo "2. Nettoyage des routes..."
php artisan route:clear
echo -e "${GREEN}✅ Routes vidées${NC}"

echo "3. Nettoyage de la configuration..."
php artisan config:clear
echo -e "${GREEN}✅ Configuration vidée${NC}"

echo "4. Nettoyage des vues..."
php artisan view:clear
echo -e "${GREEN}✅ Vues vidées${NC}"

echo "5. Nettoyage optimisé..."
php artisan optimize:clear
echo -e "${GREEN}✅ Optimisation vidée${NC}"

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}ÉTAPE 5/5 : Vérification des routes${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

echo "Vérification des routes mobile..."
php artisan route:list | grep -E "mobile-dashboard|mobile-analytics|document-templates|fiscal-resources|calculators"

echo ""
echo "════════════════════════════════════════════════════════════════"
echo -e "${GREEN}      ✅ CORRECTION TERMINÉE${NC}"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Maintenant, testez les URLs suivantes dans votre navigateur :"
echo ""
echo "  🔗 https://dossypro.com/mobile-dashboard"
echo "  🔗 https://dossypro.com/mobile-analytics"
echo "  🔗 https://dossypro.com/document-templates"
echo "  🔗 https://dossypro.com/fiscal-resources"
echo "  🔗 https://dossypro.com/calculators"
echo "  🔗 https://dossypro.com/legal-library/category/create"
echo ""
echo -e "${YELLOW}⚠️  Si vous avez encore des erreurs 500 :${NC}"
echo "1. Vérifiez les logs : tail -50 storage/logs/laravel.log"
echo "2. Rechargez la page avec CTRL+F5 (forcer le rechargement)"
echo "3. Envoyez-moi la dernière erreur du fichier laravel.log"
echo ""
echo "════════════════════════════════════════════════════════════════"
