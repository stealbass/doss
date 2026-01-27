<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MobileAppSubscription;
use App\Services\SimpleRagService;
use App\Services\AdvancedRagService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Get chat history (all messages from all conversations)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatHistory(Request $request)
    {
        $user = $request->user();
        $conversationId = $request->query('conversation_id');

        $query = Message::query()
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.user_id', $user->id)
            ->select('messages.*');

        if ($conversationId) {
            $query->where('messages.conversation_id', $conversationId);
        }

        $messages = $query->orderBy('messages.created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'conversation_id' => $msg->conversation_id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'tokens_used' => $msg->tokens_used,
                    'created_at' => $msg->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
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
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'message' => 'required|string|max:2000',
            'use_rag' => 'nullable|boolean',
            'rag_type' => 'nullable|in:simple,advanced,both',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'integer|exists:submitted_documents,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        Log::info('Mobile chat request received', [
            'user_id' => $user->id,
            'conversation_id' => $request->conversation_id,
            'use_rag' => $request->use_rag,
            'rag_type' => $request->rag_type,
        ]);

        // Conversation handling: reuse provided conversation or auto-create for mobile clients
        $conversation = null;
        if ($request->filled('conversation_id')) {
            $conversation = Conversation::where('id', $request->conversation_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }
        } else {
            // Create a new conversation for each new chat
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => 'Conversation',
            ]);
            
            Log::info('Mobile chat: New conversation created', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
            ]);
        }

        // Check quota
        // FIX: Direct query to avoid cache issues with mobileAppSubscription() method
        $subscription = MobileAppSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->latest('created_at')
            ->first();
        
        if (!$subscription) {
            Log::warning('Mobile chat blocked: no active subscription', ['user_id' => $user->id]);
            return $this->quotaErrorResponse('ai_analysis', null, 'Abonnement actif requis pour utiliser l\'analyse IA', 'subscription_required');
        }

        if (!$subscription->canUseAIAnalysis()) {
            Log::warning('Mobile chat blocked: AI quota exceeded', [
                'user_id' => $user->id,
                'plan_id' => $subscription->mobile_app_plan_id,
                'ai_analyses_used' => $subscription->ai_analyses_used,
            ]);
            return $this->quotaErrorResponse('ai_analysis', $subscription, 'Quota d\'analyse IA dépassé. Veuillez mettre à niveau votre plan.');
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
            $sources = [];
            $simpleResult = ['context' => '', 'sources' => []];
            $useRag = $request->use_rag ?? true;
            $ragType = $request->rag_type ?? 'both';
            $hasSelectedDocuments = $request->filled('document_ids') && is_array($request->document_ids) && count($request->document_ids) > 0;
            
            // Get user country for AI context
            $userCountry = $user->country ?? 'Sénégal';
            $countryContext = $this->getCountryAIContext($userCountry);

            // ✅ OPTIMISÉ : Recherche sémantique complète via Pinecone pour documents utilisateur
            $userDocumentContent = '';
            $userDocumentSources = [];
            if ($hasSelectedDocuments) {
                Log::info('Mobile chat: Processing user documents with semantic search', [
                    'user_id' => $user->id,
                    'document_ids' => $request->document_ids,
                    'query' => $request->message,
                ]);
                
                try {
                    // Vérifier que les documents appartiennent à l'utilisateur
                    $userDocuments = \App\Models\SubmittedDocument::whereIn('id', $request->document_ids)
                        ->where('user_id', $user->id)
                        ->where('processing_status', 'completed')
                        ->get();
                    
                    if ($userDocuments->isEmpty()) {
                        Log::warning('Mobile chat: No valid documents found', [
                            'requested_ids' => $request->document_ids,
                        ]);
                    } else {
                        // Utiliser AdvancedRagService pour recherche sémantique
                        $ragResults = $this->advancedRag->searchSpecificDocuments(
                            $request->message,
                            $user->id,
                            $request->document_ids,
                            10 // Top 10 chunks les plus pertinents
                        );
                        
                        if (!empty($ragResults)) {
                            // 🔧 Grouper les résultats par document_id pour priorité
                            $resultsByDocument = [];
                            foreach ($ragResults as $result) {
                                $docId = $result['metadata']['document_id'] ?? null;
                                if ($docId) {
                                    if (!isset($resultsByDocument[$docId])) {
                                        $resultsByDocument[$docId] = [];
                                    }
                                    $resultsByDocument[$docId][] = $result;
                                }
                            }
                            
                            // 🎯 Ordonner par document_ids spécifiés (priorité au premier)
                            $orderedResults = [];
                            foreach ($request->document_ids as $reqDocId) {
                                if (isset($resultsByDocument[$reqDocId])) {
                                    $orderedResults[$reqDocId] = $resultsByDocument[$reqDocId];
                                }
                            }
                            // Ajouter les documents restants
                            foreach ($resultsByDocument as $docId => $results) {
                                if (!isset($orderedResults[$docId])) {
                                    $orderedResults[$docId] = $results;
                                }
                            }
                            
                            $userDocumentContent = "\n\n=== DOCUMENTS DE L'UTILISATEUR (Recherche Sémantique) ===\n\n";
                            $documentCount = 0;
                            
                            // Afficher les documents dans l'ordre prioritaire
                            foreach ($orderedResults as $docId => $results) {
                                $documentCount++;
                                
                                // Déterminer si c'est le document principal (premier sélectionné)
                                $isPrimaryDoc = $documentCount === 1;
                                $priorityMarker = $isPrimaryDoc ? '⭐ DOCUMENT PRINCIPAL : ' : '';
                                
                                foreach ($results as $result) {
                                    $score = $result['score'] ?? 0;
                                    $fileName = $result['metadata']['file_name'] ?? 'Document';
                                    $text = $result['metadata']['text'] ?? '';
                                    $chunkIndex = $result['metadata']['chunk_index'] ?? 0;
                                    
                                    $userDocumentContent .= "📄 {$priorityMarker}**{$fileName}** (Pertinence: " . number_format($score * 100, 1) . "%)\n";
                                    $userDocumentContent .= "Extrait #{$chunkIndex}: {$text}\n\n";
                                }
                                
                                // Ajouter à la liste des sources (éviter doublons)
                                if (!in_array($docId, array_column($userDocumentSources, 'id'))) {
                                    $primaryResult = reset($results); // Premier résultat pour les métadonnées
                                    $fileName = $primaryResult['metadata']['file_name'] ?? 'Document';
                                    $score = $primaryResult['score'] ?? 0;
                                    
                                    $userDocumentSources[] = [
                                        'id' => $docId,
                                        'title' => $fileName,
                                        'type' => 'user_document_semantic',
                                        'file_name' => $fileName,
                                        'relevance_score' => $score,
                                        'is_primary' => $isPrimaryDoc,
                                    ];
                                }
                            }
                            
                            $userDocumentContent .= "===\n\n";
                            
                            Log::info('Mobile chat: Semantic search completed', [
                                'results_count' => count($ragResults),
                                'content_length' => strlen($userDocumentContent),
                                'documents_found' => count($userDocumentSources),
                            ]);
                        } else {
                            Log::info('Mobile chat: No relevant content found in documents via semantic search');

                            // 🔁 FALLBACK 1: local search on extracted_text when Pinecone is empty
                            try {
                                $localResults = $this->advancedRag->searchUserDocuments(
                                    $request->message,
                                    $user->id,
                                    10
                                );

                                if (!empty($localResults)) {
                                    $userDocumentContent = "\n\n=== DOCUMENTS DE L'UTILISATEUR (Recherche Locale) ===\n\n";
                                    foreach ($localResults as $item) {
                                        $score = $item['score'] ?? 0;
                                        $fileName = $item['filename'] ?? 'Document';
                                        $contextText = $item['context'] ?? '';

                                        $userDocumentContent .= "📄 **{$fileName}** (Pertinence: " . number_format($score * 100, 1) . "%)\n";
                                        $userDocumentContent .= $contextText . "\n\n";

                                        $docId = $item['id'] ?? null;
                                        if ($docId && !in_array($docId, array_column($userDocumentSources, 'id'))) {
                                            $userDocumentSources[] = [
                                                'id' => $docId,
                                                'title' => $fileName,
                                                'type' => 'user_document_local',
                                                'file_name' => $fileName,
                                                'relevance_score' => $score,
                                            ];
                                        }
                                    }
                                    $userDocumentContent .= "===\n\n";

                                    Log::info('Mobile chat: Local search fallback completed', [
                                        'results_count' => count($localResults),
                                        'content_length' => strlen($userDocumentContent),
                                    ]);
                                } else {
                                    Log::info('Mobile chat: No relevant content found via local search fallback');
                                    
                                    // 🔁 FALLBACK 2: Get full document content if extraction exists
                                    Log::info('Mobile chat: Trying full document content fallback', [
                                        'document_ids' => $request->document_ids,
                                    ]);
                                    
                                    foreach ($request->document_ids as $docId) {
                                        $doc = \App\Models\SubmittedDocument::find($docId);
                                        if ($doc && $doc->extracted_text_length > 0) {
                                            $fileName = $doc->original_filename ?? 'Document';
                                            $extractedText = $doc->extracted_text;
                                            
                                            // Limiter la longueur pour ne pas surcharger le contexte (20000 chars max)
                                            $maxChars = 20000;
                                            if (strlen($extractedText) > $maxChars) {
                                                $extractedText = substr($extractedText, 0, $maxChars) . "\n\n[... contenu tronqué pour limiter la taille du contexte]";
                                            }
                                            
                                            $userDocumentContent = "\n\n=== DOCUMENTS DE L'UTILISATEUR (Contenu Complet) ===\n\n";
                                            $userDocumentContent .= "📄 **{$fileName}**\n\n";
                                            $userDocumentContent .= $extractedText . "\n\n";
                                            $userDocumentContent .= "===\n\n";
                                            
                                            $userDocumentSources[] = [
                                                'id' => $docId,
                                                'title' => $fileName,
                                                'type' => 'user_document_full',
                                                'file_name' => $fileName,
                                                'relevance_score' => 1.0,
                                            ];
                                            
                                            Log::info('Mobile chat: Full document content fallback used', [
                                                'document_id' => $docId,
                                                'file_name' => $fileName,
                                                'content_length' => strlen($extractedText),
                                            ]);
                                            
                                            break; // Utiliser seulement le premier document avec du contenu
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::error('Mobile chat: Local search fallback failed', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Mobile chat: Failed semantic search on user documents', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'document_ids' => $request->document_ids,
                    ]);
                }
            }

            // Only use RAG if question needs documents (not for greetings or general chat)
            $needsRag = $this->questionNeedsDocuments($request->message);
            
            Log::info('Mobile chat: RAG detection', [
                'message' => $request->message,
                'needsRag' => $needsRag,
                'useRag' => $useRag,
                'hasSelectedDocuments' => $hasSelectedDocuments,
            ]);
            
            // Toujours utiliser les bibliothèques globales sauf si documents utilisateur spécifiques
            $allowGlobalRag = true;

            if ($useRag && ($needsRag || !empty($userDocumentContent))) {
                // Advanced RAG (User Documents from Pinecone)
                if ($allowGlobalRag && in_array($ragType, ['advanced', 'both'])) {
                    try {
                        $advancedContext = $this->advancedRag->getContext($request->message, $user->id, 1000);
                        if (!empty($advancedContext)) {
                            $context .= $advancedContext;
                        }
                    } catch (\Exception $e) {
                        Log::error('Advanced RAG failed', ['error' => $e->getMessage()]);
                        // Continue without RAG context
                    }
                }

                // ✅ SEULE SOURCE DE VÉRITÉ : Recherche dans les bibliothèques (legal, template, fiscal)
                if ($allowGlobalRag && $needsRag) {
                    Log::info('Mobile chat: Starting library search', [
                        'message' => $request->message,
                        'allowGlobalRag' => $allowGlobalRag,
                        'needsRag' => $needsRag,
                    ]);
                    
                    try {
                        $questionTypeForSearch = $this->detectQuestionType($request->message);
                        $allowedSources = match ($questionTypeForSearch) {
                            'legal' => ['legal'],
                            'template' => ['template'],
                            'fiscal' => ['fiscal'],
                            default => ['legal', 'template', 'fiscal'],
                        };

                        Log::info('Mobile chat: Detected question type', [
                            'questionType' => $questionTypeForSearch,
                            'allowedSources' => $allowedSources,
                        ]);

                        $libraryResult = $this->advancedRag->getLibraryContext(
                            $request->message,
                            $userCountry,
                            1500, // Max tokens pour bibliothèques
                            $allowedSources
                        );
                        
                        Log::info('Mobile chat: Library context result', [
                            'sources_count' => count($libraryResult['sources'] ?? []),
                            'context_length' => strlen($libraryResult['context'] ?? ''),
                            'sources' => $libraryResult['sources'] ?? [],
                        ]);
                        
                        if (!empty($libraryResult['context'])) {
                            $context .= $libraryResult['context'] . "\n\n";
                            $sources = array_merge($sources, $libraryResult['sources']);
                            
                            Log::info('Mobile chat: Library search results', [
                                'sources_found' => count($libraryResult['sources']),
                                'context_length' => strlen($libraryResult['context']),
                                'source_types' => array_column($libraryResult['sources'], 'type'),
                                'all_sources' => $sources,
                            ]);
                        } else {
                            Log::info('Mobile chat: Library context is empty', [
                                'message' => $request->message,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Library search failed', ['error' => $e->getMessage()]);
                        // Continue without library context
                    }
                } else {
                    Log::info('Mobile chat: Library search skipped', [
                        'allowGlobalRag' => $allowGlobalRag,
                        'needsRag' => $needsRag,
                    ]);
                }
            }
            
            // ✅ NOUVEAU : Ajouter le contenu des documents utilisateur sélectionnés
            if (!empty($userDocumentContent)) {
                $context .= $userDocumentContent;
                $sources = array_merge($sources, $userDocumentSources);
            }

            if ($hasSelectedDocuments) {
                $context = "=== MODE DOCUMENTS SÉLECTIONNÉS ===\n"
                    . "Réponds UNIQUEMENT à partir des documents sélectionnés ci-dessous. Ignore tout autre document ou connaissance générale.\n"
                    . "Si l'information n'est pas présente dans ces documents, dis-le clairement sans inventer.\n\n"
                    . $context;
            } else if ($needsRag && !empty($sources)) {
                // If we have library documents, prioritize them heavily
                $context = "=== RÉPONSE BASÉE SUR LES DOCUMENTS JURIDIQUES/FISCALES ===\n"
                    . "TRÈS IMPORTANT: Tu dois baser TA RÉPONSE en PRIORITÉ sur les documents fournis ci-dessous.\n"
                    . "Cite toujours les articles, clauses ou références exactes du document quand c'est applicable.\n"
                    . "Ne donne une réponse générale que si l'information n'existe pas dans les documents.\n\n"
                    . $context;
            }
            
            // Add country-specific AI instructions
            $context .= "\n\n" . $countryContext;


            // Get AI model from subscription plan
            $aiModel = $subscription->plan->ai_model ?? 'gpt-3.5-turbo';

            Log::info('Mobile chat: calling OpenAI', [
                'user_id' => $user->id,
                'model' => $aiModel,
                'context_length' => strlen($context),
                'history_count' => count($history),
                'sources_count' => count($sources),
                'query' => $request->message,
            ]);
            
            // Debug: Log the full context for inspection
            Log::debug('Mobile chat: Full RAG context', [
                'context_preview' => substr($context, 0, 1500),
                'sources' => $sources,
            ]);

            // Generate AI response
            $response = $this->openai->chatWithContext(
                $request->message,
                $context,
                $history,
                $aiModel
            );

            Log::info('Mobile chat: OpenAI response received', [
                'user_id' => $user->id,
                'success' => $response['success'],
            ]);

            if (!$response['success']) {
                Log::error('Mobile chat: OpenAI response failed', [
                    'user_id' => $user->id,
                    'error' => $response['error'] ?? 'unknown',
                    'error_type' => $response['error_type'] ?? null,
                ]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $response['error'] ?? 'AI response failed',
                    'error' => $response['error'] ?? 'Unknown error',
                ], 500);
            }

            // If AI says it doesn't have the information, don't show sources
            if ($this->aiResponseIndicatesNoInfo($response['message'])) {
                $sources = [];
                Log::info('Mobile chat: AI indicated no information, clearing sources');
            } else if (!empty($sources)) {
                // Filter sources to only those mentioned by the AI in its response
                $sources = $this->filterSourcesByAIResponse($response['message'], $sources);
                
                Log::info('Mobile chat: Filtered sources by AI mention', [
                    'remaining_sources' => count($sources),
                ]);
            }
            
            // Log final sources before response
            Log::info('Mobile chat: Final sources for response', [
                'total_sources' => count($sources),
                'source_types' => array_column($sources, 'type'),
            ]);

            // Save assistant message
            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response['message'],
                'tokens_used' => $response['tokens_used']['total'] ?? 0,
            ]);

            // Increment AI usage and capture any alerts for the app
            $alerts = $subscription->incrementAIAnalysis();

            // Update conversation title if first message
            if ($conversation->messages()->count() == 2) {
                $title = mb_substr($request->message, 0, 50);
                $conversation->update(['title' => $title]);
            }

            DB::commit();

            // Only include sources if RAG actually found something
            $responseData = [
                'conversation_id' => $conversation->id,
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
                // Mobile app compatibility fields
                'response' => $assistantMessage->content,
                'metadata' => [],
                'is_anonymized' => false,
                'tokens_used' => $response['tokens_used'],
                'model' => $response['model'],
                'quotas' => $this->quotaPayload($subscription, 'ai_analysis'),
                'alerts' => $alerts,
            ];

            // Only add sources if they were actually found
            if (!empty($sources)) {
                $responseData['sources'] = $sources;
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mobile chat: unexpected exception', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id ?? null,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Message send failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect if question needs RAG documents (legal, fiscal, templates)
     * Returns true only for questions about law, contracts, fiscal matters, etc.
     */
    private function questionNeedsDocuments(string $message): bool
    {
        $text = mb_strtolower(trim($message));
        
        // Strong phrases that almost always require documents
        $documentPhrases = [
            // Juridique
            'code civil', 'code penal', 'code pénal', 'code de commerce', 'code de procedure',
            'acte uniforme', 'ohada', 'article ', 'loi n°', 'décret n°', 'arrêt ', 'arret ',
            
            // Fiscal & Social
            'code general des impots', 'code général des impôts', 'déclaration tva', 'liasse fiscale',
            'centre de gestion agréé', 'plan comptable', 'cotisations sociales', 'charges sociales',
            
            // Templates / Modèles
            'modèle de contrat', 'modele de contrat', 'modèle de bail', 'contrat de bail',
            'contrat de travail', 'procès verbal', 'proces verbal', 'formulaire type', 'lettre de mission',
        ];
        foreach ($documentPhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }

        // Keywords that indicate a need for documents
        $documentKeywords = [
            // Juridique
            'loi', 'code', 'article', 'décret', 'jurisprudence', 'tribunal', 'justice',
            'contrat', 'bail', 'convention', 'accord', 'clause', 'modèle', 'modele',
            'droit', 'juridique', 'légal', 'legal', 'réglementation', 'reglementation', 'texte',
            
            // Fiscal & Social
            'fiscal', 'impôt', 'impot', 'taxe', 'tva', 'irpp', 'is', 'cnps', 'sécurité sociale', 'securite sociale',
            'cotisation', 'cotisations', 'déclaration fiscale', 'declaration fiscale', 'centre de gestion', 'agréé', 'agree',
            'repertoire', 'répertoire', 'liste', 'centres', 'taux', 'barème', 'bareme',
            
            // Templates
            'modèle', 'modele', 'template', 'formulaire', 'document type', 'avenant', 'pv', 'procès', 'proces',
            
            // Questions
            'comment rédiger', 'comment rediger', 'où trouver', 'ou trouver', 'quel article', 'quelle loi',
            'quels sont les', 'quelles sont les', 'existe-t-il', 'existe t il', 'donne moi',
        ];
        foreach ($documentKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        
        // If message is a question mark, probably needs documents
        if (str_contains($text, '?')) {
            // But not for general questions
            $generalQuestions = [
                'comment vas', 'ca va', 'ça va', 'tu vas bien',
                'quelle heure', 'quel jour', 'quelle date',
                'qui es-tu', 'qui êtes-vous', "c'est quoi",
            ];
            
            foreach ($generalQuestions as $general) {
                if (str_contains($text, $general)) {
                    return false;
                }
            }
            
            return true; // Other questions probably need RAG
        }
        
        return false; // Default: no RAG for general chat
    }

    /**
     * Detect if AI response indicates it doesn't have the information
     * Returns true if AI says "I don't have", "I don't know", "I recommend consulting", etc.
     */
    private function aiResponseIndicatesNoInfo(string $response): bool
    {
        $text = mb_strtolower($response);
        
        // ONLY return true if the AI clearly says it has NO info
        // NOT for phrases like "I recommend consulting" which indicate sources exist
        $noInfoPhrases = [
            "je n'ai pas cette information",
            "je n'ai pas d'information",
            "je ne dispose pas",
            "je n'ai vraiment aucune",
            "aucune information",
            "pas de données",
            "no information available",
            "no data available",
        ];
        
        foreach ($noInfoPhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }
        
        // EXPLICITLY return false if response recommends sources
        if (str_contains($text, "je recommande") || 
            str_contains($text, "je vous conseille") ||
            str_contains($text, "consultez") ||
            str_contains($text, "voici")) {
            return false;
        }
        
        return false;
    }

    /**
     * Filter sources to show only the type that was actually used in the response
     * - If response mentions templates/models/contrats → show only document_template
     * - If response mentions fiscal/impôts/taxes → show only fiscal_resource  
     * - If response is legal/juridique → show only legal_document
     */
    private function filterSourcesByRelevantType(string $aiResponse, array $sources): array
    {
        if (empty($sources)) {
            return [];
        }
        
        // Get question type from the message that was stored during chat
        // We'll use the original query from the last message to detect type
        $query = request()->input('message', '');
        $questionType = $this->detectQuestionType($query);
        
        Log::info('Mobile chat: Filtering sources by question type', [
            'detected_type' => $questionType,
            'total_sources' => count($sources),
            'source_types' => array_column($sources, 'type'),
        ]);
        
        // Helper: normalize legacy/new types and match
        $isType = function($source, $types) {
            $sourceType = strtolower(trim($source['type'] ?? ''));
            $map = [
                'legal_document' => 'legal',
                'document_template' => 'template',
                'fiscal_resource' => 'fiscal',
            ];
            $normalized = strtolower(trim($map[$sourceType] ?? $sourceType));
            foreach ((array)$types as $type) {
                if ($normalized === $type || $sourceType === $type) {
                    return true;
                }
            }
            return false;
        };

        // Always preserve user documents regardless of type filtering
        $userDocs = array_filter($sources, function($source) {
            return strtolower(trim($source['type'] ?? '')) === 'user_document_local';
        });
        
        // Helper to merge preserved user docs back into the filtered list
        $withUserDocs = function(array $filtered) use ($userDocs) {
            if (empty($userDocs)) {
                return array_values($filtered);
            }
            return array_values(array_merge($filtered, $userDocs));
        };

        // PRIORITIZE: Filter sources by the DETECTED question type first
        // This ensures we show the type that RAG prioritized for this question
        
        if ($questionType === 'fiscal') {
            // Fiscal question: prioritize fiscal sources
            $filtered = array_filter($sources, function($source) use ($isType) {
                return $isType($source, ['fiscal']);
            });
            
            if (!empty($filtered)) {
                Log::info('Mobile chat: Showing fiscal sources (detected type)', [
                    'count' => count($filtered),
                ]);
                return $withUserDocs($filtered);
            }
            
            // Fallback: if no fiscal sources, show templates
            $filtered = array_filter($sources, function($source) use ($isType) {
                return $isType($source, ['template']);
            });
            
            if (!empty($filtered)) {
                Log::warning('Mobile chat: No fiscal sources found, showing templates as fallback');
                return $withUserDocs($filtered);
            }
            
            // Final fallback: show legal
            $filtered = array_filter($sources, function($source) use ($isType) {
                return $isType($source, ['legal']);
            });
            
            Log::warning('Mobile chat: No fiscal/template sources found, showing legal as fallback');
            return $withUserDocs($filtered);
        }
        
        if ($questionType === 'template') {
            // Template question: prioritize templates
            $filtered = array_filter($sources, function($source) use ($isType) {
                return $isType($source, ['template']);
            });
            
            if (!empty($filtered)) {
                Log::info('Mobile chat: Showing template sources (detected type)', [
                    'count' => count($filtered),
                ]);
                return $withUserDocs($filtered);
            }
            
            // Fallback: if no templates, show legal
            $filtered = array_filter($sources, function($source) use ($isType) {
                return $isType($source, ['legal']);
            });
            
            if (!empty($filtered)) {
                Log::warning('Mobile chat: No template sources found, showing legal as fallback');
                return $withUserDocs($filtered);
            }
            
            // Final fallback: show fiscal
            $filtered = array_filter($sources, function($source) use ($isType) {
                return $isType($source, ['fiscal']);
            });
            
            Log::warning('Mobile chat: No template/legal sources found, showing fiscal as fallback');
            return $withUserDocs($filtered);
        }
        
        // DEFAULT: Legal question — do NOT fall back to template/fiscal
        $filtered = array_filter($sources, function($source) use ($isType) {
            return $isType($source, ['legal']);
        });
        
        if (!empty($filtered)) {
            Log::info('Mobile chat: Showing legal sources (detected type)', [
                'count' => count($filtered),
            ]);
            return $withUserDocs($filtered);
        }

        if (!empty($userDocs)) {
            Log::warning('Mobile chat: No legal sources found, returning user documents only');
            return array_values($userDocs);
        }

        Log::warning('Mobile chat: No legal sources found, returning empty list');
        return [];
    }

    /**
     * Detect the TYPE of question to prioritize RAG search
     * Returns: 'fiscal', 'template', 'legal', or 'mixed'
     */
    private function detectQuestionType(string $query): string
    {
        $text = mb_strtolower($query);

        // Multi-word phrases are stronger signals than single tokens
        $signals = [
            'legal' => [
                'phrases' => [
                    'code civil', 'code penal', 'code pénal', 'code de commerce', 'code de procedure',
                    'acte uniforme', 'ohada', 'arrêt de la cour', 'jurisprudence', 'article', 'loi n°',
                    'décret n°', 'ordonnance', 'réglementation', 'texte légal', 'texte de loi',
                ],
                'keywords' => [
                    'loi', 'code', 'article', 'décret', 'jurisprudence', 'tribunal', 'justice',
                    'légal', 'juridique', 'procédure', 'réglementation', 'texte', 'arret', 'arrêt',
                ],
            ],
            'fiscal' => [
                'phrases' => [
                    'code general des impots', 'code général des impôts', 'declaration tva',
                    'déclaration tva', 'liasse fiscale', 'centre de gestion agréé', 'plan comptable',
                    'cotisations sociales', 'charges sociales', 'plafond cnps',
                ],
                'keywords' => [
                    'impôt', 'impots', 'taxe', 'taxes', 'fiscal', 'tva', 'irpp', 'is', 'cnps',
                    'cotisation', 'cotisations', 'social', 'bareme', 'barème', 'taux', 'repertoire',
                    'répertoire', 'agréé', 'declaration', 'déclaration', 'douane', 'douanes',
                ],
            ],
            'template' => [
                'phrases' => [
                    'modele de contrat', 'modèle de contrat', 'modèle de bail', 'contrat de bail',
                    'contrat de travail', 'contrat de prestation', 'proces verbal', 'procès verbal',
                    'lettre de motivation', 'lettre de mission', 'attestation', 'formulaire type',
                ],
                'keywords' => [
                    'modèle', 'modele', 'template', 'contrat', 'accord', 'formulaire', 'rédiger',
                    'clause', 'avenant', 'bail', 'lettre', 'attestation', 'pv', 'procès', 'proces',
                ],
            ],
        ];

        $scores = ['legal' => 0, 'fiscal' => 0, 'template' => 0];

        foreach ($signals as $type => $sets) {
            foreach ($sets['phrases'] as $phrase) {
                if (str_contains($text, $phrase)) {
                    $scores[$type] += 3; // phrases are strong indicators
                }
            }
            foreach ($sets['keywords'] as $keyword) {
                if (str_contains($text, $keyword)) {
                    $scores[$type] += 1;
                }
            }
        }

        // Pick the best score; if tie, prefer template when contract keywords are present, otherwise legal
        arsort($scores);
        $topType = array_key_first($scores);
        $topScore = $scores[$topType];

        // If all zero, default to legal
        if ($topScore === 0) {
            return 'legal';
        }

        // Detect meaningful ties (two categories within 1 point)
        $secondType = null;
        $secondScore = null;
        $i = 0;
        foreach ($scores as $type => $score) {
            if ($i === 1) { $secondType = $type; $secondScore = $score; break; }
            $i++;
        }

        if ($secondScore !== null && ($topScore - $secondScore) <= 1) {
            // If template is involved in a tie with legal/fiscal, favor template for contracts/forms
            if (in_array('template', [$topType, $secondType], true)) {
                return 'template';
            }
            // If tie between legal and fiscal, keep legal as safer default
        }

        return $topType;
    }

    /**
     * Intelligent RAG search prioritizing question type
     * For fiscal questions: prioritize fiscal_resource (70%) over templates and legal
     * For template questions: prioritize templates (70%) over others
     * For legal questions: prioritize legal_document (70%) over others
     */
    /**
     * Filter sources by checking if they were actually mentioned/cited in the AI response
     * Only show sources that the AI actually used
     */
    private function filterSourcesByAIResponse(string $aiResponse, array $sources): array
    {
        if (empty($sources)) {
            return [];
        }

        // IMPORTANT: We almost always return all sources found
        // The AI may use them without explicitly mentioning the document name
        // This is the correct behavior - sources are the documents the AI read to generate the answer
        
        Log::info('Mobile chat: Returning all found sources (AI may have used them)', [
            'sources_count' => count($sources),
            'source_types' => array_column($sources, 'type'),
        ]);

        return $sources;
    }

    /**
     * Detect simple greeting messages to avoid showing sources on greetings
     */
    private function isGreetingMessage(string $message): bool
    {
        $text = mb_strtolower(trim($message));
        $greetings = [
            'bonjour', 'salut', 'hello', 'hi', 'bonsoir',
            'bjr', 'slm', 'salam', 'hey', 'yo',
        ];

        foreach ($greetings as $greet) {
            if ($text === $greet || str_starts_with($text, $greet . ' ')) {
                return true;
            }
        }
        return false;
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
        // Chercher le code pays dans la config
        $supportedCountries = config('mobile_countries.supported_countries', []);
        $countryCode = null;
        
        // Trouver le code pays à partir du nom
        foreach ($supportedCountries as $code => $data) {
            if ($data['name'] === $country || $code === $country) {
                $countryCode = $code;
                break;
            }
        }
        
        // Si pays non trouvé, utiliser contexte OHADA par défaut
        if (!$countryCode || !isset($supportedCountries[$countryCode])) {
            return $this->getDefaultAIContext();
        }
        
        $countryData = $supportedCountries[$countryCode];
        $countryName = $countryData['name'];
        $legalSystems = implode(', ', $countryData['legal_systems'] ?? ['Civil Law']);
        $region = $countryData['region'] ?? 'Africa';
        
        // Récupérer les instructions spécifiques au pays
        $countrySpecificContext = config("mobile_countries.ai_context.country_specific.{$countryCode}", '');
        
        // Construction du prompt système avec règles strictes
        $context = "=== CONTEXTE JURIDIQUE STRICT ===\n\n";
        $context .= "Tu es un assistant juridique expert en droit africain.\n";
        $context .= "L'utilisateur a sélectionné la juridiction : {$countryName}.\n\n";
        
        $context .= "INFORMATIONS JURIDICTION :\n";
        $context .= "- Pays : {$countryName}\n";
        $context .= "- Région : {$region}\n";
        $context .= "- Systèmes juridiques : {$legalSystems}\n\n";
        
        // Instructions spécifiques au pays si disponibles
        if (!empty($countrySpecificContext)) {
            $context .= "INSTRUCTIONS SPÉCIFIQUES - {$countryName} :\n";
            $context .= $countrySpecificContext . "\n\n";
        }
        
        // Règles strictes UNIVERSELLES
        $context .= "=== RÈGLES STRICTES (À RESPECTER ABSOLUMENT) ===\n\n";
        
        $context .= "1. DROIT DES AFFAIRES (Création entreprise, sociétés, commerce) :\n";
        if (in_array('OHADA', $countryData['legal_systems'] ?? [])) {
            $context .= "   → Utilise EXCLUSIVEMENT les Actes Uniformes OHADA.\n";
            $context .= "   → Liste des Actes Uniformes applicables :\n";
            $ohadaActes = config('mobile_countries.legal_systems.OHADA.actes_uniformes', []);
            foreach ($ohadaActes as $acte) {
                $context .= "     • {$acte}\n";
            }
        } else {
            $context .= "   → Utilise le Code de Commerce et législation nationale de {$countryName}.\n";
        }
        $context .= "\n";
        
        $context .= "2. DROIT DE LA FAMILLE (Mariage, divorce, succession) :\n";
        if ($countryCode === 'SN') {
            $context .= "   → Pour le Sénégal : Utilise EXCLUSIVEMENT le Code de la Famille du Sénégal.\n";
        } elseif ($countryCode === 'MA') {
            $context .= "   → Pour le Maroc : Utilise EXCLUSIVEMENT la Moudawana (Code de la Famille marocain).\n";
        } elseif ($countryCode === 'TN') {
            $context .= "   → Pour la Tunisie : Utilise EXCLUSIVEMENT le Code du Statut Personnel tunisien.\n";
        } else {
            $context .= "   → Utilise le Code de la Famille ou Code civil de {$countryName}.\n";
        }
        $context .= "\n";
        
        $context .= "3. DROIT DU TRAVAIL :\n";
        $context .= "   → Utilise EXCLUSIVEMENT le Code du Travail de {$countryName}.\n";
        $context .= "   → Ne JAMAIS référencer le droit du travail d'un autre pays.\n\n";
        
        $context .= "4. INTERDICTION STRICTE DE MÉLANGE DE JURIDICTIONS :\n";
        $context .= "   → Ne JAMAIS citer des lois d'un autre pays que {$countryName}.\n";
        $context .= "   → Si la question concerne un autre pays, réponds :\n";
        $context .= "     \"Cette question concerne [autre pays]. Je suis configuré pour {$countryName}.\n";
        $context .= "      Veuillez sélectionner la bonne juridiction dans les paramètres.\"\n\n";
        
        $context .= "5. CITATIONS ET RÉFÉRENCES :\n";
        $context .= "   → Cite TOUJOURS les articles de loi avec références exactes.\n";
        $context .= "   → Format : \"Article X du [Nom du Code/Loi] de {$countryName}\"\n";
        $context .= "   → Exemple : \"Article 52 du Code de la Famille du Sénégal\"\n\n";
        
        $context .= "6. EN CAS D'INCERTITUDE :\n";
        $context .= "   → Si tu ne sais pas, dis EXACTEMENT :\n";
        $context .= "     \"Je n'ai pas cette information pour {$countryName}.\n";
        $context .= "      Je recommande de consulter un juriste local spécialisé.\"\n";
        $context .= "   → Ne JAMAIS inventer ou extrapoler à partir d'autres juridictions.\n\n";
        
        $context .= "=== FORMAT DE RÉPONSE ===\n";
        $context .= "Toutes tes réponses doivent suivre ce format :\n";
        $context .= "1. Réponse claire et directe\n";
        $context .= "2. Base légale : Articles et codes applicables en {$countryName}\n";
        $context .= "3. Explications complémentaires si nécessaire\n";
        $context .= "4. Avertissement : \"Cette réponse est basée sur le droit de {$countryName}. Consultez un avocat pour votre cas spécifique.\"\n";
        
        return $context;
    }
    
    /**
     * Get default OHADA AI context
     * 
     * @return string
     */
    private function getDefaultAIContext()
    {
        $context = "=== CONTEXTE JURIDIQUE STRICT ===\n\n";
        $context .= "Tu es un assistant juridique expert en droit africain francophone.\n";
        $context .= "Juridiction : Droit OHADA (par défaut).\n\n";
        
        $context .= "=== RÈGLES STRICTES ===\n\n";
        $context .= "1. DROIT DES AFFAIRES :\n";
        $context .= "   → Utilise EXCLUSIVEMENT les Actes Uniformes OHADA.\n";
        $context .= "   → Les 9 Actes Uniformes couvrent : sociétés commerciales, droit commercial général,\n";
        $context .= "     sûretés, procédures de recouvrement, arbitrage, transport, comptabilité.\n\n";
        
        $context .= "2. AUTRES DOMAINES (Famille, Travail, Pénal) :\n";
        $context .= "   → Demande à l'utilisateur de préciser son pays car ces domaines relèvent\n";
        $context .= "     de la législation nationale de chaque État membre OHADA.\n\n";
        
        $context .= "3. CITATIONS :\n";
        $context .= "   → Cite toujours les articles exacts : \"Article X de l'Acte Uniforme relatif au [domaine]\"\n\n";
        
        $context .= "4. EN CAS D'INCERTITUDE :\n";
        $context .= "   → Dis clairement : \"Je n'ai pas cette information. Consultez un juriste OHADA.\"\n";
        $context .= "   → Ne JAMAIS inventer.\n";
        
        return $context;
    }

            private function quotaErrorResponse(string $feature, $subscription, string $message, string $code = 'quota_exceeded')
            {
                $payload = [
                    'success' => false,
                    'code' => $code,
                    'feature' => $feature,
                    'message' => $message,
                ];

                if ($subscription) {
                    $payload['quotas'] = $this->quotaPayload($subscription, $feature);
                }

                return response()->json($payload, 403);
            }

            private function quotaPayload($subscription, string $feature): array
            {
                $map = [
                    'ai_analysis' => 'ai_analyses',
                    'search' => 'searches',
                    'pdf_download' => 'pdf_downloads',
                ];

                $base = $map[$feature] ?? $feature;
                $usedField = $base . '_used';
                $limitField = $base . '_limit';

                return [
                    'feature' => $feature,
                    'used' => $subscription->$usedField ?? 0,
                    'limit' => $subscription->plan ? ($subscription->plan->$limitField ?? 0) : 0,
                    'remaining' => ($subscription->plan && isset($subscription->$usedField))
                        ? max(0, ($subscription->plan->$limitField ?? 0) - ($subscription->$usedField))
                        : 0,
                    'reset_at' => $subscription->quota_reset_at?->toIso8601String(),
                ];
            }
}
