<?php
/**
 * Test script to verify API endpoints
 * Run: php test_api_endpoints.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING API ENDPOINTS ===\n\n";

// Test 1: Check mobile_app_plans table
echo "1. Checking mobile_app_plans table:\n";
$plans = DB::table('mobile_app_plans')->select('id', 'name', 'price_monthly', 'price_yearly')->get();
echo "Found " . $plans->count() . " plans:\n";
foreach ($plans as $plan) {
    echo "  - {$plan->name}: {$plan->price_monthly} XAF/month\n";
}
echo "\n";

// Test 2: Check users with referral codes
echo "2. Checking users with referral codes:\n";
$users = DB::table('users')
    ->whereNotNull('referral_code')
    ->where('referral_code', '!=', '')
    ->select('id', 'name', 'email', 'referral_code')
    ->limit(5)
    ->get();
echo "Found " . $users->count() . " users with referral codes:\n";
foreach ($users as $user) {
    echo "  - {$user->name} ({$user->email}): {$user->referral_code}\n";
}
echo "\n";

// Test 3: Test getReferralCode controller logic
echo "3. Testing getReferralCode logic:\n";
if ($users->count() > 0) {
    $testUser = $users->first();
    echo "Using test user: {$testUser->email}\n";
    echo "Referral code in DB: '{$testUser->referral_code}'\n";
    
    // Simulate what the controller does
    $user = App\Models\User::find($testUser->id);
    echo "Referral code from model: '{$user->referral_code}'\n";
    
    $referralsCount = DB::table('referrals')
        ->where('referrer_user_id', $user->id)
        ->where('status', 'completed')
        ->count();
    echo "Completed referrals: {$referralsCount}\n";
}
echo "\n";

// Test 4: Check if mobile_subscription_plans is empty
echo "4. Checking mobile_subscription_plans table:\n";
$subPlans = DB::table('mobile_subscription_plans')->count();
echo "Found {$subPlans} plans (should be 0 or empty)\n";
echo "\n";

echo "=== TESTS COMPLETE ===\n";
