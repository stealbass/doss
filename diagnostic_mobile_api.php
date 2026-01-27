<?php
/**
 * Script de Diagnostic API Mobile - DOSSY PRO
 * 
 * Ce script vérifie la configuration du backend pour l'app mobile
 * Placez ce fichier à la racine du projet Laravel et accédez via navigateur
 */

// Charger Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic API Mobile - DOSSY PRO</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #27ae60; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 15px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #34495e; color: white; }
        tr:hover { background-color: #f5f5f5; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #27ae60; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-warning { background: #f39c12; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnostic API Mobile - DOSSY PRO</h1>
    <p><strong>Date:</strong> <?= date('Y-m-d H:i:s') ?></p>

    <?php
    use Illuminate\Support\Facades\DB;
    
    $issues = [];
    $warnings = [];
    $success = [];

    // ===== 1. VÉRIFICATION BASE DE DONNÉES =====
    echo '<h2>📊 1. Vérification Base de Données</h2>';
    
    try {
        DB::connection()->getPdo();
        echo '<div class="success">✅ Connexion base de données: <strong>OK</strong></div>';
    } catch (\Exception $e) {
        echo '<div class="error">❌ Erreur connexion BDD: ' . $e->getMessage() . '</div>';
        $issues[] = 'Connexion base de données échouée';
    }

    // ===== 2. VÉRIFICATION UTILISATEURS =====
    echo '<h2>👥 2. Utilisateurs et Plans</h2>';
    
    $users = DB::table('users')->select('id', 'name', 'email', 'plan', 'country')->limit(10)->get();
    
    if ($users->count() > 0) {
        echo '<div class="success">✅ ' . $users->count() . ' utilisateur(s) trouvé(s)</div>';
        
        echo '<table>';
        echo '<tr><th>ID</th><th>Nom</th><th>Email</th><th>Plan</th><th>Pays</th></tr>';
        foreach ($users as $user) {
            $planBadge = 'badge-warning';
            if (in_array($user->plan, ['Professionnel', 'Cabinet/Entreprise'])) {
                $planBadge = 'badge-success';
            }
            
            echo '<tr>';
            echo '<td>' . $user->id . '</td>';
            echo '<td>' . $user->name . '</td>';
            echo '<td>' . $user->email . '</td>';
            echo '<td><span class="badge ' . $planBadge . '">' . ($user->plan ?? 'N/A') . '</span></td>';
            echo '<td>' . ($user->country ?? 'N/A') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Vérifier les plans distincts
        $distinctPlans = DB::table('users')->distinct()->pluck('plan');
        echo '<div class="info"><strong>Plans disponibles:</strong> ' . $distinctPlans->implode(', ') . '</div>';
        
        if (!$distinctPlans->contains('Cabinet/Entreprise')) {
            $warnings[] = 'Aucun utilisateur avec plan Cabinet/Entreprise trouvé';
        }
    } else {
        echo '<div class="error">❌ Aucun utilisateur trouvé</div>';
        $issues[] = 'Pas d\'utilisateurs dans la base';
    }

    // ===== 3. VÉRIFICATION TEMPLATES =====
    echo '<h2>📄 3. Modèles de Documents (Templates)</h2>';
    
    $templatesCount = DB::table('document_templates')->count();
    $templatesMobileVisible = DB::table('document_templates')->where('is_mobile_visible', 1)->count();
    
    echo '<div class="info">';
    echo '<strong>Total templates:</strong> ' . $templatesCount . '<br>';
    echo '<strong>Visibles mobile:</strong> ' . $templatesMobileVisible;
    echo '</div>';
    
    if ($templatesMobileVisible == 0) {
        echo '<div class="error">❌ Aucun template visible pour l\'app mobile!</div>';
        $issues[] = 'Aucun template avec is_mobile_visible = 1';
        echo '<div class="warning">💡 <strong>Solution:</strong> <code>UPDATE document_templates SET is_mobile_visible = 1;</code></div>';
    } else {
        echo '<div class="success">✅ ' . $templatesMobileVisible . ' template(s) disponible(s) pour mobile</div>';
    }
    
    // Détails par pays
    $templatesByCountry = DB::table('document_templates')
        ->where('is_mobile_visible', 1)
        ->select('country', DB::raw('COUNT(*) as count'))
        ->groupBy('country')
        ->get();
    
    if ($templatesByCountry->count() > 0) {
        echo '<table>';
        echo '<tr><th>Pays</th><th>Nombre</th></tr>';
        foreach ($templatesByCountry as $row) {
            echo '<tr><td>' . ($row->country ?? 'Tous pays (NULL)') . '</td><td>' . $row->count . '</td></tr>';
        }
        echo '</table>';
    }

    // ===== 4. VÉRIFICATION RESSOURCES FISCALES =====
    echo '<h2>💼 4. Ressources Fiscales & Sociales</h2>';
    
    $fiscalCount = DB::table('fiscal_social_resources')->count();
    $fiscalMobileVisible = DB::table('fiscal_social_resources')->where('is_mobile_visible', 1)->count();
    $fiscalCurrentYear = DB::table('fiscal_social_resources')
        ->where('is_mobile_visible', 1)
        ->where('year', '>=', 2024)
        ->count();
    
    echo '<div class="info">';
    echo '<strong>Total ressources:</strong> ' . $fiscalCount . '<br>';
    echo '<strong>Visibles mobile:</strong> ' . $fiscalMobileVisible . '<br>';
    echo '<strong>Année 2024+:</strong> ' . $fiscalCurrentYear;
    echo '</div>';
    
    if ($fiscalCurrentYear == 0) {
        echo '<div class="error">❌ Aucune ressource fiscale récente visible pour mobile!</div>';
        $issues[] = 'Aucune ressource fiscale avec is_mobile_visible = 1 et year >= 2024';
        echo '<div class="warning">💡 <strong>Solution:</strong> <code>UPDATE fiscal_social_resources SET is_mobile_visible = 1 WHERE year >= 2024;</code></div>';
    } else {
        echo '<div class="success">✅ ' . $fiscalCurrentYear . ' ressource(s) récente(s) disponible(s)</div>';
    }
    
    // Détails par pays
    $fiscalByCountry = DB::table('fiscal_social_resources')
        ->where('is_mobile_visible', 1)
        ->where('year', '>=', 2024)
        ->select('country', DB::raw('COUNT(*) as count'))
        ->groupBy('country')
        ->get();
    
    if ($fiscalByCountry->count() > 0) {
        echo '<table>';
        echo '<tr><th>Pays</th><th>Nombre</th></tr>';
        foreach ($fiscalByCountry as $row) {
            echo '<tr><td>' . ($row->country ?? 'Tous pays (NULL)') . '</td><td>' . $row->count . '</td></tr>';
        }
        echo '</table>';
    }

    // ===== 5. VÉRIFICATION CALCULATEURS =====
    echo '<h2>🧮 5. Calculateurs & Simulateurs</h2>';
    
    $calcCount = DB::table('calculator_configs')->count();
    $calcMobileVisible = DB::table('calculator_configs')
        ->where('is_mobile_visible', 1)
        ->where('is_active', 1)
        ->count();
    
    echo '<div class="info">';
    echo '<strong>Total calculateurs:</strong> ' . $calcCount . '<br>';
    echo '<strong>Visibles mobile & actifs:</strong> ' . $calcMobileVisible;
    echo '</div>';
    
    if ($calcMobileVisible == 0) {
        echo '<div class="error">❌ Aucun calculateur disponible pour l\'app mobile!</div>';
        $issues[] = 'Aucun calculateur avec is_mobile_visible = 1 et is_active = 1';
        echo '<div class="warning">💡 <strong>Solution:</strong> <code>UPDATE calculator_configs SET is_mobile_visible = 1, is_active = 1;</code></div>';
    } else {
        echo '<div class="success">✅ ' . $calcMobileVisible . ' calculateur(s) disponible(s)</div>';
    }
    
    // Liste des calculateurs
    $calculators = DB::table('calculator_configs')
        ->where('is_mobile_visible', 1)
        ->where('is_active', 1)
        ->select('id', 'name', 'calculator_type', 'required_plan', 'country')
        ->get();
    
    if ($calculators->count() > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Nom</th><th>Type</th><th>Plan Requis</th><th>Pays</th></tr>';
        foreach ($calculators as $calc) {
            echo '<tr>';
            echo '<td>' . $calc->id . '</td>';
            echo '<td>' . $calc->name . '</td>';
            echo '<td>' . $calc->calculator_type . '</td>';
            echo '<td>' . ($calc->required_plan ?? 'N/A') . '</td>';
            echo '<td>' . ($calc->country ?? 'Tous') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    // ===== 6. VÉRIFICATION ALERTES JURIDIQUES =====
    echo '<h2>🔔 6. Alertes Juridiques</h2>';
    
    $alertsCount = DB::table('legal_alerts')->count();
    $alertsMobileVisible = DB::table('legal_alerts')->where('is_mobile_visible', 1)->count();
    
    echo '<div class="info">';
    echo '<strong>Total alertes:</strong> ' . $alertsCount . '<br>';
    echo '<strong>Visibles mobile:</strong> ' . $alertsMobileVisible;
    echo '</div>';
    
    if ($alertsMobileVisible == 0 && $alertsCount > 0) {
        echo '<div class="warning">⚠️ Alertes existent mais ne sont pas visibles pour mobile</div>';
        echo '<div class="warning">💡 <strong>Solution:</strong> <code>UPDATE legal_alerts SET is_mobile_visible = 1;</code></div>';
    } elseif ($alertsCount == 0) {
        echo '<div class="warning">⚠️ Aucune alerte juridique créée</div>';
    } else {
        echo '<div class="success">✅ ' . $alertsMobileVisible . ' alerte(s) disponible(s)</div>';
    }

    // ===== 7. VÉRIFICATION CATÉGORIES =====
    echo '<h2>📁 7. Catégories</h2>';
    
    $categoriesCount = DB::table('legal_categories')->count();
    
    echo '<div class="info"><strong>Total catégories:</strong> ' . $categoriesCount . '</div>';
    
    if ($categoriesCount == 0) {
        echo '<div class="warning">⚠️ Aucune catégorie juridique créée</div>';
        $warnings[] = 'Pas de catégories juridiques';
    } else {
        $categories = DB::table('legal_categories')
            ->select('id', 'name', 'slug')
            ->limit(10)
            ->get();
        
        echo '<table>';
        echo '<tr><th>ID</th><th>Nom</th><th>Slug</th></tr>';
        foreach ($categories as $cat) {
            echo '<tr>';
            echo '<td>' . $cat->id . '</td>';
            echo '<td>' . $cat->name . '</td>';
            echo '<td>' . $cat->slug . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    // ===== 8. RÉSUMÉ ET SOLUTIONS =====
    echo '<h2>📋 8. Résumé et Actions Recommandées</h2>';
    
    if (count($issues) == 0 && count($warnings) == 0) {
        echo '<div class="success">🎉 <strong>Tout semble bon!</strong> Le backend est correctement configuré pour l\'app mobile.</div>';
    } else {
        if (count($issues) > 0) {
            echo '<div class="error">';
            echo '<strong>❌ Problèmes critiques détectés:</strong><ul>';
            foreach ($issues as $issue) {
                echo '<li>' . $issue . '</li>';
            }
            echo '</ul></div>';
        }
        
        if (count($warnings) > 0) {
            echo '<div class="warning">';
            echo '<strong>⚠️ Avertissements:</strong><ul>';
            foreach ($warnings as $warning) {
                echo '<li>' . $warning . '</li>';
            }
            echo '</ul></div>';
        }
        
        echo '<div class="info">';
        echo '<h3>🔧 Script SQL de Correction Rapide</h3>';
        echo '<pre style="background:#2c3e50; color:#ecf0f1; padding:15px; border-radius:5px; overflow-x:auto;">';
        echo "-- Activer la visibilité mobile pour tous les documents\n";
        echo "UPDATE document_templates SET is_mobile_visible = 1;\n";
        echo "UPDATE fiscal_social_resources SET is_mobile_visible = 1;\n";
        echo "UPDATE calculator_configs SET is_mobile_visible = 1, is_active = 1;\n";
        echo "UPDATE legal_alerts SET is_mobile_visible = 1;\n\n";
        
        echo "-- Vérifier/Corriger le plan d'un utilisateur spécifique\n";
        echo "UPDATE users SET plan = 'Cabinet/Entreprise' WHERE email = 'votre-email@example.com';\n\n";
        
        echo "-- Rendre les documents accessibles à tous les pays (temporaire)\n";
        echo "UPDATE document_templates SET country = NULL;\n";
        echo "UPDATE fiscal_social_resources SET country = NULL;\n";
        echo "UPDATE calculator_configs SET country = NULL;\n";
        echo '</pre>';
        echo '</div>';
    }

    // ===== 9. ENDPOINTS API =====
    echo '<h2>🌐 9. Endpoints API Mobile</h2>';
    
    $baseUrl = url('/api/mobile');
    
    echo '<div class="info">';
    echo '<p><strong>Base URL:</strong> <code>' . $baseUrl . '</code></p>';
    echo '<p><strong>Endpoints disponibles:</strong></p>';
    echo '<ul>';
    echo '<li>GET <code>/templates</code> - Liste des modèles de documents</li>';
    echo '<li>GET <code>/fiscal-resources</code> - Ressources fiscales et sociales</li>';
    echo '<li>GET <code>/calculators</code> - Calculateurs et simulateurs</li>';
    echo '<li>GET <code>/legal-alerts</code> - Alertes juridiques</li>';
    echo '<li>POST <code>/legal-library/search</code> - Recherche bibliothèque juridique</li>';
    echo '</ul>';
    echo '<p><strong>Note:</strong> Toutes les requêtes nécessitent un header <code>Authorization: Bearer TOKEN</code></p>';
    echo '</div>';

    ?>

    <hr style="margin: 30px 0;">
    <p style="text-align: center; color: #7f8c8d;">
        <small>Diagnostic DOSSY PRO - <?= date('Y-m-d H:i:s') ?></small>
    </p>
</div>
</body>
</html>
