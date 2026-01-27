#!/bin/bash
set -e

echo "=============================================================="
echo "INSTALLATION AUTOMATIQUE DES DÉPENDANCES D'EXTRACTION"
echo "=============================================================="
echo ""

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

# ====== ÉTAPE 1 : composer install ======
echo "📦 ÉTAPE 1 : Composer install (résoudre les dépendances)"
echo "-------------------------------------------------------"
composer install --no-dev --optimize-autoloader --no-interaction
echo "✅ composer install OK"
echo ""

# ====== ÉTAPE 2 : Ajouter les packages manquants ======
echo "📦 ÉTAPE 2 : Installer les packages PHP"
echo "------------------------------------"

for package in "smalot/pdfparser" "phpoffice/phpword" "phpoffice/phpspreadsheet"; do
    echo "  >> composer require $package"
    composer require "$package" --no-dev --no-interaction
done

echo "✅ Packages PHP OK"
echo ""

# ====== ÉTAPE 3 : Autoload optimisé ======
echo "⚙️  ÉTAPE 3 : Générer l'autoload optimisé"
echo "---------------------------------------"
composer dump-autoload -o --no-dev
echo "✅ Autoload OK"
echo ""

# ====== ÉTAPE 4 : Outils système (avec sudo non-interactif) ======
echo "🔧 ÉTAPE 4 : Installer les outils système"
echo "--------------------------------------"

# Détecte le gestionnaire de paquets
if command -v apt-get &> /dev/null; then
    echo "  Détecté : apt-get (Debian/Ubuntu)"
    echo "  >> sudo apt-get update"
    sudo -n apt-get update || echo "⚠️  apt-get update nécessite un mot de passe"
    
    echo "  >> sudo apt-get install -y poppler-utils libreoffice-core libreoffice-common"
    sudo -n apt-get install -y poppler-utils libreoffice-core libreoffice-common || {
        echo "⚠️  Installation avec sudo nécessite un mot de passe"
        echo "  Relancez avec : sudo -S bash $0 < /dev/stdin"
        exit 1
    }
    echo "✅ Outils système OK"
    
elif command -v yum &> /dev/null; then
    echo "  Détecté : yum (CentOS/RHEL)"
    echo "  >> sudo yum update -y"
    sudo -n yum update -y || echo "⚠️  yum update nécessite un mot de passe"
    
    echo "  >> sudo yum install -y poppler libreoffice-core"
    sudo -n yum install -y poppler libreoffice-core || {
        echo "⚠️  Installation avec sudo nécessite un mot de passe"
        echo "  Relancez avec : sudo -S bash $0 < /dev/stdin"
        exit 1
    }
    echo "✅ Outils système OK"
    
else
    echo "⚠️  Gestionnaire de paquets non détecté (apt-get ou yum)"
    echo "Installation manuelle requise :"
    echo "  - Debian/Ubuntu : sudo apt-get install poppler-utils libreoffice-core"
    echo "  - CentOS/RHEL : sudo yum install poppler libreoffice-core"
fi

echo ""

# ====== ÉTAPE 5 : Vérification finale ======
echo "✅ VÉRIFICATION FINALE"
echo "---------------------"
echo ""

echo "📦 Packages PHP :"
php -r "
\$packages = [
    'Smalot\PdfParser\Parser' => 'smalot/pdfparser',
    'PhpOffice\PhpWord\IOFactory' => 'phpoffice/phpword',
    'PhpOffice\PhpSpreadsheet\IOFactory' => 'phpoffice/phpspreadsheet',
];
foreach (\$packages as \$class => \$package) {
    if (class_exists(\$class)) {
        echo \"   ✅ \$package\n\";
    } else {
        echo \"   ❌ \$package\n\";
    }
}
"

echo ""
echo "🔧 Outils système :"
for tool in "pdftotext" "libreoffice"; do
    if command -v "$tool" &> /dev/null; then
        echo "   ✅ $tool"
    else
        echo "   ❌ $tool"
    fi
done

echo ""
echo "=============================================================="
echo "✅ Installation terminée !"
echo "Lancez maintenant : php test_extraction.php"
echo "=============================================================="
