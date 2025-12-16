# DOSSY CHAT IA

**Analyse et Assistant Juridique, Fiscal & Social**

Application Flutter mobile pour l'Afrique francophone offrant un assistant juridique intelligent avec IA, bibliothèque juridique complète, outils pour étudiants et professionnels.

---

## 📱 Vue d'ensemble

DOSSY CHAT IA est une application mobile complète qui combine :
- 🤖 **Chat IA avec RAG** : Assistant intelligent utilisant OpenAI GPT et Pinecone
- 📚 **Bibliothèque Juridique** : Accès aux textes légaux par juridiction (OHADA, codes pays)
- 🎓 **Outils Étudiants** : Générateurs de fiches d'arrêt, révision, QCM
- 💼 **Solutions Professionnelles** : Anonymisation, modèles de contrats, veille juridique
- 🌍 **Multi-juridictions** : Support de 14 pays d'Afrique francophone

---

## 🎨 Design & UI/UX

### Couleur Dominante
- **Vert Principal** : `#00C853`
- **Vert Foncé** : `#00A143`
- **Vert Clair** : `#5EFC82`

### Thème
- Material Design 3
- Mode Clair & Sombre
- Interface en Français & Anglais
- Design optimisé pour l'Afrique francophone

---

## 🚀 Fonctionnalités

### Pour Tous
- ✅ Inscription/Connexion sécurisée
- ✅ Chat IA avec suggestions de prompts
- ✅ Recherche dans la bibliothèque juridique
- ✅ Sélection de juridiction (Cameroun, Côte d'Ivoire, Sénégal, etc.)

### Pour Étudiants (Plan Étudiant)
- 📋 Générateur de fiches d'arrêt
- 📚 Générateur de fiches de révision
- ✍️ Plan de dissertation & problématiques
- ❓ QCM interactifs
- 🎯 Mode révision active avec questions guidées
- 🎤 Transcription audio des cours

### Pour Professionnels (Plan Professionnel)
- 🔒 **Anonymisation automatique** (remplace noms clients par [X], [Y])
- 📄 Modèles de contrats éditables (Word)
- 🔔 Veille juridique avec alertes Email/WhatsApp
- 💰 Assistant fiscal & social
- 🧮 Simulateurs RH et paie

### Pour Entreprises/Cabinets (Plan Cabinet)
- 👥 Multi-comptes (jusqu'à 10 utilisateurs)
- 📊 Tableau de bord administrateur
- 📧 Système d'emailing (promotions, relance inactivité)
- 🎟️ Gestion de coupons
- 🤝 Programme de parrainage (validation après 10 inscriptions)
- 📈 KPI et analytics

---

## 📦 Structure du Projet

```
dossy_chat_ia/
├── lib/
│   ├── core/
│   │   ├── constants/
│   │   │   └── app_constants.dart          # Constantes globales
│   │   ├── theme/
│   │   │   ├── app_theme.dart              # Thème Material Design
│   │   │   └── app_colors.dart             # Palette de couleurs
│   │   ├── utils/                          # Utilitaires
│   │   └── extensions/                     # Extensions Dart
│   │
│   ├── data/
│   │   ├── models/
│   │   │   ├── user_model.dart             # Modèle utilisateur
│   │   │   └── message_model.dart          # Modèle message chat
│   │   ├── providers/
│   │   │   ├── auth_provider.dart          # Provider authentification
│   │   │   ├── chat_provider.dart          # Provider chat
│   │   │   ├── subscription_provider.dart  # Provider abonnements
│   │   │   ├── locale_provider.dart        # Provider langue
│   │   │   └── theme_provider.dart         # Provider thème
│   │   ├── services/
│   │   │   └── api_service.dart            # Service API REST
│   │   └── repositories/                   # Repositories
│   │
│   ├── presentation/
│   │   ├── screens/
│   │   │   ├── splash/
│   │   │   │   └── splash_screen.dart      # Écran splash
│   │   │   ├── onboarding/
│   │   │   │   └── onboarding_screen.dart  # Onboarding
│   │   │   ├── auth/
│   │   │   │   ├── login_screen.dart       # Connexion
│   │   │   │   └── register_screen.dart    # Inscription
│   │   │   ├── home/
│   │   │   │   └── home_screen.dart        # Écran principal
│   │   │   ├── chat/                       # Écrans chat
│   │   │   ├── library/                    # Bibliothèque juridique
│   │   │   ├── tools/                      # Outils étudiants
│   │   │   ├── subscription/               # Abonnements
│   │   │   └── profile/                    # Profil utilisateur
│   │   ├── widgets/                        # Widgets réutilisables
│   │   └── shared/                         # Composants partagés
│   │
│   └── main.dart                           # Point d'entrée
│
├── android/                                # Configuration Android
├── ios/                                    # Configuration iOS
├── assets/                                 # Assets (images, icons)
├── test/                                   # Tests unitaires
├── pubspec.yaml                            # Dépendances Flutter
└── README.md                               # Ce fichier
```

---

## 🛠️ Technologies

### Frontend (Flutter)
- **Flutter SDK** : 3.2+
- **Dart** : 3.2+
- **State Management** : Provider
- **UI Components** : Material Design 3
- **Responsive** : flutter_screenutil

### Backend API
- **URL** : `https://dossy.alwaysdata.net/api/mobile`
- **Authentification** : Laravel Sanctum (Bearer Token)
- **Format** : JSON REST API

### Intégrations
- **OpenAI GPT** : Chat intelligent
- **Pinecone** : RAG vectoriel pour documents utilisateurs
- **Cloudflare R2** : Stockage de documents
- **Flutterwave** : Paiements (XAF, Afrique francophone)
- **Firebase** : Notifications push

---

## 📋 Prérequis

1. **Flutter SDK 3.2+**
   ```bash
   flutter --version
   ```

2. **Android Studio / VS Code**
   - Android SDK installé
   - Émulateur Android ou appareil physique

3. **Git**
   ```bash
   git --version
   ```

---

## 🔧 Installation

### 1. Cloner le projet
```bash
cd /home/user/webapp
cd dossy_chat_ia
```

### 2. Installer les dépendances
```bash
flutter pub get
```

### 3. Configuration (Optionnel)

Modifier `lib/core/constants/app_constants.dart` pour changer l'API :
```dart
static const String baseUrl = 'https://dossy.alwaysdata.net/api/mobile';
```

### 4. Générer les assets (si nécessaire)
```bash
flutter packages pub run build_runner build --delete-conflicting-outputs
```

---

## 🏃 Lancer l'application

### Sur Émulateur Android
```bash
flutter run
```

### Build APK (Debug)
```bash
flutter build apk --debug
```

### Build APK (Release)
```bash
flutter build apk --release
```

L'APK sera disponible dans : `build/app/outputs/flutter-apk/app-release.apk`

### Build App Bundle (Pour Google Play)
```bash
flutter build appbundle --release
```

---

## 📱 Tester sur Android

### Méthode 1 : Émulateur
1. Ouvrir Android Studio
2. Ouvrir AVD Manager
3. Créer/Lancer un émulateur
4. Exécuter : `flutter run`

### Méthode 2 : Appareil Physique
1. Activer le mode développeur sur votre téléphone
2. Activer le débogage USB
3. Connecter via USB
4. Exécuter : `flutter run`

### Méthode 3 : Installer l'APK
1. Build APK : `flutter build apk --release`
2. Copier l'APK sur téléphone
3. Installer manuellement

---

## 🌍 Pays Supportés

L'application supporte 14 pays d'Afrique francophone :
- 🇨🇲 Cameroun
- 🇨🇮 Côte d'Ivoire
- 🇸🇳 Sénégal
- 🇲🇱 Mali
- 🇹🇬 Togo
- 🇨🇩 République Démocratique du Congo
- 🇨🇬 République du Congo
- 🇧🇯 Bénin
- 🇧🇫 Burkina Faso
- 🇳🇪 Niger
- 🇹🇩 Tchad
- 🇬🇦 Gabon
- 🇬🇳 Guinée
- 🇨🇫 République Centrafricaine

---

## 💳 Plans d'Abonnement

| Plan | Prix (XAF/mois) | Recherches | Analyses IA | Téléchargements | Fonctionnalités |
|------|----------------|------------|-------------|-----------------|-----------------|
| **Gratuit** | 0 | 5 | 2 | 0 | Accès de base |
| **Étudiant** | 5,000 | 50 | 20 | 10 | Outils étudiants + Transcription audio |
| **Professionnel** | 15,000 | 200 | 100 | 50 | Anonymisation + Modèles + Veille |
| **Cabinet/Entreprise** | 50,000 | ∞ | ∞ | ∞ | Multi-comptes + Support 24/7 |

---

## 🔑 Endpoints API Principaux

### Authentification
- `POST /register` : Inscription
- `POST /login` : Connexion
- `POST /logout` : Déconnexion
- `GET /profile` : Profil utilisateur

### Chat
- `POST /chat` : Envoyer un message
- `GET /chat/history` : Historique des conversations

### Documents
- `POST /documents/upload` : Upload document
- `GET /documents` : Liste des documents
- `DELETE /documents/{id}` : Supprimer document

### Abonnements
- `GET /subscriptions/plans` : Plans disponibles
- `POST /subscriptions/initiate-payment` : Initier paiement Flutterwave

### Parrainage
- `GET /referral/info` : Info parrainage

---

## 🎯 Prochaines Étapes

### Phase 3.1 : Écrans Core (En cours)
- ✅ Splash Screen
- ✅ Onboarding
- ✅ Login Screen
- ✅ Home Screen avec tabs
- ⏳ Register Screen
- ⏳ Chat Screen complet
- ⏳ Bibliothèque Juridique

### Phase 3.2 : Outils Étudiants
- ⏳ Générateur Fiche d'Arrêt
- ⏳ Générateur Fiche de Révision
- ⏳ Générateur QCM
- ⏳ Mode Révision Active

### Phase 3.3 : Fonctionnalités Pro
- ⏳ Anonymisation automatique
- ⏳ Modèles de documents (Word)
- ⏳ Transcription audio
- ⏳ Veille juridique et alertes

### Phase 3.4 : Abonnements & Paiements
- ⏳ Intégration Flutterwave
- ⏳ Gestion des coupons
- ⏳ Programme de parrainage

### Phase 3.5 : Admin & CRM
- ⏳ Dashboard admin
- ⏳ Gestion des templates email
- ⏳ KPI et analytics

---

## 📞 Support

- **Email** : support@dossypro.com
- **Site Web** : https://dossypro.com
- **API Backend** : https://dossy.alwaysdata.net

---

## 📄 Licence

© 2024 DOSSY CHAT IA. Tous droits réservés.

---

## 🙏 Remerciements

Développé avec ❤️ pour l'Afrique francophone.

---

**Version** : 1.0.0  
**Dernière mise à jour** : 2024-12-16
