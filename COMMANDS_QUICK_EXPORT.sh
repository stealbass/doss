#!/bin/bash

#############################################################################
#                                                                           #
#  🚀 SCRIPT D'EXPORT RAPIDE - DOSSY CHAT IA FLUTTER PROJECT 🚀           #
#                                                                           #
#############################################################################

echo ""
echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║             📦 EXPORT DOSSY CHAT IA FLUTTER PROJECT 📦              ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""

# Couleurs pour le terminal
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
PROJECT_DIR="/home/user/webapp/dossy_chat_ia"
OUTPUT_FILE="/home/user/webapp/dossy_chat_ia.tar.gz"

echo -e "${BLUE}📂 Dossier source:${NC} $PROJECT_DIR"
echo -e "${BLUE}📦 Fichier de sortie:${NC} $OUTPUT_FILE"
echo ""

# Vérifier si le dossier existe
if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${YELLOW}❌ Erreur: Le dossier $PROJECT_DIR n'existe pas!${NC}"
    exit 1
fi

# Créer l'archive
echo -e "${GREEN}🔄 Création de l'archive tar.gz...${NC}"
cd /home/user/webapp
tar -czf dossy_chat_ia.tar.gz dossy_chat_ia/

# Vérifier la création
if [ -f "$OUTPUT_FILE" ]; then
    FILE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo ""
    echo -e "${GREEN}✅ Archive créée avec succès!${NC}"
    echo -e "${BLUE}📦 Fichier:${NC} $OUTPUT_FILE"
    echo -e "${BLUE}📏 Taille:${NC} $FILE_SIZE"
    echo ""
    echo "╔══════════════════════════════════════════════════════════════════════╗"
    echo "║                                                                      ║"
    echo "║                   📥 COMMENT TÉLÉCHARGER L'ARCHIVE 📥               ║"
    echo "║                                                                      ║"
    echo "╚══════════════════════════════════════════════════════════════════════╝"
    echo ""
    echo "Option 1 - SCP (depuis votre machine locale):"
    echo "  scp user@server:/home/user/webapp/dossy_chat_ia.tar.gz ~/Downloads/"
    echo ""
    echo "Option 2 - SFTP (interface graphique):"
    echo "  - Utiliser FileZilla, WinSCP, ou Cyberduck"
    echo "  - Télécharger: /home/user/webapp/dossy_chat_ia.tar.gz"
    echo ""
    echo "Option 3 - Depuis l'interface web du serveur (si disponible)"
    echo ""
    echo "╔══════════════════════════════════════════════════════════════════════╗"
    echo "║                                                                      ║"
    echo "║               📱 APRÈS TÉLÉCHARGEMENT SUR VOTRE PC 📱               ║"
    echo "║                                                                      ║"
    echo "╚══════════════════════════════════════════════════════════════════════╝"
    echo ""
    echo "1️⃣  Décompresser l'archive:"
    echo "    tar -xzf dossy_chat_ia.tar.gz"
    echo ""
    echo "2️⃣  Ouvrir dans Android Studio:"
    echo "    - File → Open"
    echo "    - Sélectionner le dossier 'dossy_chat_ia'"
    echo ""
    echo "3️⃣  Installer les dépendances:"
    echo "    cd dossy_chat_ia"
    echo "    flutter pub get"
    echo ""
    echo "4️⃣  Lancer l'application:"
    echo "    flutter run"
    echo ""
    echo "5️⃣  Générer l'APK:"
    echo "    flutter build apk --release"
    echo ""
    echo "╔══════════════════════════════════════════════════════════════════════╗"
    echo "║                                                                      ║"
    echo "║                        ✅ SUCCÈS ! ✅                                ║"
    echo "║                                                                      ║"
    echo "║   Le projet Flutter DOSSY CHAT IA est prêt à être téléchargé !     ║"
    echo "║                                                                      ║"
    echo "╚══════════════════════════════════════════════════════════════════════╝"
    echo ""
else
    echo -e "${YELLOW}❌ Erreur lors de la création de l'archive!${NC}"
    exit 1
fi

#############################################################################
#                         FIN DU SCRIPT                                     #
#############################################################################
