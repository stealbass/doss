@echo off
echo ========================================
echo TEST PROMPT ENGINEERING AI
echo ========================================
echo.

REM Chercher PHP dans le système
where php >nul 2>nul
if %errorlevel% equ 0 (
    echo Execution avec PHP...
    php test_prompt_engineering.php
    goto :end
)

REM Essayer avec php artisan
echo PHP non trouvé dans PATH, essai avec le script direct...
php test_prompt_engineering.php

:end
pause
