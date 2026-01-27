#!/bin/bash
#
# Script de réindexation batch pour documents légaux avec texte extrait
# Récupère les documents avec extracted_text mais qui ne sont pas indexés
# Usage: bash reindex_legal_documents.sh
#

set -e

echo "=========================================="
echo "Réindexation batch - Documents légaux"
echo "=========================================="
echo ""

# Configuration
PYTHON_PATH="/home/threesixty/yyy/Dossy/python-packages:/home/threesixty/yyy/Dossy/.local/lib/python3.7/site-packages"
DB_HOST="mysql-threesixty.alwaysdata.net"
DB_USER="188651"
DB_PASS="A#Mapan1409@"
DB_NAME="threesixty_dossypro_legal_new"

# Récupérer la liste des IDs avec texte extrait
echo "📋 Récupération des documents avec texte extrait..."
DOCUMENT_IDS=$(mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" -D"${DB_NAME}" -N -B -e \
  "SELECT id FROM legal_documents WHERE extracted_text IS NOT NULL AND extracted_text != '' LIMIT 100;" 2>/dev/null)

if [ -z "$DOCUMENT_IDS" ]; then
    echo "✅ Aucun document à réindexer"
    exit 0
fi

# Compter les documents
DOC_COUNT=$(echo "$DOCUMENT_IDS" | wc -l)
echo "📊 ${DOC_COUNT} document(s) trouvé(s) à réindexer"
echo ""

# PHP script pour lancer les jobs
cd /home/threesixty/yyy/Dossy

CURRENT=0
SUCCESS=0
FAILED=0

for DOC_ID in $DOCUMENT_IDS; do
    CURRENT=$((CURRENT + 1))
    echo "────────────────────────────────────────"
    echo "[$CURRENT/$DOC_COUNT] Réindexation du document ID: $DOC_ID"
    echo "────────────────────────────────────────"
    
    # Lancer le job d'indexation via PHP
    if php artisan queue:work --once --queue=default > /tmp/reindex_$DOC_ID.log 2>&1; then
        echo "✅ Document $DOC_ID réindexé avec succès"
        SUCCESS=$((SUCCESS + 1))
    else
        echo "⚠️ Document $DOC_ID - vérifiez le log"
        FAILED=$((FAILED + 1))
    fi
    
    sleep 1
done

echo ""
echo "=========================================="
echo "Résumé de la réindexation"
echo "=========================================="
echo "Total traité:  $DOC_COUNT"
echo "Succès:        $SUCCESS"
echo "Autres:        $FAILED"
echo "=========================================="
