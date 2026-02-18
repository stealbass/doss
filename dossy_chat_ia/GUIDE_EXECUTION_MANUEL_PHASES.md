# 🎯 GUIDE D'EXÉCUTION MANUEL - PHASES 2 À 5

**Application:** DOSSY Chat IA  
**Date:** 28 Janvier 2026  
**Statut:** 🔄 À EXÉCUTER PAR L'UTILISATEUR

---

## 📋 ÉTAPES À EXÉCUTER DANS L'ORDRE

### ✅ PHASE DÉJÀ COMPLÉTÉE: Vérifications Critiques

**STATUS:**
- ✅ Version pubspec.yaml: **1.0.0+1** ✓
- ✅ isTestMode: **false** ✓
- ✅ URL Backend: **https://dossypro.com/api/mobile** ✓
- ✅ Permissions Android: **TOUTES PRÉSENTES** ✓

---

## 🔄 PHASES À EXÉCUTER MAINTENANT

### **PHASE 2: NETTOYAGE ET DÉPENDANCES** (10-15 min)

**Ouvrir terminal** et naviguer au projet:
```powershell
cd "c:\Users\Lenovo T580\Downloads\doss-genspark_ai_developer_5\doss-genspark_ai_developer\dossy_chat_ia"
```

**Exécuter les commandes** une par une:

**2.1 - Nettoyage**
```bash
flutter clean
```
✅ Attendez: "Build cache cleaned."

**2.2 - Récupérer dépendances**
```bash
flutter pub get
```
✅ Attendez: "packages resolved in X seconds."

**2.3 - Mettre à jour dépendances**
```bash
flutter pub upgrade
```
✅ Attendez: "Upgraded X packages."

**Résultat attendu après PHASE 2:**
```
✅ Cache nettoyé
✅ Dépendances récupérées
✅ Dépendances mises à jour
```

---

### **PHASE 3: ANALYSE STATIQUE** (5-10 min)

**3.1 - Analyse Flutter**
```bash
flutter analyze
```

**Résultat attendu:**
```
✅ No issues found! (Zero errors)
```

Ou si avertissements:
```
⚠️ 5 issues found. (see the line numbers below)
```

**3.2 - Analyse Dart**
```bash
dart analyze
```

**Résultat attendu:**
```
✅ No issues found!
```

---

### **PHASE 4: GÉNÉRATION ICONS** (2-5 min)

**4.1 - Générer les icons**
```bash
flutter pub run flutter_launcher_icons:main
```

**Résultat attendu:**
```
✅ Successfully generated launcher icons
```

Ou:
```
⚠️ flutter_launcher_icons not configured
(Cela ne bloque pas, les icons existent déjà)
```

---

### **PHASE 5: TESTS PRÉ-RELEASE** (5-15 min)

**5.1 - Exécuter les tests**
```bash
flutter test
```

**Résultat attendu:**
```
✅ X tests passed
✅ All tests passed
```

Ou si pas de tests:
```
No tests found in test/ directory.
```

---

### **PHASE 6: BUILD AAB RELEASE** (10-20 min - LE PLUS IMPORTANT)

**⚠️ AVANT CETTE ÉTAPE - Vérifier:**
- [ ] android/key.properties existe avec mots de passe
- [ ] flutter analyze sans erreurs critiques
- [ ] isTestMode = false
- [ ] Flutterwave clé production

**6.1 - Build AAB release**
```bash
flutter build appbundle --release --obfuscate --split-debug-info=build/app/outputs/symbols
```

**Cela va prendre 5-15 minutes** - Ne pas interrompre!

**Résultat attendu:**
```
✅ Target aab_bundle_release
✅ Building AppBundle
✅ Built build/app/outputs/bundle/release/app-release.aab (X MB)
```

**6.2 - Vérifier le fichier généré**
```bash
ls -lh build/app/outputs/bundle/release/app-release.aab
```

**Résultat attendu:**
```
-rw-r--r--  1 user  staff  35.5M  app-release.aab
(Taille: généralement 30-50 MB, doit être < 100 MB)
```

---

## 📊 RÉSUMÉ DES PHASES

| Phase | Commande | Durée | Status |
|-------|----------|-------|--------|
| **1** | Vérifications | ✅ | COMPLÉTÉE |
| **2** | flutter clean/pub get/upgrade | 10-15 min | À FAIRE |
| **3** | flutter analyze | 5-10 min | À FAIRE |
| **4** | flutter_launcher_icons | 2-5 min | À FAIRE |
| **5** | flutter test | 5-15 min | À FAIRE |
| **6** | flutter build appbundle | 10-20 min | À FAIRE |
| **TOTAL** | Phases 2-6 | **40-70 min** | À FAIRE |

---

## ✅ CHECKLIST D'EXÉCUTION

**Avant Phase 2:**
- [ ] Terminal ouvert au bon répertoire
- [ ] Aucune autre commande Flutter en cours
- [ ] Internet stable

**Après Phase 2:**
- [ ] Aucune erreur de dépendances

**Après Phase 3:**
- [ ] flutter analyze sans erreurs critiques

**Après Phase 4:**
- [ ] Icons générées (ou avertissement acceptable)

**Après Phase 5:**
- [ ] Tests passent (ou pas de tests = OK)

**Après Phase 6:**
- [ ] Fichier app-release.aab généré
- [ ] Taille < 100 MB
- [ ] Aucune erreur de compilation

---

## 🆘 DÉPANNAGE RAPIDE

### ❌ "flutter clean" échoue
```
Relancer la commande
flutter clean
```

### ❌ "No packages found in pubspec.yaml"
```
Vérifier pubspec.yaml existe
Relancer: flutter pub get
```

### ❌ "flutter analyze" montre trop d'erreurs
```
Vérifier: flutter analyze | head -20
Lire les erreurs critiques
Consulter la documentation
```

### ❌ "flutter build appbundle" échoue

**Cause 1: key.properties manquant**
```
Vérifier: ls android/key.properties
Si absent: créer avec mots de passe
```

**Cause 2: build.gradle incompatible**
```
Vérifier targetSdk = 36 dans android/app/build.gradle
```

**Cause 3: Manque de espace disque**
```
Nettoyer disque
Relancer build
```

---

## 📈 PROGRESSION

```
┌─────────────────────────────────────┐
│ PHASES À EXÉCUTER                   │
├─────────────────────────────────────┤
│ Phase 2: Nettoyage      [         ] │
│ Phase 3: Analyse        [         ] │
│ Phase 4: Icons          [         ] │
│ Phase 5: Tests          [         ] │
│ Phase 6: Build AAB      [         ] │
├─────────────────────────────────────┤
│ TOTAL:                  [         ] │
└─────────────────────────────────────┘

Durée estimée: 40-70 minutes
Status: À FAIRE
```

---

## 🎯 OBJECTIF FINAL

**Après exécution de toutes les phases:**

```
✅ build/app/outputs/bundle/release/app-release.aab
   (Prête pour upload Play Store si nécessaire)

✅ Zéro erreurs critiques
✅ Code optimisé et obfusqué
✅ Debug symbols séparés
✅ Prêt pour production
```

---

## 📝 NOTES IMPORTANTES

1. **Ne pas interrompre Phase 6** - Build peut prendre 20 min
2. **Garder terminal ouvert** - Voir les logs en temps réel
3. **En cas d'erreur** - Relancer depuis dernière étape complétée
4. **Si tout échoue** - Exécuter: `flutter clean` puis recommencer

---

## 🚀 PROCHAINES ÉTAPES APRÈS

Une fois Phase 6 complétée:

1. ✅ AAB est prêt
2. ✅ Optionnel: Préparer screenshots (si déploiement prévu)
3. ✅ Optionnel: Upload Play Console

---

**Commence par PHASE 2 maintenant!** 🚀

*Exécution estimée: 40-70 minutes*  
*Difficulté: Facile (copier-coller les commandes)*
