# 📋 Proposition - Chat AI Juridique pour Dossy Pro

## 🎯 Objectif du module

Créer un assistant juridique IA accessible aux utilisateurs de Dossy Pro avec les capacités suivantes :

1. **Recherche intelligente** dans la bibliothèque juridique
2. **Assistance juridique** via chat conversationnel
3. **Citation de sources** avec extraits des documents
4. **Analyse de documents** uploadés par l'utilisateur
5. **Limitation de requêtes** selon le plan d'abonnement

---

## 🤖 Type d'IA à implémenter

### Option recommandée : **OpenAI GPT-4 avec RAG (Retrieval-Augmented Generation)**

#### Pourquoi GPT-4 ?

✅ **Avantages** :
- Excellente compréhension du français juridique
- Peut citer des sources précises (articles de loi)
- API simple à intégrer avec Laravel
- Support de fichiers PDF via API
- Gestion de contexte longue (128k tokens pour GPT-4 Turbo)
- Modération de contenu intégrée

❌ **Inconvénients** :
- Coût par requête (~$0.01 - $0.03 par requête selon usage)
- Nécessite connexion internet
- Dépendance à un service tiers

#### Architecture RAG (Retrieval-Augmented Generation)

```
┌─────────────────────────────────────────────────────────┐
│                    USER QUERY                           │
│          "Quels sont mes droits en cas de licenciement ?"│
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  ÉTAPE 1: RECHERCHE DANS LA BIBLIOTHÈQUE JURIDIQUE      │
│  - Extraction de mots-clés                              │
│  - Recherche vectorielle (embeddings)                   │
│  - Récupération des documents pertinents                │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  ÉTAPE 2: EXTRACTION DE CONTEXTE                        │
│  - Découpage des PDFs en chunks                         │
│  - Sélection des passages les plus pertinents           │
│  - Préparation du contexte pour l'IA                    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  ÉTAPE 3: GÉNÉRATION DE RÉPONSE (GPT-4)                 │
│  - Contexte : Documents juridiques pertinents           │
│  - Instruction : Répondre avec citations                │
│  - Génération de réponse structurée                     │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  RÉPONSE À L'UTILISATEUR                                │
│  - Réponse juridique détaillée                          │
│  - Citations des articles/documents                     │
│  - Références cliquables vers les PDFs                  │
└─────────────────────────────────────────────────────────┘
```

---

## 🏗️ Architecture technique proposée

### 1. Base de données - Nouvelles tables

#### Table `ai_chat_conversations`
```sql
CREATE TABLE ai_chat_conversations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    title VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Table `ai_chat_messages`
```sql
CREATE TABLE ai_chat_messages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT NOT NULL,
    role ENUM('user', 'assistant', 'system'),
    content TEXT NOT NULL,
    document_references JSON NULL, -- IDs des documents cités
    tokens_used INT DEFAULT 0,
    created_at TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES ai_chat_conversations(id) ON DELETE CASCADE
);
```

#### Table `ai_usage_tracking`
```sql
CREATE TABLE ai_usage_tracking (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    company_id INT NOT NULL,
    plan_id BIGINT NOT NULL,
    month_year VARCHAR(7), -- Format: 2024-11
    requests_count INT DEFAULT 0,
    tokens_used INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id, month_year),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Table `ai_uploaded_documents`
```sql
CREATE TABLE ai_uploaded_documents (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    file_size BIGINT,
    extracted_text TEXT,
    uploaded_at TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES ai_chat_conversations(id) ON DELETE CASCADE
);
```

#### Table `document_embeddings` (pour recherche vectorielle)
```sql
CREATE TABLE document_embeddings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    document_id BIGINT NOT NULL,
    chunk_text TEXT NOT NULL,
    chunk_index INT,
    embedding_vector JSON, -- Vecteur d'embedding OpenAI
    created_at TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES legal_documents(id) ON DELETE CASCADE
);
```

### 2. Configuration des limites par plan

#### Modification de la table `plans` (ou configuration séparée)

**Option A** : Ajouter des colonnes à la table `plans` existante
```sql
ALTER TABLE plans ADD COLUMN ai_requests_limit INT DEFAULT 0;
```

**Option B** : Table de configuration séparée (recommandé)
```sql
CREATE TABLE ai_plan_limits (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    plan_name VARCHAR(50) UNIQUE,
    plan_price DECIMAL(10,2),
    requests_per_year INT,
    requests_per_month INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

INSERT INTO ai_plan_limits (plan_name, plan_price, requests_per_year, requests_per_month) VALUES
('Gratuit', 0, 50, 5),              -- 50 requêtes/an ≈ 5/mois
('Solo', 120000, 600, 50),          -- 600 requêtes/an ≈ 50/mois
('Basic', 240000, 1200, 100),       -- 1200 requêtes/an ≈ 100/mois
('Pro', 480000, 2400, 200);         -- 2400 requêtes/an ≈ 200/mois
```

---

## 🔧 Stack technique

### Backend (Laravel)
```php
// Packages à installer
composer require openai-php/laravel    // Client OpenAI officiel
composer require smalot/pdfparser      // Extraction texte PDF (déjà utilisé?)
composer require league/flysystem-aws-s3-v3  // Si stockage S3
```

### API OpenAI
- **Modèle principal** : `gpt-4-turbo-preview` ou `gpt-4o`
- **Embeddings** : `text-embedding-3-small` (pour recherche vectorielle)
- **Vision** : `gpt-4-vision-preview` (si analyse d'images de documents)

### Frontend
- **Interface chat** : Composant Blade + JavaScript
- **Markdown rendering** : Pour formatage des réponses
- **Upload de fichiers** : Dropzone.js ou similaire
- **Citations cliquables** : Liens vers les PDFs de la bibliothèque

---

## 📊 Processus d'intégration détaillé

### Phase 1 : Préparation (Semaine 1)

#### Étape 1.1 : Indexation de la bibliothèque juridique
```php
// Commande Artisan pour indexer tous les PDFs
php artisan legal:index-documents

Actions :
1. Extraire le texte de chaque PDF
2. Découper en chunks (500-1000 mots)
3. Générer embeddings via OpenAI
4. Stocker dans document_embeddings
```

#### Étape 1.2 : Configuration OpenAI
```php
// .env
OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=org-...
AI_MODEL=gpt-4-turbo-preview
AI_EMBEDDING_MODEL=text-embedding-3-small
AI_MAX_TOKENS=4000
AI_TEMPERATURE=0.3  // Précision juridique
```

#### Étape 1.3 : Création des migrations
```bash
php artisan make:migration create_ai_chat_tables
php artisan make:migration create_ai_plan_limits_table
php artisan make:migration create_document_embeddings_table
```

---

### Phase 2 : Backend (Semaine 2-3)

#### Étape 2.1 : Modèles Laravel
```php
// app/Models/AiChatConversation.php
// app/Models/AiChatMessage.php
// app/Models/AiUsageTracking.php
// app/Models/AiUploadedDocument.php
// app/Models/DocumentEmbedding.php
```

#### Étape 2.2 : Services
```php
// app/Services/OpenAIService.php
class OpenAIService {
    public function chat(array $messages, array $context = [])
    public function generateEmbedding(string $text)
    public function analyzeDocument(string $filePath)
}

// app/Services/LegalSearchService.php
class LegalSearchService {
    public function searchRelevantDocuments(string $query, int $limit = 5)
    public function extractRelevantChunks(int $documentId, string $query)
}

// app/Services/AiUsageLimitService.php
class AiUsageLimitService {
    public function canMakeRequest(User $user): bool
    public function getRemainingRequests(User $user): int
    public function recordUsage(User $user, int $tokensUsed)
}
```

#### Étape 2.3 : Contrôleur
```php
// app/Http/Controllers/AiChatController.php
class AiChatController extends Controller {
    public function index()                    // Liste des conversations
    public function show($id)                  // Afficher une conversation
    public function store(Request $request)    // Nouvelle conversation
    public function sendMessage(Request $request)  // Envoyer un message
    public function uploadDocument(Request $request) // Upload pour analyse
    public function getUsageStats()            // Statistiques d'utilisation
}
```

---

### Phase 3 : Frontend (Semaine 3-4)

#### Étape 3.1 : Interface de chat
```
┌─────────────────────────────────────────────────────────┐
│  Chat AI Juridique                    [Nouvelle conv.]  │
├─────────────────────────────────────────────────────────┤
│ Historique          │  Conversation active              │
│                     │                                   │
│ > Conv. 1           │  👤 Utilisateur:                  │
│   Licenciement      │  Quels sont mes droits...         │
│                     │                                   │
│ > Conv. 2           │  🤖 Assistant:                    │
│   Contrat de trav.  │  Selon le Code du Travail...      │
│                     │  📄 [Article 32 - Code Civil]     │
│ [+ Nouvelle]        │                                   │
│                     │  👤 Utilisateur:                  │
│ Quota: 45/50 ⚠️     │  Peux-tu analyser ce contrat?     │
│                     │  📎 contrat.pdf                   │
│                     │                                   │
│                     │  [📎 Joindre] [Envoyer]           │
└─────────────────────────────────────────────────────────┘
```

#### Étape 3.2 : Composants Blade
```php
// resources/views/ai-chat/
├── index.blade.php         // Liste des conversations
├── chat.blade.php          // Interface de chat
├── partials/
│   ├── message.blade.php   // Message individuel
│   ├── sidebar.blade.php   // Historique
│   └── usage-stats.blade.php // Statistiques
```

#### Étape 3.3 : JavaScript
```javascript
// resources/js/ai-chat.js
- Envoi de messages en temps réel
- Upload de fichiers
- Affichage streaming des réponses (optionnel)
- Formatage Markdown
```

---

### Phase 4 : Fonctionnalités avancées (Semaine 4-5)

#### Feature 1 : Recherche intelligente dans la bibliothèque
```php
Requête : "Articles sur le licenciement abusif"

Processus :
1. Génération embedding de la requête
2. Recherche de similarité dans document_embeddings
3. Récupération des 5 documents les plus pertinents
4. Retour à l'utilisateur avec liens cliquables
```

#### Feature 2 : Réponse avec citations
```php
Prompt système :
"Tu es un assistant juridique expert.
Utilise UNIQUEMENT les documents suivants pour répondre :

[CONTEXTE]
Document: Code du Travail - Article 32
Texte: ...

[/CONTEXTE]

Instructions :
- Cite TOUJOURS tes sources
- Format : [Source: Nom du document, Article X]
- Si tu ne trouves pas de réponse dans les documents, dis-le clairement"
```

#### Feature 3 : Analyse de document uploadé
```php
Flux :
1. User upload "mon_contrat.pdf"
2. Extraction du texte
3. Stockage dans ai_uploaded_documents
4. User pose question : "Ce contrat est-il conforme ?"
5. AI analyse le contrat + compare avec la bibliothèque juridique
6. Réponse avec références légales
```

---

## 💰 Gestion des limites et quotas

### Système de quota proposé

#### Plans et limites
```php
Plan Gratuit (0 FCFA/an)
├── 50 requêtes/an
├── 5 requêtes/mois maximum
├── Pas d'upload de documents
└── Accès bibliothèque juridique de base

Plan Solo (120 000 FCFA/an)
├── 600 requêtes/an
├── 50 requêtes/mois maximum
├── Upload de documents (max 5 MB)
└── Historique 30 jours

Plan Basic (240 000 FCFA/an)
├── 1200 requêtes/an
├── 100 requêtes/mois maximum
├── Upload de documents (max 10 MB)
└── Historique 90 jours

Plan Pro (480 000 FCFA/an)
├── 2400 requêtes/an
├── 200 requêtes/mois maximum
├── Upload de documents (max 20 MB)
└── Historique illimité
```

### Affichage du quota
```php
┌──────────────────────────────────────┐
│  Utilisation du Chat AI              │
├──────────────────────────────────────┤
│  Ce mois : 45 / 50 requêtes  ⚠️      │
│  [████████████░░░░] 90%              │
│                                      │
│  Cette année : 245 / 600 requêtes ✅ │
│  [████░░░░░░░░░░░░] 41%              │
│                                      │
│  [Mettre à niveau]                   │
└──────────────────────────────────────┘
```

### Gestion du dépassement
```php
Si quota dépassé :
1. Bloquer nouvelles requêtes
2. Afficher message :
   "Quota mensuel atteint (50/50)
    - Attendez le mois prochain
    - Ou passez au plan Basic (100 requêtes/mois)"
3. Proposer upgrade de plan
```

---

## 🔒 Sécurité et confidentialité

### Mesures de sécurité

1. **Validation des entrées**
   ```php
   - Limitation taille des messages (2000 caractères)
   - Validation format des fichiers (PDF uniquement)
   - Scan antivirus des uploads (optionnel)
   ```

2. **Protection des données**
   ```php
   - Chiffrement des conversations sensibles
   - Suppression automatique après X jours (selon plan)
   - Pas de stockage de données personnelles sensibles
   ```

3. **Limitation d'abus**
   ```php
   - Rate limiting : 10 requêtes/minute max
   - Détection de spam/abus
   - Blacklist de mots-clés interdits
   ```

4. **Modération OpenAI**
   ```php
   - API Moderation d'OpenAI
   - Détection contenu inapproprié
   - Blocage automatique
   ```

---

## 💵 Estimation des coûts

### Coûts OpenAI (approximatifs)

#### Coûts par requête
```
GPT-4 Turbo :
- Input : $0.01 / 1k tokens
- Output : $0.03 / 1k tokens

Requête moyenne (avec contexte) :
- Input : ~2000 tokens (contexte + question) = $0.02
- Output : ~500 tokens (réponse) = $0.015
Total par requête : ~$0.035 (≈ 18 FCFA)

Embeddings (indexation) :
- text-embedding-3-small : $0.0001 / 1k tokens
- Coût négligeable pour indexation initiale
```

#### Coûts mensuels estimés

```
Plan Gratuit (5 req/mois) :
- 5 × 18 FCFA = 90 FCFA/mois
- 5 utilisateurs = 450 FCFA/mois

Plan Solo (50 req/mois) :
- 50 × 18 FCFA = 900 FCFA/mois
- Revenus : 10 000 FCFA/mois (120k/12)
- Marge : 9 100 FCFA

Plan Basic (100 req/mois) :
- 100 × 18 FCFA = 1 800 FCFA/mois
- Revenus : 20 000 FCFA/mois (240k/12)
- Marge : 18 200 FCFA

Plan Pro (200 req/mois) :
- 200 × 18 FCFA = 3 600 FCFA/mois
- Revenus : 40 000 FCFA/mois (480k/12)
- Marge : 36 400 FCFA
```

**Rentabilité** : ✅ Très bonne marge sur tous les plans

---

## 📱 Interface utilisateur - Navigation

### Menu principal (Sidebar)
```
Legal Library
Chat AI Juridique  ← NOUVEAU
├── Mes conversations
├── Nouvelle conversation
└── Utilisation & Quotas
```

### Permissions
```php
Permission : 'use ai chat'
Assignée aux rôles : company, advocate, client, co advocate

Super Admin :
- Voir statistiques globales d'utilisation
- Configurer les limites par plan
- Modérer les conversations (optionnel)
```

---

## 🚀 Planning de développement

### Semaine 1 : Fondations
- [ ] Configuration OpenAI
- [ ] Création des migrations
- [ ] Création des modèles
- [ ] Indexation des documents existants

### Semaine 2 : Backend
- [ ] Services OpenAI, Search, Usage
- [ ] Contrôleurs
- [ ] Routes
- [ ] Tests unitaires

### Semaine 3 : Frontend
- [ ] Interface de chat
- [ ] Composants Blade
- [ ] JavaScript (envoi messages)
- [ ] Upload de fichiers

### Semaine 4 : Features avancées
- [ ] Recherche intelligente
- [ ] Citations avec sources
- [ ] Analyse de documents
- [ ] Gestion des quotas

### Semaine 5 : Tests & Déploiement
- [ ] Tests d'intégration
- [ ] Tests utilisateurs
- [ ] Documentation
- [ ] Déploiement production

---

## ✅ Points de validation requis

### Questions pour validation

#### 1. **Type d'IA**
- ✅ Valider : OpenAI GPT-4 Turbo
- ❓ Alternative : Autre modèle ? (Claude, Gemini, Mistral?)

#### 2. **Limites de requêtes**
- ✅ Valider les quotas proposés :
  - Gratuit : 5/mois, 50/an
  - Solo : 50/mois, 600/an
  - Basic : 100/mois, 1200/an
  - Pro : 200/mois, 2400/an
- ❓ Ajuster ces limites ?

#### 3. **Fonctionnalités**
- ✅ Recherche dans bibliothèque juridique
- ✅ Réponses avec citations
- ✅ Analyse de documents uploadés
- ❓ Autres features souhaitées ?

#### 4. **Budget OpenAI**
- ✅ Coût estimé : 18 FCFA/requête
- ✅ Marge confortable sur tous les plans
- ❓ Budget mensuel maximum acceptable ?

#### 5. **Sécurité**
- ✅ Conversations privées par utilisateur
- ✅ Suppression automatique après X jours
- ❓ Durée de conservation selon plan ?

#### 6. **Interface**
- ✅ Interface web dans Dossy Pro
- ❓ Version mobile nécessaire ?
- ❓ Notifications push ?

---

## 📋 Checklist de validation

Avant de commencer le développement, veuillez valider :

- [ ] **Type d'IA** : OpenAI GPT-4 Turbo convient
- [ ] **Quotas** : Limites par plan approuvées
- [ ] **Fonctionnalités** : Liste des features validée
- [ ] **Budget** : Coût estimé acceptable
- [ ] **Planning** : 5 semaines de développement OK
- [ ] **Architecture** : Structure base de données approuvée
- [ ] **Sécurité** : Mesures de protection validées

---

## 🎯 Livrable final

Une fois validé, le module comprendra :

✅ **Backend**
- Contrôleurs, Services, Modèles
- API Routes
- Gestion des quotas
- Indexation automatique

✅ **Frontend**
- Interface de chat moderne
- Gestion des conversations
- Upload de documents
- Affichage des quotas

✅ **Documentation**
- Guide utilisateur
- Documentation API
- Guide d'administration

✅ **Tests**
- Tests unitaires
- Tests d'intégration
- Scénarios utilisateurs

---

## 📞 Prochaines étapes

1. **Vous validez** cette proposition (avec ajustements si nécessaire)
2. **Je commence** le développement selon le planning
3. **Validation intermédiaire** à la fin de chaque semaine
4. **Livraison** après tests et validation finale

---

**Qu'en pensez-vous ? Y a-t-il des points à ajuster avant que je commence le développement ?**
