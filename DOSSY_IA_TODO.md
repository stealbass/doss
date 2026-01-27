# 📋 DOSSY IA - TODO LIST DÉTAILLÉE

## 🎯 PHASE 2: SERVICES RAG & API BACKEND (Semaine 1-2)

### A. Services RAG Advanced

#### 1. EmbeddingService.php ⏳
**Localisation:** `app/Services/AI/EmbeddingService.php`

**Responsabilités:**
- Génération embeddings avec OpenAI text-embedding-3-small
- Conversion texte → vecteur 1536 dimensions
- Gestion cache pour éviter appels redondants

**Méthodes:**
```php
generateEmbedding(string $text): array
generateBatchEmbeddings(array $texts): array
getCachedEmbedding(string $text): ?array
```

#### 2. PineconeService.php ⏳
**Localisation:** `app/Services/AI/PineconeService.php`

**Responsabilités:**
- Connexion API Pinecone
- Upsert vecteurs (indexation documents juridiques)
- Recherche par similarité

**Méthodes:**
```php
upsertVector(string $id, array $vector, array $metadata): bool
searchSimilar(array $queryVector, int $topK = 5, float $threshold = 0.7): array
deleteVector(string $id): bool
describeIndex(): array
```

#### 3. DocumentProcessingService.php ⏳
**Localisation:** `app/Services/AI/DocumentProcessingService.php`

**Responsabilités:**
- Extraction texte PDF (smalot/pdfparser)
- Chunking intelligent (max 2000 tokens par chunk)
- Détection métadonnées (titre, auteur, dates)

**Méthodes:**
```php
extractTextFromPdf(string $filepath): string
chunkText(string $text, int $maxTokens = 2000): array
extractMetadata(string $filepath): array
processDocument(SubmittedDocument $doc): bool
```

#### 4. RAGService.php ⏳
**Localisation:** `app/Services/AI/RAGService.php`

**Responsabilités:**
- Orchestration complète RAG Advanced
- Mode simple (MySQL FULLTEXT) et advanced (embeddings)
- Génération réponses avec contexte

**Méthodes:**
```php
simpleRAG(string $query, int $maxResults = 3): array
advancedRAG(string $query, int $topK = 5): array
generateResponse(string $query, array $context, string $model = 'gpt-3.5-turbo'): string
```

#### 5. OpenAIService.php ⏳
**Localisation:** `app/Services/AI/OpenAIService.php`

**Responsabilités:**
- Appels API OpenAI (chat completions)
- Gestion historique conversation
- Comptage tokens, gestion erreurs

**Méthodes:**
```php
chat(array $messages, string $model = 'gpt-3.5-turbo', int $maxTokens = 1000): array
countTokens(string $text): int
formatConversationHistory(Conversation $conversation): array
```

---

### B. Contrôleurs API

#### 6. AuthController.php ⏳
**Localisation:** `app/Http/Controllers/API/AuthController.php`

**Routes:**
- POST /api/auth/register
- POST /api/auth/login
- POST /api/auth/logout
- GET /api/auth/me
- POST /api/auth/refresh-token

**Responsabilités:**
- Enregistrement utilisateurs (email + password)
- Login avec Laravel Sanctum (tokens API)
- Logout (révocation token)
- Récupération profil utilisateur
- Rafraîchissement token

#### 7. ConversationController.php ⏳
**Localisation:** `app/Http/Controllers/API/ConversationController.php`

**Routes:**
- GET /api/conversations (liste)
- POST /api/conversations (créer)
- GET /api/conversations/{id}
- PUT /api/conversations/{id} (renommer)
- DELETE /api/conversations/{id}
- POST /api/conversations/{id}/archive
- POST /api/conversations/{id}/favorite

**Responsabilités:**
- CRUD conversations
- Pagination et filtres (archived, favorites)
- Tri par date récente

#### 8. MessageController.php ⏳
**Localisation:** `app/Http/Controllers/API/MessageController.php`

**Routes:**
- GET /api/conversations/{id}/messages
- POST /api/conversations/{id}/messages
- POST /api/messages/{id}/feedback

**Responsabilités:**
- Envoi message utilisateur
- Génération réponse IA (via RAGService)
- Stockage message + réponse
- Feedback (thumbs up/down)

#### 9. SubscriptionController.php ⏳
**Localisation:** `app/Http/Controllers/API/SubscriptionController.php`

**Routes:**
- GET /api/subscriptions/current
- GET /api/subscriptions/history
- POST /api/subscriptions/cancel
- GET /api/subscriptions/usage

**Responsabilités:**
- Récupération abonnement actif
- Historique abonnements
- Annulation abonnement
- Statistiques d'usage (quotas restants)

#### 10. PaymentController.php ⏳
**Localisation:** `app/Http/Controllers/API/PaymentController.php`

**Routes:**
- POST /api/payments/initiate
- POST /api/payments/webhook (Flutterwave)
- GET /api/payments/history
- GET /api/payments/{id}/status

**Responsabilités:**
- Initialisation paiement Flutterwave
- Webhook verification (HMAC signature)
- Création/activation abonnement après paiement
- Historique paiements

#### 11. PlanController.php ⏳
**Localisation:** `app/Http/Controllers/API/PlanController.php`

**Routes:**
- GET /api/plans
- GET /api/plans/{id}

**Responsabilités:**
- Liste plans disponibles
- Détails plan avec features

#### 12. ReferralController.php ⏳
**Localisation:** `app/Http/Controllers/API/ReferralController.php`

**Routes:**
- GET /api/referrals/my-code
- POST /api/referrals/send-invite
- GET /api/referrals/stats
- GET /api/referrals/rewards
- POST /api/referrals/redeem-reward

**Responsabilités:**
- Récupération code parrainage utilisateur
- Envoi invitations (email/SMS)
- Statistiques parrainages (pending/completed)
- Liste récompenses disponibles
- Utilisation récompense

#### 13. DocumentController.php ⏳
**Localisation:** `app/Http/Controllers/API/DocumentController.php`

**Routes:**
- POST /api/documents/upload
- GET /api/documents/{id}
- DELETE /api/documents/{id}
- POST /api/documents/{id}/analyze

**Responsabilités:**
- Upload PDF utilisateur (max 10MB)
- Stockage sur Cloudflare R2
- Extraction texte (background job)
- Analyse IA du document

#### 14. LegalLibraryController.php ⏳
**Localisation:** `app/Http/Controllers/API/LegalLibraryController.php`

**Routes:**
- GET /api/legal-library/categories
- GET /api/legal-library/documents
- GET /api/legal-library/documents/{id}
- GET /api/legal-library/documents/{id}/download
- POST /api/legal-library/search

**Responsabilités:**
- Liste catégories juridiques
- Liste documents avec pagination/filtres
- Détails document
- Téléchargement PDF (tracking)
- Recherche fulltext

#### 15. UserController.php ⏳
**Localisation:** `app/Http/Controllers/API/UserController.php`

**Routes:**
- GET /api/user/profile
- PUT /api/user/profile
- POST /api/user/avatar
- PUT /api/user/preferences
- POST /api/user/fcm-token

**Responsabilités:**
- Récupération profil
- Mise à jour infos (nom, email)
- Upload avatar
- Préférences notifications
- Enregistrement FCM token (push notifications)

#### 16. WebChatController.php ⏳
**Localisation:** `app/Http/Controllers/API/WebChatController.php`

**Routes:**
- POST /api/web-chat/message
- GET /api/web-chat/quota
- GET /api/web-chat/history

**Responsabilités:**
- **STRATÉGIQUE:** Gestion chat web avec quotas limités
- Vérification quota avant réponse
- Envoi alertes 80% et 100%
- Historique conversations web

---

### C. Middlewares

#### 17. SubscriptionMiddleware.php ⏳
**Localisation:** `app/Http/Middleware/SubscriptionMiddleware.php`

**Responsabilités:**
- Vérifier abonnement actif et non expiré
- Bloquer accès features premium si plan gratuit
- Retourner erreur 403 si quota épuisé

**Routes protégées:**
- POST /api/conversations/{id}/messages (si quota analyses IA épuisé)
- GET /api/legal-library/documents/{id}/download (si quota PDFs épuisé)

#### 18. QuotaMiddleware.php ⏳
**Localisation:** `app/Http/Middleware/QuotaMiddleware.php`

**Responsabilités:**
- Vérifier quotas mensuels (searches, ai_analyses, pdf_downloads)
- Décrémenter quota après action réussie
- Retourner quotas restants dans headers

**Headers de réponse:**
```
X-Quota-Remaining-Searches: 45
X-Quota-Remaining-AI: 8
X-Quota-Remaining-PDFs: 7
X-Quota-Reset-Date: 2024-12-01
```

#### 19. WebChatQuotaMiddleware.php ⏳
**Localisation:** `app/Http/Middleware/WebChatQuotaMiddleware.php`

**Responsabilités:**
- **STRATÉGIQUE:** Vérifier quotas web chat (10/100/200/400)
- Envoyer alertes à 80% et 100%
- Bloquer si quota épuisé avec message conversion

---

### D. Routes API

#### 20. api.php ⏳
**Localisation:** `routes/api.php`

**Structure complète:**
```php
// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (Sanctum auth)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Conversations & Messages
    Route::apiResource('conversations', ConversationController::class);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);
    
    // Plans & Subscriptions
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);
    
    // Payments
    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    
    // Referrals
    Route::get('/referrals/my-code', [ReferralController::class, 'myCode']);
    Route::post('/referrals/send-invite', [ReferralController::class, 'sendInvite']);
    
    // Documents
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    
    // Legal Library
    Route::get('/legal-library/categories', [LegalLibraryController::class, 'categories']);
    Route::get('/legal-library/documents', [LegalLibraryController::class, 'index']);
    
    // User Profile
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    
    // Web Chat (quotas limités)
    Route::middleware(WebChatQuotaMiddleware::class)->group(function () {
        Route::post('/web-chat/message', [WebChatController::class, 'sendMessage']);
    });
});

// Webhook Flutterwave (pas d'auth)
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);
```

---

### E. Jobs Background

#### 21. ProcessDocumentJob.php ⏳
**Localisation:** `app/Jobs/ProcessDocumentJob.php`

**Responsabilités:**
- Extraction texte PDF en background
- Génération embeddings
- Upsert dans Pinecone
- Mise à jour SubmittedDocument

#### 22. ResetMonthlyQuotasJob.php ⏳
**Localisation:** `app/Jobs/ResetMonthlyQuotasJob.php`

**Responsabilités:**
- Cron job mensuel (1er du mois)
- Reset quotas mobile (searches_used, ai_analyses_used, pdf_downloads_used)
- Reset quotas web (requests_used)

#### 23. SendQuotaAlertJob.php ⏳
**Localisation:** `app/Jobs/SendQuotaAlertJob.php`

**Responsabilités:**
- Envoi notification push (FCM)
- Envoi email alerte quota
- Types: 80% atteint, 100% atteint

---

### F. Tests API

#### 24-30. Feature Tests ⏳
**Localisation:** `tests/Feature/API/`

- AuthTest.php
- ConversationTest.php
- MessageTest.php
- SubscriptionTest.php
- PaymentTest.php
- ReferralTest.php
- DocumentTest.php

---

## 🎯 PHASE 3: WIDGET CHAT WEB DOSSY PRO (Semaine 3)

### A. Composant Vue.js

#### 31. ChatWidget.vue ⏳
**Localisation:** `resources/js/components/ChatWidget.vue`

**Fonctionnalités:**
- Interface chat flottante (coin inférieur droit)
- Affichage quota en temps réel
- Liste conversations
- Envoi messages + réponses IA
- Alertes 80% et 100%
- Popup download app mobile

**UI Components:**
- Badge quota (ex: "45/100 requêtes restantes")
- Barre progression quota
- Bouton "Télécharger l'app mobile"
- Modal alerte quota épuisé

#### 32. QuotaBadge.vue ⏳
**Localisation:** `resources/js/components/QuotaBadge.vue`

**Fonctionnalités:**
- Badge dans navigation Dossy Pro
- Couleurs: vert (>50%), orange (20-50%), rouge (<20%)
- Tooltip: "X requêtes restantes ce mois"

#### 33. DownloadAppModal.vue ⏳
**Localisation:** `resources/js/components/DownloadAppModal.vue`

**Fonctionnalités:**
- Modal conversion vers app mobile
- Boutons iOS App Store / Google Play Store
- QR codes pour téléchargement rapide
- Message: "Quota épuisé ? Téléchargez l'app pour accès illimité!"

---

### B. Intégration Layout

#### 34. landingpage.blade.php (modification) ⏳
**Localisation:** `Modules/LandingPage/Resources/views/layouts/landingpage.blade.php`

**Modifications:**
- Injection ChatWidget.vue pour users connectés
- Injection QuotaBadge.vue dans navbar
- Scripts Vue.js

---

### C. API Endpoints Web Chat

#### 35. web-chat.php routes ⏳
**Localisation:** `routes/web.php`

**Routes:**
```php
Route::middleware('auth')->prefix('web-chat')->group(function () {
    Route::get('/quota', [WebChatController::class, 'getQuota']);
    Route::post('/message', [WebChatController::class, 'sendMessage']);
    Route::get('/history', [WebChatController::class, 'history']);
});
```

---

## 🎯 PHASE 4: APPLICATION FLUTTER MOBILE (Semaine 4-6)

### A. Structure Projet Flutter

#### 36. Création projet Flutter ⏳
```bash
flutter create dossy_ia --org cm.dossypro
cd dossy_ia
```

#### 37. Dependencies (pubspec.yaml) ⏳
```yaml
dependencies:
  flutter_riverpod: ^2.4.0
  dio: ^5.3.0
  hive: ^2.2.3
  hive_flutter: ^1.1.0
  flutter_pdfview: ^1.3.0
  flutterwave_standard: ^1.0.8
  firebase_messaging: ^14.6.0
  shared_preferences: ^2.2.0
  cached_network_image: ^3.2.3
  lottie: ^2.5.0
  tabler_icons: ^1.0.1
```

---

### B. Couche Data (repositories + API)

#### 38-45. Data Layer ⏳
**Localisation:** `lib/data/`

- api/api_client.dart (Dio + interceptors)
- api/auth_api.dart
- api/conversation_api.dart
- api/subscription_api.dart
- api/payment_api.dart
- repositories/auth_repository.dart
- repositories/conversation_repository.dart
- models/ (25+ fichiers JSON serialization)

---

### C. Couche Domain (use cases)

#### 46-55. Domain Layer ⏳
**Localisation:** `lib/domain/usecases/`

- login_usecase.dart
- register_usecase.dart
- send_message_usecase.dart
- get_conversations_usecase.dart
- subscribe_plan_usecase.dart
- initiate_payment_usecase.dart
- upload_document_usecase.dart
- download_pdf_usecase.dart
- send_referral_usecase.dart
- get_quota_usecase.dart

---

### D. Couche Presentation (UI + State)

#### 56-80. Presentation Layer ⏳
**Localisation:** `lib/presentation/`

**Screens:**
- screens/splash_screen.dart
- screens/onboarding_screen.dart
- screens/auth/login_screen.dart
- screens/auth/register_screen.dart
- screens/home/home_screen.dart
- screens/chat/chat_list_screen.dart
- screens/chat/chat_screen.dart
- screens/legal_library/library_screen.dart
- screens/legal_library/document_detail_screen.dart
- screens/legal_library/pdf_viewer_screen.dart
- screens/subscription/plans_screen.dart
- screens/subscription/checkout_screen.dart
- screens/subscription/payment_screen.dart
- screens/profile/profile_screen.dart
- screens/profile/settings_screen.dart
- screens/referral/referral_screen.dart

**Providers (Riverpod):**
- providers/auth_provider.dart
- providers/conversation_provider.dart
- providers/subscription_provider.dart
- providers/theme_provider.dart

**Widgets:**
- widgets/message_bubble.dart
- widgets/quota_indicator.dart
- widgets/plan_card.dart
- widgets/document_card.dart

---

### E. Services Flutter

#### 81-85. Services ⏳
**Localisation:** `lib/services/`

- local_storage_service.dart (Hive)
- notification_service.dart (FCM)
- payment_service.dart (Flutterwave)
- pdf_service.dart
- connectivity_service.dart

---

### F. Configuration

#### 86-90. Config Files ⏳

- lib/core/constants/api_constants.dart
- lib/core/theme/app_theme.dart
- lib/core/routes/app_router.dart
- lib/core/utils/validators.dart
- lib/core/utils/formatters.dart

---

## 🎯 PHASE 5: CI/CD & DÉPLOIEMENT (Semaine 7)

### A. Codemagic (iOS)

#### 91. codemagic.yaml ⏳
**Localisation:** Root du projet Flutter

**Configuration:**
- Build iOS automatique
- Tests unitaires
- Signature avec Apple Developer
- Upload TestFlight

### B. GitHub Actions (Android)

#### 92. android-build.yml ⏳
**Localisation:** `.github/workflows/`

**Configuration:**
- Build APK/AAB
- Tests
- Signing avec keystore
- Upload Google Play Console

### C. Documentation

#### 93-95. Docs ⏳

- API_DOCUMENTATION.md (endpoints complets)
- FLUTTER_SETUP.md (guide installation app)
- DEPLOYMENT.md (guide déploiement stores)

---

## 📊 RÉCAPITULATIF FICHIERS

| Catégorie | Fichiers | Status |
|-----------|----------|--------|
| **Phase 1 (Complété)** |
| Migrations | 12 | ✅ |
| Models | 10 | ✅ |
| Seeders | 2 | ✅ |
| **Phase 2 (Backend API)** |
| Services | 5 | ⏳ |
| Controllers | 11 | ⏳ |
| Middlewares | 3 | ⏳ |
| Routes | 1 | ⏳ |
| Jobs | 3 | ⏳ |
| Tests | 7 | ⏳ |
| **Phase 3 (Web Chat)** |
| Vue Components | 3 | ⏳ |
| Layout Integration | 1 | ⏳ |
| **Phase 4 (Flutter App)** |
| Data Layer | 8 | ⏳ |
| Domain Layer | 10 | ⏳ |
| Presentation Layer | 25 | ⏳ |
| Services | 5 | ⏳ |
| Config | 5 | ⏳ |
| **Phase 5 (CI/CD)** |
| CI/CD Config | 2 | ⏳ |
| Documentation | 3 | ⏳ |
| **TOTAL** | **95+** | **25 ✅ / 70+ ⏳** |

---

## ⚡ PRIORITÉS IMMÉDIATES

### Semaine 1-2: Backend API
1. ✅ Services RAG (OpenAI + Pinecone)
2. ✅ Contrôleurs API essentiels (Auth, Conversation, Message)
3. ✅ Middleware quotas
4. ✅ Routes API complètes
5. ✅ Tests API basiques

### Semaine 3: Web Chat Widget
1. ✅ Composant Vue ChatWidget
2. ✅ Système quotas web
3. ✅ Alertes + conversion popup

### Semaine 4-6: Flutter App
1. ✅ Architecture + structure projet
2. ✅ Écrans principaux (Auth, Home, Chat)
3. ✅ Intégration Flutterwave
4. ✅ Mode offline

### Semaine 7: Déploiement
1. ✅ CI/CD Codemagic + GitHub Actions
2. ✅ Tests finaux
3. ✅ Soumission stores

---

**Prochaine commande:** Créer les services RAG et contrôleurs API (Phase 2)
