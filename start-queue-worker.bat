@echo off
REM ============================================
REM Queue Worker - Démarrage automatique avec redémarrage
REM ============================================

echo [%date% %time%] Demarrage du Queue Worker...

:loop
REM Lancer le queue worker avec timeout de 5 minutes
php artisan queue:work database --tries=3 --timeout=300 --sleep=3 --max-time=3600

REM Si le worker s'arrête, attendre 5 secondes et redémarrer
echo [%date% %time%] Queue Worker arrete. Redemarrage dans 5 secondes...
timeout /t 5 /nobreak > nul

REM Retour au début de la boucle
goto loop
