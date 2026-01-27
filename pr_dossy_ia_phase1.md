# 📱 Dossy IA - Phase 1: Infrastructure Backend Complete

## 🎯 Vue d'ensemble

Cette PR introduit l'infrastructure backend complète pour **Dossy IA**, l'application mobile (iOS + Android) qui complète Dossy Pro avec un assistant juridique IA et une stratégie de conversion web → mobile.

## ✅ Fonctionnalités implémentées

### 📊 Base de données (12 migrations)

#### Système d'abonnement mobile
- **mobile_app_plans**: 4 plans (Gratuit/Étudiant/Pro/Cabinet) avec pricing et limites
- **mobile_app_subscriptions**: Gestion abonnements avec quotas mensuels auto-reset
- **mobile_app_payments**: Intégration Flutterwave (MTN Mobile Money, Orange Money, Cartes)

#### Chat IA & Conversations
- **conversations**: Historique complet (source: mobile_app ou web_chat)
- **messages**: Messages avec role (user/assistant), contexte RAG, comptage tokens OpenAI
- **submitted_documents**: PDFs uploadés par utilisateurs avec extraction texte
- **document_downloads**: Tracking téléchargements bibliothèque juridique

#### Système de parrainage
- **referrals**: Codes uniques, tracking parrain → filleul
- **referral_rewards**: Auto-création récompense après 10 parrainages réussis

#### ⭐ STRATÉGIE DE CONVERSION (Clef du projet)
- **web_chat_usage**: Quotas web limités (10/100/200/400 par plan Dossy Pro)
  - Alertes automatiques à 80% et 100%
  - Popup téléchargement app mobile quand quota épuisé
  - Reset mensuel automatique

#### Configuration IA
- **ai_settings**: OpenAI (GPT-3.5/4/4-Turbo), RAG (Simple/Advanced), Pinecone
- **add_mobile_app_fields_to_users**: Extension table users (FCM token, referral_code, etc.)

### 🎨 Modèles Eloquent (11 fichiers)

Tous les modèles avec:
- Relations complètes (BelongsTo, HasMany, HasOne)
- Scopes utiles (active, expired, completed, etc.)
- Méthodes métier (canUseFeature, incrementUsage, resetQuota, etc.)
- Casts appropriés (datetime, boolean, array, json)

**Highlight:** `WebChatUsage` avec logique alertes automatiques pour conversion

### 🌱 Seeders (2 fichiers)

- **MobileAppPlansSeeder**: 4 plans avec données complètes
  - Gratuit: 0 FCFA (5 recherches, 2 analyses IA, 3 PDFs) - GPT-3.5
  - Étudiant: 2,000/mois ou 22,000/an (30/10/10) - GPT-3.5
  - Pro: 5,000/mois ou 55,000/an (100/50/∞) - GPT-4
  - Cabinet: 15,000/mois ou 165,000/an (∞/∞/∞) - GPT-4 Turbo
  - Note: Prix annuel = 11 mois (1 mois offert)

- **AiSettingsSeeder**: Configuration IA par défaut (OpenAI, Pinecone, prompts système)

### 📚 Documentation (4 fichiers)

- **DOSSY_IA_DEVELOPMENT_PROGRESS.md** (14KB): Rapport technique complet
- **DOSSY_IA_TODO.md** (17KB): Roadmap détaillée 7 semaines (95+ fichiers à créer)
- **DOSSY_IA_FILES_CREATED.md** (17KB): Arborescence et détails migrations/modèles
- **DOSSY_IA_RESUME.txt** (10KB): Résumé ultra-rapide pour démarrage

## 🎯 Stratégie de conversion Web → Mobile

**Objectif:** Convertir utilisateurs Dossy Pro gratuits en abonnés mobiles payants

**Parcours:**
1. Utilisateur Dossy Pro utilise chat web → quota décrémente (10/100/200/400 selon plan)
2. Alerte à 80% → "Plus que X requêtes ce mois"
3. Alerte à 100% → Popup: **"Téléchargez l'app mobile pour accès illimité!"**
4. Download app → Inscription
5. Upgrade plan payant → Accès complet

## 💰 Plans d'abonnement

| Plan | Prix/mois | Prix/an | Recherches | Analyses IA | PDFs | Modèle IA |
|------|-----------|---------|------------|-------------|------|-----------|
| **Gratuit** | 0 FCFA | 0 FCFA | 5 | 2 | 3 | GPT-3.5 (1000 tokens) |
| **Étudiant** | 2,000 FCFA | 22,000 FCFA* | 30 | 10 | 10 | GPT-3.5 (2000 tokens) |
| **Pro** | 5,000 FCFA | 55,000 FCFA* | 100 | 50 | ∞ | GPT-4 (4000 tokens) |
| **Cabinet** | 15,000 FCFA | 165,000 FCFA* | ∞ | ∞ | ∞ | GPT-4 Turbo (8000 tokens) |

*Prix annuel = 11 mois (1 mois offert)

## 🔧 Technologies

- **Backend:** Laravel 11 + MySQL 8.0
- **IA:** OpenAI GPT-3.5/4/4-Turbo, RAG Advanced (Pinecone embeddings 1536-dim)
- **Paiement:** Flutterwave (MTN Mobile Money, Orange Money, Cartes Visa/Mastercard)
- **Stockage:** Cloudflare R2 (PDFs utilisateurs)
- **Mobile:** Flutter 3.24+ (iOS + Android) - À venir Phase 4

## 📊 Fichiers modifiés/créés

### Migrations (12 fichiers)
```
database/migrations/2025_11_21_000001_create_mobile_app_plans_table.php
database/migrations/2025_11_21_000002_create_mobile_app_subscriptions_table.php
database/migrations/2025_11_21_000003_create_conversations_table.php
database/migrations/2025_11_21_000004_create_messages_table.php
database/migrations/2025_11_21_000005_create_mobile_app_payments_table.php
database/migrations/2025_11_21_000006_create_referrals_table.php
database/migrations/2025_11_21_000007_create_referral_rewards_table.php
database/migrations/2025_11_21_000008_create_submitted_documents_table.php
database/migrations/2025_11_21_000009_create_document_downloads_table.php
database/migrations/2025_11_21_000010_create_web_chat_usage_table.php ⭐
database/migrations/2025_11_21_000011_create_ai_settings_table.php
database/migrations/2025_11_21_000012_add_mobile_app_fields_to_users_table.php
```

### Modèles (11 fichiers)
```
app/Models/MobileAppPlan.php
app/Models/MobileAppSubscription.php
app/Models/Conversation.php
app/Models/Message.php
app/Models/MobileAppPayment.php
app/Models/Referral.php
app/Models/ReferralReward.php
app/Models/SubmittedDocument.php
app/Models/DocumentDownload.php
app/Models/WebChatUsage.php ⭐
app/Models/User.php (modifié: + 11 relations)
```

### Seeders (2 fichiers)
```
database/seeders/MobileAppPlansSeeder.php
database/seeders/AiSettingsSeeder.php
```

### Documentation (4 fichiers)
```
DOSSY_IA_DEVELOPMENT_PROGRESS.md
DOSSY_IA_TODO.md
DOSSY_IA_FILES_CREATED.md
DOSSY_IA_RESUME.txt
```

## 🧪 Tests à effectuer

### Avant merge:
1. ✅ Vérifier migrations sans erreur: `php artisan migrate`
2. ✅ Exécuter seeders: `php artisan db:seed --class=MobileAppPlansSeeder`
3. ✅ Vérifier relations Eloquent dans Tinker
4. ✅ Valider structure BDD (index, foreign keys)

### Après merge:
- Sur serveur AlwaysData: exécuter migrations
- Configurer .env (OPENAI_API_KEY, PINECONE_API_KEY, FLUTTERWAVE_*)
- Tester création plans via seeder

## 📋 Prochaines étapes (Phase 2)

**30 fichiers à créer:**
- 5 Services RAG (EmbeddingService, PineconeService, RAGService, etc.)
- 11 Contrôleurs API (Auth, Conversation, Message, Payment, etc.)
- 3 Middlewares (Subscription, Quota, WebChatQuota)
- 1 Fichier routes API (50+ endpoints)
- 3 Jobs background (ProcessDocument, ResetQuotas, SendAlerts)
- 7 Tests unitaires

## ⚠️ Configuration requise

**Variables .env à ajouter:**
```env
# OpenAI
OPENAI_API_KEY=sk-proj-...

# Pinecone (RAG Advanced)
PINECONE_API_KEY=...
PINECONE_ENVIRONMENT=gcp-starter
PINECONE_INDEX_NAME=dossy-legal-docs

# Flutterwave
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK-...
FLUTTERWAVE_SECRET_KEY=FLWSECK-...
FLUTTERWAVE_ENCRYPTION_KEY=...

# Firebase (notifications push)
FCM_SERVER_KEY=...
```

**Dépendances Composer (Phase 2):**
```bash
composer require openai-php/laravel
composer require pinecone/pinecone-php-client
composer require smalot/pdfparser
```

## 📈 Impact sur le projet

### Ajouts:
- 28 nouveaux fichiers
- 12 nouvelles tables BDD
- 10 nouveaux modèles Eloquent
- Infrastructure complète pour app mobile

### Modifications:
- Table `users` étendue (8 colonnes, 11 relations)

### Compatibilité:
- ✅ Pas de breaking changes sur Dossy Pro existant
- ✅ Tables séparées (pas de modification tables existantes sauf users)
- ✅ Compatible PHP 8.2, Laravel 11

## 🎯 Métriques à tracker (après déploiement complet)

- Taux conversion web chat → app download
- Taux conversion app download → abonnement payant
- Moyenne requêtes/utilisateur/plan
- Coûts OpenAI par plan
- Parrainages réussis par utilisateur

## ✅ Checklist avant merge

- [x] Migrations testées localement
- [x] Modèles avec relations complètes
- [x] Seeders fonctionnels
- [x] Documentation complète
- [x] Aucun breaking change
- [x] Compatible PHP 8.2
- [x] Code formatté PSR-12
- [ ] Tests unitaires (Phase 2)

## 📝 Notes

Cette PR pose les fondations backend pour Dossy IA. Les phases suivantes ajouteront:
- Phase 2: Services RAG + API REST (2 semaines)
- Phase 3: Widget chat web Dossy Pro (1 semaine)
- Phase 4: App Flutter mobile (3 semaines)
- Phase 5: CI/CD + déploiement stores (1 semaine)

**Progression globale:** 17% (28/150+ fichiers)

---

**Prêt pour review et merge** ✅
