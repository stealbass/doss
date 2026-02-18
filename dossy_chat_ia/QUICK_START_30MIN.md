# 🚀 QUICK START - DÉPLOYER EN 30 MINUTES

**Pour les utilisateurs en hurry! Voici le minimum absolu.**

---

## ⏱️ TIMELINE: 30 MIN

```
0-5 min:   Cloner/vérifier projet
5-10 min:  Vérifier configs
10-20 min: Générer clés & build
20-30 min: Uploader sur Play Console
```

---

## 📝 CHECKLIST RAPIDE

### ✅ Code Flutter - DÉJÀ FAIT

- ✅ `isTestMode = false` 
- ✅ URLs pointent `https://dossypro.com/api/mobile`
- ✅ Terms/Privacy links fonctionnels
- ✅ Mobile role bug fixé

**Rien à changer côté code!**

### ⚙️ Configuration à Faire (5 min)

**1. Ouvrir `lib/core/constants/app_constants.dart`**

Chercher ligne ~28:
```dart
// À trouver
static const String flutterwavePublicKey = 'FLWPUBK_TEST-XXXXXXXXXXXXX-X';
static const bool isTestMode = true;
```

Vérifier c'est changé à:
```dart
// ✅ Déjà fait si tu lis ce message
static const bool isTestMode = false;
```

**À FAIRE: Remplacer la clé Flutterwave**

2. Aller sur https://dashboard.flutterwave.com/
3. Copier la vraie clé production
4. Remplacer `FLWPUBK_TEST-XXXXXXXXXXXXX-X` par celle-ci

**2. Vérifier `android/app/google-services.json` existe**

```bash
# Terminal
ls android/app/google-services.json
```

Si **NOT FOUND**:
1. Télécharger depuis https://console.firebase.google.com/
2. Placer dans `android/app/`

**3. Créer `android/key.properties`**

Si pas trouvé, créer avec vos vrais mots de passe:

```properties
storePassword=MON_MOT_DE_PASSE_KEYSTORE
keyPassword=MON_MOT_DE_PASSE_CLÉ
keyAlias=upload
storeFile=upload-keystore.jks
```

**⚠️ IMPORTANT:** Les mots de passe doivent être générés avec:

```bash
# Windows: Ouvrir PowerShell et exécuter
keytool -genkey -v -keystore upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

---

## 🏗️ BUILD RELEASE (5-10 min)

### Option 1: PowerShell (Recommandé pour Windows)

```powershell
# Ouvrir PowerShell dans le projet
# Naviguer vers dossy_chat_ia/

# Run le script de build
.\prepare_playstore.ps1
```

### Option 2: Terminal classique

```bash
# Terminal
flutter clean
flutter pub get
flutter build appbundle --release --obfuscate --split-debug-info=build/app/outputs/symbols
```

### ✅ Résultat

Fichier généré:
```
build/app/outputs/bundle/release/app-release.aab
```

**Copier ce chemin, vous en aurez besoin!**

---

## 📤 UPLOAD PLAY STORE (10 min)

### Procédure Simple

1. **Aller sur:** https://play.google.com/console/
2. **Login** avec compte Google
3. **Créer app** si première fois
   - Cliquer "Créer app"
   - Nom: `DOSSY Chat IA - Assistant Juridique IA`
   - Sélectionner "Éducation"
   - Suivre wizard
4. **Ou sélectionner app** existante

### Remplir Fiche App (5 min)

Gauche > Mise en production > Créer version

**1. Upload AAB:**
```
Cliquer "Browse files"
Sélectionner: build/app/outputs/bundle/release/app-release.aab
Attendre validation (5-30 sec)
```

**2. Notes de version:**
```
V1.0.0 🚀

✨ Lancement initial de DOSSY Chat IA
- Recherche juridique intelligente
- Chat avec assistant IA
- 100,000+ documents
- 14 pays africains
- Plans d'abonnement
- Paiement mobile money

Merci de télécharger! 🇸🇳🇨🇮🇲🇦
```

**3. Cliquer "Examiner"**

### Remplir Informations App (5 min)

Gauche > Fiche de l'App

**Onglet: Nom et description**

```
Nom: DOSSY Chat IA - Assistant Juridique IA
Description courte: Assistant juridique IA pour Afrique francophone
Description: [copier depuis PREPARATION_PLAYSTORE_COMPLETE.md]
```

**Onglet: Graphiques**

```
- Importer 5-8 screenshots (1080x1920)
- Importer icon 512x512
- (Optionnel) Feature graphic 1024x500
```

**Onglet: Catégorie & Contenu**

```
Catégorie: Éducation
Classifier le contenu: Répondre au questionnaire
```

**Onglet: URLs supplémentaires**

```
Site web: https://dossypro.com
Email: contact@dossypro.com
Privacy: https://dossypro.com/privacy
Terms: https://dossypro.com/pages/conditions_générales_d'utilisation
```

### Déployer (1 min)

**Version > Cliquer "Déployer" en haut à droite**

```
Confirmez: OUI
Attendre: Quelques heures d'examen
Status: 🟢 En production
```

---

## ✅ C'EST FAIT!

Votre app est maintenant:
- ✅ Compilée pour production
- ✅ Signée avec votre keystore
- ✅ Uploadée sur Play Console
- ✅ En attente d'examen Google (2-4 heures)

### Monitoring Après Soumission

1. **Vérifier status:** Play Console > Mise en production
2. **Attendre approbation:** 2-4 heures généralement
3. **Si rejet:** Vérifier email avec raison
4. **Si approuvée:** 🎉 App en ligne!

---

## 🆘 ERREURS COURANTES RAPIDE

### ❌ Build échoue: "key.properties not found"
```bash
# Solution: Créer android/key.properties avec vos mots de passe
# Voir section "Configuration à Faire" au-dessus
```

### ❌ Erreur: "AAB trop volumineux"
```bash
# Doit être < 100 MB généralement
# Si > 100MB: vérifier assets folder trop gros
```

### ❌ Play Console: "Fichier invalide"
```bash
# Vérifier le fichier uploadé est .aab (pas .apk)
# Vérifier version plus haute que précédente
# Vérifier min SDK (API 21)
```

### ❌ App rejouée: "Permissões inválidas"
```bash
# Solution: Vérifier Android manifest permissions
# Voir CONFIGURATIONS_PRODUCTION.md
```

---

## 📞 RESSOURCES RAPIDES

| Besoin | Lien |
|--------|------|
| Play Console | https://play.google.com/console/ |
| Firebase Console | https://console.firebase.google.com/ |
| Flutterwave Dashboard | https://dashboard.flutterwave.com/ |
| Flutter Docs | https://docs.flutter.dev/deployment/android |
| Keytool Help | `keytool -help` dans terminal |

---

## 🎯 PROCHAINES ÉTAPES (APRÈS SOUMISSION)

1. **Marketing (semaine 1)**
   - Annoncer sur réseaux sociaux
   - Email à utilisateurs bêta
   - Faire trending si possible

2. **Monitoring (semaine 1-2)**
   - Vérifier crashes dans Play Console
   - Répondre aux avis
   - Fixer bugs urgents

3. **Updates (semaine 2+)**
   - Version 1.0.1 avec petites corrections
   - Améliorer d'après feedback
   - Nouveaux features

---

## ✨ BRAVO! 🎉

**App déployée avec succès sur Google Play Store!**

Retrouve l'app ici quand elle est approuvée:
```
https://play.google.com/store/apps/details?id=com.dossypro.dossy_chat_ia
```

**Questions?** Voir les guides détaillés:
- `PREPARATION_PLAYSTORE_COMPLETE.md` - Guide complet
- `CONFIGURATIONS_PRODUCTION.md` - Configurations avancées
- `GUIDE_SCREENSHOTS_ASSETS.md` - Assets & graphiques

---

**Status:** ✅ **30 MIN COUNTDOWN - GO!**
