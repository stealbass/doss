<?php
/**
 * Script de Diagnostic des Subscriptions Dupliquées
 * 
 * Ce script affiche les users avec plusieurs subscriptions ACTIVES
 * et propose des corrections SQL
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Subscriptions - DOSSY PRO</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #2c3e50; border-bottom: 3px solid #27ae60; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 15px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #34495e; color: white; }
        tr:hover { background-color: #f5f5f5; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-warning { background: #f39c12; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnostic Subscriptions Dupliquées - DOSSY PRO</h1>
    <p><strong>Date:</strong> <?= date('Y-m-d H:i:s') ?></p>

    <?php
    use Illuminate\Support\Facades\DB;
    
    // ===== 1. VÉRIFICATION SUBSCRIPTIONS DUPLIQUÉES =====
    echo '<h2>📊 1. Users avec Subscriptions Dupliquées</h2>';
    
    $duplicates = DB::table('mobile_app_subscriptions')
        ->select('user_id', DB::raw('COUNT(*) as count'))
        ->where('status', 'active')
        ->groupBy('user_id')
        ->having(DB::raw('COUNT(*)'), '>', 1)
        ->get();
    
    if ($duplicates->count() == 0) {
        echo '<div class="success">✅ Aucun user avec subscriptions dupliquées détecté!</div>';
    } else {
        echo '<div class="error">❌ ' . $duplicates->count() . ' user(s) avec subscriptions dupliquées:</div>';
        
        echo '<table>';
        echo '<tr><th>User ID</th><th>Nombre d\'Actives</th><th>Action</th></tr>';
        foreach ($duplicates as $dup) {
            echo '<tr>';
            echo '<td>' . $dup->user_id . '</td>';
            echo '<td><span class="badge badge-danger">' . $dup->count . ' actives</span></td>';
            echo '<td><a href="#fix-user-' . $dup->user_id . '">Voir correction</a></td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    // ===== 2. DÉTAIL DES SUBSCRIPTIONS PAR USER =====
    echo '<h2>📋 2. Détail des Subscriptions</h2>';
    
    $allSubscriptions = DB::table('mobile_app_subscriptions')
        ->join('users', 'mobile_app_subscriptions.user_id', '=', 'users.id')
        ->join('mobile_app_plans', 'mobile_app_subscriptions.mobile_app_plan_id', '=', 'mobile_app_plans.id')
        ->select(
            'mobile_app_subscriptions.id',
            'users.id as user_id',
            'users.name',
            'users.email',
            'mobile_app_subscriptions.status',
            'mobile_app_plans.name as plan_name',
            'mobile_app_subscriptions.created_at',
            'mobile_app_subscriptions.expires_at'
        )
        ->where('mobile_app_subscriptions.status', 'active')
        ->orderBy('users.id')
        ->orderBy('mobile_app_subscriptions.created_at', 'desc')
        ->get();
    
    if ($allSubscriptions->count() > 0) {
        echo '<table>';
        echo '<tr><th>Sub ID</th><th>User ID</th><th>Email</th><th>Plan</th><th>Status</th><th>Créée</th><th>Expire</th></tr>';
        
        $currentUserId = null;
        foreach ($allSubscriptions as $sub) {
            $rowClass = '';
            if ($sub->user_id !== $currentUserId) {
                $currentUserId = $sub->user_id;
            }
            
            echo '<tr>';
            echo '<td>' . $sub->id . '</td>';
            echo '<td>' . $sub->user_id . '</td>';
            echo '<td>' . $sub->email . '</td>';
            echo '<td>' . $sub->plan_name . '</td>';
            echo '<td><span class="badge badge-warning">' . $sub->status . '</span></td>';
            echo '<td>' . (new DateTime($sub->created_at))->format('d/m/Y H:i') . '</td>';
            echo '<td>' . ($sub->expires_at ? (new DateTime($sub->expires_at))->format('d/m/Y') : 'Never') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    // ===== 3. SCRIPTS SQL DE CORRECTION =====
    echo '<h2>🔧 3. Scripts SQL de Correction</h2>';
    
    if ($duplicates->count() > 0) {
        echo '<div class="warning">';
        echo '<h3>⚠️ Correction pour chaque User Problématique:</h3>';
        
        foreach ($duplicates as $dup) {
            echo '<div id="fix-user-' . $dup->user_id . '">';
            echo '<h4>User ID: ' . $dup->user_id . '</h4>';
            echo '<pre>';
            echo "-- Voir les subscriptions de ce user\n";
            echo "SELECT id, status, created_at, expires_at FROM mobile_app_subscriptions\n";
            echo "WHERE user_id = " . $dup->user_id . "\n";
            echo "ORDER BY created_at DESC;\n\n";
            echo "-- Marquer l'ancienne comme CANCELLED (garder seulement la plus récente)\n";
            echo "UPDATE mobile_app_subscriptions\n";
            echo "SET status = 'cancelled'\n";
            echo "WHERE user_id = " . $dup->user_id . "\n";
            echo "AND status = 'active'\n";
            echo "AND id NOT IN (\n";
            echo "    SELECT id FROM (\n";
            echo "        SELECT id FROM mobile_app_subscriptions\n";
            echo "        WHERE user_id = " . $dup->user_id . "\n";
            echo "        AND status = 'active'\n";
            echo "        ORDER BY created_at DESC\n";
            echo "        LIMIT 1\n";
            echo "    ) AS latest\n";
            echo ");\n";
            echo '</pre>';
            echo '</div>';
        }
        echo '</div>';
    }

    // ===== 4. SCRIPT SQL GÉNÉRIQUE =====
    echo '<h2>🔧 4. Script SQL Générique (Tous les Users)</h2>';
    
    echo '<div class="warning">';
    echo '<p><strong>Exécutez ce script pour corriger TOUS les users avec subscriptions dupliquées:</strong></p>';
    echo '<pre>';
echo "-- Marquer comme CANCELLED toutes les anciennes subscriptions\n";
echo "-- Garder seulement la plus récente par user\n";
echo "UPDATE mobile_app_subscriptions m1\n";
echo "SET status = \'cancelled\'\n";
echo "WHERE status = \'active\'\n";
echo "AND id NOT IN (\n";
echo "    SELECT id FROM (\n";
echo "        SELECT id\n";
echo "        FROM mobile_app_subscriptions m2\n";
echo "        WHERE m2.user_id = m1.user_id\n";
echo "        AND m2.status = \'active\'\n";
echo "        ORDER BY m2.created_at DESC\n";
echo "        LIMIT 1\n";
echo "    ) AS latest_per_user\n";
echo ");\n\n";
echo "-- Vérifier qu'il ne reste qu'une active par user\n";
echo "SELECT user_id, COUNT(*) as active_count\n";
echo "FROM mobile_app_subscriptions\n";
echo "WHERE status = \'active\'\n";
echo "GROUP BY user_id\n";
echo "HAVING COUNT(*) > 1;\n";
    echo '</pre>';
    echo '</div>';

    // ===== 5. VÉRIFICATION DE LA CORRECTION =====
    echo '<h2>✅ 5. Vérification Post-Correction</h2>';
    
    $checkDuplicates = DB::table('mobile_app_subscriptions')
        ->select('user_id', DB::raw('COUNT(*) as active_count'))
        ->where('status', 'active')
        ->groupBy('user_id')
        ->having(DB::raw('COUNT(*)'), '>', 1)
        ->count();
    
    echo '<p>Exécutez ce SQL après la correction pour vérifier qu\'il ne reste plus de doublons:</p>';
    echo '<pre>';
echo "SELECT user_id, COUNT(*) as active_count\n";
echo "FROM mobile_app_subscriptions\n";
echo "WHERE status = \'active\'\n";
echo "GROUP BY user_id\n";
echo "HAVING COUNT(*) > 1;\n\n";
echo "-- Ce SELECT ne devrait retourner AUCUNE ligne si la correction est complète\n";
    echo '</pre>';

    // ===== 6. CHECKLIST =====
    echo '<h2>📋 6. Checklist de Correction</h2>';
    
    echo '<div class="info">';
    echo '<ul>';
    echo '<li>[ ] Exécuter le script SQL générique dans phpMyAdmin</li>';
    echo '<li>[ ] Vérifier avec le SELECT de contrôle (pas de résultats = OK)</li>';
    echo '<li>[ ] Exécuter: <code>php artisan cache:clear</code></li>';
    echo '<li>[ ] Exécuter: <code>php artisan config:clear</code></li>';
    echo '<li>[ ] Redémarrer l\'app Flutter: <code>flutter run</code></li>';
    echo '<li>[ ] Se connecter avec un user affecté</li>';
    echo '<li>[ ] Vérifier que le plan s\'affiche correctement</li>';
    echo '</ul>';
    echo '</div>';

    // ===== 7. APRÈS LA CORRECTION =====
    echo '<h2>🚀 7. Après la Correction</h2>';
    
    echo '<div class="success">';
    echo '<p>Une fois les corrections appliquées, votre app Flutter devrait:</p>';
    echo '<ul>';
    echo '<li>✅ Afficher le bon plan au login</li>';
    echo '<li>✅ Déverrouiller les cartes correctes</li>';
    echo '<li>✅ Charger les données depuis l\'API backend</li>';
    echo '</ul>';
    echo '</div>';

    ?>

    <hr style="margin: 30px 0;">
    <p style="text-align: center; color: #7f8c8d;">
        <small>Diagnostic Subscriptions - <?= date('Y-m-d H:i:s') ?></small>
    </p>
</div>
</body>
</html>
