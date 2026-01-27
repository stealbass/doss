@echo off
echo === Vidage du cache Laravel ===
echo.

echo [1/5] Vidage du cache de routes...
php artisan route:clear

echo [2/5] Vidage du cache de configuration...
php artisan config:clear

echo [3/5] Vidage du cache applicatif...
php artisan cache:clear

echo [4/5] Vidage du cache des vues...
php artisan view:clear

echo [5/5] Optimisation de l'autoloader...
composer dump-autoload

echo.
echo === Cache vide ! ===
echo La page push-notifications devrait maintenant fonctionner.
echo.
pause
