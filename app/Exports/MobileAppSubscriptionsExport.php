<?php

namespace App\Exports;

use App\Models\MobileAppPayment;
use App\Models\MobileAppSubscription;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MobileAppSubscriptionsExport implements FromCollection, WithHeadings
{
    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
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

        if (!empty($this->filters['role'])) {
            $query->whereHas('user', function ($q) {
                $q->where('mobile_role', $this->filters['role']);
            });
        }

        if (!empty($this->filters['plan'])) {
            $query->whereHas('plan', function ($q) {
                $q->where('slug', $this->filters['plan']);
            });
        }

        if (!empty($this->filters['status'])) {
            if ($this->filters['status'] === 'active') {
                $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            } elseif ($this->filters['status'] === 'expired') {
                $query->where(function ($q) {
                    $q->where('status', 'expired')
                        ->orWhere('expires_at', '<=', now());
                });
            } else {
                $query->where('status', $this->filters['status']);
            }
        }

        if (!empty($this->filters['country'])) {
            $query->whereHas('user', function ($q) {
                $q->where('jurisdiction', $this->filters['country']);
            });
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('started_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('started_at', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['search'])) {
            $query->whereHas('user', function ($q) {
                $search = $this->filters['search'];
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $subscriptions = $query
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->get();

        return $subscriptions->map(function ($subscription) {
            $user = $subscription->user;
            $plan = $subscription->plan;

            return [
                'Nom' => $user ? $user->name : 'N/A',
                'Role' => $user ? ($user->mobile_role ?? 'N/A') : 'N/A',
                'Plan' => $plan ? $plan->name : 'N/A',
                'Status' => $subscription->status ?? 'N/A',
                'Expires At' => $subscription->expires_at ? $subscription->expires_at->format('d/m/Y') : 'N/A',
                'Payments' => $subscription->total_payments ?? 0,
                'Last Transaction Amount' => $subscription->last_payment_amount ?? 0,
                'Last Transaction Date' => $subscription->last_payment_date ? Carbon::parse($subscription->last_payment_date)->format('d/m/Y H:i') : 'N/A',
                'Registered' => $user && $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Role',
            'Plan',
            'Status',
            'Expires At',
            'Payments',
            'Last Transaction Amount',
            'Last Transaction Date',
            'Registered',
        ];
    }
}
