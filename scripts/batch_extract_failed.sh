#!/bin/bash
# Script pour extraire les documents qui ont échoué avec gestion mémoire progressive
# Usage: ./batch_extract_failed.sh

export PYTHONPATH=/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/.local/lib/python3.7/site-packages

# Documents problématiques identifiés (sans texte extrait)
FAILED_IDS=(54 55 74 75 104 191 217 234 249 250 256 260 269 282 283)

echo "==================================================================="
echo "Extraction batch des documents échoués avec OCR progressif"
echo "Total: ${#FAILED_IDS[@]} documents"
echo "==================================================================="

# Fonction pour extraire avec stratégie adaptative
extract_with_strategy() {
    local doc_id=$1
    local size_mb=$2
    
    echo ""
    echo "--- Document ID: $doc_id (taille estimée: ${size_mb}MB) ---"
    
    # Stratégie basée sur la taille
    if [ "$size_mb" -lt 5 ]; then
        # Petits fichiers: extraction complète haute qualité
        echo "Stratégie: Petit fichier - OCR complet haute qualité"
        python3 /home/threesixty/yyy/Dossy/scripts/extract_documents.py \
            --source=legal \
            --document-id=$doc_id \
            --ignore-size \
            --ocr-pages=all \
            --ocr-lang=fra+eng \
            --ocr-dpi=150 \
            --ocr-delay=0.0 \
            --force-ocr
    elif [ "$size_mb" -lt 10 ]; then
        # Moyens: OCR complet avec délai
        echo "Stratégie: Fichier moyen - OCR complet avec pause"
        python3 /home/threesixty/yyy/Dossy/scripts/extract_documents.py \
            --source=legal \
            --document-id=$doc_id \
            --ignore-size \
            --ocr-pages=all \
            --ocr-lang=fra+eng \
            --ocr-dpi=130 \
            --ocr-delay=0.5 \
            --force-ocr
    elif [ "$size_mb" -lt 20 ]; then
        # Grands: limitation pages + pause longue
        echo "Stratégie: Grand fichier - 100 premières pages"
        python3 /home/threesixty/yyy/Dossy/scripts/extract_documents.py \
            --source=legal \
            --document-id=$doc_id \
            --ignore-size \
            --ocr-pages=100 \
            --ocr-lang=fra+eng \
            --ocr-dpi=110 \
            --ocr-delay=1.0 \
            --force-ocr
    else
        # Très grands: traitement conservateur
        echo "Stratégie: Très grand fichier - 50 premières pages DPI réduit"
        python3 /home/threesixty/yyy/Dossy/scripts/extract_documents.py \
            --source=legal \
            --document-id=$doc_id \
            --ignore-size \
            --ocr-pages=50 \
            --ocr-lang=fra+eng \
            --ocr-dpi=90 \
            --ocr-delay=2.0 \
            --force-ocr
    fi
    
    local exit_code=$?
    
    if [ $exit_code -eq 0 ]; then
        echo "✓ Document $doc_id extrait avec succès"
    else
        echo "✗ ERREUR: Document $doc_id échoué (code: $exit_code)"
    fi
    
    # Pause entre documents pour libérer mémoire
    sleep 5
}

# Tailles estimées des documents (MB)
declare -A SIZES=(
    [54]=6
    [55]=4
    [74]=6
    [75]=22
    [104]=5
    [191]=8
    [217]=8
    [234]=21
    [249]=2
    [250]=3
    [256]=7
    [260]=20
    [269]=26
    [282]=33
    [283]=21
)

# Traitement séquentiel avec stratégies adaptées
for doc_id in "${FAILED_IDS[@]}"; do
    size=${SIZES[$doc_id]:-5}
    extract_with_strategy $doc_id $size
done

echo ""
echo "==================================================================="
echo "Extraction batch terminée"
echo "==================================================================="
echo ""
echo "Vérification des résultats:"
echo ""

# Vérifier combien ont été extraits
cd /home/threesixty/yyy/Dossy
for doc_id in "${FAILED_IDS[@]}"; do
    result=$(php artisan tinker --execute "echo DB::table('legal_documents')->where('id',$doc_id)->value('extracted_text_length');" 2>/dev/null)
    if [ -n "$result" ] && [ "$result" != "null" ]; then
        echo "✓ ID $doc_id: $result caractères extraits"
    else
        echo "✗ ID $doc_id: Aucun texte (à retraiter manuellement)"
    fi
done

echo ""
echo "Pour retraiter un document individuellement:"
echo "PYTHONPATH=... python3 scripts/extract_documents.py --source=legal --document-id=ID --ignore-size --ocr-pages=30 --ocr-dpi=80 --ocr-delay=3.0 --force-ocr"
