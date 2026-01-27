<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MobileAppPlan;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPayment;
use App\Models\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MobileDashboardController extends Controller
{
    /**
     * Display mobile app dashboard
     */
    public function index()
    {
        // Check if user is super admin
        if (auth()->user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Get statistics
        $stats = [
            'total_users' => User::whereHas('mobileSubscriptions')->count(),
            'active_users' => User::whereHas('activeMobileSubscription', function($q) {
                $q->where('status', 'active');
            })->count(),
            // Using active subscriptions count for dashboard display
            'total_subscriptions' => MobileAppSubscription::active()->count(),
            // Sum of successful payments in the current month
            'revenue_this_month' => MobileAppPayment::successful()
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
        ];

        // Get real recent mobile users with subscriptions
        $recentUsers = User::whereHas('mobileSubscriptions')
            ->with(['activeMobileSubscription.plan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get subscription plans
        $plans = MobileAppPlan::active()->get();

        // User registration trends (last 30 days) - real data
        $registrationTrends = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = User::whereHas('mobileSubscriptions')
                ->whereDate('created_at', $date->toDateString())
                ->count();
            
            $registrationTrends[] = [
                'date' => $date->format('d M'),
                'count' => $count
            ];
        }

        // Get real recent push notifications
        $recentNotifications = PushNotification::with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get real plan distribution
        $planDistribution = MobileAppSubscription::active()
            ->select('mobile_app_plan_id', DB::raw('count(*) as count'))
            ->with('plan:id,name,name_fr')
            ->groupBy('mobile_app_plan_id')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->plan->name_fr ?? $item->plan->name,
                    'count' => $item->count
                ];
            });

        return view('mobile-dashboard', compact('stats', 'recentUsers', 'plans', 'registrationTrends', 'recentNotifications', 'planDistribution'));
    }
}
