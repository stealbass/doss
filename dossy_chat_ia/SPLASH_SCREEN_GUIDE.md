# 💦 GUIDE SPLASH SCREEN - DOSSY CHAT IA

## 📋 Vue d'Ensemble

Le Splash Screen est le premier écran affiché au lancement de l'application. Il doit :
- Apparaître **immédiatement** (natif, pas Flutter)
- Afficher le **logo DOSSY** sur fond vert
- Être **cohérent** avec l'identité visuelle
- Se charger **rapidement** (< 2 secondes)

---

## 🛠️ Méthode Recommandée : Flutter Native Splash

### Étape 1 : Installer le Package

```bash
# Ajouter dans pubspec.yaml dev_dependencies
flutter pub add --dev flutter_native_splash
```

### Étape 2 : Configuration

Ajouter dans `pubspec.yaml` :

```yaml
flutter_native_splash:
  # Couleur de fond (vert DOSSY)
  color: "#00C853"
  
  # Image du splash screen
  image: assets/images/splash_logo.png
  
  # Image pour mode sombre (optionnel)
  image_dark: assets/images/splash_logo.png
  color_dark: "#00A040"
  
  # Android 12+ (splash screen natif)
  android_12:
    image: assets/images/splash_logo.png
    color: "#00C853"
    icon_background_color: "#00C853"
  
  # Configuration Web (optionnel)
  web: false
  
  # iOS configuration
  ios: true
  android: true
  
  # Paramètres d'affichage
  android_gravity: center
  ios_content_mode: center
  
  # Fullscreen mode
  fullscreen: true
```

### Étape 3 : Créer l'Image Splash

**Spécifications `splash_logo.png`** :
- **Dimensions** : 1152x1152 px (recommandé)
- **Format** : PNG avec transparence
- **Contenu** :
  ```
  ┌─────────────────────┐
  │                     │
  │                     │
  │    ⚖️  DOSSY       │
  │   [Logo Balance]    │
  │                     │
  │  Assistant IA       │
  │   Juridique         │
  │                     │
  │                     │
  └─────────────────────┘
  ```
- **Zone de sécurité** : Contenu centré dans un carré de 768x768 px
- **Fond** : Transparent (couleur appliquée par config)

### Étape 4 : Placer les Fichiers

```bash
# Créer le dossier assets/images si nécessaire
mkdir -p assets/images

# Placer splash_logo.png dans assets/images/
# ls assets/images/splash_logo.png
```

### Étape 5 : Générer le Splash Screen

```bash
# Générer les fichiers natifs
dart run flutter_native_splash:create

# Ou
flutter pub run flutter_native_splash:create
```

Cette commande va générer automatiquement :
- **Android** : `android/app/src/main/res/drawable*/`
- **iOS** : `ios/Runner/Assets.xcassets/LaunchImage.imageset/`
- **Android 12+** : `android/app/src/main/res/values/styles.xml`

---

## 📱 Configuration Android (Détails)

### Fichiers Modifiés Automatiquement

1. **`android/app/src/main/res/drawable/launch_background.xml`**
   ```xml
   <?xml version="1.0" encoding="utf-8"?>
   <layer-list xmlns:android="http://schemas.android.com/apk/res/android">
       <item android:drawable="@color/splash_color"/>
       <item>
           <bitmap
               android:gravity="center"
               android:src="@drawable/splash"/>
       </item>
   </layer-list>
   ```

2. **`android/app/src/main/res/values/colors.xml`**
   ```xml
   <?xml version="1.0" encoding="utf-8"?>
   <resources>
       <color name="splash_color">#00C853</color>
   </resources>
   ```

3. **`android/app/src/main/res/values/styles.xml`**
   ```xml
   <style name="LaunchTheme" parent="@android:style/Theme.Light.NoTitleBar">
       <item name="android:windowBackground">@drawable/launch_background</item>
   </style>
   ```

4. **Android 12+ (API 31+)**
   ```xml
   <style name="NormalTheme" parent="@android:style/Theme.Light.NoTitleBar">
       <item name="android:windowSplashScreenBackground">#00C853</item>
       <item name="android:windowSplashScreenAnimatedIcon">@drawable/splash</item>
   </style>
   ```

---

## 🍎 Configuration iOS (Détails)

### Fichiers Modifiés Automatiquement

1. **`ios/Runner/Assets.xcassets/LaunchImage.imageset/`**
   - `LaunchImage.png` (1x)
   - `LaunchImage@2x.png` (2x)
   - `LaunchImage@3x.png` (3x)

2. **`ios/Runner/Base.lproj/LaunchScreen.storyboard`**
   - Storyboard avec l'image centrée
   - Couleur de fond verte (#00C853)

---

## 🎨 Design du Splash Screen

### Option 1 : Logo Simple
```
┌─────────────────┐
│                 │
│   ⚖️  DOSSY    │
│   [Balance]     │
│                 │
└─────────────────┘
```
- Logo blanc sur fond vert
- Taille : 200x200 px (zone visible)
- Centré verticalement et horizontalement

### Option 2 : Logo + Slogan
```
┌─────────────────┐
│                 │
│   ⚖️  DOSSY    │
│   Assistant IA  │
│   Juridique     │
│                 │
└─────────────────┘
```
- Logo + texte blanc
- Taille : 250x300 px

### Option 3 : Logo + Loading (Animé)
```
┌─────────────────┐
│   ⚖️  DOSSY    │
│                 │
│   ●●●○○○       │
│  Chargement...  │
└─────────────────┘
```
- Logo + indicateur de chargement
- Animation gérée par le splash screen Flutter (après le natif)

---

## 🔄 Transition vers Flutter

### Dans `lib/main.dart`

```dart
import 'package:flutter_native_splash/flutter_native_splash.dart';

void main() {
  // Préserver le splash screen natif
  WidgetsBinding widgetsBinding = WidgetsFlutterBinding.ensureInitialized();
  FlutterNativeSplash.preserve(widgetsBinding: widgetsBinding);
  
  runApp(const DossyChatIAApp());
}

// Dans le premier écran (SplashScreen custom Flutter)
class SplashScreen extends StatefulWidget {
  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _initialize();
  }
  
  Future<void> _initialize() async {
    // Initialiser les services (AuthProvider, etc.)
    await Future.delayed(Duration(seconds: 2));
    
    // Retirer le splash screen natif
    FlutterNativeSplash.remove();
    
    // Naviguer vers l'écran suivant
    Navigator.pushReplacementNamed(context, '/onboarding');
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Color(0xFF00C853),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Logo animé
            Image.asset('assets/images/splash_logo.png', width: 200),
            SizedBox(height: 30),
            // Indicateur de chargement
            CircularProgressIndicator(color: Colors.white),
          ],
        ),
      ),
    );
  }
}
```

---

## ✅ Checklist de Vérification

### Avant Génération
- [ ] `flutter_native_splash` ajouté dans `dev_dependencies`
- [ ] `splash_logo.png` créé (1152x1152 minimum)
- [ ] Image placée dans `assets/images/`
- [ ] Configuration ajoutée dans `pubspec.yaml`
- [ ] `flutter pub get` exécuté

### Après Génération
- [ ] Fichiers Android générés dans `res/drawable*/`
- [ ] Fichiers iOS générés dans `Assets.xcassets/`
- [ ] Couleurs configurées correctement
- [ ] Test sur émulateur Android
- [ ] Test sur simulateur iOS
- [ ] Test en mode sombre (si configuré)
- [ ] Test Android 12+ (API 31+)

---

## 🚀 Commandes Utiles

```bash
# Installer le package
flutter pub add --dev flutter_native_splash

# Installer les dépendances
flutter pub get

# Générer le splash screen
dart run flutter_native_splash:create

# Générer avec fichier de config personnalisé
dart run flutter_native_splash:create --path=splash_config.yaml

# Supprimer le splash screen (revenir à l'état initial)
dart run flutter_native_splash:remove

# Tester sur Android
flutter run

# Tester sur iOS
flutter run
```

---

## 🎯 Configuration Complète Exemple

```yaml
# pubspec.yaml
dev_dependencies:
  flutter_native_splash: ^2.3.5

flutter_native_splash:
  color: "#00C853"
  color_dark: "#00A040"
  image: assets/images/splash_logo.png
  image_dark: assets/images/splash_logo_dark.png
  
  android_12:
    image: assets/images/splash_logo.png
    icon_background_color: "#00C853"
    image_dark: assets/images/splash_logo_dark.png
    icon_background_color_dark: "#00A040"
  
  android_gravity: center
  ios_content_mode: center
  fullscreen: true
  
  android: true
  ios: true
  web: false
```

---

## 🚨 Dépannage

### Splash screen non affiché
```bash
# Nettoyer et reconstruire
flutter clean
flutter pub get
dart run flutter_native_splash:create
flutter run
```

### Image étirée/déformée
- Vérifier la résolution de l'image (minimum 1152x1152)
- S'assurer que `android_gravity: center` et `ios_content_mode: center`
- Zone de sécurité respectée (768x768 px au centre)

### Android 12+ affiche icône launcher
- Vérifier la config `android_12` dans `pubspec.yaml`
- Régénérer avec `dart run flutter_native_splash:create`

### iOS affiche écran blanc
- Vérifier le storyboard dans Xcode
- S'assurer que les images sont bien importées
- Vérifier `Info.plist` pour `UILaunchStoryboardName`

---

## 📚 Ressources

- **Package** : https://pub.dev/packages/flutter_native_splash
- **Documentation** : https://flutter.dev/docs/development/ui/advanced/splash-screen
- **Android 12 Splash** : https://developer.android.com/guide/topics/ui/splash-screen
- **iOS Launch Screen** : https://developer.apple.com/design/human-interface-guidelines/launch-screen

---

**© 2025 DOSSY PRO - L'IA au service du Droit**
