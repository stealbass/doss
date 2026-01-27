<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileAppSetting;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Get mobile app configuration
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfig(Request $request)
    {
        try {
            $settings = MobileAppSetting::get();
            $platform = $request->input('platform', 'android'); // android or ios
            $appVersion = $request->input('app_version', '1.0.0');

            // Check if update is required
            $updateRequired = !$settings->isVersionValid($platform, $appVersion);
            $minVersion = $platform === 'android' ? $settings->min_android_version : $settings->min_ios_version;
            $latestVersion = $platform === 'android' ? $settings->android_version : $settings->ios_version;

            // Check maintenance mode
            $inMaintenance = $settings->isInMaintenance();

            $response = [
                'success' => true,
                'data' => [
                    // Version Info
                    'version_info' => [
                        'latest_version' => $latestVersion,
                        'min_version' => $minVersion,
                        'update_required' => $updateRequired,
                        'force_update' => $platform === 'android' ? $settings->force_update_android : $settings->force_update_ios,
                    ],

                    // Maintenance Mode
                    'maintenance' => [
                        'enabled' => $inMaintenance,
                        'message' => $settings->maintenance_message,
                        'start_time' => $settings->maintenance_start?->toIso8601String(),
                        'end_time' => $settings->maintenance_end?->toIso8601String(),
                    ],

                    // Features Toggle
                    'features' => [
                        'chat_enabled' => $settings->chat_enabled,
                        'documents_enabled' => $settings->documents_enabled,
                        'tools_enabled' => $settings->tools_enabled,
                        'referral_enabled' => $settings->referral_enabled,
                    ],

                    // App URLs
                    'urls' => [
                        'play_store' => $settings->play_store_url,
                        'app_store' => $settings->app_store_url,
                        'privacy_policy' => $settings->privacy_policy_url,
                        'terms_of_service' => $settings->terms_of_service_url,
                    ],

                    // Support Info
                    'support' => [
                        'email' => $settings->support_email,
                        'phone' => $settings->support_phone,
                        'whatsapp' => $settings->whatsapp_number,
                    ],

                    // Payment Configuration (Public key only)
                    'payment' => [
                        'flutterwave_public_key' => $settings->flutterwave_public_key,
                        'environment' => $settings->flutterwave_environment,
                    ],
                ],
            ];

            // If in maintenance, return limited config
            if ($inMaintenance) {
                return response()->json([
                    'success' => false,
                    'error' => 'MAINTENANCE_MODE',
                    'message' => $settings->maintenance_message ?? 'The app is currently under maintenance. Please try again later.',
                    'data' => [
                        'maintenance' => $response['data']['maintenance'],
                        'support' => $response['data']['support'],
                    ],
                ], 503);
            }

            // If update required, include update info
            if ($updateRequired) {
                return response()->json([
                    'success' => false,
                    'error' => 'UPDATE_REQUIRED',
                    'message' => 'Please update your app to the latest version.',
                    'data' => [
                        'version_info' => $response['data']['version_info'],
                        'urls' => $response['data']['urls'],
                    ],
                ], 426); // 426 Upgrade Required
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch app configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get plan limits for authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlanLimits(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $settings = MobileAppSetting::get();
            $planName = $user->plan ?? 'Gratuit';
            $limits = $settings->getLimitsForPlan($planName);

            return response()->json([
                'success' => true,
                'data' => [
                    'plan' => $planName,
                    'limits' => $limits,
                    'usage' => [
                        'searches' => $user->searches_used ?? 0,
                        'analyses' => $user->analyses_used ?? 0,
                        'downloads' => $user->downloads_used ?? 0,
                    ],
                    'remaining' => [
                        'searches' => $limits['searches'] == -1 ? -1 : max(0, $limits['searches'] - ($user->searches_used ?? 0)),
                        'analyses' => $limits['analyses'] == -1 ? -1 : max(0, $limits['analyses'] - ($user->analyses_used ?? 0)),
                        'downloads' => $limits['downloads'] == -1 ? -1 : max(0, $limits['downloads'] - ($user->downloads_used ?? 0)),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plan limits',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
