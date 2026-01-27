#!/bin/bash

# Redéploiement du code - Fix sources filtrage
echo "================================================"
echo "Redéploiement du correctif sources"
echo "================================================"
echo ""

# 1. Vérifier les permissions
echo "Vérification des permissions..."
chmod -R 755 /home/threesixty/yyy/Dossy/app

# 2. Vider les caches Laravel
echo "Nettoyage des caches..."
php /home/threesixty/yyy/Dossy/artisan cache:clear
php /home/threesixty/yyy/Dossy/artisan config:clear
php /home/threesixty/yyy/Dossy/artisan view:clear
php /home/threesixty/yyy/Dossy/artisan route:clear

# 3. Vérifier le code déployé
echo ""
echo "Vérification du code ChatController..."
grep -n "isType = function" /home/threesixty/yyy/Dossy/app/Http/Controllers/Api/Mobile/ChatController.php | head -5

echo ""
echo "Vérification du code AdvancedRagService..."
grep -n "typeMap = \[" /home/threesixty/yyy/Dossy/app/Services/AdvancedRagService.php | head -5

echo ""
echo "================================================"
echo "Redéploiement terminé"
echo "================================================"
echo ""
echo "✅ Testez à nouveau avec une question juridique"
echo "   Les sources doivent afficher uniquement legal_document"
echo ""
