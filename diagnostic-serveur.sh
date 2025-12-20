#!/bin/bash

echo "════════════════════════════════════════════════════════════════"
echo "      🔍 DIAGNOSTIC SERVEUR DOSSY PRO - Erreurs 500"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. Vérification du répertoire courant"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
pwd
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2. Vérification de la branche Git"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
git branch --show-current
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3. Dernier commit Git"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
git log -1 --oneline
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4. Vérification des contrôleurs Mobile"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "app/Http/Controllers/MobileDashboardController.php" ]; then
    echo -e "${GREEN}✅ MobileDashboardController.php existe${NC}"
else
    echo -e "${RED}❌ MobileDashboardController.php MANQUANT${NC}"
fi

if [ -f "app/Http/Controllers/MobileAnalyticsController.php" ]; then
    echo -e "${GREEN}✅ MobileAnalyticsController.php existe${NC}"
else
    echo -e "${RED}❌ MobileAnalyticsController.php MANQUANT${NC}"
fi

if [ -f "app/Http/Controllers/DocumentTemplateController.php" ]; then
    echo -e "${GREEN}✅ DocumentTemplateController.php existe${NC}"
else
    echo -e "${RED}❌ DocumentTemplateController.php MANQUANT${NC}"
fi

if [ -f "app/Http/Controllers/FiscalSocialResourceController.php" ]; then
    echo -e "${GREEN}✅ FiscalSocialResourceController.php existe${NC}"
else
    echo -e "${RED}❌ FiscalSocialResourceController.php MANQUANT${NC}"
fi

if [ -f "app/Http/Controllers/CalculatorController.php" ]; then
    echo -e "${GREEN}✅ CalculatorController.php existe${NC}"
else
    echo -e "${RED}❌ CalculatorController.php MANQUANT${NC}"
fi

echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "5. Vérification des imports dans routes/web.php"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if grep -q "MobileDashboardController" routes/web.php; then
    echo -e "${GREEN}✅ MobileDashboardController importé${NC}"
else
    echo -e "${RED}❌ MobileDashboardController NON importé${NC}"
fi

if grep -q "DocumentTemplateController" routes/web.php; then
    echo -e "${GREEN}✅ DocumentTemplateController importé${NC}"
else
    echo -e "${RED}❌ DocumentTemplateController NON importé${NC}"
fi

if grep -q "FiscalSocialResourceController" routes/web.php; then
    echo -e "${GREEN}✅ FiscalSocialResourceController importé${NC}"
else
    echo -e "${RED}❌ FiscalSocialResourceController NON importé${NC}"
fi

if grep -q "CalculatorController" routes/web.php; then
    echo -e "${GREEN}✅ CalculatorController importé${NC}"
else
    echo -e "${RED}❌ CalculatorController NON importé${NC}"
fi

echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "6. Vérification du modèle LegalCategory"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if grep -q "'country'" app/Models/LegalCategory.php; then
    echo -e "${GREEN}✅ Champ 'country' dans \$fillable${NC}"
else
    echo -e "${RED}❌ Champ 'country' MANQUANT dans \$fillable${NC}"
fi

if grep -q "'is_mobile_visible'" app/Models/LegalCategory.php; then
    echo -e "${GREEN}✅ Champ 'is_mobile_visible' dans \$fillable${NC}"
else
    echo -e "${RED}❌ Champ 'is_mobile_visible' MANQUANT dans \$fillable${NC}"
fi

echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "7. Dernières erreurs Laravel (20 dernières lignes)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "storage/logs/laravel.log" ]; then
    echo -e "${YELLOW}Dernières erreurs :${NC}"
    tail -20 storage/logs/laravel.log
else
    echo -e "${YELLOW}Aucun fichier de log trouvé${NC}"
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "      🔧 ACTIONS RECOMMANDÉES"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Si des fichiers sont MANQUANTS (❌), exécutez :"
echo -e "${YELLOW}git pull origin main${NC}"
echo ""
echo "Ensuite, VIDER LES CACHES (OBLIGATOIRE) :"
echo -e "${YELLOW}php artisan cache:clear && php artisan route:clear && php artisan config:clear && php artisan view:clear${NC}"
echo ""
echo "════════════════════════════════════════════════════════════════"
