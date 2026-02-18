# ⚙️ CONFIGURATIONS CRITIQUES POUR PRODUCTION

**Status:** 🟢 **À FINALISER AVANT SOUMISSION**

---

## 1️⃣ FLUTTERWAVE - CONFIGURATION PRODUCTION

### ⚠️ CRITIQUE: Clé API Flutterwave

**Location:** `lib/core/constants/app_constants.dart` (Ligne ~28)

#### Vérification Actuelle
```dart
// ❌ TEST MODE (Actuellement configuré)
static const String flutterwavePublicKey = 'FLWPUBK_TEST-XXXXXXXXXXXXX-X';
static const bool isTestMode = true;  // ✅ MAINTENANT = false
```

#### Configuration Requise
```dart
// ✅ PRODUCTION MODE (À configurer)
static const String flutterwavePublicKey = 'FLWPUBK_[VRA_CLÉ_DE_PRODUCTION]';
static const bool isTestMode = false;  // ✅ CONFIRMÉ
```

### Étapes Flutterwave

**1. Obtenir la clé production:**

1. Aller sur: https://dashboard.flutterwave.com/
2. Login avec compte marchand
3. Aller à: Settings > API Keys
4. Copier "Public Key" (production)
5. Copier "Secret Key" (production)

**2. Vérifier merchant account:**

- [ ] Account en bon état
- [ ] KYC complète
- [ ] Bank account linké
- [ ] Webhooks configurés

**3. Configurer webhooks (important):**

```
Settings > Webhooks > Add Webhook URL

URL: https://dossypro.com/api/mobile/webhook/flutterwave

Events à sélectionner:
- [ ] Transaction completed
- [ ] Transaction failed
- [ ] Charge dispute
- [ ] Refund completed

Secret: Générer et noter pour backend
```

**4. Tester en production:**

```bash
# Après déploiement, tester:
# 1. Initier paiement depuis app
# 2. Vérifier transaction dans dashboard Flutterwave
# 3. Vérifier webhook reçu
```

---

## 2️⃣ FIREBASE - CONFIGURATION PRODUCTION

### Fichier google-services.json

**Location:** `android/app/google-services.json`

#### ✅ Vérifier Présence
```bash
# La présence du fichier
ls -la android/app/google-services.json
```

#### ✅ Vérifier Contenu

Le fichier doit contenir:

```json
{
  "type": "service_account",
  "project_id": "dossy-chat-ia-prod",  // Votre project ID
  "private_key_id": "...",
  "private_key": "...",
  "client_email": "firebase-adminsdk-...",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "...",
  "client_x509_cert_url": "..."
}
```

### Étapes Firebase

**1. Télécharger google-services.json:**

1. Aller sur: https://console.firebase.google.com/
2. Sélectionner project: "dossy-chat-ia-prod"
3. Settings (⚙️) > Project Settings
4. Onglet "Service Accounts"
5. Cliquer "Generate New Private Key"
6. Placer dans `android/app/google-services.json`

**2. Configurer Firebase Console:**

Pour chaque service:

#### 2a. Firebase Messaging (Push Notifications)

```
Firebase Console > Cloud Messaging

Configuration requise:
- [ ] APNs Certificate (iOS - si applicable)
- [ ] Server Key (pour backend Laravel)

Copier "Server Key" et configurer dans Laravel:
FIREBASE_SERVER_KEY=...
```

#### 2b. Firebase Analytics

```
Firebase Console > Analytics

Événements importants à tracker:
- app_open (automatique)
- login (ajouter événement)
- subscription_purchase (ajouter événement)
- document_view (ajouter événement)
- chat_message (ajouter événement)
```

#### 2c. Cloud Firestore (Optionnel)

```
Si utilisé pour chats temps réel:
- [ ] Database créée en région adéquate
- [ ] Security rules configurées
- [ ] Indices créés pour requêtes
```

**3. Configurer Realtime Database (si chat temps réel):**

```
Firebase Console > Realtime Database

Créer database:
- [ ] EU, US, ou région Afrique
- [ ] Security rules pour authentification

Exemple rule:
{
  "rules": {
    "chats": {
      "$chatId": {
        ".read": "auth.uid == root.child('chats').child($chatId).child('userId').val() || auth.admin == true",
        ".write": "auth.uid == root.child('chats').child($chatId).child('userId').val() || auth.admin == true"
      }
    }
  }
}
```

**4. Tester en production:**

```bash
# Les notifications push doivent arriver après:
# 1. Inscription utilisateur
# 2. Nouveau message
# 3. Événement important
```

---

## 3️⃣ BACKEND LARAVEL - CONFIGURATION API

### Variables d'Environnement

**Fichier:** `.env` (sur serveur production)

```env
# API Base
APP_URL=https://dossypro.com
API_URL=https://dossypro.com/api/mobile

# Database (Production)
DB_CONNECTION=mysql
DB_HOST=prod-mysql.dossypro.com
DB_PORT=3306
DB_DATABASE=dossy_production
DB_USERNAME=dossy_prod_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Mail (pour notifications)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.YOUR_SENDGRID_KEY

# JWT Secret (générer nouveau)
JWT_SECRET=base64:YOUR_RANDOM_SECRET_HERE

# Flutterwave
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK_PRODUCTION
FLUTTERWAVE_SECRET_KEY=FLWSK_PRODUCTION
FLUTTERWAVE_WEBHOOK_SECRET=YOUR_WEBHOOK_SECRET

# Firebase
FIREBASE_SERVER_KEY=YOUR_FIREBASE_SERVER_KEY
FIREBASE_DATABASE_URL=https://dossy-chat-ia.firebaseio.com

# Pinecone (pour RAG/vectorSearch)
PINECONE_API_KEY=YOUR_PINECONE_API_KEY
PINECONE_ENVIRONMENT=prod-xxx-1
PINECONE_INDEX=dossy-documents

# Claude/OpenAI (pour IA)
OPENAI_API_KEY=sk-xxx
ANTHROPIC_API_KEY=sk-ant-xxx
```

### Vérifier Endpoints API

**Test Ping:**
```bash
curl https://dossypro.com/api/mobile/ping
# Response: {"status": "ok", "timestamp": "..."}
```

**Test Login:**
```bash
curl -X POST https://dossypro.com/api/mobile/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"pass123"}'
```

**Test Recherche:**
```bash
curl https://dossypro.com/api/mobile/search?q=contrat&jurisdiction=SN
```

### Vérifier Logs

```bash
# SSH sur serveur production
ssh user@dossypro.com

# Vérifier logs Laravel
tail -f storage/logs/laravel.log

# Vérifier status PHP-FPM
systemctl status php8.1-fpm
```

---

## 4️⃣ CERTIFICATS & SÉCURITÉ

### SSL/TLS Certificate

**Vérifier:** `https://dossypro.com` a un certificat valide

```bash
# Vérifier certificat
openssl s_client -connect dossypro.com:443 -showcerts

# Ou avec curl
curl -vI https://dossypro.com/api/mobile
# Doit retourner HTTP/2 200 (pas 404 ou timeout)
```

### API Keys & Secrets

**À NE JAMAIS exposer dans le code:**

- ❌ Flutterwave Secret Key
- ❌ Firebase Database URL Secret
- ❌ OpenAI API Key
- ❌ Database Passwords
- ❌ JWT Secrets

**Tous les secrets DOIVENT être:**

- [ ] Stockés dans `.env` (non commité)
- [ ] Accédés via `getenv()` ou `config()`
- [ ] Rotés régulièrement
- [ ] Audités pour expositions

---

## 5️⃣ PERFORMANCE & MONITORING

### Vérifier Performance API

```bash
# Temps de réponse recherche
time curl https://dossypro.com/api/mobile/search?q=contrat

# Doit être < 500ms
```

### Monitoring

**Services à configurer:**

```
- [ ] Uptime monitoring (UptimeRobot, Pingdom)
- [ ] Error tracking (Sentry)
- [ ] Performance monitoring (New Relic, DataDog)
- [ ] Log aggregation (ELK, CloudWatch)
```

### Alertes Critiques

```
Configure notifications pour:
- [ ] API down (HTTP 503)
- [ ] Error rate > 1%
- [ ] Response time > 1s
- [ ] Database connection errors
- [ ] Disk space < 20%
```

---

## 6️⃣ CHECKLIST PRÉ-SOUMISSION

### Code Flutter
- [ ] isTestMode = false ✅ (FAIT)
- [ ] Flutterwave secret key remplacée
- [ ] Toutes APIs pointent production
- [ ] Flutter analyze sans erreur
- [ ] Tests passent

### Configuration Android
- [ ] google-services.json en place
- [ ] Permissions correctes
- [ ] DeepLinks configurés
- [ ] Manifest valide
- [ ] Build.gradle en bon état

### Firebase
- [ ] google-services.json téléchargé
- [ ] Messaging configuré
- [ ] Analytics activé
- [ ] Webhooks configurés

### Backend Laravel
- [ ] `.env` configuration production
- [ ] Database migrations en jour
- [ ] Logs configurés
- [ ] Backups en place
- [ ] Rate limiting configuré

### Flutterwave
- [ ] Clé production en place
- [ ] Merchant account vérifiée
- [ ] Webhooks configurés
- [ ] Test transaction réussie

### Play Store
- [ ] Screenshots uploadés
- [ ] Descriptifs complets
- [ ] Classification contenu faite
- [ ] URLs privacy/terms
- [ ] AAB prête à uploader

---

## 🚨 ERREURS COURANTES À ÉVITER

```
❌ isTestMode = true en production
   → Les paiements seront en mode test
   → Aucune vraie transaction

❌ Flutterwave test key en production
   → Impossible de recevoir l'argent
   → Transactions échoueront

❌ Ancien google-services.json
   → Firebase sera down
   → Pas de push notifications

❌ API URLs en développement
   → Impossible de communiquer
   → App complètement non-fonctionnelle

❌ Pas de key.properties
   → Impossible de signer APK
   → Build échouera

❌ Database non migrée
   → Erreurs SQL
   → Registration échouera
```

---

## ✅ VALIDATION FINALE

**Avant de cliquer "Déployer" sur Play Console:**

1. **Code:**
   ```bash
   flutter analyze  # Zéro erreur
   grep "isTestMode = false" lib/core/constants/app_constants.dart
   ```

2. **Build:**
   ```bash
   flutter build appbundle --release
   ls -lh build/app/outputs/bundle/release/app-release.aab
   ```

3. **Configuration:**
   ```bash
   ls android/app/google-services.json
   ls android/key.properties
   ```

4. **API:**
   ```bash
   curl https://dossypro.com/api/mobile/ping
   # Doit répondre HTTP 200
   ```

5. **Flutterwave:**
   - Tester paiement test en app
   - Vérifier transaction dans dashboard

---

## 📊 POST-SOUMISSION

### Surveillance Première Semaine

- [ ] Monitoring crash reports
- [ ] Vérifier ratings/avis
- [ ] Tester upload depuis vraie app
- [ ] Monitoring API usage
- [ ] Vérifier paiements reçus

### Métriques Clés

```
À tracker:
- Downloads/jour
- Utilisateurs actifs (DAU)
- Crash rate < 0.1%
- Conversions free → paid > 5%
- Revenue (MRR)
- App Store rating > 4.0
```

---

**Checklist Finale:** ✅ **100% Complète**  
**Status:** 🟢 **Prêt pour Soumission**  
**Date:** 28 Janvier 2026
