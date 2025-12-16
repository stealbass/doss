# 🎨 GUIDE DE GÉNÉRATION DES ICÔNES - DOSSY CHAT IA

## 📋 Prérequis

Le projet est déjà configuré avec `flutter_launcher_icons: ^0.13.1` dans `pubspec.yaml`.

## 🖼️ Icônes Requises

### 1. **Icône Principale** (`app_icon.png`)
- **Emplacement** : `assets/icons/app_icon.png`
- **Dimensions** : 1024x1024 px (minimum)
- **Format** : PNG avec transparence
- **Contenu** :
  - Logo DOSSY avec balance de justice
  - Couleur dominante : Vert #00C853
  - Design moderne et professionnel
  - Doit être visible sur fond clair ET foncé

### 2. **Icône Adaptive Foreground** (`app_icon_foreground.png`)
- **Emplacement** : `assets/icons/app_icon_foreground.png`
- **Dimensions** : 1024x1024 px
- **Format** : PNG avec transparence
- **Contenu** :
  - Logo DOSSY centré
  - Zone de sécurité : cercle de 640px de diamètre au centre
  - Fond transparent
  - Prévu pour être superposé sur le background

### 3. **Background Adaptive Icon**
- **Couleur** : `#00C853` (vert signature de DOSSY)
- **Déjà configuré** dans `pubspec.yaml` : `adaptive_icon_background: "#00C853"`

---

## 🎨 Recommandations de Design

### Logo DOSSY
```
┌─────────────────────┐
│                     │
│    ⚖️  DOSSY       │
│    [Balance]        │
│                     │
│   Assistant IA      │
│   Juridique         │
│                     │
└─────────────────────┘
```

### Palette de Couleurs
- **Vert Principal** : #00C853
- **Vert Foncé** : #00A040
- **Blanc** : #FFFFFF
- **Noir/Gris** : #212121 / #757575

### Éléments Graphiques
- ⚖️ Balance de justice (symbole juridique)
- 🤖 Élément IA subtil (cercles, lignes technologiques)
- 📚 Livre de droit (optionnel)
- 🇨🇮🇸🇳🇧🇯 Couleurs africaines (optionnel, subtil)

---

## 🚀 Génération des Icônes

### Étape 1 : Créer les Images
1. Créer `app_icon.png` (1024x1024)
2. Créer `app_icon_foreground.png` (1024x1024)
3. Placer les fichiers dans `assets/icons/`

### Étape 2 : Générer les Icônes
```bash
# Installer les dépendances (si nécessaire)
flutter pub get

# Générer les icônes pour Android & iOS
flutter pub run flutter_launcher_icons

# Alternative (si flutter_launcher_icons est installé globalement)
dart run flutter_launcher_icons
```

### Étape 3 : Vérifier la Génération
```bash
# Vérifier les icônes Android générées
ls -la android/app/src/main/res/mipmap-*/

# Vérifier les icônes iOS générées
ls -la ios/Runner/Assets.xcassets/AppIcon.appiconset/
```

---

## 📱 Icônes Générées Automatiquement

### Android (Densités)
- `mipmap-mdpi/ic_launcher.png` - 48x48 px
- `mipmap-hdpi/ic_launcher.png` - 72x72 px
- `mipmap-xhdpi/ic_launcher.png` - 96x96 px
- `mipmap-xxhdpi/ic_launcher.png` - 144x144 px
- `mipmap-xxxhdpi/ic_launcher.png` - 192x192 px

### Android Adaptive Icon (API 26+)
- `mipmap-anydpi-v26/ic_launcher.xml`
- Foreground + Background layers séparés
- Support des formes système (cercle, carré arrondi, etc.)

### iOS (Toutes les Tailles)
- `AppIcon-20@2x.png` - 40x40 px
- `AppIcon-20@3x.png` - 60x60 px
- `AppIcon-29@2x.png` - 58x58 px
- `AppIcon-29@3x.png` - 87x87 px
- `AppIcon-40@2x.png` - 80x80 px
- `AppIcon-40@3x.png` - 120x120 px
- `AppIcon-60@2x.png` - 120x120 px
- `AppIcon-60@3x.png` - 180x180 px
- `AppIcon-76.png` - 76x76 px (iPad)
- `AppIcon-76@2x.png` - 152x152 px (iPad)
- `AppIcon-83.5@2x.png` - 167x167 px (iPad Pro)
- `AppIcon-1024.png` - 1024x1024 px (App Store)

---

## 🎨 Outils de Création Recommandés

### Outils en Ligne
- **Figma** (https://figma.com) - Design professionnel
- **Canva** (https://canva.com) - Design simplifié
- **IconKitchen** (https://icon.kitchen) - Générateur d'icônes Android
- **AppIconMaker** (https://appiconmaker.co) - Multi-plateforme

### Logiciels Desktop
- **Adobe Illustrator** - Design vectoriel professionnel
- **Affinity Designer** - Alternative à Illustrator
- **Inkscape** - Gratuit, open-source
- **GIMP** - Édition d'images gratuite

---

## ✅ Checklist de Vérification

Avant de générer les icônes, assurez-vous que :

- [ ] `app_icon.png` existe dans `assets/icons/` (1024x1024 minimum)
- [ ] `app_icon_foreground.png` existe dans `assets/icons/` (1024x1024)
- [ ] Les images sont en PNG avec transparence appropriée
- [ ] Le logo est centré et respecte la zone de sécurité
- [ ] Le logo est lisible sur fond clair ET foncé
- [ ] Les couleurs correspondent à la charte graphique (#00C853)
- [ ] `flutter pub get` a été exécuté
- [ ] Configuration dans `pubspec.yaml` est correcte

---

## 🔄 Commandes Utiles

```bash
# Vérifier la configuration
cat pubspec.yaml | grep -A 10 "flutter_launcher_icons"

# Nettoyer avant génération
flutter clean

# Installer les dépendances
flutter pub get

# Générer les icônes
flutter pub run flutter_launcher_icons

# Vérifier les icônes Android
find android/app/src/main/res -name "ic_launcher*"

# Vérifier les icônes iOS
ls ios/Runner/Assets.xcassets/AppIcon.appiconset/
```

---

## 🎯 Configuration Actuelle (pubspec.yaml)

```yaml
flutter_launcher_icons:
  android: true
  ios: true
  image_path: "assets/icons/app_icon.png"
  adaptive_icon_background: "#00C853"
  adaptive_icon_foreground: "assets/icons/app_icon_foreground.png"
```

---

## 📝 Notes Importantes

1. **Format Android Adaptive** :
   - Le foreground doit être **centré dans un cercle de 640px**
   - Le background est **un carré de 1024px** (ou couleur unie)
   - Android découpe l'icône selon la forme système

2. **Format iOS** :
   - Pas de transparence (fond requis)
   - Coins arrondis appliqués automatiquement par iOS
   - Taille 1024x1024 pour l'App Store

3. **Bonnes Pratiques** :
   - Éviter les textes trop petits (illisibles en 48x48)
   - Utiliser des formes simples et reconnaissables
   - Tester sur appareil réel (clair + foncé)
   - Vérifier sur différents launchers Android

---

## 🚨 Dépannage

### Erreur "image_path not found"
```bash
# Vérifier que le fichier existe
ls -la assets/icons/app_icon.png

# Créer le dossier si nécessaire
mkdir -p assets/icons
```

### Icônes non générées
```bash
# Nettoyer et régénérer
flutter clean
flutter pub get
flutter pub run flutter_launcher_icons
```

### Icônes iOS non affichées
```bash
# Ouvrir le projet dans Xcode et vérifier
open ios/Runner.xcworkspace
# Dans Xcode : Runner > Assets.xcassets > AppIcon
```

---

**© 2025 DOSSY PRO - L'IA au service du Droit**
