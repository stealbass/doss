@echo off
cd dossy_chat_ia
echo Cleaning Flutter...
flutter clean
echo.
echo Getting dependencies...
flutter pub get
echo.
echo Running Flutter analyze...
flutter analyze
echo.
echo Build complete!
pause
