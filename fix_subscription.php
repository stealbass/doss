<?php
// Script pour activer manuellement la subscription d'un utilisateur après paiement réussi

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\MobileAppPayment;
use App\Models\MobileAppSubscription;
use Carbon\Carbon;

// ID utilisateur et payment du screenshot
$userId = 202;
$paymentId = 5; // Le paiement successful du screenshot

echo "=== ACTIVATION MANUELLE SUBSCRIPTION ===\n\n";

// 1. Récupérer le paiement
$payment = MobileAppPayment::find($paymentId);
if (!$payment) {
    echo "❌ Paiement ID $paymentId non trouvé\n";
    exit(1);
}

echo "✅ Paiement trouvé:\n";
echo "   User ID: {$payment->user_id}\n";
echo "   Plan ID: {$payment->mobile_app_plan_id}\n";
echo "   Amount: {$payment->amount} {$payment->currency}\n";
echo "   Status: {$payment->status}\n";
echo "   Paid at: {$payment->paid_at}\n\n";

// 2. Vérifier que le paiement est successful
if ($payment->status !== 'successful') {
    echo "❌ Le paiement n'est pas 'successful', status actuel: {$payment->status}\n";
    exit(1);
}

// 3. Récupérer les données du plan
$plan = $payment->plan;
$billingCycle = $payment->flutterwave_data['billing_cycle'] ?? 'monthly';

echo "📋 Plan: {$plan->name}\n";
echo "   Billing cycle: $billingCycle\n\n";

// 4. Calculer la date d'expiration
$expiresAt = $billingCycle === 'monthly' 
    ? Carbon::now()->addMonth() 
    : Carbon::now()->addYear();

echo "📅 Dates:\n";
echo "   Started at: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
echo "   Expires at: " . $expiresAt->format('Y-m-d H:i:s') . "\n\n";

// 5. Créer ou mettre à jour la subscription
echo "🔄 Création/Mise à jour de la subscription...\n";

$subscription = MobileAppSubscription::updateOrCreate(
    ['user_id' => $payment->user_id],
    [
        'mobile_app_plan_id' => $plan->id,
        'billing_cycle' => $billingCycle,
        'status' => 'active',
        'started_at' => Carbon::now(),
        'expires_at' => $expiresAt,
        'next_billing_date' => $expiresAt,
        'amount_paid' => $payment->amount,
        'payment_reference' => $payment->transaction_id,
        'auto_renew' => true,
        'searches_used' => 0,
        'ai_analyses_used' => 0,
        'pdf_downloads_used' => 0,
        'quota_reset_at' => Carbon::now()->addMonth(),
    ]
);

echo "✅ Subscription créée/mise à jour!\n";
echo "   Subscription ID: {$subscription->id}\n";
echo "   Plan: {$subscription->plan->name}\n";
echo "   Status: {$subscription->status}\n";
echo "   Expires: {$subscription->expires_at}\n\n";

// 6. Vérifier que la subscription est récupérable
$user = User::find($userId);
$activeSub = $user->mobileAppSubscription()->first();

if ($activeSub) {
    echo "✅ SUCCESS! La subscription est maintenant active et récupérable via l'API\n";
    echo "   L'utilisateur verra maintenant: {$activeSub->plan->name}\n";
} else {
    echo "❌ ERREUR: La subscription n'est pas récupérable via User->mobileAppSubscription()\n";
    echo "   Vérifier les conditions de la relation (status='active', expires_at future)\n";
}

echo "\n=== FIN ===\n";
