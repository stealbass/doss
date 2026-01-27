@echo off
cd /d "c:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer\dossy_chat_ia"
echo Cleaning build...
call flutter clean
echo.
echo Getting dependencies...
call flutter pub get
echo.
echo Running analyze...
call flutter analyze
echo.
echo Done!
pause
