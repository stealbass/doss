<?php

namespace App\Http\Controllers;

use App\Models\PushNotification;
use App\Models\User;
use App\Models\MobileAppPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PushNotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la liste des notifications
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $type = $request->get('type');

        $query = PushNotification::with('creator');

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->orderByDesc('created_at')->paginate(20);

        // Statistiques
        $stats = [
            'total' => PushNotification::count(),
            'sent' => PushNotification::where('status', 'sent')->count(),
            'scheduled' => PushNotification::where('status', 'scheduled')
                ->where('scheduled_at', '>', now())->count(),
            'drafts' => PushNotification::where('status', 'draft')->count(),
            'total_recipients' => PushNotification::where('status', 'sent')->sum('successful_sends'),
            'avg_open_rate' => $this->calculateAverageOpenRate(),
        ];

        return view('push-notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        $plans = MobileAppPlan::where('is_active', true)->get();
        $targetAudiences = [
            'all' => 'Tous les utilisateurs',
            'students' => 'Étudiants uniquement',
            'lawyers' => 'Avocats uniquement',
            'enterprises' => 'Entreprises uniquement',
            'plan_specific' => 'Plan spécifique',
        ];

        return view('push-notifications.create', compact('plans', 'targetAudiences'));
    }

    /**
     * Enregistre une nouvelle notification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'required|in:general,promotion,alert,update',
            'target_audience' => 'required|in:all,students,lawyers,enterprises,plan_specific',
            'target_plan' => 'required_if:target_audience,plan_specific|nullable|exists:mobile_app_plans,id',
            'image_url' => 'nullable|url|max:500',
            'action_url' => 'nullable|string|max:500',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = $request->has('send_now') ? 'sending' : 
                              ($request->scheduled_at ? 'scheduled' : 'draft');

        $notification = PushNotification::create($validated);

        // Si envoi immédiat
        if ($request->has('send_now')) {
            $this->sendNotification($notification);
            return redirect()->route('push-notifications.index')
                ->with('success', 'Notification envoyée avec succès !');
        }

        return redirect()->route('push-notifications.index')
            ->with('success', 'Notification créée avec succès !');
    }

    /**
     * Affiche les détails d'une notification
     */
    public function show($id)
    {
        $notification = PushNotification::with('creator')->findOrFail($id);
        
        return view('push-notifications.show', compact('notification'));
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit($id)
    {
        $notification = PushNotification::findOrFail($id);

        // Seuls les brouillons peuvent être édités
        if (!in_array($notification->status, ['draft', 'scheduled'])) {
            return redirect()->route('push-notifications.index')
                ->with('error', 'Cette notification ne peut pas être modifiée.');
        }

        $plans = MobileAppPlan::where('is_active', true)->get();
        $targetAudiences = [
            'all' => 'Tous les utilisateurs',
            'students' => 'Étudiants uniquement',
            'lawyers' => 'Avocats uniquement',
            'enterprises' => 'Entreprises uniquement',
            'plan_specific' => 'Plan spécifique',
        ];

        return view('push-notifications.edit', compact('notification', 'plans', 'targetAudiences'));
    }

    /**
     * Met à jour une notification
     */
    public function update(Request $request, $id)
    {
        $notification = PushNotification::findOrFail($id);

        if (!in_array($notification->status, ['draft', 'scheduled'])) {
            return redirect()->route('push-notifications.index')
                ->with('error', 'Cette notification ne peut pas être modifiée.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'type' => 'required|in:general,promotion,alert,update',
            'target_audience' => 'required|in:all,students,lawyers,enterprises,plan_specific',
            'target_plan' => 'required_if:target_audience,plan_specific|nullable|exists:mobile_app_plans,id',
            'image_url' => 'nullable|url|max:500',
            'action_url' => 'nullable|string|max:500',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['status'] = $request->scheduled_at ? 'scheduled' : 'draft';

        $notification->update($validated);

        return redirect()->route('push-notifications.index')
            ->with('success', 'Notification mise à jour avec succès !');
    }

    /**
     * Envoie immédiatement une notification
     */
    public function send($id)
    {
        $notification = PushNotification::findOrFail($id);

        if (!$notification->canBeSent()) {
            return redirect()->route('push-notifications.index')
                ->with('error', 'Cette notification ne peut pas être envoyée.');
        }

        $this->sendNotification($notification);

        return redirect()->route('push-notifications.index')
            ->with('success', 'Notification envoyée avec succès !');
    }

    /**
     * Logique d'envoi de notification (simulation)
     */
    private function sendNotification(PushNotification $notification)
    {
        $notification->status = 'sending';
        $notification->save();

        try {
            // Récupérer les destinataires
            $recipients = $this->getRecipients($notification);
            $totalRecipients = $recipients->count();

            $notification->update([
                'total_recipients' => $totalRecipients,
            ]);

            // TODO: Intégration Firebase Cloud Messaging
            // Pour l'instant, on simule l'envoi
            $successful = 0;
            $failed = 0;

            foreach ($recipients as $user) {
                // Simulation d'envoi
                $success = rand(0, 100) > 5; // 95% de succès
                
                if ($success) {
                    $successful++;
                } else {
                    $failed++;
                }
            }

            // Mise à jour des statistiques
            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'successful_sends' => $successful,
                'failed_sends' => $failed,
                // Simulation d'ouvertures et clics
                'opened_count' => (int)($successful * 0.45), // 45% taux d'ouverture
                'clicked_count' => (int)($successful * 0.45 * 0.3), // 30% de CTR
            ]);

            return true;

        } catch (\Exception $e) {
            $notification->update([
                'status' => 'failed',
            ]);

            \Log::error('Erreur envoi notification push: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les destinataires selon le ciblage
     */
    private function getRecipients(PushNotification $notification)
    {
        $query = User::whereHas('mobileSubscriptions');

        switch ($notification->target_audience) {
            case 'students':
                $query->where('mobile_role', 'student');
                break;
            
            case 'lawyers':
                $query->where('mobile_role', 'lawyer');
                break;
            
            case 'enterprises':
                $query->where('mobile_role', 'enterprise');
                break;
            
            case 'plan_specific':
                if ($notification->target_plan) {
                    $query->whereHas('activeMobileSubscription', function($q) use ($notification) {
                        $q->where('plan_id', $notification->target_plan);
                    });
                }
                break;
            
            case 'all':
            default:
                // Tous les utilisateurs mobiles
                break;
        }

        return $query->get();
    }

    /**
     * Duplique une notification
     */
    public function duplicate($id)
    {
        $original = PushNotification::findOrFail($id);
        
        $notification = $original->replicate();
        $notification->title = $original->title . ' (Copie)';
        $notification->status = 'draft';
        $notification->scheduled_at = null;
        $notification->sent_at = null;
        $notification->total_recipients = 0;
        $notification->successful_sends = 0;
        $notification->failed_sends = 0;
        $notification->opened_count = 0;
        $notification->clicked_count = 0;
        $notification->created_by = Auth::id();
        $notification->save();

        return redirect()->route('push-notifications.edit', $notification->id)
            ->with('success', 'Notification dupliquée avec succès !');
    }

    /**
     * Supprime une notification
     */
    public function destroy($id)
    {
        $notification = PushNotification::findOrFail($id);

        // Seuls les brouillons peuvent être supprimés
        if ($notification->status !== 'draft') {
            return redirect()->route('push-notifications.index')
                ->with('error', 'Seuls les brouillons peuvent être supprimés.');
        }

        $notification->delete();

        return redirect()->route('push-notifications.index')
            ->with('success', 'Notification supprimée avec succès !');
    }

    /**
     * Annule une notification planifiée
     */
    public function cancel($id)
    {
        $notification = PushNotification::findOrFail($id);

        if ($notification->status !== 'scheduled') {
            return redirect()->route('push-notifications.index')
                ->with('error', 'Cette notification ne peut pas être annulée.');
        }

        $notification->update([
            'status' => 'draft',
            'scheduled_at' => null,
        ]);

        return redirect()->route('push-notifications.index')
            ->with('success', 'Notification annulée avec succès !');
    }

    /**
     * Prévisualise les destinataires
     */
    public function previewRecipients(Request $request)
    {
        $audience = $request->get('audience');
        $planId = $request->get('plan_id');

        $query = User::whereHas('mobileSubscriptions');

        switch ($audience) {
            case 'students':
                $query->where('mobile_role', 'student');
                break;
            case 'lawyers':
                $query->where('mobile_role', 'lawyer');
                break;
            case 'enterprises':
                $query->where('mobile_role', 'enterprise');
                break;
            case 'plan_specific':
                if ($planId) {
                    $query->whereHas('activeMobileSubscription', function($q) use ($planId) {
                        $q->where('plan_id', $planId);
                    });
                }
                break;
        }

        $count = $query->count();
        $breakdown = $this->getAudienceBreakdown($query->get());

        return response()->json([
            'total' => $count,
            'breakdown' => $breakdown
        ]);
    }

    /**
     * Répartition de l'audience
     */
    private function getAudienceBreakdown($users)
    {
        return [
            'by_role' => [
                'student' => $users->where('mobile_role', 'student')->count(),
                'lawyer' => $users->where('mobile_role', 'lawyer')->count(),
                'enterprise' => $users->where('mobile_role', 'enterprise')->count(),
            ],
            'by_plan' => $users->groupBy(function($user) {
                return $user->activeMobileSubscription->plan->name_fr ?? 'Aucun';
            })->map->count()->toArray()
        ];
    }

    /**
     * Calcule le taux d'ouverture moyen
     */
    private function calculateAverageOpenRate()
    {
        $sentNotifications = PushNotification::where('status', 'sent')
            ->where('successful_sends', '>', 0)
            ->get();

        if ($sentNotifications->isEmpty()) {
            return 0;
        }

        $totalOpenRate = $sentNotifications->sum(function($notification) {
            return $notification->open_rate;
        });

        return round($totalOpenRate / $sentNotifications->count(), 2);
    }

    /**
     * Statistiques détaillées
     */
    public function statistics()
    {
        $stats = [
            'total_notifications' => PushNotification::count(),
            'sent_notifications' => PushNotification::where('status', 'sent')->count(),
            'total_recipients' => PushNotification::where('status', 'sent')->sum('successful_sends'),
            'avg_open_rate' => $this->calculateAverageOpenRate(),
            'avg_click_rate' => $this->calculateAverageClickRate(),
            'by_type' => PushNotification::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'recent_performance' => $this->getRecentPerformance(),
        ];

        return response()->json($stats);
    }

    /**
     * Calcule le CTR moyen
     */
    private function calculateAverageClickRate()
    {
        $sentNotifications = PushNotification::where('status', 'sent')
            ->where('opened_count', '>', 0)
            ->get();

        if ($sentNotifications->isEmpty()) {
            return 0;
        }

        $totalClickRate = $sentNotifications->sum(function($notification) {
            return $notification->click_rate;
        });

        return round($totalClickRate / $sentNotifications->count(), 2);
    }

    /**
     * Performance des 7 derniers jours
     */
    private function getRecentPerformance()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            $sent = PushNotification::where('status', 'sent')
                ->whereDate('sent_at', $date->toDateString())
                ->count();
            
            $recipients = PushNotification::where('status', 'sent')
                ->whereDate('sent_at', $date->toDateString())
                ->sum('successful_sends');
            
            $data[] = [
                'date' => $date->locale('fr')->isoFormat('ddd DD/MM'),
                'sent' => $sent,
                'recipients' => $recipients
            ];
        }
        
        return $data;
    }
}
