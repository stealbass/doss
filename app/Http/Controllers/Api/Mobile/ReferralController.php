<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Models\ReferralReward;
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

        // Generate referral code if doesn't exist
        if (empty($user->referral_code)) {
            $user->referral_code = $this->generateUniqueReferralCode();
            $user->save();
        }

        $referralsCount = Referral::where('referrer_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $rewardsEarned = floor($referralsCount / 10);
        $nextRewardAt = (floor($referralsCount / 10) + 1) * 10;
        $progressToNextReward = $referralsCount % 10;

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $user->referral_code,
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

        $referrals = Referral::where('referrer_id', $user->id)
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
                    'reward_value' => $reward->reward_value,
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
            'referrer_id' => $referrer->id,
            'referred_id' => $userId,
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
        $referral = Referral::where('referred_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($referral) {
            $referral->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
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
            $months = $reward->reward_value;
            return "{$months} mois d'abonnement gratuit";
        }

        return 'Récompense de parrainage';
    }
}
