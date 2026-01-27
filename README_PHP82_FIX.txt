╔═══════════════════════════════════════════════════════════════╗
║                                                               ║
║         🔧 CORRECTION PHP 8.2 - DOSSY PRO                    ║
║            Solution au problème Composer                      ║
║                                                               ║
╚═══════════════════════════════════════════════════════════════╝

❌ PROBLÈME RENCONTRÉ :
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

"Fatal error: Composer detected issues in your platform: 
Your Composer dependencies require a PHP version ">= 8.2.0". 
You are running 7.4.33."


🎯 CAUSE :
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Le dossier vendor/ a été généré avec PHP 7.4. Il faut le 
régénérer avec PHP 8.2.


✅ SOLUTION RAPIDE (Recommandée) :
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Connectez-vous en SSH à votre serveur
2. Allez dans le répertoire du projet :
   
   cd /home/threesixty/yyy/Dossy/legal

3. Exécutez le script automatique :
   
   chmod +x regenerate-composer-php82.sh
   ./regenerate-composer-php82.sh

   OU si PHP 8.2 n'est pas par défaut :
   
   php8.2 regenerate-composer-php82.sh


📋 SOLUTION MANUELLE :
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

cd /home/threesixty/yyy/Dossy/legal

# 1. Nettoyer
rm -rf vendor/
rm -f composer.lock
rm -rf bootstrap/cache/*.php

# 2. Installer avec PHP 8.2
composer install --no-interaction --prefer-dist --optimize-autoloader

# 3. Vérifier
php artisan --version


🔧 SI PHP 8.2 N'EST PAS PAR DÉFAUT :
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Utilisez explicitement php8.2 :

php8.2 /usr/local/bin/composer install --no-interaction


📌 FICHIERS FOURNIS :
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✓ regenerate-composer-php82.sh   - Script automatique
✓ FIX_PHP82_COMPOSER.md          - Guide détaillé complet
✓ .gitignore                     - Mis à jour (vendor/ exclu)


🚀 APRÈS LA CORRECTION :
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Exécuter les migrations :
   php artisan migrate --force

2. Configurer le stockage :
   mkdir -p storage/app/public/legal_documents
   chmod -R 775 storage/app/public/legal_documents
   php artisan storage:link

3. Vider les caches :
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear

4. Tester la bibliothèque juridique :
   - Admin : https://votre-domaine.com/legal-library
   - Users : https://votre-domaine.com/library


⚠️ IMPORTANT :
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• Ne JAMAIS commiter vendor/ dans Git
• Le .gitignore a été mis à jour pour exclure vendor/
• Chaque serveur doit régénérer son propre vendor/
• Assurez-vous que PHP 8.2 est actif pour votre domaine


📞 BESOIN D'AIDE ?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Consultez FIX_PHP82_COMPOSER.md pour le guide complet avec 
toutes les solutions possibles et le dépannage.


✨ APRÈS CES ÉTAPES, VOTRE BIBLIOTHÈQUE JURIDIQUE 
   FONCTIONNERA PARFAITEMENT ! 🚀
