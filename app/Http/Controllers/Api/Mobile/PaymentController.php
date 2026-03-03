<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAppPlan;
use App\Models\MobileAppPayment;
use App\Models\MobileAppSetting;
use App\Models\Utility;
use App\Models\MobileAppSubscription;
use App\Http\Controllers\Api\Mobile\ReferralController;
use App\Models\UserCoupon;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Initiate payment - returns data for frontend to redirect to Flutterwave
     * Same flow as SaaS planPayWithFlutterwave
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:mobile_app_plans,id',
            'billing_cycle' => 'required|in:monthly,annual',
            'coupon_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $plan = MobileAppPlan::find($request->plan_id);
        $keys = $this->resolveFlutterwaveKeys();

        if (empty($keys['public_key'])) {
            return response()->json([
                'success' => false,
                'message' => 'Flutterwave public key not configured',
            ], 500);
        }

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found',
            ], 404);
        }

        // Calculate amount based on billing cycle
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

                if ($usedCount >= $coupon->limit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce code promo a atteint sa limite d\'utilisation',
                    ], 400);
                }

                $userAlreadyUsed = UserCoupon::where('coupon', $coupon->id)
                    ->where('user', $user->id)
                    ->exists();

                if ($userAlreadyUsed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous avez déjà utilisé ce code promo',
                    ], 400);
                }

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

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Free plan does not require payment',
            ], 400);
        }

        // Determine country and currency for Flutterwave
        $countryCode = strtoupper((string) ($user->country ?? 'CM'));
        $cemac = ['CM', 'GA', 'GQ', 'TD', 'CF', 'CG'];
        $uemoa = ['BJ', 'BF', 'CI', 'GW', 'ML', 'NE', 'SN', 'TG'];
        $currency = in_array($countryCode, $uemoa, true) ? 'XOF' : 'XAF';

        try {
            // Generate unique transaction reference (same format as SaaS)
            $txRef = 'DOSSY-MOBILE-' . $user->id . '-' . time() . '-' . rand(1000, 9999);

            // Create pending payment record
            $flutterwaveData = [
                'billing_cycle' => $request->billing_cycle,
                'plan_name' => $plan->name,
                'original_amount' => $originalAmount,
            ];

            if ($couponData) {
                $flutterwaveData['coupon'] = $couponData;
            }

            $payment = MobileAppPayment::create([
                'user_id' => $user->id,
                'mobile_app_plan_id' => $plan->id,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => 'flutterwave',
                'status' => 'pending',
                'transaction_id' => $txRef,
                'flutterwave_reference' => $txRef,
                'flutterwave_data' => $flutterwaveData,
            ]);

            // Return data for frontend to construct Flutterwave payment (same as SaaS)
            return response()->json([
                'success' => true,
                'message' => 'Payment data ready',
                'data' => [
                    'payment_id' => $payment->id,
                    'tx_ref' => $txRef,
                    'amount' => $amount,
                    'currency' => $currency,
                    'country' => $countryCode,
                    'email' => $user->email,
                    'name' => $user->name,
                    'phone' => $user->phone ?? '',
                    'plan_name' => $plan->name,
                    'public_key' => $keys['public_key'],
                    'callback_url' => url('/api/mobile/payment/callback'),
                    'redirect_url' => route('mobile.payment.callback', ['tx_ref' => $txRef, 'plan_id' => $plan->id]),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Payment initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Payment callback from Flutterwave - same flow as SaaS getPaymentStatus
     * Verifies payment with Flutterwave API and activates subscription
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function paymentCallback(Request $request)
    {
        $txRef = $request->input('tx_ref');
        $status = $request->input('status');
        $transactionId = $request->input('transaction_id');

        Log::info('Flutterwave callback received', [
            'tx_ref' => $txRef,
            'status' => $status,
            'transaction_id' => $transactionId,
        ]);

        // Find payment by tx_ref
        $payment = MobileAppPayment::where('flutterwave_reference', $txRef)->first();

        if (!$payment) {
            Log::error('Payment not found for callback', ['tx_ref' => $txRef]);
            return redirect()->to('dossychatia://payment/callback?status=error&message=' . urlencode('Payment not found'));
        }

        $keys = $this->resolveFlutterwaveKeys();
        
        if (empty($keys['secret_key'])) {
            Log::error('Flutterwave settings not configured');
            return redirect()->to('dossychatia://payment/callback?status=error&message=' . urlencode('Flutterwave not configured'));
        }

        $plan = $payment->plan;
        $user = $payment->user;

        try {
            // Verify with Flutterwave API v3 (nouvelle API)
            $url = "https://api.flutterwave.com/v3/transactions/{$transactionId}/verify";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $keys['secret_key'],
                'Content-Type' => 'application/json',
            ])->get($url);

            $responseData = $response->json();

            Log::info('Flutterwave verification response', ['response' => $responseData]);

            // Check if verification was successful
            if ($responseData['status'] === 'success' && 
                isset($responseData['data']) && 
                $responseData['data']['status'] === 'successful' &&
                $responseData['data']['amount'] >= $payment->amount &&
                $responseData['data']['currency'] === $payment->currency) {
                
                $paydata = $responseData['data'];

                // Update payment status
                $payment->update([
                    'status' => 'successful',
                    'paid_at' => now(),
                    'transaction_id' => $transactionId ?? $txRef,
                    'flutterwave_data' => array_merge(
                        $payment->flutterwave_data ?? [],
                        ['verification_data' => $paydata]
                    ),
                ]);

                // Activate subscription (same logic as SaaS)
                $subscription = $this->activateSubscription($payment);

                // Mark referral completed and create commission if eligible
                ReferralController::completeReferral($payment->user_id);
                ReferralController::createCommissionForPayment($payment);

                if ($subscription) {
                    $this->sendSubscriptionConfirmationEmail($payment, $subscription);
                }

                // Mark coupon as used if present
                $coupon = $payment->flutterwave_data['coupon'] ?? null;
                if ($coupon && isset($coupon['id'])) {
                    UserCoupon::firstOrCreate(
                        [
                            'user' => $payment->user_id,
                            'coupon' => $coupon['id'],
                        ],
                        [
                            'order' => $payment->transaction_id,
                        ]
                    );
                }

                Log::info('Payment completed and subscription activated', [
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ]);

                // Redirect back to app with success
                return redirect()->to('dossychatia://payment/callback?status=success&tx_ref=' . $txRef);
            } else {
                Log::warning('Payment verification failed', ['response' => $response]);
                return redirect()->to('dossychatia://payment/callback?status=failed&message=' . urlencode('Payment verification failed'));
            }

        } catch (\Exception $e) {
            Log::error('Payment callback exception', [
                'error' => $e->getMessage(),
                'tx_ref' => $txRef,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->to('dossychatia://payment/callback?status=error&message=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Resolve Flutterwave keys with fallback to admin payment settings.
     */
    private function resolveFlutterwaveKeys(): array
    {
        $settings = MobileAppSetting::first();
        $publicKey = $settings?->flutterwave_public_key;
        $secretKey = $settings?->flutterwave_secret_key;

        if (empty($publicKey) || empty($secretKey)) {
            $adminSettings = Utility::payment_settings();
            $publicKey = $publicKey ?: ($adminSettings['flutterwave_public_key'] ?? null);
            $secretKey = $secretKey ?: ($adminSettings['flutterwave_secret_key'] ?? null);
        }

        return [
            'public_key' => $publicKey,
            'secret_key' => $secretKey,
        ];
    }

    /**
     * Activate subscription after successful payment
     */
    private function activateSubscription($payment)
    {
        $plan = $payment->plan;
        $billingCycle = $payment->flutterwave_data['billing_cycle'] ?? 'monthly';
        
        // Calculate expiry date
        $expiresAt = $billingCycle === 'monthly' 
            ? Carbon::now()->addMonth() 
            : Carbon::now()->addYear();

        // Create or update subscription
        $subscription = MobileAppSubscription::updateOrCreate(
            ['user_id' => $payment->user_id],
            [
                'mobile_app_plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'status' => 'active',
                'started_at' => Carbon::now(),
                'expires_at' => $expiresAt,
                'next_billing_date' => $expiresAt,
                'amount_paid' => $payment->amount,
                'payment_reference' => $payment->transaction_id,
                'auto_renew' => true,
                'searches_used' => 0,
                'ai_analyses_used' => 0,
                'pdf_downloads_used' => 0,
                'quota_reset_at' => Carbon::now()->addMonth(),
            ]
        );

        $payment->update([
            'mobile_app_subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    /**
     * Send subscription confirmation email for mobile app users.
     */
    private function sendSubscriptionConfirmationEmail(MobileAppPayment $payment, MobileAppSubscription $subscription): void
    {
        try {
            $subscription->loadMissing('user', 'plan');
            $user = $subscription->user;
            $plan = $subscription->plan;

            if (!$user || !$user->email || !$plan) {
                return;
            }

            $ownerId = $user->creatorId() ?: 1;
            Utility::getSMTPDetails($ownerId);

            $planPrice = number_format($payment->amount, 0) . ' ' . $payment->currency;
            $planDuration = ($subscription->billing_cycle === 'annual')
                ? 'Annuel (12 mois)'
                : 'Mensuel (1 mois)';

            $emailData = [
                'userName' => $user->name,
                'planName' => $plan->name,
                'planPrice' => $planPrice,
                'planDuration' => $planDuration,
                'expirationDate' => optional($subscription->expires_at)->toDateString(),
                'paymentMethod' => 'Flutterwave',
                'dashboardUrl' => route('home'),
            ];

            
            
            \Mail::to($user->email)->send(
                new \App\Mail\SubscriptionConfirmation($user, $plan, $emailData)
            );

            Log::info('Mobile subscription confirmation email sent', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send mobile subscription confirmation email', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
