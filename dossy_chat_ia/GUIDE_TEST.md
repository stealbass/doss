# 🧪 GUIDE DE TEST - SÉCURITÉ & PERFORMANCE

## ✅ TESTS À EFFECTUER

### 1. 🔐 TEST DE SÉCURITÉ - STOCKAGE DES TOKENS

#### Test 1.1: Vérification du stockage sécurisé (Android)

**Prérequis:**
- Appareil Android connecté via USB avec ADB activé
- Application installée en mode debug

**Étapes:**
```bash
# 1. Lancer l'application
flutter run

# 2. Se connecter avec un compte test
# Email: test@example.com
# Password: test123

# 3. Vérifier le stockage avec ADB
adb shell
cd /data/data/com.dossy.dossy_chat_ia/files
ls -la

# 4. Rechercher flutter_secure_storage
find . -name "*flutter_secure_storage*"
```

**Résultat attendu:**
- ✅ Dossier `flutter_secure_storage` existe
- ✅ Fichiers avec noms cryptiques (ex: `8a7f3b9c2d1e4f5g`)
- ✅ Contenu illisible (chiffré)
- ❌ PAS de fichier contenant "token" en clair

**Comparaison AVANT/APRÈS:**

AVANT (SharedPreferences):
```
/data/data/com.dossy.dossy_chat_ia/shared_prefs/FlutterSharedPreferences.xml
Contenu visible:
<string name="flutter.auth_token">eyJ0eXAiOiJKV1QiLCJh...</string>
```

APRÈS (FlutterSecureStorage):
```
/data/data/com.dossy.dossy_chat_ia/files/flutter_secure_storage/8a7f3b9c
Contenu chiffré: �x�L▒��▒�▒��▒�▒��▒
```

---

#### Test 1.2: Test de validation des entrées

**Test Login:**
```dart
// Test 1: Email invalide
Email: "test"
Password: "123456"
Résultat attendu: ❌ "Adresse email invalide"

// Test 2: Mot de passe trop court
Email: "test@example.com"
Password: "123"
Résultat attendu: ❌ "Le mot de passe doit contenir au moins 6 caractères"

// Test 3: Valide
Email: "test@example.com"
Password: "test123"
Résultat attendu: ✅ Connexion réussie
```

**Test Register:**
```dart
// Test 1: Nom trop court
Name: "Te"
Résultat attendu: ❌ "Le nom doit contenir au moins 3 caractères"

// Test 2: Email invalide
Email: "test@"
Résultat attendu: ❌ "Adresse email invalide"

// Test 3: Mots de passe différents
Password: "test123"
Confirmation: "test456"
Résultat attendu: ❌ "Les mots de passe ne correspondent pas"

// Test 4: Téléphone invalide
Phone: "123"
Résultat attendu: ❌ "Numéro de téléphone invalide"

// Test 5: Valide
Name: "Test User"
Email: "test@example.com"
Password: "test123"
Confirmation: "test123"
Phone: "+221771234567"
Résultat attendu: ✅ Inscription réussie
```

---

### 2. ⚡ TEST DE PERFORMANCE - IMAGES

#### Test 2.1: Mesure du temps de chargement

**Prérequis:**
- Connexion internet lente simulée (4G/3G)
- Application lancée

**Méthode de test:**
```dart
// Ajouter dans le code temporairement:
final stopwatch = Stopwatch()..start();
ImageService.cachedImage(imageUrl);
print('⏱️ Temps de chargement: ${stopwatch.elapsedMilliseconds}ms');
```

**Résultats attendus:**

| Scénario | AVANT (Image.network) | APRÈS (ImageService) | Amélioration |
|----------|----------------------|---------------------|-------------|
| Premier chargement | 2000-5000ms | 2000-5000ms | 0% (normal) |
| Deuxième affichage | 2000-5000ms | 100-300ms | 95% |
| Offline (cache) | ❌ Erreur | 50-100ms | 100% |

---

#### Test 2.2: Utilisation de la mémoire

**Avec Flutter DevTools:**
```bash
# 1. Lancer l'app en mode debug
flutter run

# 2. Ouvrir DevTools
flutter pub global activate devtools
flutter pub global run devtools

# 3. Naviguer vers Performance > Memory
# 4. Scroller plusieurs fois dans une liste avec images
# 5. Observer la consommation mémoire
```

**Résultats attendus:**
- ✅ Mémoire stable (pas d'augmentation continue)
- ✅ Pas de memory leaks
- ✅ Cache limité à 100MB max

---

#### Test 2.3: Bande passante réseau

**Avec Flutter DevTools:**
```bash
# 1. Ouvrir DevTools > Network
# 2. Scroller dans une liste avec 10 images
# 3. Revenir en arrière et re-scroller
# 4. Compter les requêtes HTTP
```

**Résultats attendus:**

| Scénario | Requêtes AVANT | Requêtes APRÈS | Économie |
|----------|---------------|---------------|---------|
| Premier scroll | 10 | 10 | 0% |
| Deuxième scroll | 10 | 0 | 100% |
| Troisième scroll | 10 | 0 | 100% |

---

### 3. 🔒 TEST DE SÉCURITÉ AVANCÉ

#### Test 3.1: Reverse Engineering

**Avec APK Analyzer:**
```bash
# 1. Build APK release
flutter build apk --release

# 2. Analyser avec jadx-gui
jadx-gui build/app/outputs/flutter-apk/app-release.apk

# 3. Rechercher "token" dans le code décompilé
```

**Résultat attendu:**
- ❌ Pas de token en dur dans le code
- ❌ Pas de clés API visibles
- ✅ Code obfusqué (si ProGuard activé)

---

#### Test 3.2: Test de persistence

**Scénario:**
1. Se connecter à l'app
2. Fermer l'app complètement
3. Redémarrer l'app
4. Vérifier si on est toujours connecté

**Résultat attendu:**
- ✅ Utilisateur toujours connecté
- ✅ Token chargé depuis secure storage
- ✅ Pas besoin de se reconnecter

---

#### Test 3.3: Test de déconnexion

**Scénario:**
1. Se connecter
2. Vérifier le stockage (token présent)
3. Se déconnecter
4. Vérifier le stockage (token supprimé)

**Commandes ADB:**
```bash
# Après connexion
adb shell
cd /data/data/com.dossy.dossy_chat_ia/files/flutter_secure_storage
ls -la  # Doit voir des fichiers

# Après déconnexion
ls -la  # Doit être vide ou fichiers supprimés
```

---

### 4. 📊 BENCHMARK COMPLET

#### Test 4.1: Temps de démarrage

```bash
# Mesurer le temps de cold start
adb shell am force-stop com.dossy.dossy_chat_ia
adb shell am start -W -n com.dossy.dossy_chat_ia/.MainActivity
```

**Résultats attendus:**
| Métriques | Cible | Max acceptable |
|-----------|-------|---------------|
| TotalTime | < 2s | 3s |
| WaitTime | < 2.5s | 4s |

---

#### Test 4.2: FPS (Frames per Second)

**Avec Flutter Performance Overlay:**
```dart
// Dans main.dart:
MaterialApp(
  showPerformanceOverlay: true,
  ...
)
```

**Résultat attendu:**
- ✅ FPS stable à 60
- ✅ Pas de jank (frame drops)
- ✅ GPU/UI threads < 16ms

---

### 5. 🧪 TESTS AUTOMATISÉS

#### Test 5.1: Tests unitaires (auth_provider)

```dart
// test/providers/auth_provider_test.dart
void main() {
  test('Login avec email invalide doit échouer', () async {
    final provider = AuthProvider();
    final result = await provider.login(
      email: 'invalide',
      password: 'test123',
    );
    expect(result, false);
    expect(provider.error, 'Adresse email invalide');
  });
  
  test('Login avec mot de passe court doit échouer', () async {
    final provider = AuthProvider();
    final result = await provider.login(
      email: 'test@example.com',
      password: '123',
    );
    expect(result, false);
    expect(provider.error, contains('6 caractères'));
  });
}
```

**Exécuter:**
```bash
flutter test test/providers/auth_provider_test.dart
```

---

### 6. 📱 TESTS MULTI-PLATEFORMES

#### iOS (iPhone/iPad)
```bash
flutter run -d <iPhone_ID>
# Tester:
# - Keychain storage
# - Face ID / Touch ID
# - Permissions photos
```

#### Android
```bash
flutter run -d <Android_ID>
# Tester:
# - Keystore storage
# - Fingerprint authentication
# - Permissions camera/storage
```

#### Web
```bash
flutter run -d chrome
# Tester:
# - LocalStorage fallback
# - Session persistence
# - CORS images
```

---

### 7. ✅ CHECKLIST FINALE

Avant de déployer en production:

**Sécurité:**
- [ ] Tokens stockés dans FlutterSecureStorage
- [ ] Validation des entrées activée
- [ ] Aucun token en clair dans les logs
- [ ] ProGuard activé (Android)
- [ ] Code signing configuré (iOS)

**Performance:**
- [ ] Images mises en cache
- [ ] Temps de démarrage < 3s
- [ ] FPS stable à 60
- [ ] Pas de memory leaks
- [ ] Taille APK < 50MB

**Tests:**
- [ ] Login/Logout fonctionnels
- [ ] Validation des formulaires
- [ ] Images chargées rapidement
- [ ] Mode offline fonctionnel
- [ ] Pas de crash au démarrage

---

### 8. 🐛 RÉSOLUTION DE PROBLÈMES

#### Problème: Token non persisté après redémarrage

**Solution:**
```dart
// Vérifier l'initialisation dans main.dart
WidgetsFlutterBinding.ensureInitialized();
await authProvider.initialize();
```

#### Problème: Images non cachées

**Solution:**
```bash
# Nettoyer le cache
flutter clean
flutter pub get

# Vérifier l'espace disque
adb shell df -h
```

#### Problème: Erreur Keystore (Android)

**Solution:**
```bash
# Vérifier les permissions AndroidManifest.xml
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE"/>
```

---

### 9. 📈 MÉTRIQUES DE SUCCÈS

**Sécurité:**
- ✅ 0 tokens en clair
- ✅ 100% validation des entrées
- ✅ Chiffrement AES-256

**Performance:**
- ✅ 95% réduction temps de chargement images
- ✅ 90% réduction bande passante
- ✅ 0 memory leaks

**Qualité:**
- ✅ 0 erreurs de compilation
- ✅ 0 warnings critiques
- ✅ 100% compatibilité Android/iOS

---

## 📝 RAPPORT DE TEST

À remplir après les tests:

**Date:** _____________
**Testeur:** _____________
**Appareil:** _____________
**Version OS:** _____________

### Résultats:

| Test | Statut | Notes |
|------|--------|-------|
| Stockage sécurisé | ⬜ Pass ⬜ Fail | |
| Validation entrées | ⬜ Pass ⬜ Fail | |
| Cache images | ⬜ Pass ⬜ Fail | |
| Performance | ⬜ Pass ⬜ Fail | |
| Persistence | ⬜ Pass ⬜ Fail | |

**Bugs trouvés:**
1. _____________
2. _____________

**Recommandations:**
1. _____________
2. _____________

---

**Signature:** _____________
**Date:** _____________
