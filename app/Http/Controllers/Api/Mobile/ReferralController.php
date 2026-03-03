<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Models\ReferralCommission;
use App\Models\MobileAppPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    /**
     * Get user's referral code
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReferralCode(Request $request)
    {
        $user = $request->user();
        
        // Debug: Check if user is authenticated
        if (!$user) {
            \Log::error('DEBUG getReferralCode - User is NULL. Headers: ' . json_encode($request->headers->all()));
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
                'debug' => [
                    'user' => null,
                    'headers' => $request->headers->get('Authorization'),
                ]
            ], 401);
        }
        
        \Log::debug('DEBUG getReferralCode - User ID: ' . $user->id . ', Email: ' . $user->email . ', Code before: "' . $user->referral_code . '"');

        // Generate referral code if doesn't exist
        if (empty($user->referral_code)) {
            $user->referral_code = $this->generateUniqueReferralCode();
            $user->save();
            \Log::debug('DEBUG getReferralCode - Generated new code: "' . $user->referral_code . '"');
        }

        $completedReferrals = Referral::where('referrer_user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $commissionRate = 20.0;
        $isEligible = $completedReferrals >= 10;
        $pendingCommission = ReferralCommission::where('referrer_user_id', $user->id)
            ->where('status', 'pending')
            ->sum('commission_amount');
        $paidCommission = ReferralCommission::where('referrer_user_id', $user->id)
            ->where('status', 'paid')
            ->sum('commission_amount');
        $currency = ReferralCommission::where('referrer_user_id', $user->id)
            ->orderByDesc('id')
            ->value('currency') ?? 'XAF';
        
        \Log::debug('DEBUG getReferralCode - Completed referrals: ' . $completedReferrals . ', Commission eligible: ' . ($isEligible ? 'yes' : 'no'));

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $user->referral_code ?? '',
                'total_referrals' => $completedReferrals,
                'commission' => [
                    'rate' => $commissionRate,
                    'eligible' => $isEligible,
                    'threshold' => 10,
                    'completed_referrals' => $completedReferrals,
                    'pending_commission' => (float) $pendingCommission,
                    'paid_commission' => (float) $paidCommission,
                    'total_commission' => (float) ($pendingCommission + $paidCommission),
                    'currency' => $currency,
                ],
                'share_message' => "Rejoignez Dossy IA, l'assistant juridique intelligent ! Utilisez mon code de parrainage {$user->referral_code} pour vous inscrire. Après 10 abonnements réussis, gagnez 20% de commission sur les paiements de vos filleuls.",
            ],
        ], 200);
    }

    /**
     * Get referral history
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReferralHistory(Request $request)
    {
        $user = $request->user();

        $referrals = Referral::where('referrer_user_id', $user->id)
            ->with('referred:id,name,email,created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($referral) {
                return [
                    'id' => $referral->id,
                    'referred_user' => [
                        'name' => $referral->referred->name ?? 'N/A',
                        'email' => $referral->referred->email ?? 'N/A',
                    ],
                    'status' => $referral->status,
                    'created_at' => $referral->created_at->format('Y-m-d H:i:s'),
                    'completed_at' => $referral->completed_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $referrals,
        ], 200);
    }

    /**
     * Get referral rewards
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReferralRewards(Request $request)
    {
        $user = $request->user();

        $commissions = ReferralCommission::where('referrer_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'referred_user_id' => $commission->referred_user_id,
                    'payment_id' => $commission->mobile_app_payment_id,
                    'payment_amount' => (float) $commission->payment_amount,
                    'commission_amount' => (float) $commission->commission_amount,
                    'commission_rate' => (float) $commission->commission_rate,
                    'currency' => $commission->currency,
                    'status' => $commission->status,
                    'created_at' => $commission->created_at->format('Y-m-d H:i:s'),
                    'paid_at' => $commission->paid_at?->format('Y-m-d H:i:s'),
                ];
            });

        $completedReferrals = Referral::where('referrer_user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $commissionRate = 20.0;
        $pendingCommission = ReferralCommission::where('referrer_user_id', $user->id)
            ->where('status', 'pending')
            ->sum('commission_amount');
        $paidCommission = ReferralCommission::where('referrer_user_id', $user->id)
            ->where('status', 'paid')
            ->sum('commission_amount');
        $currency = ReferralCommission::where('referrer_user_id', $user->id)
            ->orderByDesc('id')
            ->value('currency') ?? 'XAF';

        $summary = [
            'commission_rate' => $commissionRate,
            'eligible' => $completedReferrals >= 10,
            'threshold' => 10,
            'completed_referrals' => $completedReferrals,
            'pending_commission' => (float) $pendingCommission,
            'paid_commission' => (float) $paidCommission,
            'total_commission' => (float) ($pendingCommission + $paidCommission),
            'currency' => $currency,
            'total_commissions' => $commissions->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'commissions' => $commissions,
                'summary' => $summary,
            ],
        ], 200);
    }

    /**
     * Validate referral code
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateReferralCode(Request $request)
    {
        $code = $request->input('code');

        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => 'Referral code is required',
            ], 422);
        }

        $referrer = User::where('referral_code', $code)->first();

        if (!$referrer) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Invalid referral code',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'data' => [
                'referrer_name' => $referrer->name,
                'message' => "Valid referral code from {$referrer->name}",
            ],
        ], 200);
    }

    /**
     * Apply referral code during registration (called from AuthController)
     * 
     * @param int $userId New user ID
     * @param string $referralCode
     * @return bool
     */
    public static function applyReferralCode(int $userId, string $referralCode): bool
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer) {
            return false;
        }

        // Create referral record
        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $userId,
            'status' => 'pending',
            'referral_code' => $referralCode,
        ]);

        return true;
    }

    /**
     * Mark referral as completed (called after first subscription)
     * 
     * @param int $userId
     * @return void
     */
    public static function completeReferral(int $userId): void
    {
        $referral = Referral::where('referred_user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($referral) {
            $referral->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Check if referrer has reached a reward threshold and auto-apply free month
            self::grantRewardIfEligible($referral->referrer_user_id);
        }

        // Fallback: also check by referrer code just in case
        $referrer = User::find($referral?->referrer_user_id);
        if ($referrer) {
            self::grantRewardIfEligible($referrer->id);
        }
    }

    /**
     * Generate unique referral code
     * 
     * @return string
     */
    private function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Legacy hook (free-month rewards removed). Commission logic handled per payment.
     */
    public static function grantRewardIfEligible(int $referrerId): void
    {
        return;
    }

    /**
     * Create a 20% commission for a successful payment by a referred user.
     */
    public static function createCommissionForPayment(MobileAppPayment $payment): void
    {
        if ($payment->status !== 'successful') {
            return;
        }

        $referral = Referral::where('referred_user_id', $payment->user_id)
            ->where('status', 'completed')
            ->first();

        if (!$referral) {
            return;
        }

        $completedReferrals = Referral::where('referrer_user_id', $referral->referrer_user_id)
            ->where('status', 'completed')
            ->count();

        if ($completedReferrals < 10) {
            return;
        }

        $rate = 20.0;
        $commissionAmount = round(((float) $payment->amount) * 0.20, 2);

        ReferralCommission::firstOrCreate(
            ['mobile_app_payment_id' => $payment->id],
            [
                'referrer_user_id' => $referral->referrer_user_id,
                'referred_user_id' => $payment->user_id,
                'payment_amount' => $payment->amount,
                'commission_rate' => $rate,
                'commission_amount' => $commissionAmount,
                'currency' => $payment->currency ?? 'XAF',
                'status' => 'pending',
            ]
        );
    }
}
