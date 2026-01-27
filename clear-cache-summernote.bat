@echo off
echo Nettoyage du cache Laravel...
echo.

cd /d "%~dp0"

echo 1. Cache de configuration...
php artisan config:clear
echo.

echo 2. Cache des routes...
php artisan route:clear
echo.

echo 3. Cache des vues...
php artisan view:clear
echo.

echo 4. Cache de l'application...
php artisan cache:clear
echo.

echo ===================================
echo Cache vide avec succes!
echo ===================================
echo.
echo Vous pouvez maintenant tester l'upload d'images dans Summernote.
echo.
pause
