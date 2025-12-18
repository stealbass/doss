<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileSubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionApiController extends Controller
{
    public function plans()
    {
        $plans = MobileSubscriptionPlan::active()->visible()->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
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
