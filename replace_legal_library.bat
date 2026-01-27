@echo off
cd /d "c:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer\dossy_chat_ia\lib\screens\legal_library"
echo Remplacement de legal_library_screen.dart...
copy /Y legal_library_screen_new.dart legal_library_screen.dart
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo SUCCESS: Fichier remplace avec succes!
    echo ========================================
    echo.
) else (
    echo.
    echo ========================================
    echo ERREUR: Le remplacement a echoue
    echo ========================================
    echo.
)
pause
