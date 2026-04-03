<?php

namespace App\Services;

use App\Models\User;
use App\Models\FcmToken;
use App\Models\MobileAppSetting;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
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
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    protected $fcmServerKey;
    protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    protected $serviceAccount = null;
    protected $firebaseProjectId = null;
    protected $fcmV1Url = null;
    protected $useHttpV1 = false;

    public function __construct()
    {
        // Legacy key: conservée comme fallback uniquement.
        $settingsKey = null;
        $mobileSettings = null;

        try {
            $mobileSettings = MobileAppSetting::first();
            $settingsKey = $mobileSettings?->firebase_server_key;
        } catch (\Throwable $e) {
            // Ne pas bloquer le service si la table/settings n'est pas disponible.
            $settingsKey = null;
            $mobileSettings = null;
        }

        $resolvedKey = is_string($settingsKey) && trim($settingsKey) !== ''
            ? trim($settingsKey)
            : trim((string) env('FCM_SERVER_KEY', ''));

        if (preg_match('/^[a-f0-9]{30,}$/i', $resolvedKey) === 1) {
            Log::warning('firebase_server_key appears to be a key ID, not a valid FCM legacy server key');
        }

        // Éviter l'utilisation d'un placeholder de type "...".
        $this->fcmServerKey = $resolvedKey !== '...' ? $resolvedKey : '';

        // FCM HTTP v1 (recommandé et requis quand legacy API est désactivée).
        $this->serviceAccount = $this->resolveServiceAccountCredentials($mobileSettings);
        $this->firebaseProjectId = $this->resolveFirebaseProjectId($mobileSettings, $this->serviceAccount);
        $this->useHttpV1 = is_array($this->serviceAccount) && !empty($this->firebaseProjectId);

        if ($this->useHttpV1) {
            $this->fcmV1Url = 'https://fcm.googleapis.com/v1/projects/' . $this->firebaseProjectId . '/messages:send';
            Log::info('FCM configured with HTTP v1', [
                'project_id' => $this->firebaseProjectId,
            ]);
        } else {
            Log::warning('FCM HTTP v1 not configured, using legacy fallback if available');
        }
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
        $dbTokens = FcmToken::whereIn('user_id', $userIds)->pluck('token')->toArray();
        $fcmTokens = $dbTokens;

        Log::info('Push token resolution started', [
            'user_ids' => $userIds,
            'db_tokens_count' => count($dbTokens),
        ]);

        // Fallback legacy: certains environnements stockent encore le token
        // principal directement dans users.fcm_token.
        if (empty($fcmTokens)) {
            $legacyTokens = User::whereIn('id', $userIds)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->filter(fn ($token) => is_string($token) && trim($token) !== '')
                ->map(fn ($token) => trim((string) $token))
                ->values()
                ->toArray();

            Log::info('Push token fallback users.fcm_token checked', [
                'user_ids' => $userIds,
                'legacy_tokens_count' => count($legacyTokens),
            ]);

            if (!empty($legacyTokens)) {
                $fcmTokens = $legacyTokens;

                Log::info('Using fallback users.fcm_token for push send', [
                    'user_ids' => $userIds,
                    'token_count' => count($legacyTokens),
                ]);
            }
        }

        // Dédupliquer pour éviter des envois multiples inutiles.
        $fcmTokens = array_values(array_unique($fcmTokens));

        if (!empty($fcmTokens)) {
            Log::info('Push token resolution succeeded', [
                'user_ids' => $userIds,
                'usable_tokens_count' => count($fcmTokens),
            ]);
        }

        if (empty($fcmTokens)) {
            Log::warning('No FCM tokens found for users; trying topic fallback', ['user_ids' => $userIds]);

            $topicSuccess = 0;
            $topicFailed = 0;

            foreach ($userIds as $userId) {
                $topic = 'user_' . $userId;
                $topicPayload = array_merge($data, [
                    'target_user_id' => (string) $userId,
                ]);

                $topicResult = $this->sendToTopic($topic, $title, $body, $topicPayload);

                if (!empty($topicResult['success'])) {
                    $topicSuccess++;
                } else {
                    $topicFailed++;
                }
            }

            return [
                // Sans token device connu, un ACK topic n'est pas une preuve de livraison réelle.
                'success' => false,
                'success_count' => $topicSuccess,
                'failed_count' => $topicFailed,
                'message' => $topicSuccess > 0
                    ? 'Aucun token FCM device trouvé: envoi topic accepté (livraison non garantie). Ouvrir l\'app puis se reconnecter pour enregistrer le token.'
                    : 'Aucun token FCM trouvé et fallback topic en échec.',
                'delivery_guaranteed' => false,
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
        $tokens = array_values(array_unique(array_filter($tokens, fn ($token) => is_string($token) && trim($token) !== '')));

        if (empty($tokens)) {
            return [
                'success' => false,
                'success_count' => 0,
                'failed_count' => 0,
                'message' => 'Aucun token FCM valide à envoyer.',
            ];
        }

        // Chemin principal: FCM HTTP v1 (OAuth service account).
        if ($this->useHttpV1 && !empty($this->fcmV1Url)) {
            return $this->sendToTokensViaV1($tokens, $title, $body, $data);
        }

        // Fallback legacy: uniquement si clé server présente.
        if (empty($this->fcmServerKey)) {
            Log::error('FCM not configured: neither HTTP v1 nor legacy key available');
            return [
                'success' => false,
                'success_count' => 0,
                'failed_count' => count($tokens),
                'message' => 'Configuration Firebase incomplète (HTTP v1 recommandé).',
            ];
        }

        return $this->sendToTokensLegacy($tokens, $title, $body, $data);
    }

    private function sendToTokensLegacy(array $tokens, string $title, string $body, array $data = [])
    {
        $data = $this->sanitizeDataPayload($data);

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
                $successCount = (int) ($result['success'] ?? 0);
                $failedCount = (int) ($result['failure'] ?? 0);
                $firstError = null;

                if (!empty($result['results']) && is_array($result['results'])) {
                    foreach ($result['results'] as $item) {
                        if (is_array($item) && !empty($item['error'])) {
                            $firstError = (string) $item['error'];
                            break;
                        }
                    }
                }
                
                Log::info('Push notification sent successfully', [
                    'success' => $successCount,
                    'failure' => $failedCount,
                    'title' => $title,
                    'first_error' => $firstError,
                ]);

                // Supprimer les tokens invalides
                if (!empty($result['results'])) {
                    $this->removeInvalidTokens($tokens, $result['results']);
                }

                return [
                    'success' => $successCount > 0,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'message' => $successCount > 0
                        ? 'Notification push envoyée avec succès'
                        : ('Aucune notification push délivrée' . ($firstError ? ' (Firebase: ' . $firstError . ')' : ' (vérifier tokens/clé Firebase).')),
                    'result' => $result,
                ];
            } else {
                Log::error('Failed to send push notification', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'success_count' => 0,
                    'failed_count' => count($tokens),
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
                'success_count' => 0,
                'failed_count' => count($tokens),
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
        $topic = trim($topic);

        if ($topic === '') {
            return [
                'success' => false,
                'message' => 'Topic vide',
            ];
        }

        if ($this->useHttpV1 && !empty($this->fcmV1Url)) {
            return $this->sendToTopicViaV1($topic, $title, $body, $data);
        }

        if (empty($this->fcmServerKey)) {
            Log::error('FCM not configured: neither HTTP v1 nor legacy key available');
            return [
                'success' => false,
                'message' => 'Configuration Firebase incomplète (HTTP v1 recommandé).',
            ];
        }

        return $this->sendToTopicLegacy($topic, $title, $body, $data);
    }

    private function sendToTopicLegacy(string $topic, string $title, string $body, array $data = [])
    {
        $data = $this->sanitizeDataPayload($data);

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

    private function sendToTokensViaV1(array $tokens, string $title, string $body, array $data = [])
    {
        $successCount = 0;
        $failedCount = 0;
        $firstError = null;

        foreach ($tokens as $token) {
            $result = $this->sendV1Message([
                'token' => $token,
            ], $title, $body, $data);

            if (!empty($result['success'])) {
                $successCount++;
            } else {
                $failedCount++;
                if ($firstError === null) {
                    $firstError = $result['error'] ?? $result['message'] ?? 'Erreur inconnue';
                }

                // Nettoyage des tokens manifestement invalides.
                $errorText = strtolower((string) ($result['error'] ?? ''));
                if (str_contains($errorText, 'unregistered') || str_contains($errorText, 'invalid argument')) {
                    FcmToken::where('token', $token)->delete();
                }
            }
        }

        return [
            'success' => $successCount > 0,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'message' => $successCount > 0
                ? 'Notification push envoyée via FCM v1'
                : ('Aucune notification push délivrée via FCM v1' . ($firstError ? ' (' . $firstError . ')' : '.')),
        ];
    }

    private function sendToTopicViaV1(string $topic, string $title, string $body, array $data = [])
    {
        $result = $this->sendV1Message([
            'topic' => $topic,
        ], $title, $body, $data);

        if (!empty($result['success'])) {
            return [
                'success' => true,
                'message' => 'Notification envoyée au topic via FCM v1',
            ];
        }

        return [
            'success' => false,
            'message' => 'Échec envoi topic via FCM v1',
            'error' => $result['error'] ?? null,
        ];
    }

    private function sendV1Message(array $target, string $title, string $body, array $data = [])
    {
        try {
            $accessToken = $this->fetchGoogleAccessToken();
            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => 'Impossible d\'obtenir un access token Google',
                ];
            }

            $stringData = $this->sanitizeDataPayload($data);

            $message = [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'dossy_pro_channel',
                        'sound' => 'default',
                        'color' => '#00A86B',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ];

            foreach ($target as $k => $v) {
                $message[$k] = $v;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->fcmV1Url, [
                'message' => $message,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                ];
            }

            $bodyPayload = $response->json();
            $errorMessage = $bodyPayload['error']['message'] ?? $response->body();

            Log::error('FCM v1 send failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'target' => $target,
            ]);

            return [
                'success' => false,
                'error' => (string) $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('FCM v1 exception', [
                'error' => $e->getMessage(),
                'target' => $target,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchGoogleAccessToken(): ?string
    {
        if (!is_array($this->serviceAccount)) {
            return null;
        }

        $credentials = new ServiceAccountCredentials(self::FCM_SCOPE, $this->serviceAccount);
        $token = $credentials->fetchAuthToken(HttpHandlerFactory::build());

        return $token['access_token'] ?? null;
    }

    private function resolveServiceAccountCredentials(?MobileAppSetting $mobileSettings): ?array
    {
        $envRawJson = trim((string) env('FIREBASE_SERVICE_ACCOUNT_JSON', ''));
        if ($envRawJson !== '') {
            $decoded = json_decode($envRawJson, true);
            if (is_array($decoded) && !empty($decoded['client_email']) && !empty($decoded['private_key'])) {
                return $decoded;
            }
        }

        $settingsRaw = trim((string) ($mobileSettings?->firebase_server_key ?? ''));
        if ($settingsRaw !== '' && str_starts_with($settingsRaw, '{')) {
            $decoded = json_decode($settingsRaw, true);
            if (is_array($decoded) && !empty($decoded['client_email']) && !empty($decoded['private_key'])) {
                return $decoded;
            }
        }

        $candidatePaths = array_filter([
            env('FIREBASE_SERVICE_ACCOUNT_PATH'),
            storage_path('app/firebase/service-account-credentials.json'),
            storage_path('app/google-calendar/service-account-credentials.json'),
        ]);

        $firebaseDir = storage_path('app/firebase');
        if (is_dir($firebaseDir)) {
            foreach (glob($firebaseDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $jsonFile) {
                $candidatePaths[] = $jsonFile;
            }
        }

        foreach ($candidatePaths as $path) {
            if (!is_string($path) || !is_file($path)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && !empty($decoded['client_email']) && !empty($decoded['private_key'])) {
                return $decoded;
            }
        }

        return null;
    }

    private function resolveFirebaseProjectId(?MobileAppSetting $mobileSettings, ?array $serviceAccount): ?string
    {
        $envProjectId = trim((string) env('FIREBASE_PROJECT_ID', ''));
        if ($envProjectId !== '') {
            return $envProjectId;
        }

        if (is_array($serviceAccount) && !empty($serviceAccount['project_id'])) {
            return (string) $serviceAccount['project_id'];
        }

        $settingsProject = trim((string) ($mobileSettings?->firebase_messaging_sender_id ?? ''));
        // Sender ID est numérique, pas un project_id: on ne l'utilise pas ici.
        if ($settingsProject !== '' && !ctype_digit($settingsProject)) {
            return $settingsProject;
        }

        // Fallback: lire project_id depuis le google-services.json mobile.
        $googleServicesPath = base_path('dossy_chat_ia/android/app/google-services.json');
        if (is_file($googleServicesPath)) {
            $decoded = json_decode((string) file_get_contents($googleServicesPath), true);
            $projectId = is_array($decoded) ? trim((string) ($decoded['project_info']['project_id'] ?? '')) : '';
            if ($projectId !== '') {
                return $projectId;
            }
        }

        return null;
    }

    /**
     * Keep FCM data payload under safe limits to avoid Android "message too big" errors.
     */
    private function sanitizeDataPayload(array $data, int $maxPayloadBytes = 2800, int $maxValueLen = 300): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $k = (string) $key;
            if ($k === '') {
                continue;
            }

            $v = is_scalar($value)
                ? (string) $value
                : (string) json_encode($value, JSON_UNESCAPED_UNICODE);

            $v = trim($v);
            if ($v === '') {
                continue;
            }

            if (mb_strlen($v) > $maxValueLen) {
                $v = mb_substr($v, 0, $maxValueLen);
            }

            $normalized[$k] = $v;
        }

        $size = strlen((string) json_encode($normalized, JSON_UNESCAPED_UNICODE));
        if ($size <= $maxPayloadBytes) {
            return $normalized;
        }

        // Drop lowest-priority keys first when payload is too large.
        // Keep notification_id so mobile app can fetch full message content from API.
        $dropOrder = ['html_body', 'plain_body', 'action_url', 'type'];
        foreach ($dropOrder as $dropKey) {
            if (!array_key_exists($dropKey, $normalized)) {
                continue;
            }

            unset($normalized[$dropKey]);
            $size = strlen((string) json_encode($normalized, JSON_UNESCAPED_UNICODE));
            if ($size <= $maxPayloadBytes) {
                break;
            }
        }

        return $normalized;
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
    }
}