@echo off
echo ================================================
echo   ACTIVATION DU COMPTE contact@dossypro.com
echo ================================================
echo.

REM Lire les informations de connexion depuis .env
set "ENV_FILE=.env"
if not exist "%ENV_FILE%" (
    echo ERREUR: Fichier .env non trouve
    pause
    exit /b 1
)

echo Lecture de la configuration...
for /f "usebackq tokens=1,2 delims==" %%a in ("%ENV_FILE%") do (
    if "%%a"=="DB_HOST" set DB_HOST=%%b
    if "%%a"=="DB_PORT" set DB_PORT=%%b
    if "%%a"=="DB_DATABASE" set DB_DATABASE=%%b
    if "%%a"=="DB_USERNAME" set DB_USERNAME=%%b
    if "%%a"=="DB_PASSWORD" set DB_PASSWORD=%%b
)

echo.
echo Configuration detectee:
echo   Hote: %DB_HOST%
echo   Port: %DB_PORT%
echo   Base de donnees: %DB_DATABASE%
echo   Utilisateur: %DB_USERNAME%
echo.

REM Créer un fichier SQL temporaire
set "SQL_FILE=temp_activate.sql"
echo -- Activation du compte contact@dossypro.com > "%SQL_FILE%"
echo UPDATE users SET is_active = 1, updated_at = NOW() WHERE email = 'contact@dossypro.com'; >> "%SQL_FILE%"
echo SELECT id, name, email, is_active as active FROM users WHERE email = 'contact@dossypro.com'; >> "%SQL_FILE%"

echo Tentative d'activation du compte...
echo.

REM Essayer avec mysql
where mysql >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Execution avec MySQL...
    mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USERNAME% -p%DB_PASSWORD% %DB_DATABASE% < "%SQL_FILE%"
    goto :success
)

REM Essayer avec php artisan
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Execution avec PHP Artisan...
    php artisan tinker --execute="DB::table('users')->where('email', 'contact@dossypro.com')->update(['is_active' => 1]); echo 'Compte active!'.PHP_EOL;"
    goto :success
)

REM Si aucune commande n'est disponible
echo.
echo ================================================
echo AUCUNE METHODE AUTOMATIQUE DISPONIBLE
echo ================================================
echo.
echo Veuillez executer manuellement cette requete SQL:
echo.
type "%SQL_FILE%"
echo.
echo Via:
echo   - PhpMyAdmin
echo   - MySQL Workbench
echo   - Tout autre client de base de donnees
echo.
goto :end

:success
echo.
echo ================================================
echo   COMPTE ACTIVE AVEC SUCCES!
echo ================================================
echo.
echo Vous pouvez maintenant vous connecter avec:
echo   Email: contact@dossypro.com
echo   Mot de passe: [Votre nouveau mot de passe]
echo.

:end
if exist "%SQL_FILE%" del "%SQL_FILE%"
echo.
pause
