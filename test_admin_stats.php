<?php
// Script pour tester toutes les statistiques admin après paiement

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\MobileAppPayment;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPlan;

echo "=== TEST DES STATISTIQUES ADMIN ===\n\n";

$userId = 202; // Utilisateur qui a payé

// 1. Mobile Users Controller - Statistics
echo "📊 Mobile Users Statistics:\n";
$totalUsers = User::whereHas('mobileSubscriptions')->count();
$activeUsers = User::whereHas('activeMobileSubscription')->count();
$totalRevenue = MobileAppPayment::where('status', 'successful')->sum('amount');
$newUsersThisMonth = User::whereHas('mobileSubscriptions')
    ->whereYear('created_at', now()->year)
    ->whereMonth('created_at', now()->month)
    ->count();

echo "  Total users (avec subs): $totalUsers\n";
echo "  Active users (sub active): $activeUsers\n";
echo "  Total revenue: $totalRevenue XAF\n";
echo "  New users this month: $newUsersThisMonth\n\n";

// 2. Mobile Dashboard - Statistics
echo "📈 Mobile Dashboard Statistics:\n";
$dashTotalUsers = User::whereHas('mobileSubscriptions')->count();
$dashActiveUsers = User::whereHas('activeMobileSubscription', function($q) {
    $q->where('status', 'active');
})->count();
$dashTotalSubs = MobileAppSubscription::where('status', 'active')
    ->where(function($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })->count();
$dashRevenueThisMonth = MobileAppPayment::where('status', 'successful')
    ->whereYear('paid_at', now()->year)
    ->whereMonth('paid_at', now()->month)
    ->sum('amount');

echo "  Total users: $dashTotalUsers\n";
echo "  Active users: $dashActiveUsers\n";
echo "  Total subscriptions: $dashTotalSubs\n";
echo "  Revenue this month: $dashRevenueThisMonth XAF\n\n";

// 3. Mobile Plans - Statistics
echo "💰 Mobile Plans Statistics:\n";
$totalPlans = MobileAppPlan::count();
$activePlans = MobileAppPlan::where('is_active', true)->count();
$plansTotalSubs = MobileAppSubscription::where('status', 'active')
    ->where(function($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })->count();
$plansMonthlyRevenue = MobileAppPayment::where('status', 'successful')
    ->whereYear('paid_at', now()->year)
    ->whereMonth('paid_at', now()->month)
    ->sum('amount');

echo "  Total plans: $totalPlans\n";
echo "  Active plans: $activePlans\n";
echo "  Total active subscriptions: $plansTotalSubs\n";
echo "  Monthly revenue: $plansMonthlyRevenue XAF\n\n";

// 4. Vérifier que l'utilisateur 202 apparaît dans les compteurs
echo "🔍 Vérification utilisateur ID $userId:\n";
$user = User::find($userId);
$hasMobileSubs = $user->mobileSubscriptions()->count() > 0;
$hasActiveSub = $user->activeMobileSubscription()->first() !== null;

echo "  A des subscriptions mobile: " . ($hasMobileSubs ? "✅ OUI" : "❌ NON") . "\n";
echo "  A une subscription active: " . ($hasActiveSub ? "✅ OUI" : "❌ NON") . "\n";

if ($hasActiveSub) {
    $activeSub = $user->activeMobileSubscription()->first();
    echo "  Plan actif: {$activeSub->plan->name}\n";
    echo "  Status: {$activeSub->status}\n";
    echo "  Expires: {$activeSub->expires_at}\n";
}

// 5. Vérifier les paiements de l'utilisateur
echo "\n💳 Paiements de l'utilisateur $userId:\n";
$payments = MobileAppPayment::where('user_id', $userId)
    ->where('status', 'successful')
    ->get();

foreach ($payments as $payment) {
    echo "  - {$payment->amount} XAF (ref: {$payment->flutterwave_reference})\n";
}

$userTotalPaid = MobileAppPayment::where('user_id', $userId)
    ->where('status', 'successful')
    ->sum('amount');
echo "  Total payé par user $userId: $userTotalPaid XAF\n\n";

// 6. Test des compteurs par plan
echo "📋 Subscriptions actives par plan:\n";
$plans = MobileAppPlan::withCount(['subscriptions as active_count' => function($q) {
    $q->where('status', 'active')
      ->where(function($query) {
          $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
      });
}])->get();

foreach ($plans as $plan) {
    echo "  - {$plan->name}: {$plan->active_count} subscriptions actives\n";
}

// 7. Résumé final
echo "\n" . str_repeat("=", 50) . "\n";
echo "RÉSUMÉ:\n";
echo "✅ Total utilisateurs mobile: $totalUsers\n";
echo "✅ Subscriptions actives: $plansTotalSubs\n";
echo "✅ Revenue total: $totalRevenue XAF\n";
echo "✅ Revenue ce mois: $plansMonthlyRevenue XAF\n";

if ($hasActiveSub && $payments->count() > 0) {
    echo "\n✅ SUCCÈS: L'utilisateur $userId est bien compté dans toutes les statistiques!\n";
} else {
    echo "\n❌ PROBLÈME: L'utilisateur $userId n'apparaît pas correctement dans les stats\n";
    if (!$hasActiveSub) echo "   - Pas de subscription active\n";
    if ($payments->count() === 0) echo "   - Pas de paiement successful\n";
}

echo "\n=== FIN DU TEST ===\n";
