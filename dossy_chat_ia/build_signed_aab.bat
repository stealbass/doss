@echo off
setlocal EnableExtensions EnableDelayedExpansion

cd /d "%~dp0"

echo =========================================
echo Dossy Chat IA - Build AAB Release Signe
echo =========================================

if not exist "pubspec.yaml" (
  echo [ERREUR] Lance ce script depuis le dossier dossy_chat_ia.
  exit /b 1
)

if not exist "android\key.properties" (
  echo [ERREUR] Fichier android\key.properties introuvable.
  exit /b 1
)

set "STORE_FILE="
for /f "tokens=1,* delims==" %%A in ('findstr /b /c:"storeFile=" "android\key.properties"') do set "STORE_FILE=%%B"

if "%STORE_FILE%"=="" (
  echo [ERREUR] storeFile non defini dans android\key.properties.
  exit /b 1
)

if exist "%STORE_FILE%" (
  set "KEYSTORE_OK=1"
) else (
  if exist "android\%STORE_FILE%" (
    set "KEYSTORE_OK=1"
  ) else (
    set "KEYSTORE_OK=0"
  )
)

if "%KEYSTORE_OK%"=="0" (
  echo [ERREUR] Keystore introuvable: %STORE_FILE%
  exit /b 1
)

set "FLUTTER_CMD=flutter"
where flutter >nul 2>nul
if errorlevel 1 (
  if exist "C:\flutter\bin\flutter.bat" (
    set "FLUTTER_CMD=C:\flutter\bin\flutter.bat"
  ) else (
    echo [ERREUR] Flutter n'est pas dans PATH et C:\flutter\bin\flutter.bat introuvable.
    exit /b 1
  )
)

for /f "tokens=2 delims=:" %%V in ('findstr /b /c:"version:" pubspec.yaml') do set "APP_VERSION=%%V"
set "APP_VERSION=%APP_VERSION: =%"

if "%APP_VERSION%"=="" (
  set "APP_VERSION=unknown"
)

echo [1/2] flutter pub get
call "%FLUTTER_CMD%" pub get
if errorlevel 1 (
  echo [ERREUR] flutter pub get a echoue.
  exit /b 1
)

echo [2/2] flutter build appbundle --release
call "%FLUTTER_CMD%" build appbundle --release
if errorlevel 1 (
  echo [ERREUR] Build release AAB a echoue.
  exit /b 1
)

set "AAB_PATH=build\app\outputs\bundle\release\app-release.aab"
if not exist "%AAB_PATH%" (
  set "AAB_PATH=android\app\build\outputs\bundle\release\app-release.aab"
)

if not exist "%AAB_PATH%" (
  echo [ERREUR] AAB non trouve apres build.
  exit /b 1
)

set "OUTPUT_FILE=..\dossy_chat_ia_v%APP_VERSION%_release.aab"
copy /Y "%AAB_PATH%" "%OUTPUT_FILE%" >nul

if errorlevel 1 (
  echo [ERREUR] Impossible de copier l'AAB vers %OUTPUT_FILE%
  exit /b 1
)

echo =========================================
echo SUCCES

echo AAB genere: %AAB_PATH%
echo Copie finale: %OUTPUT_FILE%
echo Version: %APP_VERSION%
echo =========================================

exit /b 0
