#!/bin/bash

################################################################################
# Forcer l'extraction de TOUS les documents utilisateur en attente
################################################################################

cd /home/threesixty/yyy/Dossy

export PYTHONPATH=/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/.local/lib/python3.7/site-packages

echo "════════════════════════════════════════════════════════════════════════════"
echo "EXTRACTION FORÇÉE - DOCUMENTS UTILISATEUR"
echo "════════════════════════════════════════════════════════════════════════════"
echo ""

# Récupérer tous les documents sans texte extrait
echo "📋 Documents en attente d'extraction..."
php artisan tinker --execute="
    \$docs = App\Models\SubmittedDocument::where(function(\$q) {
        \$q->whereNull('extracted_text_length')
          ->orWhere('extracted_text_length', 0)
          ->orWhere('extracted_text', '')
          ->orWhereNull('extracted_text');
    })->where('processing_status', 'pending')
      ->orWhere('processing_status', 'failed')
      ->get(['id', 'original_filename', 'file_size', 'processing_status']);
    
    if (\$docs->count() == 0) {
        echo 'Aucun document en attente!' . PHP_EOL;
    } else {
        echo \$docs->count() . ' document(s) en attente:' . PHP_EOL;
        foreach (\$docs as \$doc) {
            \$size_mb = (\$doc->file_size ?? 0) / (1024 * 1024);
            echo sprintf('  ID %3d | %6.1f MB | Status: %-8s | %s' . PHP_EOL,
                \$doc->id,
                \$size_mb,
                \$doc->processing_status,
                substr(\$doc->original_filename, 0, 50)
            );
        }
    }
"

echo ""
echo "════════════════════════════════════════════════════════════════════════════"
echo "⚙️  EXTRACTION EN COURS..."
echo "════════════════════════════════════════════════════════════════════════════"
echo ""

# Récupérer les IDs des documents
DOCS=$(php artisan tinker --execute="
    \$docs = App\Models\SubmittedDocument::where(function(\$q) {
        \$q->whereNull('extracted_text_length')
          ->orWhere('extracted_text_length', 0)
          ->orWhere('extracted_text', '')
          ->orWhereNull('extracted_text');
    })->where('processing_status', 'pending')
      ->orWhere('processing_status', 'failed')
      ->pluck('id')
      ->toArray();
    
    echo implode(' ', \$docs);
" 2>/dev/null | tail -1)

if [ -z "$DOCS" ]; then
    echo "Aucun document à traiter"
    exit 0
fi

SUCCESS=0
FAILED=0

for DOC_ID in $DOCS; do
    echo ""
    echo "─────────────────────────────────────────────────────────────────────────────"
    echo "📄 Document ID: $DOC_ID"
    
    # Récupérer les infos
    INFO=$(php artisan tinker --execute="
        \$doc = App\Models\SubmittedDocument::find($DOC_ID);
        if (\$doc) {
            echo \$doc->original_filename . '|' . (\$doc->file_size ?? 0);
        }
    " 2>/dev/null | tail -1)
    
    FILENAME=$(echo "$INFO" | cut -d'|' -f1)
    FILESIZE=$(echo "$INFO" | cut -d'|' -f2)
    SIZE_MB=$(echo "scale=1; $FILESIZE / 1024 / 1024" | bc)
    
    echo "Fichier: $FILENAME ($SIZE_MB MB)"
    echo ""
    
    # Extraction avec OCR
    if python3 scripts/extract_documents.py \
        --source=submitted \
        --document-id=$DOC_ID \
        --ignore-size \
        --ocr-pages=all \
        --ocr-dpi=120 \
        --force-ocr \
        2>&1 | tail -20; then
        
        # Vérifier le résultat
        CHARS=$(php artisan tinker --execute="
            \$doc = App\Models\SubmittedDocument::find($DOC_ID);
            echo (\$doc->extracted_text_length ?? 0);
        " 2>/dev/null | tail -1)
        
        if [ "$CHARS" -gt 0 ]; then
            echo "✅ Extraction réussie ($CHARS caractères)"
            ((SUCCESS++))
        else
            echo "⚠️  Extraction avec 0 caractères"
            ((FAILED++))
        fi
    else
        echo "❌ Erreur lors de l'extraction"
        ((FAILED++))
    fi
    
    sleep 2
done

echo ""
echo "════════════════════════════════════════════════════════════════════════════"
echo "RÉSUMÉ"
echo "════════════════════════════════════════════════════════════════════════════"
echo "✅ Succès: $SUCCESS"
echo "❌ Échecs: $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "✅ TOUS LES DOCUMENTS ONT ÉTÉ EXTRAITS!"
else
    echo "⚠️  Certains documents nécessitent une attention particulière"
fi

echo ""
