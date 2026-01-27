#!/bin/bash
set -e

echo "=============================================================="
echo "INSTALLATION FINALE ET VÉRIFICATION"
echo "=============================================================="
echo ""

cd "$(dirname "$0")"

# 1. Installer les packages PHP UN PAR UN
echo "📦 Installation des packages PHP"
echo "--------------------------------"

for pkg in "smalot/pdfparser" "phpoffice/phpword" "phpoffice/phpspreadsheet"; do
    echo ""
    echo ">> Installation de $pkg"
    composer require "$pkg" --no-interaction --no-dev -vvv 2>&1 | tail -20
    
    # Vérifier immédiatement si installé
    php -r "
    if (strpos('$pkg', 'smalot') !== false && class_exists('Smalot\PdfParser\Parser')) {
        echo '  ✅ $pkg installé\n';
    } elseif (strpos('$pkg', 'phpword') !== false && class_exists('PhpOffice\PhpWord\IOFactory')) {
        echo '  ✅ $pkg installé\n';
    } elseif (strpos('$pkg', 'phpspreadsheet') !== false && class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        echo '  ✅ $pkg installé\n';
    } else {
        echo '  ❌ $pkg NON installé\n';
    }
    "
done

echo ""

# 2. Dump autoload
echo "⚙️  Régénération de l'autoload"
echo "----------------------------"
composer dump-autoload -o
echo "✅ Autoload OK"
echo ""

# 3. Installer libreoffice
echo "🔧 Installation de libreoffice"
echo "----------------------------"
sudo apt-get update -qq
sudo apt-get install -y libreoffice-core libreoffice-common
echo "✅ Libreoffice OK"
echo ""

# 4. Test final
echo "✅ VÉRIFICATION FINALE"
echo "---------------------"
php test_extraction_simple.php

echo ""
echo "=============================================================="
echo "Si tous les packages sont maintenant ✅, relancez :"
echo "  php test_extraction.php (avec Laravel complet)"
echo "=============================================================="
