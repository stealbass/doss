#!/bin/bash

# Script pour corriger et vérifier le traitement automatique des documents

echo "=========================================="
echo "FIX AUTOMATIC DOCUMENT PROCESSING"
echo "=========================================="
echo ""

# Couleurs pour output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Chemin vers le dossier Laravel
cd /home/threesixty/yyy/Dossy

echo -e "${BLUE}Étape 1: Vérification de la configuration queue${NC}"
echo "QUEUE_CONNECTION=$(grep QUEUE_CONNECTION .env | cut -d '=' -f2)"
echo ""

echo -e "${BLUE}Étape 2: Vérification de l'existence de la table jobs${NC}"
php check_queue_status.php
echo ""

echo -e "${BLUE}Étape 3: Vérification si le worker de queue est actif${NC}"
QUEUE_WORKER=$(ps aux | grep "queue:work" | grep -v grep)
if [ -z "$QUEUE_WORKER" ]; then
    echo -e "${RED}❌ PROBLÈME DÉTECTÉ: Aucun worker de queue n'est actif!${NC}"
    echo ""
    echo -e "${YELLOW}Solutions possibles:${NC}"
    echo "1. Lancer manuellement: php artisan queue:work --daemon"
    echo "2. Configurer un cron job:"
    echo "   * * * * * cd /home/threesixty/yyy/Dossy && php artisan schedule:run >> /dev/null 2>&1"
    echo "3. Utiliser Supervisor pour gérer le worker"
    echo ""
else
    echo -e "${GREEN}✅ Worker de queue actif:${NC}"
    echo "$QUEUE_WORKER"
    echo ""
fi

echo -e "${BLUE}Étape 4: Traitement du document 48${NC}"
php artisan documents:process-pending --document=48 --force
echo ""

echo -e "${BLUE}Étape 5: Vérification du résultat${NC}"
php -r "
require 'bootstrap/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

\$doc = DB::table('submitted_documents')->where('id', 48)->first();
if (\$doc) {
    echo 'Document 48:' . PHP_EOL;
    echo '  - Texte extrait: ' . (strlen(\$doc->extracted_text ?? '') > 0 ? 'OUI (' . strlen(\$doc->extracted_text) . ' chars)' : 'NON') . PHP_EOL;
    echo '  - Indexé: ' . (\$doc->is_indexed ?? 0 ? 'OUI' : 'NON') . PHP_EOL;
}
"
echo ""

echo -e "${BLUE}Étape 6: Test du chat avec le document 48${NC}"
echo "Pour tester, utilisez Flutter et posez une question sur le contenu du document."
echo ""

echo -e "${GREEN}=========================================="
echo "FIN DU PROCESSUS DE CORRECTION"
echo "==========================================${NC}"
echo ""

echo -e "${YELLOW}RECOMMANDATIONS:${NC}"
echo "1. Si le worker n'est pas actif, configurez Supervisor ou un cron job"
echo "2. Surveillez les logs: tail -f storage/logs/laravel.log"
echo "3. Pour traiter tous les documents en attente: php artisan documents:process-pending"
echo ""
