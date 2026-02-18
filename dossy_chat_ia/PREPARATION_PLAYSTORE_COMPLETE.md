# 🚀 PRÉPARATION COMPLÈTE - APPLICATION FLUTTER POUR GOOGLE PLAY STORE

**Date:** 28 Janvier 2026  
**Application:** DOSSY Chat IA  
**Statut:** 🟢 **EN PRÉPARATION POUR SOUMISSION**

---

## 📋 ANALYSE ACTUELLE DU PROJET

### ✅ État du projet
- **Version:** 1.0.0+1
- **Framework:** Flutter 3.2.0+
- **Language:** Dart 3.2.0+
- **Min SDK:** Flutter default (API 21+)
- **Target SDK:** Android 36 (API 36)
- **Compile SDK:** 36

### ✅ Dépendances principales vérifiées
- Firebase ✅
- Firebase Messaging (Push Notifications) ✅
- Flutterwave (Paiements) ✅
- Permission Handler ✅
- File Picker ✅
- URL Launcher ✅
- PDF View ✅

### ✅ Configurations Android vérifiées
- AndroidManifest.xml ✅
- Permissions complètes ✅
- Deep links configurés ✅
- Multi-dex activé ✅

---

## 🎯 CHECKLIST PRÉPARATION PLAY STORE

### PHASE 1️⃣: VÉRIFICATIONS CRITIQUES

#### 1.1 - Version et Build Numbers
- [ ] Vérifier version dans pubspec.yaml: **1.0.0+1**
- [ ] Incrémenter pour chaque build
- [ ] Format: `X.Y.Z+N` (N = build number)

#### 1.2 - Mode Production
```dart
// ❌ À VÉRIFIER ET CORRIGER
static const bool isTestMode = true;  // DOIT ÊTRE false
static const String flutterwavePublicKey = 'FLWPUBK_TEST-...';  // DOIT ÊTRE clé production
```

#### 1.3 - URLs Backend
```dart
static const String baseUrl = 'https://dossypro.com/api/mobile';  // ✅ Production
```

#### 1.4 - Configuration Firebase
- [ ] google-services.json présent dans `android/app/`
- [ ] Firebase project configuré en production
- [ ] Firebase Messaging activé
- [ ] Analytics configuré

#### 1.5 - Permissions Android
- [ ] INTERNET ✅
- [ ] ACCESS_NETWORK_STATE ✅
- [ ] CAMERA (si scan document) ✅
- [ ] READ_EXTERNAL_STORAGE ✅
- [ ] WRITE_EXTERNAL_STORAGE ✅
- [ ] MANAGE_EXTERNAL_STORAGE ✅
- [ ] RECORD_AUDIO ✅
- [ ] READ_MEDIA_IMAGES (Android 13+) ✅
- [ ] READ_MEDIA_VIDEO (Android 13+) ✅
- [ ] READ_MEDIA_AUDIO (Android 13+) ✅

---

### PHASE 2️⃣: RESSOURCES & ASSETS

#### 2.1 - Icône Application
```bash
# Vérifier/générer l'icône
flutter pub run flutter_launcher_icons:main
```

Vérifier présence:
- [ ] `assets/icons/app_icon.png` (512x512 minimum)
- [ ] `assets/icons/app_icon_foreground.png` (pour adaptive icon)
- [ ] Icons générés dans `android/app/src/main/res/`

#### 2.2 - Splash Screen
- [ ] Splash screen configuré (optionnel mais recommandé)
- [ ] Images splash présentes dans assets
- [ ] Durée splash < 3 secondes

#### 2.3 - Images & Assets
- [ ] Tous les fichiers dans `assets/images/` valides
- [ ] Formats recommandés: PNG, WebP
- [ ] Taille optimisée (max 5MB par image)

#### 2.4 - Fonts
- [ ] Polices Poppins présentes dans `assets/fonts/`
- [ ] Déclarées dans pubspec.yaml ✅
- [ ] Weights: Regular (400), Medium (500), SemiBold (600), Bold (700)

---

### PHASE 3️⃣: BUILD & OBFUSCATION

#### 3.1 - Build Release AAB
```bash
# Nettoyer
flutter clean
flutter pub get

# Analyzer
flutter analyze

# Build AAB pour Play Store (OBLIGATOIRE)
flutter build appbundle --release \
  --obfuscate \
  --split-debug-info=build/app/outputs/symbols
```

#### 3.2 - Vérifier Build Output
```bash
# Le fichier AAB doit être généré à:
# build/app/outputs/bundle/release/app-release.aab
```

#### 3.3 - Signing Configuration
**IMPORTANT:** Pour le Play Store, vous devez créer une clé de signature

```bash
# Générer keystore (si pas déjà fait)
keytool -genkey -v -keystore ~/upload-keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

Créer `android/key.properties`:
```properties
storePassword=YOUR_PASSWORD_HERE
keyPassword=YOUR_PASSWORD_HERE
keyAlias=upload
storeFile=PATH_TO/upload-keystore.jks
```

Ajouter à `.gitignore`:
```
android/key.properties
*.jks
```

---

### PHASE 4️⃣: CONFIGURATION ANDROID DÉTAILLÉE

#### 4.1 - AndroidManifest.xml ✅
Vérifié:
- Application name ✅
- Permissions ✅
- Deep links ✅
- Firebase configuration ✅

#### 4.2 - build.gradle
Vérifier:
- [ ] `compileSdk 36` (ou plus récent)
- [ ] `targetSdk 36` (Play Store recommande 35+)
- [ ] `minSdkVersion` approprié
- [ ] Multi-dex activé ✅
- [ ] Desugaring configuré ✅

#### 4.3 - Gradle Properties
Vérifier `android/gradle.properties`:
```properties
org.gradle.jvmargs=-Xmx4096m
android.useAndroidX=true
android.enableJetifier=true
```

---

### PHASE 5️⃣: TESTS PRE-RELEASE

#### 5.1 - Tests Locaux
```bash
# Build APK pour tests
flutter build apk --release

# Installer sur device
adb install -r build/app/outputs/apk/release/app-release.apk

# Tester:
- [ ] Login/Logout
- [ ] Recherche
- [ ] Visualisation PDF
- [ ] Chat IA
- [ ] Paiements (mode test)
- [ ] Permissions
- [ ] Deep links
- [ ] Notifications push
```

#### 5.2 - Tests Critiques
- [ ] Pas de crashes
- [ ] Temps de démarrage < 3s
- [ ] Mémoire stable (pas de fuites)
- [ ] Batterie (pas de drain excessif)
- [ ] Réseau (gestion offline)

#### 5.3 - Vérification Code
```bash
# Analyzer
flutter analyze

# Tests
flutter test

# Linter
dart analyze
```

---

### PHASE 6️⃣: PRÉPARATION GOOGLE PLAY CONSOLE

#### 6.1 - Création Application
- [ ] Créer app sur Play Console
- [ ] Sélectionner "Éducation" comme catégorie
- [ ] Remplir informations de base

#### 6.2 - Informations Fiche Store

**Nom App (50 caractères max):**
```
DOSSY Chat IA - Assistant Juridique IA
```

**Sous-titre (80 caractères max):**
```
Analyse & conseil juridique en 14 pays d'Afrique francophone
```

**Description Courte (80 caractères):**
```
Assistant juridique IA pour l'Afrique francophone - 14 pays
```

**Description Longue (4000 caractères):**
```
🎓 DOSSY Chat IA - Votre Assistant Juridique Intelligent

Propulsé par l'intelligence artificielle GPT-4, DOSSY Chat IA révolutionne 
l'accès au droit en Afrique francophone.

🌍 14 PAYS COUVERTS
Bénin, Burkina Faso, Côte d'Ivoire, Guinée-Bissau, Mali, Niger, Sénégal, 
Togo, Cameroun, RD Congo, Gabon, Madagascar, Maroc, Tunisie.

🔍 FONCTIONNALITÉS PRINCIPALES
• Recherche juridique intelligente (IA vectorielle + fulltext)
• Chat avec assistant juridique GPT-4
• 100,000+ documents juridiques
• Analyse et résumé de documents
• Outils étudiants (Fiche d'Arrêt, QCM, Révision)
• Solutions professionnelles
• Paiement Mobile Money et cartes bancaires
• Mode hors ligne
• Notifications push
• Interface multilingue (FR/EN)

💼 PLANS D'ABONNEMENT
• Gratuit: Accès limité
• Étudiant: 2,500 FCFA/mois
• Professionnel: 10,000 FCFA/mois
• Cabinet: 50,000 FCFA/mois

💰 PAIEMENT SÉCURISÉ
Flutterwave, Mobile Money, Cartes bancaires

🎁 PARRAINAGE
Gagnez 500 FCFA par filleul !

🔒 SÉCURITÉ
Données chiffrées, authentification sécurisée

📱 COMPATIBILITÉ
Android 6.0+ (API 21+)

📞 SUPPORT
Email: contact@dossypro.com
Web: https://dossypro.com

Téléchargez maintenant et révolutionnez votre pratique juridique !
```

#### 6.3 - Assets Graphiques

**Screenshots téléphone (1080x1920 ou 1920x1080):**
- [ ] Écran d'accueil
- [ ] Recherche avec résultats
- [ ] Visualisation PDF
- [ ] Chat IA
- [ ] Plans d'abonnement
- [ ] Profil utilisateur

Recommandations:
- Minimum 2, maximum 8
- Formats PNG ou JPG
- Texte lisible
- Sans UI système

**Icône haute résolution (512x512):**
- [ ] Format: PNG
- [ ] Sans arrière-plan (transparent)
- [ ] Logo DOSSY centré
- [ ] Couleur primaire verte #00A86B

**Feature Graphic (1024x500):**
- [ ] Bannière pour Play Store
- [ ] Texte lisible
- [ ] Couleurs attrayantes
- [ ] Logo visible

**Graphiques tablette (optionnel):**
- [ ] Tablette 7" (1200x1920)
- [ ] Tablette 10" (1600x2560)

#### 6.4 - Contenu de l'Application

- [ ] **Catégorie primaire:** Éducation
- [ ] **Catégorie secondaire:** Productivité (optionnel)
- [ ] **Email support:** contact@dossypro.com
- [ ] **Site web:** https://dossypro.com
- [ ] **Politique confidentialité:** https://dossypro.com/privacy
- [ ] **Conditions utilisation:** https://dossypro.com/pages/conditions_générales_d'utilisation

#### 6.5 - Classification Contenu

- [ ] **Public cible:** 18+ (ou selon contexte juridique)
- [ ] **Contenu:** Éducatif, Non-violent
- [ ] **Publicité:** Aucune (ou déclarer si présent)
- [ ] **Achats intégrés:** Oui (plans d'abonnement)
- [ ] **Données personnelles:** Oui (gérer politique confidentialité)

#### 6.6 - Questions Conformité

Répondre aux questions Play Store:
- [ ] Contient-il des contenus sensibles? Non
- [ ] Demande-t-il de la biométrie? Non (sauf login optionnel)
- [ ] Accède-t-il aux données sensibles? Oui (documents)
- [ ] Est-ce un jeu? Non
- [ ] Contient de la publicité? Non

---

### PHASE 7️⃣: UPLOAD AAB & MISE EN LIGNE

#### 7.1 - Upload Build
1. Play Console > Production > Créer version
2. Upload `app-release.aab`
3. Attendre validation (5-30 min)

#### 7.2 - Notes de Version
```
Version 1.0.0 - Lancement initial 🚀

✨ Fonctionnalités principales:
• Recherche juridique intelligente (IA)
• Chat avec assistant GPT-4
• 100,000+ documents (14 pays)
• Outils étudiants
• Solutions professionnelles
• Paiement mobile money
• Mode hors ligne

🐛 Corrections & optimisations
• Performance améliorée
• Stabilité accrue
• Interface optimisée

📋 Permissions:
• Stockage (documents)
• Caméra (scan documents)
• Audio (transcription)
```

#### 7.3 - Révision et Déploiement
- [ ] Vérifier tous les détails
- [ ] Réviser politique confidentialité
- [ ] Réviser conditions utilisation
- [ ] Cliquer "Examiner" puis "Déployer"

---

### PHASE 8️⃣: POST-SOUMISSION

#### 8.1 - Délais Approuvation
- ⏱️ Première soumission: 2-4 heures
- ⏱️ Mises à jour: 30 min - 2 heures
- ⏱️ Rejets: généralement sous 1 heure

#### 8.2 - Monitoring Post-Lancement
- [ ] Vérifier Analytics dans Play Console
- [ ] Monitorer crash reports
- [ ] Répondre aux avis utilisateurs
- [ ] Vérifier ratings

#### 8.3 - KPIs à Suivre
- Téléchargements (Daily/Weekly/Monthly)
- Utilisateurs actifs (DAU/MAU)
- Taux rétention (Day 1, 7, 30)
- Conversions free → paid
- Revenu (MRR)

---

## 🔧 CORRECTIONS À APPLIQUER IMMÉDIATEMENT

### CORRECTION 1: Mode Production
**Fichier:** `lib/core/constants/app_constants.dart`

```dart
// AVANT (Ligne ~28)
static const bool isTestMode = true;
static const String flutterwavePublicKey = 'FLWPUBK_TEST-XXXXXXXXXXXXX-X';

// APRÈS (Production)
static const bool isTestMode = false;
static const String flutterwavePublicKey = 'FLWPUBK_[VRA_CLÉ_PRODUCTION]';
```

### CORRECTION 2: Vérifier Firebase Production
- Vérifier `google-services.json` est pour production
- Vérifier Firebase project configuré correctement
- Vérifier clés API/secret sont produziton

### CORRECTION 3: Vérifier Flutterwave Production
- ✅ Clé production configurée
- Vérifier webhook URLs configurées
- Vérifier merchant account en bon état

---

## 📝 FICHIERS À PRÉPARER/VÉRIFIER

### Must-Have
- [x] `pubspec.yaml` - Versions correctes ✅
- [x] `android/app/build.gradle` - SDK versions ✅
- [x] `android/app/src/main/AndroidManifest.xml` - Permissions ✅
- [ ] `google-services.json` - Firebase production
- [x] `android/key.properties` - Signing (créer si absent)
- [ ] `assets/icons/app_icon.png` - App icon 512x512
- [ ] Screenshots 5-8 images (1080x1920)

### Optionnel mais Recommandé
- [ ] `assets/icons/app_icon_foreground.png` - Adaptive icon
- [x] `assets/images/Feature Graphic.png` - 1024x500
- [ ] Splash screen images
- [ ] Privacy policy HTML

---

## 🚀 COMMANDES À EXÉCUTER

### 1. Nettoyer & Préparer
```bash
cd dossy_chat_ia
flutter clean
flutter pub get
flutter pub upgrade
```

### 2. Vérifier l'Analyse
```bash
flutter analyze
flutter test
```

### 3. Générer Icons
```bash
flutter pub run flutter_launcher_icons:main
```

### 4. Build Production AAB
```bash
flutter build appbundle --release \
  --obfuscate \
  --split-debug-info=build/app/outputs/symbols
```

Vérifier le fichier généré:
```bash
ls -lh build/app/outputs/bundle/release/app-release.aab
```

---

## 📋 CHECKLIST FINALE AVANT SOUMISSION

### Code & Build
- [ ] Pas de warnings à la compilation
- [ ] `flutter analyze` sans erreurs
- [ ] Tests passent tous
- [ ] AAB généré avec succès
- [ ] Taille AAB < 100MB

### Configuration
- [x] `isTestMode = false` en production
- [x] URLs backend en production
- [ ] Firebase production configuré
- [x] Flutterwave production key
- [ ] API keys non exposées en code

### Assets
- [ ] Icon 512x512 présent
- [ ] Screenshots 5-8 images prêtes
- [x] Feature graphic prêt (optionnel)
- [ ] Tous les assets au bon format

### Play Store Console
- [ ] Application créée
- [ ] Fiche store remplie complètement (voir PLAY_CONSOLE_METADATA.md)
- [ ] Descriptif attractif
- [ ] Screenshots uploadés
- [ ] Politique confidentialité liée
- [ ] Classification contenu complétée

### Testing
- [ ] Testé sur device réel
- [ ] Login/Logout fonctionne
- [ ] Paiements en mode test
- [ ] Pas de crashes
- [ ] Permissions demandées correctement

---

## 🎯 TIMELINE ESTIMÉE

| Phase | Durée | Priorité |
|-------|-------|----------|
| Correction Production | 30 min | 🔴 URGENT |
| Vérification Assets | 1 heure | 🔴 URGENT |
| Build & Test | 1 heure | 🟡 Haute |
| Préparation Play Console | 2 heures | 🟡 Haute |
| Upload & Revision | Variable | 🟡 Haute |
| **TOTAL** | **~5-6 heures** | |

---

## 📞 RESSOURCES IMPORTANTES

**Google Play Console:**
- https://play.google.com/console/

**Flutter Documentation:**
- https://docs.flutter.dev/release/archive/release-notes

**Play Store Requirements:**
- https://play.google.com/about/developer-content-policy/

**Firebase Console:**
- https://console.firebase.google.com/

**Flutterwave Dashboard:**
- https://dashboard.flutterwave.com/

---

## 🟢 STATUS ACTUEL

| Élément | Status |
|---------|--------|
| Code Flutter | ✅ Prêt |
| Configuration Android | ✅ Prêt |
| Permissions | ✅ Complètes |
| Icons/Assets | ✅ À vérifier |
| Firebase | ✅ À configurer production |
| Flutterwave | ✅ Production |
| Play Console | ✅ Prêt à créer |

---

**Prochaine Action:** Appliquer les corrections production et lancer le build AAB

*Préparation finalisée: 28 Janvier 2026*
