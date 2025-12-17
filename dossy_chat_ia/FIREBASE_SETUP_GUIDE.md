# 🔥 Guide de Configuration Firebase

Ce guide explique comment configurer Firebase pour DOSSY Chat IA.

---

## 📋 Prérequis

- Compte Google
- Accès à la [Console Firebase](https://console.firebase.google.com/)
- Flutter SDK installé
- Projet DOSSY Chat IA cloné

---

## 🚀 Étape 1: Créer un Projet Firebase

1. Allez sur https://console.firebase.google.com/
2. Cliquez sur "Ajouter un projet"
3. Nommez le projet: `dossy-chat-ia` (ou autre nom)
4. Activez Google Analytics (recommandé)
5. Créez le projet

---

## 📱 Étape 2: Ajouter les Applications

### Android

1. Dans la console Firebase, cliquez sur l'icône Android
2. Renseignez les informations:
   - **Nom du package Android**: `com.dossypro.dossy_chat_ia`
   - **Surnom de l'app**: DOSSY Chat IA (optionnel)
   - **SHA-1**: Optionnel pour le debug
3. Téléchargez `google-services.json`
4. Placez le fichier dans: `android/app/google-services.json`

### iOS

1. Dans la console Firebase, cliquez sur l'icône iOS
2. Renseignez les informations:
   - **ID de bundle iOS**: `com.dossypro.dossyChatIa`
   - **Surnom de l'app**: DOSSY Chat IA (optionnel)
3. Téléchargez `GoogleService-Info.plist`
4. Placez le fichier dans: `ios/Runner/GoogleService-Info.plist`
5. Ouvrez Xcode et ajoutez le fichier au projet Runner

---

## ⚙️ Étape 3: Activer les Services Firebase

### 3.1 Firebase Analytics

1. Dans la console, allez dans **Analytics** > **Dashboard**
2. Analytics est activé par défaut si configuré lors de la création

### 3.2 Firebase Cloud Messaging (FCM)

1. Allez dans **Build** > **Cloud Messaging**
2. Cliquez sur "Get started"
3. Notez la **Server Key** (pour le backend)

#### Configuration Android

Déjà configuré via `google-services.json` ✅

#### Configuration iOS

1. Téléchargez votre clé APNs (.p8) depuis Apple Developer
2. Dans Firebase Console > Project Settings > Cloud Messaging > iOS
3. Uploadez le fichier .p8
4. Renseignez:
   - **Key ID**
   - **Team ID**

### 3.3 Firebase Authentication (Optionnel)

Si vous voulez utiliser Firebase Auth au lieu de votre API:

1. Allez dans **Build** > **Authentication**
2. Activez les méthodes souhaitées:
   - Email/Password
   - Google
   - Téléphone (SMS)

---

## 📦 Étape 4: Vérification des Fichiers

### Structure finale:

```
dossy_chat_ia/
├── android/
│   └── app/
│       └── google-services.json          ✅ Requis
├── ios/
│   └── Runner/
│       └── GoogleService-Info.plist      ✅ Requis
└── lib/
    └── core/
        └── services/
            └── firebase_service.dart     ✅ Déjà créé
```

---

## 🧪 Étape 5: Tester la Configuration

### Test 1: Vérifier la compilation

```bash
# Android
flutter build apk --debug

# iOS
flutter build ios --debug
```

### Test 2: Vérifier Firebase dans l'app

```bash
flutter run
```

Dans les logs, cherchez:
```
✅ Firebase initialisé avec succès
✅ Analytics configuré
📱 FCM Token: xxxxx
```

### Test 3: Vérifier dans la Console Firebase

1. Lancez l'app
2. Allez dans **Analytics** > **Dashboard**
3. Vous devriez voir:
   - Utilisateurs actifs
   - Événements (app_open, session_start)

---

## 📊 Étape 6: Configurer les Événements Analytics

Les événements sont déjà configurés dans `analytics_helper.dart`:

- ✅ Authentification (login, signup)
- ✅ Recherche (fulltext, vector)
- ✅ Documents (view, download, share)
- ✅ Chat IA
- ✅ Abonnements
- ✅ Paiements
- ✅ Outils étudiants
- ✅ Parrainage

---

## 🔔 Étape 7: Configurer les Push Notifications

### Android

1. Le fichier `google-services.json` contient déjà la config FCM ✅

### iOS

1. Ouvrez Xcode
2. Allez dans **Runner** > **Signing & Capabilities**
3. Ajoutez la capability "Push Notifications"
4. Ajoutez la capability "Background Modes"
5. Cochez "Remote notifications"

### Backend (Laravel)

Pour envoyer des notifications depuis le backend:

```php
// .env
FIREBASE_SERVER_KEY=your_server_key_here

// Utiliser le controller PushNotificationsController
```

---

## 🔐 Étape 8: Sécurité Firebase

### Règles Firestore (si utilisé)

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /{document=**} {
      allow read, write: if request.auth != null;
    }
  }
}
```

### Règles Storage (si utilisé)

```javascript
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /{allPaths=**} {
      allow read, write: if request.auth != null;
    }
  }
}
```

---

## 📱 Étape 9: Configuration par Environnement

### Développement

Utilisez un projet Firebase de développement:
- Nom: `dossy-chat-ia-dev`
- Fichiers: `google-services-dev.json` / `GoogleService-Info-dev.plist`

### Production

Utilisez un projet Firebase de production:
- Nom: `dossy-chat-ia-prod`
- Fichiers: `google-services.json` / `GoogleService-Info.plist`

### Switch entre environnements

```bash
# Development
cp android/app/google-services-dev.json android/app/google-services.json

# Production
cp android/app/google-services-prod.json android/app/google-services.json
```

---

## 🧪 Étape 10: Tests des Notifications

### Test manuel (Android)

```bash
# Installer l'app
flutter run

# Copier le FCM Token depuis les logs
# Aller sur https://console.firebase.google.com/
# Cloud Messaging > Send test message
# Coller le token et envoyer
```

### Test depuis le backend

```bash
# Utiliser l'admin panel
php artisan migrate
# Aller sur /push-notifications/create
# Créer et envoyer une notification test
```

---

## 📊 Dashboard Analytics Recommandés

### Événements à surveiller

1. **Engagement**:
   - `app_open`
   - `session_start`
   - `session_end`

2. **Conversion**:
   - `view_subscription_plans`
   - `begin_checkout`
   - `purchase`

3. **Utilisation**:
   - `search`
   - `view_item` (documents)
   - `chat_message`
   - `tool_usage`

### Audiences à créer

1. **Utilisateurs gratuits**: `subscription_plan = free`
2. **Étudiants**: `subscription_plan = student`
3. **Professionnels**: `subscription_plan = professional`
4. **Utilisateurs actifs**: Ouverture app > 3x/semaine
5. **Power users**: Recherches > 10/jour

---

## 🎯 Optimisations

### Performance

```dart
// Désactiver analytics en debug
await FirebaseAnalytics.instance.setAnalyticsCollectionEnabled(
  !kDebugMode
);
```

### Quotas

Firebase gratuit offre:
- ✅ Analytics: Illimité
- ✅ FCM: Illimité
- ✅ Crashlytics: Illimité
- ⚠️ Auth: 10k/mois (si utilisé)
- ⚠️ Firestore: 50k lectures/jour (si utilisé)

---

## 🆘 Troubleshooting

### Erreur "google-services.json not found"

```bash
# Vérifier l'emplacement
ls android/app/google-services.json

# Si absent, télécharger depuis Firebase Console
```

### FCM Token null

```bash
# Vérifier les permissions Android
# android/app/src/main/AndroidManifest.xml
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
```

### Notifications ne s'affichent pas

```bash
# Vérifier que l'app n'est pas en foreground
# Les notifications foreground nécessitent un handler local
# (déjà implémenté dans firebase_service.dart)
```

---

## 📚 Ressources

- [Documentation Firebase](https://firebase.google.com/docs)
- [FlutterFire](https://firebase.flutter.dev/)
- [FCM Guide](https://firebase.google.com/docs/cloud-messaging)
- [Analytics Events](https://firebase.google.com/docs/analytics/events)

---

## ✅ Checklist Finale

- [ ] Projet Firebase créé
- [ ] App Android ajoutée
- [ ] App iOS ajoutée
- [ ] `google-services.json` placé
- [ ] `GoogleService-Info.plist` placé
- [ ] Analytics activé
- [ ] FCM activé
- [ ] Clé APNs uploadée (iOS)
- [ ] App compilée sans erreur
- [ ] Firebase initialisé dans les logs
- [ ] FCM Token reçu
- [ ] Événements Analytics visibles
- [ ] Notification test reçue

---

**🎉 Firebase est maintenant configuré pour DOSSY Chat IA !**

*Pour toute question: contact@dossypro.com*
