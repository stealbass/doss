<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MobileSubscriptionPlan;
use Carbon\Carbon;

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
            'total_users' => User::where('type', 'mobile_user')->count(),
            'active_users' => User::where('type', 'mobile_user')
                ->where('is_active', 1)
                ->count(),
            'total_subscriptions' => 0, // Placeholder
            'revenue_this_month' => 0, // Placeholder
        ];

        // Get recent mobile users
        $recentUsers = User::where('type', 'mobile_user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get subscription plans
        $plans = MobileSubscriptionPlan::where('is_active', 1)->get();

        // User registration trends (last 30 days)
        $registrationTrends = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = User::where('type', 'mobile_user')
                ->whereDate('created_at', $date->toDateString())
                ->count();
            
            $registrationTrends[] = [
                'date' => $date->format('d M'),
                'count' => $count
            ];
        }

        return view('mobile-dashboard', compact('stats', 'recentUsers', 'plans', 'registrationTrends'));
    }
}
