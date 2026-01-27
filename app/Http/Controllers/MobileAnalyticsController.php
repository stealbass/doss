<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\MobileAppPlan;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPayment;
use App\Models\PushNotification;
use Carbon\Carbon;

class MobileAnalyticsController extends Controller
{
    /**
     * Display mobile analytics dashboard
     */
    public function index()
    {
        // Check if user is super admin
        if (auth()->user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Get basic statistics - real data
        $totalMobileUsers = User::whereHas('mobileSubscriptions')->count();
        $activeSubscriptions = MobileAppSubscription::active()->count();
        
        $stats = [
            'total_users' => $totalMobileUsers,
            'active_users' => $activeSubscriptions,
            'total_downloads' => 0, // Not tracked yet
            'active_sessions' => 0, // Not tracked yet
        ];

        // Get user growth data (last 7 days) - real data
        $userGrowth = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = User::whereHas('mobileSubscriptions')
                ->whereDate('created_at', $date->toDateString())
                ->count();
            
            $userGrowth[] = [
                'date' => $date->format('d M'),
                'count' => $count
            ];
        }

        // Initialize KPIs
        $monthlyRevenue = MobileAppPayment::successful()
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');
        $kpis = [
            'total_users' => [
                'label' => 'Total Users',
                'value' => $stats['total_users'],
                'growth' => 0,
                'color' => 'primary',
                'icon' => 'users'
            ],
            'active_users' => [
                'label' => 'Active Users',
                'value' => $stats['active_users'],
                'growth' => 0,
                'color' => 'success',
                'icon' => 'user-check'
            ],
            'monthly_revenue' => [
                'label' => 'Monthly Revenue',
                'value' => $monthlyRevenue,
                'growth' => 0,
                'color' => 'info',
                'icon' => 'cash'
            ],
            'conversion_rate' => [
                'label' => 'Conversion Rate',
                'value' => 0,
                'growth' => 0,
                'color' => 'warning',
                'icon' => 'percentage'
            ],
            'churn_rate' => [
                'label' => 'Churn Rate',
                'value' => 0,
                'growth' => 0,
                'color' => 'danger',
                'icon' => 'user-minus'
            ],
            'avg_session_duration' => [
                'label' => 'Avg Session Duration',
                'value' => 0,
                'growth' => 0,
                'color' => 'secondary',
                'icon' => 'clock'
            ],
        ];

        // Top users data - REAL DATA
        $topUsers = User::whereHas('mobileSubscriptions')
            ->with(['activeMobileSubscription.plan'])
            ->withCount(['mobilePayments'])
            ->orderBy('mobile_payments_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->mobile_role ?? 'N/A',
                    'activity' => 0, // Not tracked yet
                    'conversations' => 0, // Not tracked yet
                    'documents' => 0, // Not tracked yet
                    'downloads' => 0, // Not tracked yet
                    'subscriptions' => $user->mobile_payments_count
                ];
            });

        // Top plans data - REAL DATA
        $topPlans = MobileAppPlan::with(['subscriptions' => function($q) {
                $q->where('status', 'active');
            }])
            ->withCount(['subscriptions as active_subscribers' => function($q) {
                $q->where('status', 'active');
            }])
            ->get()
            ->map(function($plan) {
                $revenue = MobileAppPayment::where('mobile_app_plan_id', $plan->id)
                    ->where('status', 'successful')
                    ->sum('amount');
                
                return [
                    'name' => $plan->name_fr ?? $plan->name,
                    'price' => $plan->price_yearly,
                    'subscribers' => $plan->active_subscribers,
                    'revenue' => $revenue
                ];
            })
            ->sortByDesc('revenue');

        // Recent activities - REAL DATA from users and payments
        $recentUsers = User::whereHas('mobileSubscriptions')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
        
        $recentPayments = MobileAppPayment::with(['user', 'plan'])
            ->where('status', 'successful')
            ->orderBy('paid_at', 'desc')
            ->limit(3)
            ->get();
        
        $recentActivities = collect();
        foreach($recentUsers as $user) {
            $recentActivities->push([
                'message' => 'Nouvel utilisateur inscrit: ' . $user->name,
                'time' => $user->created_at->diffForHumans(),
                'icon' => 'user-plus',
                'color' => 'success'
            ]);
        }
        foreach($recentPayments as $payment) {
            $recentActivities->push([
                'message' => 'Paiement reçu: ' . number_format($payment->amount) . ' FCFA',
                'time' => $payment->paid_at->diffForHumans(),
                'icon' => 'cash',
                'color' => 'info'
            ]);
        }
        $recentActivities = $recentActivities->sortByDesc('time')->take(5);

        // Charts data - REAL DATA
        // Users evolution (last 6 months)
        $usersEvolution = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = User::whereHas('mobileSubscriptions')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $usersEvolution[] = [
                'month' => $month->format('M'),
                'count' => $count
            ];
        }
        
        // Revenue evolution (last 6 months)
        $revenueEvolution = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $amount = MobileAppPayment::where('status', 'successful')
                ->whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('amount');
            $revenueEvolution[] = [
                'month' => $month->format('M'),
                'amount' => $amount
            ];
        }
        
        // Subscriptions by plan
        $subscriptionsByPlan = MobileAppSubscription::active()
            ->select('mobile_app_plan_id', DB::raw('count(*) as count'))
            ->with('plan:id,name,name_fr')
            ->groupBy('mobile_app_plan_id')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->plan->name_fr ?? $item->plan->name,
                    'count' => $item->count
                ];
            })
            ->values()
            ->toArray();
        
        // 4. Users by role
        $usersByRole = User::whereHas('mobileSubscriptions')
            ->select('mobile_role', DB::raw('count(*) as count'))
            ->whereNotNull('mobile_role')
            ->groupBy('mobile_role')
            ->get()
            ->map(function($item) {
                $colors = [
                    'student' => '#3498db',
                    'lawyer' => '#f39c12',
                    'enterprise' => '#2ecc71',
                ];
                return [
                    'role' => ucfirst($item->mobile_role),
                    'count' => $item->count,
                    'color' => $colors[$item->mobile_role] ?? '#95a5a6'
                ];
            })
            ->values()
            ->toArray();
        
        // Engagement by day (last 7 days) - placeholder since we don't track this yet
        $engagementByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $engagementByDay[] = [
                'date' => $date->format('D'),
                'conversations' => 0, // Not tracked yet
                'documents' => 0, // Not tracked yet
                'downloads' => 0 // Not tracked yet
            ];
        }
        
        $charts = [
            'users_evolution' => $usersEvolution,
            'revenue_evolution' => $revenueEvolution,
            'subscriptions_by_plan' => $subscriptionsByPlan,
            'users_by_role' => $usersByRole,
            'engagement_by_day' => $engagementByDay,
        ];

        return view('mobile-analytics.index', compact('stats', 'userGrowth', 'kpis', 'topUsers', 'topPlans', 'recentActivities', 'charts'));
    }

    /**
     * Get realtime analytics data
     */
    public function realtime(Request $request)
    {
        if (auth()->user()->type !== 'super admin') {
            return response()->json(['error' => 'Permission Denied'], 403);
        }

        $data = [
            'active_now' => 0, // Placeholder
            'requests_per_minute' => 0, // Placeholder
            'last_update' => now()->toIso8601String()
        ];

        return response()->json($data);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        if (auth()->user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Get all mobile users
        $users = User::where('type', 'mobile_user')
            ->select('id', 'name', 'email', 'created_at', 'is_active')
            ->get();

        $filename = 'mobile_analytics_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['ID', 'Name', 'Email', 'Registration Date', 'Status']);
            
            // Data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->created_at->format('Y-m-d H:i:s'),
                    $user->is_active ? 'Active' : 'Inactive'
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}