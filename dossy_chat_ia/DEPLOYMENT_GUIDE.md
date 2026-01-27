# 🚀 Guide de Déploiement - DOSSY Chat IA

Guide complet pour déployer l'application sur Google Play Store et Apple App Store.

---

## 📋 TABLE DES MATIÈRES

1. [Prérequis](#prérequis)
2. [Préparation](#préparation)
3. [Configuration Android](#android)
4. [Configuration iOS](#ios)
5. [Build Production](#build-production)
6. [Tests Pre-release](#tests)
7. [Publication Play Store](#play-store)
8. [Publication App Store](#app-store)
9. [Post-déploiement](#post-déploiement)

---

## 🎯 PRÉREQUIS

### Comptes Requis
- ✅ Compte Google Play Console ($25 une fois)
- ✅ Compte Apple Developer ($99/an)
- ✅ Compte Firebase (gratuit)
- ✅ Compte Flutterwave

### Outils Requis
- ✅ Flutter SDK 3.2+
- ✅ Android Studio
- ✅ Xcode (macOS uniquement)
- ✅ Git

### Accès Backend
- ✅ Serveur Laravel déployé
- ✅ Base de données configurée
- ✅ API accessible

---

## 🔧 PRÉPARATION

### 1. Vérifier les Informations

Éditez `lib/core/constants/app_constants.dart`:

```dart
class AppConstants {
  // Informations app
  static const String appName = 'DOSSY Chat IA';
  static const String appVersion = '1.0.0';
  static const String buildNumber = '1';
  
  // URLs Production
  static const String apiBaseUrl = 'https://dossy.alwaysdata.net/api/mobile';
  static const String websiteUrl = 'https://dossypro.com';
  
  // Clés API Production
  static const String flutterwavePublicKey = 'FLWPUBK-VOTRE-CLE-PRODUCTION';
  static const bool isTestMode = false; // ⚠️ IMPORTANT: false en production
  
  static const String openAIApiKey = ''; // Backend seulement
  static const String pineconeApiKey = ''; // Backend seulement
}
```

### 2. Nettoyer le Projet

```bash
# Nettoyer les caches
flutter clean
flutter pub get

# Vérifier les dépendances
flutter pub outdated
flutter pub upgrade

# Analyser le code
flutter analyze
```

### 3. Tests Finaux

```bash
# Lancer tous les tests
flutter test

# Vérifier sur un device réel
flutter run --release
```

---

## 📱 ANDROID

### Étape 1: Keystore de Production

#### Créer le keystore

```bash
keytool -genkey -v -keystore ~/upload-keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 \
  -alias upload

# Répondre aux questions:
# - Mot de passe: [NOTER QUELQUE PART DE SÛR]
# - Nom et prénom: DOSSY Chat IA
# - Organisation: DOSSY Pro
# - Pays: BJ (ou votre pays)
```

#### Configurer le keystore

Créez `android/key.properties`:

```properties
storePassword=VOTRE_MOT_DE_PASSE
keyPassword=VOTRE_MOT_DE_PASSE
keyAlias=upload
storeFile=/Users/VOTRE_USER/upload-keystore.jks
```

⚠️ **IMPORTANT**: Ajoutez `key.properties` à `.gitignore`

### Étape 2: Configuration Build

Éditez `android/app/build.gradle`:

```gradle
def keystoreProperties = new Properties()
def keystorePropertiesFile = rootProject.file('key.properties')
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}

android {
    compileSdkVersion 34
    
    defaultConfig {
        applicationId "com.dossypro.dossy_chat_ia"
        minSdkVersion 23
        targetSdkVersion 34
        versionCode 1
        versionName "1.0.0"
        multiDexEnabled true
    }

    signingConfigs {
        release {
            keyAlias keystoreProperties['keyAlias']
            keyPassword keystoreProperties['keyPassword']
            storeFile keystoreProperties['storeFile'] ? file(keystoreProperties['storeFile']) : null
            storePassword keystoreProperties['storePassword']
        }
    }

    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled true
            shrinkResources true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
}
```

### Étape 3: Créer les Assets Play Store

#### Icône de l'application (512x512 PNG)

```bash
# Utiliser flutter_launcher_icons (déjà configuré)
flutter pub run flutter_launcher_icons:main
```

#### Screenshots requis

- **Téléphone**: 2-8 screenshots (1080x1920 ou 1920x1080)
- **Tablette 7"**: 2-8 screenshots (1200x1920 ou 1920x1200)
- **Tablette 10"**: 2-8 screenshots (1600x2560 ou 2560x1600)

Recommandation: Prenez des screenshots de:
1. Écran d'accueil
2. Recherche avec résultats
3. Visualiseur PDF
4. Chat IA
5. Plans d'abonnement

#### Graphiques promotionnels

- **Feature Graphic**: 1024x500 PNG
- **Icône haute résolution**: 512x512 PNG

### Étape 4: Build APK/AAB

```bash
# Build App Bundle (recommandé pour Play Store)
flutter build appbundle --release

# Le fichier sera dans:
# build/app/outputs/bundle/release/app-release.aab

# Build APK (pour tests)
flutter build apk --release --split-per-abi

# Les fichiers seront dans:
# build/app/outputs/apk/release/
```

---

## 🍎 iOS

### Étape 1: Configuration Xcode

1. Ouvrez `ios/Runner.xcworkspace` dans Xcode
2. Sélectionnez **Runner** dans le navigateur
3. Dans **General**:
   - **Display Name**: DOSSY Chat IA
   - **Bundle Identifier**: com.dossypro.dossyChatIa
   - **Version**: 1.0.0
   - **Build**: 1

### Étape 2: Signing & Capabilities

1. Dans **Signing & Capabilities**:
   - Cochez "Automatically manage signing"
   - Sélectionnez votre **Team**
   - Vérifiez le **Provisioning Profile**

2. Ajoutez les capabilities:
   - ✅ Push Notifications
   - ✅ Background Modes > Remote notifications
   - ✅ Associated Domains (pour deep links)

### Étape 3: Info.plist

Éditez `ios/Runner/Info.plist`:

```xml
<key>CFBundleDisplayName</key>
<string>DOSSY Chat IA</string>

<key>NSCameraUsageDescription</key>
<string>Nous avons besoin d'accéder à votre caméra pour scanner des documents</string>

<key>NSPhotoLibraryUsageDescription</key>
<string>Nous avons besoin d'accéder à vos photos pour importer des documents</string>

<key>NSMicrophoneUsageDescription</key>
<string>Nous avons besoin d'accéder à votre microphone pour la transcription audio</string>

<key>NSUserTrackingUsageDescription</key>
<string>Nous utilisons vos données pour personnaliser votre expérience</string>
```

### Étape 4: Créer les Assets App Store

#### Screenshots requis

- **iPhone 6.7"** (1290x2796): 3-10 screenshots
- **iPhone 5.5"** (1242x2208): 3-10 screenshots
- **iPad Pro 12.9"** (2048x2732): 3-10 screenshots

#### App Icon

Le fichier `ios/Runner/Assets.xcassets/AppIcon.appiconset` doit contenir:
- 1024x1024 (App Store)
- Toutes les tailles requises (généré automatiquement)

### Étape 5: Build IPA

```bash
# Build pour archivage
flutter build ios --release

# Ouvrir Xcode pour créer l'archive
open ios/Runner.xcworkspace

# Dans Xcode:
# 1. Product > Archive
# 2. Window > Organizer
# 3. Distribute App > App Store Connect
```

---

## 🏗️ BUILD PRODUCTION

### Checklist Pre-build

- [ ] Version et build number mis à jour
- [ ] `isTestMode = false` dans app_constants.dart
- [ ] Clés API production configurées
- [ ] Firebase production configuré
- [ ] Tests passent tous
- [ ] Code analysé sans warnings

### Android

```bash
# Clean build
flutter clean
flutter pub get

# Build App Bundle
flutter build appbundle --release \
  --obfuscate \
  --split-debug-info=build/app/outputs/symbols

# Le fichier sera:
# build/app/outputs/bundle/release/app-release.aab
# Taille: ~15-30 MB
```

### iOS

```bash
# Clean build
flutter clean
flutter pub get

# Build iOS
flutter build ios --release \
  --obfuscate \
  --split-debug-info=build/ios/outputs/symbols

# Puis dans Xcode: Product > Archive
```

---

## 🧪 TESTS PRE-RELEASE

### Tests Fonctionnels

- [ ] Installation/Désinstallation
- [ ] Login/Logout
- [ ] Recherche (fulltext + vector)
- [ ] Visualisation PDF
- [ ] Chat IA
- [ ] Paiement (en mode test d'abord!)
- [ ] Outils étudiants
- [ ] Parrainage
- [ ] Notifications push
- [ ] Mode offline

### Tests Techniques

- [ ] Performance (temps de chargement < 3s)
- [ ] Mémoire (pas de fuites)
- [ ] Batterie (pas de drain excessif)
- [ ] Réseau (gestion erreurs)
- [ ] Permissions (demandées correctement)

### Beta Testing

#### Android - Internal Testing

1. Play Console > Testing > Internal testing
2. Upload l'AAB
3. Ajouter des testeurs (emails)
4. Partager le lien

#### iOS - TestFlight

1. App Store Connect > TestFlight
2. Upload l'IPA depuis Xcode Organizer
3. Ajouter des testeurs
4. Inviter par email

---

## 🏪 PLAY STORE

### Étape 1: Créer l'Application

1. Allez sur [Play Console](https://play.google.com/console/)
2. Créer une application
3. Remplir les informations:
   - **Nom**: DOSSY Chat IA
   - **Langue**: Français (France)
   - **Catégorie**: Éducation
   - **Tags**: Juridique, Droit, IA, Afrique

### Étape 2: Fiche Store

#### Description courte (80 caractères)
```
Assistant juridique IA pour l'Afrique francophone - 14 pays
```

#### Description complète (4000 caractères)
```
🎓 DOSSY Chat IA - Votre Assistant Juridique Intelligent

Propulsé par l'intelligence artificielle GPT-4, DOSSY Chat IA révolutionne 
l'accès au droit en Afrique francophone.

🌍 14 PAYS COUVERTS
Bénin, Burkina Faso, Côte d'Ivoire, Guinée-Bissau, Mali, Niger, Sénégal, 
Togo, Cameroun, RD Congo, Gabon, Madagascar, Maroc, Tunisie.

🔍 RECHERCHE INTELLIGENTE
• Recherche plein texte et vectorielle
• Plus de 100,000 documents juridiques
• Jurisprudence, législation, doctrine
• Résultats en temps réel

💬 CHAT IA JURIDIQUE
• Analyse de documents
• Conseils personnalisés
• Citations de sources
• Support bilingue (FR/EN)

📚 BIBLIOTHÈQUE COMPLÈTE
• Codes et lois
• Arrêts et jugements
• Doctrine et commentaires
• Mise à jour quotidienne

🎓 OUTILS ÉTUDIANTS
• Générateur de Fiche d'Arrêt
• Créateur de QCM
• Révision Active
• Transcription Audio

💼 SOLUTIONS PROFESSIONNELLES
• Anonymisation de documents
• Veille juridique automatique
• Génération de contrats
• Tableau de bord analytics

💳 4 PLANS D'ABONNEMENT
• Gratuit: Accès limité
• Étudiant: 2,500 FCFA/mois
• Professionnel: 10,000 FCFA/mois
• Cabinet: 50,000 FCFA/mois

💰 PAIEMENT FACILE
Mobile Money (MTN, Orange, Moov) et cartes bancaires acceptées.

🎁 PARRAINAGE
Gagnez 500 FCFA par filleul !

📱 CARACTÉRISTIQUES
• Mode hors ligne
• Partage de documents
• Favoris
• Notifications push
• Interface responsive

🔒 SÉCURITÉ
Vos données sont chiffrées et protégées.

📞 SUPPORT
Email: contact@dossypro.com
WhatsApp: +229 XX XX XX XX

Téléchargez DOSSY Chat IA maintenant et transformez votre pratique juridique !
```

### Étape 3: Assets Graphiques

Uploadez:
- ✅ Icône haute résolution (512x512)
- ✅ Feature Graphic (1024x500)
- ✅ Screenshots téléphone (min 2)
- ✅ Screenshots tablette 7" (min 2)

### Étape 4: Contenu de l'Application

- **Catégorie**: Éducation > Juridique
- **Adresse email**: contact@dossypro.com
- **Site web**: https://dossypro.com
- **Politique de confidentialité**: https://dossypro.com/privacy

### Étape 5: Classification du Contenu

- **Public cible**: 18+
- **Contenu**: Éducatif
- **Publicité**: Non

### Étape 6: Upload AAB

1. Production > Créer une version
2. Upload `app-release.aab`
3. Remplir les notes de version:

```
Version 1.0.0 - Lancement initial

Fonctionnalités:
• Recherche juridique intelligente (IA)
• Chat avec assistant juridique GPT-4
• 100,000+ documents de 14 pays
• Outils étudiants (Fiche d'Arrêt, QCM)
• Solutions professionnelles
• Paiement Mobile Money et carte
• Mode hors ligne
```

4. Examiner et déployer

---

## 🍏 APP STORE

### Étape 1: App Store Connect

1. Allez sur [App Store Connect](https://appstoreconnect.apple.com/)
2. My Apps > + > New App
3. Remplir:
   - **Platform**: iOS
   - **Name**: DOSSY Chat IA
   - **Language**: French (France)
   - **Bundle ID**: com.dossypro.dossyChatIa
   - **SKU**: dossychatia001

### Étape 2: Informations App

#### Nom (30 caractères)
```
DOSSY Chat IA
```

#### Sous-titre (30 caractères)
```
Assistant Juridique IA Afrique
```

#### Description (4000 caractères)
```
[Utiliser la même que Play Store, traduite si besoin]
```

#### Mots-clés (100 caractères)
```
juridique,droit,loi,avocat,étudiant,afrique,IA,assistant,recherche,legal
```

#### URL support
```
https://dossypro.com/support
```

#### URL marketing
```
https://dossypro.com
```

### Étape 3: Prix et Disponibilité

- **Prix**: Gratuit (avec achats intégrés)
- **Disponibilité**: Tous les territoires
- **Achats intégrés**:
  - Plan Étudiant: 2,500 FCFA/mois
  - Plan Professionnel: 10,000 FCFA/mois
  - Plan Cabinet: 50,000 FCFA/mois

### Étape 4: Build

1. Upload depuis Xcode Organizer
2. Attendre la validation (peut prendre 30min - 24h)
3. Sélectionner le build dans App Store Connect

### Étape 5: Soumettre pour Examen

1. Remplir les questions d'examen
2. Ajouter notes pour l'examinateur:

```
Compte de test:
Email: test@dossypro.com
Password: Test123456!

Instructions:
1. Utiliser le compte de test pour se connecter
2. Tester la recherche avec "contrat de travail"
3. Le paiement est en mode test (ne sera pas débité)

Fonctionnalités à tester:
- Recherche juridique
- Chat IA
- Visualisation PDF
- Paiement (mode test)
```

3. Soumettre pour examen

---

## 📊 POST-DÉPLOIEMENT

### Monitoring

#### Analytics
```dart
// Déjà configuré dans analytics_helper.dart
await AnalyticsHelper.logAppOpen();
```

#### Crashlytics (Optionnel)
```bash
# Ajouter Firebase Crashlytics
flutter pub add firebase_crashlytics

# Configuration dans main.dart
FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterError;
```

### KPIs à Suivre

- **Téléchargements**: Daily/Weekly/Monthly
- **Utilisateurs actifs**: DAU/MAU
- **Conversions**: Free → Paid
- **Rétention**: Day 1, Day 7, Day 30
- **Revenus**: MRR (Monthly Recurring Revenue)

### Mises à Jour

```bash
# Incrémenter la version
# pubspec.yaml
version: 1.0.1+2  # version+buildNumber

# Rebuild et redéployer
flutter build appbundle --release  # Android
flutter build ios --release         # iOS
```

---

## ✅ CHECKLIST FINALE

### Pré-lancement
- [ ] Code finalisé et testé
- [ ] Firebase configuré
- [ ] Clés API production
- [ ] Assets créés
- [ ] Descriptions rédigées
- [ ] Comptes développeur actifs

### Android
- [ ] Keystore créé et sauvegardé
- [ ] AAB buildé
- [ ] Fiche Play Store complète
- [ ] Screenshots uploadés
- [ ] Beta testée
- [ ] Soumis pour examen

### iOS
- [ ] Certificats et profiles créés
- [ ] IPA buildé et archivé
- [ ] Fiche App Store complète
- [ ] Screenshots uploadés
- [ ] TestFlight testée
- [ ] Soumis pour examen

### Post-lancement
- [ ] Analytics configuré
- [ ] Monitoring actif
- [ ] Support client prêt
- [ ] Communication lancée
- [ ] Feedback utilisateurs suivi

---

## 📞 SUPPORT

**Questions ?**
- Email: contact@dossypro.com
- Website: https://dossypro.com
- Discord: https://discord.gg/dossypro

---

**🎉 Bonne chance pour le lancement de DOSSY Chat IA !**

*Guide mis à jour: Décembre 2025*
