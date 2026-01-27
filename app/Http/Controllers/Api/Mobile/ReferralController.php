<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\MobileAppSubscription;
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

        $referralsCount = Referral::where('referrer_user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $rewardsEarned = floor($referralsCount / 10);
        $nextRewardAt = (floor($referralsCount / 10) + 1) * 10;
        $progressToNextReward = $referralsCount % 10;
        
        \Log::debug('DEBUG getReferralCode - Referrals count: ' . $referralsCount . ', Rewards earned: ' . $rewardsEarned);

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $user->referral_code ?? '',
                'total_referrals' => $referralsCount,
                'rewards_earned' => $rewardsEarned,
                'next_reward_at' => $nextRewardAt,
                'progress_to_next' => $progressToNextReward,
                'share_message' => "Rejoignez Dossy IA, l'assistant juridique intelligent ! Utilisez mon code de parrainage {$user->referral_code} pour vous inscrire. 10 parrainages = 1 mois gratuit !",
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

        $rewards = ReferralReward::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reward) {
                return [
                    'id' => $reward->id,
                    'reward_type' => $reward->reward_type,
                    'reward_value' => $reward->value,
                    'status' => $reward->status,
                    'description' => $this->getRewardDescription($reward),
                    'created_at' => $reward->created_at->format('Y-m-d H:i:s'),
                    'claimed_at' => $reward->claimed_at?->format('Y-m-d H:i:s'),
                ];
            });

        $summary = [
            'total_rewards' => $rewards->count(),
            'pending_rewards' => $rewards->where('status', 'pending')->count(),
            'claimed_rewards' => $rewards->where('status', 'claimed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'rewards' => $rewards,
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
     * Get reward description
     * 
     * @param ReferralReward $reward
     * @return string
     */
    private function getRewardDescription(ReferralReward $reward): string
    {
        if ($reward->reward_type === 'free_month') {
            $months = $reward->value;
            return "{$months} mois d'abonnement gratuit";
        }

        return 'Récompense de parrainage';
    }

    /**
     * Grant reward and auto-apply free month to active paid subscription if threshold reached
     */
    public static function grantRewardIfEligible(int $referrerId): void
    {
        $completedCount = Referral::where('referrer_user_id', $referrerId)
            ->where('status', 'completed')
            ->count();

        // One free month every 10 completed referrals
        $rewardsEarned = intdiv($completedCount, 10);
        $rewardsCreated = ReferralReward::where('user_id', $referrerId)
            ->where('reward_type', 'free_month')
            ->count();

        if ($rewardsEarned <= $rewardsCreated) {
            return;
        }

        $toCreate = $rewardsEarned - $rewardsCreated;
        for ($i = 0; $i < $toCreate; $i++) {
            \Log::info('Referral reward triggered', [
                'referrer_user_id' => $referrerId,
                'completed_referrals' => $completedCount,
                'creating_reward_index' => $i + 1,
            ]);

            $reward = ReferralReward::create([
                'user_id' => $referrerId,
                'reward_type' => 'free_month',
                'value' => 1,
                'description' => '1 mois gratuit offert pour 10 parrainages complétés.',
                'referrals_required' => 10,
                'referrals_completed' => 10,
                'status' => 'earned',
                'earned_at' => now(),
                'expires_at' => now()->addYear(),
            ]);

            // Auto-apply to active paid subscription
            $subscription = MobileAppSubscription::where('user_id', $referrerId)
                ->where('status', 'active')
                ->orderByDesc('expires_at')
                ->first();

            if ($subscription && $subscription->plan && $subscription->plan->price_monthly > 0) {
                $newExpiry = ($subscription->expires_at ?? now())->copy()->addMonth();
                $subscription->update(['expires_at' => $newExpiry]);
                \Log::info('Referral reward applied to subscription', [
                    'referrer_user_id' => $referrerId,
                    'subscription_id' => $subscription->id,
                    'new_expires_at' => $newExpiry,
                ]);
                $reward->update([
                    'status' => 'redeemed',
                    'redeemed_at' => now(),
                    'mobile_app_subscription_id' => $subscription->id,
                ]);
            }
        }
    }
}
