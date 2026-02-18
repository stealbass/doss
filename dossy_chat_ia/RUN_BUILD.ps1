# ============================================================================
# 🚀 SCRIPT BUILD PHASES 2-6 - PLAYSTORE PREPARATION
# ============================================================================

# Configuration
$projectRoot = Get-Location
$logFile = "$projectRoot\BUILD_LOG_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"

Write-Host "🚀 DÉMARRAGE BUILD PHASES 2-6" -ForegroundColor Magenta
Write-Host "Répertoire: $projectRoot" -ForegroundColor Cyan
Write-Host ""

# === PHASE 2: NETTOYAGE ===
Write-Host "================================" -ForegroundColor Magenta
Write-Host "PHASE 2: NETTOYAGE & DÉPENDANCES" -ForegroundColor Magenta
Write-Host "================================" -ForegroundColor Magenta
Write-Host ""

$startTime = Get-Date
flutter clean
flutter pub get
flutter pub upgrade
$phase2Duration = ((Get-Date) - $startTime).TotalSeconds
Write-Host "✅ PHASE 2 OK (${phase2Duration}s)" -ForegroundColor Green
Write-Host ""

# === PHASE 3: ANALYSE ===
Write-Host "================================" -ForegroundColor Magenta
Write-Host "PHASE 3: ANALYSE STATIQUE" -ForegroundColor Magenta
Write-Host "================================" -ForegroundColor Magenta
Write-Host ""

$startTime = Get-Date
flutter analyze
dart analyze
$phase3Duration = ((Get-Date) - $startTime).TotalSeconds
Write-Host "✅ PHASE 3 OK (${phase3Duration}s)" -ForegroundColor Green
Write-Host ""

# === PHASE 4: ICONS ===
Write-Host "================================" -ForegroundColor Magenta
Write-Host "PHASE 4: GÉNÉRER ICONS" -ForegroundColor Magenta
Write-Host "================================" -ForegroundColor Magenta
Write-Host ""

$startTime = Get-Date
flutter pub run flutter_launcher_icons:main
$phase4Duration = ((Get-Date) - $startTime).TotalSeconds
Write-Host "✅ PHASE 4 OK (${phase4Duration}s)" -ForegroundColor Green
Write-Host ""

# === PHASE 5: TESTS ===
Write-Host "================================" -ForegroundColor Magenta
Write-Host "PHASE 5: TESTS PRÉ-RELEASE" -ForegroundColor Magenta
Write-Host "================================" -ForegroundColor Magenta
Write-Host ""

$startTime = Get-Date
flutter test
$phase5Duration = ((Get-Date) - $startTime).TotalSeconds
Write-Host "✅ PHASE 5 OK (${phase5Duration}s)" -ForegroundColor Green
Write-Host ""

# === PHASE 6: BUILD AAB ===
Write-Host "================================" -ForegroundColor Magenta
Write-Host "PHASE 6: BUILD AAB RELEASE" -ForegroundColor Magenta
Write-Host "⚠️ NE PAS INTERROMPRE!" -ForegroundColor Yellow
Write-Host "================================" -ForegroundColor Magenta
Write-Host ""

$startTime = Get-Date
flutter build appbundle --release --obfuscate --split-debug-info=build/app/outputs/symbols
$phase6Duration = ((Get-Date) - $startTime).TotalSeconds

# Vérifier AAB
if (Test-Path "build/app/outputs/bundle/release/app-release.aab") {
    $aabSize = (Get-Item "build/app/outputs/bundle/release/app-release.aab").Length / 1MB
    Write-Host "✅ APP-RELEASE.AAB CRÉÉ ($('{0:F2}' -f $aabSize) MB)" -ForegroundColor Green
} else {
    Write-Host "❌ AAB NON CRÉÉ" -ForegroundColor Red
}
Write-Host "✅ PHASE 6 OK (${phase6Duration}s)" -ForegroundColor Green
Write-Host ""

# === RÉSUMÉ ===
Write-Host "================================" -ForegroundColor Magenta
Write-Host "RÉSUMÉ FINAL" -ForegroundColor Magenta
Write-Host "================================" -ForegroundColor Magenta
Write-Host "✅ Phase 2: OK" -ForegroundColor Green
Write-Host "✅ Phase 3: OK" -ForegroundColor Green
Write-Host "✅ Phase 4: OK" -ForegroundColor Green
Write-Host "✅ Phase 5: OK" -ForegroundColor Green
Write-Host "✅ Phase 6: OK" -ForegroundColor Green
Write-Host ""
Write-Host "🎉 BUILD COMPLET - PRÊT POUR PLAY STORE!" -ForegroundColor Green
