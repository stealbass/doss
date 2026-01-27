#!/bin/bash

echo "==================================================="
echo "INSTALLATION PACKAGES EXTRACTION DE DOCUMENTS"
echo "==================================================="
echo ""

cd /home/threesixty/yyy/Dossy/

echo "[1/4] Installation PhpWord (Word documents)..."
composer require phpoffice/phpword:^1.2

echo ""
echo "[2/4] Installation PhpSpreadsheet (Excel documents)..."
composer require phpoffice/phpspreadsheet:^2.0

echo ""
echo "[3/4] Verification de PdfParser..."
composer show smalot/pdfparser

echo ""
echo "[4/4] Mise a jour autoload..."
composer dump-autoload

echo ""
echo "==================================================="
echo "INSTALLATION TERMINEE !"
echo "==================================================="
echo ""
echo "Maintenant, reessayez l'extraction:"
echo "  php artisan document:retry-extraction 13"
echo "  php artisan queue:work --once"
echo ""
