<?php
// Script de diagnostic pour vérifier la subscription après paiement

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\MobileAppPayment;
use App\Models\MobileAppSubscription;

// ID utilisateur du screenshot
$userId = 202;

echo "=== DIAGNOSTIC SUBSCRIPTION USER $userId ===\n\n";

// 1. Vérifier l'utilisateur
$user = User::find($userId);
if (!$user) {
    echo "❌ Utilisateur $userId non trouvé\n";
    exit(1);
}
echo "✅ Utilisateur trouvé: {$user->name} ({$user->email})\n\n";

// 2. Vérifier les paiements
echo "--- PAIEMENTS ---\n";
$payments = MobileAppPayment::where('user_id', $userId)
    ->orderBy('id', 'desc')
    ->take(5)
    ->get();

foreach ($payments as $payment) {
    echo "Payment ID: {$payment->id}\n";
    echo "  Plan: {$payment->mobile_app_plan_id}\n";
    echo "  Amount: {$payment->amount} {$payment->currency}\n";
    echo "  Status: {$payment->status}\n";
    echo "  Paid at: {$payment->paid_at}\n";
    echo "  Flutterwave ref: {$payment->flutterwave_reference}\n";
    echo "  Created: {$payment->created_at}\n\n";
}

// 3. Vérifier les subscriptions
echo "--- SUBSCRIPTIONS ---\n";
$subscriptions = MobileAppSubscription::where('user_id', $userId)
    ->orderBy('id', 'desc')
    ->with('plan')
    ->get();

if ($subscriptions->isEmpty()) {
    echo "❌ AUCUNE SUBSCRIPTION TROUVÉE pour l'utilisateur $userId\n";
    echo "   Le problème est que activateSubscription() n'a pas créé la subscription!\n\n";
} else {
    foreach ($subscriptions as $sub) {
        echo "Subscription ID: {$sub->id}\n";
        echo "  Plan: {$sub->plan->name} (ID: {$sub->mobile_app_plan_id})\n";
        echo "  Status: {$sub->status}\n";
        echo "  Billing: {$sub->billing_cycle}\n";
        echo "  Started: {$sub->started_at}\n";
        echo "  Expires: {$sub->expires_at}\n";
        echo "  Created: {$sub->created_at}\n";
        echo "  Payment ref: {$sub->payment_reference}\n\n";
    }
}

// 4. Vérifier la subscription active
echo "--- SUBSCRIPTION ACTIVE ---\n";
$activeSub = $user->mobileAppSubscription()->first();
if (!$activeSub) {
    echo "❌ Pas de subscription active trouvée via la relation User->mobileAppSubscription()\n";
    
    // Vérifier pourquoi
    $allSubs = MobileAppSubscription::where('user_id', $userId)->get();
    if ($allSubs->isEmpty()) {
        echo "   Raison: Aucune subscription n'existe dans la table\n";
    } else {
        echo "   Subscriptions existantes:\n";
        foreach ($allSubs as $s) {
            echo "   - ID {$s->id}: status={$s->status}, expires={$s->expires_at}\n";
            if ($s->status !== 'active') {
                echo "     ⚠️  Status n'est pas 'active'\n";
            }
            if ($s->expires_at && $s->expires_at < now()) {
                echo "     ⚠️  Subscription expirée\n";
            }
        }
    }
} else {
    echo "✅ Subscription active trouvée:\n";
    echo "   Plan: {$activeSub->plan->name}\n";
    echo "   Status: {$activeSub->status}\n";
    echo "   Expires: {$activeSub->expires_at}\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
