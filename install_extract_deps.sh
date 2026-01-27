#!/usr/bin/env bash
set -euo pipefail

# Paquets systeme pour l'extraction de PDF/Word/Excel
sudo apt-get update
sudo apt-get install -y poppler-utils libreoffice-core libreoffice-common

# Dependances PHP pour l'extraction
composer require smalot/pdfparser phpoffice/phpword phpoffice/phpspreadsheet --no-dev

# Regeneration de l'autoload optimise
composer dump-autoload -o

echo "OK - dependances installees. Lance ensuite : php test_extraction.php"
