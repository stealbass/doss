# 🧪 Dossy IA - Guide de Test API

Ce guide vous accompagne pour tester l'API Mobile Dossy IA de manière complète et structurée.

---

## 📦 Prérequis

### 1. Installation Postman

- Télécharger [Postman Desktop](https://www.postman.com/downloads/)
- Ou utiliser [Postman Web](https://web.postman.com/)

### 2. Importer la Collection

1. Ouvrir Postman
2. Cliquer sur **Import**
3. Sélectionner le fichier `DOSSY_IA_API.postman_collection.json`
4. La collection apparaît dans la sidebar

### 3. Configuration Variables

Variables automatiques (pas besoin de configurer) :
- `base_url` : `https://dossy.alwaysdata.net/api/mobile`
- `access_token` : Auto-rempli après login
- `conversation_id` : Auto-rempli après création conversation
- `document_id` : Auto-rempli après upload document
- `payment_id` : Auto-rempli après initiation paiement

---

## 🔐 Flux de Test Complet

### **Étape 1 : Authentication**

#### 1.1 Register User

**Request** :
```bash
POST /api/mobile/register
```

**Body** :
```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+237670000000"
}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "message": "User registered successfully with Free plan",
  "data": {
    "user": { ... },
    "token": "1|abc123...",
    "subscription": {
      "plan_name": "Gratuit",
      "status": "active"
    }
  }
}
```

✅ **Vérifications** :
- Token généré automatiquement
- Plan gratuit assigné
- Quotas initialisés (10/10/5)

---

#### 1.2 Login User

**Request** :
```bash
POST /api/mobile/login
```

**Body** :
```json
{
  "email": "test@example.com",
  "password": "password123"
}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "2|xyz789...",
    "subscription": { ... }
  }
}
```

✅ **Vérifications** :
- Token différent du précédent
- Variable `{{access_token}}` mise à jour automatiquement

---

#### 1.3 Get Profile

**Request** :
```bash
GET /api/mobile/profile
Authorization: Bearer {{access_token}}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "user": { ... },
    "subscription": {
      "quotas": {
        "searches": { "used": 0, "limit": 10 },
        "ai_analyses": { "used": 0, "limit": 10 },
        "pdf_downloads": { "used": 0, "limit": 5 }
      }
    },
    "statistics": {
      "total_conversations": 0,
      "total_messages": 0,
      "total_documents_uploaded": 0
    }
  }
}
```

✅ **Vérifications** :
- Quotas non consommés
- Statistiques à 0

---

### **Étape 2 : Chat AI**

#### 2.1 Create Conversation

**Request** :
```bash
POST /api/mobile/chat/conversation
Authorization: Bearer {{access_token}}
```

**Body** :
```json
{
  "title": "Question juridique test"
}
```

**Expected Response (201)** :
```json
{
  "success": true,
  "data": {
    "conversation": {
      "id": 1,
      "title": "Question juridique test"
    }
  }
}
```

✅ **Vérifications** :
- Variable `{{conversation_id}}` mise à jour automatiquement

---

#### 2.2 Send Message - No RAG

**Request** :
```bash
POST /api/mobile/chat/send
Authorization: Bearer {{access_token}}
```

**Body** :
```json
{
  "conversation_id": 1,
  "message": "Bonjour, peux-tu m'aider ?",
  "use_rag": false
}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "user_message": { ... },
    "assistant_message": {
      "content": "Bonjour ! Je suis votre assistant juridique AI...",
      "tokens_used": { "total": 50 }
    },
    "quotas": {
      "ai_analyses_used": 1,
      "ai_analyses_limit": 10,
      "remaining": 9
    }
  }
}
```

✅ **Vérifications** :
- Réponse générée par GPT
- Quota AI analyses : 1/10

---

#### 2.3 Send Message - RAG Simple

**Request** :
```bash
POST /api/mobile/chat/send
```

**Body** :
```json
{
  "conversation_id": 1,
  "message": "Qu'est-ce qu'un contrat synallagmatique ?",
  "use_rag": true,
  "rag_type": "simple"
}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "assistant_message": {
      "content": "Selon l'article 1106 du Code civil...",
      "tokens_used": { "total": 250 }
    },
    "rag_context": {
      "used": true,
      "type": "simple",
      "sources": [
        {
          "type": "legal_library",
          "title": "Code civil camerounais",
          "relevance": 0.85
        }
      ]
    },
    "quotas": {
      "ai_analyses_used": 2,
      "remaining": 8
    }
  }
}
```

✅ **Vérifications** :
- Contexte RAG inclus
- Sources juridiques citées
- Quota AI analyses : 2/10

---

#### 2.4 Get Conversation Messages

**Request** :
```bash
GET /api/mobile/chat/conversation/{{conversation_id}}/messages
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "conversation": { ... },
    "messages": [
      {
        "id": 1,
        "role": "user",
        "content": "Bonjour..."
      },
      {
        "id": 2,
        "role": "assistant",
        "content": "Bonjour ! Je suis..."
      }
    ]
  }
}
```

✅ **Vérifications** :
- Messages dans l'ordre chronologique
- Rôles alternés (user/assistant)

---

### **Étape 3 : Documents**

#### 3.1 Upload Document

**Request** :
```bash
POST /api/mobile/documents/upload
Content-Type: multipart/form-data
```

**FormData** :
- `file` : (PDF file)
- `title` : "Mon contrat test"
- `description` : "Test upload"

**Expected Response (201)** :
```json
{
  "success": true,
  "message": "Document uploaded and analyzed successfully",
  "data": {
    "document": {
      "id": 1,
      "title": "Mon contrat test",
      "file_url": "https://files.dossypro.com/documents/...",
      "text_extracted": true,
      "indexed_in_pinecone": true
    },
    "quotas": {
      "ai_analyses_used": 3,
      "remaining": 7
    }
  }
}
```

✅ **Vérifications** :
- Fichier uploadé sur R2 (`https://files.dossypro.com/...`)
- Texte extrait : `true`
- Indexé dans Pinecone : `true`
- Quota AI analyses : 3/10

---

#### 3.2 Get My Documents

**Request** :
```bash
GET /api/mobile/documents/my-documents
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "documents": [
      {
        "id": 1,
        "title": "Mon contrat test",
        "file_name": "contrat_test.pdf",
        "file_url": "https://files.dossypro.com/..."
      }
    ]
  }
}
```

✅ **Vérifications** :
- Document uploadé visible
- URL R2 valide

---

#### 3.3 Send Message - RAG Advanced

**Request** :
```bash
POST /api/mobile/chat/send
```

**Body** :
```json
{
  "conversation_id": 1,
  "message": "Analyse mon contrat test",
  "use_rag": true,
  "rag_type": "advanced"
}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "assistant_message": {
      "content": "D'après votre document \"Mon contrat test\"..."
    },
    "rag_context": {
      "used": true,
      "type": "advanced",
      "sources": [
        {
          "type": "user_document",
          "title": "Mon contrat test",
          "relevance": 0.92
        }
      ]
    }
  }
}
```

✅ **Vérifications** :
- Contexte extrait du document utilisateur
- Pinecone utilisé pour recherche

---

#### 3.4 Search Legal Documents

**Request** :
```bash
POST /api/mobile/documents/search
```

**Body** :
```json
{
  "query": "droit du travail",
  "limit": 5
}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 15,
        "title": "Code du travail - Art. 1 à 50",
        "relevance_score": 0.88,
        "excerpt": "...dispositions du code du travail..."
      }
    ],
    "quotas": {
      "searches_used": 1,
      "searches_limit": 10,
      "remaining": 9
    }
  }
}
```

✅ **Vérifications** :
- Quota searches : 1/10
- Résultats triés par pertinence

---

#### 3.5 Download Legal Document

**Request** :
```bash
GET /api/mobile/documents/legal/15/download
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "document": {
      "download_url": "https://files.dossypro.com/legal_documents/..."
    },
    "quotas": {
      "pdf_downloads_used": 1,
      "pdf_downloads_limit": 5,
      "remaining": 4
    }
  }
}
```

✅ **Vérifications** :
- URL R2 valide
- Quota PDF downloads : 1/5

---

### **Étape 4 : Subscription**

#### 4.1 Get Plans

**Request** :
```bash
GET /api/mobile/plans
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "plans": [
      {
        "id": 1,
        "name": "Gratuit",
        "price": 0,
        "features": {
          "searches": 10,
          "ai_analyses": 10,
          "pdf_downloads": 5
        }
      },
      {
        "id": 2,
        "name": "Étudiant",
        "price": 2000,
        "features": {
          "searches": 100,
          "ai_analyses": 50,
          "pdf_downloads": 30
        }
      }
    ]
  }
}
```

✅ **Vérifications** :
- 4 plans disponibles
- Prices en XAF

---

#### 4.2 Get Current Subscription

**Request** :
```bash
GET /api/mobile/subscription/current
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "subscription": {
      "plan": {
        "name": "Gratuit",
        "price": 0
      },
      "status": "active"
    },
    "usage": {
      "searches": { "used": 1, "limit": 10, "remaining": 9 },
      "ai_analyses": { "used": 3, "limit": 10, "remaining": 7 },
      "pdf_downloads": { "used": 1, "limit": 5, "remaining": 4 }
    }
  }
}
```

✅ **Vérifications** :
- Usage cohérent avec actions précédentes
- Plan actuel = Gratuit

---

#### 4.3 Initiate Payment

**Request** :
```bash
POST /api/mobile/subscription/initiate
```

**Body** :
```json
{
  "plan_id": 2,
  "billing_cycle": "monthly",
  "payment_method": "mobile_money",
  "phone_number": "+237670000000"
}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "payment": {
      "id": 1,
      "transaction_reference": "DOSSY1732...",
      "amount": 2000,
      "status": "pending"
    },
    "payment_url": "...",
    "instructions": "Composez *126# pour autoriser"
  }
}
```

✅ **Vérifications** :
- Payment créé avec status `pending`
- Variable `{{payment_id}}` mise à jour

---

### **Étape 5 : Referral**

#### 5.1 Get My Referral Code

**Request** :
```bash
GET /api/mobile/referral/code
```

**Expected Response (200)** :
```json
{
  "success": true,
  "data": {
    "referral_code": "TEST1234",
    "total_referrals": 0,
    "progress_to_next_reward": {
      "current": 0,
      "target": 10,
      "percentage": 0
    }
  }
}
```

✅ **Vérifications** :
- Code unique généré
- Progression 0/10

---

#### 5.2 Validate Referral Code

**Request** :
```bash
POST /api/mobile/referral/validate
```

**Body** :
```json
{
  "referral_code": "TEST1234"
}
```

**Expected Response (200)** :
```json
{
  "success": true,
  "message": "Referral code is valid",
  "data": {
    "valid": true
  }
}
```

✅ **Vérifications** :
- Code validé correctement

---

## ⚠️ Tests de Quotas

### Test 1 : Quota AI Analyses Exceeded

**Setup** :
1. Envoyer 10 messages avec `use_rag: true`
2. Tenter un 11ème message

**Expected Response (403)** :
```json
{
  "success": false,
  "message": "AI analysis quota exceeded. Please upgrade your plan.",
  "data": {
    "quotas": {
      "ai_analyses_used": 10,
      "ai_analyses_limit": 10,
      "remaining": 0
    }
  }
}
```

---

### Test 2 : Quota Searches Exceeded

**Setup** :
1. Faire 10 recherches `POST /documents/search`
2. Tenter une 11ème recherche

**Expected Response (403)** :
```json
{
  "success": false,
  "message": "Search quota exceeded. Please upgrade your plan."
}
```

---

### Test 3 : Quota PDF Downloads Exceeded

**Setup** :
1. Télécharger 5 PDFs
2. Tenter un 6ème téléchargement

**Expected Response (403)** :
```json
{
  "success": false,
  "message": "PDF download quota exceeded. Please upgrade your plan."
}
```

---

## 🔒 Tests de Sécurité

### Test 1 : Token Invalide

**Request** :
```bash
GET /api/mobile/profile
Authorization: Bearer invalid_token
```

**Expected Response (401)** :
```json
{
  "message": "Unauthenticated."
}
```

---

### Test 2 : Accès aux Ressources d'un Autre Utilisateur

**Setup** :
1. Créer User A et User B
2. User A upload document ID=5
3. User B tente `DELETE /documents/5`

**Expected Response (404)** :
```json
{
  "success": false,
  "message": "Document not found"
}
```

---

### Test 3 : Validation Errors

**Request** :
```bash
POST /api/mobile/register
```

**Body** :
```json
{
  "name": "",
  "email": "invalid-email",
  "password": "123"
}
```

**Expected Response (422)** :
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email must be a valid email address."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## 📊 Checklist de Test Complet

### Authentication
- [ ] Register user
- [ ] Login user
- [ ] Get profile
- [ ] Update profile
- [ ] Refresh token
- [ ] Logout

### Chat AI
- [ ] Create conversation
- [ ] Send message (no RAG)
- [ ] Send message (RAG simple)
- [ ] Send message (RAG advanced)
- [ ] Send message (RAG both)
- [ ] Get conversations
- [ ] Get messages
- [ ] Delete conversation

### Documents
- [ ] Upload PDF
- [ ] Get my documents
- [ ] Search legal library
- [ ] Download legal document
- [ ] Delete document

### Subscription
- [ ] Get plans
- [ ] Get current subscription
- [ ] Initiate payment
- [ ] Activate subscription
- [ ] Cancel subscription
- [ ] Get payment history

### Referral
- [ ] Validate code (public)
- [ ] Get my code
- [ ] Get history
- [ ] Get rewards

### Quotas
- [ ] AI analyses quota exceeded
- [ ] Searches quota exceeded
- [ ] PDF downloads quota exceeded

### Security
- [ ] Invalid token
- [ ] Access other user's resources
- [ ] Validation errors

---

## 🐛 Debugging

### Activer les Logs Laravel

```bash
tail -f storage/logs/laravel.log
```

### Vérifier les Variables d'Environnement

```bash
php artisan config:clear
php artisan cache:clear
```

### Tester Connexion OpenAI

```bash
php artisan tinker
>>> app('App\Services\OpenAIService')->validateApiKey()
```

### Tester Connexion Pinecone

```bash
php artisan tinker
>>> app('App\Services\AdvancedRagService')->testConnection()
```

---

**Date** : 2024-11-26  
**Version** : 1.0.0  
**Auteur** : Dossy IA Team
