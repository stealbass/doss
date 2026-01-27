#!/bin/bash

echo "=========================================="
echo "  Push des Corrections d'Envoi d'Email"
echo "=========================================="
echo ""

# Vérifier qu'on est dans le bon répertoire
if [ ! -d ".git" ]; then
    echo "❌ Erreur: Ce script doit être exécuté depuis la racine du projet"
    exit 1
fi

# Afficher la branche actuelle
echo "📍 Branche actuelle:"
git branch --show-current
echo ""

# Afficher les commits à pousser
echo "📦 Commits à pousser:"
git log origin/genspark_ai_developer..HEAD --oneline
echo ""

# Demander confirmation
read -p "Voulez-vous pousser ces commits vers GitHub? (o/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Oo]$ ]]; then
    echo "🚀 Push en cours..."
    echo ""
    
    git push origin genspark_ai_developer
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ Push réussi!"
        echo ""
        echo "🔗 Vérifiez le Pull Request #7:"
        echo "   https://github.com/stealbass/doss/pull/7"
        echo ""
        echo "📋 Prochaines étapes:"
        echo "   1. Vérifier que les commits apparaissent dans le PR"
        echo "   2. Merger le PR #7"
        echo "   3. Tester la fonctionnalité d'envoi d'email"
        echo "   4. Consulter STATUS_EMAIL_FIXES.md pour les détails"
        echo ""
    else
        echo ""
        echo "❌ Échec du push"
        echo ""
        echo "💡 Solutions possibles:"
        echo "   1. Vérifier votre authentification GitHub"
        echo "   2. Configurer un Personal Access Token (PAT)"
        echo "   3. Utiliser SSH au lieu de HTTPS"
        echo "   4. Pousser depuis votre environnement local"
        echo ""
        echo "📖 Guide GitHub PAT:"
        echo "   https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token"
        echo ""
    fi
else
    echo ""
    echo "❌ Push annulé"
    echo ""
fi
