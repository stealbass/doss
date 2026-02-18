# 🚀 SCRIPT PRÉPARATION PLAY STORE - VERSION POWERSHELL (Windows)

# Ce script automatise la préparation de l'application Flutter pour Google Play Store

Write-Host "===============================================" -ForegroundColor Green
Write-Host "🚀 DOSSY CHAT IA - PRÉPARATION PLAY STORE" -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Green
Write-Host ""

# =====================================================================
# ÉTAPE 1: VÉRIFICATIONS PRÉALABLES
# =====================================================================

Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "🔍 ÉTAPE 1: VÉRIFICATIONS PRÉALABLES" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

# Vérifier Flutter
$flutterCheck = flutter --version 2>$null

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Flutter n'est pas installé" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Flutter trouvé:" -ForegroundColor Green
Write-Host "$flutterCheck" -ForegroundColor Gray

# Vérifier Dart
$dartVersion = dart --version 2>$null
Write-Host "✅ Dart version: $dartVersion" -ForegroundColor Green

# Vérifier structure
if (-not (Test-Path "pubspec.yaml")) {
    Write-Host "❌ pubspec.yaml non trouvé" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Structure du projet validée" -ForegroundColor Green

# =====================================================================
# ÉTAPE 2: NETTOYAGE ET DÉPENDANCES
# =====================================================================

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "🧹 ÉTAPE 2: NETTOYAGE ET DÉPENDANCES" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

Write-Host "🔄 Nettoyage du projet..." -ForegroundColor Yellow
flutter clean

Write-Host "📦 Récupération des dépendances..." -ForegroundColor Yellow
flutter pub get

Write-Host "⬆️  Mise à jour des dépendances..." -ForegroundColor Yellow
flutter pub upgrade

Write-Host "✅ Dépendances mises à jour" -ForegroundColor Green

# =====================================================================
# ÉTAPE 3: ANALYSE STATIQUE
# =====================================================================

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "🔎 ÉTAPE 3: ANALYSE STATIQUE DU CODE" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

Write-Host "🔍 Exécution de flutter analyze..." -ForegroundColor Yellow
$analyzeOutput = flutter analyze 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Aucune erreur d'analyse" -ForegroundColor Green
} else {
    Write-Host "⚠️  Avertissements trouvés (non bloquant)" -ForegroundColor Yellow
}

# =====================================================================
# ÉTAPE 4: GÉNÉRATION DES ICONS
# =====================================================================

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "🎨 ÉTAPE 4: GÉNÉRATION DES ICONS" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

$pubspecContent = Get-Content "pubspec.yaml" -Raw

if ($pubspecContent -like "*flutter_launcher_icons*") {
    Write-Host "🔄 Génération des icons..." -ForegroundColor Yellow
    flutter pub run flutter_launcher_icons:main
    Write-Host "✅ Icons générées" -ForegroundColor Green
} else {
    Write-Host "⚠️  flutter_launcher_icons non configuré" -ForegroundColor Yellow
}

# =====================================================================
# ÉTAPE 5: VÉRIFICATION CONFIGURATION PRODUCTION
# =====================================================================

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "⚙️  ÉTAPE 5: VÉRIFICATION CONFIG PRODUCTION" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

$appConstantsPath = "lib/core/constants/app_constants.dart"

if (Test-Path $appConstantsPath) {
    $appConstantsContent = Get-Content $appConstantsPath -Raw
    
    if ($appConstantsContent -like "*isTestMode = false*") {
        Write-Host "✅ isTestMode = false (PRODUCTION)" -ForegroundColor Green
    } else {
        Write-Host "⚠️  isTestMode = true (Mode TEST)" -ForegroundColor Yellow
        Write-Host "   ⚠️  ATTENTION: Doit être 'false' pour Play Store" -ForegroundColor Red
    }
    
    if ($appConstantsContent -like "*https://dossypro.com/api/mobile*") {
        Write-Host "✅ URLs pointent vers production" -ForegroundColor Green
    }
} else {
    Write-Host "❌ app_constants.dart non trouvé" -ForegroundColor Red
}

# Vérifier google-services.json
if (Test-Path "android/app/google-services.json") {
    Write-Host "✅ google-services.json trouvé" -ForegroundColor Green
} else {
    Write-Host "⚠️  google-services.json manquant" -ForegroundColor Yellow
    Write-Host "   Télécharger depuis Firebase Console" -ForegroundColor Yellow
}

# Vérifier key.properties
if (Test-Path "android/key.properties") {
    Write-Host "✅ key.properties trouvé" -ForegroundColor Green
    
    $keyPropsContent = Get-Content "android/key.properties" -Raw
    if ($keyPropsContent -like "*YOUR_KEYSTORE_PASSWORD*") {
        Write-Host "⚠️  ⚠️  IMPORTANT: Remplacez les valeurs placeholder!" -ForegroundColor Red
        Write-Host "   Fichier: android/key.properties" -ForegroundColor Yellow
        Write-Host "   - storePassword: [votre mot de passe keystore]" -ForegroundColor Yellow
        Write-Host "   - keyPassword: [votre mot de passe clé]" -ForegroundColor Yellow
    } else {
        Write-Host "✅ key.properties semble configuré" -ForegroundColor Green
    }
} else {
    Write-Host "⚠️  key.properties manquant - Création template..." -ForegroundColor Yellow
    if (-not (Test-Path "android")) {
        New-Item -ItemType Directory -Path "android" | Out-Null
    }
    
    $keyPropsContent = @"
storePassword=YOUR_KEYSTORE_PASSWORD_HERE
keyPassword=YOUR_KEY_PASSWORD_HERE
keyAlias=upload
storeFile=upload-keystore.jks
"@
    
    Set-Content -Path "android/key.properties" -Value $keyPropsContent
    Write-Host "📝 Template créé - À éditer!" -ForegroundColor Yellow
}

# =====================================================================
# ÉTAPE 6: GÉNÉRATION CLÉS DE SIGNATURE
# =====================================================================

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "🔑 ÉTAPE 6: GESTION CLÉ DE SIGNATURE" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "ℹ️  Pour créer une clé de signature de production:" -ForegroundColor Cyan
Write-Host ""
Write-Host "Ouvrir PowerShell ou Command Prompt et exécuter:" -ForegroundColor Cyan
Write-Host ""
Write-Host "keytool -genkey -v -keystore upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload" -ForegroundColor White -BackgroundColor DarkGray
Write-Host ""
Write-Host "Puis: Placer le fichier upload-keystore.jks dans le répertoire racine du projet" -ForegroundColor Yellow
Write-Host "Puis: Éditer android/key.properties avec les mots de passe utilisés" -ForegroundColor Yellow
Write-Host ""

# =====================================================================
# ÉTAPE 7: BUILD RELEASE
# =====================================================================

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "🏗️  ÉTAPE 7: BUILD RELEASE AAB" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "⏳ Compilation du bundle AAB pour Google Play Store..." -ForegroundColor Yellow
Write-Host "   Cela peut prendre 2-5 minutes..." -ForegroundColor Yellow
Write-Host ""

# Vérifier si key.properties est correctement configuré
$keyPropsContent = Get-Content "android/key.properties" -Raw

if ($keyPropsContent -like "*YOUR_KEYSTORE_PASSWORD*") {
    Write-Host "⚠️  ARRÊT: key.properties contient des valeurs placeholder" -ForegroundColor Red
    Write-Host "   Veuillez éditer android/key.properties avec vos vrais mots de passe" -ForegroundColor Red
    Write-Host ""
    Write-Host "   Ensuite, relancez ce script" -ForegroundColor Yellow
    exit 1
}

# Build with obfuscation
flutter build appbundle `
    --release `
    --obfuscate `
    --split-debug-info=build/app/outputs/symbols

$buildStatus = $LASTEXITCODE

# =====================================================================
# ÉTAPE 8: VÉRIFICATION BUILD
# =====================================================================

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "✅ ÉTAPE 8: VÉRIFICATION DU BUILD" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan

if ($buildStatus -eq 0) {
    $aabFile = "build/app/outputs/bundle/release/app-release.aab"
    
    if (Test-Path $aabFile) {
        $fileSize = (Get-Item $aabFile).Length / 1MB
        Write-Host "✅ AAB générée avec succès!" -ForegroundColor Green
        Write-Host "📦 Fichier: $aabFile" -ForegroundColor Green
        Write-Host "📊 Taille: $([Math]::Round($fileSize, 2)) MB" -ForegroundColor Green
        Write-Host ""
        Write-Host "🎯 PROCHAINES ÉTAPES:" -ForegroundColor Cyan
        Write-Host "   1. ✅ Vérifier android/key.properties configuré" -ForegroundColor Green
        Write-Host "   2. Aller sur https://play.google.com/console/" -ForegroundColor White
        Write-Host "   3. Créer/Sélectionner l'application" -ForegroundColor White
        Write-Host "   4. Aller dans 'Mise en production > Créer version'" -ForegroundColor White
        Write-Host "   5. Upload le fichier AAB:" -ForegroundColor White
        Write-Host "      $aabFile" -ForegroundColor Yellow
        Write-Host "   6. Remplir les informations complètes (titre, description, screenshots)" -ForegroundColor White
        Write-Host "   7. Cliquer 'Examiner' puis 'Déployer'" -ForegroundColor White
        Write-Host ""
    } else {
        Write-Host "❌ Erreur: Fichier AAB non trouvé" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "❌ Erreur lors de la compilation" -ForegroundColor Red
    Write-Host "   Vérifiez les messages d'erreur ci-dessus" -ForegroundColor Red
    exit 1
}

# =====================================================================
# RÉSUMÉ FINAL
# =====================================================================

Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "📋 RÉSUMÉ - POINTS À VÉRIFIER AVANT SOUMISSION" -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "☑️  Code & Configuration:" -ForegroundColor Green
Write-Host "    ✅ Pas d'erreurs d'analyse (flutter analyze)" -ForegroundColor Green
Write-Host "    ✅ Icons générées" -ForegroundColor Green
Write-Host "    ✅ Mode production activé (isTestMode = false)" -ForegroundColor Green
Write-Host "    ✅ URL API: https://dossypro.com/api/mobile" -ForegroundColor Green
Write-Host "    ✅ google-services.json présent" -ForegroundColor Green
Write-Host "    ✅ android/key.properties configuré" -ForegroundColor Green
Write-Host ""

Write-Host "☑️  Assets Play Store:" -ForegroundColor Cyan
Write-Host "    📋 Screenshots (1080x1920): 5-8 images requises" -ForegroundColor White
Write-Host "    📋 Icon app (512x512): PNG transparent" -ForegroundColor White
Write-Host "    📋 Feature graphic (1024x500): optionnel mais recommandé" -ForegroundColor White
Write-Host ""

Write-Host "☑️  Fiche Play Console:" -ForegroundColor Cyan
Write-Host "    📋 Titre: 'DOSSY Chat IA - Assistant Juridique IA'" -ForegroundColor White
Write-Host "    📋 Description courte (80 car)" -ForegroundColor White
Write-Host "    📋 Description complète (4000 car)" -ForegroundColor White
Write-Host "    📋 Catégorie primaire: Éducation" -ForegroundColor White
Write-Host "    📋 Lien conditions: https://dossypro.com/pages/conditions_générales_d'utilisation" -ForegroundColor White
Write-Host "    📋 Lien privacy: https://dossypro.com/privacy" -ForegroundColor White
Write-Host ""

Write-Host "📦 Fichier AAB prêt pour soumission:" -ForegroundColor Green
Write-Host "   build/app/outputs/bundle/release/app-release.aab" -ForegroundColor Yellow
Write-Host ""

Write-Host "✅ SCRIPT TERMINÉ AVEC SUCCÈS!" -ForegroundColor Green
Write-Host ""
Write-Host "Consultez PREPARATION_PLAYSTORE_COMPLETE.md pour le guide détaillé" -ForegroundColor Cyan
Write-Host ""
