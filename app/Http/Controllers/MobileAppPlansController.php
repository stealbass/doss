<?php

namespace App\Http\Controllers;

use App\Models\MobileAppPlan;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $q->where('status', 'active')
              ->where(function($query) {
                  $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
              });
        }])
        ->withCount('subscriptions as total_subscriptions_count')
        ->orderBy('price_monthly', 'asc')
        ->get();

        // Statistiques globales
        $stats = [
            'total_plans' => MobileAppPlan::count(),
            'active_plans' => MobileAppPlan::where('is_active', true)->count(),
            'total_subscriptions' => MobileAppSubscription::where('status', 'active')
                ->where(function($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
            'monthly_revenue' => MobileAppPayment::where('status', 'successful')
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
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
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:50|unique:mobile_app_plans,name',
                'name_fr' => 'required|string|max:100',
                'price_monthly' => 'required|integer|min:0',
                'price_yearly' => 'required|integer|min:0',
                'searches_limit' => 'required|integer|min:-1',
                'ai_analyses_limit' => 'required|integer|min:-1',
                'pdf_downloads_limit' => 'required|integer|min:-1',
                'has_full_history' => 'nullable',
                'has_advanced_ai' => 'nullable',
                'ai_model' => 'required|string|max:50',
                'max_tokens' => 'required|integer|min:100|max:100000',
                'is_active' => 'nullable',
            ]);

            // Convertir les checkbox
            $validated['has_full_history'] = $request->has('has_full_history');
            $validated['has_advanced_ai'] = $request->has('has_advanced_ai');
            $validated['is_active'] = $request->has('is_active');

            Log::info('MobileAppPlansController::store - creating plan', ['payload' => $validated, 'user' => Auth::id()]);

            $plan = MobileAppPlan::create($validated);

            return redirect()->route('mobile-app-plans.index')
                ->with('success', "Plan '{$plan->name_fr}' créé avec succès !");
        } catch (\Throwable $e) {
            Log::error('MobileAppPlansController::store error', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'request' => $request->all(), 'user' => Auth::id()]);
            return redirect()->back()->withInput()->with('error', __('An unexpected error occurred while creating the plan. See logs.'));
        }
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
            'has_full_history' => 'nullable',
            'has_advanced_ai' => 'nullable',
            'ai_model' => 'required|string|max:50',
            'max_tokens' => 'required|integer|min:100|max:100000',
            'is_active' => 'nullable',
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

        try {
            $subscriptions = $plan->subscriptions();

            $totalSubscriptions = (clone $subscriptions)->count();
            $activeSubscriptions = (clone $subscriptions)
                ->where('status', 'active')
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->count();
            $expiredSubscriptions = (clone $subscriptions)
                ->where(function($q) {
                    $q->where('status', 'expired')
                      ->orWhere(function($query) {
                          $query->whereNotNull('expires_at')
                                ->where('expires_at', '<=', now());
                      });
                })
                ->count();

            $monthlyRevenue = $activeSubscriptions * ($plan->price_monthly ?? 0);

            $totalRevenue = $plan->payments()
                ->whereIn('status', ['successful', 'completed'])
                ->sum('amount');

            $avgDuration = $subscriptions
                ->selectRaw('AVG(DATEDIFF(expires_at, COALESCE(started_at, created_at))) as avg_days')
                ->value('avg_days');

            $newThisMonth = (clone $subscriptions)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();

            return response()->json([
                'total_subscriptions' => $totalSubscriptions,
                'active_subscriptions' => $activeSubscriptions,
                'expired_subscriptions' => $expiredSubscriptions,
                'monthly_revenue' => (float) $monthlyRevenue,
                'total_revenue' => (float) $totalRevenue,
                'avg_subscription_duration' => (float) ($avgDuration ?? 0),
                'new_subscriptions_this_month' => $newThisMonth,
                'churn_rate' => $this->calculateChurnRate($plan),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load statistics',
                'error' => app()->environment('production') ? null : $e->getMessage(),
            ], 500);
        }
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
            ->whereRaw('COALESCE(started_at, created_at) < ?', [$startOfMonth])
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
