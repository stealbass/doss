#!/bin/bash

################################################################################
# EXTRACTION FINALE - APPROCHE PROGRESSIVE ET ROBUSTE
# Extrait tous les documents restants avec retry automatique
################################################################################

set -e

export PYTHONPATH=/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/.local/lib/python3.7/site-packages
cd /home/threesixty/yyy/Dossy

echo "════════════════════════════════════════════════════════════════════════════"
echo "EXTRACTION FINALE DES DOCUMENTS RESTANTS"
echo "════════════════════════════════════════════════════════════════════════════"
echo ""

# Array des documents à traiter
DOCS=(249 250 55 104 54 74 256 191 217 234 75)

SUCCESS=0
FAILED=0
RETRY_FAILED=()

################################################################################
# PHASE 1: PREMIÈRE TENTATIVE avec stratégies de base
################################################################################
echo ""
echo "────────────────────────────────────────────────────────────────────────────"
echo "PHASE 1: Première tentative - Extraction avec OCR forcé"
echo "────────────────────────────────────────────────────────────────────────────"
echo ""

for DOC_ID in "${DOCS[@]}"; do
    echo ""
    echo "📄 Document ID: $DOC_ID"
    
    if python3 scripts/extract_documents.py \
        --source=legal \
        --document-id=$DOC_ID \
        --ignore-size \
        --ocr-pages=all \
        --ocr-dpi=120 \
        --force-ocr \
        2>&1 | head -50; then
        
        # Vérifier si vraiment extrait
        RESULT=$(php artisan tinker --execute="echo (App\Models\LegalDocument::find($DOC_ID)->extracted_text_length ?? 0);" 2>/dev/null | tail -1)
        
        if [ "$RESULT" -gt 0 ] 2>/dev/null; then
            echo "✅ Succès ($RESULT chars)"
            ((SUCCESS++))
        else
            echo "⚠️  Pas d'extraction, ajouter à retry"
            RETRY_FAILED+=($DOC_ID)
            ((FAILED++))
        fi
    else
        echo "❌ Erreur lors de l'extraction"
        RETRY_FAILED+=($DOC_ID)
        ((FAILED++))
    fi
    
    sleep 2
done

################################################################################
# PHASE 2: RETRY avec paramètres réduits (si nécessaire)
################################################################################
if [ ${#RETRY_FAILED[@]} -gt 0 ]; then
    echo ""
    echo "────────────────────────────────────────────────────────────────────────────"
    echo "PHASE 2: Retry - Paramètres réduits pour ${#RETRY_FAILED[@]} documents"
    echo "────────────────────────────────────────────────────────────────────────────"
    echo ""
    
    for DOC_ID in "${RETRY_FAILED[@]}"; do
        echo ""
        echo "🔄 Document ID: $DOC_ID (RETRY)"
        
        if python3 scripts/extract_documents.py \
            --source=legal \
            --document-id=$DOC_ID \
            --ignore-size \
            --ocr-pages=100 \
            --ocr-dpi=90 \
            --ocr-delay=1.0 \
            --force-ocr \
            2>&1 | head -50; then
            
            RESULT=$(php artisan tinker --execute="echo (App\Models\LegalDocument::find($DOC_ID)->extracted_text_length ?? 0);" 2>/dev/null | tail -1)
            
            if [ "$RESULT" -gt 0 ] 2>/dev/null; then
                echo "✅ Succès au retry ($RESULT chars)"
                ((SUCCESS++))
                ((FAILED--))
            else
                echo "⚠️  Toujours pas d'extraction"
            fi
        else
            echo "❌ Erreur au retry"
        fi
        
        sleep 3
    done
fi

################################################################################
# RAPPORT FINAL
################################################################################
echo ""
echo "════════════════════════════════════════════════════════════════════════════"
echo "RAPPORT FINAL"
echo "════════════════════════════════════════════════════════════════════════════"
echo ""
echo "Total documents traités: ${#DOCS[@]}"
echo "✅ Succès: $SUCCESS"
echo "❌ Échecs: $FAILED"
echo ""

if [ $FAILED -gt 0 ]; then
    echo "⚠️  DOCUMENTS ENCORE EN ÉCHEC:"
    for DOC_ID in "${RETRY_FAILED[@]}"; do
        FILENAME=$(php artisan tinker --execute="echo (App\Models\LegalDocument::find($DOC_ID)->file_name ?? 'N/A');" 2>/dev/null | tail -1)
        CHARS=$(php artisan tinker --execute="echo (App\Models\LegalDocument::find($DOC_ID)->extracted_text_length ?? 0);" 2>/dev/null | tail -1)
        echo "  - ID $DOC_ID: $FILENAME ($CHARS chars)"
    done
fi

echo ""
echo "════════════════════════════════════════════════════════════════════════════"

if [ $FAILED -eq 0 ]; then
    echo "✅ SUCCÈS! TOUS LES DOCUMENTS ONT ÉTÉ EXTRAITS"
    exit 0
else
    echo "⚠️  $FAILED document(s) restent en échec"
    exit 1
fi
