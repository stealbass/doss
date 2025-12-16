<?php

namespace App\Http\Controllers;

use App\Models\MobileAppPlan;
use App\Models\MobileAppSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileAppPlansController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la liste des plans
     */
    public function index()
    {
        $plans = MobileAppPlan::withCount(['subscriptions as active_subscriptions_count' => function($q) {
            $q->where('status', 'active')->where('expires_at', '>', now());
        }])
        ->withCount('subscriptions as total_subscriptions_count')
        ->orderBy('price_monthly', 'asc')
        ->get();

        // Statistiques globales
        $stats = [
            'total_plans' => MobileAppPlan::count(),
            'active_plans' => MobileAppPlan::where('is_active', true)->count(),
            'total_subscriptions' => MobileAppSubscription::where('status', 'active')
                ->where('expires_at', '>', now())->count(),
            'monthly_revenue' => MobileAppSubscription::where('status', 'active')
                ->where('expires_at', '>', now())
                ->join('mobile_app_plans', 'mobile_app_subscriptions.plan_id', '=', 'mobile_app_plans.id')
                ->sum('mobile_app_plans.price_monthly'),
        ];

        return view('mobile-app-plans.index', compact('plans', 'stats'));
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        $aiModels = [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Rapide, Économique)',
            'gpt-4' => 'GPT-4 (Haute Qualité)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Meilleur Performance)',
            'gpt-4o' => 'GPT-4o (Optimisé)',
        ];

        return view('mobile-app-plans.create', compact('aiModels'));
    }

    /**
     * Enregistre un nouveau plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:mobile_app_plans,name',
            'name_fr' => 'required|string|max:100',
            'price_monthly' => 'required|integer|min:0',
            'price_yearly' => 'required|integer|min:0',
            'searches_limit' => 'required|integer|min:-1',
            'ai_analyses_limit' => 'required|integer|min:-1',
            'pdf_downloads_limit' => 'required|integer|min:-1',
            'has_full_history' => 'boolean',
            'has_advanced_ai' => 'boolean',
            'ai_model' => 'required|string|max:50',
            'max_tokens' => 'required|integer|min:100|max:100000',
            'is_active' => 'boolean',
        ]);

        // Convertir les checkbox
        $validated['has_full_history'] = $request->has('has_full_history');
        $validated['has_advanced_ai'] = $request->has('has_advanced_ai');
        $validated['is_active'] = $request->has('is_active');

        $plan = MobileAppPlan::create($validated);

        return redirect()->route('mobile-app-plans.index')
            ->with('success', "Plan '{$plan->name_fr}' créé avec succès !");
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit($id)
    {
        $plan = MobileAppPlan::findOrFail($id);
        
        $aiModels = [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Rapide, Économique)',
            'gpt-4' => 'GPT-4 (Haute Qualité)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Meilleur Performance)',
            'gpt-4o' => 'GPT-4o (Optimisé)',
        ];

        return view('mobile-app-plans.edit', compact('plan', 'aiModels'));
    }

    /**
     * Met à jour un plan
     */
    public function update(Request $request, $id)
    {
        $plan = MobileAppPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:mobile_app_plans,name,' . $id,
            'name_fr' => 'required|string|max:100',
            'price_monthly' => 'required|integer|min:0',
            'price_yearly' => 'required|integer|min:0',
            'searches_limit' => 'required|integer|min:-1',
            'ai_analyses_limit' => 'required|integer|min:-1',
            'pdf_downloads_limit' => 'required|integer|min:-1',
            'has_full_history' => 'boolean',
            'has_advanced_ai' => 'boolean',
            'ai_model' => 'required|string|max:50',
            'max_tokens' => 'required|integer|min:100|max:100000',
            'is_active' => 'boolean',
        ]);

        // Convertir les checkbox
        $validated['has_full_history'] = $request->has('has_full_history');
        $validated['has_advanced_ai'] = $request->has('has_advanced_ai');
        $validated['is_active'] = $request->has('is_active');

        $plan->update($validated);

        return redirect()->route('mobile-app-plans.index')
            ->with('success', "Plan '{$plan->name_fr}' mis à jour avec succès !");
    }

    /**
     * Active/Désactive un plan
     */
    public function toggleActive($id)
    {
        $plan = MobileAppPlan::findOrFail($id);
        
        // Ne pas permettre de désactiver si des abonnements actifs existent
        if ($plan->is_active) {
            $activeSubscriptions = $plan->subscriptions()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->count();

            if ($activeSubscriptions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de désactiver ce plan. {$activeSubscriptions} abonnement(s) actif(s) existe(nt)."
                ], 400);
            }
        }

        $plan->is_active = !$plan->is_active;
        $plan->save();

        $status = $plan->is_active ? 'activé' : 'désactivé';
        return response()->json([
            'success' => true,
            'message' => "Plan '{$plan->name_fr}' {$status} avec succès !",
            'is_active' => $plan->is_active
        ]);
    }

    /**
     * Supprime un plan
     */
    public function destroy($id)
    {
        $plan = MobileAppPlan::findOrFail($id);

        // Vérifier qu'aucun abonnement n'existe
        $subscriptionsCount = $plan->subscriptions()->count();
        
        if ($subscriptionsCount > 0) {
            return redirect()->route('mobile-app-plans.index')
                ->with('error', "Impossible de supprimer ce plan. {$subscriptionsCount} abonnement(s) existe(nt).");
        }

        $planName = $plan->name_fr;
        $plan->delete();

        return redirect()->route('mobile-app-plans.index')
            ->with('success', "Plan '{$planName}' supprimé avec succès !");
    }

    /**
     * Affiche la page de comparaison des plans
     */
    public function comparison()
    {
        $plans = MobileAppPlan::where('is_active', true)
            ->orderBy('price_monthly', 'asc')
            ->get();

        return view('mobile-app-plans.comparison', compact('plans'));
    }

    /**
     * Clone un plan existant
     */
    public function duplicate($id)
    {
        $originalPlan = MobileAppPlan::findOrFail($id);
        
        $newPlan = $originalPlan->replicate();
        $newPlan->name = $originalPlan->name . '_copy';
        $newPlan->name_fr = $originalPlan->name_fr . ' (Copie)';
        $newPlan->is_active = false; // Désactivé par défaut
        $newPlan->save();

        return redirect()->route('mobile-app-plans.edit', $newPlan->id)
            ->with('success', "Plan dupliqué avec succès ! Vous pouvez maintenant le modifier.");
    }

    /**
     * Obtient les statistiques d'un plan spécifique
     */
    public function statistics($id)
    {
        $plan = MobileAppPlan::findOrFail($id);

        $stats = [
            'total_subscriptions' => $plan->subscriptions()->count(),
            'active_subscriptions' => $plan->subscriptions()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->count(),
            'expired_subscriptions' => $plan->subscriptions()
                ->where(function($q) {
                    $q->where('status', 'expired')
                      ->orWhere('expires_at', '<=', now());
                })
                ->count(),
            'monthly_revenue' => $plan->subscriptions()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->count() * $plan->price_monthly,
            'total_revenue' => $plan->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            'avg_subscription_duration' => $plan->subscriptions()
                ->selectRaw('AVG(DATEDIFF(expires_at, starts_at)) as avg_days')
                ->value('avg_days'),
            'new_subscriptions_this_month' => $plan->subscriptions()
                ->whereMonth('created_at', now()->month)
                ->count(),
            'churn_rate' => $this->calculateChurnRate($plan),
        ];

        return response()->json($stats);
    }

    /**
     * Calcule le taux de churn (désabonnement) d'un plan
     */
    private function calculateChurnRate($plan)
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $activeAtStart = $plan->subscriptions()
            ->where('status', 'active')
            ->where('starts_at', '<', $startOfMonth)
            ->where('expires_at', '>', $startOfMonth)
            ->count();

        $churned = $plan->subscriptions()
            ->where('status', 'cancelled')
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        if ($activeAtStart === 0) {
            return 0;
        }

        return round(($churned / $activeAtStart) * 100, 2);
    }

    /**
     * Récupère les données pour un graphique d'évolution
     */
    public function chartData($id)
    {
        $plan = MobileAppPlan::findOrFail($id);
        
        // Données des 12 derniers mois
        $months = [];
        $subscriptions = [];
        $revenue = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->locale('fr')->isoFormat('MMM YYYY');
            
            $months[] = $monthName;
            
            $subsCount = $plan->subscriptions()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $subscriptions[] = $subsCount;
            
            $rev = $plan->payments()
                ->where('status', 'completed')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
            
            $revenue[] = $rev;
        }

        return response()->json([
            'months' => $months,
            'subscriptions' => $subscriptions,
            'revenue' => $revenue,
        ]);
    }

    /**
     * Exporte les plans en CSV
     */
    public function export()
    {
        $plans = MobileAppPlan::withCount('subscriptions')->get();

        $filename = 'mobile-app-plans-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($plans) {
            $file = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($file, [
                'ID',
                'Nom',
                'Nom FR',
                'Prix Mensuel (CFA)',
                'Prix Annuel (CFA)',
                'Limite Recherches',
                'Limite Analyses IA',
                'Limite Téléchargements PDF',
                'Historique Complet',
                'IA Avancée',
                'Modèle IA',
                'Max Tokens',
                'Actif',
                'Abonnements',
                'Date Création',
            ]);

            // Données
            foreach ($plans as $plan) {
                fputcsv($file, [
                    $plan->id,
                    $plan->name,
                    $plan->name_fr,
                    $plan->price_monthly,
                    $plan->price_yearly,
                    $plan->searches_limit == -1 ? 'Illimité' : $plan->searches_limit,
                    $plan->ai_analyses_limit == -1 ? 'Illimité' : $plan->ai_analyses_limit,
                    $plan->pdf_downloads_limit == -1 ? 'Illimité' : $plan->pdf_downloads_limit,
                    $plan->has_full_history ? 'Oui' : 'Non',
                    $plan->has_advanced_ai ? 'Oui' : 'Non',
                    $plan->ai_model,
                    $plan->max_tokens,
                    $plan->is_active ? 'Oui' : 'Non',
                    $plan->subscriptions_count ?? 0,
                    $plan->created_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
