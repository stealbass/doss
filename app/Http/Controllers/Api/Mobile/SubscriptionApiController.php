<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAppPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SubscriptionApiController extends Controller
{
    public function plans()
    {
        // Get plans from mobile_app_plans (the real plans created by admin)
        $query = MobileAppPlan::query();
        $query->where('is_active', true);
        if (Schema::hasColumn('mobile_app_plans', 'sort_order')) {
            $query->orderBy('sort_order', 'asc')->orderBy('price_monthly', 'asc');
        } else {
            $query->orderBy('price_monthly', 'asc');
        }

        $plans = $query->get()
            ->map(function ($plan) {
                // Generate readable features from plan data
                $features = [];
                
                // Searches limit
                if ($plan->searches_limit == -1) {
                    $features[] = 'Recherches illimitées';
                } else {
                    $features[] = "{$plan->searches_limit} recherches par mois";
                }
                
                // AI analyses limit
                if ($plan->ai_analyses_limit == -1) {
                    $features[] = 'Analyses IA illimitées';
                } else {
                    $features[] = "{$plan->ai_analyses_limit} analyses IA par mois";
                }
                
                // PDF downloads limit
                if ($plan->pdf_downloads_limit == -1) {
                    $features[] = 'Téléchargements PDF illimités';
                } elseif ($plan->pdf_downloads_limit > 0) {
                    $features[] = "{$plan->pdf_downloads_limit} téléchargements PDF par mois";
                } else {
                    $features[] = 'Pas de téléchargement PDF';
                }
                
                // Full history
                if ($plan->has_full_history) {
                    $features[] = 'Historique complet des conversations';
                }
                
                // Advanced AI
                if ($plan->has_advanced_ai) {
                    $features[] = 'IA avancée (' . ($plan->ai_model ?? 'GPT-4') . ')';
                } else {
                    $features[] = 'IA standard (' . ($plan->ai_model ?? 'GPT-3.5') . ')';
                }

                return [
                    'id' => (string)$plan->id,
                    'name' => $plan->name,
                    'name_fr' => $plan->name_fr ?? $plan->name,
                    'price_monthly' => (float)$plan->price_monthly,
                    'price_yearly' => (float)($plan->price_yearly ?? $plan->price_monthly * 12),
                    'price' => (float)$plan->price_monthly,
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
            })
            ->values();

        return response()->json([
            'success' => true,
            'plans' => $plans,
        ], 200);
    }

    public function currentPlan(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'current_plan' => $user->plan ?? 'Gratuit',
                'expires_at' => $user->plan_expires_at ?? null,
            ],
        ]);
    }
}
