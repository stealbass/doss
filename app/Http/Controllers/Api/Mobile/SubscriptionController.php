<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAppPlan;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPayment;
use App\Models\ReferralReward;
use App\Models\UserCoupon;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubscriptionController extends Controller
{
    /**
     * Get all available plans - Format compatible with Flutter app
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlans()
    {
        $query = MobileAppPlan::query();

        // Only active plans if the column exists
        if (Schema::hasColumn('mobile_app_plans', 'is_active')) {
            $query->where('is_active', true);
        }

        // Prefer admin-defined sort_order if present, then price
        if (Schema::hasColumn('mobile_app_plans', 'sort_order')) {
            $query->orderBy('sort_order', 'asc')->orderBy('price_monthly', 'asc');
        } else {
            $query->orderBy('price_monthly', 'asc');
        }

        $plans = $query->get()
            ->map(function ($plan) {
                // Build human-readable features (parity with authenticated endpoint)
                $features = [];
                if ($plan->searches_limit == -1) {
                    $features[] = 'Recherches illimitées';
                } else {
                    $features[] = $plan->searches_limit . ' recherches par mois';
                }

                if ($plan->ai_analyses_limit == -1) {
                    $features[] = 'Analyses IA illimitées';
                } else {
                    $features[] = $plan->ai_analyses_limit . ' analyses IA par mois';
                }

                if ($plan->pdf_downloads_limit == -1) {
                    $features[] = 'Téléchargements PDF illimités';
                } elseif ($plan->pdf_downloads_limit > 0) {
                    $features[] = $plan->pdf_downloads_limit . ' téléchargements PDF par mois';
                } else {
                    $features[] = 'Pas de téléchargement PDF';
                }

                if ($plan->has_full_history) {
                    $features[] = 'Historique complet des conversations';
                }

                if ($plan->has_advanced_ai) {
                    $features[] = 'IA avancée (' . ($plan->ai_model ?? 'GPT-4') . ')';
                } else {
                    $features[] = 'IA standard (' . ($plan->ai_model ?? 'GPT-3.5') . ')';
                }

                return [
                    'id' => (string)$plan->id,
                    'name' => $plan->name,
                    'name_fr' => $plan->name_fr ?? $plan->name,
                    // Keep backward-compatible 'price' (monthly)
                    'price' => (float)$plan->price_monthly,
                    // Also expose explicit monthly/yearly fields
                    'price_monthly' => (float)$plan->price_monthly,
                    'price_yearly' => (float)($plan->price_yearly ?? $plan->price_monthly * 12),
                    'currency' => 'XAF',
                    'duration' => 'monthly',
                    'features' => $features,
                    'limits' => [
                        'searches' => (int)$plan->searches_limit,
                        'analyses' => (int)$plan->ai_analyses_limit,
                        'downloads' => (int)$plan->pdf_downloads_limit,
                    ],
                    'ai_model' => $plan->ai_model ?? 'gpt-3.5-turbo',
                    'max_tokens' => (int)($plan->max_tokens ?? 1000),
                    'has_full_history' => (bool)$plan->has_full_history,
                    'has_advanced_ai' => (bool)$plan->has_advanced_ai,
                ];
            });

        return response()->json([
            'success' => true,
            'plans' => $plans,
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
            'subscription' => [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan->id,
                'plan_name' => $subscription->plan->name,
                'billing_cycle' => $subscription->billing_cycle,
                'status' => $subscription->status,
                'start_date' => optional($subscription->started_at)->format('Y-m-d'),
                'end_date' => optional($subscription->expires_at)->format('Y-m-d'),
                'auto_renew' => $subscription->auto_renew,
                'is_trial' => $subscription->is_trial,
                'searches_used' => $subscription->searches_used,
                'ai_analyses_used' => $subscription->ai_analyses_used,
                'pdf_downloads_used' => $subscription->pdf_downloads_used,
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'price_monthly' => $subscription->plan->price_monthly,
                    'price_annual' => $subscription->plan->price_yearly,
                    'searches_limit' => $subscription->plan->searches_limit,
                    'ai_analyses_limit' => $subscription->plan->ai_analyses_limit,
                    'pdf_downloads_limit' => $subscription->plan->pdf_downloads_limit,
                ],
            ],
            'data' => [
                'subscription_id' => $subscription->id,
                'plan' => [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'price_monthly' => $subscription->plan->price_monthly,
                    'price_annual' => $subscription->plan->price_yearly,
                ],
                'billing_cycle' => $subscription->billing_cycle,
                'status' => $subscription->status,
                'start_date' => optional($subscription->started_at)->format('Y-m-d'),
                'end_date' => optional($subscription->expires_at)->format('Y-m-d'),
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
     * Get user profile with statistics - NEW ENDPOINT
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserProfile(Request $request)
    {
        $user = $request->user();
        
        $subscription = $user->mobileAppSubscription()
            ->where('status', 'active')
            ->with('plan')
            ->first();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role ?? 'student',
                'plan' => $subscription?->plan->name ?? 'Gratuit',
                'jurisdiction' => $user->jurisdiction,
                'subscription_end' => $subscription?->expires_at?->toIso8601String(),
                'searches_used' => $subscription?->searches_used ?? 0,
                'searches_limit' => $subscription?->plan->searches_limit ?? 5,
                'analyses_used' => $subscription?->ai_analyses_used ?? 0,
                'analyses_limit' => $subscription?->plan->ai_analyses_limit ?? 2,
                'downloads_used' => $subscription?->pdf_downloads_used ?? 0,
                'downloads_limit' => $subscription?->plan->pdf_downloads_limit ?? 0,
                'referral_count' => $user->successful_referrals_count ?? 0,
                'referral_code' => $user->referral_code,
                'summaries_generated' => $user->summaries_generated ?? 0,
                'quizzes_created' => $user->quizzes_created ?? 0,
                'revision_sessions' => $user->revision_sessions ?? 0,
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Increment user statistics when content is created - NEW ENDPOINT
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function incrementUserStats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stat_type' => 'required|in:summaries_generated,quizzes_created,revision_sessions',
            'count' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $statType = $request->stat_type;
        $count = $request->count ?? 1;

        $user->increment($statType, $count);

        return response()->json([
            'success' => true,
            'message' => "Statistic '{$statType}' incremented by {$count}",
            'data' => [
                'summaries_generated' => $user->summaries_generated ?? 0,
                'quizzes_created' => $user->quizzes_created ?? 0,
                'revision_sessions' => $user->revision_sessions ?? 0,
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
            'coupon_code' => 'nullable|string', // Optional coupon code
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
        $originalAmount = ($request->billing_cycle === 'monthly') 
            ? $plan->price_monthly 
            : $plan->price_yearly;
        
        $amount = $originalAmount;
        $discountAmount = 0;
        $couponData = null;

        // Validate and apply coupon if provided
        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))
                ->where('is_active', 1)
                ->first();

            if ($coupon) {
                $usedCount = UserCoupon::where('coupon', $coupon->id)->count();
                
                // Check if coupon limit is reached
                if ($usedCount >= $coupon->limit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce code promo a atteint sa limite d\'utilisation',
                    ], 400);
                }
                
                // Check if user already used this coupon
                $userAlreadyUsed = UserCoupon::where('coupon', $coupon->id)
                    ->where('user', $user->id)
                    ->exists();
                
                if ($userAlreadyUsed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous avez déjà utilisé ce code promo',
                    ], 400);
                }
                
                // Calculate discount
                $discountAmount = ($originalAmount * $coupon->discount) / 100;
                $amount = $originalAmount - $discountAmount;
                
                $couponData = [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'discount' => $coupon->discount,
                    'discount_amount' => $discountAmount,
                ];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Code promo invalide ou expiré',
                ], 400);
            }
        }

        if ($amount == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Free plan does not require payment',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create payment record
            $flutterwaveData = [
                'billing_cycle' => $request->billing_cycle,
                'phone_number' => $request->phone_number,
                'original_amount' => $originalAmount,
            ];
            
            if ($couponData) {
                $flutterwaveData['coupon'] = $couponData;
            }
            
            $payment = MobileAppPayment::create([
                'user_id' => $user->id,
                'mobile_app_plan_id' => $plan->id,
                'amount' => $amount,
                'currency' => 'XAF', // FCFA
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'transaction_id' => 'TX-' . now()->timestamp . rand(1000,9999),
                'flutterwave_reference' => 'FW-' . now()->timestamp . rand(1000,9999),
                'flutterwave_data' => $flutterwaveData,
            ]);

            // TODO: Integrate with Flutterwave for actual payment
            // For now, return payment details for frontend to handle

            DB::commit();

            $responseData = [
                'payment_id' => $payment->id,
                'transaction_reference' => $payment->transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                // Flutterwave payment link would be here
                'payment_url' => route('mobile.payment.process', $payment->id),
            ];
            
            if ($couponData) {
                $responseData['coupon_applied'] = true;
                $responseData['original_amount'] = $originalAmount;
                $responseData['discount_amount'] = $discountAmount;
                $responseData['coupon_code'] = $couponData['code'];
                $responseData['discount_percentage'] = $couponData['discount'];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => $responseData,
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
            'coupon_code' => 'nullable|string', // Optional coupon code
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

        if ($payment->status === 'successful') {
            return response()->json([
                'success' => false,
                'message' => 'Payment already processed',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // TODO: Verify payment with Flutterwave using transaction_id
            // For now, we'll assume payment is successful

            // Update payment status (columns that exist in schema)
            $payment->update([
                'status' => 'successful',
                'transaction_id' => $request->transaction_id,
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
            $billingCycle = $payment->flutterwave_data['billing_cycle'] ?? $payment->billing_cycle ?? 'monthly';
            $endDate = ($billingCycle === 'monthly')
                ? now()->addMonth()
                : now()->addYear();

            // Create new subscription
            $subscription = MobileAppSubscription::create([
                'user_id' => $user->id,
                'mobile_app_plan_id' => $payment->mobile_app_plan_id,
                'billing_cycle' => $billingCycle,
                'status' => 'active',
                'started_at' => $startDate,
                'expires_at' => $endDate,
                'auto_renew' => true,
                'is_trial' => false,
            ]);

            // Mark referral as completed if user was referred
            ReferralController::completeReferral($user->id);
            
            // Save coupon usage if coupon was applied
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', strtoupper($request->coupon_code))
                    ->where('is_active', 1)
                    ->first();
                
                if ($coupon) {
                    UserCoupon::create([
                        'user' => $user->id,
                        'coupon' => $coupon->id,
                        'order' => $request->transaction_id,
                    ]);
                }
            }
            
            // Check for referral rewards
            $this->checkReferralRewards($user);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription activated successfully',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'plan_name' => $subscription->plan->name ?? null,
                    'status' => $subscription->status,
                    'start_date' => optional($subscription->started_at)->format('Y-m-d'),
                    'end_date' => optional($subscription->expires_at)->format('Y-m-d'),
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
                'end_date' => optional($subscription->expires_at)->format('Y-m-d'),
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
                    'billing_cycle' => $payment->flutterwave_data['billing_cycle'] ?? null,
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
        \App\Http\Controllers\Api\Mobile\ReferralController::grantRewardIfEligible($user->id);
    }
}
