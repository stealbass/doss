@echo off
cd /d "c:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer\dossy_chat_ia\lib\screens\legal_library"
copy /Y legal_library_screen.dart legal_library_screen_old.dart
copy /Y legal_library_screen_new.dart legal_library_screen.dart
echo.
echo ✓ Fichier legal_library_screen.dart remplacé avec succès!
echo ✓ Ancien fichier sauvegardé dans legal_library_screen_old.dart
echo.
pause
