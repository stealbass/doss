<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     * Enregistrer ou mettre à jour le token FCM de l'utilisateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:500',
            'platform' => 'required|string|in:android,ios',
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

            \Log::info('FCM Token saved', [
                'user_id' => $user->id,
                'platform' => $request->platform,
                'action' => $fcmToken->wasRecentlyCreated ? 'created' : 'updated',
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
                'user_id' => auth()->id(),
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
