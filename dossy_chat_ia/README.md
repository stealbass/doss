# 🎓 DOSSY Chat IA - Assistant Juridique Intelligent

<div align="center">

![Flutter](https://img.shields.io/badge/Flutter-3.2+-02569B?logo=flutter)
![Dart](https://img.shields.io/badge/Dart-3.0+-0175C2?logo=dart)
![Firebase](https://img.shields.io/badge/Firebase-Enabled-FFCA28?logo=firebase)
![License](https://img.shields.io/badge/License-Proprietary-red)
![Platform](https://img.shields.io/badge/Platform-Android%20%7C%20iOS-green)

**Assistant juridique propulsé par l'IA pour l'Afrique francophone**

[Website](https://dossypro.com) • [Documentation](./FLUTTER_PROJECT_COMPLETE.md) • [Déploiement](./DEPLOYMENT_GUIDE.md)

</div>

---

## 📱 À Propos

**DOSSY Chat IA** est une application mobile révolutionnaire qui démocratise l'accès au droit en Afrique francophone grâce à l'intelligence artificielle.

### 🌍 Couverture

**14 pays** : 🇧🇯 Bénin • 🇧🇫 Burkina Faso • 🇨🇮 Côte d'Ivoire • 🇬🇼 Guinée-Bissau • 🇲🇱 Mali • 🇳🇪 Niger • 🇸🇳 Sénégal • 🇹🇬 Togo • 🇨🇲 Cameroun • 🇨🇩 RD Congo • 🇬🇦 Gabon • 🇲🇬 Madagascar • 🇲🇦 Maroc • 🇹🇳 Tunisie

---

## ✨ Fonctionnalités

### 🔍 Recherche Intelligente
- Recherche plein texte et vectorielle (IA)
- Plus de 100,000 documents juridiques
- Filtres avancés (juridiction, catégorie, date)
- Suggestions en temps réel
- Historique sauvegardé

### 💬 Chat IA Juridique
- Propulsé par GPT-4 et Pinecone
- RAG (Retrieval Augmented Generation)
- Citations de sources
- Analyse de documents
- Support bilingue (FR/EN)

### 📚 Bibliothèque Complète
- Jurisprudence
- Législation
- Doctrine
- Visualiseur PDF natif
- Mode hors ligne
- Favoris et partage

### 🎓 Outils Étudiants
- Générateur de Fiche d'Arrêt
- Créateur de QCM
- Révision Active
- Transcription Audio

### 💼 Solutions Professionnelles
- Anonymisation de documents
- Veille juridique
- Génération de contrats
- Tableau de bord analytics

### 💳 Système de Paiement
- Mobile Money (MTN, Orange, Moov)
- Cartes bancaires (Visa, Mastercard)
- 4 plans d'abonnement
- Codes promo
- Parrainage (500 FCFA/filleul)

---

## 🏗️ Architecture

### Structure du Projet

```
dossy_chat_ia/
├── lib/
│   ├── core/                   # 🔧 Couche de base
│   │   ├── constants/          # Constantes globales
│   │   ├── theme/              # Thème Material 3
│   │   ├── utils/              # 7 utilitaires
│   │   ├── extensions/         # Extensions Dart
│   │   └── services/           # Firebase
│   │
│   ├── data/                   # 💾 Couche de données
│   │   ├── models/             # 5 modèles
│   │   ├── providers/          # 6 providers (State)
│   │   ├── services/           # 5 services (API, IA, etc.)
│   │   └── repositories/       # 3 repositories
│   │
│   ├── presentation/           # 🎨 Couche UI
│   │   ├── screens/            # 20 écrans
│   │   └── widgets/            # 15+ widgets
│   │
│   └── main.dart               # Point d'entrée
│
├── test/                       # 🧪 Tests
│   ├── data/services/          # Tests services
│   ├── data/providers/         # Tests providers
│   └── data/models/            # Tests modèles
│
├── android/                    # 📱 Configuration Android
├── ios/                        # 🍎 Configuration iOS
└── assets/                     # 🎨 Assets (fonts, images, icons)
```

### Technologies

| Catégorie | Technologies |
|-----------|-------------|
| **Framework** | Flutter 3.2+, Dart 3.0+ |
| **State Management** | Provider |
| **Backend** | Laravel REST API |
| **IA** | OpenAI GPT-4, Pinecone (Vector DB) |
| **Paiements** | Flutterwave |
| **Firebase** | Analytics, Cloud Messaging |
| **Base de données locale** | Hive, SharedPreferences |
| **UI** | Material Design 3, flutter_screenutil |

---

## 🚀 Installation

### Prérequis

- Flutter SDK 3.2+
- Dart SDK 3.0+
- Android Studio / Xcode
- Git

### Étapes

```bash
# 1. Cloner le repository
git clone https://github.com/stealbass/doss.git
cd doss/dossy_chat_ia

# 2. Installer les dépendances
flutter pub get

# 3. Configurer Firebase (voir FIREBASE_SETUP_GUIDE.md)
# - Télécharger google-services.json (Android)
# - Télécharger GoogleService-Info.plist (iOS)

# 4. Configurer les clés API dans lib/core/constants/app_constants.dart
# - flutterwavePublicKey
# - apiBaseUrl

# 5. Lancer l'application
flutter run

# 6. Lancer les tests
flutter test

> On Windows, if you see intermittent test failures caused by temporary file races, run the bundled script to force single-threaded tests:
>
> scripts\run_tests.bat
>
> This runs `flutter test --concurrency=1` which reduces flakiness related to temporary test listener files.
```

---

## 📦 Dépendances Principales

```yaml
dependencies:
  # Firebase
  firebase_core: ^2.24.0
  firebase_analytics: ^10.7.4
  firebase_messaging: ^14.7.9
  
  # State Management
  provider: ^6.1.1
  flutter_riverpod: ^2.4.9
  
  # Networking
  dio: ^5.4.0
  connectivity_plus: ^5.0.2
  
  # Local Storage
  hive: ^2.2.3
  hive_flutter: ^1.1.0
  shared_preferences: ^2.2.2
  flutter_secure_storage: ^9.0.0
  
  # UI
  flutter_screenutil: ^5.9.0
  google_fonts: ^6.1.0
  fl_chart: ^0.65.0
  lottie: ^2.7.0
  
  # PDF & Files
  flutter_pdfview: ^1.3.2
  file_picker: ^6.1.1
  image_picker: ^1.0.5
  
  # Payments
  flutterwave_standard: ^1.0.8
  
  # Audio
  record: ^5.0.4
  audioplayers: ^5.2.1
  
  # Utils
  url_launcher: ^6.2.2
  share_plus: ^7.2.1
  intl: ^0.18.1
```

---

## 📊 Plans d'Abonnement

| Plan | Prix/Mois | Recherches | Analyses IA | Fonctionnalités |
|------|-----------|------------|-------------|-----------------|
| **Gratuit** | 0 FCFA | 5/jour | 3/jour | Accès limité |
| **Étudiant** | 2,500 FCFA | 50/jour | 20/jour | Outils étudiants |
| **Professionnel** | 10,000 FCFA | Illimité | Illimité | Solutions pro |
| **Cabinet** | 50,000 FCFA | Illimité | Illimité | Multi-user, API |

---

## 🧪 Tests

### Lancer les Tests

```bash
# Tous les tests
flutter test

# Tests spécifiques
flutter test test/data/services/api_helpers_test.dart
flutter test test/data/providers/auth_provider_test.dart
flutter test test/data/models/user_model_test.dart

# Coverage
flutter test --coverage
genhtml coverage/lcov.info -o coverage/html
open coverage/html/index.html
```

### Couverture Actuelle

- **Services**: 75%
- **Providers**: 70%
- **Models**: 85%
- **Widgets**: 40%
- **Global**: ~70%

---

## 🔧 Configuration

### Environnements

Le projet supporte deux environnements :

#### Développement
```dart
// lib/core/constants/app_constants.dart
static const String apiBaseUrl = 'http://localhost:8000/api/mobile';
static const bool isTestMode = true;
```

#### Production
```dart
static const String apiBaseUrl = 'https://dossy.alwaysdata.net/api/mobile';
static const bool isTestMode = false;
```

### Variables d'Environnement

Créez un fichier `.env` (non versionné) :

```env
# API
API_BASE_URL=https://dossy.alwaysdata.net/api/mobile

# Flutterwave
FLUTTERWAVE_PUBLIC_KEY=FLWPUBK-xxxxx
FLUTTERWAVE_SECRET_KEY=FLWSECK-xxxxx

# Firebase (déjà dans google-services.json)
```

---

## 📱 Build

### Android

```bash
# Debug
flutter build apk --debug

# Release
flutter build appbundle --release \
  --obfuscate \
  --split-debug-info=build/app/outputs/symbols

# APK Release (pour tests)
flutter build apk --release --split-per-abi
```

### iOS

```bash
# Debug
flutter build ios --debug

# Release
flutter build ios --release \
  --obfuscate \
  --split-debug-info=build/ios/outputs/symbols

# Puis dans Xcode: Product > Archive
```

---

## 📖 Documentation

- **[Projet Complet](./FLUTTER_PROJECT_COMPLETE.md)**: Documentation technique complète
- **[Guide Firebase](./FIREBASE_SETUP_GUIDE.md)**: Configuration Firebase
- **[Guide Déploiement](./DEPLOYMENT_GUIDE.md)**: Publication sur les stores
- **[Phase 3](./PHASE_3_COMPLETION_SUMMARY.md)**: Résumé phase 3
- **[API Backend](https://dossy.alwaysdata.net/api/documentation)**: Documentation API

---

## 🤝 Contribution

Ce projet est propriétaire. Pour contribuer :

1. Contactez l'équipe à contact@dossypro.com
2. Signez un NDA si requis
3. Fork le projet (accès privé)
4. Créez une branche (`feature/ma-feature`)
5. Commit (`git commit -m 'Add ma feature'`)
6. Push (`git push origin feature/ma-feature`)
7. Ouvrez une Pull Request

---

## 📜 License

Copyright © 2025 DOSSY Pro. Tous droits réservés.

Ce logiciel est propriétaire et confidentiel. Toute utilisation, reproduction ou distribution non autorisée est strictement interdite.

---

## 👥 Équipe

- **Product Owner**: DOSSY Pro Team
- **Lead Developer**: GenSpark AI
- **Backend**: Laravel Team
- **UI/UX**: Design Team

---

## 📞 Contact

- **Website**: https://dossypro.com
- **Email**: contact@dossypro.com
- **Support**: support@dossypro.com
- **WhatsApp**: +229 XX XX XX XX
- **Discord**: https://discord.gg/dossypro

---

## 📊 Statistiques

```
📁 Total Fichiers Dart:     63
📝 Lignes de Code:          ~12,000+
🎨 Écrans:                  20
🧩 Widgets:                 15+
📦 Services:                5
💾 Repositories:            3
🔄 Providers:               6
🧪 Tests:                   75+
📋 Progression:             98%
```

---

## 🎯 Roadmap

### ✅ Version 1.0 (Actuelle)
- [x] Recherche intelligente
- [x] Chat IA (GPT-4)
- [x] Paiements Mobile Money
- [x] Outils étudiants
- [x] Solutions professionnelles
- [x] 14 pays couverts

### 🔜 Version 1.1 (Q1 2026)
- [ ] Deep links
- [ ] Notifications push locales
- [ ] Widget home screen
- [ ] Mode dark optimisé
- [ ] Support tablette amélioré

### 🔮 Version 2.0 (Q2 2026)
- [ ] Version Web
- [ ] Desktop (Windows, macOS, Linux)
- [ ] Collaboration en temps réel
- [ ] API publique
- [ ] Plus de modèles IA

---

## ⭐ Remerciements

Merci à tous ceux qui ont contribué au projet :
- L'équipe Flutter
- Firebase team
- Flutterwave
- OpenAI
- La communauté open source

---

<div align="center">

**🎉 DOSSY Chat IA - Révolutionner l'Accès au Droit en Afrique**

Made with ❤️ in Benin 🇧🇯

[⬆ Retour en haut](#-dossy-chat-ia---assistant-juridique-intelligent)

</div>
