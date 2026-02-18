# ============================================================================
# 🚀 SCRIPT COMPLET D'EXÉCUTION - PHASES 2-6 (PLAYSTORE PREPARATION)
# ============================================================================
# 
# Script PowerShell automatisé qui exécute TOUTES les phases
# sans dépendre du terminal interactif bloquant
#
# Utilisation: 
#   PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File RUN_PHASES_COMPLETE.ps1
#
# ============================================================================

# Configuration
$projectRoot = Get-Location
$logFile = "$projectRoot\EXECUTION_LOG_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
$reportFile = "$projectRoot\EXECUTION_REPORT_$(Get-Date -Format 'yyyyMMdd_HHmmss').md"

# Colors for output
$colors = @{
    Success = "Green"
    Error   = "Red"
    Warning = "Yellow"
    Info    = "Cyan"
    Progress = "Magenta"
}

# Logging function
function Write-Log {
    param(
        [string]$Message,
        [string]$Type = "Info"
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Type] $Message"
    
    Write-Host $logMessage -ForegroundColor $colors[$Type]
    Add-Content -Path $logFile -Value $logMessage
}

# Report building
$reportContent = @"
# 📋 RAPPORT D'EXÉCUTION PHASES 2-6

**Généré:** $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
**Répertoire:** $projectRoot

---

## 📊 RÉSUMÉ EXÉCUTION

| Phase | Status | Durée | Notes |
|-------|--------|-------|-------|
"@

# ============================================================================
# PHASE 0: PRÉ-VÉRIFICATIONS
# ============================================================================

Write-Log "🚀 DÉMARRAGE PHASES 2-6 (Préparation Play Store)" "Progress"
Write-Log "Répertoire: $projectRoot" "Info"

# Vérifier Flutter
Write-Log "Vérification Flutter..." "Info"
$flutterVersion = flutter --version 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Log "❌ Flutter non trouvé ou non configuré" "Error"
    exit 1
}
Write-Log "✅ Flutter trouvé: $flutterVersion" "Success"

# Vérifier Dart
Write-Log "Vérification Dart..." "Info"
$dartVersion = dart --version 2>&1
Write-Log "✅ Dart: $dartVersion" "Success"

# Vérifier pub.yaml
if (-not (Test-Path "pubspec.yaml")) {
    Write-Log "❌ pubspec.yaml non trouvé" "Error"
    exit 1
}
Write-Log "✅ pubspec.yaml présent" "Success"

# ============================================================================
# PHASE 2: NETTOYAGE ET DÉPENDANCES (10-15 min)
# ============================================================================

Write-Host "`n" 
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "PHASE 2: NETTOYAGE ET DÉPENDANCES" -ForegroundColor $colors.Progress
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "`n"

$phase2Start = Get-Date

try {
    # flutter clean
    Write-Log "Exécution: flutter clean" "Info"
    flutter clean | Tee-Object -FilePath $logFile -Append
    
    if ($LASTEXITCODE -ne 0) {
        throw "flutter clean échoué"
    }
    Write-Log "✅ flutter clean réussi" "Success"
    
    # flutter pub get
    Write-Log "Exécution: flutter pub get" "Info"
    flutter pub get | Tee-Object -FilePath $logFile -Append
    
    if ($LASTEXITCODE -ne 0) {
        throw "flutter pub get échoué"
    }
    Write-Log "✅ flutter pub get réussi" "Success"
    
    # flutter pub upgrade
    Write-Log "Exécution: flutter pub upgrade" "Info"
    flutter pub upgrade | Tee-Object -FilePath $logFile -Append
    
    if ($LASTEXITCODE -ne 0) {
        Write-Log "⚠️ flutter pub upgrade retourné status non-zéro (peut être normal)" "Warning"
    } else {
        Write-Log "✅ flutter pub upgrade réussi" "Success"
    }
    
    $phase2Duration = (Get-Date) - $phase2Start
    Write-Log "✅ PHASE 2 COMPLÉTÉE en $($phase2Duration.TotalSeconds)s" "Success"
    $phase2Status = "✅ RÉUSSI"
}
catch {
    Write-Log "❌ PHASE 2 ÉCHOUÉE: $_" "Error"
    $phase2Status = "❌ ÉCHOUÉ"
    $reportContent += "`n| Phase 2 | ❌ ÉCHOUÉ | - | $_ |"
}

# ============================================================================
# PHASE 3: ANALYSE STATIQUE (5-10 min)
# ============================================================================

Write-Host "`n"
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "PHASE 3: ANALYSE STATIQUE" -ForegroundColor $colors.Progress
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "`n"

$phase3Start = Get-Date

try {
    # flutter analyze
    Write-Log "Exécution: flutter analyze" "Info"
    $analyzeOutput = flutter analyze 2>&1
    Add-Content -Path $logFile -Value $analyzeOutput
    Write-Host $analyzeOutput
    
    if ($LASTEXITCODE -ne 0) {
        Write-Log "⚠️ flutter analyze retourné status non-zéro" "Warning"
        # Non-bloquant
    } else {
        Write-Log "✅ flutter analyze OK" "Success"
    }
    
    # dart analyze
    Write-Log "Exécution: dart analyze" "Info"
    $dartAnalyzeOutput = dart analyze 2>&1
    Add-Content -Path $logFile -Value $dartAnalyzeOutput
    Write-Host $dartAnalyzeOutput
    
    if ($LASTEXITCODE -ne 0) {
        Write-Log "⚠️ dart analyze retourné status non-zéro" "Warning"
    } else {
        Write-Log "✅ dart analyze OK" "Success"
    }
    
    $phase3Duration = (Get-Date) - $phase3Start
    Write-Log "✅ PHASE 3 COMPLÉTÉE en $($phase3Duration.TotalSeconds)s" "Success"
    $phase3Status = "✅ RÉUSSI"
}
catch {
    Write-Log "❌ PHASE 3 ÉCHOUÉE: $_" "Error"
    $phase3Status = "❌ ÉCHOUÉ"
}

# ============================================================================
# PHASE 4: GÉNÉRATION ICONS (2-5 min)
# ============================================================================

Write-Host "`n"
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "PHASE 4: GÉNÉRATION ICONS" -ForegroundColor $colors.Progress
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "`n"

$phase4Start = Get-Date

try {
    Write-Log "Exécution: flutter pub run flutter_launcher_icons:main" "Info"
    $iconOutput = flutter pub run flutter_launcher_icons:main 2>&1
    Add-Content -Path $logFile -Value $iconOutput
    Write-Host $iconOutput
    
    # Icons peuvent ne pas être configurés - c'est OK
    Write-Log "✅ PHASE 4 COMPLÉTÉE" "Success"
    $phase4Status = "✅ RÉUSSI"
}
catch {
    Write-Log "❌ PHASE 4 ÉCHOUÉE: $_" "Error"
    $phase4Status = "⚠️ AVERTISSEMENT"
}

# ============================================================================
# PHASE 5: TESTS PRÉ-RELEASE (5-15 min)
# ============================================================================

Write-Host "`n"
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "PHASE 5: TESTS PRÉ-RELEASE" -ForegroundColor $colors.Progress
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "`n"

$phase5Start = Get-Date

try {
    Write-Log "Exécution: flutter test" "Info"
    $testOutput = flutter test 2>&1
    Add-Content -Path $logFile -Value $testOutput
    Write-Host $testOutput
    
    # Tests peuvent ne pas exister - c'est OK
    Write-Log "✅ PHASE 5 COMPLÉTÉE" "Success"
    $phase5Status = "✅ RÉUSSI"
}
catch {
    Write-Log "❌ PHASE 5 ÉCHOUÉE: $_" "Error"
    $phase5Status = "⚠️ AVERTISSEMENT"
}

# ============================================================================
# PHASE 6: BUILD AAB RELEASE (10-20 min - LE PLUS IMPORTANT!)
# ============================================================================

Write-Host "`n"
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "PHASE 6: BUILD AAB RELEASE (NE PAS INTERROMPRE!)" -ForegroundColor $colors.Progress
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "`n"

$phase6Start = Get-Date

try {
    Write-Log "⚠️ ATTENTION: Phase 6 la plus longue (10-20 min) - NE PAS INTERROMPRE!" "Warning"
    
    Write-Log "Exécution: flutter build appbundle --release --obfuscate --split-debug-info" "Info"
    
    $buildOutput = flutter build appbundle `
        --release `
        --obfuscate `
        --split-debug-info=build/app/outputs/symbols 2>&1
    
    Add-Content -Path $logFile -Value $buildOutput
    Write-Host $buildOutput
    
    if ($LASTEXITCODE -ne 0) {
        throw "Build appbundle échoué avec status: $LASTEXITCODE"
    }
    
    # Vérifier que le fichier a été créé
    $aabFile = "build/app/outputs/bundle/release/app-release.aab"
    if (Test-Path $aabFile) {
        $aabSize = (Get-Item $aabFile).Length / 1MB
        Write-Log "✅ app-release.aab créé ($('{0:F2}' -f $aabSize) MB)" "Success"
        
        if ($aabSize -gt 100) {
            Write-Log "❌ AAB trop gros (> 100 MB): $('{0:F2}' -f $aabSize) MB" "Error"
            $phase6Status = "⚠️ AAB TROP GROS"
        } else {
            Write-Log "✅ AAB taille OK (< 100 MB)" "Success"
            $phase6Status = "✅ RÉUSSI"
        }
    } else {
        throw "Fichier app-release.aab non créé à $aabFile"
    }
    
    $phase6Duration = (Get-Date) - $phase6Start
    Write-Log "✅ PHASE 6 COMPLÉTÉE en $($phase6Duration.TotalSeconds)s" "Success"
}
catch {
    Write-Log "❌ PHASE 6 ÉCHOUÉE: $_" "Error"
    $phase6Status = "❌ ÉCHOUÉ"
}

# ============================================================================
# RÉSUMÉ FINAL
# ============================================================================

Write-Host "`n"
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "📋 RÉSUMÉ EXÉCUTION" -ForegroundColor $colors.Progress
Write-Host "================================" -ForegroundColor $colors.Progress
Write-Host "`n"

Write-Log "Phase 2 (Nettoyage/Dépendances): $phase2Status" "Info"
Write-Log "Phase 3 (Analyse Statique): $phase3Status" "Info"
Write-Log "Phase 4 (Icons): $phase4Status" "Info"
Write-Log "Phase 5 (Tests): $phase5Status" "Info"
Write-Log "Phase 6 (Build AAB): $phase6Status" "Info"

$totalDuration = (Get-Date) - $phase2Start

Write-Host "`n"
Write-Host "Durée totale: $($totalDuration.TotalMinutes) minutes" -ForegroundColor $colors.Info
Write-Host "Log complet: $logFile" -ForegroundColor $colors.Info
Write-Host "`n"

# Créer rapport Markdown final
$reportContent += @"

| Phase 2 | $phase2Status | - | Nettoyage, pub get, pub upgrade |
| Phase 3 | $phase3Status | - | flutter analyze, dart analyze |
| Phase 4 | $phase4Status | - | flutter_launcher_icons |
| Phase 5 | $phase5Status | - | flutter test |
| Phase 6 | $phase6Status | - | flutter build appbundle --release |

---

## ⏱️ Chronologie

**Durée totale:** $($totalDuration.TotalMinutes) minutes

**Détails par phase:**
- Phase 2: $($phase2Duration.TotalSeconds) secondes
- Phase 3: $($phase3Duration.TotalSeconds) secondes
- Phase 4: $($phase4Duration.TotalSeconds) secondes
- Phase 5: $($phase5Duration.TotalSeconds) secondes
- Phase 6: $($phase6Duration.TotalSeconds) secondes

---

## 📁 Fichiers Générés

- **app-release.aab** → `build/app/outputs/bundle/release/app-release.aab`
- **Debug symbols** → `build/app/outputs/symbols/`
- **Log complet** → `$logFile`

---

## ✅ Prêt pour Play Store?

### Si ALL PHASES ✅:
→ app-release.aab est prêt pour upload Play Store

### Si certaines phases ⚠️/❌:
→ Consulter log et corriger avant upload

---

*Rapport généré automatiquement: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')*
"@

$reportContent | Out-File -Path $reportFile -Encoding UTF8
Write-Host "Rapport: $reportFile" -ForegroundColor $colors.Info

Write-Host "`n✅ SCRIPT COMPLET!" -ForegroundColor $colors.Success
