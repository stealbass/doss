# 🚀 Guide de Création du Projet Flutter DOSSY CHAT IA

## 📋 Prérequis

- Flutter SDK 3.24+ installé
- Android Studio ou VSCode avec extensions Flutter
- Git installé
- Compte GitHub (optionnel)

---

## 🏗️ Étape 1 : Créer le Projet Flutter

### Commande de Création

```bash
# Créer le projet Flutter
flutter create dossy_chat_ia

# Se déplacer dans le dossier
cd dossy_chat_ia

# Ouvrir dans VSCode
code .
```

---

## 📦 Étape 2 : Configurer `pubspec.yaml`

Remplacer le contenu de `pubspec.yaml` par :

```yaml
name: dossy_chat_ia
description: "Analyse et Assistant Juridique, Fiscal & Social"
publish_to: 'none'
version: 1.0.0+1

environment:
  sdk: '>=3.2.0 <4.0.0'

dependencies:
  flutter:
    sdk: flutter
  flutter_localizations:
    sdk: flutter

  # State Management
  flutter_riverpod: ^2.5.1
  
  # HTTP & API
  dio: ^5.4.0
  pretty_dio_logger: ^1.3.1
  
  # Local Storage
  hive: ^2.2.3
  hive_flutter: ^1.1.0
  shared_preferences: ^2.2.2
  
  # Authentication & Security
  flutter_secure_storage: ^9.0.0
  
  # UI Components
  google_fonts: ^6.1.0
  flutter_svg: ^2.0.9
  cached_network_image: ^3.3.1
  shimmer: ^3.0.0
  lottie: ^3.0.0
  
  # Chat UI
  flutter_chat_ui: ^1.6.12
  dash_chat_2: ^0.0.18
  
  # Markdown
  flutter_markdown: ^0.6.18
  markdown: ^7.2.1
  
  # PDF
  syncfusion_flutter_pdfviewer: ^24.2.3
  pdf: ^3.10.7
  
  # Audio Recording (Plans 5000+)
  flutter_sound: ^9.2.13
  permission_handler: ^11.1.0
  
  # File Picker
  file_picker: ^6.1.1
  
  # Payments
  flutterwave_standard: ^1.0.8
  
  # Push Notifications
  firebase_core: ^2.24.2
  firebase_messaging: ^14.7.9
  
  # Analytics
  firebase_analytics: ^10.8.0
  
  # Localization
  intl: ^0.18.1
  
  # QR Code (Parrainage)
  qr_flutter: ^4.1.0
  share_plus: ^7.2.1
  
  # Image
  image_picker: ^1.0.5
  
  # URL Launcher
  url_launcher: ^6.2.2
  
  # Animations
  animate_do: ^3.1.2
  
  # Icons
  cupertino_icons: ^1.0.6
  font_awesome_flutter: ^10.6.0

dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^3.0.0
  build_runner: ^2.4.7
  hive_generator: ^2.0.1
  riverpod_generator: ^2.3.9

flutter:
  uses-material-design: true
  
  assets:
    - assets/images/
    - assets/icons/
    - assets/animations/
    - assets/fonts/
  
  fonts:
    - family: Poppins
      fonts:
        - asset: assets/fonts/Poppins-Regular.ttf
        - asset: assets/fonts/Poppins-Medium.ttf
          weight: 500
        - asset: assets/fonts/Poppins-SemiBold.ttf
          weight: 600
        - asset: assets/fonts/Poppins-Bold.ttf
          weight: 700
```

Installer les dépendances :
```bash
flutter pub get
```

---

## 📁 Étape 3 : Structure des Dossiers

Créer cette structure dans `lib/` :

```
lib/
├── main.dart
├── app.dart
├── core/
│   ├── constants/
│   │   ├── app_colors.dart
│   │   ├── app_texts.dart
│   │   ├── app_assets.dart
│   │   └── api_constants.dart
│   ├── theme/
│   │   ├── app_theme.dart
│   │   └── text_styles.dart
│   ├── utils/
│   │   ├── validators.dart
│   │   ├── formatters.dart
│   │   └── helpers.dart
│   ├── services/
│   │   ├── api_service.dart
│   │   ├── storage_service.dart
│   │   ├── auth_service.dart
│   │   └── notification_service.dart
│   └── l10n/
│       ├── app_en.arb
│       └── app_fr.arb
├── data/
│   ├── models/
│   │   ├── user_model.dart
│   │   ├── conversation_model.dart
│   │   ├── message_model.dart
│   │   ├── plan_model.dart
│   │   └── document_model.dart
│   ├── repositories/
│   │   ├── auth_repository.dart
│   │   ├── chat_repository.dart
│   │   ├── user_repository.dart
│   │   └── payment_repository.dart
│   └── providers/
│       ├── auth_provider.dart
│       ├── theme_provider.dart
│       ├── locale_provider.dart
│       └── chat_provider.dart
├── features/
│   ├── auth/
│   │   ├── screens/
│   │   │   ├── splash_screen.dart
│   │   │   ├── onboarding_screen.dart
│   │   │   ├── login_screen.dart
│   │   │   ├── register_screen.dart
│   │   │   └── jurisdiction_screen.dart
│   │   └── widgets/
│   ├── home/
│   │   ├── screens/
│   │   │   └── home_screen.dart
│   │   └── widgets/
│   ├── chat/
│   │   ├── screens/
│   │   │   ├── conversations_screen.dart
│   │   │   └── chat_screen.dart
│   │   └── widgets/
│   │       ├── message_bubble.dart
│   │       ├── prompt_suggestions.dart
│   │       └── chat_input.dart
│   ├── student_tools/
│   │   ├── screens/
│   │   │   ├── fiche_arret_screen.dart
│   │   │   ├── fiche_revision_screen.dart
│   │   │   ├── qcm_generator_screen.dart
│   │   │   └── plan_dissertation_screen.dart
│   │   └── widgets/
│   ├── library/
│   │   ├── screens/
│   │   │   ├── library_screen.dart
│   │   │   └── document_viewer_screen.dart
│   │   └── widgets/
│   ├── profile/
│   │   ├── screens/
│   │   │   ├── profile_screen.dart
│   │   │   ├── referral_screen.dart
│   │   │   └── settings_screen.dart
│   │   └── widgets/
│   ├── subscription/
│   │   ├── screens/
│   │   │   ├── plans_screen.dart
│   │   │   └── payment_screen.dart
│   │   └── widgets/
│   └── enterprise/
│       ├── screens/
│       │   ├── multi_accounts_screen.dart
│       │   ├── templates_screen.dart
│       │   └── alerts_screen.dart
│       └── widgets/
└── widgets/
    ├── custom_button.dart
    ├── custom_textfield.dart
    ├── loading_indicator.dart
    └── error_widget.dart
```

---

## 🎨 Étape 4 : Créer les Fichiers de Base

### 1. `lib/core/constants/app_colors.dart`

```dart
import 'package:flutter/material.dart';

class AppColors {
  // Primary Colors
  static const Color primaryGreen = Color(0xFF00C853);
  static const Color secondaryGreen = Color(0xFF4CAF50);
  static const Color darkGreen = Color(0xFF2E7D32);
  static const Color lightGreen = Color(0xFFA5D6A7);
  
  // Semantic Colors
  static const Color success = Color(0xFF00C853);
  static const Color warning = Color(0xFFFFC107);
  static const Color error = Color(0xFFF44336);
  static const Color info = Color(0xFF2196F3);
  
  // Neutral Colors
  static const Color background = Color(0xFFF5F5F5);
  static const Color cardBackground = Color(0xFFFFFFFF);
  static const Color textPrimary = Color(0xFF212121);
  static const Color textSecondary = Color(0xFF757575);
  static const Color divider = Color(0xFFBDBDBD);
  
  // Gradient
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primaryGreen, secondaryGreen],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
```

### 2. `lib/core/theme/app_theme.dart`

```dart
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../constants/app_colors.dart';

class AppTheme {
  static ThemeData lightTheme = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.primaryGreen,
      brightness: Brightness.light,
    ),
    textTheme: GoogleFonts.poppinsTextTheme(),
    scaffoldBackgroundColor: AppColors.background,
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.primaryGreen,
      foregroundColor: Colors.white,
      elevation: 0,
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.primaryGreen,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: AppColors.divider),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: AppColors.divider),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: AppColors.primaryGreen, width: 2),
      ),
    ),
  );
  
  static ThemeData darkTheme = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.primaryGreen,
      brightness: Brightness.dark,
    ),
    textTheme: GoogleFonts.poppinsTextTheme(ThemeData.dark().textTheme),
  );
}
```

### 3. `lib/core/constants/api_constants.dart`

```dart
class ApiConstants {
  // Base URL
  static const String baseUrl = 'https://dossy.alwaysdata.net/api/mobile';
  
  // Auth Endpoints
  static const String register = '/register';
  static const String login = '/login';
  static const String logout = '/logout';
  static const String profile = '/profile';
  
  // Chat Endpoints
  static const String createConversation = '/chat/conversation';
  static const String getConversations = '/chat/conversations';
  static const String sendMessage = '/chat/send';
  
  // Documents Endpoints
  static const String uploadDocument = '/documents/upload';
  static const String searchDocuments = '/documents/search';
  
  // Subscription Endpoints
  static const String getPlans = '/plans';
  static const String getCurrentSubscription = '/subscription/current';
  static const String initiatePayment = '/subscription/initiate';
  
  // Referral Endpoints
  static const String getReferralCode = '/referral/code';
  static const String validateReferral = '/referral/validate';
}
```

### 4. `lib/main.dart`

```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'app.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Hive
  await Hive.initFlutter();
  
  // Initialize Firebase (for notifications)
  // await Firebase.initializeApp();
  
  runApp(
    const ProviderScope(
      child: DossyChatApp(),
    ),
  );
}
```

### 5. `lib/app.dart`

```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/screens/splash_screen.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

class DossyChatApp extends ConsumerWidget {
  const DossyChatApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Watch theme provider
    // final isDarkMode = ref.watch(themeProvider);
    
    // Watch locale provider
    // final locale = ref.watch(localeProvider);
    
    return MaterialApp(
      title: 'DOSSY CHAT IA',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: ThemeMode.light, // or themeMode: isDarkMode ? ThemeMode.dark : ThemeMode.light,
      
      // Localization
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [
        Locale('fr', 'FR'),
        Locale('en', 'US'),
      ],
      locale: const Locale('fr', 'FR'), // or locale: locale,
      
      home: const SplashScreen(),
    );
  }
}
```

---

## 🎯 Étape 5 : Créer les Écrans Principaux

Je vais vous fournir les templates pour les écrans clés.

### `lib/features/auth/screens/splash_screen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:animate_do/animate_do.dart';
import '../../../core/constants/app_colors.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _navigateToNext();
  }

  Future<void> _navigateToNext() async {
    await Future.delayed(const Duration(seconds: 3));
    // Check if user is logged in
    // Navigate to OnboardingScreen or HomeScreen
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: AppColors.primaryGradient,
        ),
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              FadeInDown(
                duration: const Duration(milliseconds: 800),
                child: const Icon(
                  Icons.gavel_rounded,
                  size: 100,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 20),
              FadeInUp(
                duration: const Duration(milliseconds: 800),
                child: const Text(
                  'DOSSY CHAT IA',
                  style: TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(height: 10),
              FadeInUp(
                delay: const Duration(milliseconds: 200),
                child: const Text(
                  'Analyse et Assistant Juridique,\nFiscal & Social',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.white70,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

## 🚀 Étape 6 : Exécuter le Projet

```bash
# Lancer sur émulateur Android
flutter run

# Ou sur device iOS
flutter run -d ios

# Ou sur navigateur web (pour tester)
flutter run -d chrome
```

---

## 📱 Étape 7 : Build APK Android

```bash
# Build APK de développement
flutter build apk --debug

# Build APK de production (release)
flutter build apk --release

# Build App Bundle (pour Google Play Store)
flutter build appbundle --release
```

Le fichier APK sera dans `build/app/outputs/flutter-apk/`

---

## 📋 Checklist de Configuration

- [ ] Flutter SDK installé (3.24+)
- [ ] Projet créé (`flutter create dossy_chat_ia`)
- [ ] `pubspec.yaml` configuré avec toutes les dépendances
- [ ] `flutter pub get` exécuté
- [ ] Structure des dossiers créée
- [ ] Fichiers de base créés (colors, theme, API constants)
- [ ] Firebase configuré (pour notifications)
- [ ] Flutterwave configuré (pour paiements)
- [ ] Assets ajoutés (images, fonts)
- [ ] Localization configurée (FR/EN)
- [ ] Test sur émulateur réussi

---

## 🆘 Problèmes Courants

### Erreur : "Dart SDK is not found"
```bash
flutter doctor
flutter clean
flutter pub get
```

### Erreur : "Gradle build failed"
Modifier `android/app/build.gradle` :
```gradle
android {
    compileSdkVersion 34
    
    defaultConfig {
        minSdkVersion 21
        targetSdkVersion 34
    }
}
```

### Erreur : "CocoaPods not installed" (iOS)
```bash
sudo gem install cocoapods
cd ios
pod install
```

---

**Date** : 2025-11-27  
**Projet** : DOSSY CHAT IA  
**Version** : 1.0.0
