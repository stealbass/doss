<?php

namespace App\Http\Controllers;

use App\Exports\MobileAppSubscriptionsExport;
use App\Models\MobileAppPayment;
use App\Models\MobileAppPlan;
use App\Models\MobileAppSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MobileAppSubscriptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = $this->filtersFromRequest($request);
        $baseQuery = $this->buildQuery($filters);

        $subscriptions = (clone $baseQuery)
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->appends($request->query());

        $totalPayments = MobileAppPayment::successful()
            ->where(function ($query) use ($baseQuery) {
                $query->whereIn('mobile_app_subscription_id', (clone $baseQuery)->select('mobile_app_subscriptions.id'))
                    ->orWhere(function ($subQuery) use ($baseQuery) {
                        $subQuery->whereNull('mobile_app_subscription_id')
                            ->whereIn('user_id', (clone $baseQuery)->select('mobile_app_subscriptions.user_id'));
                    });
            })
            ->sum('amount');

        $plans = MobileAppPlan::where('is_active', true)->get();
        $roles = ['student', 'lawyer', 'enterprise'];
        $countries = config('mobile_countries.supported_countries', []);

        return view('mobile-app-subscriptions.index', compact(
            'subscriptions',
            'plans',
            'roles',
            'countries',
            'totalPayments',
            'filters'
        ));
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->filtersFromRequest($request);
        $query = $this->buildQuery($filters)
            ->orderByDesc('started_at')
            ->orderByDesc('created_at');

        $subscriptions = $query->get();

        $filename = 'mobile-app-subscriptions-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($subscriptions) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Nom',
                'Role',
                'Plan',
                'Status',
                'Expires At',
                'Payments',
                'Last Transaction Amount',
                'Last Transaction Date',
                'Registered',
            ]);

            foreach ($subscriptions as $subscription) {
                $user = $subscription->user;
                $plan = $subscription->plan;

                fputcsv($file, [
                    $user ? $user->name : 'N/A',
                    $user ? ($user->mobile_role ?? 'N/A') : 'N/A',
                    $plan ? $plan->name : 'N/A',
                    $subscription->status ?? 'N/A',
                    $subscription->expires_at ? $subscription->expires_at->format('d/m/Y') : 'N/A',
                    $subscription->total_payments ?? 0,
                    $subscription->last_payment_amount ?? 0,
                    $subscription->last_payment_date ? Carbon::parse($subscription->last_payment_date)->format('d/m/Y H:i') : 'N/A',
                    $user && $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->filtersFromRequest($request);
        $filename = 'mobile-app-subscriptions-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new MobileAppSubscriptionsExport($filters), $filename);
    }

    private function filtersFromRequest(Request $request)
    {
        return [
            'role' => $request->get('role'),
            'plan' => $request->get('plan'),
            'status' => $request->get('status'),
            'country' => $request->get('country'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];
    }

    private function buildQuery(array $filters)
    {
        $query = MobileAppSubscription::query()
            ->with(['user', 'plan'])
            ->select('mobile_app_subscriptions.*')
            ->addSelect([
                'total_payments' => MobileAppPayment::selectRaw('COALESCE(SUM(amount), 0)')
                    ->where('status', 'successful')
                    ->where(function ($subQuery) {
                        $subQuery->whereColumn('mobile_app_subscription_id', 'mobile_app_subscriptions.id')
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('mobile_app_subscription_id')
                                    ->whereColumn('user_id', 'mobile_app_subscriptions.user_id');
                            });
                    }),
                'last_payment_amount' => MobileAppPayment::select('amount')
                    ->where('status', 'successful')
                    ->where(function ($subQuery) {
                        $subQuery->whereColumn('mobile_app_subscription_id', 'mobile_app_subscriptions.id')
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('mobile_app_subscription_id')
                                    ->whereColumn('user_id', 'mobile_app_subscriptions.user_id');
                            });
                    })
                    ->orderByDesc('paid_at')
                    ->limit(1),
                'last_payment_date' => MobileAppPayment::select('paid_at')
                    ->where('status', 'successful')
                    ->where(function ($subQuery) {
                        $subQuery->whereColumn('mobile_app_subscription_id', 'mobile_app_subscriptions.id')
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('mobile_app_subscription_id')
                                    ->whereColumn('user_id', 'mobile_app_subscriptions.user_id');
                            });
                    })
                    ->orderByDesc('paid_at')
                    ->limit(1),
            ]);

        if (!empty($filters['role'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('mobile_role', $filters['role']);
            });
        }

        if (!empty($filters['plan'])) {
            $query->whereHas('plan', function ($q) use ($filters) {
                $q->where('slug', $filters['plan']);
            });
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            } elseif ($filters['status'] === 'expired') {
                $query->where(function ($q) {
                    $q->where('status', 'expired')
                        ->orWhere('expires_at', '<=', now());
                });
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['country'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('jurisdiction', $filters['country']);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('started_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('started_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }
}
