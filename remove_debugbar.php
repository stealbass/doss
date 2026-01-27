<?php
/**
 * Supprimer Barryvdh Debugbar qui cause l'erreur
 * À lancer depuis la racine du projet : php remove_debugbar.php
 */

echo "Suppression de barryvdh/laravel-debugbar...\n";
shell_exec("composer remove barryvdh/laravel-debugbar --no-dev 2>&1 | tail -5");
echo "\n✅ Barryvdh supprimé\n";
echo "Régénération de l'autoload...\n";
shell_exec("composer dump-autoload -o 2>&1");
echo "✅ Autoload régénéré\n";
echo "\nLe site devrait fonctionner maintenant.\n";
