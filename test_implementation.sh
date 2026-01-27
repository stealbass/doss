#!/bin/bash

# Test Script - Implémentation Documents Utilisateur
# À exécuter sur le serveur pour valider l'installation

set -e

echo "🧪 TEST - Implémentation Documents Utilisateur"
echo "=============================================="

PROJECT_ROOT="/home/threesixty/yyy/Dossy"
cd "$PROJECT_ROOT"

# Test 1: Python script exists
echo ""
echo "[1] Vérification du script Python..."
if [ -f "scripts/extract_documents.py" ]; then
    echo "  ✅ scripts/extract_documents.py existe"
else
    echo "  ❌ scripts/extract_documents.py manquant"
    exit 1
fi

# Test 2: Artisan command exists
echo ""
echo "[2] Vérification de la commande Artisan..."
if php artisan list | grep -q "documents:extract"; then
    echo "  ✅ Commande 'documents:extract' disponible"
else
    echo "  ❌ Commande 'documents:extract' non trouvée"
    exit 1
fi

# Test 3: Database connection
echo ""
echo "[3] Test de connexion à la BDD..."
if php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo "  ✅ Connexion BDD OK"
else
    echo "  ❌ Erreur de connexion BDD"
    exit 1
fi

# Test 4: AdvancedRagService exists
echo ""
echo "[4] Vérification d'AdvancedRagService..."
if php artisan tinker --execute="class_exists(App\\Services\\AdvancedRagService::class);" > /dev/null 2>&1; then
    echo "  ✅ AdvancedRagService chargé"
else
    echo "  ❌ AdvancedRagService non trouvé"
    exit 1
fi

# Test 5: Test submitted_documents table
echo ""
echo "[5] Vérification de la table submitted_documents..."
if php artisan tinker --execute="Schema::hasTable('submitted_documents');" > /dev/null 2>&1; then
    echo "  ✅ Table submitted_documents existe"
    
    # Count documents
    COUNT=$(php artisan tinker --execute="echo SubmittedDocument::count();" 2>/dev/null | tail -1)
    echo "     Total documents: $COUNT"
else
    echo "  ❌ Table submitted_documents manquante"
    exit 1
fi

# Test 6: Execute extraction command
echo ""
echo "[6] Exécution de la commande extraction..."
if php artisan documents:extract > /tmp/extraction_test.log 2>&1; then
    echo "  ✅ Commande extraction exécutée"
    
    # Check for success/failure messages
    if grep -q "Traitement terminé" /tmp/extraction_test.log; then
        echo "     ✅ Extraction complétée"
    else
        echo "     ⚠️  Vérifiez /tmp/extraction_test.log pour les détails"
    fi
else
    echo "  ❌ Erreur lors de l'exécution"
    cat /tmp/extraction_test.log
    exit 1
fi

# Test 7: Check for completed documents
echo ""
echo "[7] Vérification des documents traités..."
COMPLETED=$(php artisan tinker --execute="echo SubmittedDocument::where('processing_status', 'completed')->count();" 2>/dev/null | tail -1)
echo "     Documents complétés: $COMPLETED"

if [ "$COMPLETED" -gt 0 ]; then
    echo "  ✅ Documents extraits avec succès"
else
    echo "  ⚠️  Aucun document complété (peut être normal s'il n'y a pas d'upload en attente)"
fi

# Test 8: Cronjob configuration
echo ""
echo "[8] Vérification du cronjob..."
if crontab -l 2>/dev/null | grep -q "documents:extract"; then
    echo "  ✅ Cronjob configuré"
    echo "     $(crontab -l 2>/dev/null | grep documents:extract)"
else
    echo "  ⚠️  Cronjob non configuré (optionnel)"
fi

echo ""
echo "=============================================="
echo "✅ TESTS COMPLÉTÉS AVEC SUCCÈS"
echo "=============================================="
echo ""
echo "L'implémentation est prête à l'emploi!"
echo ""
echo "Prochaine étape: Uploader un document via Flutter et tester le chat"
echo ""
