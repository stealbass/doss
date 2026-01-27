@echo off
cd /d "%~dp0"
echo Clearing Laravel caches...
php artisan clear-compiled
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
echo.
echo Regenerating autoload files...
composer dump-autoload
echo.
echo Done! Press any key to exit...
pause
