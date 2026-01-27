# Mobile App Integration Guide - Laravel Backend

## Required Changes in Laravel Backend

### 1. Mobile Users Database Schema

Ensure the `mobile_users` table has these fields:
```sql
CREATE TABLE mobile_users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'student', 
    plan VARCHAR(100) DEFAULT 'Gratuit',
    jurisdiction VARCHAR(10), -- Country code
    subscription_end DATETIME NULL,
    searches_used INT DEFAULT 0,
    searches_limit INT DEFAULT 5,
    analyses_used INT DEFAULT 0,
    analyses_limit INT DEFAULT 2,
    downloads_used INT DEFAULT 0,
    downloads_limit INT DEFAULT 0,
    referral_count INT DEFAULT 0,
    referral_code VARCHAR(50) UNIQUE,
    summaries_generated INT DEFAULT 0, -- NEW FIELD
    quizzes_created INT DEFAULT 0,     -- NEW FIELD
    revision_sessions INT DEFAULT 0,    -- NEW FIELD
    fcm_token VARCHAR(255), -- For push notifications
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. API Endpoints Required

#### Authentication Endpoints (Already implemented)
- POST `/api/mobile/register` - User registration
- POST `/api/mobile/login` - User login
- POST `/api/mobile/logout` - User logout

**IMPORTANT:** Ensure `/api/mobile/register` saves data to the `mobile_users` table that appears in your admin dashboard.

#### Subscription Plans Endpoint
- GET `/api/mobile/subscriptions/plans`

**Response Format:**
```json
{
  "success": true,
  "plans": [
    {
      "id": "gratuit",
      "name": "Gratuit",
      "price": 0,
      "currency": "XAF",
      "duration": "Permanent",
      "features": [
        "5 recherches par mois",
        "2 analyses IA par mois",
        "Accès bibliothèque juridique de base",
        "Chat IA limité"
      ],
      "limits": {
        "searches": 5,
        "analyses": 2,
        "downloads": 0
      }
    },
    {
      "id": "etudiant",
      "name": "Étudiant",
      "price": 2000,
      "currency": "XAF",
      "duration": "monthly",
      "features": [
        "50 recherches par mois",
        "20 analyses IA par mois",
        "10 téléchargements PDF",
        "Générateur de fiches d'arrêt",
        "Générateur de fiches de révision",
        "QCM interactifs",
        "Mode révision active",
        "Transcription audio des cours"
      ],
      "limits": {
        "searches": 50,
        "analyses": 20,
        "downloads": 10
      }
    },
    {
      "id": "professionnel",
      "name": "Professionnel",
      "price": 5000,
      "currency": "XAF",
      "duration": "monthly",
      "features": [
        "200 recherches par mois",
        "100 analyses IA par mois",
        "50 téléchargements PDF",
        "Anonymisation automatique",
        "Transcription audio",
        "Export Word éditable",
        "Modèles de contrats",
        "Veille juridique et alertes",
        "Assistant fiscal et social"
      ],
      "limits": {
        "searches": 200,
        "analyses": 100,
        "downloads": 50
      }
    },
    {
      "id": "cabinet",
      "name": "Cabinet/Entreprise",
      "price": 15000,
      "currency": "XAF",
      "duration": "monthly",
      "features": [
        "Recherches illimitées",
        "Analyses IA illimitées",
        "Téléchargements illimités",
        "Multi-comptes (jusqu'à 10 utilisateurs)",
        "Anonymisation automatique",
        "Export Word éditable",
        "Modèles de contrats premium",
        "Simulateurs RH et paie",
        "Veille juridique personnalisée",
        "Alertes Email et WhatsApp",
        "Support prioritaire 24/7",
        "Formation et onboarding"
      ],
      "limits": {
        "searches": -1,
        "analyses": -1,
        "downloads": -1
      }
    }
  ]
}
```

**NOTE:** Plans should be dynamically retrieved from your admin configuration (Mobile App Plans table).

#### User Profile Endpoint
- GET `/api/mobile/user/profile` - Get current user data

**Response Format:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+237690000000",
    "role": "student",
    "plan": "Étudiant",
    "jurisdiction": "CM",
    "subscription_end": "2024-12-31T23:59:59Z",
    "searches_used": 5,
    "searches_limit": 50,
    "analyses_used": 2,
    "analyses_limit": 20,
    "downloads_used": 1,
    "downloads_limit": 10,
    "referral_count": 3,
    "referral_code": "JOHN123",
    "summaries_generated": 12,
    "quizzes_created": 8,
    "revision_sessions": 24,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

### 3. User Statistics Update Endpoints

When users generate content in the mobile app, these values should be incremented:

```php
// Example: Update statistics when user generates a summary
POST /api/mobile/user/stats/increment
{
  "field": "summaries_generated",  // or "quizzes_created" or "revision_sessions"
  "count": 1
}
```

Or handle it automatically when content is created via other endpoints.

### 4. Admin Dashboard Integration

Ensure the Laravel admin dashboard:
1. **Shows mobile users** from `mobile_users` table in "Mobile Users Management"
2. **Syncs subscription plans** from admin to API endpoint `/api/mobile/subscriptions/plans`
3. **Displays user statistics**: summaries_generated, quizzes_created, revision_sessions in user profiles

### 5. Payment Integration (Flutterwave)

Endpoint:
- POST `/api/mobile/subscriptions/initiate-payment`

**Request:**
```json
{
  "plan_id": "etudiant",
  "duration": "monthly",
  "coupon_code": "DISCOUNT20"
}
```

**Response:**
```json
{
  "success": true,
  "payment_url": "https://payment.flutterwave.com/...",
  "transaction_id": "TX123456789"
}
```

### 6. Testing Checklist

- [ ] Register new user via mobile app → Check user appears in admin dashboard
- [ ] Login with registered user → Verify JWT token works
- [ ] Fetch subscription plans → Ensure admin plans are returned
- [ ] Update user stats (summaries_generated) → Verify counts increment
- [ ] Check user profile endpoint → Ensure all fields return correctly
- [ ] Verify subscription plan prices match admin configuration

## Implementation Priority

1. **HIGH**: Fix `/api/mobile/register` to save users to `mobile_users` table visible in admin
2. **HIGH**: Add `summaries_generated`, `quizzes_created`, `revision_sessions` fields to database
3. **HIGH**: Implement `/api/mobile/subscriptions/plans` to fetch from admin configuration
4. **MEDIUM**: Create endpoints to update user statistics
5. **MEDIUM**: Add user profile endpoint with all fields
6. **LOW**: Payment integration testing

## Contact

If you need help with any of these implementations, the Flutter app is now configured to work with these endpoints.
