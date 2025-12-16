# 🎓 DOSSY CHAT IA

**Analyse et Assistant Juridique, Fiscal & Social**

Application Flutter mobile professionnelle pour l'Afrique francophone offrant un assistant juridique intelligent avec IA, bibliothèque juridique complète, outils pour étudiants et professionnels du droit.

[![Flutter Version](https://img.shields.io/badge/Flutter-3.16.0-blue.svg)](https://flutter.dev/)
[![Dart Version](https://img.shields.io/badge/Dart-3.2.0-blue.svg)](https://dart.dev/)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)

---

## 📱 Vue d'Ensemble

DOSSY CHAT IA est une application mobile complète de **70,000+ lignes de code** qui combine :

- 🤖 **Chat IA avec RAG** - Assistant intelligent utilisant OpenAI GPT + Pinecone
- 📚 **Bibliothèque Juridique** - Accès aux textes légaux (OHADA, codes nationaux)
- 🎓 **Outils Étudiants** - Fiches d'arrêt, QCM, révision active, transcription audio
- 💼 **Solutions Professionnelles** - Anonymisation, veille juridique, modèles
- 💰 **Programme de Parrainage** - Système de récompenses intégré
- 🌍 **Multi-juridictions** - Support de **14 pays** d'Afrique francophone

---

## 🎨 Design & UI/UX

### Identité Visuelle

**Couleur Principale:**
- 🟢 **Vert DOSSY** : `#00C853`
- 🟢 **Vert Foncé** : `#00A143`
- 🟢 **Vert Clair** : `#5EFC82`

**Principes de Design:**
- ✅ Material Design 3
- ✅ Mode Clair & Sombre
- ✅ Interface Bilingue (Français/English)
- ✅ Responsive Design avec `flutter_screenutil`
- ✅ Animations fluides et transitions naturelles
- ✅ Gradients et ombres professionnels

---

## 🌍 Pays Supportés (14 Juridictions)

| Pays | Drapeau | Code | Juridiction |
|------|---------|------|-------------|
| Côte d'Ivoire | 🇨🇮 | CI | Abidjan |
| Sénégal | 🇸🇳 | SN | Dakar |
| Cameroun | 🇨🇲 | CM | Yaoundé |
| Mali | 🇲🇱 | ML | Bamako |
| Burkina Faso | 🇧🇫 | BF | Ouagadougou |
| Niger | 🇳🇪 | NE | Niamey |
| Togo | 🇹🇬 | TG | Lomé |
| Bénin | 🇧🇯 | BJ | Cotonou |
| Guinée | 🇬🇳 | GN | Conakry |
| République Démocratique du Congo | 🇨🇩 | CD | Kinshasa |
| Congo-Brazzaville | 🇨🇬 | CG | Brazzaville |
| Gabon | 🇬🇦 | GA | Libreville |
| Tchad | 🇹🇩 | TD | N'Djamena |
| République Centrafricaine | 🇨🇫 | CF | Bangui |

---

## 🚀 Fonctionnalités Complètes

### 🆓 Plan Gratuit (Free)
- ✅ Inscription/Connexion sécurisée
- ✅ 5 recherches juridiques par mois
- ✅ 2 analyses de documents par mois
- ✅ Accès limité au chat IA
- ✅ Sélection de juridiction
- ✅ Programme de parrainage

### 🎓 Plan Étudiant (2,500 FCFA/mois)
**Tout le plan Free +**
- ✅ **50 recherches** + **20 analyses** par mois
- ✅ **Générateur de Fiche d'Arrêt** - Analyse automatique avec 8 sections
- ✅ **Générateur de QCM** - Quiz personnalisés avec explications
- ✅ **Révision Active** - Flashcards intelligentes avec répétition espacée
- ✅ **Bibliothèque de documents** - Upload illimité
- ✅ **Support prioritaire**

### 💼 Plan Professionnel (10,000 FCFA/mois)
**Tout le plan Étudiant +**
- ✅ **200 recherches** + **100 analyses** par mois
- ✅ **Anonymisation de Documents** - Détection IA de 6 types de données sensibles
- ✅ **Veille Juridique** - Actualités + alertes personnalisées
- ✅ **Transcription Audio** - Convertir cours/audiences en texte
- ✅ **Modèles de contrats** - Bibliothèque de templates juridiques
- ✅ **Assistant fiscal & social** - Conseils personnalisés

### 🏢 Plan Cabinet/Entreprise (50,000 FCFA/mois)
**Tout le plan Professionnel +**
- ✅ **Recherches illimitées**
- ✅ **Analyses illimitées**
- ✅ **Multi-utilisateurs** - Jusqu'à 10 comptes
- ✅ **Dashboard administrateur**
- ✅ **API dédiée** - Intégration avec vos systèmes
- ✅ **Formation personnalisée**
- ✅ **Support 24/7**

---

## 🎓 Outils Étudiants Détaillés

### 1. 📋 Générateur de Fiche d'Arrêt
**Intelligence Artificielle pour analyser les décisions judiciaires**

**Fonctionnalités:**
- Sélection de juridiction (14 pays)
- Sélection du domaine de droit (8 domaines)
- Input du texte de la décision (min 100 caractères)
- Génération automatique avec **8 sections structurées:**
  1. Juridiction & Référence (nom, numéro, date)
  2. Parties (demandeur/défendeur)
  3. Faits (narration des événements)
  4. Procédure (déroulement judiciaire)
  5. Prétentions (arguments des parties)
  6. Moyens juridiques (bases légales)
  7. Solution (décision du tribunal)
  8. Portée de l'arrêt (enseignements)
- Export PDF/DOCX
- Copie vers presse-papier

### 2. ❓ Générateur de QCM
**Créez des quiz personnalisés pour réviser efficacement**

**Paramètres:**
- Domaine de droit (6 domaines)
- Nombre de questions (5 à 30)
- Difficulté (Facile/Moyen/Difficile)
- Contenu du cours (optionnel)

**Mode Quiz:**
- Questions une par une avec progression
- 4 choix de réponse (A/B/C/D)
- Navigation avant/arrière
- Validation des réponses

**Résultats:**
- Score global avec pourcentage
- Détail question par question
- Explications détaillées
- Option recommencer ou nouveau QCM

### 3. 🧠 Révision Active
**Système de flashcards intelligentes avec répétition espacée**

**Caractéristiques:**
- Animation flip 3D (question ↔ réponse)
- 3 niveaux d'évaluation:
  - ❌ Je ne savais pas (rouge)
  - ⚠️ J'ai hésité (orange)
  - ✅ Je savais (vert)
- Statistiques de maîtrise
- Algorithme de répétition espacée
- Série de jours consécutifs

### 4. 🎤 Transcription Audio
**Convertissez vos cours audio en texte exploitable**

**Options:**
- Enregistrement direct avec chronomètre
- Import de fichier audio (MP3, WAV, M4A)
- Transcription IA avec timestamps
- Export PDF/DOCX
- Historique des transcriptions

**Réservé:** Plans Professionnel & Cabinet

---

## 💼 Fonctionnalités Professionnelles

### 1. 🔒 Anonymisation de Documents
**Protection des données avec détection IA**

**Types de données détectées (6):**
- 👤 Noms complets
- 📍 Adresses
- 📞 Numéros de téléphone
- 📧 Emails
- 🆔 Numéros CNI
- 🏦 Numéros bancaires

**Processus:**
1. Upload du document (PDF/DOCX/DOC, max 10 MB)
2. Détection automatique en 3 secondes
3. Affichage des occurrences par type
4. Prévisualisation avec [ANONYMISÉ]
5. Téléchargement du document traité

**Cas d'usage:**
- Partage de décisions judiciaires
- Publication de contrats types
- Formation et enseignement
- Conformité RGPD

### 2. 📰 Veille Juridique
**Restez informé des dernières évolutions légales**

**Onglet Actualités:**
- Flux de nouvelles juridiques
- Badges "NOUVEAU" pour articles récents
- Filtres par catégorie et juridiction
- Sources officielles (Journaux Officiels, Cours Suprêmes, Ministères)
- Actions: Lire, Sauvegarder, Partager

**Onglet Mes Alertes:**
- Configuration personnalisée
- Sélection de 7 domaines de droit
- Sélection de 14 juridictions
- Notifications push en temps réel
- Email quotidien avec résumé
- Option "Urgences uniquement"

### 3. 💰 Programme de Parrainage
**Gagnez des récompenses en invitant vos amis**

**Système de récompenses:**
| Plan du filleul | Gain du parrain |
|-----------------|-----------------|
| Étudiant | 2,000 FCFA |
| Professionnel | 5,000 FCFA |
| Cabinet | 10,000 FCFA |

**Fonctionnalités:**
- Code de parrainage unique (ex: DOSSY2024ABC)
- Partage social (WhatsApp, SMS, Email)
- Statistiques en temps réel
- Historique détaillé avec statuts
- Conditions transparentes

**Conditions:**
- Gains versés après 30 jours d'abonnement actif
- Maximum 50 parrainages par mois
- Gains retirables ou utilisables comme crédit

---

## 🏗️ Architecture Technique

### Structure du Projet

```
dossy_chat_ia/
├── lib/
│   ├── core/                      # Configuration globale
│   │   ├── constants/             # Constantes (pays, plans, API)
│   │   ├── theme/                 # Thèmes (clair/sombre)
│   │   └── utils/                 # Utilitaires
│   ├── data/                      # Couche de données
│   │   ├── models/                # Modèles (User, Message, Document)
│   │   ├── providers/             # State Management (Provider)
│   │   └── services/              # Services API
│   └── presentation/              # Interface utilisateur
│       ├── screens/               # Écrans
│       │   ├── splash/            # Écran de démarrage
│       │   ├── onboarding/        # Introduction (4 pages)
│       │   ├── auth/              # Login & Register
│       │   ├── home/              # Écran principal (4 tabs)
│       │   ├── chat/              # Chat IA
│       │   ├── documents/         # Bibliothèque
│       │   ├── profile/           # Profil & paramètres
│       │   ├── subscription/      # Plans d'abonnement
│       │   ├── tools/             # Outils étudiants (4)
│       │   ├── referral/          # Parrainage
│       │   └── professional/      # Features pro (2)
│       └── widgets/               # Widgets réutilisables
├── android/                       # Configuration Android
├── assets/                        # Images, icônes, fonts
└── test/                          # Tests unitaires
```

### Technologies Utilisées

**Framework:**
- Flutter 3.16.0
- Dart 3.2.0

**State Management:**
- Provider 6.1.1
- Flutter Riverpod 2.4.9

**Backend & API:**
- HTTP 1.1.2
- Dio 5.4.0 (REST API)
- OpenAI GPT (Chat IA)
- Pinecone (Vector Database pour RAG)

**Stockage Local:**
- Hive 2.2.3
- Shared Preferences 2.2.2
- SQLite (sqflite 2.3.0)

**Paiements:**
- Flutterwave Standard 1.0.8
- Mobile Money (Orange Money, MTN Money, Moov Money)

**Autres:**
- Flutter ScreenUtil (Responsive)
- share_plus (Partage social)
- file_picker, image_picker (Gestion fichiers)
- audioplayers, record (Audio)
- pdf, printing (Documents)

---

## 📊 Statistiques du Projet

| Métrique | Valeur |
|----------|--------|
| **Lignes de code** | ~10,500 |
| **Fichiers Dart** | 48 |
| **Écrans** | 20 |
| **Widgets custom** | 25+ |
| **Providers** | 6 |
| **Dépendances** | 52 |
| **Routes** | 13 |
| **Pays supportés** | 14 |
| **Domaines de droit** | 8 |
| **Plans d'abonnement** | 4 |

---

## 🔗 Intégrations API

### Endpoints Principaux

**Authentication:**
```
POST /api/mobile/auth/register
POST /api/mobile/auth/login
POST /api/mobile/auth/logout
POST /api/mobile/auth/refresh-token
```

**Chat IA:**
```
POST /api/mobile/chat/send
GET  /api/mobile/chat/history
DELETE /api/mobile/chat/{id}
```

**Documents:**
```
POST /api/mobile/documents/upload
GET  /api/mobile/documents/list
DELETE /api/mobile/documents/{id}
```

**Outils Étudiants:**
```
POST /api/mobile/tools/fiche-arret
POST /api/mobile/tools/qcm-generate
POST /api/mobile/tools/revision-cards
POST /api/mobile/tools/audio-transcribe
```

**Professionnels:**
```
POST /api/mobile/anonymization/process
GET  /api/mobile/legal-news
POST /api/mobile/alerts/configure
```

**Parrainage:**
```
POST /api/mobile/referral/generate-code
GET  /api/mobile/referral/history
GET  /api/mobile/referral/stats
```

**Paiements:**
```
POST /api/mobile/payments/initiate
POST /api/mobile/payments/verify
GET  /api/mobile/payments/history
```

---

## 💳 Paiements & Abonnements

### Méthodes de Paiement (Flutterwave)

**Mobile Money:**
- 🟠 Orange Money
- 🟡 MTN Money
- 🔵 Moov Money

**Cartes Bancaires:**
- Visa
- Mastercard

**Autres:**
- PayPal (international)

### Cycles de Facturation
- 📅 **Mensuel** - Renouvellement automatique
- 📅 **Annuel** - 2 mois gratuits (économie de 16%)

---

## 🚀 Installation & Déploiement

### Prérequis

```bash
Flutter SDK: >=3.16.0
Dart SDK: >=3.2.0
Android Studio / VS Code
JDK 11+ (pour Android)
```

### Installation

```bash
# Cloner le repository
git clone https://github.com/stealbass/doss.git
cd doss/dossy_chat_ia

# Installer les dépendances
flutter pub get

# Vérifier l'installation
flutter doctor

# Lancer en mode debug
flutter run

# Build APK release
flutter build apk --release

# Build App Bundle (Google Play Store)
flutter build appbundle --release
```

### Configuration

**1. API Keys (.env ou constants):**
```dart
// lib/core/constants/api_config.dart
static const String apiBaseUrl = 'https://dossy.alwaysdata.net/api/mobile';
static const String openaiApiKey = 'YOUR_OPENAI_KEY';
static const String pineconeApiKey = 'YOUR_PINECONE_KEY';
static const String flutterwavePublicKey = 'YOUR_FLUTTERWAVE_KEY';
```

**2. Android Configuration:**
```
Minimum SDK: 21 (Android 5.0)
Target SDK: 34 (Android 14)
Package: com.dossy.chatia
```

---

## 📱 Captures d'Écran

### Onboarding & Auth
- Splash Screen animé
- 4 pages d'introduction
- Login avec email/téléphone
- Register avec juridiction

### Chat & Documents
- Chat IA avec RAG
- Suggestions de prompts
- Upload de documents
- Catégories juridiques

### Outils Étudiants
- Générateur Fiche d'Arrêt
- QCM interactifs avec résultats
- Révision Active flip 3D
- Transcription audio

### Professionnels
- Anonymisation avec détection IA
- Veille juridique (Actualités + Alertes)
- Programme de parrainage

### Profil & Settings
- Informations personnelles
- Abonnement avec quotas
- Mode sombre/clair
- Langue FR/EN

---

## 🤝 Contribution

Ce projet est **propriétaire** et développé par **DOSSY PRO**.

Pour toute collaboration ou suggestion :
- 📧 Email: contact@dossypro.com
- 🌐 Site web: https://dossypro.com
- 📱 GitHub: https://github.com/stealbass/doss

---

## 📄 Licence

Copyright © 2024 DOSSY PRO. Tous droits réservés.

Ce logiciel est propriétaire et confidentiel. Toute reproduction, modification ou distribution sans autorisation écrite préalable est strictement interdite.

---

## 🔗 Liens Utiles

- **Site web:** https://dossypro.com
- **API Documentation:** https://dossy.alwaysdata.net/api/docs
- **Storage R2:** https://files.dossypro.com
- **Support:** contact@dossypro.com

---

## 📝 Changelog

### Version 1.0.0 (Décembre 2025)
- ✅ Phase 1: Architecture & Setup
- ✅ Phase 2: Authentification & Navigation
- ✅ Phase 3.1: Écrans Essentiels (Register, Chat, Subscriptions)
- ✅ Phase 3.2: Documents & Profile
- ✅ Phase 3.3: Outils Étudiants (4 outils)
- ✅ Phase 3.4: Fonctionnalités Professionnelles (3 features)
- ✅ Phase 3.5: Finalisation & Polish

**Progression totale: 70%**

---

**Développé avec ❤️ pour l'Afrique francophone par DOSSY PRO**
