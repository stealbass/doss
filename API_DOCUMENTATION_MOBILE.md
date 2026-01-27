# 📱 Dossy IA - Documentation API Mobile

**Version** : 1.0.0  
**Base URL** : `https://dossy.alwaysdata.net/api/mobile`  
**Authentication** : Laravel Sanctum (Bearer Token)

---

## 📑 Table des Matières

1. [Authentication](#authentication)
2. [Chat AI](#chat-ai)
3. [Documents](#documents)
4. [Subscription](#subscription)
5. [Referral](#referral)
6. [Error Handling](#error-handling)
7. [Rate Limiting](#rate-limiting)

---

## 🔐 Authentication

### Register User

**Endpoint** : `POST /register`  
**Auth Required** : No

**Request Body** :
```json
{
  "name": "Jean Dupont",
  "email": "jean@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+237670000000",
  "referral_code": "ABC12345" // Optional
}
```

**Success Response (200)** :
```json
{
  "success": true,
  "message": "User registered successfully with Free plan",
  "data": {
    "user": {
      "id": 1,
      "name": "Jean Dupont",
      "email": "jean@example.com",
      "phone": "+237670000000",
      "referral_code": "JD123456",
      "created_at": "2024-11-26T10:00:00.000000Z"
    },
    "token": "1|abc123def456...",
    "subscription": {
      "id": 1,
      "plan_name": "Gratuit",
      "status": "active",
      "starts_at": "2024-11-26",
      "expires_at": null
    }
  }
}
```

**Error Response (422)** :
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### Login User

**Endpoint** : `POST /login`  
**Auth Required** : No

**Request Body** :
```json
{
  "email": "jean@example.com",
  "password": "password123"
}
```

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Jean Dupont",
      "email": "jean@example.com",
      "phone": "+237670000000"
    },
    "token": "2|xyz789abc123...",
    "subscription": {
      "plan_name": "Gratuit",
      "status": "active",
      "quotas": {
        "searches": { "used": 0, "limit": 10 },
        "ai_analyses": { "used": 0, "limit": 10 },
        "pdf_downloads": { "used": 0, "limit": 5 }
      }
    }
  }
}
```

**Error Response (401)** :
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### Get Profile

**Endpoint** : `GET /profile`  
**Auth Required** : Yes

**Headers** :
```
Authorization: Bearer 2|xyz789abc123...
```

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Jean Dupont",
      "email": "jean@example.com",
      "phone": "+237670000000",
      "referral_code": "JD123456"
    },
    "subscription": {
      "plan_name": "Étudiant",
      "status": "active",
      "starts_at": "2024-11-20",
      "expires_at": "2024-12-20",
      "quotas": {
        "searches": { "used": 15, "limit": 100, "remaining": 85 },
        "ai_analyses": { "used": 5, "limit": 50, "remaining": 45 },
        "pdf_downloads": { "used": 2, "limit": 30, "remaining": 28 }
      }
    },
    "statistics": {
      "total_conversations": 12,
      "total_messages": 45,
      "total_documents_uploaded": 3,
      "total_referrals": 2
    }
  }
}
```

---

### Update Profile

**Endpoint** : `PUT /profile`  
**Auth Required** : Yes

**Request Body** :
```json
{
  "name": "Jean-Pierre Dupont",
  "phone": "+237670000001",
  "current_password": "password123", // Required if changing password
  "password": "newpassword456", // Optional
  "password_confirmation": "newpassword456" // Required if password provided
}
```

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Jean-Pierre Dupont",
      "email": "jean@example.com",
      "phone": "+237670000001"
    }
  }
}
```

---

### Logout

**Endpoint** : `POST /logout`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### Refresh Token

**Endpoint** : `POST /refresh-token`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "3|new789token456..."
  }
}
```

---

## 💬 Chat AI

### Create Conversation

**Endpoint** : `POST /chat/conversation`  
**Auth Required** : Yes

**Request Body** :
```json
{
  "title": "Question sur le droit du travail" // Optional
}
```

**Success Response (201)** :
```json
{
  "success": true,
  "message": "Conversation created successfully",
  "data": {
    "conversation": {
      "id": 1,
      "title": "Question sur le droit du travail",
      "created_at": "2024-11-26T10:30:00.000000Z",
      "updated_at": "2024-11-26T10:30:00.000000Z"
    }
  }
}
```

---

### Get All Conversations

**Endpoint** : `GET /chat/conversations`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "conversations": [
      {
        "id": 1,
        "title": "Question sur le droit du travail",
        "last_message": "Merci pour ces précisions !",
        "last_message_at": "2024-11-26T11:00:00.000000Z",
        "messages_count": 8,
        "created_at": "2024-11-26T10:30:00.000000Z"
      },
      {
        "id": 2,
        "title": "Contrat de vente",
        "last_message": "Quelles sont les conditions de validité ?",
        "last_message_at": "2024-11-25T14:20:00.000000Z",
        "messages_count": 4,
        "created_at": "2024-11-25T14:00:00.000000Z"
      }
    ]
  }
}
```

---

### Get Conversation Messages

**Endpoint** : `GET /chat/conversation/{id}/messages`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "conversation": {
      "id": 1,
      "title": "Question sur le droit du travail"
    },
    "messages": [
      {
        "id": 1,
        "role": "user",
        "content": "Qu'est-ce qu'un contrat synallagmatique ?",
        "created_at": "2024-11-26T10:31:00.000000Z"
      },
      {
        "id": 2,
        "role": "assistant",
        "content": "Un contrat synallagmatique est un contrat dans lequel les parties s'engagent réciproquement l'une envers l'autre...",
        "tokens_used": {
          "prompt": 120,
          "completion": 180,
          "total": 300
        },
        "created_at": "2024-11-26T10:31:05.000000Z"
      }
    ]
  }
}
```

---

### Send Message

**Endpoint** : `POST /chat/send`  
**Auth Required** : Yes

**Request Body** :
```json
{
  "conversation_id": 1,
  "message": "Qu'est-ce qu'un contrat synallagmatique ?",
  "use_rag": true, // Optional, default: false
  "rag_type": "both", // Optional: "simple", "advanced", "both" (default: "both")
  "model": "gpt-3.5-turbo" // Optional: "gpt-3.5-turbo", "gpt-4", "gpt-4-turbo-preview"
}
```

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "user_message": {
      "id": 3,
      "role": "user",
      "content": "Qu'est-ce qu'un contrat synallagmatique ?",
      "created_at": "2024-11-26T11:00:00.000000Z"
    },
    "assistant_message": {
      "id": 4,
      "role": "assistant",
      "content": "Un contrat synallagmatique est un contrat dans lequel les parties s'engagent réciproquement l'une envers l'autre. Selon l'article 1106 du Code civil camerounais...",
      "tokens_used": {
        "prompt": 150,
        "completion": 220,
        "total": 370
      },
      "created_at": "2024-11-26T11:00:03.000000Z"
    },
    "rag_context": {
      "used": true,
      "type": "both",
      "sources": [
        {
          "type": "legal_library",
          "title": "Code civil camerounais",
          "relevance": 0.85
        },
        {
          "type": "user_document",
          "title": "Mon cours de droit civil",
          "relevance": 0.72
        }
      ]
    },
    "quotas": {
      "ai_analyses_used": 6,
      "ai_analyses_limit": 50,
      "remaining": 44
    }
  }
}
```

**Error Response (403)** :
```json
{
  "success": false,
  "message": "AI analysis quota exceeded. Please upgrade your plan.",
  "data": {
    "quotas": {
      "ai_analyses_used": 10,
      "ai_analyses_limit": 10,
      "remaining": 0
    },
    "upgrade_url": "/subscription/plans"
  }
}
```

---

### Delete Conversation

**Endpoint** : `DELETE /chat/conversation/{id}`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Conversation deleted successfully"
}
```

---

## 📄 Documents

### Upload Document

**Endpoint** : `POST /documents/upload`  
**Auth Required** : Yes  
**Content-Type** : `multipart/form-data`

**Request Body** :
```
FormData:
  file: [PDF file]
  title: "Mon contrat de travail"
  description: "Contrat CDI signé en 2023" // Optional
```

**Success Response (201)** :
```json
{
  "success": true,
  "message": "Document uploaded and analyzed successfully",
  "data": {
    "document": {
      "id": 5,
      "title": "Mon contrat de travail",
      "description": "Contrat CDI signé en 2023",
      "file_name": "contrat_travail.pdf",
      "file_size": 245678, // bytes
      "file_url": "https://files.dossypro.com/documents/user_1/contrat_travail.pdf",
      "text_extracted": true,
      "indexed_in_pinecone": true,
      "created_at": "2024-11-26T12:00:00.000000Z"
    },
    "quotas": {
      "ai_analyses_used": 7,
      "ai_analyses_limit": 50,
      "remaining": 43
    }
  }
}
```

**Error Response (422)** :
```json
{
  "message": "The file must be a file of type: pdf.",
  "errors": {
    "file": ["The file must be a file of type: pdf."]
  }
}
```

**Error Response (413)** :
```json
{
  "success": false,
  "message": "The file may not be greater than 10240 kilobytes."
}
```

---

### Get My Documents

**Endpoint** : `GET /documents/my-documents`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "documents": [
      {
        "id": 5,
        "title": "Mon contrat de travail",
        "description": "Contrat CDI signé en 2023",
        "file_name": "contrat_travail.pdf",
        "file_size": 245678,
        "file_url": "https://files.dossypro.com/documents/user_1/contrat_travail.pdf",
        "created_at": "2024-11-26T12:00:00.000000Z"
      },
      {
        "id": 4,
        "title": "Facture prestation juridique",
        "file_name": "facture_2023.pdf",
        "file_size": 123456,
        "created_at": "2024-11-25T09:30:00.000000Z"
      }
    ]
  }
}
```

---

### Delete Document

**Endpoint** : `DELETE /documents/{id}`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Document deleted successfully"
}
```

**Error Response (404)** :
```json
{
  "success": false,
  "message": "Document not found"
}
```

---

### Search Legal Documents

**Endpoint** : `POST /documents/search`  
**Auth Required** : Yes

**Request Body** :
```json
{
  "query": "prescription acquisitive immobilière",
  "category_id": 2, // Optional
  "limit": 10 // Optional, default: 10
}
```

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 15,
        "title": "Code civil - Art. 2262 à 2281",
        "category": "Droit des biens",
        "description": "Prescription acquisitive et extinctive",
        "file_name": "code_civil_biens.pdf",
        "relevance_score": 0.92,
        "excerpt": "...la prescription acquisitive immobilière s'acquiert par trente ans..."
      },
      {
        "id": 23,
        "title": "Jurisprudence CS/CA 2018",
        "category": "Droit des biens",
        "relevance_score": 0.78,
        "excerpt": "...conditions de la prescription trentenaire..."
      }
    ],
    "quotas": {
      "searches_used": 16,
      "searches_limit": 100,
      "remaining": 84
    }
  }
}
```

**Error Response (403)** :
```json
{
  "success": false,
  "message": "Search quota exceeded. Please upgrade your plan.",
  "data": {
    "quotas": {
      "searches_used": 10,
      "searches_limit": 10,
      "remaining": 0
    }
  }
}
```

---

### Download Legal Document

**Endpoint** : `GET /documents/legal/{id}/download`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "document": {
      "id": 15,
      "title": "Code civil - Art. 2262 à 2281",
      "file_name": "code_civil_biens.pdf",
      "file_size": 3456789,
      "download_url": "https://files.dossypro.com/legal_documents/civil/code_civil_biens.pdf"
    },
    "quotas": {
      "pdf_downloads_used": 3,
      "pdf_downloads_limit": 30,
      "remaining": 27
    }
  }
}
```

**Error Response (403)** :
```json
{
  "success": false,
  "message": "PDF download quota exceeded. Please upgrade your plan.",
  "data": {
    "quotas": {
      "pdf_downloads_used": 5,
      "pdf_downloads_limit": 5,
      "remaining": 0
    }
  }
}
```

---

## 💳 Subscription

### Get Plans

**Endpoint** : `GET /plans`  
**Auth Required** : No

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "plans": [
      {
        "id": 1,
        "name": "Gratuit",
        "price": 0,
        "currency": "XAF",
        "billing_cycle": "monthly",
        "features": {
          "searches": 10,
          "ai_analyses": 10,
          "pdf_downloads": 5,
          "ai_model": "gpt-3.5-turbo"
        },
        "description": "Plan gratuit pour découvrir Dossy IA",
        "popular": false
      },
      {
        "id": 2,
        "name": "Étudiant",
        "price": 2000,
        "currency": "XAF",
        "billing_cycle": "monthly",
        "features": {
          "searches": 100,
          "ai_analyses": 50,
          "pdf_downloads": 30,
          "ai_model": "gpt-3.5-turbo"
        },
        "description": "Idéal pour les étudiants en droit",
        "popular": true
      },
      {
        "id": 3,
        "name": "Pro",
        "price": 5000,
        "currency": "XAF",
        "billing_cycle": "monthly",
        "features": {
          "searches": 500,
          "ai_analyses": 200,
          "pdf_downloads": 100,
          "ai_model": "gpt-4"
        },
        "description": "Pour les avocats et professionnels",
        "popular": false
      },
      {
        "id": 4,
        "name": "Cabinet",
        "price": 15000,
        "currency": "XAF",
        "billing_cycle": "monthly",
        "features": {
          "searches": -1,
          "ai_analyses": 1000,
          "pdf_downloads": 500,
          "ai_model": "gpt-4-turbo-preview"
        },
        "description": "Solution pour cabinets d'avocats",
        "popular": false
      }
    ]
  }
}
```

---

### Get Current Subscription

**Endpoint** : `GET /subscription/current`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "subscription": {
      "id": 2,
      "plan": {
        "id": 2,
        "name": "Étudiant",
        "price": 2000,
        "currency": "XAF"
      },
      "status": "active",
      "starts_at": "2024-11-20",
      "expires_at": "2024-12-20",
      "auto_renew": true,
      "days_remaining": 24
    },
    "usage": {
      "searches": {
        "used": 16,
        "limit": 100,
        "remaining": 84,
        "percentage": 16
      },
      "ai_analyses": {
        "used": 7,
        "limit": 50,
        "remaining": 43,
        "percentage": 14
      },
      "pdf_downloads": {
        "used": 3,
        "limit": 30,
        "remaining": 27,
        "percentage": 10
      }
    }
  }
}
```

---

### Initiate Payment

**Endpoint** : `POST /subscription/initiate`  
**Auth Required** : Yes

**Request Body** :
```json
{
  "plan_id": 2,
  "billing_cycle": "monthly", // "monthly" or "yearly"
  "payment_method": "mobile_money", // "mobile_money", "card", etc.
  "phone_number": "+237670000000" // Required for mobile_money
}
```

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Payment initiated successfully",
  "data": {
    "payment": {
      "id": 10,
      "transaction_reference": "DOSSY1732xxxxxx1234",
      "amount": 2000,
      "currency": "XAF",
      "status": "pending",
      "payment_method": "mobile_money",
      "created_at": "2024-11-26T13:00:00.000000Z"
    },
    "payment_url": "https://checkout.flutterwave.com/...",
    "instructions": "Composez *126# pour autoriser le paiement"
  }
}
```

---

### Activate Subscription

**Endpoint** : `POST /subscription/activate`  
**Auth Required** : Yes

**Request Body** :
```json
{
  "payment_id": 10,
  "transaction_reference": "DOSSY1732xxxxxx1234"
}
```

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Subscription activated successfully",
  "data": {
    "subscription": {
      "id": 3,
      "plan_name": "Étudiant",
      "status": "active",
      "starts_at": "2024-11-26",
      "expires_at": "2024-12-26",
      "quotas_reset": true
    },
    "payment": {
      "id": 10,
      "status": "completed",
      "paid_at": "2024-11-26T13:05:00.000000Z"
    }
  }
}
```

**Error Response (400)** :
```json
{
  "success": false,
  "message": "Payment not verified yet. Please try again in a few moments."
}
```

---

### Cancel Subscription

**Endpoint** : `POST /subscription/cancel`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Subscription cancelled successfully. Access will remain until 2024-12-26.",
  "data": {
    "subscription": {
      "status": "cancelled",
      "expires_at": "2024-12-26",
      "auto_renew": false
    }
  }
}
```

---

### Get Payment History

**Endpoint** : `GET /subscription/payments`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "payments": [
      {
        "id": 10,
        "plan_name": "Étudiant",
        "amount": 2000,
        "currency": "XAF",
        "status": "completed",
        "payment_method": "mobile_money",
        "transaction_reference": "DOSSY1732xxxxxx1234",
        "paid_at": "2024-11-26T13:05:00.000000Z"
      },
      {
        "id": 8,
        "plan_name": "Étudiant",
        "amount": 2000,
        "currency": "XAF",
        "status": "completed",
        "payment_method": "mobile_money",
        "paid_at": "2024-10-26T10:30:00.000000Z"
      }
    ]
  }
}
```

---

## 🎁 Referral

### Validate Referral Code (Public)

**Endpoint** : `POST /referral/validate`  
**Auth Required** : No

**Request Body** :
```json
{
  "referral_code": "JD123456"
}
```

**Success Response (200)** :
```json
{
  "success": true,
  "message": "Referral code is valid",
  "data": {
    "referrer": {
      "name": "Jean Dupont",
      "referral_code": "JD123456"
    },
    "valid": true
  }
}
```

**Error Response (404)** :
```json
{
  "success": false,
  "message": "Invalid referral code"
}
```

---

### Get My Referral Code

**Endpoint** : `GET /referral/code`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "referral_code": "JD123456",
    "total_referrals": 8,
    "pending_referrals": 2,
    "rewards_earned": 0,
    "progress_to_next_reward": {
      "current": 8,
      "target": 10,
      "percentage": 80
    },
    "share_message": "Rejoignez-moi sur Dossy IA avec mon code de parrainage JD123456 et obtenez un assistant juridique IA gratuit !"
  }
}
```

---

### Get Referral History

**Endpoint** : `GET /referral/history`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "referrals": [
      {
        "id": 5,
        "referred_user": {
          "name": "Marie Martin",
          "email": "marie@example.com"
        },
        "status": "active",
        "registered_at": "2024-11-25T14:00:00.000000Z",
        "subscription_status": "active"
      },
      {
        "id": 4,
        "referred_user": {
          "name": "Paul Dubois",
          "email": "paul@example.com"
        },
        "status": "pending",
        "registered_at": "2024-11-24T10:00:00.000000Z",
        "subscription_status": "free"
      }
    ],
    "stats": {
      "total": 8,
      "active": 6,
      "pending": 2
    }
  }
}
```

---

### Get Referral Rewards

**Endpoint** : `GET /referral/rewards`  
**Auth Required** : Yes

**Success Response (200)** :
```json
{
  "success": true,
  "data": {
    "rewards": [
      {
        "id": 1,
        "type": "free_month",
        "description": "1 mois gratuit - 10 parrainages",
        "earned_at": "2024-10-15T12:00:00.000000Z",
        "applied_at": "2024-10-15T12:05:00.000000Z",
        "status": "applied"
      }
    ],
    "total_rewards": 1,
    "next_reward_progress": {
      "current": 8,
      "target": 10,
      "remaining": 2
    }
  }
}
```

---

## ⚠️ Error Handling

### Error Response Format

All API errors follow this format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized (invalid token) |
| 403 | Forbidden (quota exceeded, etc.) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests (rate limit) |
| 500 | Internal Server Error |

### Common Error Codes

**Validation Errors (422)** :
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Quota Exceeded (403)** :
```json
{
  "success": false,
  "message": "AI analysis quota exceeded. Please upgrade your plan.",
  "data": {
    "quotas": {
      "ai_analyses_used": 10,
      "ai_analyses_limit": 10,
      "remaining": 0
    },
    "upgrade_url": "/subscription/plans"
  }
}
```

**Unauthorized (401)** :
```json
{
  "message": "Unauthenticated."
}
```

**Not Found (404)** :
```json
{
  "success": false,
  "message": "Resource not found"
}
```

---

## 🚦 Rate Limiting

**Rate Limit** : 60 requests per minute per user  
**Response Headers** :
- `X-RateLimit-Limit`: 60
- `X-RateLimit-Remaining`: 45
- `Retry-After`: 30 (seconds, if rate limit exceeded)

**Rate Limit Exceeded (429)** :
```json
{
  "message": "Too Many Requests",
  "retry_after": 30
}
```

---

## 🔒 Security Best Practices

### Token Management

1. **Store tokens securely** in device keychain/keystore
2. **Never log tokens** in production
3. **Refresh tokens** before expiry (use `/refresh-token`)
4. **Revoke tokens** on logout (use `/logout`)

### Request Headers

```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### HTTPS Only

All API requests must use HTTPS. HTTP requests will be redirected to HTTPS.

---

## 📊 Quota Management

### Plan Quotas

| Plan | Searches | AI Analyses | PDF Downloads | AI Model |
|------|----------|-------------|---------------|----------|
| **Gratuit** | 10 | 10 | 5 | gpt-3.5-turbo |
| **Étudiant** | 100 | 50 | 30 | gpt-3.5-turbo |
| **Pro** | 500 | 200 | 100 | gpt-4 |
| **Cabinet** | Unlimited | 1000 | 500 | gpt-4-turbo |

### Quota Reset

- Quotas reset at the start of each billing cycle
- For monthly plans: 1st of each month
- For yearly plans: Anniversary date

### Checking Quotas

All endpoints that consume quotas return current usage:

```json
"quotas": {
  "searches_used": 16,
  "searches_limit": 100,
  "remaining": 84
}
```

---

## 🧪 Testing

### Postman Collection

Import the [Postman Collection](./DOSSY_IA_API.postman_collection.json) for easy testing.

### Example: Complete Flow

```bash
# 1. Register
curl -X POST https://dossy.alwaysdata.net/api/mobile/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+237670000000"
  }'

# 2. Login (get token)
curl -X POST https://dossy.alwaysdata.net/api/mobile/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# 3. Create conversation
curl -X POST https://dossy.alwaysdata.net/api/mobile/chat/conversation \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Question juridique"
  }'

# 4. Send message with RAG
curl -X POST https://dossy.alwaysdata.net/api/mobile/chat/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 1,
    "message": "Qu'\''est-ce qu'\''un contrat synallagmatique ?",
    "use_rag": true,
    "rag_type": "both"
  }'
```

---

**Last Updated** : 2024-11-26  
**API Version** : 1.0.0  
**Support** : support@dossypro.com
