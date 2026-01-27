<?php
/**
 * Diagnostic - Vérifier plan utilisateur et permissions Anonymisation
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSTIC ANONYMISATION ===\n\n";

// Récupérer tous les utilisateurs pour vérifier les plans
$users = DB::table('users')
    ->select('id', 'name', 'email', 'plan')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo "📊 PLANS D'ABONNEMENT ACTUELS\n";
echo str_repeat("=", 60) . "\n";

foreach ($users as $user) {
    echo "ID: {$user->id} | Nom: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo "  Plan: {$user->plan}\n";
    
    // Vérifier si l'utilisateur a accès à l'anonymisation
    $plan = strtolower($user->plan);
    $plans = ['professionnel', 'cabinet', 'entreprise', 'cabinet/entreprise'];
    
    $hasAccess = false;
    foreach ($plans as $p) {
        if (strpos($plan, $p) !== false) {
            $hasAccess = true;
            break;
        }
    }
    
    if ($hasAccess) {
        echo "  ✅ Accès ANONYMISATION\n";
    } else {
        echo "  ❌ Pas d'accès anonymisation\n";
    }
    
    echo "\n";
}

echo "\n📝 VÉRIFICATION DE L'UTILISATEUR TEST\n";
echo str_repeat("=", 60) . "\n";

$testUser = DB::table('users')->where('id', 201)->first();
if ($testUser) {
    echo "✅ Utilisateur trouvé\n";
    echo "   ID: {$testUser->id}\n";
    echo "   Nom: {$testUser->name}\n";
    echo "   Email: {$testUser->email}\n";
    echo "   Plan: {$testUser->plan}\n";
    
    $plan = strtolower($testUser->plan);
    $hasAnon = in_array($plan, ['professionnel', 'cabinet', 'entreprise']) || 
               strpos($plan, 'cabinet') !== false ||
               strpos($plan, 'professionnel') !== false;
    
    if ($hasAnon) {
        echo "   ✅ Devrait avoir accès à l'Anonymisation\n";
    } else {
        echo "   ❌ N'a PAS accès à l'Anonymisation\n";
        echo "   \n   💡 SOLUTION: Mettre à jour le plan\n";
        echo "      UPDATE users SET plan = 'Professionnel' WHERE id = 201;\n";
    }
} else {
    echo "❌ Utilisateur 201 non trouvé\n";
}

echo "\n🔍 VÉRIFICATION GETTER hasAnonymization (Flutter)\n";
echo str_repeat("=", 60) . "\n";

echo "Les plans qui donnent accès à l'Anonymisation :\n";
echo "✅ Professionnel\n";
echo "✅ Cabinet\n";
echo "✅ Entreprise\n";
echo "✅ Cabinet/Entreprise\n";

echo "\nLes plans qui N'ONT PAS accès :\n";
echo "❌ Essentiel\n";
echo "❌ Essential\n";
echo "❌ Standard\n";
echo "❌ Gratuit\n";

echo "\n";
