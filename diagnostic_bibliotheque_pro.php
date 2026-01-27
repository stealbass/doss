<?php
/**
 * Diagnostic Rapide - Calculateurs, Veille Juridique, Alertes
 * Vérifie que toutes les données sont prêtes pour les tests
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSTIC BIBLIOTHÈQUE PRO - 3 FONCTIONNALITÉS ===\n\n";

$errors = 0;
$warnings = 0;

// ===== 1. CALCULATEURS =====
echo "📊 1. CALCULATEURS\n";
echo str_repeat("=", 50) . "\n";

$calculatorsCount = DB::table('calculator_configs')->count();
echo "✓ Total calculateurs : $calculatorsCount\n";

if ($calculatorsCount == 0) {
    echo "❌ ERREUR : Aucun calculateur configuré\n";
    $errors++;
} else {
    $byCountry = DB::select("SELECT country, COUNT(*) as count FROM calculator_configs GROUP BY country");
    foreach ($byCountry as $row) {
        echo "  → {$row->country}: {$row->count} calculateur(s)\n";
    }
}

$calculatorLogs = DB::table('calculator_logs')->count();
echo "✓ Historique calculs : $calculatorLogs entrée(s)\n\n";

// ===== 2. VEILLE JURIDIQUE =====
echo "👁️  2. VEILLE JURIDIQUE\n";
echo str_repeat("=", 50) . "\n";

// Vérifier si la table existe
try {
    $newsCount = DB::table('legal_news')->count();
    echo "✓ Total actualités : $newsCount\n";
    
    if ($newsCount == 0) {
        echo "⚠️  ATTENTION : Aucune actualité disponible\n";
        $warnings++;
    } else {
        $recentNews = DB::table('legal_news')
            ->where('published_at', '>=', now()->subDays(7))
            ->count();
        echo "  → Actualités (7 derniers jours) : $recentNews\n";
        
        $byCategory = DB::select("SELECT category, COUNT(*) as count FROM legal_news GROUP BY category LIMIT 5");
        echo "  → Par catégorie :\n";
        foreach ($byCategory as $row) {
            echo "     • {$row->category}: {$row->count}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ ERREUR : Table 'legal_news' n'existe pas\n";
    echo "   Solution : Créer la migration pour legal_news\n";
    $errors++;
}

echo "\n";

// ===== 3. ALERTES JURIDIQUES =====
echo "🔔 3. ALERTES JURIDIQUES\n";
echo str_repeat("=", 50) . "\n";

$alertsCount = DB::table('legal_alerts')->count();
$alertsVisible = DB::table('legal_alerts')->where('is_mobile_visible', 1)->count();

echo "✓ Total alertes : $alertsCount\n";
echo "✓ Alertes visibles mobile : $alertsVisible\n";

if ($alertsCount == 0) {
    echo "⚠️  ATTENTION : Aucune alerte créée\n";
    $warnings++;
} elseif ($alertsVisible == 0) {
    echo "❌ ERREUR : Aucune alerte visible dans l'app mobile\n";
    echo "   Solution : UPDATE legal_alerts SET is_mobile_visible = 1;\n";
    $errors++;
} else {
    $byPriority = DB::select("SELECT priority, COUNT(*) as count FROM legal_alerts WHERE is_mobile_visible = 1 GROUP BY priority");
    echo "  → Par priorité :\n";
    foreach ($byPriority as $row) {
        $emoji = $row->priority == 'urgent' ? '🔴' : ($row->priority == 'high' ? '🟡' : '🟢');
        echo "     $emoji {$row->priority}: {$row->count}\n";
    }
}

echo "\n";

// ===== 4. VÉRIFICATION ROUTES API =====
echo "🌐 4. ROUTES API MOBILES\n";
echo str_repeat("=", 50) . "\n";

$routes = [
    '/api/mobile/calculators',
    '/api/mobile/legal-monitoring',
    '/api/mobile/legal-alerts',
];

echo "Routes attendues :\n";
foreach ($routes as $route) {
    echo "  ✓ $route\n";
}

echo "\n";

// ===== 5. PERMISSIONS UTILISATEUR TEST =====
echo "👤 5. UTILISATEUR TEST (ID 201)\n";
echo str_repeat("=", 50) . "\n";

$testUser = DB::table('users')->where('id', 201)->first();
if ($testUser) {
    echo "✓ Nom : {$testUser->name}\n";
    echo "✓ Email : {$testUser->email}\n";
    echo "✓ Plan : {$testUser->plan}\n";
    
    $plan = strtolower($testUser->plan);
    $hasAccess = !in_array($plan, ['free', 'gratuit', 'essentiel', 'essential']);
    
    if ($hasAccess) {
        echo "✅ ACCÈS AUTORISÉ aux 3 fonctionnalités\n";
    } else {
        echo "❌ ACCÈS REFUSÉ - Plan insuffisant\n";
        echo "   Solution : UPDATE users SET plan = 'Pro' WHERE id = 201;\n";
        $errors++;
    }
} else {
    echo "⚠️  Utilisateur test non trouvé\n";
    $warnings++;
}

echo "\n";

// ===== 6. DONNÉES DE DÉMONSTRATION =====
echo "📦 6. DONNÉES DE DÉMONSTRATION\n";
echo str_repeat("=", 50) . "\n";

$demoData = [
    'calculator_configs' => $calculatorsCount,
    'legal_alerts (visible)' => $alertsVisible,
];

try {
    $demoData['legal_news'] = DB::table('legal_news')->count();
} catch (Exception $e) {
    $demoData['legal_news'] = 0;
}

$allReady = true;
foreach ($demoData as $table => $count) {
    $status = $count > 0 ? '✅' : '❌';
    echo "$status $table: $count\n";
    if ($count == 0) $allReady = false;
}

echo "\n";

// ===== RÉSUMÉ =====
echo str_repeat("=", 50) . "\n";
echo "RÉSUMÉ DU DIAGNOSTIC\n";
echo str_repeat("=", 50) . "\n";

if ($errors == 0 && $warnings == 0) {
    echo "✅ TOUT EST PRÊT POUR LES TESTS !\n\n";
    echo "Vous pouvez maintenant :\n";
    echo "1. Ouvrir l'app Flutter\n";
    echo "2. Se connecter avec l'utilisateur ID 201\n";
    echo "3. Aller dans Bibliothèque Pro\n";
    echo "4. Tester les 3 fonctionnalités\n";
} else {
    echo "⚠️  $errors erreur(s), $warnings avertissement(s)\n\n";
    
    if ($errors > 0) {
        echo "CORRECTIONS NÉCESSAIRES :\n";
        echo "-------------------------\n";
        
        if ($calculatorsCount == 0) {
            echo "1. Ajouter des calculateurs de test\n";
        }
        
        if ($alertsVisible == 0 && $alertsCount > 0) {
            echo "2. Rendre les alertes visibles :\n";
            echo "   UPDATE legal_alerts SET is_mobile_visible = 1;\n";
        }
        
        if ($testUser && !$hasAccess) {
            echo "3. Mettre à jour le plan utilisateur :\n";
            echo "   UPDATE users SET plan = 'Pro' WHERE id = 201;\n";
        }
    }
}

echo "\n";

// ===== SQL RAPIDES =====
if ($errors > 0 || $warnings > 0) {
    echo "📝 SCRIPTS SQL DE RÉPARATION\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "-- Rendre toutes les alertes visibles\n";
    echo "UPDATE legal_alerts SET is_mobile_visible = 1;\n\n";
    
    echo "-- Mettre utilisateur 201 en plan Pro\n";
    echo "UPDATE users SET plan = 'Pro' WHERE id = 201;\n\n";
    
    if ($calculatorsCount == 0) {
        echo "-- Ajouter un calculateur de test\n";
        echo "INSERT INTO calculator_configs (name, country, category, formula, inputs, created_at) VALUES\n";
        echo "('Indemnités de licenciement', 'CI', 'Droit du Travail', 'salary * years * 0.5', '[]', NOW());\n\n";
    }
}

echo "\nDate : " . date('Y-m-d H:i:s') . "\n";
