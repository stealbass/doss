<?php

/**
 * Script pour activer un compte utilisateur désactivé
 * 
 * Utilisation : php activate_user.php
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Activation de Compte Utilisateur ===\n\n";

// Demander l'email de l'utilisateur
echo "Entrez l'adresse email de l'utilisateur à activer: ";
$email = trim(fgets(STDIN));

if (empty($email)) {
    echo "❌ Email requis.\n";
    exit(1);
}

// Valider l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Adresse email invalide: $email\n";
    exit(1);
}

try {
    // Rechercher l'utilisateur
    $user = DB::table('users')->where('email', $email)->first();
    
    if (!$user) {
        echo "❌ Aucun utilisateur trouvé avec l'email: $email\n";
        exit(1);
    }
    
    echo "\n📋 Informations de l'utilisateur:\n";
    echo "   ID: {$user->id}\n";
    echo "   Nom: {$user->name}\n";
    echo "   Email: {$user->email}\n";
    echo "   Type: {$user->type}\n";
    echo "   Statut actuel: " . ($user->is_active ? "✅ Activé" : "❌ Désactivé") . "\n\n";
    
    if ($user->is_active) {
        echo "ℹ️  Ce compte est déjà activé.\n";
        
        echo "\nVoulez-vous le désactiver ? (o/n): ";
        $response = trim(fgets(STDIN));
        
        if (strtolower($response) === 'o' || strtolower($response) === 'y') {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'is_active' => 0,
                    'updated_at' => now()
                ]);
            
            echo "✅ Compte désactivé avec succès !\n";
        } else {
            echo "Opération annulée.\n";
        }
    } else {
        echo "Voulez-vous activer ce compte ? (o/n): ";
        $response = trim(fgets(STDIN));
        
        if (strtolower($response) === 'o' || strtolower($response) === 'y' || strtolower($response) === 'yes' || strtolower($response) === 'oui') {
            // Activer le compte
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'is_active' => 1,
                    'updated_at' => now()
                ]);
            
            echo "\n✅ Compte activé avec succès !\n";
            echo "   L'utilisateur peut maintenant se connecter.\n\n";
            
            // Afficher les informations de connexion
            echo "📱 Informations de connexion:\n";
            echo "   Email: {$user->email}\n";
            echo "   Mot de passe: [Le nouveau mot de passe défini]\n\n";
            
            // Vérifier si l'utilisateur a une subscription
            $subscription = DB::table('mobile_app_subscriptions')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
            
            if ($subscription) {
                echo "✅ Subscription active trouvée\n";
            } else {
                echo "⚠️  Aucune subscription active\n";
                echo "   L'utilisateur devrait avoir une subscription gratuite par défaut.\n";
                
                // Vérifier s'il y a un plan gratuit
                $freePlan = DB::table('mobile_app_plans')
                    ->where('price_monthly', 0)
                    ->first();
                
                if ($freePlan) {
                    echo "\n   Voulez-vous créer une subscription gratuite pour cet utilisateur ? (o/n): ";
                    $createSub = trim(fgets(STDIN));
                    
                    if (strtolower($createSub) === 'o' || strtolower($createSub) === 'y') {
                        DB::table('mobile_app_subscriptions')->insert([
                            'user_id' => $user->id,
                            'mobile_app_plan_id' => $freePlan->id,
                            'status' => 'active',
                            'started_at' => now(),
                            'expires_at' => null,
                            'auto_renew' => 0,
                            'searches_used' => 0,
                            'ai_analyses_used' => 0,
                            'pdf_downloads_used' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        echo "   ✅ Subscription gratuite créée !\n";
                    }
                }
            }
            
        } else {
            echo "Opération annulée.\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Terminé ===\n";
