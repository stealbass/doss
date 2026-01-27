<?php

/*
 * FIX URGENT: Chat Flutter ne fonctionne pas
 * 
 * Problème: Call to undefined method App\Models\User::mobileAppSubscription()
 * Cause: Cache Laravel + Opcache qui charge l'ancienne version du modèle User
 * 
 * Solution immédiate: Exécuter ces commandes
 */

// Commands à exécuter dans le terminal

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  FIX URGENT: Chat Flutter - Méthode mobileAppSubscription  ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "📋 ÉTAPES À SUIVRE:\n\n";

echo "1️⃣  Nettoyer tous les caches Laravel:\n";
echo "   cd " . base_path() . "\n";
echo "   php artisan cache:clear\n";
echo "   php artisan config:clear\n";
echo "   php artisan view:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan optimize:clear\n\n";

echo "2️⃣  Redémarrer PHP-FPM (si sur serveur de production):\n";
echo "   sudo service php8.1-fpm restart\n";
echo "   # OU\n";
echo "   sudo systemctl restart php8.1-fpm\n\n";

echo "3️⃣  Nettoyer OPcache (si activé):\n";
echo "   # Créer un fichier: public/clear-opcache.php\n";
echo "   <?php opcache_reset(); echo 'OPcache cleared'; ?>\n";
echo "   # Puis visiter: http://votre-site.com/clear-opcache.php\n\n";

echo "4️⃣  Tester le chat Flutter:\n";
echo "   - Ouvrir l'app Flutter\n";
echo "   - Aller dans Chat\n";
echo "   - Envoyer un message\n";
echo "   - Vérifier la réponse\n\n";

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC RAPIDE                                         ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Test 1: Vérifier si la méthode existe
echo "Test 1: Vérification de la méthode User::mobileAppSubscription()\n";
try {
    $user = \App\Models\User::first();
    if ($user) {
        $hasMethod = method_exists($user, 'mobileAppSubscription');
        echo $hasMethod ? "   ✅ Méthode existe\n" : "   ❌ Méthode n'existe pas\n";
        
        if ($hasMethod) {
            echo "   Tentative d'appel...\n";
            $subscription = $user->mobileAppSubscription()->first();
            echo "   ✅ Appel réussi\n";
            echo "   Subscription ID: " . ($subscription->id ?? 'NULL') . "\n";
        }
    } else {
        echo "   ⚠️  Aucun utilisateur trouvé en DB\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Vérifier les imports
echo "Test 2: Vérification du namespace MobileAppSubscription\n";
try {
    $classExists = class_exists(\App\Models\MobileAppSubscription::class);
    echo $classExists ? "   ✅ Classe existe\n" : "   ❌ Classe n'existe pas\n";
} catch (\Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Vérifier la table
echo "Test 3: Vérification de la table mobile_app_subscriptions\n";
try {
    $tableExists = \Schema::hasTable('mobile_app_subscriptions');
    echo $tableExists ? "   ✅ Table existe\n" : "   ❌ Table n'existe pas\n";
    
    if ($tableExists) {
        $count = \DB::table('mobile_app_subscriptions')->count();
        echo "   Nombre de subscriptions: $count\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Log le problème
echo "Test 4: Vérification des logs Laravel\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $log = file_get_contents($logPath);
    $occurrences = substr_count($log, 'mobileAppSubscription()');
    echo "   Nombre d'occurrences d'erreur: $occurrences\n";
    
    if ($occurrences > 0) {
        echo "   ⚠️  Erreurs détectées dans les logs!\n";
        echo "   Commande pour voir: tail -100 storage/logs/laravel.log | grep mobileAppSubscription\n";
    }
} else {
    echo "   ⚠️  Fichier log introuvable\n";
}

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║  SOLUTION DE CONTOURNEMENT (si rien ne fonctionne)       ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "Si après toutes les commandes le problème persiste:\n\n";
echo "1. Modifier ChatController.php ligne 223:\n";
echo "   AVANT:\n";
echo "   \$subscription = \$user->mobileAppSubscription()->where('status', 'active')->first();\n\n";
echo "   APRÈS:\n";
echo "   \$subscription = \\App\\Models\\MobileAppSubscription::where('user_id', \$user->id)\n";
echo "                     ->where('status', 'active')->first();\n\n";

echo "2. Ou utiliser la relation directe:\n";
echo "   \$subscription = \$user->activeMobileSubscription()->first();\n\n";

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  FIN DU DIAGNOSTIC                                         ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
