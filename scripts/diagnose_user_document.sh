#!/bin/bash

################################################################################
# Script pour diagnostiquer et corriger l'extraction des documents utilisateur
################################################################################

cd /home/threesixty/yyy/Dossy

export PYTHONPATH=/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/.local/lib/python3.7/site-packages

DOC_ID=${1:-48}

echo "════════════════════════════════════════════════════════════════════════════"
echo "DIAGNOSTIC - Document utilisateur ID: $DOC_ID"
echo "════════════════════════════════════════════════════════════════════════════"
echo ""

# 1. Vérifier l'existence du document dans la BD
echo "1️⃣  Vérification dans la base de données..."
php artisan tinker --execute="
    \$doc = App\Models\SubmittedDocument::find($DOC_ID);
    if (\$doc) {
        echo 'Trouvé!' . PHP_EOL;
        echo '  - ID: ' . \$doc->id . PHP_EOL;
        echo '  - Fichier: ' . \$doc->original_filename . PHP_EOL;
        echo '  - Utilisateur: ' . \$doc->user_id . PHP_EOL;
        echo '  - Status: ' . \$doc->processing_status . PHP_EOL;
        echo '  - Texte extrait: ' . (\$doc->extracted_text_length ?? 0) . ' caractères' . PHP_EOL;
        echo '  - Stockage: ' . \$doc->storage_path . PHP_EOL;
    } else {
        echo 'Document non trouvé!' . PHP_EOL;
    }
"

echo ""
echo "2️⃣  Tentative d'extraction du texte..."

# Récupérer le path du fichier
FILE_PATH=$(php artisan tinker --execute="
    \$doc = App\Models\SubmittedDocument::find($DOC_ID);
    if (\$doc && \$doc->storage_path) {
        echo \$doc->storage_path;
    }
" 2>/dev/null | tail -1)

if [ -z "$FILE_PATH" ]; then
    echo "❌ Impossible de récupérer le chemin du fichier"
    exit 1
fi

echo "Path trouvé: $FILE_PATH"

# Essayer d'extraire via le script Python
echo ""
echo "3️⃣  Extraction avec OCR forcé..."
python3 scripts/extract_documents.py \
    --source=submitted \
    --document-id=$DOC_ID \
    --ignore-size \
    --ocr-pages=all \
    --ocr-dpi=120 \
    --force-ocr \
    2>&1 | head -100

echo ""
echo "4️⃣  Vérification du résultat..."
php artisan tinker --execute="
    \$doc = App\Models\SubmittedDocument::find($DOC_ID);
    if (\$doc) {
        \$chars = \$doc->extracted_text_length ?? 0;
        if (\$chars > 0) {
            echo '✅ Extraction réussie: ' . \$chars . ' caractères' . PHP_EOL;
            echo 'Premiers 300 caractères:' . PHP_EOL;
            echo str_repeat('-', 80) . PHP_EOL;
            echo substr(\$doc->extracted_text, 0, 300) . '...' . PHP_EOL;
            echo str_repeat('-', 80) . PHP_EOL;
        } else {
            echo '❌ Extraction échouée (0 caractères)' . PHP_EOL;
        }
    }
"

echo ""
echo "5️⃣  Tentative d'indexation Pinecone..."
php artisan tinker --execute="
    \$doc = App\Models\SubmittedDocument::find($DOC_ID);
    if (\$doc && (\$doc->extracted_text_length ?? 0) > 0) {
        try {
            // Dispatcher le job d'indexation
            App\Jobs\ProcessDocumentForRAG::dispatch($DOC_ID, 'submitted');
            echo '✅ Job d'indexation lancé' . PHP_EOL;
        } catch (\Exception \$e) {
            echo '❌ Erreur: ' . \$e->getMessage() . PHP_EOL;
        }
    } else {
        echo '❌ Pas de texte extrait, impossible d\'indexer' . PHP_EOL;
    }
"

echo ""
echo "════════════════════════════════════════════════════════════════════════════"
