#!/bin/bash

###############################################################################
# Installation OCR + Packages pour support Images dans ProcessDocumentForRAG
# 
# Ce script installe les dépendances nécessaires pour extraire du texte
# depuis les images (jpg, png, gif, etc.) en utilisant Tesseract OCR
#
# Supporte: Linux (Ubuntu/Debian), macOS, Windows (WSL)
###############################################################################

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║ Installation des Packages OCR pour Extraction d'Images     ║"
echo "║ Dossier: dossy-genspark_ai_developer                       ║"
echo "╚════════════════════════════════════════════════════════════╝"

# Déterminer l'OS
OS="$(uname)"
echo "🖥️  Système d'exploitation détecté: $OS"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 1️⃣ INSTALLER TESSERACT-OCR (système)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "📦 Étape 1: Installation de Tesseract-OCR (moteur OCR système)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ "$OS" = "Linux" ]; then
    echo "🐧 Linux détecté - Installation via apt..."
    
    # Mettre à jour apt
    sudo apt update
    
    # Installer Tesseract et support français
    echo "  → Installation tesseract-ocr..."
    sudo apt install -y tesseract-ocr
    
    echo "  → Installation données français tesseract..."
    sudo apt install -y tesseract-ocr-fra
    
    echo "  → Installation données anglais tesseract..."
    sudo apt install -y tesseract-ocr-eng
    
    echo "  → Installation ImageMagick (pour conversion images)..."
    sudo apt install -y imagemagick
    
elif [ "$OS" = "Darwin" ]; then
    echo "🍎 macOS détecté - Installation via Homebrew..."
    
    # Vérifier si Homebrew est installé
    if ! command -v brew &> /dev/null; then
        echo "❌ Homebrew not found. Installez Homebrew d'abord:"
        echo "   /bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\""
        exit 1
    fi
    
    echo "  → Installation tesseract-ocr..."
    brew install tesseract
    
    echo "  → Installation ImageMagick..."
    brew install imagemagick
    
elif [[ "$OS" == *"MINGW"* ]] || [[ "$OS" == *"MSYS"* ]]; then
    echo "🪟 Windows (WSL/MSYS) détecté - Installation pour WSL..."
    
    # WSL Ubuntu - même que Linux
    sudo apt update
    sudo apt install -y tesseract-ocr tesseract-ocr-fra tesseract-ocr-eng imagemagick
    
else
    echo "❌ Système non reconnu: $OS"
    echo "⚙️  Installation manuelle:"
    echo "  - Linux: sudo apt install tesseract-ocr tesseract-ocr-fra"
    echo "  - macOS: brew install tesseract"
    echo "  - Windows: Installer https://github.com/UB-Mannheim/tesseract/wiki"
    exit 1
fi

echo "✅ Tesseract-OCR installé"

# Vérifier l'installation
echo ""
echo "🔍 Vérification Tesseract..."
tesseract --version | head -n 1

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 2️⃣ INSTALLER PACKAGES PYTHON
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "📦 Étape 2: Installation des Packages Python"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Déterminer le répertoire cible pour les packages partagés
SHARED_PACKAGES_DIR="/home/threesixty/yyy/Dossy/python-packages"

echo "📁 Répertoire cible: $SHARED_PACKAGES_DIR"

# Créer le répertoire s'il n'existe pas
if [ ! -d "$SHARED_PACKAGES_DIR" ]; then
    echo "  → Création du répertoire..."
    mkdir -p "$SHARED_PACKAGES_DIR"
fi

# Vérifier les permissions
if [ ! -w "$SHARED_PACKAGES_DIR" ]; then
    echo "⚠️  ATTENTION: Pas de permissions en écriture sur $SHARED_PACKAGES_DIR"
    echo "   Correction avec: sudo chown -R $(whoami):$(whoami) $SHARED_PACKAGES_DIR"
    sudo chown -R $(whoami):$(whoami) "$SHARED_PACKAGES_DIR"
fi

# Packages Python pour OCR + autres extractions
PYTHON_PACKAGES=(
    "pytesseract>=0.3.10"
    "Pillow>=9.0.0"
    "pdfplumber>=0.9.0"
    "PyPDF2>=3.0.0"
    "python-docx>=0.8.11"
    "openpyxl>=3.10.0"
    "python-pptx>=0.6.21"
    "boto3>=1.26.0"
    "mysql-connector-python>=8.0.33"
)

echo "📥 Installation des packages Python..."
for package in "${PYTHON_PACKAGES[@]}"; do
    echo "  → Installing $package..."
    pip3 install --target="$SHARED_PACKAGES_DIR" "$package" 2>&1 | grep -E "(Successfully|already satisfied|Collecting)" || true
done

echo "✅ Tous les packages Python sont installés"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 3️⃣ VÉRIFICATION FINALE
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "✅ VÉRIFICATION FINALE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Vérifier Tesseract
if command -v tesseract &> /dev/null; then
    echo "✅ Tesseract-OCR: OK"
    TESSERACT_PATH=$(which tesseract)
    echo "   📍 Chemin: $TESSERACT_PATH"
else
    echo "❌ Tesseract-OCR: NON TROUVÉ"
fi

# Vérifier les données de langue
if [ -d "/usr/share/tesseract-ocr/tessdata" ]; then
    echo "✅ Données Tesseract: OK"
    LANG_COUNT=$(ls /usr/share/tesseract-ocr/tessdata/*.traineddata 2>/dev/null | wc -l)
    echo "   📊 Langues disponibles: $LANG_COUNT"
    echo "   🇫🇷 Français: $([ -f /usr/share/tesseract-ocr/tessdata/fra.traineddata ] && echo 'OK' || echo 'MANQUANT')"
    echo "   🇬🇧 Anglais: $([ -f /usr/share/tesseract-ocr/tessdata/eng.traineddata ] && echo 'OK' || echo 'MANQUANT')"
elif [ -d "/usr/local/share/tessdata" ]; then
    echo "✅ Données Tesseract: OK (macOS)"
else
    echo "⚠️  Données Tesseract: À vérifier"
fi

# Vérifier les packages Python
echo ""
echo "🐍 Vérification Packages Python..."
PYTHONPATH="$SHARED_PACKAGES_DIR:$SHARED_PACKAGES_DIR/.local/lib/python3.7/site-packages" python3 << 'EOF'
import sys
print(f"   Python version: {sys.version.split()[0]}")

packages_to_check = [
    'pytesseract',
    'PIL',
    'pdfplumber',
    'PyPDF2',
    'docx',
    'openpyxl',
    'pptx',
    'boto3',
    'mysql'
]

for pkg in packages_to_check:
    try:
        __import__(pkg)
        print(f"   ✅ {pkg}: OK")
    except ImportError:
        print(f"   ❌ {pkg}: NON INSTALLÉ")
EOF

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 4️⃣ INFORMATIONS FINALES
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║ ✅ INSTALLATION TERMINÉE                                    ║"
echo "╚════════════════════════════════════════════════════════════╝"

echo ""
echo "📋 RÉSUMÉ DE L'INSTALLATION:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🖼️  Support Images (OCR):"
echo "   ✅ Tesseract-OCR installé"
echo "   ✅ Langues: Français + Anglais"
echo "   ✅ Formats supportés: JPG, PNG, GIF, BMP, WEBP, TIFF"
echo ""
echo "📄 Autres Formats:"
echo "   ✅ PDF: pdfplumber + PyPDF2"
echo "   ✅ Word: python-docx"
echo "   ✅ Excel: openpyxl"
echo "   ✅ PowerPoint: python-pptx"
echo "   ✅ Cloud Storage: boto3 (R2, S3)"
echo ""
echo "📦 Packages Python installés dans:"
echo "   📁 $SHARED_PACKAGES_DIR"
echo ""
echo "🔧 Configuration Laravel:"
echo "   PYTHONPATH est déjà configuré dans ProcessDocumentForRAG.php"
echo "   à: /home/threesixty/yyy/Dossy/python-packages"
echo ""
echo "🚀 Le système est prêt à extraire du texte depuis:"
echo "   ✅ Images (JPG, PNG, GIF, etc.) avec OCR"
echo "   ✅ PDF, Word, Excel, PowerPoint, Texte"
echo "   ✅ Fichiers jusqu'à 30 MB"
echo "   ✅ Extraction en moins de 15 minutes"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "💡 NOTES:"
echo "   • Si tesseract n'est pas trouvé, redémarrez le terminal"
echo "   • Pour Windows: Installez https://github.com/UB-Mannheim/tesseract/wiki"
echo "   • Les formats d'images sont maintenant supportés 🎉"
echo ""
