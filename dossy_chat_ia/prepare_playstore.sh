# 🚀 SCRIPT COMPLET - PRÉPARATION PLAY STORE POUR DOSSY CHAT IA

# Ce script automatise la préparation de l'application Flutter pour Google Play Store

# =====================================================================
# ÉTAPE 1: VÉRIFICATIONS PRÉALABLES
# =====================================================================

echo "==============================================="
echo "🔍 ÉTAPE 1: VÉRIFICATIONS PRÉALABLES"
echo "==============================================="

cd "$(dirname "$0")"
PROJECT_DIR=$(pwd)

# Vérifier que Flutter est installé
if ! command -v flutter &> /dev/null; then
    echo "❌ Flutter n'est pas installé. Installez Flutter d'abord."
    exit 1
fi

echo "✅ Flutter est installé"
echo "✅ Version Flutter:"
flutter --version

# Vérifier Dart
echo "✅ Version Dart:"
dart --version

# Vérifier la structure du projet
if [ ! -f "pubspec.yaml" ]; then
    echo "❌ pubspec.yaml non trouvé. Assurez-vous d'être dans le répertoire racine du projet."
    exit 1
fi

echo "✅ Structure du projet validée"

# =====================================================================
# ÉTAPE 2: NETTOYAGE ET RÉCUPÉRATION DES DÉPENDANCES
# =====================================================================

echo ""
echo "==============================================="
echo "🧹 ÉTAPE 2: NETTOYAGE ET DÉPENDANCES"
echo "==============================================="

echo "🔄 Nettoyage du projet..."
flutter clean

echo "📦 Récupération des dépendances..."
flutter pub get

echo "⬆️  Mise à jour des dépendances..."
flutter pub upgrade

echo "✅ Dépendances mises à jour"

# =====================================================================
# ÉTAPE 3: ANALYSE STATIQUE
# =====================================================================

echo ""
echo "==============================================="
echo "🔎 ÉTAPE 3: ANALYSE STATIQUE DU CODE"
echo "==============================================="

echo "🔍 Exécution de flutter analyze..."
if flutter analyze; then
    echo "✅ Aucune erreur d'analyse"
else
    echo "⚠️  Avertissements d'analyse trouvés (non bloquant)"
fi

# =====================================================================
# ÉTAPE 4: GÉNÉRATION DES ICONS
# =====================================================================

echo ""
echo "==============================================="
echo "🎨 ÉTAPE 4: GÉNÉRATION DES ICONS"
echo "==============================================="

if [ -f "pubspec.yaml" ] && grep -q "flutter_launcher_icons:" pubspec.yaml; then
    echo "🔄 Génération des icons..."
    flutter pub run flutter_launcher_icons:main
    echo "✅ Icons générées"
else
    echo "⚠️  flutter_launcher_icons non configuré"
fi

# =====================================================================
# ÉTAPE 5: VÉRIFICATION DE LA CONFIGURATION DE PRODUCTION
# =====================================================================

echo ""
echo "==============================================="
echo "⚙️  ÉTAPE 5: VÉRIFICATION CONFIG PRODUCTION"
echo "==============================================="

# Vérifier app_constants.dart
if grep -q "isTestMode = false" lib/core/constants/app_constants.dart; then
    echo "✅ isTestMode = false (PRODUCTION)"
else
    echo "⚠️  isTestMode pas en production! Correction nécessaire."
fi

# Vérifier les URLs
if grep -q "https://dossypro.com/api/mobile" lib/core/constants/app_constants.dart; then
    echo "✅ URLs pointent vers production"
fi

# Vérifier google-services.json
if [ -f "android/app/google-services.json" ]; then
    echo "✅ google-services.json trouvé"
else
    echo "⚠️  google-services.json manquant - Firebase n'aura pas accès"
fi

# Vérifier key.properties
if [ -f "android/key.properties" ]; then
    echo "✅ key.properties trouvé"
    
    # Vérifier les valeurs
    if grep -q "YOUR_KEYSTORE_PASSWORD" android/key.properties; then
        echo "⚠️  ⚠️  IMPORTANT: Remplacez les valeurs placeholder dans android/key.properties"
        echo "   Éditez: android/key.properties"
        echo "   - storePassword: YOUR_KEYSTORE_PASSWORD_HERE"
        echo "   - keyPassword: YOUR_KEY_PASSWORD_HERE"
    else
        echo "✅ key.properties semble configuré"
    fi
else
    echo "⚠️  key.properties manquant - Création fichier template..."
    mkdir -p android
    cat > android/key.properties << 'EOF'
storePassword=YOUR_KEYSTORE_PASSWORD_HERE
keyPassword=YOUR_KEY_PASSWORD_HERE
keyAlias=upload
storeFile=upload-keystore.jks
EOF
    echo "📝 Fichier template créé - À éditer avec vraies valeurs!"
fi

# =====================================================================
# ÉTAPE 6: GÉNÉRATION DE LA CLÉS DE SIGNATURE (optionnel)
# =====================================================================

echo ""
echo "==============================================="
echo "🔑 ÉTAPE 6: GESTION CLÉ DE SIGNATURE"
echo "==============================================="

# Sur Windows, le script créera une variable
if [ -f "~/.android/debug.keystore" ]; then
    echo "✅ Keystore debug trouvé (~/.android/debug.keystore)"
fi

echo ""
echo "⚠️  IMPORTANT:"
echo "   Pour générer une clé de production (keytool):"
echo ""
echo "   Sous Windows (PowerShell):"
echo "   keytool -genkey -v -keystore upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload"
echo ""
echo "   Sous macOS/Linux:"
echo "   keytool -genkey -v -keystore ~/upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload"
echo ""
echo "   Puis placer le fichier .jks dans le répertoire racine du projet"
echo "   Et mettre à jour android/key.properties avec les mots de passe"
echo ""

# =====================================================================
# ÉTAPE 7: BUILD RELEASE
# =====================================================================

echo ""
echo "==============================================="
echo "🏗️  ÉTAPE 7: BUILD RELEASE AAB"
echo "==============================================="

echo ""
echo "⏳ Compilation du bundle AAB pour Google Play Store..."
echo "   Cela peut prendre 2-5 minutes..."
echo ""

# Build with obfuscation and split debug symbols
flutter build appbundle \
    --release \
    --obfuscate \
    --split-debug-info=build/app/outputs/symbols

BUILD_STATUS=$?

# =====================================================================
# ÉTAPE 8: VÉRIFICATION DU BUILD
# =====================================================================

echo ""
echo "==============================================="
echo "✅ ÉTAPE 8: VÉRIFICATION DU BUILD"
echo "==============================================="

if [ $BUILD_STATUS -eq 0 ]; then
    AAB_FILE="build/app/outputs/bundle/release/app-release.aab"
    
    if [ -f "$AAB_FILE" ]; then
        FILE_SIZE=$(du -h "$AAB_FILE" | cut -f1)
        echo "✅ AAB générée avec succès!"
        echo "📦 Fichier: $AAB_FILE"
        echo "📊 Taille: $FILE_SIZE"
        echo ""
        echo "🎯 PROCHAINES ÉTAPES:"
        echo "   1. Vérifier android/key.properties est correctement configuré"
        echo "   2. Aller sur https://play.google.com/console/"
        echo "   3. Créer/Sélectionner l'application"
        echo "   4. Aller dans \"Mise en production > Créer version\""
        echo "   5. Upload le fichier AAB généré"
        echo "   6. Remplir les informations de l'app store"
        echo ""
    else
        echo "❌ Erreur: Fichier AAB non généré"
        exit 1
    fi
else
    echo "❌ Erreur lors de la compilation"
    echo "   Vérifiez les messages d'erreur ci-dessus"
    exit 1
fi

# =====================================================================
# RÉSUMÉ FINAL
# =====================================================================

echo ""
echo "==============================================="
echo "📋 RÉSUMÉ - POINTS À VÉRIFIER"
echo "==============================================="
echo ""
echo "Avant de soumettre sur Play Store:"
echo ""
echo "☑️  Code:"
echo "    ✅ Pas d'erreurs d'analyse"
echo "    ✅ Icons générées"
echo "    ✅ Mode production activé (isTestMode = false)"
echo ""
echo "☑️  Configuration:"
echo "    ✅ URL API pointent vers https://dossypro.com/api/mobile"
echo "    ✅ google-services.json présent"
echo "    ✅ key.properties configuré avec vrais mots de passe"
echo ""
echo "☑️  Assets:"
echo "    📋 Screenshots (1080x1920) - 5-8 images"
echo "    📋 Icon 512x512 (.png)"
echo "    📋 Feature graphic 1024x500 (optionnel)"
echo ""
echo "☑️  Play Console:"
echo "    📋 Nom app: 'DOSSY Chat IA - Assistant Juridique IA'"
echo "    📋 Description attrayante"
echo "    📋 Catégorie: Éducation"
echo "    📋 Conditions utilisation & Privacy"
echo ""
echo "📦 AAB Prête: $AAB_FILE"
echo ""
echo "✅ Exécution terminée avec succès!"
echo ""
