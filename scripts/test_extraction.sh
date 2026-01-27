#!/bin/bash

################################################################################
# Script de test simple pour vérifier l'extraction/OCR
# Lance une extraction sur UN document avec logs verbeux
################################################################################

cd /home/threesixty/yyy/Dossy

if [ $# -lt 1 ]; then
    echo "Usage: $0 <document_id> [options]"
    echo ""
    echo "Exemples:"
    echo "  $0 249                                    # Test simple sur doc 249"
    echo "  $0 75 --ocr-pages=40 --ocr-dpi=85       # Test doc 75 avec params custom"
    echo ""
    exit 1
fi

DOC_ID=$1
shift

export PYTHONPATH=/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/.local/lib/python3.7/site-packages

echo "════════════════════════════════════════════════════════════════════════════"
echo "TEST D'EXTRACTION - Document ID: $DOC_ID"
echo "════════════════════════════════════════════════════════════════════════════"
echo ""

# Lancer l'extraction avec --force-ocr
python3 scripts/extract_documents.py \
    --source=legal \
    --document-id=$DOC_ID \
    --ignore-size \
    --force-ocr \
    "$@" \
    2>&1 | tee test_extraction_${DOC_ID}.log

echo ""
echo "════════════════════════════════════════════════════════════════════════════"
echo "Résultat dans la base de données:"
echo "════════════════════════════════════════════════════════════════════════════"
echo ""

php artisan tinker --execute="
    \$doc = App\Models\LegalDocument::find($DOC_ID);
    if (\$doc) {
        echo 'ID: ' . \$doc->id . PHP_EOL;
        echo 'Fichier: ' . \$doc->file_name . PHP_EOL;
        echo 'Taille fichier: ' . round((\$doc->file_size ?? 0) / 1024 / 1024, 1) . ' MB' . PHP_EOL;
        echo 'Texte extrait: ' . (\$doc->extracted_text_length ?? 0) . ' caractères' . PHP_EOL;
        if ((\$doc->extracted_text_length ?? 0) > 0) {
            echo 'Premiers 200 caractères: ' . PHP_EOL;
            echo str_repeat('-', 80) . PHP_EOL;
            echo substr(\$doc->extracted_text, 0, 200) . '...' . PHP_EOL;
            echo str_repeat('-', 80) . PHP_EOL;
        }
    } else {
        echo 'Document non trouvé!' . PHP_EOL;
    }
"

echo ""
echo "Log sauvegardé: test_extraction_${DOC_ID}.log"
