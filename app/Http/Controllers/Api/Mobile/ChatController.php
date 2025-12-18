<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\SimpleRagService;
use App\Services\AdvancedRagService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    private SimpleRagService $simpleRag;
    private AdvancedRagService $advancedRag;
    private OpenAIService $openai;

    public function __construct(
        SimpleRagService $simpleRag,
        AdvancedRagService $advancedRag,
        OpenAIService $openai
    ) {
        $this->simpleRag = $simpleRag;
        $this->advancedRag = $advancedRag;
        $this->openai = $openai;
    }

    /**
     * Create a new conversation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createConversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => $request->title ?? 'Nouvelle conversation',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation_id' => $conversation->id,
                'title' => $conversation->title,
                'created_at' => $conversation->created_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    /**
     * Get user's conversations
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversations(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::where('user_id', $user->id)
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($conv) {
                return [
                    'id' => $conv->id,
                    'title' => $conv->title,
                    'messages_count' => $conv->messages_count,
                    'created_at' => $conv->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $conv->updated_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ], 200);
    }

    /**
     * Get conversation messages
     * 
     * @param Request $request
     * @param int $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request, $conversationId)
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
            ], 404);
        }

        $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'tokens_used' => $msg->tokens_used,
                    'created_at' => $msg->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                ],
                'messages' => $messages,
            ],
        ], 200);
    }

    /**
     * Send a message (AI chat with RAG)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message' => 'required|string|max:2000',
            'use_rag' => 'nullable|boolean',
            'rag_type' => 'nullable|in:simple,advanced,both',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check conversation ownership
        $conversation = Conversation::where('id', $request->conversation_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
            ], 404);
        }

        // Check quota
        $subscription = $user->mobileAppSubscription()->where('status', 'active')->first();
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription',
            ], 403);
        }

        if (!$subscription->canUseAIAnalysis()) {
            return response()->json([
                'success' => false,
                'message' => 'AI analysis quota exceeded. Please upgrade your plan.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Save user message
            $userMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $request->message,
            ]);

            // Get conversation history (last 10 messages)
            $history = Message::where('conversation_id', $conversation->id)
                ->where('id', '<', $userMessage->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse()
                ->map(function ($msg) {
                    return [
                        'role' => $msg->role,
                        'content' => $msg->content,
                    ];
                })
                ->toArray();

            // RAG context with country-specific filtering
            $context = '';
            $useRag = $request->use_rag ?? true;
            $ragType = $request->rag_type ?? 'both';
            
            // Get user country for AI context
            $userCountry = $user->country ?? 'Sénégal';
            $countryContext = $this->getCountryAIContext($userCountry);

            if ($useRag) {
                // Simple RAG (Legal Library - filtered by country)
                if (in_array($ragType, ['simple', 'both'])) {
                    $simpleContext = $this->simpleRag->getContextByCountry($request->message, $userCountry, 1000);
                    if (!empty($simpleContext)) {
                        $context .= $simpleContext . "\n\n";
                    }
                }

                // Advanced RAG (User Documents)
                if (in_array($ragType, ['advanced', 'both'])) {
                    $advancedContext = $this->advancedRag->getContext($request->message, $user->id, 1000);
                    if (!empty($advancedContext)) {
                        $context .= $advancedContext;
                    }
                }
            }
            
            // Add country-specific AI instructions
            $context .= "\n\n" . $countryContext;

            // Get AI model from subscription plan
            $aiModel = $subscription->plan->ai_model ?? 'gpt-3.5-turbo';

            // Generate AI response
            $response = $this->openai->chatWithContext(
                $request->message,
                $context,
                $history,
                $aiModel
            );

            if (!$response['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'AI response failed',
                    'error' => $response['error'] ?? 'Unknown error',
                ], 500);
            }

            // Save assistant message
            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response['message'],
                'tokens_used' => $response['tokens_used']['total'] ?? 0,
            ]);

            // Increment AI usage
            $subscription->incrementAIAnalysis();

            // Update conversation title if first message
            if ($conversation->messages()->count() == 2) {
                $title = mb_substr($request->message, 0, 50);
                $conversation->update(['title' => $title]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'user_message' => [
                        'id' => $userMessage->id,
                        'content' => $userMessage->content,
                        'created_at' => $userMessage->created_at->format('Y-m-d H:i:s'),
                    ],
                    'assistant_message' => [
                        'id' => $assistantMessage->id,
                        'content' => $assistantMessage->content,
                        'created_at' => $assistantMessage->created_at->format('Y-m-d H:i:s'),
                    ],
                    'tokens_used' => $response['tokens_used'],
                    'model' => $response['model'],
                    'quotas' => [
                        'ai_analyses_used' => $subscription->ai_analyses_used,
                        'ai_analyses_limit' => $subscription->plan->ai_analyses_limit,
                        'remaining' => $subscription->plan->ai_analyses_limit - $subscription->ai_analyses_used,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Message send failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete conversation
     * 
     * @param Request $request
     * @param int $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteConversation(Request $request, $conversationId)
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
            ], 404);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully',
        ], 200);
    }

    /**
     * Get country-specific AI context
     * 
     * @param string $country
     * @return string
     */
    private function getCountryAIContext($country)
    {
        $countries = config('mobile_countries.countries', []);
        
        if (!isset($countries[$country])) {
            // Default OHADA context
            return "CONTEXTE JURIDIQUE : Vous êtes un assistant juridique spécialisé dans le droit OHADA (Organisation pour l'Harmonisation en Afrique du Droit des Affaires). Fournissez des réponses basées UNIQUEMENT sur les lois et réglementations du pays de l'utilisateur. Si une question concerne un domaine non harmonisé par l'OHADA, précisez que la réponse dépend de la législation nationale spécifique.";
        }
        
        $countryData = $countries[$country];
        $legalSystem = $countryData['legal_system'] ?? 'Civil Law';
        $aiContext = $countryData['ai_context'] ?? '';
        $region = $countryData['region'] ?? 'West Africa';
        
        $context = "CONTEXTE JURIDIQUE SPÉCIFIQUE - {$country} :\n";
        $context .= "- Système juridique : {$legalSystem}\n";
        $context .= "- Région : {$region}\n";
        $context .= "- Instructions AI : {$aiContext}\n\n";
        $context .= "IMPORTANT : Toutes vos réponses doivent être EXCLUSIVEMENT basées sur les lois, codes et réglementations applicables en {$country}. ";
        $context .= "Ne donnez JAMAIS de conseils juridiques basés sur d'autres juridictions. ";
        $context .= "Si vous n'avez pas d'information spécifique pour {$country}, indiquez-le clairement et suggérez de consulter un juriste local.";
        
        return $context;
    }
}
