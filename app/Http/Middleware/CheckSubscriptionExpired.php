<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckSubscriptionExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Skip check for superadmin
            if ($user->type === 'super admin') {
                return $next($request);
            }
            
            // Check for company accounts (those who pay subscriptions)
            if ($user->type === 'company') {
                // If user has a plan expiration date
                if ($user->plan_expire_date) {
                    $expirationDate = Carbon::parse($user->plan_expire_date);
                    $now = Carbon::now();
                    
                    // If plan is expired
                    if ($now->greaterThan($expirationDate)) {
                        // Get current route name
                        $currentRoute = $request->route()->getName();
                        
                        // Routes allowed for expired users (plans, payment, profile, logout)
                        $allowedPrefixes = [
                            'plan.', 'plans.', 'stripe', 'paypal', 'mercado', 'mollie', 
                            'skrill', 'coingate', 'paystack', 'flaterwave', 'razorpay', 
                            'paytm', 'toyyibpay', 'sspay', 'bank.transfer', 'error.plan',
                            'profile', 'logout'
                        ];
                        
                        // Check if current route starts with any allowed prefix
                        $isAllowedRoute = false;
                        foreach ($allowedPrefixes as $prefix) {
                            if (str_starts_with($currentRoute, $prefix)) {
                                $isAllowedRoute = true;
                                break;
                            }
                        }
                        
                        // If not an allowed route, flash session for modal display
                        if (!$isAllowedRoute) {
                            session()->flash('subscription_expired', true);
                            session()->flash('expiration_date', $user->plan_expire_date);
                        }
                    }
                }
            }
        }
        
        return $next($request);
    }
}
