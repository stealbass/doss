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
     * Get all available plans - Format compatible with Flutter app
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlans()
    {
        $plans = MobileAppPlan::orderBy('price_monthly', 'asc')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => (string)$plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price_monthly, // Price in FCFA/XAF
                    'currency' => 'XAF', // Changed from FCFA to XAF (ISO 4217 code)
                    'duration' => 'monthly', // Default to monthly
                    'features' => $this->getPlanFeatures($plan),
                    'limits' => [
                        'searches' => $plan->searches_limit,
                        'analyses' => $plan->ai_analyses_limit,
                        'downloads' => $plan->pdf_downloads_limit,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'plans' => $plans, // Changed from 'data' to 'plans'
        ], 200);
    }

    /**
     * Get current subscription details for authenticated user
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
            // Return free plan if no active subscription
            return response()->json([
                'success' => true,
                'data' => [
                    'subscription_id' => null,
                    'plan' => [
                        'id' => 'gratuit',
                        'name' => 'Gratuit',
                        'price' => 0,
                        'currency' => 'XAF',
                    ],
                    'status' => 'active',
                    'is_trial' => false,
                    'usage' => $this->getUserUsage($user),
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscription_id' => $subscription->id,
                'plan' => [
                    'id' => (string)$subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'price' => $subscription->plan->price_monthly,
                    'currency' => 'XAF',
                ],
                'billing_cycle' => $subscription->billing_cycle,
                'status' => $subscription->status,
                'start_date' => $subscription->start_date->format('Y-m-d'),
                'end_date' => $subscription->end_date?->format('Y-m-d'),
                'auto_renew' => (bool)$subscription->auto_renew,
                'is_trial' => (bool)$subscription->is_trial,
                'usage' => $this->getUserUsage($user, $subscription->plan),
            ],
        ], 200);
    }

    /**
     * Get user profile with statistics
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
                'subscription_end' => $subscription?->end_date?->toIso8601String(),
                'searches_used' => $user->searches_used ?? 0,
                'searches_limit' => $subscription?->plan->searches_limit ?? 5,
                'analyses_used' => $user->analyses_used ?? 0,
                'analyses_limit' => $subscription?->plan->ai_analyses_limit ?? 2,
                'downloads_used' => $user->downloads_used ?? 0,
                'downloads_limit' => $subscription?->plan->pdf_downloads_limit ?? 0,
                'referral_count' => $user->referral_count ?? 0,
                'referral_code' => $user->referral_code,
                // NEW FIELDS: User statistics
                'summaries_generated' => $user->summaries_generated ?? 0,
                'quizzes_created' => $user->quizzes_created ?? 0,
                'revision_sessions' => $user->revision_sessions ?? 0,
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Increment user statistics when content is created
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

        // Increment the requested statistic
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
                'currency' => 'XAF',
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
     * Get plan features based on plan object
     * 
     * @param MobileAppPlan $plan
     * @return array
     */
    private function getPlanFeatures($plan)
    {
        $baseFeatures = [
            'Chat IA avec RAG',
            'Bibliothèque juridique',
            'Support 14 pays africains',
            'Multi-langue (FR/EN)',
            'Mode Sombre',
        ];

        $planFeatures = [
            1 => [ // Gratuit
                '5 recherches par mois',
                '2 analyses IA par mois',
                'Accès bibliothèque juridique de base',
                'Chat IA limité',
            ],
            2 => [ // Étudiant (2000 CFA)
                '50 recherches par mois',
                '20 analyses IA par mois',
                '10 téléchargements PDF',
                'Générateur de fiches d\'arrêt',
                'Générateur de fiches de révision',
                'QCM interactifs',
                'Mode révision active',
                'Transcription audio des cours',
            ],
            3 => [ // Pro (5000 CFA)
                '200 recherches par mois',
                '100 analyses IA par mois',
                '50 téléchargements PDF',
                'Anonymisation automatique',
                'Transcription audio',
                'Export Word éditable',
                'Modèles de contrats',
                'Veille juridique et alertes',
                'Assistant fiscal et social',
            ],
            4 => [ // Cabinet/Entreprise (15000 CFA)
                'Recherches illimitées',
                'Analyses IA illimitées',
                'Téléchargements illimités',
                'Multi-comptes (jusqu\'à 10 utilisateurs)',
                'Anonymisation automatique',
                'Export Word éditable',
                'Modèles de contrats premium',
                'Simulateurs RH et paie',
                'Veille juridique personnalisée',
                'Alertes Email et WhatsApp',
                'Support prioritaire 24/7',
                'Formation et onboarding',
            ],
        ];

        return $planFeatures[$plan->id] ?? $baseFeatures;
    }

    /**
     * Get user usage statistics
     * 
     * @param User $user
     * @param MobileAppPlan|null $plan
     * @return array
     */
    private function getUserUsage($user, $plan = null)
    {
        if (!$plan) {
            // Get free plan limits
            $searchLimit = 5;
            $analysesLimit = 2;
            $downloadLimit = 0;
        } else {
            $searchLimit = $plan->searches_limit;
            $analysesLimit = $plan->ai_analyses_limit;
            $downloadLimit = $plan->pdf_downloads_limit;
        }

        $searchesUsed = $user->searches_used ?? 0;
        $analysesUsed = $user->analyses_used ?? 0;
        $downloadsUsed = $user->downloads_used ?? 0;

        return [
            'searches' => [
                'used' => $searchesUsed,
                'limit' => $searchLimit,
                'remaining' => max(0, $searchLimit - $searchesUsed),
            ],
            'ai_analyses' => [
                'used' => $analysesUsed,
                'limit' => $analysesLimit,
                'remaining' => max(0, $analysesLimit - $analysesUsed),
            ],
            'pdf_downloads' => [
                'used' => $downloadsUsed,
                'limit' => $downloadLimit,
                'remaining' => max(0, $downloadLimit - $downloadsUsed),
            ],
        ];
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
