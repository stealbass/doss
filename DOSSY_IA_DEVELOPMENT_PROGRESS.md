# 📱 DOSSY IA - RAPPORT DE DÉVELOPPEMENT

## 🎯 Vue d'ensemble du projet

**Dossy IA** est une application mobile (iOS + Android) avec backend Laravel intégré, développée pour compléter Dossy Pro avec:

### Fonctionnalités principales
- ✅ Assistant IA juridique avec chat + upload/analyse de documents
- ✅ Bibliothèque juridique (PDFs catégorisés, recherche, téléchargement)
- ✅ Authentification et profils utilisateurs
- ✅ 4 plans d'abonnement (0 / 2,000 / 5,000 / 15,000 FCFA/mois)
- ✅ Plans annuels avec 1 mois offert
- ✅ Programme de parrainage (10 filleuls = 1 mois gratuit)
- ✅ Mode hors-ligne (historique conversations)
- ✅ Paiement via Flutterwave (MTN/Orange Money, cartes)

### Fonctionnalité stratégique additionnelle
- ✅ Widget Chat IA pour Dossy Pro web avec quotas limités:
  - Gratuit: 10 requêtes/mois
  - Solo: 100 requêtes/mois
  - Basic: 200 requêtes/mois
  - Pro: 400 requêtes/mois
- ✅ Alertes à 80% et 100% du quota → incitation à télécharger l'app mobile

---

## ✅ PHASE 1: BACKEND - COMPLETÉ

### 📊 Migrations créées (12 fichiers)

#### 1. **mobile_app_plans** ✅
- Définit les 4 plans d'abonnement (Gratuit, Étudiant, Pro, Cabinet)
- Prix mensuel/annuel en FCFA
- Limites fonctionnalités (recherches, analyses IA, téléchargements PDF)
- Configuration IA (GPT-3.5/GPT-4/GPT-4-Turbo, max tokens)

#### 2. **mobile_app_subscriptions** ✅
- Abonnements utilisateurs avec cycle de facturation (monthly/yearly)
- Statuts: active, expired, cancelled, pending
- Quotas d'utilisation mensuels avec compteurs
- Auto-renouvellement

#### 3. **conversations** ✅
- Historique des conversations par utilisateur
- Source: mobile_app ou web_chat
- Statistiques (nombre messages, tokens utilisés)
- Archivage et favoris

#### 4. **messages** ✅
- Messages individuels (user/assistant/system)
- Documents attachés et contexte RAG
- Compteurs de tokens OpenAI (prompt/completion)
- Feedback utilisateur

#### 5. **mobile_app_payments** ✅
- Paiements Flutterwave
- Statuts: pending, successful, failed, cancelled, refunded
- Méthodes: mtn_momo, orange_money, card
- Données webhook pour traçabilité

#### 6. **referrals** ✅
- Système de parrainage avec codes uniques
- Statuts: pending, registered, completed, expired
- Tracking parrain → filleul

#### 7. **referral_rewards** ✅
- Récompenses automatiques (1 mois gratuit tous les 10 parrainages)
- Expiration après 12 mois
- Statuts: pending, earned, redeemed, expired

#### 8. **submitted_documents** ✅
- Documents PDF uploadés par utilisateurs
- Extraction de texte pour analyse IA
- Stockage sur Cloudflare R2
- URLs temporaires (24h)

#### 9. **document_downloads** ✅
- Historique téléchargements bibliothèque juridique
- Tracking device type (iOS/Android/web)
- Statistiques par source (mobile vs web)

#### 10. **web_chat_usage** ✅
- **CLEF DE LA STRATÉGIE DE CONVERSION**
- Quotas mensuels par plan Dossy Pro
- Compteurs de requêtes utilisées/restantes
- Reset automatique mensuel
- Alertes à 80% et 100%

#### 11. **ai_settings** ✅
- Configuration OpenAI (API key, modèles, température)
- Paramètres RAG (mode simple/advanced, Pinecone)
- Prompts système personnalisables
- Limites de sécurité

#### 12. **add_mobile_app_fields_to_users** ✅
- Extension table users existante
- Code de parrainage unique par utilisateur
- Compteur de parrainages réussis
- FCM token pour notifications push
- Préférences notifications
- Tracking activité mobile

---

### 🎨 Modèles Eloquent créés (10 fichiers)

#### Core Models
1. **MobileAppPlan** ✅
   - Relations: subscriptions, payments
   - Scopes: active()
   - Methods: isFree(), hasUnlimited()
   - Calcul prix annuel avec 1 mois offert

2. **MobileAppSubscription** ✅
   - Relations: user, plan, payments
   - Scopes: active(), expired()
   - Methods: canUseFeature(), incrementUsage(), resetQuota()
   - Calcul jours restants

3. **Conversation** ✅
   - Relations: user, messages, submittedDocuments
   - Scopes: active(), favorites(), fromSource()
   - Methods: generateTitle(), updateMessageCount(), updateTokensUsed()
   - SoftDeletes

4. **Message** ✅
   - Relations: conversation, submittedDocuments
   - Scopes: userMessages(), assistantMessages(), withFeedback()
   - Methods: isUserMessage(), hasRagContext(), estimatedCost()

5. **MobileAppPayment** ✅
   - Relations: user, subscription, plan
   - Scopes: successful(), pending(), failed()
   - Methods: markAsSuccessful(), markAsFailed(), getNetAmount()

#### Referral System Models
6. **Referral** ✅
   - Relations: referrer, referred
   - Scopes: completed(), pending(), expired()
   - Methods: generateUniqueCode(), markAsRegistered(), markAsCompleted()
   - Logique auto-création récompense après 10 parrainages

7. **ReferralReward** ✅
   - Relations: user, subscription
   - Scopes: earned(), redeemed(), expired()
   - Methods: redeem(), isExpired(), canBeRedeemed()

#### Document Models
8. **SubmittedDocument** ✅
   - Relations: user, conversation, message
   - Scopes: completed(), processing(), failed()
   - Methods: generateTemporaryUrl(), markAsProcessed(), markAsFailed()
   - SoftDeletes

9. **DocumentDownload** ✅
   - Relations: user
   - Scopes: fromMobile(), fromWeb(), today(), thisMonth()
   - Method: getFormattedSize()

#### Web Chat Model
10. **WebChatUsage** ✅
    - **MODÈLE STRATÉGIQUE POUR CONVERSION**
    - Relations: user
    - Methods: canMakeRequest(), incrementUsage(), resetQuota()
    - Logique alertes automatiques à 80% et 100%
    - Static: getOrCreateForUser()

---

### 🔗 Relations User Model ajoutées ✅

Extension du modèle User existant avec 11 nouvelles relations:

```php
// Abonnements mobiles
activeMobileSubscription()  // Abonnement actif en cours
mobileSubscriptions()       // Tous les abonnements

// Conversations et paiements
conversations()
mobilePayments()

// Système de parrainage
referralsMade()            // En tant que parrain
referralsReceived()        // En tant que filleul
referralRewards()

// Documents
submittedDocuments()
documentDownloads()

// Chat web (STRATÉGIQUE)
webChatUsage()             // Usage mensuel actuel
getOrCreateWebChatUsage()  // Récupère ou crée

// Méthodes utilitaires
getWebChatQuota()          // Calcule quota basé sur plan Dossy Pro
generateReferralCode()     // Génère code parrainage unique
hasMobileSubscription()    // Vérifie abonnement actif
getMobilePlan()            // Récupère plan mobile actif
```

**Mapping quotas web automatique:**
- Plan Gratuit Dossy Pro → 10 requêtes/mois
- Plan Solo → 100 requêtes/mois
- Plan Basic → 200 requêtes/mois
- Plan Pro → 400 requêtes/mois

---

### 🌱 Seeders créés (2 fichiers)

#### 1. MobileAppPlansSeeder ✅

Crée les 4 plans avec données complètes:

| Plan | Prix Mensuel | Prix Annuel | Recherches | Analyses IA | PDFs | Modèle IA |
|------|--------------|-------------|------------|-------------|------|-----------|
| **Gratuit** | 0 FCFA | 0 FCFA | 5 | 2 | 3 | GPT-3.5 |
| **Étudiant** | 2,000 FCFA | 22,000 FCFA | 30 | 10 | 10 | GPT-3.5 |
| **Pro** | 5,000 FCFA | 55,000 FCFA | 100 | 50 | Illimité | GPT-4 |
| **Cabinet** | 15,000 FCFA | 165,000 FCFA | Illimité | Illimité | Illimité | GPT-4 Turbo |

**Note:** Prix annuel = 11 mois (1 mois offert)

#### 2. AiSettingsSeeder ✅

Configuration IA par défaut:
- OpenAI: GPT-3.5-turbo, temperature 0.7, 1000 tokens max
- RAG: Mode advanced activé, top_k=5, similarity_threshold=0.7
- Pinecone: Index dossy-legal-docs
- Prompts système:
  - Assistant juridique spécialisé droit camerounais
  - Analyseur de documents juridiques
- Sécurité: 5000 chars max, 10MB max, PDF uniquement

---

## 📋 PROCHAINES ÉTAPES

### Phase 2: Services RAG & API (À venir)

1. **Service RAG Advanced** (OpenAI Embeddings + Pinecone)
   - EmbeddingService: Génération embeddings 1536-dim
   - PineconeService: Upsert, search par similarité
   - DocumentProcessingService: Extraction texte PDF, chunking
   - RAGService: Orchestration complète (retrieve + generate)

2. **Contrôleurs API Mobile** (~15 fichiers)
   - AuthController: Login/register avec Sanctum tokens
   - ConversationController: CRUD conversations + messages
   - SubscriptionController: Gestion abonnements
   - PaymentController: Flutterwave integration + webhooks
   - ReferralController: Codes parrainage, tracking
   - DocumentController: Upload/download/analyse
   - UserController: Profil, préférences

3. **Routes API** (routes/api.php)
   - Authentification: POST /api/auth/{login,register,logout}
   - Chat: GET/POST /api/conversations, /api/messages
   - Plans: GET /api/plans
   - Abonnements: POST /api/subscriptions, /api/payments
   - Parrainages: GET/POST /api/referrals
   - Documents: POST /api/documents/upload, GET /api/documents/{id}
   - Bibliothèque: GET /api/legal-library

4. **Middlewares**
   - SubscriptionMiddleware: Vérifier abonnement actif
   - QuotaMiddleware: Vérifier quotas restants
   - RateLimitingMiddleware: Protection anti-spam

### Phase 3: Widget Chat Web Dossy Pro (À venir)

1. **Composant Vue.js** (resources/js/components/ChatWidget.vue)
   - Interface chat flottante
   - Affichage quota en temps réel
   - Alertes 80% et 100%
   - Popup download app mobile

2. **Contrôleur Web** (app/Http/Controllers/WebChatController.php)
   - store(): Créer message avec vérification quota
   - checkQuota(): Vérifier requêtes restantes
   - resetQuota(): Cron mensuel

3. **Intégration dans layout**
   - Ajouter widget dans layouts/landingpage.blade.php
   - Afficher uniquement pour utilisateurs connectés
   - Badge quota dans navigation

### Phase 4: Application Flutter (À venir)

1. **Architecture Clean + Riverpod**
   - data/ (repositories, API clients)
   - domain/ (models, use cases)
   - presentation/ (screens, providers)

2. **Écrans principaux** (~25 fichiers)
   - Splash, Onboarding, Login/Register
   - Home, ChatScreen, LegalLibrary
   - Profile, Subscriptions, Payments
   - ReferralScreen, Settings

3. **Intégrations tierces**
   - Flutterwave SDK
   - Hive (offline storage)
   - Dio (HTTP client)
   - flutter_pdfview

### Phase 5: CI/CD & Déploiement (À venir)

1. **Codemagic** (iOS builds)
   - Configuration codemagic.yaml
   - Apple Developer Account
   - Provisioning profiles

2. **GitHub Actions** (Android builds)
   - Build APK/AAB
   - Tests automatiques

3. **Stores**
   - Google Play Store (Android)
   - Apple App Store (iOS)

---

## 🔧 CONFIGURATION REQUISE

### Variables d'environnement à ajouter dans .env:

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-proj-...

# Pinecone Configuration (pour RAG Advanced)
PINECONE_API_KEY=...
PINECONE_ENVIRONMENT=gcp-starter
PINECONE_INDEX_NAME=dossy-legal-docs

# Flutterwave Configuration
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK-...
FLUTTERWAVE_SECRET_KEY=FLWSECK-...
FLUTTERWAVE_ENCRYPTION_KEY=FLWSECK_TEST...

# Firebase Configuration (pour notifications push)
FCM_SERVER_KEY=...
```

### Commandes à exécuter:

```bash
# 1. Exécuter les migrations
php artisan migrate

# 2. Seeder les plans mobiles
php artisan db:seed --class=MobileAppPlansSeeder

# 3. Seeder les paramètres IA
php artisan db:seed --class=AiSettingsSeeder

# 4. Installer dépendances Composer supplémentaires
composer require openai-php/laravel
composer require pinecone/pinecone-php-client
composer require smalot/pdfparser

# 5. Publier configuration OpenAI
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"
```

---

## 📊 SCHÉMA BASE DE DONNÉES

```
users (existante)
  ├── referral_code (nouveau)
  ├── successful_referrals_count (nouveau)
  └── fcm_token (nouveau)

mobile_app_plans
  └── mobile_app_subscriptions
      └── mobile_app_payments

conversations
  ├── messages
  └── submitted_documents

referrals
  └── referral_rewards

document_downloads

web_chat_usage (STRATÉGIQUE pour conversion)

ai_settings (configuration globale)
```

---

## 🎯 STRATÉGIE DE CONVERSION WEB → MOBILE

### Parcours utilisateur:

1. **Utilisateur Dossy Pro web** commence avec quota gratuit/limité
2. **Usage du chat web** décrémente quota mensuel
3. **Alerte à 80%** → Notification: "Plus que X requêtes ce mois"
4. **Alerte à 100%** → Popup:
   - "Quota épuisé, téléchargez l'app mobile pour un accès illimité!"
   - Bouton iOS / Bouton Android
   - "Ou attendez le reset le 1er du mois prochain"
5. **Download app** → Inscription avec code promo?
6. **Upgrade vers plan payant** → Accès complet

### Métriques à tracker:

- Taux conversion web chat → app download
- Taux conversion app download → abonnement payant
- Moyenne requêtes par utilisateur par plan
- Parrainages réussis par utilisateur

---

## ✅ RÉSUMÉ PROGRESSION

| Phase | Tâche | Statut | Fichiers |
|-------|-------|--------|----------|
| **1** | Migrations | ✅ Complété | 12 fichiers |
| **1** | Modèles Eloquent | ✅ Complété | 10 fichiers |
| **1** | Extension User Model | ✅ Complété | 1 fichier |
| **1** | Seeders | ✅ Complété | 2 fichiers |
| **2** | Services RAG | ⏳ À faire | ~5 fichiers |
| **2** | Contrôleurs API | ⏳ À faire | ~15 fichiers |
| **2** | Routes API | ⏳ À faire | 1 fichier |
| **2** | Middlewares | ⏳ À faire | 3 fichiers |
| **3** | Widget Chat Web | ⏳ À faire | ~3 fichiers |
| **4** | App Flutter | ⏳ À faire | ~100 fichiers |
| **5** | CI/CD | ⏳ À faire | ~5 fichiers |

**Total fichiers créés:** 25 / ~150  
**Progression globale:** ~17%  
**Phase 1 (Backend base):** ✅ 100% COMPLÉTÉ

---

## 📝 NOTES IMPORTANTES

### Différences RAG Simple vs Advanced

**RAG Simple** (1 appel OpenAI):
- Recherche MySQL FULLTEXT sur bibliothèque juridique
- Extraction texte des PDFs matchés
- 1 appel OpenAI GPT-4 avec contexte

**RAG Advanced** (2 appels OpenAI):
- 1er appel: Génération embedding requête utilisateur (text-embedding-3-small)
- Recherche similarité vectorielle dans Pinecone
- 2ème appel: GPT-4 avec documents les plus pertinents
- Plus précis mais plus coûteux

### Pricing OpenAI estimé

**Plans gratuit/étudiant (GPT-3.5):**
- ~$0.001 par requête (2000 tokens)
- 2000 requêtes/mois = ~$2

**Plan Pro (GPT-4):**
- ~$0.03 par requête (2000 tokens)
- 5000 requêtes/mois = ~$150

**Plan Cabinet (GPT-4 Turbo):**
- ~$0.01 par requête (4000 tokens)
- Illimité = variable

### Cloudflare R2 (déjà configuré)

- Stockage PDF utilisateurs (submitted_documents)
- Zéro frais egress (vs S3)
- URLs temporaires 24h

---

## 🚀 COMMANDE SUIVANTE

Pour continuer le développement:

```bash
# Phase 2: Créer les services RAG et contrôleurs API
```

---

**Dernière mise à jour:** 2024-11-22  
**Développeur:** Claude AI  
**Projet:** Dossy IA Mobile App + Web Chat Integration
