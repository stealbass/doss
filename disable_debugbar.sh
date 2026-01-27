#!/bin/bash

# Script pour désactiver Barryvdh Debugbar ServiceProvider
# À lancer depuis la racine du projet

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

CONFIG_FILE="config/app.php"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "❌ Fichier $CONFIG_FILE non trouvé"
    exit 1
fi

echo "Recherche de Barryvdh dans $CONFIG_FILE..."

# Chercher la ligne avec Barryvdh
if grep -q "Barryvdh" "$CONFIG_FILE"; then
    echo "✅ Barryvdh trouvé, suppression..."
    
    # Commenter la ligne ou la supprimer
    sed -i "/Barryvdh.*ServiceProvider/s/^[[:space:]]*/'    \/\/ /" "$CONFIG_FILE"
    
    echo "✅ Barryvdh désactivé"
    echo "Vous pouvez relancer le site maintenant"
else
    echo "⚠️  Barryvdh non trouvé dans $CONFIG_FILE"
    echo "Le problème vient peut-être d'ailleurs"
fi
