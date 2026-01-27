#!/bin/bash

# Script pour vérifier le workflow automatique RAG
# Ce script va:
# 1. Vérifier la configuration de la queue
# 2. Vérifier les documents en base de données
# 3. Vérifier l'extraction de texte
# 4. Tester si les jobs se lancent automatiquement

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "  DIAGNOSTIC: WORKFLOW AUTOMATIQUE RAG POUR DOCUMENTS UPLOADÉS"
echo "═══════════════════════════════════════════════════════════════"
echo ""

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

# Exécuter la commande Artisan
php artisan rag:verify

echo ""
echo "PROCHAINES ÉTAPES:"
echo "────────────────────────────────────"
echo ""
echo "Si vous voyez des documents en 'pending':"
echo "  1. Lancez: php artisan queue:work"
echo "  2. Ou lancez: php artisan queue:failed"
echo ""
echo "Pour traiter les documents échoués manuellement:"
echo "  php artisan rag:extract-pending"
echo ""
