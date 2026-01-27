# 🚀 Déploiement Phase 2 - RAG Services + Mobile API

## ✅ Phase 2 - COMPLÈTE

**Status** : ✅ 100% TERMINÉ  
**Date** : 2025-11-27  
**Pull Request** : https://github.com/stealbass/doss/pull/10

---

## 📦 Fichiers Créés (15 fichiers)

### **Services RAG** (3 fichiers)
- `app/Services/SimpleRagService.php` - RAG MySQL FULLTEXT
- `app/Services/AdvancedRagService.php` - RAG OpenAI + Pinecone
- `app/Services/OpenAIService.php` - Chat GPT integration

### **Contrôleurs API Mobile** (5 fichiers)
- `app/Http/Controllers/Api/Mobile/AuthController.php`
- `app/Http/Controllers/Api/Mobile/ChatController.php`
- `app/Http/Controllers/Api/Mobile/DocumentController.php`
- `app/Http/Controllers/Api/Mobile/SubscriptionController.php`
- `app/Http/Controllers/Api/Mobile/ReferralController.php`

### **Routes & Documentation** (7 fichiers)
- `routes/api.php` - API routes (28 routes)
- `API_DOCUMENTATION_MOBILE.md` - Documentation API complète
- `DOSSY_IA_API.postman_collection.json` - Collection Postman
- `API_TESTING_GUIDE.md` - Guide de test
- `PHASE_2_COMPLETE_RESUME.md` - Résumé Phase 2
- `DEPLOIEMENT_PHASE_2.md` - Ce fichier
- Plus documentation R2 (10+ fichiers)

---

## 🔧 Instructions de Déploiement

### **Étape 1 : Connexion SSH**

```bash
ssh utilisateur@ssh-utilisateur.alwaysdata.net
cd ~/www/
```

---

### **Étape 2 : Pull Latest Code**

```bash
git fetch origin
git checkout main
git pull origin main
```

Ou si vous voulez tester d'abord la branche `genspark_ai_developer` :

```bash
git fetch origin
git checkout genspark_ai_developer
git pull origin genspark_ai_developer
```

---

### **Étape 3 : Installer Dépendances**

```bash
composer install --optimize-autoloader --no-dev
```

---

### **Étape 4 : Vérifier Variables d'Environnement**

Éditer `.env` et ajouter :

```env
# OpenAI (OBLIGATOIRE pour RAG)
OPENAI_API_KEY=sk-proj-YOUR_KEY_HERE

# Pinecone (OBLIGATOIRE pour RAG Advanced)
PINECONE_API_KEY=YOUR_PINECONE_KEY
PINECONE_ENVIRONMENT=us-east-1
PINECONE_INDEX=dossy-documents

# Cloudflare R2 (déjà configuré normalement)
# Vérifier que r2_endpoint est correct dans la base de données
```

**⚠️ IMPORTANT** : Les clés OpenAI et Pinecone sont **OBLIGATOIRES** pour le fonctionnement de l'API Mobile.

---

### **Étape 5 : Migrations Base de Données**

```bash
php artisan migrate
```

**Note** : Les migrations de la Phase 1 doivent déjà être exécutées. Si ce n'est pas le cas :

```bash
php artisan migrate:fresh --seed
```

**⚠️ ATTENTION** : `migrate:fresh` supprime TOUTES les données !

---

### **Étape 6 : Seeders**

#### 6.1 Plans d'Abonnement

```bash
php artisan db:seed --class=MobileAppPlansSeeder
```

**Vérification** :

```bash
php artisan tinker
>>> MobileAppPlan::all()->pluck('name', 'price');
```

Résultat attendu :
```php
[
  0 => "Gratuit",
  2000 => "Étudiant",
  5000 => "Pro",
  15000 => "Cabinet"
]
```

---

#### 6.2 Configuration AI (Optionnel)

```bash
php artisan db:seed --class=AiSettingsSeeder
```

Ce seeder configure les paramètres par défaut pour OpenAI (model, temperature, max_tokens).

---

### **Étape 7 : Vider Cache Laravel**

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

### **Étape 8 : Permissions**

```bash
chmod -R 775 storage bootstrap/cache
```

---

### **Étape 9 : Tester Configuration R2**

```bash
php test_r2_connection.php
```

**Résultat attendu** :
```
✅ R2 Configuration Loaded
✅ R2 Connection Successful
✅ All checks passed
```

Si erreur :
```bash
php check_r2_endpoint.php
```

Vérifier que l'endpoint R2 dans la base de données est au format :
```
https://<ACCOUNT_ID>.r2.cloudflarestorage.com
```

---

### **Étape 10 : Tester API Mobile**

#### 10.1 Test Register

```bash
curl -X POST https://dossy.alwaysdata.net/api/mobile/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+237670000000"
  }'
```

**Résultat attendu** : 200 avec token et plan "Gratuit"

---

#### 10.2 Test Login

```bash
curl -X POST https://dossy.alwaysdata.net/api/mobile/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

Copier le `token` de la réponse pour les tests suivants.

---

#### 10.3 Test Profile

```bash
curl -X GET https://dossy.alwaysdata.net/api/mobile/profile \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Résultat attendu** : Profil utilisateur avec quotas (10/10/5)

---

#### 10.4 Test Chat AI (avec RAG)

**Prérequis** : OPENAI_API_KEY configurée

```bash
# 1. Créer conversation
curl -X POST https://dossy.alwaysdata.net/api/mobile/chat/conversation \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"title": "Test RAG"}'

# 2. Envoyer message avec RAG
curl -X POST https://dossy.alwaysdata.net/api/mobile/chat/send \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "conversation_id": 1,
    "message": "Qu'\''est-ce qu'\''un contrat synallagmatique ?",
    "use_rag": true,
    "rag_type": "simple"
  }'
```

**Résultat attendu** : Réponse AI avec contexte juridique extrait de `legal_documents`

---

#### 10.5 Test Upload Document (avec Pinecone)

**Prérequis** : PINECONE_API_KEY configurée

```bash
curl -X POST https://dossy.alwaysdata.net/api/mobile/documents/upload \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "file=@/path/to/test.pdf" \
  -F "title=Test Document" \
  -F "description=Upload test"
```

**Résultat attendu** : Document uploadé sur R2 + indexé dans Pinecone

---

### **Étape 11 : Vérifications Finales**

#### ✅ Checklist Déploiement

- [ ] Code pulled (`git pull`)
- [ ] Composer install
- [ ] Variables `.env` configurées (OpenAI, Pinecone, R2)
- [ ] Migrations exécutées
- [ ] Seeders exécutés (Plans, AI Settings)
- [ ] Cache vidé
- [ ] Permissions OK
- [ ] R2 testé (connexion + endpoint)
- [ ] API Register testé
- [ ] API Login testé
- [ ] API Profile testé
- [ ] API Chat AI testé (avec RAG)
- [ ] API Upload Document testé (avec Pinecone)

---

## 🔐 Variables d'Environnement Requises

### **Obligatoires**

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dossy.alwaysdata.net

# Database (déjà configuré)
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# OpenAI (OBLIGATOIRE)
OPENAI_API_KEY=sk-proj-YOUR_KEY_HERE

# Pinecone (OBLIGATOIRE)
PINECONE_API_KEY=YOUR_KEY
PINECONE_ENVIRONMENT=us-east-1
PINECONE_INDEX=dossy-documents
```

### **Optionnelles (Phase 4)**

```env
# Flutterwave (TODO Phase 4)
FLUTTERWAVE_PUBLIC_KEY=...
FLUTTERWAVE_SECRET_KEY=...
FLUTTERWAVE_ENCRYPTION_KEY=...

# Firebase Cloud Messaging (TODO Phase 4)
FCM_SERVER_KEY=...
```

---

## 🗄️ Configuration Base de Données

### **Vérifier Storage Setting**

```bash
php artisan tinker
>>> Setting::where('key', 'storage_setting')->first()->value;
```

**Résultat attendu** : `"r2"`

Si différent :
```php
>>> Setting::where('key', 'storage_setting')->update(['value' => 'r2']);
```

---

### **Vérifier R2 Endpoint**

```bash
php artisan tinker
>>> Setting::where('key', 'r2_endpoint')->first()->value;
```

**Format attendu** : `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`

Si incorrect :
```php
>>> Setting::where('key', 'r2_endpoint')->update(['value' => 'https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com']);
```

---

### **Vérifier R2 URL**

```bash
php artisan tinker
>>> Setting::where('key', 'r2_url')->first()->value;
```

**Résultat attendu** : `https://files.dossypro.com`

---

## 📊 Architecture RAG

### **RAG Simple (Legal Library)**

```
User Query → MySQL FULLTEXT → Top 5 Documents → OpenAI GPT → Response
```

**Base de données** : `legal_documents`  
**Recherche** : MySQL FULLTEXT index  
**Modèle AI** : gpt-3.5-turbo / gpt-4

---

### **RAG Advanced (User Documents)**

```
User Query → OpenAI Embeddings → Pinecone Search → Top 5 Chunks → OpenAI GPT → Response
```

**Base de données** : `submitted_documents`  
**Embeddings** : text-embedding-3-small (1536 dimensions)  
**Vector DB** : Pinecone  
**Modèle AI** : gpt-3.5-turbo / gpt-4

---

### **RAG Both (Combined)**

```
User Query → [Simple RAG (1000 tokens) + Advanced RAG (1000 tokens)] → Combined Context → OpenAI GPT → Response
```

**Contexte total** : 2000 tokens max  
**Usage** : Questions hybrides (ex: "Compare mon contrat avec le code du travail")

---

## 🧪 Tests Postman

### **Importer Collection**

1. Télécharger `DOSSY_IA_API.postman_collection.json`
2. Ouvrir Postman Desktop/Web
3. Import → Select File
4. Collection importée avec 33 requêtes pré-configurées

### **Variables Automatiques**

- `base_url` : `https://dossy.alwaysdata.net/api/mobile`
- `access_token` : Auto-rempli après login
- `conversation_id` : Auto-rempli après création
- `document_id` : Auto-rempli après upload
- `payment_id` : Auto-rempli après initiation

---

## 🐛 Troubleshooting

### **Erreur : "OpenAI API key not configured"**

**Solution** :
```bash
# Vérifier .env
grep OPENAI_API_KEY .env

# Ajouter si manquant
echo "OPENAI_API_KEY=sk-proj-YOUR_KEY" >> .env

# Vider cache
php artisan config:clear
```

---

### **Erreur : "Pinecone API key not configured"**

**Solution** :
```bash
# Ajouter dans .env
PINECONE_API_KEY=YOUR_KEY
PINECONE_ENVIRONMENT=us-east-1
PINECONE_INDEX=dossy-documents

# Vider cache
php artisan config:clear
```

---

### **Erreur : "cURL error 6: Could not resolve host"**

**Cause** : Endpoint R2 malformé

**Solution** :
```bash
php check_r2_endpoint.php
```

Corriger dans la base de données ou via Admin Settings.

---

### **Erreur : "Storage disk [r2] not configured"**

**Cause** : `storage_setting` n'est pas `'r2'` dans la base de données

**Solution** :
```bash
php artisan tinker
>>> Setting::where('key', 'storage_setting')->update(['value' => 'r2']);
>>> exit

php artisan cache:clear
```

---

### **Erreur : "AI analysis quota exceeded"**

**Cause** : Quotas plan gratuit épuisés (10 analyses max)

**Solution** :
1. Tester avec un nouveau compte
2. Ou mettre à jour le plan :
```bash
php artisan tinker
>>> $user = User::find(1);
>>> $subscription = $user->activeSubscription;
>>> $subscription->mobile_app_plan_id = 2; // Plan Étudiant
>>> $subscription->save();
>>> $subscription->resetQuotas();
```

---

## 📈 Métriques de Succès

### **Backend API**
- ✅ 28 routes API fonctionnelles
- ✅ Authentication Laravel Sanctum
- ✅ RAG Simple (MySQL FULLTEXT)
- ✅ RAG Advanced (OpenAI + Pinecone)
- ✅ 4 plans d'abonnement
- ✅ Système de quotas
- ✅ Système de parrainage

### **Documentation**
- ✅ API Documentation (24KB)
- ✅ Postman Collection (33 requêtes)
- ✅ Testing Guide complet
- ✅ Troubleshooting docs

### **Cloudflare R2**
- ✅ Images site web
- ✅ Documents juridiques (legal_documents)
- ✅ Documents utilisateur (submitted_documents)
- ✅ Configuration dynamique

---

## 🚀 Prochaines Étapes (Phase 3)

### **Flutter Mobile App**

1. **Setup Projet**
   - Flutter 3.24+
   - State Management (Provider/Riverpod)
   - HTTP Client (Dio)

2. **Écrans UI**
   - Login/Register
   - Home/Dashboard
   - Chat AI avec RAG
   - Upload Documents
   - Profile/Subscription
   - Referral

3. **Intégration API**
   - Connexion API Mobile
   - Gestion tokens Sanctum
   - Gestion quotas temps réel

4. **Tests**
   - Widget tests
   - Integration tests
   - API tests

---

## 📞 Support

**Issues GitHub** : https://github.com/stealbass/doss/issues  
**Pull Request** : https://github.com/stealbass/doss/pull/10  
**Documentation** : `API_DOCUMENTATION_MOBILE.md`

---

**Date** : 2025-11-27  
**Phase** : 2 - RAG Services + Mobile API  
**Status** : ✅ 100% COMPLÈTE  
**Progression Globale** : 30% (2/7 phases)
