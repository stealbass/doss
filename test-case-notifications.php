#!/usr/bin/env php
<?php
/**
 * Test Système d'Email des Affaires
 * Usage: php artisan tinker < test-case-notifications.php
 */

// Test 1: Vérifier que getSMTPDetails est accessible
echo "\n=== Test 1: Configuration SMTP ===\n";
try {
    $settings = Utility::getSMTPDetails(1);
    echo "✅ getSMTPDetails() est accessible\n";
    echo "   Driver: " . ($settings['mail_driver'] ?? 'N/A') . "\n";
    echo "   Host: " . ($settings['mail_host'] ?? 'N/A') . "\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 2: Vérifier la structure d'une affaire avec clients
echo "\n=== Test 2: Structure de l'Affaire ===\n";
$case = Cases::where('created_by', 1)->latest()->first();
if ($case) {
    echo "✅ Affaire trouvée: " . $case->title . "\n";
    
    $your_parties = json_decode($case->your_party_name, true);
    echo "   Parties: " . count($your_parties ?? []) . "\n";
    
    foreach ($your_parties ?? [] as $party) {
        if (isset($party['clients'])) {
            $client = User::find($party['clients']);
            if ($client) {
                echo "   - Client: " . $client->name . " (" . $client->email . ")\n";
            }
        }
    }
    
    if ($case->advocates) {
        $advocates = User::whereIn('id', explode(',', $case->advocates))->get();
        echo "   Juristes assignés: " . $advocates->count() . "\n";
        foreach ($advocates as $advocate) {
            echo "   - Juriste: " . $advocate->name . " (" . $advocate->email . ")\n";
        }
    }
} else {
    echo "⚠️  Aucune affaire trouvée\n";
}

// Test 3: Vérifier que le Job peut être dispatché
echo "\n=== Test 3: Dispatch du Job ===\n";
if ($case) {
    try {
        SendCaseCreatedNotification::dispatch($case);
        echo "✅ Job SendCaseCreatedNotification dispatché avec succès\n";
        echo "   (Vérifiez les logs: tail -f storage/logs/laravel.log)\n";
    } catch (Exception $e) {
        echo "❌ Erreur lors du dispatch: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  Impossible de dispatcher sans affaire\n";
}

// Test 4: Vérifier les imports
echo "\n=== Test 4: Vérifications ===\n";
echo "✅ Utility::getSMTPDetails() - " . (method_exists(Utility::class, 'getSMTPDetails') ? "✓" : "✗") . "\n";
echo "✅ PushNotificationService::sendCaseCreatedNotification() - " . (method_exists(PushNotificationService::class, 'sendCaseCreatedNotification') ? "✓" : "✗") . "\n";

echo "\n=== Test Complet ===\n";

// Créer une tâche de test
echo "Créer une nouvelle affaire de test pour valider?\n";
echo "Dans la web interface: Affaires → Créer une affaire\n";
echo "Puis vérifiez les logs: tail -f storage/logs/laravel.log | grep SendCaseCreatedNotification\n\n";
