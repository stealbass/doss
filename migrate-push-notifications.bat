@echo off
echo === Migration de la table push_notifications ===
echo.
echo Ajout de la colonne 'specific_users'...
echo.

php artisan migrate --path=database/migrations/2026_01_05_000001_add_specific_users_to_push_notifications.php

echo.
echo === Migration terminee ! ===
echo.
pause
