@echo off
echo ================================
echo DOSSY Chat IA - Font Downloader
echo ================================
echo.
echo Ce script va telecharger les fonts Poppins depuis Google Fonts
echo.

cd /d "%~dp0fonts"

echo Telechargement des fonts Poppins...
echo.
echo IMPORTANT: Ce script necessite PowerShell et une connexion Internet
echo.
pause

powershell -Command "& {
    $baseUrl = 'https://github.com/google/fonts/raw/main/ofl/poppins'
    $fonts = @(
        'Poppins-Regular.ttf',
        'Poppins-Medium.ttf',
        'Poppins-SemiBold.ttf',
        'Poppins-Bold.ttf'
    )
    
    foreach ($font in $fonts) {
        $url = \"$baseUrl/$font\"
        Write-Host \"Telechargement de $font...\" -ForegroundColor Green
        try {
            Invoke-WebRequest -Uri $url -OutFile $font -UseBasicParsing
            Write-Host \"  ✓ $font telecharge avec succes\" -ForegroundColor Green
        } catch {
            Write-Host \"  ✗ Erreur lors du telechargement de $font\" -ForegroundColor Red
            Write-Host \"    Vous devrez le telecharger manuellement depuis:\" -ForegroundColor Yellow
            Write-Host \"    https://fonts.google.com/specimen/Poppins\" -ForegroundColor Yellow
        }
    }
    
    Write-Host \"\" 
    Write-Host \"Telechargement termine!\" -ForegroundColor Cyan
    Write-Host \"\" 
    Write-Host \"Prochaines etapes:\" -ForegroundColor Yellow
    Write-Host \"1. Verifiez que les 4 fichiers .ttf sont presents dans assets/fonts/\" -ForegroundColor White
    Write-Host \"2. Lancez: flutter clean\" -ForegroundColor White
    Write-Host \"3. Lancez: flutter pub get\" -ForegroundColor White
    Write-Host \"4. Lancez: flutter run -d edge\" -ForegroundColor White
    Write-Host \"\"
}"

echo.
echo ================================
echo Telechargement termine!
echo ================================
echo.
echo Verifiez les fichiers dans le dossier fonts/
echo.
pause
