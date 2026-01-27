@echo off
REM ============================================
REM Installation Queue Worker comme Service Windows
REM Nécessite: NSSM (Non-Sucking Service Manager)
REM ============================================

REM Vérifier les droits administrateur
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERREUR: Ce script necessite les droits Administrateur
    echo Clic droit sur le fichier puis "Executer en tant qu'administrateur"
    pause
    exit /b 1
)

REM Configuration
set SERVICE_NAME=DossyQueueWorker
set PHP_PATH=C:\php\php.exe
set ARTISAN_PATH=%~dp0artisan
set WORK_DIR=%~dp0

echo ============================================
echo Installation du Queue Worker comme Service
echo ============================================
echo.
echo Service: %SERVICE_NAME%
echo PHP: %PHP_PATH%
echo Artisan: %ARTISAN_PATH%
echo Dossier: %WORK_DIR%
echo.

REM Vérifier si NSSM est installé
where nssm >nul 2>&1
if %errorLevel% neq 0 (
    echo ERREUR: NSSM n'est pas installe
    echo.
    echo Telechargez NSSM depuis: https://nssm.cc/download
    echo Extrayez nssm.exe dans C:\Windows\System32\
    echo.
    pause
    exit /b 1
)

REM Arrêter le service s'il existe déjà
echo Arret du service existant...
nssm stop %SERVICE_NAME% >nul 2>&1
nssm remove %SERVICE_NAME% confirm >nul 2>&1

REM Installer le nouveau service
echo Installation du service...
nssm install %SERVICE_NAME% "%PHP_PATH%" "artisan queue:work database --tries=3 --timeout=300 --sleep=3"

REM Configuration du service
nssm set %SERVICE_NAME% AppDirectory "%WORK_DIR%"
nssm set %SERVICE_NAME% DisplayName "Dossy Queue Worker"
nssm set %SERVICE_NAME% Description "Service de traitement background pour documents RAG et alertes"
nssm set %SERVICE_NAME% Start SERVICE_AUTO_START

REM Configuration des logs
nssm set %SERVICE_NAME% AppStdout "%WORK_DIR%storage\logs\queue-worker-stdout.log"
nssm set %SERVICE_NAME% AppStderr "%WORK_DIR%storage\logs\queue-worker-stderr.log"

REM Redémarrage automatique en cas d'erreur
nssm set %SERVICE_NAME% AppExit Default Restart
nssm set %SERVICE_NAME% AppRestartDelay 5000

REM Démarrer le service
echo Demarrage du service...
nssm start %SERVICE_NAME%

echo.
echo ============================================
echo Installation terminee!
echo ============================================
echo.
echo Commandes utiles:
echo   Statut:     nssm status %SERVICE_NAME%
echo   Demarrer:   nssm start %SERVICE_NAME%
echo   Arreter:    nssm stop %SERVICE_NAME%
echo   Redemarrer: nssm restart %SERVICE_NAME%
echo   Logs:       storage\logs\queue-worker-stdout.log
echo.
pause
