<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
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

        // Get basic statistics
        $stats = [
            'total_users' => User::where('type', 'mobile_user')->count(),
            'active_users' => User::where('type', 'mobile_user')
                ->where('is_active', 1)
                ->count(),
            'total_downloads' => 0, // Placeholder
            'active_sessions' => 0, // Placeholder
        ];

        // Get user growth data (last 7 days)
        $userGrowth = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = User::where('type', 'mobile_user')
                ->whereDate('created_at', $date->toDateString())
                ->count();
            
            $userGrowth[] = [
                'date' => $date->format('d M'),
                'count' => $count
            ];
        }

        // Initialize KPIs
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
                'value' => 0,
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

        return view('mobile-analytics.index', compact('stats', 'userGrowth', 'kpis'));
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
