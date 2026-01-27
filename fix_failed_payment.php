<?php

/**
 * Script pour corriger un paiement marqué "Failed" 
 * et activer manuellement l'abonnement
 * 
 * Usage: php fix_failed_payment.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MobileAppPayment;
use App\Models\MobileAppSubscription;
use Carbon\Carbon;

echo "=== CORRECTION PAIEMENT FAILED ===\n\n";

// Transaction ID à corriger (visible dans le screenshot)
$transactionId = '952843682';

echo "Recherche du paiement avec transaction_id: $transactionId\n";

$payment = MobileAppPayment::where('transaction_id', $transactionId)->first();

if (!$payment) {
    // Essayer aussi avec flutterwave_reference
    $payment = MobileAppPayment::where('flutterwave_reference', $transactionId)->first();
}

if (!$payment) {
    echo "❌ Paiement non trouvé!\n";
    echo "\nRecherche de tous les paiements de l'utilisateur 202:\n";
    
    $allPayments = MobileAppPayment::where('user_id', 202)
        ->orderBy('created_at', 'desc')
        ->get();
    
    foreach ($allPayments as $p) {
        echo "\n- ID: {$p->id}\n";
        echo "  Transaction ID: {$p->transaction_id}\n";
        echo "  Reference: {$p->flutterwave_reference}\n";
        echo "  Amount: {$p->amount} {$p->currency}\n";
        echo "  Status: {$p->status}\n";
        echo "  Created: {$p->created_at}\n";
    }
    exit(1);
}

echo "✅ Paiement trouvé:\n";
echo "  - ID: {$payment->id}\n";
echo "  - User ID: {$payment->user_id}\n";
echo "  - Plan ID: {$payment->mobile_app_plan_id}\n";
echo "  - Amount: {$payment->amount} {$payment->currency}\n";
echo "  - Status actuel: {$payment->status}\n";
echo "  - Created: {$payment->created_at}\n";
echo "  - Transaction ID: {$payment->transaction_id}\n";
echo "  - Reference: {$payment->flutterwave_reference}\n\n";

if ($payment->status === 'successful') {
    echo "⚠️  Le paiement est déjà marqué comme 'successful'\n";
    
    // Vérifier si subscription existe
    $subscription = MobileAppSubscription::where('user_id', $payment->user_id)
        ->where('status', 'active')
        ->first();
    
    if ($subscription) {
        echo "✅ L'abonnement est déjà actif (ID: {$subscription->id})\n";
        echo "  Plan: {$subscription->plan->name}\n";
        echo "  Expires: {$subscription->expires_at}\n";
        exit(0);
    } else {
        echo "⚠️  Mais aucun abonnement actif trouvé. Activation...\n";
    }
} else {
    echo "🔧 Correction du statut du paiement...\n";
    
    // Mettre à jour le statut du paiement
    $payment->update([
        'status' => 'successful',
        'paid_at' => $payment->paid_at ?? now(),
    ]);
    
    echo "✅ Statut mis à jour: successful\n\n";
}

// Activer l'abonnement
echo "🔧 Activation de l'abonnement...\n";

$plan = $payment->plan;

if (!$plan) {
    echo "❌ Plan non trouvé (ID: {$payment->mobile_app_plan_id})\n";
    exit(1);
}

echo "  Plan: {$plan->name}\n";
echo "  Prix: {$plan->price_monthly} XAF/mois\n";

$billingCycle = $payment->flutterwave_data['billing_cycle'] ?? 'monthly';

// Calculate expiry date
$expiresAt = $billingCycle === 'monthly' 
    ? Carbon::now()->addMonth() 
    : Carbon::now()->addYear();

// Create or update subscription
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

echo "\n✅ Abonnement activé avec succès!\n";
echo "  - Subscription ID: {$subscription->id}\n";
echo "  - User ID: {$subscription->user_id}\n";
echo "  - Plan: {$plan->name}\n";
echo "  - Status: {$subscription->status}\n";
echo "  - Started: {$subscription->started_at}\n";
echo "  - Expires: {$subscription->expires_at}\n";
echo "  - Billing cycle: {$subscription->billing_cycle}\n";
echo "  - Amount paid: {$subscription->amount_paid} XAF\n";

echo "\n🎉 Correction terminée! L'utilisateur peut maintenant utiliser l'app avec le plan '{$plan->name}'\n";
