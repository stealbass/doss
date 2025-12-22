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
            'exports' => 0,
            'revenue' => 0,
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
