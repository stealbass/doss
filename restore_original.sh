#!/bin/bash
set -e

echo "============================================================"
echo "RESTAURATION DE L'ÉTAT D'ORIGINE"
echo "============================================================"
echo ""

cd "$(dirname "$0")"

# 1. Restaurer composer.json depuis Git (si disponible)
echo "🔄 ÉTAPE 1 : Restauration de composer.json"
echo "-------------------------------------------"

if git rev-parse --git-dir > /dev/null 2>&1; then
    echo "Git détecté, restauration depuis le dépôt..."
    git checkout composer.json composer.lock 2>/dev/null || echo "⚠️  Pas de version Git disponible"
    echo "✅ Fichiers restaurés depuis Git"
else
    echo "⚠️  Git non disponible, on garde les fichiers actuels"
fi

echo ""

# 2. Réinstaller vendor/ dans son état d'origine
echo "📦 ÉTAPE 2 : Réinstallation de vendor/"
echo "--------------------------------------"

if [ -f "composer.lock" ]; then
    echo "composer.lock trouvé, installation des versions exactes..."
    composer install --no-dev --optimize-autoloader
    echo "✅ vendor/ restauré"
else
    echo "⚠️  composer.lock absent, installation depuis composer.json..."
    composer install --no-dev --optimize-autoloader
    echo "✅ vendor/ recréé"
fi

echo ""

# 3. Vérifier que le site fonctionne
echo "✅ VÉRIFICATION"
echo "---------------"
echo "Vérification que le site démarre..."

php -r "require 'vendor/autoload.php'; echo '✅ Autoload OK\n';" 2>&1

echo ""
echo "============================================================"
echo "✅ RESTAURATION TERMINÉE"
echo ""
echo "Le site devrait maintenant fonctionner normalement."
echo "Pour l'extraction de documents, utilisez une approche"
echo "alternative sans modifier composer (voir ci-dessous)."
echo "============================================================"
