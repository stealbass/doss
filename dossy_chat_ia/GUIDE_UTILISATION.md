# 📱 Guide d'Utilisation - DOSSY CHAT IA

## Comment Exporter et Importer ce Projet Flutter

---

## 🎯 Objectif

Ce guide vous montre comment :
1. ✅ Exporter le dossier `dossy_chat_ia` depuis le serveur
2. ✅ L'importer dans **Android Studio** ou **VS Code**
3. ✅ Générer l'APK Android
4. ✅ Tester sur émulateur ou appareil physique

---

## 📦 Méthode 1 : Exporter depuis le Serveur

### Option A : Télécharger via SSH/SFTP

1. **Compresser le dossier sur le serveur**
   ```bash
   cd /home/user/webapp
   tar -czf dossy_chat_ia.tar.gz dossy_chat_ia/
   ```

2. **Télécharger avec SFTP ou SCP**
   ```bash
   # Depuis votre machine locale
   scp user@server:/home/user/webapp/dossy_chat_ia.tar.gz ~/Downloads/
   ```

3. **Décompresser sur votre machine**
   ```bash
   cd ~/Downloads
   tar -xzf dossy_chat_ia.tar.gz
   ```

### Option B : Clone Git (Si projet sur GitHub)

```bash
git clone https://github.com/stealbass/doss.git
cd doss/dossy_chat_ia
```

---

## 🖥️ Méthode 2 : Importer dans Android Studio

### Étape 1 : Ouvrir Android Studio
1. Lancer **Android Studio**
2. Cliquer sur **"Open an Existing Project"**
3. Naviguer vers le dossier `dossy_chat_ia`
4. Sélectionner le dossier et cliquer **"OK"**

### Étape 2 : Installation des Dépendances
Android Studio va automatiquement :
- Détecter le projet Flutter
- Télécharger les dépendances (`pub get`)
- Indexer les fichiers

Si ce n'est pas automatique, ouvrez un terminal dans Android Studio :
```bash
flutter pub get
```

### Étape 3 : Vérifier la Configuration
1. **Vérifier Flutter SDK**
   - File → Settings → Languages & Frameworks → Flutter
   - Vérifier que le chemin Flutter SDK est correct

2. **Vérifier Android SDK**
   - File → Settings → Appearance & Behavior → System Settings → Android SDK
   - S'assurer que Android 13.0 (API 33) ou supérieur est installé

### Étape 4 : Lancer l'Application
1. **Sélectionner un Device**
   - Émulateur Android (AVD Manager)
   - Ou appareil physique connecté en USB

2. **Lancer l'App**
   - Cliquer sur le bouton ▶️ "Run"
   - Ou `Shift + F10`

---

## 💻 Méthode 3 : Importer dans VS Code

### Étape 1 : Ouvrir dans VS Code
1. Lancer **VS Code**
2. File → Open Folder
3. Sélectionner le dossier `dossy_chat_ia`

### Étape 2 : Installer les Extensions Nécessaires
1. **Flutter Extension**
   - Aller dans Extensions (Ctrl+Shift+X)
   - Rechercher "Flutter"
   - Installer l'extension officielle "Flutter" (by Dart Code)

2. **Dart Extension** (installée automatiquement avec Flutter)

### Étape 3 : Installation des Dépendances
Ouvrir un terminal dans VS Code (`Ctrl + `` `) :
```bash
flutter pub get
```

### Étape 4 : Lancer l'Application
1. **Ouvrir Command Palette** : `Ctrl + Shift + P`
2. Taper : `Flutter: Select Device`
3. Choisir votre émulateur ou appareil
4. Appuyer sur `F5` pour lancer en mode Debug

Ou via terminal :
```bash
flutter run
```

---

## 📲 Générer l'APK Android

### APK Debug (pour tests)
```bash
cd dossy_chat_ia
flutter build apk --debug
```

L'APK sera dans : `build/app/outputs/flutter-apk/app-debug.apk`

### APK Release (pour production)
```bash
flutter build apk --release
```

L'APK sera dans : `build/app/outputs/flutter-apk/app-release.apk`

### App Bundle (pour Google Play Store)
```bash
flutter build appbundle --release
```

Le bundle sera dans : `build/app/outputs/bundle/release/app-release.aab`

---

## 📱 Tester sur Appareil Physique

### 1. Activer le Mode Développeur
Sur votre téléphone Android :
1. Aller dans **Paramètres**
2. **À propos du téléphone**
3. Taper 7 fois sur **"Numéro de build"**
4. Mode développeur activé !

### 2. Activer le Débogage USB
1. Aller dans **Paramètres → Options pour les développeurs**
2. Activer **"Débogage USB"**

### 3. Connecter le Téléphone
1. Connecter via câble USB
2. Sur le téléphone, autoriser le débogage USB
3. Dans le terminal :
   ```bash
   flutter devices
   ```
   Votre appareil devrait apparaître

### 4. Lancer l'App
```bash
flutter run
```

Ou installer directement l'APK :
```bash
flutter install
```

---

## 🔧 Résolution de Problèmes

### Problème : "Flutter SDK not found"
**Solution** :
```bash
# Vérifier l'installation Flutter
flutter doctor

# Si Flutter n'est pas installé, télécharger depuis :
# https://docs.flutter.dev/get-started/install
```

### Problème : "Android licenses not accepted"
**Solution** :
```bash
flutter doctor --android-licenses
# Accepter toutes les licences (taper 'y')
```

### Problème : "Gradle build failed"
**Solution** :
```bash
cd android
./gradlew clean
cd ..
flutter clean
flutter pub get
flutter run
```

### Problème : "No devices found"
**Solution** :
1. Créer un émulateur Android dans AVD Manager
2. Ou connecter un appareil physique
3. Vérifier : `flutter devices`

### Problème : Dependencies not installing
**Solution** :
```bash
flutter clean
rm pubspec.lock
flutter pub get
```

---

## 🎨 Personnalisation de l'App

### Changer le Nom de l'App
Modifier dans `android/app/src/main/AndroidManifest.xml` :
```xml
<application
    android:label="VOTRE_NOM_APP"
```

### Changer l'Icône de l'App
1. Placer votre icône dans `assets/icons/app_icon.png` (512x512 px)
2. Exécuter :
   ```bash
   flutter pub run flutter_launcher_icons
   ```

### Changer le Package Name
```bash
flutter pub run change_app_package_name:main com.votre.package
```

### Changer l'URL de l'API
Modifier dans `lib/core/constants/app_constants.dart` :
```dart
static const String baseUrl = 'VOTRE_URL_API';
```

---

## 📊 Architecture du Projet

```
dossy_chat_ia/
├── lib/
│   ├── main.dart                  # Point d'entrée
│   ├── core/                      # Configuration globale
│   │   ├── constants/             # Constantes
│   │   └── theme/                 # Thème & couleurs
│   ├── data/                      # Couche de données
│   │   ├── models/                # Modèles de données
│   │   ├── providers/             # State management (Provider)
│   │   └── services/              # Services API
│   └── presentation/              # Couche UI
│       ├── screens/               # Écrans
│       └── widgets/               # Widgets réutilisables
│
├── android/                       # Configuration Android
├── ios/                           # Configuration iOS (optionnel)
├── assets/                        # Ressources (images, fonts)
├── pubspec.yaml                   # Dépendances Flutter
└── README.md                      # Documentation
```

---

## 🚀 Commandes Utiles

| Commande | Description |
|----------|-------------|
| `flutter doctor` | Vérifier l'installation Flutter |
| `flutter pub get` | Télécharger les dépendances |
| `flutter clean` | Nettoyer le projet |
| `flutter run` | Lancer l'app en mode debug |
| `flutter build apk` | Générer l'APK |
| `flutter build appbundle` | Générer l'App Bundle |
| `flutter devices` | Lister les appareils connectés |
| `flutter emulators` | Lister les émulateurs disponibles |
| `flutter emulators --launch <id>` | Lancer un émulateur |
| `flutter analyze` | Analyser le code |
| `flutter test` | Lancer les tests |

---

## 📞 Besoin d'Aide ?

### Documentation Flutter
- 📖 [Flutter Documentation](https://docs.flutter.dev/)
- 📖 [Flutter Cookbook](https://docs.flutter.dev/cookbook)
- 📖 [Dart Language Tour](https://dart.dev/guides/language/language-tour)

### Communauté
- 💬 [Stack Overflow - Flutter](https://stackoverflow.com/questions/tagged/flutter)
- 💬 [Flutter Dev Community](https://flutter.dev/community)

### Support DOSSY
- 📧 Email : support@dossypro.com
- 🌐 Site : https://dossypro.com

---

## ✅ Checklist de Démarrage

- [ ] Flutter SDK installé (`flutter doctor` passe)
- [ ] Android Studio ou VS Code installé
- [ ] Projet importé et ouvert
- [ ] Dépendances installées (`flutter pub get`)
- [ ] Émulateur créé ou appareil connecté
- [ ] Application lancée avec succès (`flutter run`)
- [ ] APK généré (`flutter build apk`)

---

**Version du Guide** : 1.0  
**Dernière mise à jour** : 2024-12-16

Bon développement ! 🚀
