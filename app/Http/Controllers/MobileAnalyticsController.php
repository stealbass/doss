<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPlan;
use App\Models\MobileAppPayment;
use App\Models\Conversation;
use App\Models\SubmittedDocument;
use App\Models\DocumentDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MobileAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche le tableau de bord analytique principal
     */
    public function index()
    {
        // KPIs généraux
        $kpis = $this->getKPIs();
        
        // Graphiques
        $charts = $this->getChartsData();
        
        // Top performers
        $topUsers = $this->getTopUsers();
        $topPlans = $this->getTopPlans();
        
        // Données récentes
        $recentActivities = $this->getRecentActivities();
        
        return view('mobile-analytics.index', compact(
            'kpis',
            'charts',
            'topUsers',
            'topPlans',
            'recentActivities'
        ));
    }

    /**
     * Calcule les KPIs principaux
     */
    private function getKPIs()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Utilisateurs mobiles
        $totalUsers = User::whereHas('mobileSubscriptions')->count();
        $usersYesterday = User::whereHas('mobileSubscriptions')
            ->whereDate('created_at', '<', $today)
            ->count();
        $usersGrowth = $usersYesterday > 0 
            ? round((($totalUsers - $usersYesterday) / $usersYesterday) * 100, 2)
            : 0;

        // Abonnements actifs
        $activeSubscriptions = MobileAppSubscription::where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();
        $activeYesterday = MobileAppSubscription::where('status', 'active')
            ->where('expires_at', '>', $yesterday)
            ->whereDate('created_at', '<', $today)
            ->count();
        $subscriptionsGrowth = $activeYesterday > 0
            ? round((($activeSubscriptions - $activeYesterday) / $activeYesterday) * 100, 2)
            : 0;

        // Revenus
        $monthlyRevenue = MobileAppPayment::where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, now()])
            ->sum('amount');
        $lastMonthRevenue = MobileAppPayment::where('status', 'completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');
        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2)
            : 0;

        // Taux de conversion
        $totalTrials = User::whereHas('mobileSubscriptions', function($q) use ($startOfMonth) {
            $q->where('created_at', '>=', $startOfMonth);
        })->count();
        $paidConversions = MobileAppPayment::where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, now()])
            ->distinct('user_id')
            ->count('user_id');
        $conversionRate = $totalTrials > 0
            ? round(($paidConversions / $totalTrials) * 100, 2)
            : 0;

        // Engagement
        $totalConversations = Conversation::whereHas('user.mobileSubscriptions')
            ->whereBetween('created_at', [$startOfMonth, now()])
            ->count();
        $activeUsers = User::whereHas('conversations', function($q) use ($startOfMonth) {
            $q->whereBetween('created_at', [$startOfMonth, now()]);
        })->count();
        $avgConversationsPerUser = $activeUsers > 0
            ? round($totalConversations / $activeUsers, 2)
            : 0;

        // Churn Rate
        $churnRate = $this->calculateChurnRate();

        return [
            'total_users' => [
                'value' => $totalUsers,
                'growth' => $usersGrowth,
                'label' => 'Total Users',
                'icon' => 'users',
                'color' => 'primary'
            ],
            'active_subscriptions' => [
                'value' => $activeSubscriptions,
                'growth' => $subscriptionsGrowth,
                'label' => 'Active Subscriptions',
                'icon' => 'credit-card',
                'color' => 'success'
            ],
            'monthly_revenue' => [
                'value' => $monthlyRevenue,
                'growth' => $revenueGrowth,
                'label' => 'Monthly Revenue (CFA)',
                'icon' => 'currency-dollar',
                'color' => 'warning'
            ],
            'conversion_rate' => [
                'value' => $conversionRate,
                'growth' => 0,
                'label' => 'Conversion Rate (%)',
                'icon' => 'chart-line',
                'color' => 'info'
            ],
            'avg_conversations' => [
                'value' => $avgConversationsPerUser,
                'growth' => 0,
                'label' => 'Avg Conversations/User',
                'icon' => 'messages',
                'color' => 'secondary'
            ],
            'churn_rate' => [
                'value' => $churnRate,
                'growth' => 0,
                'label' => 'Churn Rate (%)',
                'icon' => 'trending-down',
                'color' => 'danger'
            ],
        ];
    }

    /**
     * Obtient les données pour les graphiques
     */
    private function getChartsData()
    {
        return [
            'users_evolution' => $this->getUsersEvolution(),
            'revenue_evolution' => $this->getRevenueEvolution(),
            'subscriptions_by_plan' => $this->getSubscriptionsByPlan(),
            'users_by_role' => $this->getUsersByRole(),
            'engagement_by_day' => $this->getEngagementByDay(),
        ];
    }

    /**
     * Évolution des utilisateurs sur 12 mois
     */
    private function getUsersEvolution()
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $count = User::whereHas('mobileSubscriptions')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $data[] = [
                'month' => $date->locale('fr')->isoFormat('MMM YYYY'),
                'count' => $count
            ];
        }
        return $data;
    }

    /**
     * Évolution des revenus sur 12 mois
     */
    private function getRevenueEvolution()
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $amount = MobileAppPayment::where('status', 'completed')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
            
            $data[] = [
                'month' => $date->locale('fr')->isoFormat('MMM YYYY'),
                'amount' => $amount
            ];
        }
        return $data;
    }

    /**
     * Répartition des abonnements par plan
     */
    private function getSubscriptionsByPlan()
    {
        return MobileAppPlan::withCount(['subscriptions as active_count' => function($q) {
            $q->where('status', 'active')->where('expires_at', '>', now());
        }])
        ->get()
        ->map(function($plan) {
            return [
                'name' => $plan->name_fr,
                'count' => $plan->active_count ?? 0,
                'color' => $this->getPlanColor($plan->name)
            ];
        })
        ->toArray();
    }

    /**
     * Répartition des utilisateurs par rôle
     */
    private function getUsersByRole()
    {
        $roles = ['student', 'lawyer', 'enterprise'];
        $data = [];
        
        foreach ($roles as $role) {
            $count = User::whereHas('mobileSubscriptions')
                ->where('mobile_role', $role)
                ->count();
            
            $data[] = [
                'role' => ucfirst($role),
                'count' => $count,
                'color' => $this->getRoleColor($role)
            ];
        }
        
        return $data;
    }

    /**
     * Engagement par jour (7 derniers jours)
     */
    private function getEngagementByDay()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            $conversations = Conversation::whereDate('created_at', $date->toDateString())
                ->count();
            $documents = SubmittedDocument::whereDate('created_at', $date->toDateString())
                ->count();
            $downloads = DocumentDownload::whereDate('created_at', $date->toDateString())
                ->count();
            
            $data[] = [
                'date' => $date->locale('fr')->isoFormat('ddd DD/MM'),
                'conversations' => $conversations,
                'documents' => $documents,
                'downloads' => $downloads
            ];
        }
        
        return $data;
    }

    /**
     * Top 10 utilisateurs les plus actifs
     */
    private function getTopUsers()
    {
        return User::whereHas('mobileSubscriptions')
            ->withCount(['conversations', 'submittedDocuments', 'documentDownloads'])
            ->orderByDesc('conversations_count')
            ->take(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->mobile_role,
                    'conversations' => $user->conversations_count ?? 0,
                    'documents' => $user->submitted_documents_count ?? 0,
                    'downloads' => $user->document_downloads_count ?? 0,
                    'total_activity' => ($user->conversations_count ?? 0) + 
                                       ($user->submitted_documents_count ?? 0) + 
                                       ($user->document_downloads_count ?? 0)
                ];
            });
    }

    /**
     * Top 5 plans par revenus
     */
    private function getTopPlans()
    {
        return MobileAppPlan::withCount(['subscriptions as active_count' => function($q) {
            $q->where('status', 'active')->where('expires_at', '>', now());
        }])
        ->get()
        ->map(function($plan) {
            $revenue = $plan->active_count * $plan->price_monthly;
            return [
                'id' => $plan->id,
                'name' => $plan->name_fr,
                'price' => $plan->price_monthly,
                'subscribers' => $plan->active_count ?? 0,
                'revenue' => $revenue
            ];
        })
        ->sortByDesc('revenue')
        ->take(5)
        ->values();
    }

    /**
     * Activités récentes
     */
    private function getRecentActivities()
    {
        $activities = [];

        // Nouveaux utilisateurs
        $newUsers = User::whereHas('mobileSubscriptions')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();
        
        foreach ($newUsers as $user) {
            $activities[] = [
                'type' => 'user',
                'icon' => 'user-plus',
                'color' => 'success',
                'message' => "Nouvel utilisateur: {$user->name}",
                'time' => $user->created_at->diffForHumans(),
                'timestamp' => $user->created_at->timestamp
            ];
        }

        // Nouveaux paiements
        $newPayments = MobileAppPayment::where('status', 'completed')
            ->with('user')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();
        
        foreach ($newPayments as $payment) {
            $activities[] = [
                'type' => 'payment',
                'icon' => 'currency-dollar',
                'color' => 'warning',
                'message' => "Paiement de " . number_format($payment->amount, 0) . " CFA par {$payment->user->name}",
                'time' => $payment->created_at->diffForHumans(),
                'timestamp' => $payment->created_at->timestamp
            ];
        }

        // Trier par timestamp décroissant
        usort($activities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Calcule le taux de churn mensuel
     */
    private function calculateChurnRate()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $activeAtStart = MobileAppSubscription::where('status', 'active')
            ->where('starts_at', '<', $startOfMonth)
            ->where('expires_at', '>', $startOfMonth)
            ->count();

        $churned = MobileAppSubscription::where('status', 'cancelled')
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        if ($activeAtStart === 0) {
            return 0;
        }

        return round(($churned / $activeAtStart) * 100, 2);
    }

    /**
     * API: Statistiques en temps réel
     */
    public function realtime()
    {
        return response()->json([
            'active_users_now' => $this->getActiveUsersNow(),
            'conversations_today' => Conversation::whereDate('created_at', today())->count(),
            'payments_today' => MobileAppPayment::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount'),
            'new_subscriptions_today' => MobileAppSubscription::whereDate('created_at', today())->count(),
        ]);
    }

    /**
     * Utilisateurs actifs maintenant (dernières 5 minutes)
     */
    private function getActiveUsersNow()
    {
        return Conversation::where('updated_at', '>', Carbon::now()->subMinutes(5))
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Export des analytics en CSV
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'overview');
        
        $filename = "mobile-analytics-{$type}-" . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($type) {
            $file = fopen('php://output', 'w');
            
            if ($type === 'overview') {
                $this->exportOverview($file);
            } elseif ($type === 'users') {
                $this->exportUsers($file);
            } elseif ($type === 'revenue') {
                $this->exportRevenue($file);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportOverview($file)
    {
        fputcsv($file, ['Métrique', 'Valeur', 'Croissance (%)']);
        
        $kpis = $this->getKPIs();
        foreach ($kpis as $key => $kpi) {
            fputcsv($file, [
                $kpi['label'],
                $kpi['value'],
                $kpi['growth']
            ]);
        }
    }

    private function exportUsers($file)
    {
        fputcsv($file, ['Nom', 'Email', 'Rôle', 'Conversations', 'Documents', 'Téléchargements', 'Total Activité']);
        
        $users = $this->getTopUsers();
        foreach ($users as $user) {
            fputcsv($file, [
                $user['name'],
                $user['email'],
                $user['role'],
                $user['conversations'],
                $user['documents'],
                $user['downloads'],
                $user['total_activity']
            ]);
        }
    }

    private function exportRevenue($file)
    {
        fputcsv($file, ['Mois', 'Revenus (CFA)']);
        
        $revenue = $this->getRevenueEvolution();
        foreach ($revenue as $item) {
            fputcsv($file, [
                $item['month'],
                $item['amount']
            ]);
        }
    }

    /**
     * Couleur pour un plan
     */
    private function getPlanColor($planName)
    {
        $colors = [
            'free' => '#6c757d',
            'student' => '#0dcaf0',
            'pro' => '#0d6efd',
            'cabinet' => '#6f42c1',
        ];
        
        return $colors[$planName] ?? '#6c757d';
    }

    /**
     * Couleur pour un rôle
     */
    private function getRoleColor($role)
    {
        $colors = [
            'student' => '#0dcaf0',
            'lawyer' => '#ffc107',
            'enterprise' => '#0d6efd',
        ];
        
        return $colors[$role] ?? '#6c757d';
    }
}
