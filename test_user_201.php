<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

$user = \App\Models\User::find(201);

if (!$user) {
    echo "User 201 not found\n";
    exit(1);
}

echo "=== User 201 Subscription Check ===\n";
echo "User ID: {$user->id}\n";
echo "User Name: {$user->name}\n";
echo "User Email: {$user->email}\n\n";

$subscription = $user->activeMobileSubscription()->with('plan')->first();

if (!$subscription) {
    echo "❌ No active subscription found\n";
    echo "\nAll subscriptions for user 201:\n";
    foreach ($user->mobileSubscriptions as $sub) {
        echo "- ID: {$sub->id}, Plan ID: {$sub->mobile_app_plan_id}, Status: {$sub->status}, Expires: " . ($sub->expires_at ? $sub->expires_at->format('Y-m-d') : 'NULL') . "\n";
    }
    exit(1);
}

echo "✅ Active subscription found:\n";
echo "Subscription ID: {$subscription->id}\n";
echo "Plan ID: {$subscription->mobile_app_plan_id}\n";
echo "Plan Name: {$subscription->plan->name}\n";
echo "Plan Name (FR): {$subscription->plan->name_fr}\n";
echo "Price Monthly: {$subscription->plan->price_monthly}\n";
echo "Status: {$subscription->status}\n";
echo "Started At: " . ($subscription->started_at ? $subscription->started_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "Expires At: " . ($subscription->expires_at ? $subscription->expires_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "\n";

// Test what login API would return
echo "=== Login API Response Would Be ===\n";
$response = [
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ],
    'subscription' => [
        'plan_id' => $subscription->plan->id,
        'plan_name' => $subscription->plan->name,
        'status' => $subscription->status,
        'end_date' => $subscription->expires_at,
        'quotas' => [
            'searches_limit' => $subscription->plan->searches_limit,
            'ai_analyses_limit' => $subscription->plan->ai_analyses_limit,
            'pdf_downloads_limit' => $subscription->plan->pdf_downloads_limit,
        ],
    ],
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
