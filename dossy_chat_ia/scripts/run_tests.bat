@echo off
REM Run Flutter tests with single concurrency to reduce Windows temp-file race issues
cd /d "%~dp0"
flutter test --concurrency=1 %*
