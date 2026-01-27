<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Models\MobileAppPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * API Controller pour la gestion des coupons dans l'application mobile
 * 
 * Fonctionnalités:
 * - Valider un code coupon
 * - Appliquer un coupon à un plan d'abonnement
 * - Récupérer l'historique des coupons utilisés
 */
class CouponApiController extends Controller
{
    /**
     * Valider et appliquer un code coupon à un plan
     * 
     * POST /api/mobile/coupons/validate
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'plan_id' => 'required|integer|exists:mobile_app_plans,id',
            'billing_cycle' => 'nullable|string|in:monthly,yearly'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer le plan
        $plan = MobileAppPlan::find($request->plan_id);
        
        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan non trouvé'
            ], 404);
        }

        // Rechercher le coupon par code (case-insensitive)
        $coupon = Coupon::where('code', strtoupper($request->coupon_code))
            ->where('is_active', 1)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Code promo invalide ou expiré',
                'coupon_valid' => false
            ], 404);
        }

        // Vérifier si le coupon a atteint sa limite d'utilisation
        $usedCount = UserCoupon::where('coupon', $coupon->id)->count();
        
        if ($usedCount >= $coupon->limit) {
            return response()->json([
                'success' => false,
                'message' => 'Ce code promo a atteint sa limite d\'utilisation',
                'coupon_valid' => false
            ], 400);
        }

        // Vérifier si l'utilisateur a déjà utilisé ce coupon
        $userAlreadyUsed = UserCoupon::where('coupon', $coupon->id)
            ->where('user', Auth::id())
            ->exists();

        if ($userAlreadyUsed) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà utilisé ce code promo',
                'coupon_valid' => false
            ], 400);
        }

        // Calculer le montant de la réduction selon le cycle de facturation
        $billingCycle = $request->billing_cycle ?? 'monthly';
        $originalPrice = ($billingCycle === 'yearly') ? $plan->price_yearly : $plan->price_monthly;
        $discountAmount = ($originalPrice * $coupon->discount) / 100;
        $finalPrice = $originalPrice - $discountAmount;

        return response()->json([
            'success' => true,
            'message' => 'Code promo appliqué avec succès',
            'coupon_valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount' => $coupon->discount,
                'description' => $coupon->description
            ],
            'pricing' => [
                'original_price' => $originalPrice,
                'discount_amount' => round($discountAmount, 2),
                'discount_percentage' => $coupon->discount,
                'final_price' => round($finalPrice, 2),
                'currency' => 'XAF',
                'billing_cycle' => $billingCycle
            ],
            'usage' => [
                'used_count' => $usedCount,
                'limit' => $coupon->limit,
                'remaining' => $coupon->limit - $usedCount
            ]
        ], 200);
    }

    /**
     * Marquer un coupon comme utilisé après un paiement réussi
     * 
     * POST /api/mobile/coupons/mark-used
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markCouponAsUsed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'order_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Rechercher le coupon
        $coupon = Coupon::where('code', strtoupper($request->coupon_code))
            ->where('is_active', 1)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Code promo invalide'
            ], 404);
        }

        // Vérifier si déjà marqué comme utilisé
        $existingUsage = UserCoupon::where('coupon', $coupon->id)
            ->where('user', Auth::id())
            ->where('order', $request->order_id)
            ->first();

        if ($existingUsage) {
            return response()->json([
                'success' => true,
                'message' => 'Coupon déjà marqué comme utilisé'
            ], 200);
        }

        // Créer l'enregistrement d'utilisation
        $userCoupon = new UserCoupon();
        $userCoupon->user = Auth::id();
        $userCoupon->coupon = $coupon->id;
        $userCoupon->order = $request->order_id;
        $userCoupon->save();

        return response()->json([
            'success' => true,
            'message' => 'Coupon marqué comme utilisé avec succès'
        ], 200);
    }

    /**
     * Récupérer l'historique des coupons utilisés par l'utilisateur connecté
     * 
     * GET /api/mobile/coupons/my-history
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyUsedCoupons()
    {
        $userId = Auth::id();
        
        $usedCoupons = UserCoupon::with('couponDetails')
            ->where('user', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $history = $usedCoupons->map(function ($userCoupon) {
            $coupon = $userCoupon->couponDetails;
            
            return [
                'id' => $userCoupon->id,
                'coupon_code' => $coupon->code ?? 'N/A',
                'coupon_name' => $coupon->name ?? 'N/A',
                'discount' => $coupon->discount ?? 0,
                'order_id' => $userCoupon->order,
                'used_at' => $userCoupon->created_at->format('Y-m-d H:i:s'),
                'used_at_human' => $userCoupon->created_at->diffForHumans()
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Historique des coupons récupéré avec succès',
            'data' => [
                'total_used' => $history->count(),
                'coupons' => $history
            ]
        ], 200);
    }

    /**
     * Récupérer tous les coupons actifs disponibles
     * 
     * GET /api/mobile/coupons/available
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableCoupons()
    {
        $coupons = Coupon::where('is_active', 1)->get();

        $availableCoupons = $coupons->filter(function ($coupon) {
            $usedCount = UserCoupon::where('coupon', $coupon->id)->count();
            return $usedCount < $coupon->limit;
        })->map(function ($coupon) {
            $usedCount = UserCoupon::where('coupon', $coupon->id)->count();
            
            return [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'discount' => $coupon->discount,
                'usage' => [
                    'used_count' => $usedCount,
                    'limit' => $coupon->limit,
                    'remaining' => $coupon->limit - $usedCount
                ]
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Coupons disponibles récupérés avec succès',
            'data' => [
                'total_available' => $availableCoupons->count(),
                'coupons' => $availableCoupons
            ]
        ], 200);
    }
}
