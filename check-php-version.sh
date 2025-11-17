#!/bin/bash

# Script de vérification de version PHP
# Usage: ./check-php-version.sh

echo "════════════════════════════════════════════════════════"
echo "   🔍 Vérification de la Configuration PHP"
echo "════════════════════════════════════════════════════════"
echo ""

# Version PHP actuelle
echo "📌 Version PHP Actuelle:"
php -v | head -1
echo ""

# Versions PHP disponibles
echo "📦 Versions PHP Disponibles sur ce Serveur:"
ls -1 /usr/bin/php* 2>/dev/null | grep -E 'php[0-9]' || echo "Impossible de lister les versions"
echo ""

# Vérifier les extensions requises
echo "🔧 Extensions PHP Requises pour Laravel 11:"
required_extensions=("mbstring" "xml" "pdo" "openssl" "json" "tokenizer" "curl" "zip" "fileinfo")

for ext in "${required_extensions[@]}"; do
    if php -m | grep -qi "^$ext$"; then
        echo "  ✅ $ext"
    else
        echo "  ❌ $ext (MANQUANT)"
    fi
done
echo ""

# Composer
echo "📦 Composer:"
if command -v composer &> /dev/null; then
    composer --version 2>/dev/null | head -1
else
    echo "  ❌ Composer non trouvé"
fi
echo ""

# Chemin du projet
echo "📁 Chemin Actuel:"
pwd
echo ""

# Vérifier Laravel
if [ -f "artisan" ]; then
    echo "✅ Projet Laravel détecté"
    
    # Version Laravel
    if [ -f "composer.json" ]; then
        echo "📌 Version Laravel:"
        grep -A 2 '"laravel/framework"' composer.json | head -3
    fi
else
    echo "⚠️  Fichier artisan non trouvé - Êtes-vous dans le bon répertoire ?"
fi
echo ""

# Recommandations
echo "════════════════════════════════════════════════════════"
echo "   💡 RECOMMANDATIONS"
echo "════════════════════════════════════════════════════════"
echo ""

current_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)

if (( $(echo "$current_version < 8.2" | bc -l) )); then
    echo "⚠️  ATTENTION: PHP $current_version détecté"
    echo ""
    echo "Laravel 11 nécessite PHP >= 8.2"
    echo ""
    echo "🔧 SOLUTIONS:"
    echo ""
    echo "1. Via cPanel/Plesk:"
    echo "   → MultiPHP Manager → Sélectionner PHP 8.2 ou 8.3"
    echo ""
    echo "2. Via AlwaysData:"
    echo "   → Web → Sites → Configuration → PHP 8.2+"
    echo ""
    echo "3. Utiliser une version spécifique:"
    echo "   → /usr/bin/php8.2 artisan migrate"
    echo ""
else
    echo "✅ Version PHP Compatible ($current_version)"
    echo ""
    echo "Vous pouvez exécuter les migrations:"
    echo "  $ php artisan migrate"
    echo "  $ php artisan storage:link"
fi

echo ""
echo "════════════════════════════════════════════════════════"
