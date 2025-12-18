<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\LegalAlert;
use App\Models\LegalAlertRecipient;
use Illuminate\Http\Request;

class LegalAlertApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $userCountry = $user->country;

        $alerts = LegalAlert::published()
            ->byCountry($userCountry)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();

        $recipient = LegalAlertRecipient::firstOrCreate(
            [
                'legal_alert_id' => $id,
                'user_id' => $user->id,
            ],
            [
                'viewed_at' => now(),
            ]
        );

        if (!$recipient->viewed_at) {
            $recipient->update(['viewed_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Alert marked as read',
        ]);
    }
}
