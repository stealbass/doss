<?php

/**
 * Script de test pour vérifier la configuration SMTP
 * et envoyer un email de test pour la réinitialisation de mot de passe
 * 
 * Utilisation : php test_smtp_config.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test de Configuration SMTP pour DossuPro ===\n\n";

// Récupérer les paramètres SMTP depuis la base de données
try {
    $settings = DB::table('settings')
        ->where('created_by', 1)
        ->pluck('value', 'name')
        ->toArray();
    
    if (empty($settings)) {
        echo "❌ Aucun paramètre SMTP trouvé dans la base de données.\n";
        echo "   Veuillez configurer les paramètres SMTP dans le panneau d'administration.\n";
        exit(1);
    }
    
    echo "📧 Paramètres SMTP trouvés:\n";
    echo "   Mail Driver: " . ($settings['mail_driver'] ?? 'N/A') . "\n";
    echo "   Mail Host: " . ($settings['mail_host'] ?? 'N/A') . "\n";
    echo "   Mail Port: " . ($settings['mail_port'] ?? 'N/A') . "\n";
    echo "   Mail Username: " . ($settings['mail_username'] ?? 'N/A') . "\n";
    echo "   Mail Encryption: " . ($settings['mail_encryption'] ?? 'N/A') . "\n";
    echo "   Mail From Address: " . ($settings['mail_from_address'] ?? 'N/A') . "\n";
    echo "   Mail From Name: " . ($settings['mail_from_name'] ?? 'N/A') . "\n\n";
    
    // Vérifier que tous les paramètres requis sont présents
    $requiredSettings = [
        'mail_driver', 'mail_host', 'mail_port', 
        'mail_username', 'mail_password', 'mail_encryption',
        'mail_from_address', 'mail_from_name'
    ];
    
    $missingSettings = [];
    foreach ($requiredSettings as $setting) {
        if (!isset($settings[$setting]) || empty($settings[$setting])) {
            $missingSettings[] = $setting;
        }
    }
    
    if (!empty($missingSettings)) {
        echo "⚠️  Paramètres manquants:\n";
        foreach ($missingSettings as $missing) {
            echo "   - $missing\n";
        }
        echo "\n";
    }
    
    // Configurer Laravel Mail avec ces paramètres
    config([
        'mail.default' => $settings['mail_driver'] ?? 'smtp',
        'mail.mailers.smtp.host' => $settings['mail_host'] ?? '',
        'mail.mailers.smtp.port' => $settings['mail_port'] ?? 587,
        'mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?? 'tls',
        'mail.mailers.smtp.username' => $settings['mail_username'] ?? '',
        'mail.mailers.smtp.password' => $settings['mail_password'] ?? '',
        'mail.from.address' => $settings['mail_from_address'] ?? '',
        'mail.from.name' => $settings['mail_from_name'] ?? '',
    ]);
    
    echo "✅ Configuration SMTP chargée avec succès.\n\n";
    
    // Demander l'adresse email de test
    echo "Entrez une adresse email pour le test (ou appuyez sur Entrée pour annuler): ";
    $testEmail = trim(fgets(STDIN));
    
    if (empty($testEmail)) {
        echo "\nTest annulé.\n";
        exit(0);
    }
    
    // Valider l'adresse email
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo "❌ Adresse email invalide: $testEmail\n";
        exit(1);
    }
    
    echo "\n📤 Envoi d'un email de test à $testEmail...\n";
    
    // Envoyer un email de test
    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Ceci est un email de test pour vérifier la configuration SMTP de DossuPro.\n\n" .
            "Si vous recevez cet email, cela signifie que la configuration SMTP fonctionne correctement.\n\n" .
            "Configuration utilisée:\n" .
            "- Host: " . config('mail.mailers.smtp.host') . "\n" .
            "- Port: " . config('mail.mailers.smtp.port') . "\n" .
            "- Encryption: " . config('mail.mailers.smtp.encryption') . "\n" .
            "- Username: " . config('mail.mailers.smtp.username') . "\n\n" .
            "Date du test: " . date('Y-m-d H:i:s') . "\n",
            function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject('Test de Configuration SMTP - DossuPro');
            }
        );
        
        echo "✅ Email envoyé avec succès !\n";
        echo "   Vérifiez la boîte de réception de $testEmail\n";
        echo "   (N'oubliez pas de vérifier le dossier spam)\n\n";
        
        // Test de la fonction forgotPassword
        echo "🔄 Test de la fonction forgotPassword...\n";
        
        // Vérifier si l'utilisateur existe
        $user = DB::table('users')->where('email', $testEmail)->first();
        
        if ($user) {
            echo "   Utilisateur trouvé: {$user->name} ($testEmail)\n";
            echo "   Envoi d'un email de réinitialisation...\n";
            
            try {
                $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $testEmail]);
                
                if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
                    echo "   ✅ Email de réinitialisation envoyé avec succès !\n";
                } else {
                    echo "   ⚠️  Statut: $status\n";
                }
            } catch (\Exception $e) {
                echo "   ❌ Erreur lors de l'envoi: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ℹ️  Aucun utilisateur trouvé avec l'email $testEmail\n";
            echo "      (Test de forgotPassword ignoré)\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Erreur lors de l'envoi de l'email:\n";
        echo "   " . $e->getMessage() . "\n\n";
        
        // Afficher des suggestions de résolution
        echo "💡 Suggestions:\n";
        echo "   1. Vérifiez que le mot de passe SMTP est correct\n";
        echo "   2. Vérifiez que le port et l'encryption sont corrects (587 + TLS ou 465 + SSL)\n";
        echo "   3. Vérifiez que votre serveur SMTP autorise les connexions\n";
        echo "   4. Vérifiez les logs Laravel dans storage/logs/laravel.log\n";
        echo "   5. Testez la connexion SMTP avec telnet: telnet {$settings['mail_host']} {$settings['mail_port']}\n\n";
        
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Test terminé ===\n";
