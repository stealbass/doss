<?php

namespace App\Http\Controllers;

use App\Models\PushNotification;
use App\Models\User;
use App\Models\MobileAppPlan;
use App\Models\Utility;
use App\Services\PushNotificationService;
use App\Mail\SendPushNotificationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class PushNotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get the configured storage disk from database settings
     * Supports: local, S3, Wasabi, Cloudflare R2
     */
    private function getStorageDisk()
    {
        $settings = Utility::getStorageSetting();
        $storageSetting = $settings['storage_setting'] ?? 'local';
        
        if ($storageSetting === 'r2') {
            config([
                'filesystems.disks.r2.key' => $settings['r2_key'],
                'filesystems.disks.r2.secret' => $settings['r2_secret'],
                'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
                'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
                'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
                'filesystems.disks.r2.url' => $settings['r2_url'],
                'filesystems.disks.r2.use_path_style_endpoint' => false,
            ]);
            return 'r2';
        } elseif ($storageSetting === 's3') {
            config([
                'filesystems.disks.s3.key' => $settings['s3_key'],
                'filesystems.disks.s3.secret' => $settings['s3_secret'],
                'filesystems.disks.s3.region' => $settings['s3_region'],
                'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
            ]);
            return 's3';
        } elseif ($storageSetting === 'wasabi') {
            config([
                'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com',
            ]);
            return 'wasabi';
        }
        
        return 'public';
    }

    /**
     * Get upload limit from database settings (in KB)
     * Default: 20MB (configurable in admin panel)
     */
    private function getUploadLimit()
    {
        $settings = Utility::getStorageSetting();
        $storageSetting = $settings['storage_setting'] ?? 'local';
        $maxSize = 20480; // 20MB default
        
        if ($storageSetting === 'r2') {
            $maxSize = !empty($settings['r2_max_upload_size']) ? (int)$settings['r2_max_upload_size'] : 20480;
        } elseif ($storageSetting === 's3') {
            $maxSize = !empty($settings['s3_max_upload_size']) ? (int)$settings['s3_max_upload_size'] : 20480;
        } elseif ($storageSetting === 'wasabi') {
            $maxSize = !empty($settings['wasabi_max_upload_size']) ? (int)$settings['wasabi_max_upload_size'] : 20480;
        } else {
            $maxSize = !empty($settings['local_storage_max_upload_size']) ? (int)$settings['local_storage_max_upload_size'] : 20480;
        }
        
        return $maxSize;
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
        
        // Charger tous les utilisateurs mobiles (abonnés ou activité mobile)
        $users = User::where(function ($query) {
                $query->whereHas('mobileSubscriptions')
                    ->orWhereNotNull('last_mobile_activity_at')
                    ->orWhereNotNull('mobile_app_installed_at');
            })
            ->with(['activeMobileSubscription.plan'])
            ->orderBy('name', 'asc')
            ->get();
        
        $targetAudiences = [
            'all' => 'Tous les utilisateurs',
            'students' => 'Étudiants uniquement',
            'lawyers' => 'Avocats uniquement',
            'enterprises' => 'Entreprises uniquement',
            'plan_specific' => 'Plan spécifique',
            'specific_users' => 'Utilisateurs spécifiques',
        ];

        return view('push-notifications.create', compact('plans', 'targetAudiences', 'users'));
    }

    /**
     * Enregistre une nouvelle notification
     */
    public function store(Request $request)
    {
        // Réduire le HTML Summernote trop verbeux avant validation (styles/classes inutiles).
        $request->merge([
            'body' => $this->normalizeNotificationBody((string) $request->input('body', '')),
            'type' => strtolower((string) $request->input('type', 'general')),
        ]);

        // Récupérer les limites d'upload depuis la base de données
        $maxUploadSize = $this->getUploadLimit();
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            // Summernote stocke du HTML, ce qui peut dépasser 5000 caractères
            // même pour un texte visuel raisonnable.
            'body' => 'required|string|max:100000',
            'type' => 'required|in:general,promotion,alert,update',
            'target_audience' => 'required|in:all,students,lawyers,enterprises,plan_specific,specific_users',
            'target_plan' => 'required_if:target_audience,plan_specific|nullable|exists:mobile_app_plans,id',
            'specific_users' => 'required_if:target_audience,specific_users|nullable|array',
            'specific_users.*' => 'exists:users,id',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp|max:' . $maxUploadSize,
            'action_url' => 'nullable|string|max:500',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // Gérer l'upload d'image principale (tous les backends supportés)
        if ($request->hasFile('image')) {
            $tempRequest = new Request();
            $tempRequest->files->set('file', $request->file('image'));
            
            $filename = 'push_notification_' . time() . '_' . uniqid() . '.' . $request->file('image')->getClientOriginalExtension();
            
            // Utiliser le système d'upload centralisé
            $uploadResult = Utility::upload_file($tempRequest, 'file', $filename, 'push-notifications', [
                'mimes:png,jpg,jpeg,gif,webp',
                'max:' . $maxUploadSize,
            ]);
            
            if ($uploadResult['flag'] == 1) {
                // Récupérer l'URL publique du storage
                $disk = $this->getStorageDisk();
                $validated['image_url'] = Storage::disk($disk)->url($uploadResult['url']);
            } else {
                return redirect()->back()->withErrors(['image' => __($uploadResult['msg'])]);
            }
        }

        // Convertir les utilisateurs spécifiques en JSON
        if ($request->target_audience === 'specific_users' && $request->has('specific_users')) {
            $validated['specific_users'] = json_encode($request->specific_users);
        }

        $validated['created_by'] = Auth::id();
        $validated['status'] = $request->has('send_now') ? 'sending' : 
                              ($request->scheduled_at ? 'scheduled' : 'draft');

        $notification = PushNotification::create($validated);

        // Si envoi immédiat
        if ($request->has('send_now')) {
            $sendResult = $this->sendNotification($notification);

            if ($sendResult['ok']) {
                if (($sendResult['push_success'] ?? 0) === 0 && ($sendResult['email_success'] ?? 0) > 0) {
                    $warning = 'Email envoyé, mais aucun push n\'a été délivré.';
                    if (!empty($sendResult['message'])) {
                        $warning .= ' Détail: ' . $sendResult['message'];
                    }

                    return redirect()->route('push-notifications.index')->with('error', $warning);
                }

                $message = 'Notification envoyée.';

                if (($sendResult['push_success'] ?? 0) > 0) {
                    $message .= ' Push: ' . ($sendResult['push_success'] ?? 0) . ' succès';
                }
                if (($sendResult['push_failed'] ?? 0) > 0) {
                    $message .= ', ' . ($sendResult['push_failed'] ?? 0) . ' échec(s)';
                }
                if (($sendResult['email_success'] ?? 0) > 0) {
                    $message .= ' | Email: ' . ($sendResult['email_success'] ?? 0) . ' succès';
                }

                return redirect()->route('push-notifications.index')->with('success', $message);
            }

            return redirect()->route('push-notifications.index')
                ->with('error', $sendResult['message'] ?? 'Échec de l\'envoi de la notification.');
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
        
        // Charger tous les utilisateurs mobiles avec leurs abonnements
        $users = User::whereHas('mobileSubscriptions')
            ->with(['activeMobileSubscription.plan'])
            ->orderBy('name', 'asc')
            ->get();
        
        $targetAudiences = [
            'all' => 'Tous les utilisateurs',
            'students' => 'Étudiants uniquement',
            'lawyers' => 'Avocats uniquement',
            'enterprises' => 'Entreprises uniquement',
            'plan_specific' => 'Plan spécifique',
            'specific_users' => 'Utilisateurs spécifiques',
        ];

        return view('push-notifications.edit', compact('notification', 'plans', 'targetAudiences', 'users'));
    }

    /**
     * Met à jour une notification
     */
    public function update(Request $request, $id)
    {
        $notification = PushNotification::findOrFail($id);

        // Réduire le HTML Summernote trop verbeux avant validation (styles/classes inutiles).
        $request->merge([
            'body' => $this->normalizeNotificationBody((string) $request->input('body', '')),
            'type' => strtolower((string) $request->input('type', 'general')),
        ]);

        if (!in_array($notification->status, ['draft', 'scheduled'])) {
            return redirect()->route('push-notifications.index')
                ->with('error', 'Cette notification ne peut pas être modifiée.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            // Summernote stocke du HTML, ce qui peut dépasser 5000 caractères
            // même pour un texte visuel raisonnable.
            'body' => 'required|string|max:100000',
            'type' => 'required|in:general,promotion,alert,update',
            'target_audience' => 'required|in:all,students,lawyers,enterprises,plan_specific,specific_users',
            'target_plan' => 'required_if:target_audience,plan_specific|nullable|exists:mobile_app_plans,id',
            'specific_users' => 'required_if:target_audience,specific_users|nullable|array',
            'specific_users.*' => 'exists:users,id',
            'image_url' => 'nullable|url|max:500',
            'action_url' => 'nullable|string|max:500',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // Convertir les utilisateurs spécifiques en JSON
        if ($request->target_audience === 'specific_users' && $request->has('specific_users')) {
            $validated['specific_users'] = json_encode($request->specific_users);
        }

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

        $sendResult = $this->sendNotification($notification);

        if ($sendResult['ok']) {
            if (($sendResult['push_success'] ?? 0) === 0 && ($sendResult['email_success'] ?? 0) > 0) {
                $warning = 'Email envoyé, mais aucun push n\'a été délivré.';
                if (!empty($sendResult['message'])) {
                    $warning .= ' Détail: ' . $sendResult['message'];
                }

                return redirect()->route('push-notifications.index')->with('error', $warning);
            }

            $message = 'Notification envoyée.';

            if (($sendResult['push_success'] ?? 0) > 0) {
                $message .= ' Push: ' . ($sendResult['push_success'] ?? 0) . ' succès';
            }
            if (($sendResult['push_failed'] ?? 0) > 0) {
                $message .= ', ' . ($sendResult['push_failed'] ?? 0) . ' échec(s)';
            }
            if (($sendResult['email_success'] ?? 0) > 0) {
                $message .= ' | Email: ' . ($sendResult['email_success'] ?? 0) . ' succès';
            }

            return redirect()->route('push-notifications.index')->with('success', $message);
        }

        return redirect()->route('push-notifications.index')
            ->with('error', $sendResult['message'] ?? 'Échec de l\'envoi de la notification.');
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

            if ($totalRecipients === 0) {
                $notification->update([
                    'status' => 'failed',
                    'total_recipients' => 0,
                ]);
                return [
                    'ok' => false,
                    'message' => 'Aucun destinataire trouvé pour cette notification.',
                    'push_success' => 0,
                    'push_failed' => 0,
                    'email_success' => 0,
                    'email_failed' => 0,
                ];
            }

            $notification->update([
                'total_recipients' => $totalRecipients,
            ]);

            // Utiliser le service PushNotificationService pour envoyer réellement
            $pushService = new PushNotificationService();
            
            $data = [];
            if ($notification->action_url) {
                $data['action_url'] = $notification->action_url;
            }
            if ($notification->type) {
                $data['type'] = $notification->type;
                $data['notification_id'] = $notification->id;
            }

            $htmlBody = (string) $notification->body;
            // FCM (surtout HTTP v1 Android) impose une taille stricte de message.
            // On garde un texte court pour la notification push.
            $pushBody = $this->htmlToPushText($htmlBody, 260);

            // Données minimales pour éviter l'erreur "Android message is too big".
            $data['plain_body'] = mb_substr($pushBody, 0, 260);

            // Envoyer les notifications par FCM
            $result = $pushService->sendToUsers(
                $recipients->toArray(),
                $notification->title,
                $pushBody,
                $data
            );

            // Compter les envois réussis et échoués par push/email
            $pushSuccessful = (int) ($result['success_count'] ?? 0);
            $pushFailed = (int) ($result['failed_count'] ?? max(0, $totalRecipients - $pushSuccessful));
            $emailSuccessful = 0;
            $emailFailed = 0;

            // Envoyer aussi par EMAIL pour chaque destinataire
            try {
                // Configurer les paramètres SMTP depuis la base de données
                Utility::getSMTPDetails(Auth::user()->created_by);
                
                foreach ($recipients as $user) {
                    try {
                        if ($user->email) {
                            // Envoyer l'email via la classe Mailable
                            Mail::to($user->email)->send(new SendPushNotificationEmail($notification, $user));
                            $emailSuccessful++;
                            
                            \Log::info('Email notification envoyé avec succès', [
                                'notification_id' => $notification->id,
                                'user_id' => $user->id,
                                'email' => $user->email
                            ]);
                        }
                    } catch (\Exception $e) {
                        $emailFailed++;
                        \Log::warning('Erreur envoi email notification', [
                            'notification_id' => $notification->id,
                            'user_id' => $user->id,
                            'email' => $user->email ?? 'N/A',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Erreur configuration SMTP pour notifications: ' . $e->getMessage());
            }

            if ($pushSuccessful > 0 || $emailSuccessful > 0) {
                // Mise à jour des statistiques avec résultats réels
                $notification->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'successful_sends' => $pushSuccessful + $emailSuccessful,
                    'failed_sends' => $pushFailed + $emailFailed,
                ]);
                
                \Log::info('Notification envoyée avec succès', [
                    'notification_id' => $notification->id,
                    'fcm_success' => $pushSuccessful > 0,
                    'fcm_success_count' => $pushSuccessful,
                    'fcm_failed_count' => $pushFailed,
                    'email_sent' => $emailSuccessful,
                    'email_failed' => $emailFailed,
                    'type' => $notification->type,
                ]);
                
                return [
                    'ok' => true,
                    'message' => $pushSuccessful > 0
                        ? 'Notification envoyée.'
                        : ($result['message'] ?? 'Aucune notification push délivrée.'),
                    'push_success' => $pushSuccessful,
                    'push_failed' => $pushFailed,
                    'email_success' => $emailSuccessful,
                    'email_failed' => $emailFailed,
                ];
            } else {
                // Envoi échoué
                $notification->update([
                    'status' => 'failed',
                    'failed_sends' => $totalRecipients,
                ]);
                \Log::error('Erreur envoi notification FCM et Email: ' . ($result['message'] ?? 'Erreur inconnue'));
                return [
                    'ok' => false,
                    'message' => $result['message'] ?? 'Échec d\'envoi push et email.',
                    'push_success' => $pushSuccessful,
                    'push_failed' => $pushFailed,
                    'email_success' => $emailSuccessful,
                    'email_failed' => $emailFailed,
                ];
            }

        } catch (\Exception $e) {
            $notification->update([
                'status' => 'failed',
            ]);

            \Log::error('Erreur envoi notification push: ' . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Erreur technique lors de l\'envoi: ' . $e->getMessage(),
                'push_success' => 0,
                'push_failed' => 0,
                'email_success' => 0,
                'email_failed' => 0,
            ];
        }
    }

    /**
     * Récupère les destinataires selon le ciblage
     */
    private function getRecipients(PushNotification $notification)
    {
        // Si utilisateurs spécifiques sélectionnés
        if ($notification->target_audience === 'specific_users' && $notification->specific_users) {
            // Décoder le JSON si c'est une string
            $userIds = is_string($notification->specific_users) 
                ? json_decode($notification->specific_users, true) 
                : $notification->specific_users;
            
            if (!is_array($userIds) || empty($userIds)) {
                return collect();
            }
            
            return User::whereIn('id', $userIds)->get();
        }

        $query = User::where(function ($query) {
            $query->whereHas('mobileSubscriptions')
                ->orWhereNotNull('last_mobile_activity_at')
                ->orWhereNotNull('mobile_app_installed_at');
        });

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
                        $q->where('mobile_app_plan_id', $notification->target_plan);
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
                        $q->where('mobile_app_plan_id', $planId);
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

    /**
     * Upload d'image pour Summernote (images inline dans le contenu)
     * Support multi-storage: local, S3, Wasabi, Cloudflare R2
     * Limite: 20MB par défaut (configurable en base de données)
     */
    public function uploadImage(Request $request)
    {
        try {
            // Récupérer les limites depuis la base de données
            $maxSize = $this->getUploadLimit();
            $settings = Utility::getStorageSetting();
            $storageSetting = $settings['storage_setting'] ?? 'local';
            
            // Récupérer les extensions autorisées selon le storage
            $settingKey = $storageSetting . '_storage_validation';
            $allowedMimes = !empty($settings[$settingKey]) 
                ? $settings[$settingKey]
                : 'png,jpg,jpeg,gif,webp';
            
            // Valider l'image avec les limites du système
            $validator = Validator::make($request->all(), [
                'image' => [
                    'required',
                    'image',
                    'mimes:' . $allowedMimes,
                    'max:' . $maxSize,
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first('image'),
                ], 422);
            }

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = 'inline_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Uploader vers le storage configuré (local, S3, Wasabi, ou R2)
                $disk = $this->getStorageDisk();
                $path = Storage::disk($disk)->putFileAs(
                    'push-notifications/inline',
                    $image,
                    $filename
                );
                
                // Générer l'URL publique
                $url = Storage::disk($disk)->url($path);

                return response()->json([
                    'success' => true,
                    'url' => $url,
                    'message' => 'Image uploadée avec succès'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Aucune image reçue'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Nettoie le HTML de Summernote pour réduire sa taille sans perdre le contenu utile.
     */
    private function normalizeNotificationBody(string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            return $body;
        }

        // Supprimer scripts/styles dangereux ou inutiles.
        $body = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $body) ?? $body;

        // Supprimer commentaires HTML.
        $body = preg_replace('/<!--.*?-->/s', '', $body) ?? $body;

        // Conserver les styles inline pour le rendu email.
        // Retirer seulement les attributs data/aria générés par l'éditeur si présents.
        $body = preg_replace('/\s(?:data-[\w-]+|aria-[\w-]+)="[^"]*"/i', '', $body) ?? $body;
        $body = preg_replace('/\s(?:data-[\w-]+|aria-[\w-]+)=\'[^\']*\'/i', '', $body) ?? $body;

        // Nettoyer les espaces redondants entre balises.
        $body = preg_replace('/>\s+</', '><', $body) ?? $body;

        return trim($body);
    }

    /**
     * Convertit un HTML Summernote en texte lisible pour la notification push.
     */
    private function htmlToPushText(string $html, int $maxLength = 1000): string
    {
        $text = trim($html);

        if ($text === '') {
            return $text;
        }

        // Préserver une structure lisible avant suppression des balises.
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*\/\s*(p|div|h[1-6]|li)\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*li\b[^>]*>/i', "- ", $text) ?? $text;

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Nettoyer les espaces tout en conservant les retours ligne utiles.
        $text = preg_replace('/\r\n?|\n/u', "\n", $text) ?? $text;
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        return mb_substr($text, 0, $maxLength);
    }
}