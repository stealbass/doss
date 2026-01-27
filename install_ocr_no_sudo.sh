#!/bin/bash

###############################################################################
# Installation OCR + Packages Python SANS SUDO
# 
# Pour serveurs partagés où sudo n'est pas disponible
# Installe tout dans les répertoires utilisateur
#
# Usage: bash install_ocr_no_sudo.sh
###############################################################################

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║ Installation OCR SANS SUDO - Répertoires utilisateur       ║"
echo "║ Tesseract + Python Packages (user-space)                   ║"
echo "╚════════════════════════════════════════════════════════════╝"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 1️⃣ CRÉER RÉPERTOIRES UTILISATEUR
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "📁 Étape 1: Créer les répertoires utilisateur"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Répertoires de base
INSTALL_DIR="$HOME/.local/opt"
TESSERACT_DIR="$INSTALL_DIR/tesseract"
PYTHON_PACKAGES_DIR="$INSTALL_DIR/python-packages"
WORK_DIR="/tmp/ocr-install-$$"

echo "📍 Répertoires:"
echo "   Base: $INSTALL_DIR"
echo "   Tesseract: $TESSERACT_DIR"
echo "   Python packages: $PYTHON_PACKAGES_DIR"
echo "   Travail: $WORK_DIR"
echo ""

# Créer les répertoires
mkdir -p "$TESSERACT_DIR"
mkdir -p "$PYTHON_PACKAGES_DIR"
mkdir -p "$WORK_DIR"

echo "✅ Répertoires créés"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 2️⃣ INSTALLER TESSERACT FROM SOURCE
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "🔨 Étape 2: Compiler Tesseract depuis source"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cd "$WORK_DIR"

# Vérifier si Tesseract est déjà installé système
if command -v tesseract &> /dev/null; then
    echo "✅ Tesseract trouvé dans le système"
    TESSERACT_PATH=$(which tesseract)
    echo "   📍 Chemin: $TESSERACT_PATH"
    TESSERACT_SYSTEM=1
else
    echo "⚠️  Tesseract non trouvé, téléchargement depuis source..."
    
    # Télécharger Tesseract latest release
    TESSERACT_VERSION="5.3.3"
    TESSERACT_ARCHIVE="tesseract-${TESSERACT_VERSION}.tar.gz"
    TESSERACT_URL="https://github.com/UB-Mannheim/tesseract/archive/refs/tags/${TESSERACT_VERSION}.tar.gz"
    
    echo "   📥 Téléchargement Tesseract ${TESSERACT_VERSION}..."
    curl -L "$TESSERACT_URL" -o "$TESSERACT_ARCHIVE" 2>&1 | grep -E "(100|curl)" || true
    
    echo "   📦 Décompression..."
    tar -xzf "$TESSERACT_ARCHIVE"
    
    cd "tesseract-${TESSERACT_VERSION}"
    
    echo "   🔧 Configuration & compilation (peut prendre 2-5 min)..."
    
    # Configurer avec options minimales pour user-space install
    ./configure --prefix="$TESSERACT_DIR" \
                --disable-shared \
                --enable-static \
                --disable-graphics \
                2>&1 | tail -5
    
    echo "   ⚙️  Compilation..."
    make -j$(nproc) 2>&1 | tail -10 || make 2>&1 | tail -10
    
    echo "   📦 Installation..."
    make install 2>&1 | tail -5
    
    TESSERACT_PATH="$TESSERACT_DIR/bin/tesseract"
    TESSERACT_SYSTEM=0
    
    cd "$WORK_DIR"
    echo "✅ Tesseract compilé et installé dans: $TESSERACT_DIR"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 3️⃣ INSTALLER DONNÉES DE LANGUE
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "📚 Étape 3: Installer données de langue Tesseract"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Déterminer où stocker les données
if [ $TESSERACT_SYSTEM -eq 1 ]; then
    # Chercher le répertoire de données existant
    TESSDATA_DIR=$($TESSERACT_PATH --print-parameters 2>&1 | grep "tessdata-directory" | awk '{print $NF}' || echo "/usr/share/tesseract-ocr/tessdata")
    echo "📍 Utilisation répertoire système: $TESSDATA_DIR"
    TESSDATA_WRITABLE=0
    
    # Vérifier si on peut écrire dedans
    if [ -w "$TESSDATA_DIR" ]; then
        TESSDATA_WRITABLE=1
        echo "✅ Permissions pour écrire dans $TESSDATA_DIR"
    else
        echo "⚠️  Pas de permissions pour $TESSDATA_DIR"
        TESSDATA_DIR="$TESSERACT_DIR/tessdata"
        mkdir -p "$TESSDATA_DIR"
        TESSDATA_WRITABLE=1
        echo "   📍 Utilisation répertoire user: $TESSDATA_DIR"
    fi
else
    TESSDATA_DIR="$TESSERACT_DIR/tessdata"
    mkdir -p "$TESSDATA_DIR"
    TESSDATA_WRITABLE=1
fi

echo "   📥 Téléchargement données de langue..."

# Télécharger les données
if [ $TESSDATA_WRITABLE -eq 1 ]; then
    # Français
    if [ ! -f "$TESSDATA_DIR/fra.traineddata" ]; then
        echo "   → Français (fra)..."
        curl -L "https://github.com/tesseract-ocr/tessdata/raw/main/fra.traineddata" \
             -o "$TESSDATA_DIR/fra.traineddata" 2>&1 | grep -E "(100|curl)" || true
    fi
    
    # Anglais
    if [ ! -f "$TESSDATA_DIR/eng.traineddata" ]; then
        echo "   → Anglais (eng)..."
        curl -L "https://github.com/tesseract-ocr/tessdata/raw/main/eng.traineddata" \
             -o "$TESSDATA_DIR/eng.traineddata" 2>&1 | grep -E "(100|curl)" || true
    fi
    
    echo "✅ Données de langue installées dans: $TESSDATA_DIR"
else
    echo "⚠️  Impossible de télécharger les données de langue"
    echo "   Essayez manuellement:"
    echo "   cd $TESSDATA_DIR"
    echo "   wget https://github.com/tesseract-ocr/tessdata/raw/main/fra.traineddata"
    echo "   wget https://github.com/tesseract-ocr/tessdata/raw/main/eng.traineddata"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 4️⃣ INSTALLER PACKAGES PYTHON
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "🐍 Étape 4: Installer Packages Python (pip --user)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo "   📥 Installation dans: $HOME/.local"

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

for package in "${PYTHON_PACKAGES[@]}"; do
    echo "   → $package..."
    pip3 install --user "$package" 2>&1 | tail -1 || pip install --user "$package" 2>&1 | tail -1
done

echo "✅ Packages Python installés dans: $HOME/.local/lib/python*/site-packages"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 5️⃣ CONFIGURER LES VARIABLES D'ENVIRONNEMENT
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "⚙️  Étape 5: Configurer variables d'environnement"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Créer un fichier de configuration
CONFIG_FILE="$HOME/.dossy_ocr_env.sh"

cat > "$CONFIG_FILE" << 'ENVSCRIPT'
#!/bin/bash
# Configuration OCR sans sudo pour Dossy

# Répertoires
export INSTALL_DIR="$HOME/.local/opt"
export TESSERACT_DIR="$INSTALL_DIR/tesseract"
export TESSDATA_DIR="$INSTALL_DIR/tesseract/tessdata"

# PATH
export PATH="$TESSERACT_DIR/bin:$HOME/.local/bin:$PATH"

# PYTHONPATH - Très important pour ProcessDocumentForRAG!
export PYTHONPATH="$HOME/.local/lib/python3.9/site-packages:$HOME/.local/lib/python3.8/site-packages:$HOME/.local/lib/python3.7/site-packages:$PYTHONPATH"

# Variable pour Tesseract (optionnel)
export TESSDATA_PREFIX="$TESSDATA_DIR"

# Vérifications
if [ -x "$TESSERACT_DIR/bin/tesseract" ]; then
    echo "✅ Tesseract user: $TESSERACT_DIR/bin/tesseract"
elif command -v tesseract &> /dev/null; then
    echo "✅ Tesseract système: $(which tesseract)"
fi

echo "✅ PYTHONPATH configuré pour imports Python"
ENVSCRIPT

chmod +x "$CONFIG_FILE"

echo "📝 Configuration sauvegardée dans: $CONFIG_FILE"
echo ""
echo "   Source le fichier pour appliquer les variables:"
echo "   source $CONFIG_FILE"

# Ajouter au ~/.bashrc
echo ""
echo "🔧 Ajout automatique à ~/.bashrc..."
if ! grep -q "dossy_ocr_env" "$HOME/.bashrc"; then
    echo "" >> "$HOME/.bashrc"
    echo "# Dossy OCR Configuration (no sudo)" >> "$HOME/.bashrc"
    echo "[ -f $CONFIG_FILE ] && source $CONFIG_FILE" >> "$HOME/.bashrc"
    echo "✅ Ajouté à ~/.bashrc"
else
    echo "⚠️  Déjà présent dans ~/.bashrc"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 6️⃣ VÉRIFICATIONS
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "✅ VÉRIFICATIONS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Charger les variables
source "$CONFIG_FILE"

# Vérifier Tesseract
echo ""
echo "🔍 Tesseract:"
if command -v tesseract &> /dev/null; then
    echo "   ✅ Trouvé: $(which tesseract)"
    tesseract --version | head -n 1
else
    echo "   ❌ Non trouvé"
fi

# Vérifier les données de langue
echo ""
echo "🔍 Données de langue:"
if [ -f "$TESSDATA_DIR/fra.traineddata" ]; then
    echo "   ✅ Français: OK"
else
    echo "   ❌ Français: MANQUANT"
fi

if [ -f "$TESSDATA_DIR/eng.traineddata" ]; then
    echo "   ✅ Anglais: OK"
else
    echo "   ❌ Anglais: MANQUANT"
fi

# Vérifier les packages Python
echo ""
echo "🔍 Packages Python:"
python3 << 'PYCHECK'
import sys
packages = ['pytesseract', 'PIL', 'pdfplumber', 'PyPDF2', 'docx', 'openpyxl', 'pptx', 'boto3', 'mysql']
for pkg in packages:
    try:
        __import__(pkg)
        print(f'   ✅ {pkg}')
    except ImportError:
        print(f'   ❌ {pkg}')
PYCHECK

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 7️⃣ NETTOYAGE
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "🗑️  Nettoyage fichiers temporaires..."
rm -rf "$WORK_DIR"
echo "✅ Terminé"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# RÉSUMÉ FINAL
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║ ✅ INSTALLATION SANS SUDO TERMINÉE                          ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 RÉSUMÉ:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📍 Répertoires installés:"
echo "   Tesseract: $TESSERACT_DIR"
echo "   Données: $TESSDATA_DIR"
echo "   Python: $HOME/.local/lib/python*/site-packages"
echo ""
echo "⚙️  Variables d'environnement:"
echo "   Config: $CONFIG_FILE"
echo "   Source automatique dans: ~/.bashrc"
echo ""
echo "🔌 PYTHONPATH pour ProcessDocumentForRAG:"
echo "   $HOME/.local/lib/python3.9/site-packages"
echo "   $HOME/.local/lib/python3.8/site-packages"
echo "   $HOME/.local/lib/python3.7/site-packages"
echo ""
echo "🚀 Prochaines étapes:"
echo "   1. Redémarrez votre session SSH pour charger ~/.bashrc"
echo "   2. Vérifiez: tesseract --version"
echo "   3. Testez extraction: python3 << 'EOF'"
echo "      import pytesseract; from PIL import Image"
echo "      img = Image.open('test.png')"
echo "      print(pytesseract.image_to_string(img, lang='fra'))"
echo "      EOF"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
