<?php
// Direct database check without Laravel
$host = 'localhost';
$db = 'threesixty_dossypro_legal_new';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== User 201 Subscription Check ===\n\n";
    
    // Get user info
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = 201");
    $stmt->execute();
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userRow) {
        echo "User 201 not found\n";
        exit(1);
    }
    
    echo "User ID: " . $userRow['id'] . "\n";
    echo "User Name: " . $userRow['name'] . "\n";
    echo "User Email: " . $userRow['email'] . "\n\n";
    
    // Get all subscriptions
    echo "=== All Subscriptions for User 201 ===\n";
    $stmt = $pdo->prepare("
        SELECT 
            mas.id, 
            mas.mobile_app_plan_id, 
            mas.status, 
            mas.started_at, 
            mas.expires_at,
            map.name as plan_name,
            map.name_fr,
            map.price_monthly
        FROM mobile_app_subscriptions mas
        LEFT JOIN mobile_app_plans map ON mas.mobile_app_plan_id = map.id
        WHERE mas.user_id = 201
        ORDER BY mas.created_at DESC
    ");
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo "No subscriptions found\n";
    } else {
        foreach ($subscriptions as $sub) {
            echo "ID: {$sub['id']}, Plan: {$sub['plan_name']} ({$sub['name_fr']}), Price: {$sub['price_monthly']}, Status: {$sub['status']}\n";
            echo "  Started: {$sub['started_at']}, Expires: " . ($sub['expires_at'] ? $sub['expires_at'] : 'NULL') . "\n";
        }
    }
    
    echo "\n=== Active Subscription (Status = 'active' AND expires_at NULL OR expires_at > NOW()) ===\n";
    $stmt = $pdo->prepare("
        SELECT 
            mas.id, 
            mas.mobile_app_plan_id, 
            mas.status, 
            mas.started_at, 
            mas.expires_at,
            map.name as plan_name,
            map.name_fr,
            map.price_monthly
        FROM mobile_app_subscriptions mas
        LEFT JOIN mobile_app_plans map ON mas.mobile_app_plan_id = map.id
        WHERE mas.user_id = 201
            AND mas.status = 'active'
            AND (mas.expires_at IS NULL OR mas.expires_at > NOW())
        ORDER BY mas.created_at DESC
        LIMIT 1
    ");
    $stmt->execute();
    $activeSub = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeSub) {
        echo "✅ FOUND:\n";
        echo "Subscription ID: {$activeSub['id']}\n";
        echo "Plan ID: {$activeSub['mobile_app_plan_id']}\n";
        echo "Plan Name: {$activeSub['plan_name']}\n";
        echo "Plan Name FR: {$activeSub['name_fr']}\n";
        echo "Price Monthly: {$activeSub['price_monthly']}\n";
        echo "Status: {$activeSub['status']}\n";
        echo "Started: {$activeSub['started_at']}\n";
        echo "Expires: " . ($activeSub['expires_at'] ? $activeSub['expires_at'] : 'NULL/NEVER') . "\n";
        
        echo "\n=== What Login API Should Return ===\n";
        echo json_encode([
            'plan_id' => (int)$activeSub['mobile_app_plan_id'],
            'plan_name' => $activeSub['plan_name'],
            'status' => $activeSub['status'],
            'end_date' => $activeSub['expires_at'],
        ], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ NO ACTIVE SUBSCRIPTION FOUND\n";
    }
    
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
