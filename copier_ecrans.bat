@echo off
echo Copie des fichiers corriges...
echo.

cd /d "c:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer\dossy_chat_ia\lib\screens"

echo 1. Fiscal Resources...
cd fiscal_resources
copy /Y fiscal_resources_list_screen_new.dart fiscal_resources_list_screen.dart
if %ERRORLEVEL% EQU 0 (
    echo    OK - Fiscal Resources remplace
) else (
    echo    ERREUR
)

echo.
echo 2. Legal Library...
cd ..\legal_library
copy /Y legal_library_screen_new.dart legal_library_screen.dart
if %ERRORLEVEL% EQU 0 (
    echo    OK - Legal Library remplace
) else (
    echo    ERREUR
)

echo.
echo TERMINE! Maintenant executez: flutter run
pause
