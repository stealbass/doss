#!/bin/bash

# Script de déploiement automatique - Bibliothèque Juridique
# Usage: ./deploy-legal-library.sh

set -e  # Arrêter en cas d'erreur

echo "🚀 Déploiement de la Bibliothèque Juridique..."
echo ""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Vérifier qu'on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Erreur: Ce script doit être exécuté depuis la racine du projet Laravel${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Vérification des prérequis...${NC}"
sleep 1

# 2. Vérifier que les fichiers existent
REQUIRED_FILES=(
    "app/Models/LegalCategory.php"
    "app/Models/LegalDocument.php"
    "app/Http/Controllers/LegalLibraryController.php"
    "app/Http/Controllers/UserLegalLibraryController.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}❌ Fichier manquant: $file${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✅ Tous les fichiers sont présents${NC}"
echo ""

# 3. Créer le répertoire de stockage
echo -e "${YELLOW}📁 Création du répertoire de stockage...${NC}"
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents
echo -e "${GREEN}✅ Répertoire créé${NC}"
echo ""

# 4. Créer le lien symbolique
echo -e "${YELLOW}🔗 Création du lien symbolique...${NC}"
if [ ! -L "public/storage" ]; then
    php artisan storage:link
    echo -e "${GREEN}✅ Lien symbolique créé${NC}"
else
    echo -e "${GREEN}✅ Lien symbolique existe déjà${NC}"
fi
echo ""

# 5. Exécuter les migrations
echo -e "${YELLOW}🗄️  Exécution des migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✅ Migrations terminées${NC}"
echo ""

# 6. Vider les caches
echo -e "${YELLOW}🧹 Nettoyage des caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo -e "${GREEN}✅ Caches vidés${NC}"
echo ""

# 7. Optimisation (optionnel en production)
echo -e "${YELLOW}⚡ Optimisation...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Optimisation terminée${NC}"
echo ""

# 8. Vérification finale
echo -e "${YELLOW}🔍 Vérification finale...${NC}"
echo ""

# Vérifier les permissions
echo "Permissions du répertoire de stockage:"
ls -la storage/app/public/legal_documents
echo ""

# Vérifier les migrations
echo "Statut des migrations:"
php artisan migrate:status | grep -i legal || echo "Migrations legal library exécutées"
echo ""

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS !${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}📝 Prochaines étapes:${NC}"
echo "1. Ajouter les liens de navigation dans votre menu"
echo "2. Tester l'accès admin: /legal-library"
echo "3. Tester l'accès utilisateur: /library"
echo ""
echo -e "${YELLOW}📚 Documentation complète: LEGAL_LIBRARY_FEATURE.md${NC}"
echo ""
