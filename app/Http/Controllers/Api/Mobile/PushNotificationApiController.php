<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PushNotificationApiController extends Controller
{
    /**
     * Return full notification content (including HTML body) for mobile app popup.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $notification = PushNotification::find($id);
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        if (!$this->canUserAccessNotification($user, $notification)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $bodyHtml = (string) ($notification->body ?? '');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $notification->id,
                'title' => (string) $notification->title,
                'body_html' => $bodyHtml,
                'body_plain' => $this->htmlToPlainText($bodyHtml),
                'type' => (string) ($notification->type ?? ''),
                'sent_at' => optional($notification->sent_at)->toIso8601String(),
                'created_at' => optional($notification->created_at)->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Track notification open event (once per user per notification).
     */
    public function trackOpened(Request $request, int $id)
    {
        $user = $request->user();

        $notification = PushNotification::find($id);
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        if (!$this->canUserAccessNotification($user, $notification)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $cacheKey = sprintf('push_opened:%d:%d', (int) $notification->id, (int) $user->id);
        $isFirstOpen = Cache::add($cacheKey, true, now()->addYear());

        if ($isFirstOpen) {
            $notification->increment('opened_count');
            $notification->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'notification_id' => $notification->id,
                'opened_count' => (int) $notification->opened_count,
                'open_rate' => (float) $notification->open_rate,
                'counted' => (bool) $isFirstOpen,
            ],
        ], 200);
    }

    private function canUserAccessNotification(User $user, PushNotification $notification): bool
    {
        // The mobile inbox should only expose already-sent notifications.
        if ($notification->status !== 'sent') {
            return false;
        }

        $audience = (string) ($notification->target_audience ?? 'all');

        switch ($audience) {
            case 'specific_users':
                $targetIds = array_map('intval', (array) ($notification->specific_users ?? []));
                return in_array((int) $user->id, $targetIds, true);

            case 'students':
                return strtolower((string) $user->mobile_role) === 'student';

            case 'lawyers':
                return strtolower((string) $user->mobile_role) === 'lawyer';

            case 'enterprises':
                return strtolower((string) $user->mobile_role) === 'enterprise';

            case 'plan_specific':
                if (!$notification->target_plan) {
                    return false;
                }

                $activeSubscription = $user->activeMobileSubscription()->first();
                if (!$activeSubscription) {
                    return false;
                }

                return (int) $activeSubscription->mobile_app_plan_id === (int) $notification->target_plan;

            case 'all':
            default:
                return true;
        }
    }

    private function htmlToPlainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\r\n?|\n/u', "\n", $text) ?? $text;
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
