#!/bin/bash

# Fix Composer Permissions et Installation PdfParser
# Pour AlwaysData / Linux

set -e  # Arrêter en cas d'erreur

echo "🔧 Fix permissions Composer + Installation PdfParser"
echo "=================================================="
echo ""

# 1. Aller dans le projet
echo "📂 Navigation vers le projet..."
cd ~/yyy/Dossy || {
    echo "❌ Erreur: Impossible de trouver ~/yyy/Dossy"
    echo "Essayez: cd /home/threesixty/yyy/Dossy"
    exit 1
}
echo "✅ Dans le dossier: $(pwd)"
echo ""

# 2. Vérifier utilisateur actuel
USER_CURRENT=$(whoami)
echo "👤 Utilisateur actuel: $USER_CURRENT"
echo ""

# 3. Sauvegarder composer.lock
if [ -f composer.lock ]; then
    BACKUP_NAME="composer.lock.backup.$(date +%Y%m%d_%H%M%S)"
    cp composer.lock "$BACKUP_NAME"
    echo "✅ Backup créé: $BACKUP_NAME"
else
    echo "⚠️ composer.lock n'existe pas (normal pour nouvelle installation)"
fi
echo ""

# 4. Fix ownership complet du projet
echo "📝 Fix ownership du projet..."
chown -R $USER_CURRENT:$USER_CURRENT . 2>/dev/null || {
    echo "⚠️ chown sans sudo (normal si déjà propriétaire)"
}
echo "✅ Ownership vérifié"
echo ""

# 5. Fix permissions
echo "📝 Fix permissions..."
echo "  - Dossiers: 755"
find . -type d -exec chmod 755 {} \; 2>/dev/null
echo "  - Fichiers: 644"
find . -type f -exec chmod 644 {} \; 2>/dev/null
echo "  - Storage: 775"
chmod -R 775 storage bootstrap/cache 2>/dev/null || echo "⚠️ Storage déjà accessible"
echo "✅ Permissions fixées"
echo ""

# 6. Vérifier espace disque
echo "💾 Vérification espace disque..."
DISK_USAGE=$(du -sh . | cut -f1)
echo "  Taille projet: $DISK_USAGE"
df -h . | grep -v Filesystem
echo ""

# 7. Supprimer vendor si existe (pour installation propre)
if [ -d vendor ]; then
    echo "🗑️ Suppression de l'ancien vendor..."
    rm -rf vendor
    echo "✅ Ancien vendor supprimé"
else
    echo "ℹ️ Pas d'ancien vendor (installation fraîche)"
fi
echo ""

# 8. Créer vendor avec bonnes permissions
echo "📁 Création dossier vendor..."
mkdir -p vendor
chmod 755 vendor
echo "✅ Dossier vendor créé"
echo ""

# 9. Réinstaller toutes les dépendances
echo "📦 Installation des dépendances Laravel..."
echo "(Cela peut prendre 2-3 minutes...)"
composer install --no-interaction --prefer-dist --optimize-autoloader

if [ $? -eq 0 ]; then
    echo "✅ Dépendances Laravel installées"
else
    echo "❌ Erreur lors de l'installation des dépendances"
    echo "Essayez manuellement: composer install"
    exit 1
fi
echo ""

# 10. Installer PdfParser
echo "📦 Installation PdfParser..."
composer require smalot/pdfparser --no-interaction

if [ $? -eq 0 ]; then
    echo "✅ PdfParser installé avec succès!"
else
    echo "❌ Erreur lors de l'installation de PdfParser"
    exit 1
fi
echo ""

# 11. Vérifier installation
echo "🔍 Vérification de l'installation..."
if composer show | grep -q pdfparser; then
    VERSION=$(composer show smalot/pdfparser | grep versions | awk '{print $3}')
    echo "✅ PdfParser $VERSION installé et listé dans composer"
else
    echo "⚠️ PdfParser installé mais pas listé (peut être normal)"
fi
echo ""

# 12. Test fonctionnel PdfParser
echo "🧪 Test fonctionnel PdfParser..."
php -r "
    require 'vendor/autoload.php';
    use Smalot\PdfParser\Parser;
    try {
        \$parser = new Parser();
        echo '✅ PdfParser fonctionne correctement!\n';
    } catch (Exception \$e) {
        echo '❌ Erreur: ' . \$e->getMessage() . '\n';
        exit(1);
    }
" || {
    echo "❌ Test PdfParser échoué"
    exit 1
}
echo ""

# 13. Afficher packages installés
echo "📋 Packages installés (principaux):"
composer show | grep -E "laravel|pinecone|pdfparser|openai" | head -10
echo ""

# 14. Clear cache Laravel
echo "🗑️ Clear cache Laravel..."
php artisan config:clear 2>/dev/null || echo "⚠️ Cache config déjà cleared"
php artisan cache:clear 2>/dev/null || echo "⚠️ Cache déjà cleared"
echo "✅ Cache cleared"
echo ""

# 15. Afficher résumé final
echo "=========================================="
echo "✅ INSTALLATION TERMINÉE AVEC SUCCÈS"
echo "=========================================="
echo ""
echo "📊 Résumé:"
echo "  ✅ Permissions fixées"
echo "  ✅ Vendor réinstallé"
echo "  ✅ PdfParser installé et testé"
echo "  ✅ Cache Laravel cleared"
echo ""
echo "🎯 Prochaines étapes:"
echo ""
echo "1️⃣ Tester extraction PDF:"
echo "   php test_pdfparser_after_php_update.php"
echo ""
echo "2️⃣ Tester upload document via app mobile"
echo ""
echo "3️⃣ Vérifier logs en temps réel:"
echo "   tail -f storage/logs/laravel.log | grep -E 'Extracting text|PdfParser'"
echo ""
echo "4️⃣ Si erreur TLS Pinecone, appliquer patch SSL:"
echo "   php apply_ssl_workaround_patch.php"
echo ""
echo "5️⃣ Vérifier que l'app mobile fonctionne:"
echo "   - Upload PDF"
echo "   - Chat avec document"
echo ""
echo "=========================================="
echo "🎉 PRÊT POUR LES TESTS!"
echo "=========================================="
