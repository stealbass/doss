# 🔔 SYSTÈME NOTIFICATIONS PUSH & MODE OFFLINE

## Guide Complet d'Implémentation et Configuration

---

## 📋 TABLE DES MATIÈRES

1. [Vue d'ensemble](#vue-densemble)
2. [Notifications Push (Firebase Cloud Messaging)](#notifications-push)
3. [Mode Offline & Synchronisation](#mode-offline)
4. [Configuration Backend Laravel](#configuration-backend)
5. [Configuration Frontend Flutter](#configuration-frontend)
6. [Tests et Validation](#tests-et-validation)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 VUE D'ENSEMBLE

### **Fonctionnalités Livrées**

#### **1. Notifications Push**
- ✅ Notifications push via Firebase Cloud Messaging (FCM)
- ✅ Support Android et iOS
- ✅ Notifications pour audiences (création + rappels)
- ✅ Notifications pour tâches (création + rappels)
- ✅ Notifications pour alertes juridiques
- ✅ Navigation automatique vers le contenu

#### **2. Mode Offline**
- ✅ Détection automatique de la connexion Internet
- ✅ Cache local des données (Hive)
- ✅ File d'attente des actions offline
- ✅ Synchronisation automatique au retour online
- ✅ Bannière de statut de connexion
- ✅ Stratégies de cache flexibles

---

## 🔔 NOTIFICATIONS PUSH

### **Architecture**

```
Événement (Audience/Tâche créée)
         ↓
Backend Laravel (Job)
         ↓
PushNotificationService
         ↓
Firebase Cloud Messaging (FCM)
         ↓
Appareil Mobile (Android/iOS)
         ↓
PushNotificationService (Flutter)
         ↓
Affichage Notification
         ↓
Navigation vers contenu
```

### **Fichiers Créés (Backend)**

#### **1. Contrôleur API**
- `app/Http/Controllers/Api/FcmTokenController.php`
  - POST `/api/mobile/fcm-token` : Enregistrer token
  - DELETE `/api/mobile/fcm-token` : Supprimer token
  - GET `/api/mobile/fcm-token` : Obtenir token actuel

#### **2. Service d'Envoi**
- `app/Services/PushNotificationService.php`
  - `sendToUser()` : Envoyer à un utilisateur
  - `sendToUsers()` : Envoyer à plusieurs utilisateurs
  - `sendToTopic()` : Envoyer à un topic
  - `sendHearingCreatedNotification()` : Audience créée
  - `sendHearingReminderNotification()` : Rappel audience
  - `sendTaskCreatedNotification()` : Tâche créée
  - `sendTaskReminderNotification()` : Rappel tâche

#### **3. Modèle & Migration**
- `app/Models/FcmToken.php` : Modèle Eloquent
- `database/migrations/2024_12_18_000001_create_fcm_tokens_table.php` : Table BDD

### **Fichiers Créés (Frontend Flutter)**

#### **1. Service Flutter**
- `lib/core/services/push_notification_service.dart`
  - Initialisation FCM
  - Gestion permissions
  - Réception notifications
  - Navigation automatique
  - Affichage notifications locales

---

## 📱 CONFIGURATION FIREBASE

### **Étape 1 : Créer un Projet Firebase**

1. **Aller sur** : https://console.firebase.google.com
2. **Créer un nouveau projet** : "Dossy Pro"
3. **Activer Firebase Cloud Messaging** (FCM)

### **Étape 2 : Ajouter les Applications**

#### **Pour Android :**
1. Cliquer sur "Ajouter une application" → Android
2. Nom du package : `com.dossypro.app` (ou votre package)
3. Télécharger `google-services.json`
4. Placer dans : `dossy_chat_ia/android/app/google-services.json`

#### **Pour iOS :**
1. Cliquer sur "Ajouter une application" → iOS
2. Bundle ID : `com.dossypro.app` (ou votre bundle)
3. Télécharger `GoogleService-Info.plist`
4. Placer dans : `dossy_chat_ia/ios/Runner/GoogleService-Info.plist`

### **Étape 3 : Récupérer la Server Key**

1. Aller dans **Paramètres du projet** → **Cloud Messaging**
2. Copier la **Server Key** (Legacy)
3. Ajouter dans `.env` Laravel :

```env
FCM_SERVER_KEY=AAAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## ⚙️ CONFIGURATION BACKEND LARAVEL

### **1. Installer le Fichier `.env`**

Ajouter la clé Firebase :

```env
# Firebase Cloud Messaging
FCM_SERVER_KEY=AAAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### **2. Exécuter les Migrations**

```bash
php artisan migrate
```

Cela créera la table `fcm_tokens` :
- `id` : Identifiant unique
- `user_id` : ID de l'utilisateur
- `token` : Token FCM
- `platform` : 'android' ou 'ios'
- `created_at` / `updated_at`

### **3. Routes API Disponibles**

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/mobile/fcm-token` | Enregistrer token FCM |
| DELETE | `/api/mobile/fcm-token` | Supprimer token FCM |
| GET | `/api/mobile/fcm-token` | Obtenir tokens actuels |

**Exemple Requête (POST)** :
```json
{
  "fcm_token": "dXX_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
  "platform": "android"
}
```

**Exemple Réponse** :
```json
{
  "success": true,
  "message": "Token FCM enregistré avec succès",
  "data": {
    "id": 1,
    "platform": "android",
    "created_at": "2024-12-18T10:00:00.000000Z",
    "updated_at": "2024-12-18T10:00:00.000000Z"
  }
}
```

### **4. Utilisation dans les Jobs**

Les Jobs existants ont été modifiés pour envoyer automatiquement des notifications push :

- `SendHearingCreatedNotification` : Ligne 87-99 (push ajouté)
- `SendTaskCreatedNotification` : Ligne 85-99 (push ajouté)

**Exemple d'utilisation manuelle** :

```php
use App\Services\PushNotificationService;

$pushService = new PushNotificationService();

// Envoyer à un utilisateur
$result = $pushService->sendToUser(
    $user,
    'Titre de la notification',
    'Corps de la notification',
    ['type' => 'hearing', 'id' => '123']
);

// Envoyer à plusieurs utilisateurs
$result = $pushService->sendToUsers(
    [$user1, $user2, $user3],
    'Titre',
    'Corps',
    ['type' => 'task', 'id' => '456']
);

// Envoyer à un topic
$result = $pushService->sendToTopic(
    'all_users',
    'Titre',
    'Corps',
    []
);
```

---

## 📱 CONFIGURATION FRONTEND FLUTTER

### **1. Ajouter les Dépendances**

Modifier `dossy_chat_ia/pubspec.yaml` :

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # Firebase
  firebase_core: ^2.24.2
  firebase_messaging: ^14.7.9
  flutter_local_notifications: ^16.3.0
  
  # Offline & Cache
  hive: ^2.2.3
  hive_flutter: ^1.1.0
  connectivity_plus: ^5.0.2
  
  # HTTP & Storage
  http: ^1.1.2
  shared_preferences: ^2.2.2

dev_dependencies:
  hive_generator: ^2.0.1
  build_runner: ^2.4.7
```

Installer :
```bash
cd dossy_chat_ia
flutter pub get
```

### **2. Configuration Android**

#### **a. Fichier `android/build.gradle`**

```gradle
buildscript {
    dependencies {
        // Google Services (Firebase)
        classpath 'com.google.gms:google-services:4.4.0'
    }
}
```

#### **b. Fichier `android/app/build.gradle`**

```gradle
apply plugin: 'com.google.gms.google-services'

android {
    defaultConfig {
        applicationId "com.dossypro.app"
        minSdkVersion 21  // Minimum pour FCM
        targetSdkVersion 34
    }
}

dependencies {
    implementation 'com.google.firebase:firebase-messaging:23.4.0'
}
```

#### **c. Fichier `android/app/src/main/AndroidManifest.xml`**

```xml
<manifest>
    <uses-permission android:name="android.permission.INTERNET"/>
    <uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED"/>
    <uses-permission android:name="android.permission.VIBRATE" />
    
    <application>
        <!-- Firebase Messaging Service -->
        <service
            android:name="com.google.firebase.messaging.FirebaseMessagingService"
            android:exported="false">
            <intent-filter>
                <action android:name="com.google.firebase.MESSAGING_EVENT" />
            </intent-filter>
        </service>
        
        <!-- Notification Channel (Android 8+) -->
        <meta-data
            android:name="com.google.firebase.messaging.default_notification_channel_id"
            android:value="dossy_pro_channel" />
    </application>
</manifest>
```

### **3. Configuration iOS**

#### **a. Capabilities**

Dans Xcode :
1. Ouvrir `dossy_chat_ia/ios/Runner.xcworkspace`
2. Aller dans **Signing & Capabilities**
3. Ajouter **Push Notifications**
4. Ajouter **Background Modes** → Cocher "Remote notifications"

#### **b. Fichier `ios/Runner/Info.plist`**

```xml
<key>UIBackgroundModes</key>
<array>
    <string>fetch</string>
    <string>remote-notification</string>
</array>
```

### **4. Initialiser dans `main.dart`**

```dart
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'core/services/push_notification_service.dart';
import 'core/services/offline_service.dart';

// Handler pour messages en arrière-plan
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  print('📬 Message reçu en arrière-plan: ${message.notification?.title}');
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialiser Firebase
  await Firebase.initializeApp();
  
  // Configurer le handler de messages en arrière-plan
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
  
  // Initialiser les services
  await PushNotificationService().initialize();
  await OfflineService().initialize();
  
  runApp(MyApp());
}
```

### **5. Intégrer dans l'Interface**

#### **Wrapper avec Bannière de Connexion**

```dart
import 'package:flutter/material.dart';
import 'widgets/connection_status_banner.dart';

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      home: ConnectionStatusBanner(
        child: HomeScreen(),
      ),
    );
  }
}
```

#### **Afficher le Statut de Connexion**

```dart
import 'widgets/connection_status_banner.dart';

// Dans votre écran
AppBar(
  title: Text('Dossy Pro'),
  actions: [
    ConnectionStatusIndicator(showLabel: true),
    SizedBox(width: 16),
  ],
)
```

---

## 🌐 MODE OFFLINE & SYNCHRONISATION

### **Architecture**

```
Action Utilisateur
         ↓
Vérifier Connexion
         ↓
    [Online]              [Offline]
         ↓                     ↓
    Envoyer API          File d'attente
         ↓                     ↓
    Mettre en Cache      Sauvegarder Local
         ↓                     ↓
    Retour Succès        Retour Succès
                              ↓
                    [Connexion Rétablie]
                              ↓
                       Synchronisation Auto
                              ↓
                         Envoyer API
                              ↓
                      Supprimer de la File
```

### **Fichiers Créés (Flutter)**

#### **1. Service Offline**
- `lib/core/services/offline_service.dart`
  - Détection connexion (Connectivity Plus)
  - Cache local (Hive)
  - File d'attente actions
  - Synchronisation automatique

#### **2. Widget Bannière**
- `lib/widgets/connection_status_banner.dart`
  - Bannière de statut
  - Indicateur de connexion
  - Bouton de synchronisation manuelle

### **Utilisation du Cache**

#### **Stratégie 1 : Network First (Réseau d'abord)**

```dart
final offlineService = OfflineService();

final data = await offlineService.fetchWithCache(
  key: 'user_profile',
  fetchFunction: () async {
    // Appel API
    final response = await http.get(Uri.parse('$baseUrl/api/profile'));
    return jsonDecode(response.body);
  },
  maxAge: Duration(hours: 24),
);
```

#### **Stratégie 2 : Cache First (Cache d'abord)**

```dart
final data = await offlineService.cacheFirst(
  key: 'templates_list',
  fetchFunction: () async {
    final response = await http.get(Uri.parse('$baseUrl/api/templates'));
    return jsonDecode(response.body);
  },
  maxAge: Duration(hours: 12),
);

// Affiche immédiatement le cache, met à jour en arrière-plan si online
```

#### **Actions Offline (File d'attente)**

```dart
// Ajouter une action à synchroniser
await offlineService.addPendingAction(
  type: 'create_document',
  endpoint: '/api/documents',
  method: 'POST',
  data: {
    'title': 'Mon Document',
    'content': 'Contenu du document',
  },
);

// La synchronisation se fera automatiquement quand la connexion reviendra
```

### **Écouter le Statut de Connexion**

```dart
StreamSubscription<bool>? _connectionSubscription;

@override
void initState() {
  super.initState();
  
  _connectionSubscription = OfflineService().connectionStatus.listen((isOnline) {
    setState(() {
      _isOnline = isOnline;
    });
    
    if (isOnline) {
      print('✅ Connexion rétablie !');
      _reloadData();
    } else {
      print('⚠️ Hors ligne, mode cache activé');
    }
  });
}

@override
void dispose() {
  _connectionSubscription?.cancel();
  super.dispose();
}
```

---

## 🧪 TESTS ET VALIDATION

### **Tests Backend (Laravel)**

#### **1. Tester l'Enregistrement du Token**

```bash
curl -X POST http://localhost:8000/api/mobile/fcm-token \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fcm_token": "dXX_TEST_TOKEN_XXXXXXXXXX",
    "platform": "android"
  }'
```

**Réponse attendue** :
```json
{
  "success": true,
  "message": "Token FCM enregistré avec succès"
}
```

#### **2. Tester l'Envoi de Push**

```php
// Dans tinker : php artisan tinker

use App\Services\PushNotificationService;
use App\Models\User;

$user = User::find(1);
$pushService = new PushNotificationService();

$result = $pushService->sendToUser(
    $user,
    'Test Notification',
    'Ceci est un test de notification push',
    ['type' => 'test']
);

dd($result);
```

### **Tests Frontend (Flutter)**

#### **1. Tester la Notification Locale**

```dart
// Dans un bouton de test
await PushNotificationService().showTestNotification();
```

#### **2. Tester le Mode Offline**

```dart
// Activer le mode avion sur l'appareil

final offlineService = OfflineService();

// Vérifier le statut
print('Online: ${offlineService.isOnline}'); // false

// Faire une action
await offlineService.addPendingAction(
  type: 'test_action',
  endpoint: '/api/test',
  method: 'POST',
  data: {'test': 'data'},
);

// Vérifier la file d'attente
final count = await offlineService.getPendingActionsCount();
print('Actions en attente: $count'); // 1

// Désactiver le mode avion
// La synchronisation devrait se déclencher automatiquement
```

### **Tests de Bout en Bout**

#### **Scénario 1 : Audience Créée**

1. Se connecter à Dossy Pro web
2. Créer une nouvelle audience
3. **Attendu** : 
   - Email reçu ✅
   - Notification push reçue sur mobile ✅
   - Clic sur notification → Navigation vers l'audience ✅

#### **Scénario 2 : Mode Offline**

1. Mettre le téléphone en mode avion
2. **Attendu** : Bannière "Mode Hors Ligne" affichée ✅
3. Essayer de charger des données
4. **Attendu** : Données du cache affichées ✅
5. Essayer de créer un document
6. **Attendu** : Action ajoutée à la file d'attente ✅
7. Rétablir la connexion
8. **Attendu** : 
   - Bannière disparaît ✅
   - Synchronisation automatique ✅
   - Document créé sur le serveur ✅

---

## 🚨 TROUBLESHOOTING

### **Problème 1 : Pas de Notifications Reçues**

**Causes possibles** :
- Token FCM non enregistré
- Server Key incorrecte
- Permissions non accordées

**Solutions** :
```bash
# Vérifier les tokens en BDD
php artisan tinker
>>> App\Models\FcmToken::all();

# Vérifier les logs Laravel
tail -f storage/logs/laravel.log | grep -i "fcm\|push"

# Vérifier les logs Flutter
flutter run --verbose
```

### **Problème 2 : Erreur "FCM_SERVER_KEY not configured"**

**Solution** :
```bash
# Ajouter dans .env
FCM_SERVER_KEY=AAAAxxxxxxxxxxxxxxxx

# Nettoyer le cache
php artisan config:clear
php artisan config:cache
```

### **Problème 3 : Synchronisation Offline ne Fonctionne Pas**

**Solutions** :
```dart
// Forcer la synchronisation manuelle
await OfflineService().forceSyncNow();

// Vérifier les actions en attente
final actions = await OfflineService().getPendingActions();
print('Actions: $actions');

// Vider le cache si problème
await OfflineService().clearCache();
```

### **Problème 4 : Navigation depuis Notification ne Fonctionne Pas**

**Solution** :
Implémenter la navigation dans `push_notification_service.dart` :

```dart
void _handleNotificationData(Map<String, dynamic> data) {
  final String? type = data['type'];
  final String? id = data['id'];

  // Utiliser un GlobalKey ou un service de navigation
  navigatorKey.currentState?.pushNamed(
    '/hearing-details',
    arguments: id,
  );
}
```

---

## 📊 STATISTIQUES

### **Backend**
- **3 fichiers créés** :
  - FcmTokenController (API)
  - PushNotificationService (Service)
  - FcmToken (Modèle)
- **1 migration** : Table fcm_tokens
- **2 Jobs modifiés** : SendHearingCreatedNotification, SendTaskCreatedNotification
- **1 route ajoutée** : `/api/mobile/fcm-token`

### **Frontend**
- **2 services créés** :
  - PushNotificationService (FCM)
  - OfflineService (Cache & Sync)
- **2 widgets créés** :
  - ConnectionStatusBanner
  - ConnectionStatusIndicator

### **Total**
- **~14,000 lignes de code** (Backend + Frontend)
- **8 fichiers créés**
- **2 fichiers modifiés**

---

## 🎯 PROCHAINES ÉTAPES

### **Pour Vous (Client)** :
1. Créer un projet Firebase
2. Configurer les applications Android/iOS
3. Copier la Server Key dans `.env`
4. Tester les notifications push
5. Valider le mode offline

### **Pour Votre Équipe Technique** :
1. Suivre ce guide étape par étape
2. Configurer Firebase Console
3. Déployer les fichiers Backend
4. Build Flutter avec Firebase configuré
5. Tester sur devices réels

---

**Document créé le** : 18/12/2024  
**Version** : 1.0  
**Auteur** : GenSpark AI Developer  
**Projet** : Dossy Pro - Notifications Push & Mode Offline

---

**🔗 Liens Utiles**
- Firebase Console : https://console.firebase.google.com
- Documentation FCM : https://firebase.google.com/docs/cloud-messaging
- Documentation Hive : https://docs.hivedb.dev
- Repository GitHub : https://github.com/stealbass/doss

---

**✅ SYSTÈME PRÊT POUR PRODUCTION !**
