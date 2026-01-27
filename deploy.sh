#!/bin/bash
# ============================================
# Script de déploiement automatique Dossy
# ============================================

set -e  # Arrêt en cas d'erreur

echo "========================================="
echo "  Dossy - Déploiement en Production"
echo "========================================="
echo ""

# Configuration
APP_DIR="/var/home/threesixty/yyy/Dossy"
BRANCH="main"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonctions
print_step() {
    echo -e "${GREEN}▶ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Vérifier si on est dans le bon répertoire
cd $APP_DIR || { print_error "Directory $APP_DIR not found"; exit 1; }

# 1. Mettre l'application en maintenance
print_step "1/10 Activation du mode maintenance..."
php artisan down --refresh=15 --retry=60 --secret="dossy-deploy-secret-2026"
print_success "Mode maintenance activé"
echo ""

# 2. Pull du code
print_step "2/10 Récupération du code..."
git fetch origin $BRANCH
git reset --hard origin/$BRANCH
print_success "Code mis à jour depuis $BRANCH"
echo ""

# 3. Installation des dépendances
print_step "3/10 Installation des dépendances Composer..."
composer install --no-dev --optimize-autoloader --no-interaction
print_success "Dépendances installées"
echo ""

# 4. Migrations de base de données
print_step "4/10 Exécution des migrations..."
php artisan migrate --force
print_success "Migrations exécutées"
echo ""

# 5. Clear cache
print_step "5/10 Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
print_success "Cache nettoyé"
echo ""

# 6. Optimisation
print_step "6/10 Optimisation de l'application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
print_success "Application optimisée"
echo ""

# 7. Permissions
print_step "7/10 Configuration des permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
print_success "Permissions configurées"
echo ""

# 8. Redémarrage du Queue Worker
print_step "8/10 Redémarrage du Queue Worker..."

# Méthode 1: Graceful restart Laravel
php artisan queue:restart

# Méthode 2: Force restart Supervisor (si disponible)
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart dossy-queue-worker:* || print_warning "Supervisor non configuré"
fi

print_success "Queue Worker redémarré"
echo ""

# 9. Clear OPcache (si disponible)
print_step "9/10 Nettoyage OPcache..."
if command -v php &> /dev/null; then
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared'; } else { echo 'OPcache not available'; }"
fi
print_success "OPcache nettoyé"
echo ""

# 10. Désactiver le mode maintenance
print_step "10/10 Désactivation du mode maintenance..."
php artisan up
print_success "Application en ligne"
echo ""

# Statistiques finales
echo "========================================="
echo "  ✓ Déploiement Terminé avec Succès"
echo "========================================="
echo ""
echo "Informations:"
echo "  - Branche: $BRANCH"
echo "  - Commit: $(git rev-parse --short HEAD)"
echo "  - Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
echo "Vérifications:"
echo "  - Application: https://votredomaine.com"
echo "  - Admin: https://votredomaine.com/admin"
echo "  - API Status: https://votredomaine.com/api/health"
echo ""
echo "Logs:"
echo "  - Laravel: tail -f $APP_DIR/storage/logs/laravel.log"
echo "  - Queue: tail -f $APP_DIR/storage/logs/queue-worker.log"
echo ""

# Vérification post-déploiement
print_step "Vérifications post-déploiement..."

# Vérifier les jobs en attente
PENDING_JOBS=$(php artisan tinker --execute="echo DB::table('jobs')->count();")
echo "  - Jobs en attente: $PENDING_JOBS"

# Vérifier les failed jobs
FAILED_JOBS=$(php artisan tinker --execute="echo DB::table('failed_jobs')->count();")
if [ "$FAILED_JOBS" -gt 0 ]; then
    print_warning "$FAILED_JOBS job(s) échoué(s) - Vérifier avec: php artisan queue:failed"
else
    print_success "Aucun job échoué"
fi

# Vérifier les documents en traitement
PROCESSING_DOCS=$(php artisan tinker --execute="echo DB::table('submitted_documents')->whereIn('processing_status', ['pending', 'processing'])->count();")
echo "  - Documents en traitement: $PROCESSING_DOCS"

echo ""
echo "========================================="
print_success "Tout est en ordre !"
echo "========================================="
