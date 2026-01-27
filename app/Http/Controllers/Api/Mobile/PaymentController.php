<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAppPlan;
use App\Models\MobileAppPayment;
use App\Models\MobileAppSetting;
use App\Models\MobileAppSubscription;
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
        $settings = MobileAppSetting::first();

        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'Flutterwave settings not configured',
            ], 500);
        }

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found',
            ], 404);
        }

        // Calculate amount based on billing cycle
        $amount = ($request->billing_cycle === 'monthly') 
            ? $plan->price_monthly 
            : $plan->price_yearly;

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Free plan does not require payment',
            ], 400);
        }

        try {
            // Generate unique transaction reference (same format as SaaS)
            $txRef = 'DOSSY-MOBILE-' . $user->id . '-' . time() . '-' . rand(1000, 9999);

            // Create pending payment record
            $payment = MobileAppPayment::create([
                'user_id' => $user->id,
                'mobile_app_plan_id' => $plan->id,
                'amount' => $amount,
                'currency' => 'XAF',
                'payment_method' => 'flutterwave',
                'status' => 'pending',
                'transaction_id' => $txRef,
                'flutterwave_reference' => $txRef,
                'flutterwave_data' => [
                    'billing_cycle' => $request->billing_cycle,
                    'plan_name' => $plan->name,
                ],
            ]);

            // Return data for frontend to construct Flutterwave payment (same as SaaS)
            return response()->json([
                'success' => true,
                'message' => 'Payment data ready',
                'data' => [
                    'payment_id' => $payment->id,
                    'tx_ref' => $txRef,
                    'amount' => $amount,
                    'currency' => 'XAF',
                    'email' => $user->email,
                    'name' => $user->name,
                    'phone' => $user->phone ?? '',
                    'plan_name' => $plan->name,
                    'public_key' => $settings->flutterwave_public_key,
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

        $settings = MobileAppSetting::first();
        
        if (!$settings || !$settings->flutterwave_secret_key) {
            Log::error('Flutterwave settings not configured');
            return redirect()->to('dossychatia://payment/callback?status=error&message=' . urlencode('Flutterwave not configured'));
        }

        $plan = $payment->plan;
        $user = $payment->user;

        try {
            // Verify with Flutterwave API v3 (nouvelle API)
            $url = "https://api.flutterwave.com/v3/transactions/{$transactionId}/verify";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings->flutterwave_secret_key,
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
                $this->activateSubscription($payment);

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
        MobileAppSubscription::updateOrCreate(
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
    }

}
