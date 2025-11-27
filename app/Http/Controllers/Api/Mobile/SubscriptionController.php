<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAppPlan;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPayment;
use App\Models\ReferralReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Get all available plans
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlans()
    {
        $plans = MobileAppPlan::orderBy('price_monthly', 'asc')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price_monthly' => $plan->price_monthly,
                    'price_annual' => $plan->price_annual,
                    'currency' => 'FCFA',
                    'features' => [
                        'searches_limit' => $plan->searches_limit,
                        'ai_analyses_limit' => $plan->ai_analyses_limit,
                        'pdf_downloads_limit' => $plan->pdf_downloads_limit,
                    ],
                    'ai_model' => $plan->ai_model,
                    'rag_type' => $plan->rag_type,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $plans,
        ], 200);
    }

    /**
     * Get current subscription details
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentSubscription(Request $request)
    {
        $user = $request->user();
        
        $subscription = $user->mobileAppSubscription()
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscription_id' => $subscription->id,
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'price_monthly' => $subscription->plan->price_monthly,
                    'price_annual' => $subscription->plan->price_annual,
                ],
                'billing_cycle' => $subscription->billing_cycle,
                'status' => $subscription->status,
                'start_date' => $subscription->start_date->format('Y-m-d'),
                'end_date' => $subscription->end_date?->format('Y-m-d'),
                'auto_renew' => $subscription->auto_renew,
                'is_trial' => $subscription->is_trial,
                'usage' => [
                    'searches' => [
                        'used' => $subscription->searches_used,
                        'limit' => $subscription->plan->searches_limit,
                        'remaining' => max(0, $subscription->plan->searches_limit - $subscription->searches_used),
                    ],
                    'ai_analyses' => [
                        'used' => $subscription->ai_analyses_used,
                        'limit' => $subscription->plan->ai_analyses_limit,
                        'remaining' => max(0, $subscription->plan->ai_analyses_limit - $subscription->ai_analyses_used),
                    ],
                    'pdf_downloads' => [
                        'used' => $subscription->pdf_downloads_used,
                        'limit' => $subscription->plan->pdf_downloads_limit,
                        'remaining' => max(0, $subscription->plan->pdf_downloads_limit - $subscription->pdf_downloads_used),
                    ],
                ],
            ],
        ], 200);
    }

    /**
     * Initiate subscription (creates payment record)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiateSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:mobile_app_plans,id',
            'billing_cycle' => 'required|in:monthly,annual',
            'payment_method' => 'required|in:mobile_money,card',
            'phone_number' => 'required_if:payment_method,mobile_money|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $plan = MobileAppPlan::find($request->plan_id);

        // Calculate amount
        $amount = ($request->billing_cycle === 'monthly') 
            ? $plan->price_monthly 
            : $plan->price_annual;

        if ($amount == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Free plan does not require payment',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create payment record
            $payment = MobileAppPayment::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $amount,
                'currency' => 'XAF', // FCFA
                'payment_method' => $request->payment_method,
                'billing_cycle' => $request->billing_cycle,
                'status' => 'pending',
                'transaction_reference' => 'DOSSY' . time() . rand(1000, 9999),
            ]);

            // TODO: Integrate with Flutterwave for actual payment
            // For now, return payment details for frontend to handle

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'transaction_reference' => $payment->transaction_reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    // Flutterwave payment link would be here
                    'payment_url' => route('mobile.payment.process', $payment->id),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify and activate subscription after payment
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activateSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|integer|exists:mobile_app_payments,id',
            'transaction_id' => 'required|string', // From payment gateway
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        
        $payment = MobileAppPayment::where('id', $request->payment_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Payment already processed',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // TODO: Verify payment with Flutterwave using transaction_id
            // For now, we'll assume payment is successful

            // Update payment status
            $payment->update([
                'status' => 'completed',
                'payment_gateway_reference' => $request->transaction_id,
                'paid_at' => now(),
            ]);

            // Deactivate current subscription
            $currentSubscription = $user->mobileAppSubscription()
                ->where('status', 'active')
                ->first();

            if ($currentSubscription) {
                $currentSubscription->update(['status' => 'cancelled']);
            }

            // Calculate subscription dates
            $startDate = now();
            $endDate = ($payment->billing_cycle === 'monthly')
                ? now()->addMonth()
                : now()->addYear();

            // Create new subscription
            $subscription = MobileAppSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $payment->plan_id,
                'billing_cycle' => $payment->billing_cycle,
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'auto_renew' => true,
                'is_trial' => false,
            ]);

            // Check for referral rewards
            $this->checkReferralRewards($user);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription activated successfully',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'plan_name' => $subscription->plan->name,
                    'status' => $subscription->status,
                    'start_date' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date->format('Y-m-d'),
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Subscription activation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel subscription
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();
        
        $subscription = $user->mobileAppSubscription()
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription to cancel',
            ], 404);
        }

        // Free plan cannot be cancelled
        if ($subscription->plan->price_monthly == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Free plan cannot be cancelled',
            ], 400);
        }

        $subscription->update([
            'status' => 'cancelled',
            'auto_renew' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully. Access will remain until end date.',
            'data' => [
                'end_date' => $subscription->end_date->format('Y-m-d'),
            ],
        ], 200);
    }

    /**
     * Get payment history
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory(Request $request)
    {
        $user = $request->user();

        $payments = MobileAppPayment::where('user_id', $user->id)
            ->with('plan:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'plan_name' => $payment->plan->name,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    'billing_cycle' => $payment->billing_cycle,
                    'status' => $payment->status,
                    'transaction_reference' => $payment->transaction_reference,
                    'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $payments,
        ], 200);
    }

    /**
     * Check and grant referral rewards
     * 
     * @param User $user
     * @return void
     */
    private function checkReferralRewards($user)
    {
        // Count successful referrals
        $referralsCount = \App\Models\Referral::where('referrer_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Grant 1 month free for every 10 referrals
        $rewardsEarned = floor($referralsCount / 10);
        $rewardsGranted = ReferralReward::where('user_id', $user->id)->count();

        if ($rewardsEarned > $rewardsGranted) {
            $newRewards = $rewardsEarned - $rewardsGranted;
            
            for ($i = 0; $i < $newRewards; $i++) {
                ReferralReward::create([
                    'user_id' => $user->id,
                    'reward_type' => 'free_month',
                    'reward_value' => 1,
                    'status' => 'pending',
                ]);
            }
        }
    }
}
