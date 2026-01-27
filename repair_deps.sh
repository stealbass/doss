#!/bin/bash
set -e

echo "=============================================================="
echo "RÉPARATION DES DÉPENDANCES (nettoyage complet)"
echo "=============================================================="
echo ""

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

# ====== 1. Nettoyer les fichiers composer ======
echo "🧹 ÉTAPE 1 : Nettoyage complet"
echo "------------------------------"
echo "  Suppression de vendor/ ..."
rm -rf vendor/ 2>/dev/null || true
echo "  ✅ vendor/ supprimé"
echo ""

# ====== 2. Réinstaller à partir de composer.lock ======
echo "📦 ÉTAPE 2 : Réinstaller composer (à partir de composer.lock)"
echo "----------------------------------------------------------"
composer install --no-dev --optimize-autoloader
echo "✅ composer install OK"
echo ""

# ====== 3. Ajouter les packages manquants ======
echo "📦 ÉTAPE 3 : Ajouter les packages d'extraction"
echo "-------------------------------------------"

for package in "smalot/pdfparser" "phpoffice/phpword" "phpoffice/phpspreadsheet"; do
    echo "  >> $package"
    composer require "$package" --no-dev --no-interaction 2>&1 | grep -E "Using|Added|Installing|error" || true
done

echo "✅ Packages OK"
echo ""

# ====== 4. Autoload ======
echo "⚙️  ÉTAPE 4 : Régénérer l'autoload"
echo "--------------------------------"
composer dump-autoload -o --no-dev
echo "✅ Autoload OK"
echo ""

# ====== 5. Outils système ======
echo "🔧 ÉTAPE 5 : Installer les outils système"
echo "--------------------------------------"

if command -v apt-get &> /dev/null; then
    echo "  Debian/Ubuntu détecté"
    sudo apt-get update -qq
    sudo apt-get install -y poppler-utils libreoffice-core libreoffice-common >/dev/null 2>&1
    echo "✅ Outils système OK"
elif command -v yum &> /dev/null; then
    echo "  CentOS/RHEL détecté"
    sudo yum update -y >/dev/null 2>&1
    sudo yum install -y poppler libreoffice-core >/dev/null 2>&1
    echo "✅ Outils système OK"
else
    echo "⚠️  Installez manuellement : sudo apt-get install poppler-utils libreoffice-core"
fi

echo ""

# ====== 6. Vérification ======
echo "✅ VÉRIFICATION FINALE"
echo "---------------------"
php -r "
echo \"📦 Packages PHP :\n\";
\$packages = [
    'Smalot\PdfParser\Parser' => 'smalot/pdfparser',
    'PhpOffice\PhpWord\IOFactory' => 'phpoffice/phpword',
    'PhpOffice\PhpSpreadsheet\IOFactory' => 'phpoffice/phpspreadsheet',
    'Barryvdh\Debugbar\ServiceProvider' => 'laravel-debugbar',
];
foreach (\$packages as \$class => \$package) {
    echo class_exists(\$class) ? \"   ✅ \$package\n\" : \"   ❌ \$package\n\";
}
echo \"\n🔧 Outils système :\n\";
\$tools = ['pdftotext', 'libreoffice'];
foreach (\$tools as \$t) {
    echo shell_exec(\"which \$t 2>/dev/null\") ? \"   ✅ \$t\n\" : \"   ❌ \$t\n\";
}
"

echo ""
echo "=============================================================="
echo "✅ Réparation terminée"
echo "Lancez : php test_extraction.php"
echo "=============================================================="
