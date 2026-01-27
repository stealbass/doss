# 🌐 Détection Automatique de Langue - Documentation

## Vue d'ensemble

L'application DOSSY CHAT IA détecte maintenant automatiquement la langue du téléphone au premier lancement et traduit l'interface en conséquence.

## 🎯 Fonctionnalités

### 1. **Détection Automatique au Démarrage**
   - Lors du premier lancement de l'app, la langue du système est détectée
   - L'interface s'affiche immédiatement dans la bonne langue
   - La préférence est sauvegardée localement pour les lancements ultérieurs

### 2. **Langues Supportées**
   - 🇫🇷 **Français** (FR) - Langue par défaut
   - 🇬🇧 **Anglais** (EN) - English

### 3. **Basculement Manuel**
   - L'utilisateur peut changer la langue manuellement dans les paramètres
   - Un bouton toggle switch permet de basculer facilement entre FR et EN

## 📋 Architecture

### Fichiers Impliqués

#### 1. **`LocaleDetector` - Classe Utilitaire**
**Fichier**: `lib/core/utils/locale_detector.dart`

```dart
// Détecte la langue du système
String detectSystemLanguage()

// Crée une Locale à partir du code langue
Locale getLocaleFromLanguageCode(String languageCode)

// Crée une Locale basée sur le système
Locale getSystemLocale()

// Obtient le nom de la langue
String getLanguageName(String languageCode)

// Obtient le drapeau emoji
String getLanguageFlag(String languageCode)
```

#### 2. **`LocaleProvider` - Provider d'État**
**Fichier**: `lib/data/providers/locale_provider.dart`

Gère l'état global de la langue :
- `initialize()` : Initialise la locale au démarrage (détecte le système ou charge la préférence)
- `setLocale(String languageCode)` : Change la langue et la sauvegarde
- `toggleLocale()` : Bascule entre FR et EN

#### 3. **`SplashScreen` - Point d'Entrée**
**Fichier**: `lib/presentation/screens/splash/splash_screen.dart`

Appelle `localeProvider.initialize()` au démarrage pour :
1. Détecter la langue du système (premier lancement)
2. Charger la langue sauvegardée (lancements ultérieurs)
3. Appliquer la langue avant l'affichage de l'interface

#### 4. **`main.dart` - Configuration Globale**
Le `LocaleProvider` est intégré au `MultiProvider` et utilisé par `MaterialApp` :

```dart
Consumer2<LocaleProvider, ThemeProvider>(
  builder: (context, localeProvider, themeProvider, child) {
    return MaterialApp(
      locale: localeProvider.locale,
      supportedLocales: [
        Locale('en', 'US'),
        Locale('fr', 'FR'),
      ],
      localizationsDelegates: [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      // ... reste de la config
    );
  }
)
```

## 🔄 Flux d'Exécution

### Au Premier Lancement
```
1. App démarre
   ↓
2. Splash Screen s'affiche
   ↓
3. _initializeApp() appelé
   ↓
4. LocaleProvider.initialize() appelé
   ↓
5. Détection de la langue du système
   ↓
6. Sauvegarde de la langue dans SharedPreferences
   ↓
7. Interface affichée dans la langue détectée
```

### Aux Lancements Ultérieurs
```
1. App démarre
   ↓
2. Splash Screen s'affiche
   ↓
3. _initializeApp() appelé
   ↓
4. LocaleProvider.initialize() appelé
   ↓
5. Chargement de la langue sauvegardée
   ↓
6. Interface affichée dans la langue précédente
```

### Lors du Changement de Langue
```
1. Utilisateur clique sur le toggle/bouton de langue
   ↓
2. setLocale() ou toggleLocale() appelé
   ↓
3. LocaleProvider met à jour _locale
   ↓
4. SharedPreferences mis à jour
   ↓
5. notifyListeners() déclenche la reconstruction
   ↓
6. MaterialApp reçoit la nouvelle locale
   ↓
7. Interface recompilée dans la nouvelle langue
```

## 📱 Exemple d'Utilisation

### Changer la langue manuellement
```dart
final localeProvider = Provider.of<LocaleProvider>(context, listen: false);

// Changer en anglais
await localeProvider.setLocale('en');

// Basculer entre FR et EN
await localeProvider.toggleLocale();

// Accéder à la locale actuelle
Locale currentLocale = localeProvider.locale;
```

### Dans un Widget
```dart
Consumer<LocaleProvider>(
  builder: (context, localeProvider, child) {
    return Text(
      'Langue actuelle: ${localeProvider.locale.languageCode}',
    );
  }
)
```

## 🛠️ Langues Détectées

| Langue du Téléphone | Code | Locale Appliquée | Drapeau |
|-------------------|------|------------------|--------|
| English | `en` | `Locale('en', 'US')` | 🇬🇧 |
| Français | `fr` | `Locale('fr', 'FR')` | 🇫🇷 |
| Autre | - | `Locale('fr', 'FR')` (défaut) | 🇫🇷 |

## 💾 Stockage

La langue choisie est sauvegardée dans `SharedPreferences` avec la clé :
```dart
AppConstants.localeKey = 'app_locale'
```

Valeurs possibles :
- `'en'` pour l'anglais
- `'fr'` pour le français

## 🐛 Dépannage

### L'app ne change pas de langue au lancement
1. Vérifier que `LocaleProvider.initialize()` est appelé dans `SplashScreen`
2. Vérifier les permissions d'accès à `SharedPreferences`
3. Effacer le cache et réinstaller l'app

### La langue détectée est incorrecte
1. Vérifier la langue du système dans les paramètres de l'appareil
2. Seules `en` et `fr` sont supportées; les autres langues utilisent le français par défaut

## 🎨 Améliorations Futures

- [ ] Ajouter d'autres langues (ES, DE, IT, etc.)
- [ ] Permettre au serveur de définir la langue par défaut pour un utilisateur
- [ ] Ajouter un menu de langues avec tous les drapeaux disponibles
- [ ] Supporter les langues RTL (arabe, hébreu, etc.)
- [ ] Synchroniser la langue entre appareils pour les utilisateurs authentifiés

---

**Date de mise à jour**: 8 janvier 2026
**Version**: 1.0
