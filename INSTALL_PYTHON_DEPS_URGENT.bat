@echo off
REM INSTALL_PYTHON_DEPS_URGENT.bat
REM Automatic Python dependency installation for Windows

setlocal enabledelayedexpansion

echo.
echo =========================================
echo Installing Python Document Dependencies
echo =========================================
echo.

REM Check if Python is available
python --version >nul 2>&1
if %errorlevel% neq 0 (
    python3 --version >nul 2>&1
    if %errorlevel% neq 0 (
        echo ERROR: Python not found. Please install Python 3.7+ first.
        pause
        exit /b 1
    )
    set PYTHON_CMD=python3
) else (
    set PYTHON_CMD=python
)

echo Using Python: %PYTHON_CMD%
%PYTHON_CMD% --version

REM Update pip
echo.
echo [1/7] Updating pip...
%PYTHON_CMD% -m pip install --upgrade pip -q

REM Core PDF libraries
echo [2/7] Installing PDF processing libraries...
%PYTHON_CMD% -m pip install PyPDF2 -q
%PYTHON_CMD% -m pip install pdfplumber -q

echo [3/7] Installing Office document libraries...
%PYTHON_CMD% -m pip install python-docx -q
%PYTHON_CMD% -m pip install openpyxl -q
%PYTHON_CMD% -m pip install python-pptx -q

echo [4/7] Installing MySQL connector...
%PYTHON_CMD% -m pip install mysql-connector-python -q 2>nul || (
    echo WARNING: MySQL connector optional
)

echo [5/7] Installing OCR and image libraries...
%PYTHON_CMD% -m pip install Pillow -q
%PYTHON_CMD% -m pip install pytesseract -q 2>nul || (
    echo WARNING: Tesseract optional
)

echo [6/7] Installing utility libraries...
%PYTHON_CMD% -m pip install requests -q
%PYTHON_CMD% -m pip install python-dotenv -q

echo.
echo =========================================
echo [7/7] Verifying installations...
echo =========================================
echo.

REM Verification script
set VERIFY_SCRIPT=%temp%\verify_python.py
(
    echo import sys
    echo failed = []
    echo packages = [
    echo     ("PyPDF2", "PyPDF2"^),
    echo     ("pdfplumber", "pdfplumber"^),
    echo     ("python-docx", "docx"^),
    echo     ("openpyxl", "openpyxl"^),
    echo     ("python-pptx", "pptx"^),
    echo     ("Pillow", "PIL"^),
    echo     ("requests", "requests"^),
    echo ]
    echo for name, import_name in packages:
    echo     try:
    echo         __import__(import_name^)
    echo         print(f"  [OK] {name}"^)
    echo     except ImportError:
    echo         print(f"  [FAIL] {name}"^)
    echo         failed.append(name^)
    echo if not failed:
    echo     print("\n[SUCCESS] All core packages installed!"^)
) > "%VERIFY_SCRIPT%"

%PYTHON_CMD% "%VERIFY_SCRIPT%"
del "%VERIFY_SCRIPT%"

echo.
echo =========================================
echo Installation Complete!
echo =========================================
echo.
echo Next steps:
echo   1. Upload a test document in the mobile app
echo   2. Run queue worker: php artisan queue:work --once
echo   3. Check logs: storage/logs/laravel.log
echo.
pause
