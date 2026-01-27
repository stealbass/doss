@echo off
echo ========================================
echo Remplacement des ecrans avec categories
echo ========================================
echo.

cd /d "c:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer\dossy_chat_ia\lib\screens"

echo 1. Remplacement de legal_library_screen.dart...
cd legal_library
copy /Y legal_library_screen_new.dart legal_library_screen.dart
if %ERRORLEVEL% EQU 0 (
    echo    SUCCESS: Legal Library remplace!
) else (
    echo    ERREUR: Legal Library non remplace
)
echo.

echo 2. Remplacement de fiscal_resources_list_screen.dart...
cd ..\fiscal_resources
copy /Y fiscal_resources_list_screen_new.dart fiscal_resources_list_screen.dart
if %ERRORLEVEL% EQU 0 (
    echo    SUCCESS: Fiscal Resources remplace!
) else (
    echo    ERREUR: Fiscal Resources non remplace
)
echo.

echo ========================================
echo TERMINE!
echo ========================================
echo.
echo Les 2 ecrans ont maintenant des categories en haut
echo et la liste de documents en bas (comme Templates)
echo.
echo Prochaine etape:
echo   cd dossy_chat_ia
echo   flutter run
echo.
pause
