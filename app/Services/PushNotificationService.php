<?php

namespace App\Services;

use App\Models\User;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'envoi de notifications push via Firebase Cloud Messaging
 * 
 * Fonctionnalités :
 * - Envoi de notifications push à un utilisateur
 * - Envoi de notifications push à plusieurs utilisateurs
 * - Envoi de notifications push par topic
 * - Support données personnalisées (navigation)
 */
class PushNotificationService
{
    protected $fcmServerKey;
    protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->fcmServerKey = env('FCM_SERVER_KEY');
    }

    /**
     * Envoyer une notification push à un utilisateur
     * 
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [])
    {
        // Récupérer tous les tokens FCM de l'utilisateur
        $fcmTokens = FcmToken::where('user_id', $user->id)->pluck('token')->toArray();

        if (empty($fcmTokens)) {
            Log::warning('No FCM tokens found for user', ['user_id' => $user->id]);
            return [
                'success' => false,
                'message' => 'Aucun token FCM trouvé pour cet utilisateur',
            ];
        }

        return $this->sendToTokens($fcmTokens, $title, $body, $data);
    }

    /**
     * Envoyer une notification push à plusieurs utilisateurs
     * 
     * @param array $users
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToUsers(array $users, string $title, string $body, array $data = [])
    {
        $userIds = collect($users)->pluck('id')->toArray();
        
        // Récupérer tous les tokens FCM des utilisateurs
        $fcmTokens = FcmToken::whereIn('user_id', $userIds)->pluck('token')->toArray();

        if (empty($fcmTokens)) {
            Log::warning('No FCM tokens found for users', ['user_ids' => $userIds]);
            return [
                'success' => false,
                'message' => 'Aucun token FCM trouvé pour ces utilisateurs',
            ];
        }

        return $this->sendToTokens($fcmTokens, $title, $body, $data);
    }

    /**
     * Envoyer une notification push à des tokens spécifiques
     * 
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [])
    {
        if (empty($this->fcmServerKey)) {
            Log::error('FCM_SERVER_KEY not configured');
            return [
                'success' => false,
                'message' => 'FCM_SERVER_KEY non configuré',
            ];
        }

        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => '1',
        ];

        $payload = [
            'registration_ids' => $tokens,
            'notification' => $notification,
            'data' => $data,
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('Push notification sent successfully', [
                    'success' => $result['success'] ?? 0,
                    'failure' => $result['failure'] ?? 0,
                    'title' => $title,
                ]);

                // Supprimer les tokens invalides
                if (!empty($result['results'])) {
                    $this->removeInvalidTokens($tokens, $result['results']);
                }

                return [
                    'success' => true,
                    'message' => 'Notification envoyée avec succès',
                    'result' => $result,
                ];
            } else {
                Log::error('Failed to send push notification', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Échec de l\'envoi de la notification',
                    'error' => $response->body(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending push notification', [
                'error' => $e->getMessage(),
                'title' => $title,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer une notification push par topic
     * 
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [])
    {
        if (empty($this->fcmServerKey)) {
            Log::error('FCM_SERVER_KEY not configured');
            return [
                'success' => false,
                'message' => 'FCM_SERVER_KEY non configuré',
            ];
        }

        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => '1',
        ];

        $payload = [
            'to' => '/topics/' . $topic,
            'notification' => $notification,
            'data' => $data,
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('Push notification sent to topic successfully', [
                    'topic' => $topic,
                    'message_id' => $result['message_id'] ?? null,
                    'title' => $title,
                ]);

                return [
                    'success' => true,
                    'message' => 'Notification envoyée au topic avec succès',
                    'result' => $result,
                ];
            } else {
                Log::error('Failed to send push notification to topic', [
                    'topic' => $topic,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Échec de l\'envoi de la notification au topic',
                    'error' => $response->body(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending push notification to topic', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'title' => $title,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification au topic',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer une notification pour une nouvelle audience
     * 
     * @param \App\Models\Hearing $hearing
     * @param \App\Models\Cases $case
     * @param array $users
     * @return array
     */
    public function sendHearingCreatedNotification($hearing, $case, array $users)
    {
        $title = "Nouvelle Audience - " . $case->title;
        $body = "Une audience a été créée pour le " . \Carbon\Carbon::parse($hearing->date)->format('d/m/Y');
        
        $data = [
            'type' => 'hearing',
            'hearing_id' => (string)$hearing->id,
            'case_id' => (string)$case->id,
            'action' => 'view_hearing',
        ];

        return $this->sendToUsers($users, $title, $body, $data);
    }

    /**
     * Envoyer une notification de rappel d'audience
     * 
     * @param \App\Models\Hearing $hearing
     * @param \App\Models\Cases $case
     * @param array $users
     * @param int $daysRemaining
     * @return array
     */
    public function sendHearingReminderNotification($hearing, $case, array $users, int $daysRemaining)
    {
        $title = "⏰ Rappel Audience - " . $case->title;
        $body = "Votre audience est dans $daysRemaining jour(s) : " . \Carbon\Carbon::parse($hearing->date)->format('d/m/Y à H:i');
        
        $data = [
            'type' => 'hearing',
            'hearing_id' => (string)$hearing->id,
            'case_id' => (string)$case->id,
            'action' => 'view_hearing',
            'days_remaining' => (string)$daysRemaining,
        ];

        return $this->sendToUsers($users, $title, $body, $data);
    }

    /**
     * Envoyer une notification pour une nouvelle tâche
     * 
     * @param \App\Models\ToDo $task
     * @param array $users
     * @return array
     */
    public function sendTaskCreatedNotification($task, array $users)
    {
        $title = "Nouvelle Tâche - " . $task->title;
        $body = "Échéance : " . \Carbon\Carbon::parse($task->due_date)->format('d/m/Y');
        
        $data = [
            'type' => 'task',
            'task_id' => (string)$task->id,
            'action' => 'view_task',
        ];

        return $this->sendToUsers($users, $title, $body, $data);
    }

    /**
     * Envoyer une notification de rappel de tâche
     * 
     * @param \App\Models\ToDo $task
     * @param array $users
     * @param int $daysRemaining
     * @return array
     */
    public function sendTaskReminderNotification($task, array $users, int $daysRemaining)
    {
        $title = "⏰ Rappel Tâche - " . $task->title;
        $body = "À terminer dans $daysRemaining jour(s) : " . \Carbon\Carbon::parse($task->due_date)->format('d/m/Y');
        
        $data = [
            'type' => 'task',
            'task_id' => (string)$task->id,
            'action' => 'view_task',
            'days_remaining' => (string)$daysRemaining,
        ];

        return $this->sendToUsers($users, $title, $body, $data);
    }

    /**
     * Supprimer les tokens FCM invalides de la base de données
     * 
     * @param array $tokens
     * @param array $results
     * @return void
     */
    protected function removeInvalidTokens(array $tokens, array $results)
    {
        $invalidTokens = [];

        foreach ($results as $index => $result) {
            if (isset($result['error']) && in_array($result['error'], ['InvalidRegistration', 'NotRegistered'])) {
                $invalidTokens[] = $tokens[$index];
            }
        }

        if (!empty($invalidTokens)) {
            FcmToken::whereIn('token', $invalidTokens)->delete();
            Log::info('Removed invalid FCM tokens', ['count' => count($invalidTokens)]);
        }
    }
    
    /**
     * Envoyer une notification de création d'affaire
     * 
     * @param \App\Models\Cases $case
     * @param array $users
     * @return array
     */
    public function sendCaseCreatedNotification($case, array $users)
    {
        $title = "📂 Nouvelle Affaire - " . $case->title;
        $body = "Date de dépôt : " . \Carbon\Carbon::parse($case->filing_date)->format('d/m/Y');
        
        $data = [
            'type' => 'case',
            'case_id' => (string)$case->id,
            'action' => 'view_case',
        ];

        return $this->sendToUsers($users, $title, $body, $data);
    }}