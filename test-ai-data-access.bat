@echo off
echo ========================================
echo VERIFICATION ACCES IA AUX DONNEES
echo ========================================
echo.

REM Chercher PHP dans le système
where php >nul 2>nul
if %errorlevel% equ 0 (
    echo Execution avec PHP...
    php test_ai_data_access.php
    goto :end
)

REM Essayer avec php artisan
echo PHP non trouvé dans PATH, essai avec le script direct...
php test_ai_data_access.php

:end
pause
