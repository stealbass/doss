<?php
// Script d'installation des dependances d'extraction (PDF/Word/Excel)
// A lancer depuis la racine du projet : php install_extract_deps.php

$cmds = [
    'sudo apt-get update',
    'sudo apt-get install -y poppler-utils libreoffice-core libreoffice-common',
    'composer require smalot/pdfparser phpoffice/phpword phpoffice/phpspreadsheet --no-dev',
    'composer dump-autoload -o',
];

foreach ($cmds as $cmd) {
    echo ">> $cmd\n";
    $out = shell_exec($cmd . ' 2>&1');
    echo $out, "\n";
}

echo "Termine. Lance ensuite : php test_extraction.php\n";
