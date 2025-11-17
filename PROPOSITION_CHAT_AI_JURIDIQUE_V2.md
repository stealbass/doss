# 📋 Proposition V2 - Chat AI Juridique pour Dossy Pro
## Version Mobile + Web avec Système de Forfaits

---

## 🎯 Vision du projet

### Écosystème complet
```
┌─────────────────────────────────────────────────────────────┐
│                    DOSSY PRO WEB                            │
│  • Chat AI avec quota selon abonnement                      │
│  • Quota atteint → Message + Invitation app mobile          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
         ┌─────────────────────────────┐
         │   NOTIFICATION PUSH          │
         │  "Quota atteint! 🚀          │
         │   Continuez sur l'app mobile"│
         └──────────────┬───────────────┘
                        │
         ┌──────────────┴──────────────┐
         ▼                             ▼
┌─────────────────┐         ┌─────────────────┐
│   APP ANDROID   │         │    APP iOS      │
│                 │         │                 │
│ • Chat illimité │         │ • Chat illimité │
│ • Forfaits IA   │         │ • Forfaits IA   │
│ • Paiement intégré│       │ • Paiement intégré│
└─────────────────┘         └─────────────────┘
```

---

## 🤖 PARTIE 1 : Choix du modèle OpenAI

### Analyse comparative des modèles OpenAI

#### Option 1 : **GPT-4o-mini** ⭐ RECOMMANDÉ
```yaml
Caractéristiques:
  Nom: gpt-4o-mini
  Vitesse: ⚡⚡⚡⚡⚡ (Très rapide - 2-3 secondes)
  Qualité: ⭐⭐⭐⭐ (Excellente pour usage juridique)
  Prix Input: $0.00015 / 1k tokens
  Prix Output: $0.0006 / 1k tokens
  Contexte: 128k tokens
  Multilingue: Excellent français

Coût par requête (estimation):
  Input (2000 tokens): $0.0003 (≈ 0.18 FCFA)
  Output (500 tokens): $0.0003 (≈ 0.18 FCFA)
  TOTAL: ≈ 0.36 FCFA par requête ✅

Avantages:
  ✅ 50x moins cher que GPT-4
  ✅ Très rapide (réponses quasi instantanées)
  ✅ Excellente qualité pour le juridique
  ✅ Parfait pour un usage intensif
  ✅ Support vision (analyse documents scannés)
  
Inconvénients:
  ⚠️ Légèrement moins précis que GPT-4 (mais suffisant)
```

#### Option 2 : GPT-4o (Standard)
```yaml
Caractéristiques:
  Nom: gpt-4o
  Vitesse: ⚡⚡⚡⚡ (Rapide)
  Qualité: ⭐⭐⭐⭐⭐ (Maximale)
  Prix Input: $0.0025 / 1k tokens
  Prix Output: $0.01 / 1k tokens
  
Coût par requête:
  TOTAL: ≈ 6 FCFA par requête
  
Usage recommandé:
  - Uniquement pour requêtes complexes
  - Cas juridiques très pointus
  - Option premium (payante)
```

#### Option 3 : GPT-3.5-turbo
```yaml
Caractéristiques:
  Nom: gpt-3.5-turbo
  Vitesse: ⚡⚡⚡⚡⚡ (Très rapide)
  Qualité: ⭐⭐⭐ (Bonne mais limitée)
  Prix: Encore moins cher
  
Coût par requête:
  TOTAL: ≈ 0.1 FCFA par requête
  
Limitations:
  ❌ Moins précis pour le juridique
  ❌ Contexte plus court
  ⚠️ Non recommandé pour usage professionnel
```

### 🏆 Recommandation finale

**Modèle principal : GPT-4o-mini**

**Stratégie à 2 niveaux** :
```
┌─────────────────────────────────────────────┐
│ REQUÊTE UTILISATEUR                         │
└────────────────┬────────────────────────────┘
                 │
                 ▼
         ┌───────────────┐
         │ Analyse type  │
         │  de requête   │
         └───────┬───────┘
                 │
        ┌────────┴────────┐
        │                 │
        ▼                 ▼
┌──────────────┐   ┌──────────────┐
│ GPT-4o-mini  │   │   GPT-4o     │
│ (99% cas)    │   │ (1% cas)     │
│ 0.36 FCFA    │   │ 6 FCFA       │
└──────────────┘   └──────────────┘
```

**Critères d'utilisation GPT-4o (premium)** :
- Cas juridique très complexe
- Demande explicite de l'utilisateur
- Option payante (+500 FCFA par requête premium)

---

## 💰 PARTIE 2 : Quotas et tarification révisés

### Quotas web (doublés)

| Plan | Prix/an | Requêtes/mois | Requêtes/an | Coût IA/mois | Marge/mois |
|------|---------|---------------|-------------|--------------|------------|
| **Gratuit** | 0 FCFA | **10** | **120** | 3.6 FCFA | -3.6 FCFA |
| **Solo** | 120 000 FCFA | **100** | **1200** | 36 FCFA | 9 964 FCFA |
| **Basic** | 240 000 FCFA | **200** | **2400** | 72 FCFA | 19 928 FCFA |
| **Pro** | 480 000 FCFA | **400** | **4800** | 144 FCFA | 39 856 FCFA |

**Marge excellente avec GPT-4o-mini !** ✅

### Budget mensuel estimé

#### Scénario conservateur (100 utilisateurs actifs)
```
Plan Gratuit (30 utilisateurs × 10 req/mois):
  300 requêtes × 0.36 FCFA = 108 FCFA/mois

Plan Solo (40 utilisateurs × 50 req/mois):
  2000 requêtes × 0.36 FCFA = 720 FCFA/mois
  Revenus: 40 × 10,000 = 400,000 FCFA
  
Plan Basic (20 utilisateurs × 100 req/mois):
  2000 requêtes × 0.36 FCFA = 720 FCFA/mois
  Revenus: 20 × 20,000 = 400,000 FCFA

Plan Pro (10 utilisateurs × 200 req/mois):
  2000 requêtes × 0.36 FCFA = 720 FCFA/mois
  Revenus: 10 × 40,000 = 400,000 FCFA

TOTAL COÛTS IA: 2,268 FCFA/mois
TOTAL REVENUS: 1,200,000 FCFA/mois
MARGE: 1,197,732 FCFA/mois (99.8%) 🎉
```

#### Scénario intensif (utilisation maximale des quotas)
```
Si TOUS les utilisateurs utilisent leur quota MAX:

100 utilisateurs × moyenne 150 req/mois:
  15,000 requêtes × 0.36 FCFA = 5,400 FCFA/mois

Même dans le pire cas: coût négligeable!
```

### 💡 Budget recommandé

**Budget mensuel OpenAI** : **10,000 - 20,000 FCFA/mois**
- Couvre largement 20,000+ requêtes
- Marge de sécurité confortable
- Permet pics d'utilisation

---

## 📱 PARTIE 3 : Application mobile (Android + iOS)

### Architecture mobile

```
┌────────────────────────────────────────────────┐
│         APPLICATION MOBILE DOSSY AI            │
├────────────────────────────────────────────────┤
│                                                │
│  ┌──────────────────────────────────────┐     │
│  │  FONCTIONNALITÉS                     │     │
│  ├──────────────────────────────────────┤     │
│  │ • Chat AI illimité (avec forfait)    │     │
│  │ • Accès bibliothèque juridique       │     │
│  │ • Upload documents (30 MB max)       │     │
│  │ • Scan documents (photo → analyse)   │     │
│  │ • Recherche vocale                   │     │
│  │ • Notifications push                 │     │
│  │ • Mode hors ligne (historique)       │     │
│  │ • Partage de réponses                │     │
│  └──────────────────────────────────────┘     │
│                                                │
│  ┌──────────────────────────────────────┐     │
│  │  PAIEMENTS INTÉGRÉS                  │     │
│  ├──────────────────────────────────────┤     │
│  │ Android: Google Play Billing         │     │
│  │ iOS: In-App Purchase (StoreKit)      │     │
│  │ Alternatives: Mobile Money intégré   │     │
│  └──────────────────────────────────────┘     │
└────────────────────────────────────────────────┘
```

### Stack technique mobile

#### Option A : **React Native** ⭐ RECOMMANDÉ
```yaml
Avantages:
  ✅ Un seul code pour Android + iOS
  ✅ 70% de code partagé
  ✅ Développement plus rapide (2-3 mois)
  ✅ Moins cher (1 équipe au lieu de 2)
  ✅ Écosystème riche (bibliothèques)
  ✅ Performance excellente
  
Stack:
  - React Native 0.73+
  - TypeScript
  - Redux / Zustand (state)
  - React Navigation
  - React Native Paper / NativeBase (UI)
  - Axios (API)
  - React Native IAP (paiements)
  
Coût développement:
  - 2-3 mois de travail
  - Budget estimé: 1,500,000 - 2,500,000 FCFA
```

#### Option B : Native (Android + iOS)
```yaml
Avantages:
  ✅ Performance maximale
  ✅ Accès complet aux APIs natives
  
Inconvénients:
  ❌ 2 codebases séparées
  ❌ 2 équipes de développement
  ❌ Coût 2x plus élevé
  ❌ Maintenance 2x plus longue
  
Coût développement:
  - 4-6 mois de travail
  - Budget estimé: 3,000,000 - 5,000,000 FCFA
```

#### Option C : Flutter
```yaml
Avantages:
  ✅ Un seul code
  ✅ Performance native
  ✅ UI magnifique
  
Inconvénients:
  ⚠️ Moins mature que React Native
  ⚠️ Moins de développeurs Dart
  
Coût développement:
  - 2-3 mois de travail
  - Budget estimé: 1,500,000 - 2,500,000 FCFA
```

**🏆 Recommandation : React Native** (rapport qualité/prix/délai optimal)

---

## 💳 PARTIE 4 : Système de forfaits mobile

### Tarification proposée pour l'app mobile

#### Modèle de forfaits
```
┌─────────────────────────────────────────────────┐
│           FORFAITS CHAT AI MOBILE               │
├─────────────────────────────────────────────────┤
│                                                 │
│  FORFAIT DÉCOUVERTE (gratuit)                   │
│  • 5 requêtes offertes                          │
│  • Test de l'application                        │
│  • Accès bibliothèque juridique                 │
│  Prix: 0 FCFA                                   │
│                                                 │
├─────────────────────────────────────────────────┤
│                                                 │
│  FORFAIT 50 REQUÊTES                            │
│  • 50 questions au Chat AI                      │
│  • Valable 30 jours                             │
│  • Upload documents (30 MB)                     │
│  • Support prioritaire                          │
│  Prix: 2,500 FCFA (~$4)                         │
│  Coût IA: 18 FCFA                               │
│  Marge: 2,482 FCFA (99.3%)                      │
│                                                 │
├─────────────────────────────────────────────────┤
│                                                 │
│  FORFAIT 150 REQUÊTES ⭐ POPULAIRE              │
│  • 150 questions au Chat AI                     │
│  • Valable 60 jours                             │
│  • Upload documents (30 MB)                     │
│  • Scan de documents                            │
│  • Support prioritaire                          │
│  Prix: 5,000 FCFA (~$8)                         │
│  Coût IA: 54 FCFA                               │
│  Marge: 4,946 FCFA (98.9%)                      │
│  Économie: -50% par requête                     │
│                                                 │
├─────────────────────────────────────────────────┤
│                                                 │
│  FORFAIT 500 REQUÊTES 💎 PREMIUM                │
│  • 500 questions au Chat AI                     │
│  • Valable 90 jours                             │
│  • Upload documents (30 MB)                     │
│  • Scan de documents                            │
│  • Recherche vocale                             │
│  • 10 requêtes GPT-4o premium offertes          │
│  • Support prioritaire 24/7                     │
│  Prix: 12,000 FCFA (~$19)                       │
│  Coût IA: 180 FCFA + 60 FCFA (premium)          │
│  Marge: 11,760 FCFA (98%)                       │
│  Économie: -76% par requête                     │
│                                                 │
├─────────────────────────────────────────────────┤
│                                                 │
│  FORFAIT ILLIMITÉ (abonnement mensuel)          │
│  • Requêtes illimitées*                         │
│  • Renouvellement automatique                   │
│  • Tous les avantages Premium                   │
│  • 20 requêtes GPT-4o premium/mois              │
│  Prix: 15,000 FCFA/mois (~$24)                  │
│  * Fair usage: max 1000 req/mois                │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Passerelle entre Web et Mobile

#### Workflow complet
```
ÉTAPE 1: Utilisateur sur WEB
├─ Abonné Plan Solo: 100 requêtes/mois
├─ Utilise 95 requêtes
└─ Il reste 5 requêtes

ÉTAPE 2: Quota presque atteint (90%)
├─ Notification in-app
└─ "Plus que 5 requêtes ce mois-ci!"

ÉTAPE 3: Quota atteint (100%)
┌───────────────────────────────────────────┐
│  ⚠️ Quota mensuel atteint (100/100)       │
│                                           │
│  Continuez à chatter sur l'app mobile:    │
│                                           │
│  📱 [Télécharger sur Play Store]          │
│  🍎 [Télécharger sur App Store]           │
│                                           │
│  Forfaits mobiles disponibles:            │
│  • 50 requêtes - 2,500 FCFA               │
│  • 150 requêtes - 5,000 FCFA ⭐            │
│  • 500 requêtes - 12,000 FCFA             │
│                                           │
│  Ou attendez le mois prochain (reset auto)│
└───────────────────────────────────────────┘

ÉTAPE 4: Utilisateur télécharge l'app
├─ Se connecte avec son compte Dossy Pro
├─ Achète forfait 150 requêtes (5,000 FCFA)
└─ Continue ses conversations

ÉTAPE 5: Synchronisation
├─ Historique web → visible sur mobile
├─ Nouvelles conversations mobile → visibles web
└─ Quota web et mobile séparés
```

### Système de notification push

#### Configuration notifications
```javascript
// Types de notifications

1. Quota Web atteint
   Titre: "Quota atteint sur Dossy Pro 🚀"
   Message: "Continuez sur l'app mobile avec nos forfaits!"
   Action: Redirection vers stores

2. Forfait mobile bientôt épuisé
   Titre: "Plus que 10 requêtes restantes"
   Message: "Rechargez votre forfait pour continuer"
   Action: Page d'achat in-app

3. Forfait expiré
   Titre: "Forfait expiré"
   Message: "Renouvelez pour continuer à utiliser le Chat AI"
   Action: Page d'achat in-app

4. Nouvelle fonctionnalité
   Titre: "Nouveau: Scan de documents 📸"
   Message: "Prenez en photo vos documents juridiques"
   Action: Tutoriel
```

---

## 🏗️ PARTIE 5 : Architecture technique complète

### Backend API (Laravel)

#### Nouvelles tables (en plus des tables V1)

```sql
-- Table pour les forfaits mobiles
CREATE TABLE mobile_packages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    requests_count INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    validity_days INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    features JSON, -- {"scan": true, "voice": true, "premium_requests": 10}
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Table pour les achats de forfaits
CREATE TABLE mobile_package_purchases (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    package_id BIGINT NOT NULL,
    transaction_id VARCHAR(255), -- Google/Apple transaction ID
    platform ENUM('android', 'ios', 'web'),
    payment_method VARCHAR(50), -- 'google_play', 'app_store', 'mobile_money'
    price_paid DECIMAL(10,2),
    requests_remaining INT,
    purchased_at TIMESTAMP,
    expires_at TIMESTAMP,
    status ENUM('active', 'expired', 'refunded'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES mobile_packages(id)
);

-- Table pour les notifications push
CREATE TABLE push_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    device_token VARCHAR(255),
    platform ENUM('android', 'ios'),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table pour tracking des requêtes mobiles
CREATE TABLE mobile_ai_usage (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    purchase_id BIGINT,
    conversation_id BIGINT,
    model_used VARCHAR(50), -- 'gpt-4o-mini' or 'gpt-4o'
    tokens_used INT,
    cost_fcfa DECIMAL(8,4),
    platform ENUM('android', 'ios'),
    created_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_id) REFERENCES mobile_package_purchases(id),
    FOREIGN KEY (conversation_id) REFERENCES ai_chat_conversations(id)
);
```

#### Nouvelles routes API

```php
// routes/api.php

// API v1 - Chat AI
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Chat conversations
    Route::get('/chat/conversations', [ApiChatController::class, 'index']);
    Route::post('/chat/conversations', [ApiChatController::class, 'store']);
    Route::get('/chat/conversations/{id}', [ApiChatController::class, 'show']);
    Route::delete('/chat/conversations/{id}', [ApiChatController::class, 'destroy']);
    
    // Messages
    Route::post('/chat/conversations/{id}/messages', [ApiChatController::class, 'sendMessage']);
    Route::post('/chat/conversations/{id}/upload', [ApiChatController::class, 'uploadDocument']);
    
    // Quotas et usage
    Route::get('/chat/usage', [ApiChatController::class, 'getUsage']);
    Route::get('/chat/quota', [ApiChatController::class, 'getQuota']);
    
    // Mobile packages
    Route::get('/mobile/packages', [MobilePackageController::class, 'index']);
    Route::post('/mobile/packages/purchase', [MobilePackageController::class, 'purchase']);
    Route::get('/mobile/packages/my-purchases', [MobilePackageController::class, 'myPurchases']);
    Route::post('/mobile/packages/verify-purchase', [MobilePackageController::class, 'verifyPurchase']);
    
    // Push notifications
    Route::post('/notifications/register-device', [PushNotificationController::class, 'registerDevice']);
    Route::delete('/notifications/unregister-device', [PushNotificationController::class, 'unregisterDevice']);
    
    // Legal library
    Route::get('/legal/documents', [ApiLegalLibraryController::class, 'index']);
    Route::get('/legal/documents/{id}', [ApiLegalLibraryController::class, 'show']);
    Route::get('/legal/documents/{id}/stream', [ApiLegalLibraryController::class, 'stream']);
    Route::get('/legal/search', [ApiLegalLibraryController::class, 'search']);
});
```

### Frontend Mobile (React Native)

#### Structure du projet
```
mobile-app/
├── android/                  # Code Android natif
├── ios/                      # Code iOS natif
├── src/
│   ├── screens/
│   │   ├── ChatScreen.tsx
│   │   ├── ConversationsScreen.tsx
│   │   ├── LegalLibraryScreen.tsx
│   │   ├── PackagesScreen.tsx
│   │   ├── ProfileScreen.tsx
│   │   └── DocumentScanScreen.tsx
│   ├── components/
│   │   ├── ChatMessage.tsx
│   │   ├── MessageInput.tsx
│   │   ├── DocumentUploader.tsx
│   │   ├── PackageCard.tsx
│   │   └── QuotaIndicator.tsx
│   ├── services/
│   │   ├── api.ts
│   │   ├── openai.ts
│   │   ├── iap.ts              # In-App Purchases
│   │   └── notifications.ts
│   ├── store/
│   │   ├── chatSlice.ts
│   │   ├── authSlice.ts
│   │   └── packagesSlice.ts
│   ├── navigation/
│   │   └── AppNavigator.tsx
│   └── utils/
│       ├── storage.ts
│       └── helpers.ts
├── package.json
└── app.json
```

#### Dépendances principales
```json
{
  "dependencies": {
    "react-native": "^0.73.0",
    "react-navigation": "^6.0.0",
    "@react-native-firebase/messaging": "^19.0.0",
    "react-native-iap": "^12.0.0",
    "react-native-document-picker": "^9.0.0",
    "react-native-vision-camera": "^3.0.0",
    "react-native-pdf": "^6.7.0",
    "axios": "^1.6.0",
    "@reduxjs/toolkit": "^2.0.0",
    "react-native-voice": "^3.2.0"
  }
}
```

---

## 💳 PARTIE 6 : Intégration des paiements

### Paiements mobiles

#### Android - Google Play Billing
```javascript
// Configuration In-App Products sur Google Play Console

Produits:
1. chat_ai_50_requests
   - Type: Managed product (consommable)
   - Prix: 2,500 FCFA
   - Description: 50 requêtes Chat AI

2. chat_ai_150_requests
   - Type: Managed product
   - Prix: 5,000 FCFA
   - Description: 150 requêtes Chat AI

3. chat_ai_500_requests
   - Type: Managed product
   - Prix: 12,000 FCFA
   - Description: 500 requêtes Chat AI

4. chat_ai_unlimited_monthly
   - Type: Subscription
   - Prix: 15,000 FCFA/mois
   - Description: Requêtes illimitées

// Code React Native
import * as RNIap from 'react-native-iap';

const productIds = [
  'chat_ai_50_requests',
  'chat_ai_150_requests',
  'chat_ai_500_requests',
];

const purchasePackage = async (productId) => {
  try {
    await RNIap.requestPurchase({ skus: [productId] });
    // Vérification côté serveur
    await verifyPurchaseOnServer(receipt);
  } catch (error) {
    console.error(error);
  }
};
```

#### iOS - In-App Purchase (StoreKit)
```javascript
// Configuration sur App Store Connect

Produits identiques à Android avec prix équivalents
Utilise la même bibliothèque react-native-iap

// Spécificité iOS
- Validation des reçus Apple
- Gestion des abonnements auto-renouvelables
- Commission Apple 30% (15% après 1 an d'abonnement)
```

#### Alternative - Mobile Money (MTN, Orange, Moov)
```javascript
// Intégration API Mobile Money locale

Pour les utilisateurs sans carte bancaire:

Services supportés:
- MTN Mobile Money
- Orange Money
- Moov Money
- Wave

Flux:
1. Utilisateur choisit Mobile Money
2. Redirection vers API de paiement
3. OTP sur téléphone
4. Confirmation paiement
5. Activation forfait
```

### Vérification des achats côté serveur

```php
// app/Services/PurchaseVerificationService.php

class PurchaseVerificationService
{
    public function verifyGooglePlayPurchase($receipt, $signature)
    {
        // Appel à Google Play Developer API
        // Vérification authenticité
        // Retour: transaction valide ou non
    }
    
    public function verifyAppStorePurchase($receiptData)
    {
        // Appel à Apple App Store API
        // Validation du reçu
        // Retour: transaction valide ou non
    }
    
    public function activatePackage($userId, $packageId, $transactionId)
    {
        // Créer entrée dans mobile_package_purchases
        // Activer le forfait
        // Envoyer notification de confirmation
    }
}
```

---

## 🔔 PARTIE 7 : Système de notifications

### Firebase Cloud Messaging (FCM)

```javascript
// Configuration Firebase
// - Firebase project
// - FCM pour push notifications
// - Analytics pour tracking

// React Native Firebase
import messaging from '@react-native-firebase/messaging';

// Demander permission
const requestPermission = async () => {
  const authStatus = await messaging().requestPermission();
  const enabled =
    authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
    authStatus === messaging.AuthorizationStatus.PROVISIONAL;

  if (enabled) {
    const token = await messaging().getToken();
    // Envoyer token au serveur
    await registerDeviceToken(token);
  }
};

// Écouter les notifications
messaging().onMessage(async remoteMessage => {
  // Afficher notification in-app
  showInAppNotification(remoteMessage);
});
```

### Backend - Envoi de notifications

```php
// app/Services/PushNotificationService.php

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationService
{
    public function sendQuotaReachedNotification($user)
    {
        $deviceTokens = $user->pushNotifications()
            ->where('is_active', true)
            ->pluck('device_token')
            ->toArray();
        
        $message = CloudMessage::new()
            ->withNotification(
                Notification::create(
                    'Quota atteint sur Dossy Pro 🚀',
                    'Continuez sur l\'app mobile avec nos forfaits!'
                )
            )
            ->withData([
                'type' => 'quota_reached',
                'action' => 'open_packages',
                'web_quota' => $user->getWebQuota(),
            ]);
        
        $this->firebase->getMessaging()->sendMulticast($message, $deviceTokens);
    }
    
    public function sendPackageExpiringNotification($purchase)
    {
        // Notification 3 jours avant expiration
        // "Votre forfait expire dans 3 jours"
    }
}
```

---

## 📊 PARTIE 8 : Dashboard et analytics

### Tableau de bord Super Admin

```
┌──────────────────────────────────────────────────────┐
│        DASHBOARD CHAT AI - SUPER ADMIN               │
├──────────────────────────────────────────────────────┤
│                                                      │
│  UTILISATION GLOBALE                                 │
│  ├─ Requêtes ce mois: 45,230                         │
│  ├─ Coût OpenAI: 16,283 FCFA                         │
│  ├─ Revenus forfaits mobile: 2,450,000 FCFA          │
│  └─ Marge nette: 2,433,717 FCFA (99.3%)              │
│                                                      │
│  RÉPARTITION PLATEFORMES                             │
│  ├─ Web: 25,430 requêtes (56%)                       │
│  └─ Mobile: 19,800 requêtes (44%)                    │
│                                                      │
│  TOP MODÈLES UTILISÉS                                │
│  ├─ GPT-4o-mini: 44,850 (99.2%)                      │
│  └─ GPT-4o: 380 (0.8%)                               │
│                                                      │
│  FORFAITS MOBILES ACTIFS                             │
│  ├─ 50 requêtes: 145 utilisateurs                    │
│  ├─ 150 requêtes: 289 utilisateurs ⭐                │
│  ├─ 500 requêtes: 78 utilisateurs                    │
│  └─ Illimité: 34 utilisateurs                        │
│                                                      │
│  REVENUS PAR SOURCE                                  │
│  ├─ Google Play: 1,450,000 FCFA (59%)                │
│  ├─ App Store: 850,000 FCFA (35%)                    │
│  └─ Mobile Money: 150,000 FCFA (6%)                  │
│                                                      │
└──────────────────────────────────────────────────────┘
```

### Analytics utilisateur

```
┌──────────────────────────────────────────────────────┐
│        MON UTILISATION CHAT AI                       │
├──────────────────────────────────────────────────────┤
│                                                      │
│  WEB (Plan Solo)                                     │
│  ├─ Ce mois: 87 / 100 requêtes                       │
│  ├─ [████████░░] 87%                                 │
│  └─ Reset: dans 12 jours                             │
│                                                      │
│  MOBILE (Forfait 150 requêtes)                       │
│  ├─ Restant: 112 / 150 requêtes                      │
│  ├─ [███████░░░] 75%                                 │
│  └─ Expire: dans 45 jours                            │
│                                                      │
│  STATISTIQUES                                        │
│  ├─ Total requêtes ce mois: 199                      │
│  ├─ Documents analysés: 12                           │
│  ├─ Conversations: 23                                │
│  └─ Temps moyen de réponse: 2.3s                     │
│                                                      │
│  [📊 Voir détails] [📱 Acheter forfait]              │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

## 🚀 PARTIE 9 : Planning de développement révisé

### Phase 1 : Backend & Infrastructure (Semaine 1-2)
```
Semaine 1:
✓ Configuration OpenAI (GPT-4o-mini)
✓ Nouvelles migrations (forfaits mobile, notifications)
✓ Modèles Laravel
✓ Services (OpenAI, Search, Usage, Purchase Verification)
✓ Indexation bibliothèque juridique

Semaine 2:
✓ Contrôleurs API
✓ Routes API REST
✓ Authentification API (Laravel Sanctum)
✓ Logique de quotas web vs mobile
✓ Système de notifications push (Firebase)
✓ Tests API
```

### Phase 2 : Frontend Web (Semaine 3)
```
Semaine 3:
✓ Interface chat web
✓ Gestion conversations
✓ Upload documents (30 MB)
✓ Affichage quotas
✓ Message "Quota atteint" avec liens app stores
✓ Dashboard utilisateur
✓ Dashboard Super Admin
```

### Phase 3 : Application Mobile (Semaine 4-7)
```
Semaine 4:
✓ Setup projet React Native
✓ Configuration Firebase
✓ Authentification
✓ Navigation
✓ Design système

Semaine 5:
✓ Écran chat
✓ Liste conversations
✓ Envoi/réception messages
✓ Upload documents
✓ Scan documents (caméra)

Semaine 6:
✓ Bibliothèque juridique mobile
✓ Recherche et filtres
✓ Visualisation PDFs
✓ Recherche vocale
✓ Partage de réponses

Semaine 7:
✓ Système de forfaits
✓ Écran packages
✓ Intégration Google Play Billing
✓ Intégration Apple In-App Purchase
✓ Intégration Mobile Money
✓ Gestion achats et renouvellements
```

### Phase 4 : Tests & Optimisation (Semaine 8-9)
```
Semaine 8:
✓ Tests unitaires backend
✓ Tests API
✓ Tests interface web
✓ Tests app mobile (Android)
✓ Tests app mobile (iOS)
✓ Tests de charge

Semaine 9:
✓ Tests utilisateurs (beta testing)
✓ Corrections bugs
✓ Optimisation performances
✓ Optimisation coûts OpenAI
✓ Documentation complète
```

### Phase 5 : Déploiement (Semaine 10)
```
Semaine 10:
✓ Déploiement backend production
✓ Publication Google Play Store
✓ Publication Apple App Store
✓ Configuration Firebase production
✓ Formation équipe support
✓ Documentation admin
✓ Monitoring et analytics
```

**Durée totale : 10 semaines (2.5 mois)**

---

## 💰 PARTIE 10 : Budget détaillé

### Coûts de développement

#### 1. Développement Backend + Web
```
Backend API Laravel:
  - API REST complète: 400,000 FCFA
  - Services IA (OpenAI): 200,000 FCFA
  - Système forfaits: 150,000 FCFA
  - Notifications push: 100,000 FCFA
  Sous-total: 850,000 FCFA

Frontend Web:
  - Interface chat: 250,000 FCFA
  - Dashboards: 150,000 FCFA
  - Gestion quotas: 100,000 FCFA
  Sous-total: 500,000 FCFA

Total Backend+Web: 1,350,000 FCFA
```

#### 2. Développement Mobile (React Native)
```
Setup & Infrastructure:
  - Configuration projet: 100,000 FCFA
  - Firebase setup: 50,000 FCFA
  - CI/CD: 100,000 FCFA
  Sous-total: 250,000 FCFA

Fonctionnalités:
  - Chat interface: 400,000 FCFA
  - Bibliothèque juridique: 200,000 FCFA
  - Upload/Scan documents: 250,000 FCFA
  - Recherche vocale: 150,000 FCFA
  Sous-total: 1,000,000 FCFA

Paiements In-App:
  - Google Play Billing: 200,000 FCFA
  - Apple IAP: 200,000 FCFA
  - Mobile Money: 150,000 FCFA
  Sous-total: 550,000 FCFA

Tests & Optimisation:
  - Tests: 200,000 FCFA
  - Bug fixes: 150,000 FCFA
  - Optimisation: 100,000 FCFA
  Sous-total: 450,000 FCFA

Total Mobile: 2,250,000 FCFA
```

#### 3. Frais annexes
```
Comptes développeurs:
  - Google Play Console: 25$ (≈ 15,000 FCFA) une fois
  - Apple Developer Program: 99$/an (≈ 60,000 FCFA)
  Sous-total: 75,000 FCFA

Services tiers:
  - Firebase (gratuit jusqu'à usage élevé)
  - OpenAI API: budget mensuel 20,000 FCFA
  - Stockage cloud: 10,000 FCFA/mois
  Sous-total: 30,000 FCFA/mois

Tests & Beta:
  - TestFlight (iOS): gratuit
  - Google Play Beta: gratuit
  - Devices de test: 100,000 FCFA
  Sous-total: 100,000 FCFA

Total Frais: 175,000 FCFA + 30,000 FCFA/mois
```

### Budget total développement
```
┌──────────────────────────────────────────────┐
│  BUDGET TOTAL DÉVELOPPEMENT                  │
├──────────────────────────────────────────────┤
│  Backend + Web:      1,350,000 FCFA          │
│  Mobile (RN):        2,250,000 FCFA          │
│  Frais annexes:        175,000 FCFA          │
├──────────────────────────────────────────────┤
│  TOTAL:              3,775,000 FCFA          │
│                      (~$6,100)               │
└──────────────────────────────────────────────┘
```

### Coûts d'exploitation mensuels

```
OpenAI API:
  - Budget sécurisé: 20,000 FCFA/mois
  - Couvre ~50,000 requêtes/mois

Firebase:
  - Gratuit jusqu'à 10k utilisateurs actifs
  - Puis: ~5,000 FCFA/mois

Hébergement & Stockage:
  - Serveur: 15,000 FCFA/mois
  - CDN/Stockage: 5,000 FCFA/mois

Services paiement:
  - Frais Google/Apple: 30% des revenus in-app
  - Frais Mobile Money: 2-5% des transactions

TOTAL MENSUEL: ~45,000 FCFA/mois
(hors commissions paiement)
```

---

## 📈 PARTIE 11 : Projections financières

### Scénario conservateur (Année 1)

```
MOIS 1-3 (Lancement):
  Utilisateurs app: 200
  Achats forfaits: 60 (30%)
  Revenus: 60 × 5,000 FCFA = 300,000 FCFA
  Coûts IA: 5,000 FCFA
  Coûts serveur: 45,000 FCFA
  Commissions (30%): 90,000 FCFA
  Bénéfice: 160,000 FCFA/mois

MOIS 4-6:
  Utilisateurs app: 500
  Achats forfaits: 180 (36%)
  Revenus: 180 × 5,000 FCFA = 900,000 FCFA
  Coûts IA: 10,000 FCFA
  Coûts serveur: 45,000 FCFA
  Commissions (30%): 270,000 FCFA
  Bénéfice: 575,000 FCFA/mois

MOIS 7-12:
  Utilisateurs app: 1,000
  Achats forfaits: 400 (40%)
  Revenus: 400 × 5,000 FCFA = 2,000,000 FCFA
  Coûts IA: 15,000 FCFA
  Coûts serveur: 50,000 FCFA
  Commissions (30%): 600,000 FCFA
  Bénéfice: 1,335,000 FCFA/mois

TOTAL ANNÉE 1:
  Revenus cumulés: 13,500,000 FCFA
  Coûts cumulés: 5,415,000 FCFA
  Bénéfice net: 8,085,000 FCFA
  
ROI: (8,085,000 - 3,775,000) / 3,775,000 = 114%
Retour sur investissement en 5-6 mois ✅
```

### Scénario optimiste (Année 1)

```
Si 2,000 utilisateurs app et 50% taux conversion:

Revenus annuels: ~28,000,000 FCFA
Coûts annuels: ~7,500,000 FCFA
Bénéfice net: ~20,500,000 FCFA

ROI: 443% 🚀
```

---

## 🎯 PARTIE 12 : Checklist de validation V2

Veuillez valider les points suivants :

### Technologie & Architecture

- [ ] **Modèle IA** : GPT-4o-mini (0.36 FCFA/requête) convient ?
  - [ ] Stratégie 2 niveaux (GPT-4o pour cas complexes) OK ?
  
- [ ] **Quotas web doublés** :
  - [ ] Gratuit: 10 requêtes/mois ✓
  - [ ] Solo: 100 requêtes/mois ✓
  - [ ] Basic: 200 requêtes/mois ✓
  - [ ] Pro: 400 requêtes/mois ✓

- [ ] **Budget OpenAI** : 20,000 FCFA/mois acceptable ?

### Application Mobile

- [ ] **Stack technique** : React Native convient ?
  - [ ] Alternative Flutter à considérer ?
  
- [ ] **Forfaits mobiles** :
  - [ ] 50 requêtes - 2,500 FCFA ✓
  - [ ] 150 requêtes - 5,000 FCFA ✓
  - [ ] 500 requêtes - 12,000 FCFA ✓
  - [ ] Illimité - 15,000 FCFA/mois ✓
  
- [ ] **Paiements** :
  - [ ] Google Play Billing ✓
  - [ ] Apple In-App Purchase ✓
  - [ ] Mobile Money (MTN, Orange, Moov) ✓

### Fonctionnalités

- [ ] **Upload documents** : 30 MB max OK ?
- [ ] **Scan documents** : Via caméra smartphone OK ?
- [ ] **Recherche vocale** : Nécessaire ?
- [ ] **Conservation conversations** : Illimitée confirmée ?
- [ ] **Synchronisation web ↔ mobile** : OK ?

### Workflow & UX

- [ ] **Notification quota atteint** :
  - [ ] Message in-app web ✓
  - [ ] Push notification ✓
  - [ ] Liens vers app stores ✓
  
- [ ] **Séparation quotas** : Web et mobile indépendants OK ?

### Budget & Planning

- [ ] **Budget développement** : 3,775,000 FCFA acceptable ?
- [ ] **Délai** : 10 semaines (2.5 mois) OK ?
- [ ] **Coûts mensuels** : 45,000 FCFA acceptable ?
- [ ] **Commissions stores** : 30% Google/Apple OK ?

### Publication

- [ ] **Nom de l'app** : "Dossy AI" ou autre ?
- [ ] **Logo & Design** : Qui fournit les assets ?
- [ ] **Comptes développeurs** :
  - [ ] Google Play Console à créer ?
  - [ ] Apple Developer à créer ?

---

## 📋 Documents à fournir

Pour commencer le développement, veuillez fournir :

1. **Clé API OpenAI** (ou budget pour créer un compte)
2. **Credentials Firebase** (ou je crée le projet)
3. **Logo et assets design** de l'application
4. **Nom définitif** de l'application mobile
5. **Identifiants des comptes développeurs** (Play Store / App Store)
6. **Informations bancaires** pour les paiements in-app
7. **Credentials Mobile Money** si intégration souhaitée

---

## 🚀 Prochaines étapes

Une fois validé :

1. **Semaine 1** : Je commence par le backend (API + forfaits)
2. **Point d'étape** : Validation API fonctionnelle
3. **Semaine 3** : Interface web terminée
4. **Point d'étape** : Validation web fonctionnelle
5. **Semaine 4-7** : Développement mobile
6. **Point d'étape** : Beta testing app mobile
7. **Semaine 8-10** : Tests, corrections, publication

---

## 💡 Recommandations finales

### Priorité 1 : Commencer simple
```
Phase 1 (Mois 1-2):
✓ Web chat avec quotas
✓ App mobile basique
✓ 2 forfaits seulement (50 et 150 requêtes)
✓ Paiement Google Play + Apple seulement

Phase 2 (Mois 3-4):
✓ Ajout forfait illimité
✓ Scan de documents
✓ Recherche vocale
✓ Mobile Money

Phase 3 (Mois 5+):
✓ Analytics avancés
✓ Fonctionnalités premium
✓ Optimisations
```

### Priorité 2 : Marketing
```
Stratégies de lancement:
1. Offre découverte: 20 requêtes gratuites
2. Parrainage: 50 requêtes offertes
3. Promo lancement: -30% premier mois
4. App Store Optimization (ASO)
```

### Priorité 3 : Support
```
Support utilisateurs:
- FAQ intégrée
- Chat support in-app
- Email support
- Tutoriels vidéo
```

---

**Qu'en pensez-vous de cette proposition complète ? Y a-t-il des ajustements à faire avant de commencer le développement ?** 🚀
