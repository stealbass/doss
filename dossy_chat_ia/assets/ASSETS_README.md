# 📂 DOSSY Chat IA - Assets Guide

## ⚠️ Important: Replace Placeholder Assets

Les fichiers actuellement présents dans ce dossier sont des **placeholders vides**. Vous devez les remplacer par les vrais assets pour que l'application fonctionne correctement.

---

## 📁 Structure des Assets

```
assets/
├── fonts/
│   ├── Poppins-Regular.ttf
│   ├── Poppins-Medium.ttf
│   ├── Poppins-SemiBold.ttf
│   └── Poppins-Bold.ttf
├── icons/
│   ├── app_icon.png (1024x1024px)
│   └── app_icon_foreground.png (1024x1024px)
└── images/
    └── (vos images d'application)
```

---

## 🔤 1. Fonts Poppins (REQUIS)

### Option 1: Google Fonts (Recommandé)
Téléchargez gratuitement depuis Google Fonts:
- **URL:** https://fonts.google.com/specimen/Poppins
- Cliquez sur "Download family"
- Extrayez le ZIP et copiez ces 4 fichiers dans `assets/fonts/`:
  - `Poppins-Regular.ttf`
  - `Poppins-Medium.ttf`
  - `Poppins-SemiBold.ttf`
  - `Poppins-Bold.ttf`

### Option 2: Utiliser google_fonts package (Alternative)
Si vous ne voulez pas télécharger les fonts, modifiez `pubspec.yaml`:

**Supprimez la section fonts:**
```yaml
# Commentez ou supprimez cette section
# fonts:
#   - family: Poppins
#     fonts:
#       - asset: assets/fonts/Poppins-Regular.ttf
#       ...
```

**Et utilisez google_fonts dans votre code:**
```dart
import 'package:google_fonts/google_fonts.dart';

// Dans votre ThemeData
textTheme: GoogleFonts.poppinsTextTheme(),
```

---

## 🎨 2. App Icons (REQUIS)

### Créer vos icônes d'application:

#### Option 1: Design personnalisé
1. Créez une icône 1024x1024px (format PNG)
2. Pour l'icône adaptative Android:
   - `app_icon.png` - Icône complète avec fond
   - `app_icon_foreground.png` - Icône sans fond (transparent)

#### Option 2: Générateur en ligne (Rapide)
- **Canva:** https://www.canva.com/create/app-icons/
- **App Icon Generator:** https://appicon.co/
- **Icon Kitchen:** https://icon.kitchen/

#### Option 3: Icône simple DOSSY
Créez une icône simple avec:
- Fond vert: `#00C853`
- Texte blanc: "DOSSY" ou logo juridique
- Police: Poppins Bold

### Installation de l'icône:
```bash
# Après avoir placé app_icon.png dans assets/icons/
flutter pub run flutter_launcher_icons
```

---

## 🖼️ 3. Images (Optionnel)

Ajoutez vos images d'application dans `assets/images/`:
- Logo de l'application
- Images pour l'onboarding
- Images pour les écrans vides
- Images pour les tutoriels
- Etc.

### Exemples d'images recommandées:
```
assets/images/
├── logo.png
├── onboarding_1.png
├── onboarding_2.png
├── onboarding_3.png
├── empty_state.png
├── legal_icon.png
├── ai_assistant.png
└── payment_success.png
```

---

## 🚀 Quick Fix - Démarrer MAINTENANT

### Solution temporaire pour tester l'app:

**Étape 1:** Téléchargez les fonts Poppins
```bash
# Sur Windows (PowerShell)
cd C:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer\dossy_chat_ia\assets\fonts

# Téléchargez depuis: https://fonts.google.com/specimen/Poppins
# Ou utilisez ce lien direct:
# https://github.com/google/fonts/tree/main/ofl/poppins
```

**Étape 2:** Créez une icône simple
```bash
# Créez une icône verte 1024x1024 avec le texte "DOSSY"
# Ou utilisez https://appicon.co/ pour générer rapidement
```

**Étape 3:** Relancez l'application
```bash
flutter clean
flutter pub get
flutter run -d edge
```

---

## 🔗 Liens Utiles

### Fonts:
- **Poppins:** https://fonts.google.com/specimen/Poppins
- **Google Fonts GitHub:** https://github.com/google/fonts

### Icônes:
- **Canva:** https://www.canva.com/create/app-icons/
- **App Icon Generator:** https://appicon.co/
- **Icon Kitchen:** https://icon.kitchen/
- **Flutter Launcher Icons:** https://pub.dev/packages/flutter_launcher_icons

### Images:
- **Unsplash (Free):** https://unsplash.com/
- **Pexels (Free):** https://www.pexels.com/
- **Flaticon (Icons):** https://www.flaticon.com/

---

## ✅ Vérification

Après avoir ajouté vos assets, vérifiez:

```bash
# 1. Vérifiez que les fonts existent
ls -la assets/fonts/

# 2. Vérifiez que les icônes existent
ls -la assets/icons/

# 3. Nettoyez et régénérez
flutter clean
flutter pub get
flutter pub run flutter_launcher_icons

# 4. Lancez l'app
flutter run -d edge
```

---

## 🆘 Support

Si vous continuez à avoir des erreurs:

1. Assurez-vous que tous les fichiers existent physiquement
2. Vérifiez les permissions des fichiers
3. Lancez `flutter clean` puis `flutter pub get`
4. Vérifiez que les chemins dans `pubspec.yaml` correspondent exactement aux fichiers

---

**Note:** Les fichiers actuels sont des **placeholders vides** créés automatiquement pour éviter les erreurs de compilation. Ils **DOIVENT** être remplacés par de vrais assets pour que l'application fonctionne correctement.
