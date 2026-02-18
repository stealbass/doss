# ✅ CHECKLIST PRÉ-EXÉCUTION PHASES 2-6

**Avant de commencer les phases 2-6, vérifier que tout est en place**

---

## 🔧 VÉRIFICATIONS SYSTÈME

### Environment Flutter/Dart
- [ ] `flutter --version` retourne version 3.2.0+
- [ ] `dart --version` retourne version 3.2.0+
- [ ] `flutter doctor` montre aucune erreur critique
- [ ] Android SDK installé et à jour
- [ ] Java/JDK installé
- [ ] ANDROID_HOME configuré (si Linux/Mac)

### Système Fichiers
- [ ] Projet au bon répertoire: `dossy_chat_ia/`
- [ ] Espace disque > 5 GB disponible
- [ ] Dossiers `android/`, `lib/`, `pubspec.yaml` existants
- [ ] Aucun fichier verrouillé ou en utilisation

### Réseau
- [ ] Connexion Internet stable
- [ ] Pas de proxy/VPN bloquant pub.dev
- [ ] Accès à https://pub.dev possible

---

## 📁 FICHIERS DE CONFIGURATION

### Fichiers Critiques
- [ ] `pubspec.yaml` existe (version: 1.0.0+1)
- [ ] `lib/core/constants/app_constants.dart` existe (isTestMode = false)
- [ ] `android/app/src/main/AndroidManifest.xml` existe (permissions OK)
- [ ] `android/key.properties` existe avec vraies données
- [ ] `android/app/google-services.json` existe et valide

### Dossiers
- [ ] `android/` existe
- [ ] `lib/` existe avec `main.dart`
- [ ] `build/` peut être créé (espace disque OK)
- [ ] `pubspec.lock` prêt à être régénéré

---

## 🔑 CONFIGURATIONS REQUISES

### android/key.properties (CRUCIAL!)
```ini
storeFile=C:\\Users\\YourUser\\...\\dossy_release.jks
storePassword=<votre_password>
keyAlias=dossy
keyPassword=<votre_password>
```

- [ ] Fichier existe
- [ ] Mots de passe configés (pas placeholder)
- [ ] Chemin JKS valide
- [ ] Alias = "dossy"

### app_constants.dart
```dart
const bool isTestMode = false;  // PRODUCTION
const String flutterwavePublicKey = 'FLWPUBK-...';
const String baseUrl = 'https://dossypro.com/api/mobile';
```

- [ ] isTestMode = false
- [ ] URLs pointent production
- [ ] Clé Flutterwave valide

### AndroidManifest.xml Permissions
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
...
```

- [ ] INTERNET ✅
- [ ] CAMERA ✅
- [ ] READ_EXTERNAL_STORAGE ✅
- [ ] WRITE_EXTERNAL_STORAGE ✅
- [ ] READ_MEDIA_* (Android 13+) ✅

---

## 📋 PRÉPARATION ENVIRONNEMENT

### Terminal/PowerShell
- [ ] Terminal ouvert
- [ ] Current directory = dossy_chat_ia/
- [ ] Affichage du chemin confirmé
- [ ] Aucune autre commande Flutter en cours

### Configuration CLI
- [ ] `flutter config --no-analytics` (optionnel pour moins de bruit)
- [ ] `flutter config --android-studio-dir <path>` si besoin
- [ ] Certificats SSL à jour (pour pub.dev)

### Batterie/Électricité
- [ ] Batterie PC > 50%
- [ ] Idéalement: PC branché
- [ ] Écran ne s'éteindra pas (paramètres énergie)

---

## 📚 DOCUMENTATION PRÊTE

### Documents à Consulter
- [ ] `GUIDE_EXECUTION_MANUEL_PHASES.md` lu (au moins une fois)
- [ ] `README_EXECUTION_PHASES.md` consulté
- [ ] `PREPARATION_PLAYSTORE_COMPLETE.md` en référence
- [ ] Marque-page sur "DÉPANNAGE RAPIDE" du guide

### Notes Personnelles
- [ ] Bloc notes à côté (pour noter erreurs)
- [ ] Pen & papier ou éditeur texte ouvert
- [ ] Historique terminal conservé/loggé

---

## 🧪 TESTS PRÉ-PHASES

Avant Phase 2, tester que tout fonctionne:

```bash
# Test 1: Flutter OK?
flutter --version

# Test 2: Dart OK?
dart --version

# Test 3: Accès au projet OK?
cd dossy_chat_ia
pwd / cd  # Vérifier chemin correct

# Test 4: Dépendances actuelles OK?
flutter pub get
# Doit dire "Got dependencies" ou "Resolving dependencies"

# Test 5: Pas d'erreurs?
flutter analyze
# Doit dire "No issues found!" ou peu d'avertissements
```

- [ ] Test 1 réussi
- [ ] Test 2 réussi
- [ ] Test 3 réussi
- [ ] Test 4 réussi (download possible)
- [ ] Test 5 réussi (peu/pas d'erreurs)

---

## 🚨 POINTS CRITIQUES À VÉRIFIER

### AVANT Phase 2 (Nettoyage)
- [ ] Espace disque > 5 GB (Phase 2 télécharge dépendances)
- [ ] Aucun flutter build en cours
- [ ] Terminal responsif et vide

### AVANT Phase 6 (Build)
- [ ] android/key.properties valide (sinon build échouera)
- [ ] Phase 3 (analyse) a passé sans erreurs bloquantes
- [ ] Pas de modifications récentes uncommitted

### APRÈS chaque phase
- [ ] Résultat lu complètement
- [ ] Pas de message d'erreur visible
- [ ] Prêt à passer à la phase suivante

---

## ⚠️ SIGNAUX D'ALERTE

❌ **Ne pas continuer si:**

1. Terminal dit "permission denied"
   - Solution: flutter clean, réouvrir terminal en admin

2. "pub get failed"
   - Solution: Vérifier Internet, flutter pub cache repair

3. "Build failed" en Phase 6
   - Solution: Vérifier android/key.properties, google-services.json

4. Chiffres "100 % bloqué"
   - Solution: Attendre 5 min (pas toujours responsive), ou Ctrl+C et recommencer

5. Espace disque < 2 GB
   - Solution: Libérer espace, éviter Phase 6 (gros download)

**Si erreur majeure:** Consulter "DÉPANNAGE RAPIDE" dans GUIDE_EXECUTION_MANUEL_PHASES.md

---

## 📊 MATRICE DE PRÉPARATION

| Catégorie | Item | ✅ | Notes |
|-----------|------|-----|-------|
| **Système** | Flutter 3.2.0+ | [ ] | flutter --version |
| | Dart 3.2.0+ | [ ] | dart --version |
| | Espace disque > 5GB | [ ] | df -h / disk usage |
| **Fichiers** | pubspec.yaml (1.0.0+1) | [ ] | Existe? Version OK? |
| | app_constants.dart (isTestMode=false) | [ ] | Consulter ligne 30 |
| | AndroidManifest.xml (permissions) | [ ] | Toutes présentes? |
| | key.properties (données réelles) | [ ] | Mots de passe OK? |
| | google-services.json | [ ] | Production? Valide? |
| **Réseau** | Internet stable | [ ] | Ping google.com |
| | Accès pub.dev | [ ] | Flutter pub get test |
| **Docs** | GUIDE_EXECUTION_MANUEL_PHASES.md | [ ] | Lu? |
| | PREPARATION_PLAYSTORE_COMPLETE.md | [ ] | Référence dispo? |
| **Env** | Terminal au bon répertoire | [ ] | dossy_chat_ia/ |
| | Batterie PC > 50% | [ ] | Branché idéalement |
| | Aucune autre Flutter en cours | [ ] | ps aux / tasklist |

---

## ✅ SUIS-JE PRÊT(E)?

### Oui si:
- ✅ 20+ cases cochées ci-dessus
- ✅ Aucun message d'erreur en Test 1-5
- ✅ Documentation à proximité
- ✅ Terminal prêt
- ✅ Batterie OK

### Non si:
- ❌ Moins de 15 cases cochées
- ❌ Erreurs en Test 1-5
- ❌ Espace disque < 3 GB
- ❌ Internet instable
- ❌ android/key.properties missing

**Si non:** Corriger les problèmes avant de continuer (ne pas sauter!)

---

## 🚀 PRÊT À COMMENCER?

Si **TOUS** les checks sont ✅:

1. **Ouvrir:** GUIDE_EXECUTION_MANUEL_PHASES.md
2. **Aller à:** PHASE 2 - NETTOYAGE ET DÉPENDANCES
3. **Copier-coller:** Première commande `flutter clean`
4. **Attendre:** Résultat
5. **Continuer:** Avec PHASE 3...

---

## 📞 AU CAS OÙ

**Erreur simple?** → Section DÉPANNAGE RAPIDE du GUIDE  
**Erreur complexe?** → Lire PREPARATION_PLAYSTORE_COMPLETE.md (section correspondante)  
**Blocage total?** → Vérifier cette checklist une nouvelle fois

---

**Status:** Si tout est coché → 🟢 **VOUS ÊTES PRÊT(E)!**

Prochaine étape: [Ouvrir GUIDE_EXECUTION_MANUEL_PHASES.md](GUIDE_EXECUTION_MANUEL_PHASES.md)

---

*Checklist mise à jour: 28 Janvier 2026*  
*Application: DOSSY Chat IA*  
*Versions: Flutter 3.2.0+, Dart 3.2.0+, Android SDK 36*
