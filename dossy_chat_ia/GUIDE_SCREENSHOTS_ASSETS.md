# 📸 GUIDE COMPLET - SCREENSHOTS ET ASSETS POUR PLAY STORE

## 🎯 Vue d'ensemble Assets Play Store

Pour soumettre DOSSY Chat IA sur Google Play Store, vous avez besoin de plusieurs assets graphiques. Voici le guide complet.

---

## 📷 SCREENSHOTS (Obligatoire)

### Spécifications Techniques

| Élément | Dimension | Format | Notes |
|---------|-----------|--------|-------|
| **Phone** | 1080x1920 | PNG/JPG | Minimum 2, Maximum 8 |
| **Tablette 7"** | 1200x1920 | PNG/JPG | Optionnel |
| **Tablette 10"** | 1600x2560 | PNG/JPG | Optionnel |

### Recommandations

- **Taille fichier:** < 10 MB par image
- **Texte:** Visible et lisible (taille 14+ sp)
- **Sans UI système:** Masquer barre status, navigation bar
- **Cohérent:** Même style, même polices
- **Attrayant:** Montrer les features principales

### Écrans à Capturer (Recommandé: 5-6 images)

#### 1️⃣ **Écran de Bienvenue / Login**
```
Montre:
- Nom app "DOSSY CHAT IA"
- Slogan "Analyse et Assistant Juridique"
- Boutons Login/Register
- Logo prominent
```

#### 2️⃣ **Écran d'Accueil**
```
Montre:
- Layout principal
- Boutons de recherche
- Catégories juridiques
- Plans d'abonnement visibles
```

#### 3️⃣ **Recherche Avancée**
```
Montre:
- Champ de recherche
- Filtres par pays
- Résultats retournés
- Pertinence des résultats
```

#### 4️⃣ **Visualisation PDF/Document**
```
Montre:
- Document chargé
- Interface de lecture
- Annotations visibles
- Boutons d'action
```

#### 5️⃣ **Chat IA**
```
Montre:
- Conversation avec IA
- Messages utilisateur
- Réponses IA intelligentes
- Interface chat clean
```

#### 6️⃣ **Plans d'Abonnement**
```
Montre:
- 4 plans: Gratuit / Étudiant / Pro / Cabinet
- Prix clairs
- Features différenciation
- Boutons "S'abonner"
```

### Outil de Capture

**Sous Windows via Android Studio ou ADB:**

```bash
# Connecter device via USB (Developer Mode activé)
adb devices

# Prendre une screenshot
adb shell screencap -p /sdcard/screenshot.png

# Récupérer sur PC
adb pull /sdcard/screenshot.png .

# Recadrer à 1080x1920 avec ImageMagick:
magick convert screenshot.png -crop 1080x1920+0+0 +repage screenshot_cropped.png
```

**Ou utiliser Flutter DevTools:**
```bash
flutter pub global activate devtools
devtools
# Puis utiliser le plugin screenshot dans VS Code
```

---

## 🎨 ICÔNE APPLICATION (Obligatoire)

### Spécifications

| Élément | Taille | Format | Notes |
|---------|--------|--------|-------|
| **Store Icon** | 512x512 | PNG 32-bit | Requis pour Play Store |
| **App Icon** | 192x192 | PNG 32-bit | Adaptive icon (Android 8+) |
| **Adaptive Foreground** | 192x192 | PNG 32-bit | Sans fonds carrés |

### Conception Recommandée

```
512x512 Icon Design:
- Logo DOSSY (vert #00A86B) au centre
- Fond blanc ou gradient léger
- Sans texte (lisible à petite taille)
- Coin arrondi ou shape cohérente
- Spacing: Marge 20% du côté
```

### Checklist Icon

- [ ] Logo propre et reconnaissable
- [ ] Couleur primaire: #00A86B (vert DOSSY)
- [ ] Pas de dégradé complexe
- [ ] Compatible avec adaptive icon (Android 8+)
- [ ] Testé à 192x192 (taille minimale)
- [ ] Fichier PNG 32-bit transparent

### Placement

```
assets/
├── icons/
│   ├── app_icon.png (512x512)
│   ├── app_icon_foreground.png (192x192 - optionnel)
│   └── app_icon_background.png (192x192 - optionnel)
└── ...
```

---

## 🎆 FEATURE GRAPHIC (Hautement Recommandé)

### Spécifications

| Élément | Dimension | Format | Notes |
|---------|-----------|--------|-------|
| **Feature Graphic** | 1024x500 | PNG/JPG | Bannière Play Store |

### Contenu Recommandé

```
Aspect Design:
- Côté gauche: Logo/Image
- Côté droit: Texte descriptif
- Hauteur totale: 500px
- Largeur totale: 1024px

Texte suggéré:
- "DOSSY CHAT IA"
- "Assistant Juridique IA"
- "14 Pays d'Afrique"

Couleurs:
- Fond: Blanc ou gradient léger
- Accents: Vert #00A86B
- Texte: Noir/Gris foncé
```

### Placement

```
Dans Play Console:
Graphics > Feature graphic
Uploader le fichier 1024x500 PNG
```

---

## 📋 GRAPHIQUES TABLETTE (Optionnel)

Si vous supportez les tablettes, les dimensions:

```
Tablette 7"  (1200x1920)
- Screenshot ou contenu adapté
- Même style que phone

Tablette 10" (1600x2560)
- Screenshot ou contenu adapté
- Layout optimisé pour grand écran
```

---

## 🖼️ AUTRES GRAPHIQUES (Optionnel)

### Icône Promo (Optionnel)
```
Dimension: 512x512
Format: PNG 32-bit
Usage: Promotion interne Play Store
```

### Vidéo Promo (Optionnel)
```
Format: MP4, WebM
Résolution: 1280x720 ou plus
Durée: 15-30 secondes
Contenu: Démonstration features
Upload: Via YouTube ou Play Console
```

---

## 🔧 PROCESSUS CRÉATION ASSETS

### Étape 1: Installer les Dependencies

```bash
cd dossy_chat_ia

# Flutter Launcher Icons
flutter pub add flutter_launcher_icons --dev

# Image Magick (pour redimensionner)
# Windows: choco install imagemagick
# macOS: brew install imagemagick
# Linux: sudo apt-get install imagemagick
```

### Étape 2: Préparer Image Maître (512x512)

1. Créer/Récupérer logo DOSSY en haute résolution
2. Placer dans `assets/icons/app_icon_master.png`
3. Éditer avec Photoshop, GIMP, ou Figma
4. Exporter en 512x512 PNG 32-bit transparent

### Étape 3: Générer Variantes

**Utiliser ImageMagick:**

```bash
# Redimensionner 512x512 en 192x192
magick convert assets/icons/app_icon_master.png -resize 192x192 assets/icons/app_icon.png

# Créer version foreground (sans bg)
magick convert assets/icons/app_icon_master.png -trim assets/icons/app_icon_foreground.png
```

**Ou utiliser flutter_launcher_icons:**

```yaml
# pubspec.yaml
dev_dependencies:
  flutter_launcher_icons: "^0.13.1"

flutter_launcher_icons:
  android: "ic_launcher"
  image_path: "assets/icons/app_icon.png"
  min_sdk_android: 21
```

Générer:
```bash
flutter pub run flutter_launcher_icons:main
```

### Étape 4: Capturer Screenshots

**Sous Android via Flutter:**

```bash
# Mode hot reload
flutter run -v

# Puis prendre screenshot via VS Code ou Android Studio
```

**Ou via script Python (automatisé):**

```python
import subprocess
import os

def take_screenshot(filename):
    os.system(f"adb shell screencap -p /sdcard/{filename}.png")
    os.system(f"adb pull /sdcard/{filename}.png .")
    print(f"Screenshot sauvé: {filename}.png")

# Prendre 6 screenshots
for i in range(1, 7):
    take_screenshot(f"screenshot_{i}")
```

### Étape 5: Optimiser Images

**Réduire taille fichier:**

```bash
# Avec ImageMagick
magick convert screenshot_1.png -quality 85 -strip screenshot_1_optimized.png

# Ou avec ffmpeg
ffmpeg -i screenshot_1.png -q:v 5 screenshot_1_optimized.png
```

### Étape 6: Organiser Fichiers

```
assets_playstore/
├── screenshots/
│   ├── screenshot_1_login.png (1080x1920)
│   ├── screenshot_2_accueil.png (1080x1920)
│   ├── screenshot_3_recherche.png (1080x1920)
│   ├── screenshot_4_document.png (1080x1920)
│   ├── screenshot_5_chat.png (1080x1920)
│   └── screenshot_6_plans.png (1080x1920)
├── icons/
│   └── app_icon_512.png (512x512)
└── graphics/
    └── feature_graphic.png (1024x500)
```

---

## 🚀 UPLOAD SUR PLAY CONSOLE

### Procédure

1. **Aller sur:** https://play.google.com/console/
2. **Sélectionner application:** DOSSY CHAT IA
3. **Navigation:** Mise en production > Créer version
4. **Upload AAB:** `build/app/outputs/bundle/release/app-release.aab`
5. **Attendre validation:** 5-30 min

### Remplir Fiche Store

**Menu: Fiche de l'App > Nom et description**

```
- Nom app (50 car max): DOSSY Chat IA - Assistant Juridique IA
- Description courte (80 car): Assistant juridique IA pour l'Afrique francophone
- Description (4000 car): [Voir PREPARATION_PLAYSTORE_COMPLETE.md]
```

**Menu: Fiche de l'App > Graphiques**

- **Screenshots:** Upload 5-8 images 1080x1920
- **Icône app:** Upload 512x512 PNG
- **Feature graphic:** Upload 1024x500 PNG (optionnel)

**Menu: Fiche de l'App > Catégorie**

- Catégorie primaire: **Éducation**
- Catégorie secondaire: **Productivité** (optionnel)

**Menu: Fiche de l'App > Évaluation du contenu**

- Cliquer "Remplir le questionnaire"
- Répondre aux questions Play Store
- Valider classification

**Menu: Fiche de l'App > URLs supplémentaires**

```
- Site web: https://dossypro.com
- Email support: contact@dossypro.com
- Politique confidentialité: https://dossypro.com/privacy
- Conditions utilisation: https://dossypro.com/pages/conditions_générales_d'utilisation
```

---

## ✅ CHECKLIST FINAL

Avant de cliquer "Déployer":

### Assets
- [ ] Screenshots 5-8 images (1080x1920)
- [ ] Icon app 512x512 PNG
- [ ] Feature graphic 1024x500 PNG (optionnel)
- [ ] Tous fichiers < 10 MB
- [ ] Format PNG ou JPG (pas BMP/GIF)

### Fiche Store
- [ ] Titre complet et accrocheur
- [ ] Description courte (80 car)
- [ ] Description longue (4000 car) attrayante
- [ ] Catégorie: Éducation
- [ ] Mots-clés pertinents (14 countries, AI, Juridique)

### URL & Liens
- [ ] Site web valide
- [ ] Email support correct
- [ ] Privacy policy URL
- [ ] Terms of service URL

### Classification Contenu
- [ ] Questionnaire rempli
- [ ] Classification valide
- [ ] Pas de contenus bloqués

### Build
- [ ] AAB générée sans erreur
- [ ] Taille < 100 MB
- [ ] isTestMode = false
- [ ] APIs pointent production

---

## 🎯 EXEMPLE TEXTES PLAY STORE

### Titre (50 caractères)
```
DOSSY Chat IA - Assistant Juridique IA
```

### Description Courte (80 caractères)
```
Assistant juridique IA pour 14 pays africains - Recherche, Analyse, Conseil
```

### Description Longue (4000 caractères)

[Voir PREPARATION_PLAYSTORE_COMPLETE.md - Ligne "Description Longue (4000 caractères)"]

### Mot-clés (100 caractères)
```
juridique, avocat, consultation, IA, Afrique, droit, documents, chat, assistant
```

---

## 📞 SUPPORT & RESSOURCES

**Google Play Console Documentation:**
https://support.google.com/googleplay/android-developer

**Flutter Best Practices:**
https://docs.flutter.dev/deployment/android

**Play Store Graphics Guidelines:**
https://support.google.com/googleplay/android-developer/answer/1078870

**Timeframe Soumission:**
- ⏱️ Première soumission: 2-4 heures
- ⏱️ Mises à jour: 30 min - 2h
- ⏱️ Rejets: Généralement < 1h

---

**Document préparé:** 28 Janvier 2026  
**Pour:** Soumission DOSSY Chat IA sur Google Play Store  
**Status:** 🟢 Prêt pour Production
