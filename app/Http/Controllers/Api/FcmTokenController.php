<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Contrôleur de gestion des tokens FCM (Firebase Cloud Messaging)
 * 
 * Endpoints :
 * - POST /api/mobile/fcm-token : Enregistrer/mettre à jour un token FCM
 * - DELETE /api/mobile/fcm-token : Supprimer un token FCM
 * - GET /api/mobile/fcm-token : Obtenir le token FCM actuel
 */
class FcmTokenController extends Controller
{
    /**
     * Endpoint de secours pour synchroniser le token FCM.
     *
     * Accepte un bearer token via header Authorization ou champ auth_token.
     * Utile si certains proxys/serveurs filtrent le header Authorization.
     */
    public function sync(Request $request)
    {
        [$user, $authContext] = $this->resolveUserFromRequest($request);

        $request = $this->normalizeTokenRequest($request);

        if (!$user) {
            \Log::warning('FCM sync unauthorized', [
                'has_authorization_header' => $request->header('Authorization') ? true : false,
                'has_auth_token_field' => $request->filled('auth_token'),
                'has_fcm_token' => $request->filled('fcm_token'),
                'token_length' => strlen((string) $request->input('fcm_token', '')),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié pour la synchronisation FCM',
            ], 401);
        }

        return $this->saveTokenForUser($request, $user, $authContext);
    }

    /**
     * Enregistrer ou mettre à jour le token FCM de l'utilisateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request = $this->normalizeTokenRequest($request);

        $user = auth()->user();
        if (!$user) {
            \Log::warning('FCM token store reached without authenticated user', [
                'has_authorization_header' => $request->header('Authorization') ? true : false,
                'has_auth_token_field' => $request->filled('auth_token'),
                'has_fcm_token' => $request->filled('fcm_token'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        return $this->saveTokenForUser($request, $user, 'sanctum_guard');
    }

    private function normalizeTokenRequest(Request $request): Request
    {
        $rawToken = (string) ($request->input('fcm_token') ?? $request->input('token') ?? '');
        $platform = strtolower((string) $request->input('platform', ''));

        // Fallback plateforme depuis User-Agent si non fournie.
        if ($platform === '') {
            $ua = strtolower((string) $request->userAgent());
            if (str_contains($ua, 'android')) {
                $platform = 'android';
            } elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ios')) {
                $platform = 'ios';
            }
        }

        if (!in_array($platform, ['android', 'ios'], true)) {
            $platform = 'android';
        }

        $request->merge([
            'fcm_token' => trim($rawToken),
            'platform' => $platform,
        ]);

        return $request;
    }

    private function resolveUserFromRequest(Request $request): array
    {
        $authUser = auth()->user();
        if ($authUser) {
            return [$authUser, 'sanctum_guard'];
        }

        $rawAuth = (string) ($request->header('Authorization') ?? '');
        if ($rawAuth === '') {
            $rawAuth = (string) ($request->input('auth_token') ?? $request->input('api_token') ?? '');
        }

        $rawAuth = trim($rawAuth);
        if ($rawAuth !== '' && str_starts_with(strtolower($rawAuth), 'bearer ')) {
            $rawAuth = trim(substr($rawAuth, 7));
        }

        if ($rawAuth === '') {
            return [null, 'missing_token'];
        }

        try {
            $accessToken = PersonalAccessToken::findToken($rawAuth);
            $tokenable = $accessToken?->tokenable;
            if ($tokenable instanceof User) {
                return [$tokenable, 'manual_token_lookup'];
            }

            return [null, 'invalid_token'];
        } catch (\Throwable $e) {
            \Log::warning('FCM sync token lookup failed', [
                'error' => $e->getMessage(),
            ]);
            return [null, 'lookup_exception'];
        }
    }

    private function saveTokenForUser(Request $request, User $user, string $authContext)
    {

        \Log::info('FCM token store request received', [
            'user_id' => $user->id,
            'has_fcm_token' => $request->filled('fcm_token'),
            'token_length' => strlen((string) $request->input('fcm_token', '')),
            'platform' => $request->input('platform'),
            'has_authorization_header' => $request->header('Authorization') ? true : false,
            'auth_context' => $authContext,
        ]);

        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:500',
            'platform' => 'required|string|in:android,ios',
        ]);

        if ($validator->fails()) {
            \Log::warning('FCM token validation failed', [
                'user_id' => $user->id,
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Vérifier si le token existe déjà pour cet utilisateur
            $fcmToken = FcmToken::where('user_id', $user->id)
                ->where('platform', $request->platform)
                ->first();

            if ($fcmToken) {
                // Mettre à jour le token existant
                $fcmToken->update([
                    'token' => $request->fcm_token,
                    'updated_at' => now(),
                ]);
                
                $message = 'Token FCM mis à jour avec succès';
            } else {
                // Créer un nouveau token
                $fcmToken = FcmToken::create([
                    'user_id' => $user->id,
                    'token' => $request->fcm_token,
                    'platform' => $request->platform,
                ]);
                
                $message = 'Token FCM enregistré avec succès';
            }

            // Redondance: conserver aussi le dernier token sur la table users.
            // Cela permet un fallback si la table fcm_tokens est vide/incomplète.
            $user->fcm_token = $request->fcm_token;
            $user->push_notifications_enabled = true;
            $user->save();

            \Log::info('FCM Token saved', [
                'user_id' => $user->id,
                'platform' => $request->platform,
                'action' => $fcmToken->wasRecentlyCreated ? 'created' : 'updated',
                'auth_context' => $authContext,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $fcmToken->id,
                    'platform' => $fcmToken->platform,
                    'created_at' => $fcmToken->created_at,
                    'updated_at' => $fcmToken->updated_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error saving FCM token', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'auth_context' => $authContext,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du token FCM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer le token FCM de l'utilisateur (lors de la déconnexion)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'sometimes|string|in:android,ios',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            $query = FcmToken::where('user_id', $user->id);

            if ($request->has('platform')) {
                $query->where('platform', $request->platform);
            }

            $deletedCount = $query->delete();

            \Log::info('FCM Token deleted', [
                'user_id' => $user->id,
                'platform' => $request->platform ?? 'all',
                'count' => $deletedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token(s) FCM supprimé(s) avec succès',
                'deleted_count' => $deletedCount,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error deleting FCM token', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du token FCM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir le token FCM actuel de l'utilisateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            $user = auth()->user();
            $platform = $request->query('platform');

            $query = FcmToken::where('user_id', $user->id);

            if ($platform) {
                $query->where('platform', $platform);
            }

            $tokens = $query->get()->map(function($token) {
                return [
                    'id' => $token->id,
                    'platform' => $token->platform,
                    'created_at' => $token->created_at,
                    'updated_at' => $token->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $tokens,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error getting FCM token', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du token FCM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
