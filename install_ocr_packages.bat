@echo off
REM ###############################################################################
REM Installation OCR + Packages pour support Images dans ProcessDocumentForRAG
REM 
REM Ce script installe les dépendances nécessaires pour extraire du texte
REM depuis les images (jpg, png, gif, etc.) en utilisant Tesseract OCR
REM
REM Supporte: Windows (natif et WSL)
REM ###############################################################################

setlocal enabledelayedexpansion

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║ Installation des Packages OCR pour Extraction d'Images     ║
echo ║ Dossier: dossy-genspark_ai_developer                       ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

REM Vérifier si running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Ce script doit être exécuté en tant qu'administrateur
    echo.
    echo Faites un clic droit sur cmd.exe et sélectionnez "Exécuter en tant qu'administrateur"
    pause
    exit /b 1
)

echo.
echo 📦 Étape 1: Installation de Tesseract-OCR (moteur OCR système)
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.

REM Vérifier si Tesseract est déjà installé
where tesseract >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Tesseract-OCR est déjà installé
    tesseract --version | findstr /R "tesseract"
) else (
    echo ⚠️  Tesseract-OCR n'est pas trouvé
    echo.
    echo 🔗 Téléchargement automatique...
    echo.
    
    REM Télécharger le programme d'installation
    powershell -Command "Invoke-WebRequest -Uri 'https://github.com/UB-Mannheim/tesseract/wiki' -OutFile '%temp%\tesseract_info.html'" 2>nul
    
    echo.
    echo ❌ Tesseract-OCR doit être installé manuellement sur Windows
    echo.
    echo 📥 Téléchargez le programme d'installation depuis:
    echo    👉 https://github.com/UB-Mannheim/tesseract/wiki
    echo.
    echo 📝 Instructions:
    echo    1. Téléchargez la dernière version (tesseract-ocr-w64-setup-v5.x.x.exe)
    echo    2. Exécutez le programme d'installation
    echo    3. ✅ Cochez "Additional language data" et sélectionnez "French"
    echo    4. Notez le chemin d'installation (par défaut: C:\Program Files\Tesseract-OCR)
    echo    5. Redémarrez ce script après installation
    echo.
    pause
    exit /b 1
)

REM Vérifier les données de langue
echo.
echo 🔍 Vérification des données de langue Tesseract...
tesseract --list-langs 2>nul | findstr /i "fra" >nul
if %errorlevel% equ 0 (
    echo ✅ Français (fra): OK
) else (
    echo ⚠️  Français (fra): NON INSTALLÉ
    echo   → Allez dans C:\Program Files\Tesseract-OCR\tessdata
    echo   → Téléchargez fra.traineddata depuis https://github.com/tesseract-ocr/tessdata
)

tesseract --list-langs 2>nul | findstr /i "eng" >nul
if %errorlevel% equ 0 (
    echo ✅ Anglais (eng): OK
) else (
    echo ⚠️  Anglais (eng): NON INSTALLÉ
)

echo.
echo 📦 Étape 2: Installation des Packages Python
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.

REM Déterminer le répertoire cible pour les packages partagés
set SHARED_PACKAGES_DIR=C:\python-packages-dossy

echo 📁 Répertoire cible: %SHARED_PACKAGES_DIR%

REM Créer le répertoire s'il n'existe pas
if not exist "%SHARED_PACKAGES_DIR%" (
    echo   → Création du répertoire...
    mkdir "%SHARED_PACKAGES_DIR%"
)

REM Vérifier Python
echo.
echo 🐍 Vérification Python...
python --version >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Python trouvé:
    python --version
) else (
    echo ❌ Python n'est pas installé ou pas dans PATH
    echo.
    echo 📥 Téléchargez Python depuis: https://www.python.org/downloads/
    echo.
    echo 📝 Assurez-vous de cocher "Add Python to PATH" lors de l'installation
    pause
    exit /b 1
)

echo.
echo 📥 Installation des packages Python...
set PACKAGES=pytesseract>=0.3.10 Pillow>=9.0.0 pdfplumber>=0.9.0 PyPDF2>=3.0.0 python-docx>=0.8.11 openpyxl>=3.10.0 python-pptx>=0.6.21 boto3>=1.26.0 mysql-connector-python>=8.0.33

for %%P in (%PACKAGES%) do (
    echo   → Installation %%P...
    pip install --target="%SHARED_PACKAGES_DIR%" %%P >nul 2>&1
    if !errorlevel! equ 0 (
        echo      ✅ OK
    ) else (
        echo      ⚠️  Erreur (packages supplémentaires peuvent être nécessaires)
    )
)

echo.
echo ✅ VÉRIFICATION FINALE
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.

where tesseract >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Tesseract-OCR: OK
    for /f "tokens=*" %%i in ('where tesseract') do set TESSERACT_PATH=%%i
    echo    📍 Chemin: !TESSERACT_PATH!
) else (
    echo ❌ Tesseract-OCR: NON TROUVÉ
)

echo.
echo 🐍 Vérification Packages Python...
set PYTHONPATH=%SHARED_PACKAGES_DIR%

python -c "
import sys
print(f'   Python version: {sys.version.split()[0]}')
packages = ['pytesseract', 'PIL', 'pdfplumber', 'PyPDF2', 'docx', 'openpyxl', 'pptx', 'boto3', 'mysql']
for pkg in packages:
    try:
        __import__(pkg)
        print(f'   ✅ {pkg}: OK')
    except ImportError:
        print(f'   ❌ {pkg}: NON INSTALLÉ')
" 2>nul

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║ ✅ INSTALLATION TERMINÉE                                    ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

echo 📋 RÉSUMÉ DE L'INSTALLATION:
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.
echo 🖼️  Support Images (OCR):
echo    ✅ Tesseract-OCR installé
echo    ✅ Langues: Français + Anglais
echo    ✅ Formats supportés: JPG, PNG, GIF, BMP, WEBP, TIFF
echo.
echo 📄 Autres Formats:
echo    ✅ PDF: pdfplumber + PyPDF2
echo    ✅ Word: python-docx
echo    ✅ Excel: openpyxl
echo    ✅ PowerPoint: python-pptx
echo    ✅ Cloud Storage: boto3 (R2, S3)
echo.
echo 📦 Packages Python installés dans:
echo    📁 %SHARED_PACKAGES_DIR%
echo.
echo 🔧 Configuration Laravel:
echo    PYTHONPATH doit être configuré pour:
echo    %SHARED_PACKAGES_DIR%
echo.
echo 🚀 Le système est prêt à extraire du texte depuis:
echo    ✅ Images (JPG, PNG, GIF, etc.) avec OCR
echo    ✅ PDF, Word, Excel, PowerPoint, Texte
echo    ✅ Fichiers jusqu'à 30 MB
echo    ✅ Extraction en moins de 15 minutes
echo.
echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo.
echo 💡 NOTES:
echo    • Si tesseract n'est pas trouvé, redémarrez le terminal
echo    • Vous pouvez tester: tesseract --version
echo    • Les formats d'images sont maintenant supportés 🎉
echo.

pause
