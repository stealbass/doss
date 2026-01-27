# CORRECTION TÉLÉCHARGEMENT - GUIDE COMPLET

## ❌ PROBLÈME IDENTIFIÉ

Les téléchargements utilisaient `url_launcher` qui **ouvre l'URL dans un navigateur** au lieu de **télécharger le fichier directement**.

```dart
// ❌ ANCIEN CODE (MAUVAIS)
await launchUrl(uri, mode: LaunchMode.externalApplication);
// → Ouvre le navigateur au lieu de télécharger
```

## ✅ SOLUTION IMPLEMENTÉE

Utiliser **dio** + **path_provider** + **permission_handler** pour télécharger réellement les fichiers.

```dart
// ✅ NOUVEAU CODE (BON)
await downloadService.downloadFile(
  url: downloadUrl,
  fileName: 'document.pdf',
  token: authToken,
);
// → Télécharge et sauvegarde dans Downloads/
```

## 📦 DÉPENDANCES À AJOUTER

### 1. Modifier `pubspec.yaml`:

Ajoutez ces dépendances dans la section `dependencies:`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # ... autres dépendances existantes ...
  
  # Téléchargement de fichiers
  dio: ^5.4.0
  path_provider: ^2.1.1
  permission_handler: ^11.1.0
  open_file: ^3.3.2  # Pour ouvrir les fichiers téléchargés
```

### 2. Configuration Android (permissions):

**Fichier**: `android/app/src/main/AndroidManifest.xml`

Ajoutez ces permissions AVANT la balise `<application>`:

```xml
<!-- Permissions pour téléchargement -->
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" 
    android:maxSdkVersion="32"/>
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" 
    android:maxSdkVersion="32"/>
<uses-permission android:name="android.permission.MANAGE_EXTERNAL_STORAGE"
    tools:ignore="ScopedStorage" />

<!-- Android 13+ (SDK 33+) - Permissions granulaires -->
<uses-permission android:name="android.permission.READ_MEDIA_IMAGES"/>
<uses-permission android:name="android.permission.READ_MEDIA_VIDEO"/>
<uses-permission android:name="android.permission.READ_MEDIA_AUDIO"/>
```

**Important**: Ajoutez aussi dans la balise `<manifest>` en haut:
```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    xmlns:tools="http://schemas.android.com/tools">
```

### 3. Configuration iOS:

**Fichier**: `ios/Runner/Info.plist`

Ajoutez avant la dernière balise `</dict>`:

```xml
<key>NSPhotoLibraryAddUsageDescription</key>
<string>L'application a besoin d'accéder à votre galerie pour sauvegarder les fichiers téléchargés</string>
<key>NSPhotoLibraryUsageDescription</key>
<string>L'application a besoin d'accéder à votre galerie</string>
```

## 📁 FICHIERS CRÉÉS

### 1. `lib/services/download_service.dart` ✅
Service de téléchargement avec:
- Gestion des permissions
- Barre de progression
- Sauvegarde dans Downloads/
- Support Android + iOS

### 2. `lib/utils/download_helpers.dart` ✅
Fonctions helpers réutilisables pour:
- Templates
- Ressources fiscales
- Documents juridiques

## 🔧 MODIFICATIONS NÉCESSAIRES

### A. Templates (3 fichiers à modifier)

#### 1. `lib/screens/templates/template_detail_screen.dart`
✅ Déjà modifié - Utilise DownloadService

#### 2. `lib/screens/templates/templates_list_screen.dart`

**Remplacer** dans le callback onDownload (ligne ~220):

```dart
// ❌ ANCIEN CODE
onDownload: () async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  if (authProvider.token != null) {
    final url = await provider.downloadTemplate(
      template.id,
      authProvider.token!,
    );
    if (url != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Téléchargement démarré...'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }
},

// ✅ NOUVEAU CODE
onDownload: () async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  final provider = Provider.of<TemplateProvider>(context, listen: false);
  
  await DownloadHelpers.downloadTemplate(
    context: context,
    templateId: template.id,
    templateTitle: template.title,
    fileType: template.fileType,
    token: authProvider.token,
    fetchDownloadUrl: provider.downloadTemplate,
  );
},
```

**Ajouter** en haut du fichier:
```dart
import '../../utils/download_helpers.dart';
```

### B. Fiscal Resources (2 fichiers à modifier)

#### 1. `lib/screens/fiscal_resources/fiscal_resource_detail_screen.dart`

**Trouver** la fonction de téléchargement et **remplacer** par:

```dart
import '../../utils/download_helpers.dart';

// Dans la classe state:
Future<void> _downloadResource() async {
  final authProvider = context.read<AuthProvider>();
  final provider = context.read<FiscalResourceProvider>();
  
  await DownloadHelpers.downloadFiscalResource(
    context: context,
    resourceId: widget.resource.id,
    resourceTitle: widget.resource.title,
    fileType: widget.resource.fileType ?? 'pdf',
    token: authProvider.token,
    fetchDownloadUrl: (id, token) async {
      // Appeler l'API pour obtenir l'URL de téléchargement
      return 'URL_FROM_API'; // À adapter selon votre API
    },
  );
}
```

### C. Legal Library (2 fichiers à modifier)

#### 1. `lib/screens/legal_library/legal_library_screen_new.dart`

Dans la classe `_DocumentCard`, **remplacer** le IconButton download:

```dart
trailing: IconButton(
  onPressed: () async {
    final auth = context.read<AuthProvider>();
    
    await DownloadHelpers.downloadLegalDocument(
      context: context,
      documentId: document.id,
      documentTitle: document.title,
      fileName: document.fileType.isNotEmpty 
          ? '${document.title}.${document.fileType}' 
          : document.title,
      token: auth.token,
      fetchDownloadUrl: (id, token) async {
        // Appeler l'API pour obtenir l'URL
        return 'URL_FROM_API'; // À adapter
      },
    );
  },
  icon: const Icon(Icons.download),
  tooltip: 'Télécharger',
),
```

**Ajouter** en haut:
```dart
import '../../utils/download_helpers.dart';
```

## 🚀 COMMANDES À EXÉCUTER

```bash
cd dossy_chat_ia

# 1. Installer les nouvelles dépendances
flutter pub get

# 2. Nettoyer le build
flutter clean

# 3. Rebuild l'application
flutter run
```

## 🧪 TESTER LE TÉLÉCHARGEMENT

### Test 1: Template
1. Ouvrir "Modèles de Documents"
2. Cliquer sur le bouton de téléchargement
3. **Résultat attendu**: 
   - Dialog de progression apparaît
   - Fichier se télécharge
   - Message "✓ [nom fichier] téléchargé"
   - Fichier dans `/storage/emulated/0/Download/`

### Test 2: Fiscal Resource
1. Ouvrir "Ressources Fiscales"
2. Cliquer sur le bouton de téléchargement
3. **Résultat attendu**: Même comportement

### Test 3: Legal Document
1. Ouvrir "Bibliothèque juridique"
2. Cliquer sur le bouton de téléchargement
3. **Résultat attendu**: Même comportement

## 📂 VÉRIFIER LES FICHIERS TÉLÉCHARGÉS

Sur Android, les fichiers sont dans:
```
/storage/emulated/0/Download/
```

Ou via l'application "Fichiers" / "Files" du téléphone, onglet "Téléchargements".

## ⚠️ PROBLÈMES POTENTIELS

### Problème 1: "Permission denied"

**Solution**: L'utilisateur doit accepter les permissions de stockage. Le code demande automatiquement.

### Problème 2: Téléchargement ne démarre pas

**Cause**: L'URL de téléchargement est invalide ou l'utilisateur n'est pas authentifié.

**Debug**: Vérifier les logs:
```dart
print('Download URL: $downloadUrl');
```

### Problème 3: "Download failed"

**Causes possibles**:
1. Fichier n'existe pas sur le serveur
2. URL invalide
3. Token expiré
4. Pas de connexion internet

## 📋 CHECKLIST FINALE

Avant de tester:

- [ ] `pubspec.yaml` modifié avec les 4 dépendances
- [ ] `flutter pub get` exécuté
- [ ] `AndroidManifest.xml` modifié avec permissions
- [ ] `Info.plist` modifié (iOS)
- [ ] `download_service.dart` créé
- [ ] `download_helpers.dart` créé
- [ ] `template_detail_screen.dart` modifié
- [ ] `templates_list_screen.dart` modifié
- [ ] `fiscal_resource_detail_screen.dart` modifié
- [ ] `legal_library_screen_new.dart` modifié
- [ ] `flutter clean` exécuté
- [ ] App rebuild et testée

Une fois tout cela fait, les téléchargements fonctionneront correctement! 🎉
