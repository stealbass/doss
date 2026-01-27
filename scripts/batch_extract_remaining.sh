#!/bin/bash

################################################################################
# Script d'extraction FINALE pour tous les documents restants
# Utilise des stratégies adaptatives basées sur la taille
################################################################################

set -e

echo "================================================================================"
echo "EXTRACTION FINALE DES DOCUMENTS RESTANTS"
echo "================================================================================"
echo ""

# Configuration Python
export PYTHONPATH=/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/.local/lib/python3.7/site-packages

# Répertoire de travail
cd /home/threesixty/yyy/Dossy

# Compteurs
SUCCESS=0
FAILED=0

################################################################################
# Fonction d'extraction avec retry et gestion d'erreurs
################################################################################
extract_document() {
    local ID=$1
    local SIZE_MB=$2
    local FILENAME=$3
    local OCR_PAGES=$4
    local OCR_DPI=$5
    local OCR_DELAY=$6
    
    echo ""
    echo "────────────────────────────────────────────────────────────────────────────"
    echo "📄 Document ID: $ID"
    echo "   Fichier: $FILENAME"
    echo "   Taille: ${SIZE_MB}MB"
    echo "   Stratégie: pages=$OCR_PAGES, dpi=$OCR_DPI, delay=$OCR_DELAY"
    echo "────────────────────────────────────────────────────────────────────────────"
    
    # Tentative d'extraction
    if python3 scripts/extract_documents.py \
        --source=legal \
        --document-id=$ID \
        --ignore-size \
        --ocr-pages=$OCR_PAGES \
        --ocr-dpi=$OCR_DPI \
        --ocr-delay=$OCR_DELAY \
        --force-ocr \
        2>&1 | tee -a extraction_log_${ID}.txt; then
        
        echo "✅ Extraction réussie pour document $ID"
        ((SUCCESS++))
        
        # Vérification dans la base de données
        php artisan tinker --execute="
            \$doc = App\Models\LegalDocument::find($ID);
            if (\$doc && \$doc->extracted_text_length > 0) {
                echo '✅ Vérifié: ' . \$doc->extracted_text_length . ' caractères extraits\n';
            } else {
                echo '⚠️  Attention: extracted_text_length = ' . (\$doc ? \$doc->extracted_text_length : 'NULL') . '\n';
            }
        "
        
    else
        echo "❌ Échec de l'extraction pour document $ID"
        ((FAILED++))
        
        # Si échec, tenter avec paramètres ultra-conservateurs
        echo "🔄 Tentative de récupération avec paramètres minimaux..."
        
        if python3 scripts/extract_documents.py \
            --source=legal \
            --document-id=$ID \
            --ignore-size \
            --ocr-pages=10 \
            --ocr-dpi=60 \
            --ocr-delay=5.0 \
            --force-ocr \
            2>&1 | tee -a extraction_log_${ID}_retry.txt; then
            
            echo "✅ Récupération réussie pour document $ID (mode minimal)"
            ((SUCCESS++))
            ((FAILED--))
        else
            echo "❌ Échec définitif pour document $ID"
        fi
    fi
    
    # Pause entre documents pour éviter surcharge
    sleep 3
}

################################################################################
# PETITS FICHIERS (extraction complète haute qualité)
################################################################################
echo ""
echo "═══════════════════════════════════════════════════════════════════════════"
echo "📄 PHASE 1: PETITS FICHIERS (< 5MB) - Extraction complète"
echo "═══════════════════════════════════════════════════════════════════════════"

# 249 - 2.0MB
extract_document 249 2.0 "ARRETE_2006-14_FIXANT_fr.pdf" "all" 150 0.3

# 250 - 3.3MB
extract_document 250 3.3 "Décret 2010_466.pdf" "all" 150 0.3

# 55 - 4.5MB
extract_document 55 4.5 "Décret-2018-099-fr.pdf" "all" 140 0.5

################################################################################
# FICHIERS MOYENS (extraction partielle haute qualité)
################################################################################
echo ""
echo "═══════════════════════════════════════════════════════════════════════════"
echo "📄 PHASE 2: FICHIERS MOYENS (5-10MB) - 100 premières pages"
echo "═══════════════════════════════════════════════════════════════════════════"

# 104 - 5.4MB
extract_document 104 5.4 "LOI-2018-020-DU-11-DECEMBRE-2018-PORTANT-LOI-CADRE" "100" 120 0.8

# 54 - 5.9MB  
extract_document 54 5.9 "Décret N 2019 033 du 24 janvier 2019 portant réor" "100" 120 0.8

# 74 - 6.0MB
extract_document 74 6.0 "arrete n 0007.pdf" "100" 120 0.8

# 256 - 6.7MB
extract_document 256 6.7 "LOI N° 2017-010 DU 12 JUIL 2017 portant statut gén" "100" 110 1.0

# 191 - 8.0MB
extract_document 191 8.0 "Loi portant code de justice militaire.pdf" "100" 110 1.0

# 217 - 8.0MB
extract_document 217 8.0 "Loi portant code de justice militaire.pdf" "100" 110 1.0

################################################################################
# GRANDS FICHIERS (extraction limitée basse résolution)
################################################################################
echo ""
echo "═══════════════════════════════════════════════════════════════════════════"
echo "📄 PHASE 3: GRANDS FICHIERS (> 20MB) - 40 premières pages, DPI minimal"
echo "═══════════════════════════════════════════════════════════════════════════"

# 234 - 20.5MB
extract_document 234 20.5 "CODE-CIMA-2019.pdf" "40" 90 2.0

# 75 - 22.5MB
extract_document 75 22.5 "contrat_des_villes_en.pdf" "40" 85 2.5

################################################################################
# RAPPORT FINAL
################################################################################
echo ""
echo "================================================================================"
echo "RAPPORT FINAL D'EXTRACTION"
echo "================================================================================"
echo ""
echo "✅ Succès: $SUCCESS documents"
echo "❌ Échecs: $FAILED documents"
echo ""
echo "Total traité: $((SUCCESS + FAILED)) documents"
echo ""

# Vérification finale dans la base de données
echo "────────────────────────────────────────────────────────────────────────────"
echo "VÉRIFICATION FINALE DANS LA BASE DE DONNÉES"
echo "────────────────────────────────────────────────────────────────────────────"

php artisan tinker --execute="
    \$docs = App\Models\LegalDocument::whereIn('id', [249, 250, 55, 104, 54, 74, 256, 191, 217, 234, 75])
        ->get(['id', 'file_name', 'extracted_text_length']);
    
    echo str_repeat('=', 80) . PHP_EOL;
    echo 'RÉSULTATS FINAUX' . PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;
    
    \$success_count = 0;
    \$failed_count = 0;
    
    foreach (\$docs as \$doc) {
        \$length = \$doc->extracted_text_length ?? 0;
        \$status = \$length > 0 ? '✅' : '❌';
        \$status_text = \$length > 0 ? 'OK' : 'ÉCHEC';
        
        if (\$length > 0) {
            \$success_count++;
        } else {
            \$failed_count++;
        }
        
        echo sprintf(
            '%s ID %-4d | %-50s | %s (%d chars)' . PHP_EOL,
            \$status,
            \$doc->id,
            substr(\$doc->file_name, 0, 50),
            \$status_text,
            \$length
        );
    }
    
    echo str_repeat('-', 80) . PHP_EOL;
    echo sprintf('Succès: %d/%d documents' . PHP_EOL, \$success_count, count(\$docs));
    echo sprintf('Échecs: %d/%d documents' . PHP_EOL, \$failed_count, count(\$docs));
    echo str_repeat('=', 80) . PHP_EOL;
    
    if (\$failed_count > 0) {
        echo PHP_EOL . 'DOCUMENTS ENCORE EN ÉCHEC:' . PHP_EOL;
        foreach (\$docs as \$doc) {
            if (!(\$doc->extracted_text_length > 0)) {
                echo sprintf('  - ID %d: %s' . PHP_EOL, \$doc->id, \$doc->file_name);
            }
        }
    }
"

echo ""
echo "================================================================================"
echo "Les logs détaillés sont dans: extraction_log_*.txt"
echo "================================================================================"
echo ""

# Retourner le code de sortie approprié
if [ $FAILED -gt 0 ]; then
    exit 1
else
    exit 0
fi
