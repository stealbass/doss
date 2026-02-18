<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPlan;
use App\Models\MobileAppPayment;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class MobileUsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la liste des utilisateurs mobiles
     */
    public function index(Request $request)
    {
        // Filtres
        $role = $request->get('role');
        $plan = $request->get('plan');
        $status = $request->get('status');
        $search = $request->get('search');

        // Query de base - Utilisateurs mobile (abonnement OU activite mobile)
        $query = User::with(['activeMobileSubscription.plan', 'mobilePayments'])
            ->withSum(['mobilePayments as total_payments' => function($query) {
                $query->where('status', 'successful');
            }], 'amount')
            ->where(function($q) {
                $q->whereHas('mobileSubscriptions')
                  ->orWhereNotNull('last_mobile_activity_at')
                  ->orWhereNotNull('mobile_app_installed_at');
            });

        // Filtre par rôle mobile (mobile_role)
        if ($role) {
            $query->where('mobile_role', $role);
        }

        // Filtre par plan mobile actif
        if ($plan) {
            $query->whereHas('activeMobileSubscription.plan', function($q) use ($plan) {
                $q->where('slug', $plan);
            });
        }

        // Filtre par statut d'abonnement
        if ($status === 'active') {
            $query->whereHas('activeMobileSubscription', function($q) {
                $q->where('status', 'active')->where('expires_at', '>', now());
            });
        } elseif ($status === 'expired') {
            $query->whereHas('mobileSubscriptions', function($q) {
                $q->where('status', 'expired')
                    ->orWhere('expires_at', '<', now());
            });
        } elseif ($status === 'cancelled') {
            $query->whereHas('mobileSubscriptions', function($q) {
                $q->where('status', 'cancelled');
            });
        }

        // Recherche
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");

                if (Schema::hasColumn('users', 'phone')) {
                    $q->orWhere('phone', 'LIKE', "%{$search}%");
                }
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(25);

        // Statistiques globales
        $mobileUsersQuery = User::query()->where(function($q) {
            $q->whereHas('mobileSubscriptions')
              ->orWhereNotNull('last_mobile_activity_at')
              ->orWhereNotNull('mobile_app_installed_at');
        });
        $stats = [
            'total_users' => (clone $mobileUsersQuery)->count(),
            'active_subscriptions' => MobileAppSubscription::where('status', 'active')
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->count(),
            'total_revenue' => MobileAppPayment::where('status', 'successful')->sum('amount'),
            'new_users_this_month' => (clone $mobileUsersQuery)
                ->whereYear('last_mobile_activity_at', now()->year)
                ->whereMonth('last_mobile_activity_at', now()->month)
                ->count(),
        ];

        // Plans disponibles
        $plans = MobileAppPlan::where('is_active', true)->get();

        // Rôles mobiles
        $roles = ['student', 'lawyer', 'enterprise'];

        return view('mobile-users.index', compact('users', 'stats', 'plans', 'roles'));
    }

    /**
     * Affiche les détails d'un utilisateur mobile
     */
    public function show($id)
    {
        $user = User::with([
            'mobileSubscriptions.plan',
            'mobilePayments',
            'conversations',
            'submittedDocuments',
            'documentDownloads',
            'referralsMade',
            'referralsReceived'
        ])->findOrFail($id);

        // Statistiques utilisateur
        $userStats = [
            'total_conversations' => $user->conversations()->count(),
            'total_documents' => $user->submittedDocuments()->count(),
            'total_downloads' => $user->documentDownloads()->count(),
            'total_payments' => $user->mobilePayments()->where('status', 'successful')->sum('amount'),
            'referrals_made' => $user->referralsMade()->count(),
            'referrals_received' => $user->referralsReceived()->count(),
        ];

        return view('mobile-users.show', compact('user', 'userStats'));
    }

    /**
     * Suspendre un utilisateur mobile
     */
    public function suspend($id)
    {
        $user = User::findOrFail($id);
        
        // Suspendre l'abonnement actif
        $subscription = $user->activeMobileSubscription;
        if ($subscription) {
            $subscription->status = 'suspended';
            $subscription->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur suspendu avec succès'
        ]);
    }

    /**
     * Réactiver un utilisateur mobile
     */
    public function reactivate($id)
    {
        $user = User::findOrFail($id);
        
        // Réactiver l'abonnement
        $subscription = $user->mobileSubscriptions()
            ->where('status', 'suspended')
            ->latest()
            ->first();

        if ($subscription) {
            $subscription->status = 'active';
            $subscription->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur réactivé avec succès'
        ]);
    }

    /**
     * Changer le plan d'un utilisateur
     */
    public function changePlan(Request $request, $id)
    {
        $request->validate([
            'plan_id' => 'required|exists:mobile_app_plans,id',
            'duration' => 'required|in:1,3,6,12',
        ]);

        $user = User::findOrFail($id);
        $plan = MobileAppPlan::findOrFail($request->plan_id);

        DB::beginTransaction();
        try {
            // Annuler l'ancien abonnement
            $oldSubscription = $user->activeMobileSubscription;
            if ($oldSubscription) {
                $oldSubscription->status = 'cancelled';
                $oldSubscription->save();
            }

            // Créer nouveau abonnement
            $expiresAt = now()->addMonths((int)$request->duration);
            
            $subscription = MobileAppSubscription::create([
                'user_id' => $user->id,
                'mobile_app_plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => $expiresAt,
                'auto_renew' => false,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Plan modifié avec succès',
                'subscription' => $subscription->load('plan')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prolonger l'abonnement d'un utilisateur
     */
    public function extendSubscription(Request $request, $id)
    {
        $request->validate([
            'months' => 'required|integer|min:1|max:24',
        ]);

        $user = User::findOrFail($id);
        $subscription = $user->activeMobileSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement actif trouvé'
            ], 404);
        }

        // Prolonger la date d'expiration
        $subscription->expires_at = $subscription->expires_at->addMonths($request->months);
        $subscription->save();

        return response()->json([
            'success' => true,
            'message' => "Abonnement prolongé de {$request->months} mois",
            'new_expiry' => $subscription->expires_at->format('d/m/Y')
        ]);
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }

    /**
     * Exporter les utilisateurs en CSV
     */
    public function export(Request $request)
    {
        $users = User::with(['activeMobileSubscription.plan'])
            ->where(function($q) {
                $q->whereHas('mobileSubscriptions')
                  ->orWhereNotNull('last_mobile_activity_at')
                  ->orWhereNotNull('mobile_app_installed_at');
            })
            ->get();

        $filename = 'mobile-users-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($file, [
                'ID',
                'Nom',
                'Email',
                'Téléphone',
                'Rôle',
                'Plan Actuel',
                'Statut Abonnement',
                'Date Expiration',
                'Date Inscription',
            ]);

            // Données
            foreach ($users as $user) {
                $subscription = $user->activeMobileSubscription;
                
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? 'N/A',
                    $user->mobile_role ?? 'N/A',
                    $subscription ? $subscription->plan->name : 'Aucun',
                    $subscription ? $subscription->status : 'Inactif',
                    $subscription ? $subscription->expires_at->format('d/m/Y') : 'N/A',
                    $user->created_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Obtenir les statistiques globales (AJAX)
     */
    public function statistics()
    {
        $mobileUsersQuery = User::query()->where(function($q) {
            $q->whereHas('mobileSubscriptions')
              ->orWhereNotNull('last_mobile_activity_at')
              ->orWhereNotNull('mobile_app_installed_at');
        });
        $stats = [
            'total_users' => (clone $mobileUsersQuery)->count(),
            'active_users' => User::whereHas('activeMobileSubscription')->count(),
            'total_revenue' => MobileAppPayment::where('status', 'successful')->sum('amount'),
            'revenue_this_month' => MobileAppPayment::where('status', 'successful')
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
            'new_users_today' => (clone $mobileUsersQuery)
                ->whereDate('last_mobile_activity_at', now()->toDateString())
                ->count(),
            'users_by_plan' => MobileAppSubscription::where('status', 'active')
                ->with('plan')
                ->get()
                ->groupBy('plan.name')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'users_by_role' => (clone $mobileUsersQuery)
                ->select('mobile_role', DB::raw('count(*) as count'))
                ->groupBy('mobile_role')
                ->pluck('count', 'mobile_role')
                ->toArray(),
        ];

        return response()->json($stats);
    }
}
