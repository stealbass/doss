# ✅ Éditeur WYSIWYG avec Cloudflare R2 - Configuration Complète

## 🎯 Résumé des modifications

### ✨ Fonctionnalités ajoutées
1. **Éditeur de texte riche Summernote** pour le Message Body
2. **Upload d'images inline** directement dans le contenu
3. **Stockage automatique sur Cloudflare R2** (CDN mondial)
4. **Formatage avancé** : gras, italique, couleurs, listes, tableaux, liens, vidéos

### 📦 Fichiers modifiés (prêts à l'emploi)

#### 1. **app/Http/Controllers/PushNotificationsController.php**
✅ Ajout `use Illuminate\Support\Facades\Storage;`
✅ Méthode `store()` : Upload image principale vers R2
✅ **Nouvelle méthode `uploadImage()`** : Upload images inline Summernote vers R2

#### 2. **resources/views/push-notifications/create.blade.php**
✅ Textarea remplacé par éditeur Summernote (#summernote)
✅ CDN Summernote ajouté (CSS + JS)
✅ Configuration toolbar complète
✅ Callback upload d'images vers route `/push-notifications/upload-image`
✅ Prévisualisation en temps réel
✅ Formulaire `enctype="multipart/form-data"`

#### 3. **routes/web.php**
✅ Route POST `push-notifications/upload-image` → `uploadImage()`

#### 4. **config/filesystems.php**
✅ Configuration R2 déjà présente (disk 'r2')

---

## 🔧 Configuration Cloudflare R2

### Étape 1 : Variables d'environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
# Cloudflare R2 Configuration
R2_ACCESS_KEY_ID=your_r2_access_key_id
R2_SECRET_ACCESS_KEY=your_r2_secret_access_key
R2_BUCKET=your-bucket-name
R2_REGION=auto
R2_ENDPOINT=https://account-id.r2.cloudflarestorage.com
R2_URL=https://your-custom-domain.com  # Ou https://pub-xxxxx.r2.dev
```

### Étape 2 : Obtenir les identifiants R2

1. **Connectez-vous** à Cloudflare Dashboard
2. **Allez dans** : R2 Object Storage
3. **Créez un bucket** si nécessaire (ex: `dossypro-storage`)
4. **Générez les API Tokens** :
   - Cliquez sur "Manage R2 API Tokens"
   - Créez un nouveau token avec permissions "Edit"
   - Copiez `Access Key ID` → `R2_ACCESS_KEY_ID`
   - Copiez `Secret Access Key` → `R2_SECRET_ACCESS_KEY`
5. **Configurez le domaine public** :
   - Dans votre bucket → Settings → Public Access
   - Activez "Allow Public Access"
   - Ajoutez un domaine personnalisé ou utilisez le domaine R2 par défaut
   - Copiez l'URL → `R2_URL`

### Étape 3 : Endpoint R2

Format de l'endpoint :
```
https://<ACCOUNT_ID>.r2.cloudflarestorage.com
```

Remplacez `<ACCOUNT_ID>` par votre Account ID Cloudflare (visible dans le dashboard, section R2)

### Exemple de configuration complète

```env
R2_ACCESS_KEY_ID=abc123def456ghi789
R2_SECRET_ACCESS_KEY=xyz789uvw456rst123qpo456
R2_BUCKET=dossypro-storage
R2_REGION=auto
R2_ENDPOINT=https://1a2b3c4d5e6f7g8h.r2.cloudflarestorage.com
R2_URL=https://cdn.dossypro.com  # Domaine personnalisé configuré dans R2
```

---

## 📂 Structure de stockage R2

### Après upload, vos images seront stockées ainsi :

```
Bucket: dossypro-storage
├── push-notifications/
│   ├── 1736089234_65f8a9b2c4d3e.jpg  (image principale notification)
│   ├── 1736089256_65f8a9c8e1f2a.png
│   └── inline/
│       ├── 1736089301_65f8a9ed12345.jpg  (images insérées dans texte)
│       ├── 1736089334_65f8aa0e67890.png
│       └── 1736089367_65f8aa2fabcde.gif
```

### URLs générées

**Image principale:**
```
https://cdn.dossypro.com/push-notifications/1736089234_65f8a9b2c4d3e.jpg
```

**Images inline (dans le texte):**
```
https://cdn.dossypro.com/push-notifications/inline/1736089301_65f8a9ed12345.jpg
```

Ces URLs sont **publiques** et **accessibles mondialement** via le CDN Cloudflare.

---

## 🚀 Installation et déploiement

### Checklist complète

#### 1. Upload des fichiers
- [ ] Upload `app/Http/Controllers/PushNotificationsController.php`
- [ ] Upload `resources/views/push-notifications/create.blade.php`
- [ ] Upload `routes/web.php`

#### 2. Configuration serveur
```bash
# Mettre à jour les variables d'environnement
nano .env
# Ajouter les variables R2_ (voir Étape 1)

# Vider le cache Laravel
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

#### 3. Installation des dépendances
```bash
# S3 driver pour R2 (normalement déjà installé)
composer require league/flysystem-aws-s3-v3 "^3.0"
```

#### 4. Test de connexion R2
Créez un script de test `test_r2.php` :

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

try {
    // Test upload
    $content = 'Test Cloudflare R2 - ' . date('Y-m-d H:i:s');
    $path = 'test/test.txt';
    
    Storage::disk('r2')->put($path, $content, 'public');
    echo "✅ Upload réussi vers R2!\n";
    
    // Test lecture
    if (Storage::disk('r2')->exists($path)) {
        echo "✅ Fichier trouvé sur R2!\n";
        echo "URL: " . env('R2_URL') . '/' . $path . "\n";
    }
    
    // Nettoyage
    Storage::disk('r2')->delete($path);
    echo "✅ Connexion R2 fonctionnelle!\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
```

Exécutez :
```bash
php test_r2.php
```

---

## 🎨 Utilisation dans l'admin

### Créer une notification avec contenu riche

1. **Accédez à** : Admin → Push Notifications → Create

2. **Rédigez dans l'éditeur** :
   - Tapez votre texte
   - Sélectionnez et formatez (gras, couleur, etc.)
   - Cliquez sur l'icône 📷 "Picture" dans la toolbar
   - Sélectionnez une image depuis votre ordinateur
   - L'image s'uploade automatiquement vers R2 et s'insère

3. **Résultat** :
   - Toutes les images sont sur R2
   - URLs publiques générées automatiquement
   - Contenu HTML stocké en base de données
   - Accessible mondialement via CDN Cloudflare

### Exemple de contenu HTML généré

```html
<h2>🎉 Nouvelle Promotion !</h2>
<p>Profitez de <strong style="color: red;">50% de réduction</strong> sur tous nos plans.</p>
<p><img src="https://cdn.dossypro.com/push-notifications/inline/1736089301_65f8a9ed12345.jpg" style="width: 400px;"></p>
<ul>
  <li>✅ Valable jusqu'au 31 janvier</li>
  <li>✅ Tous les utilisateurs éligibles</li>
  <li>✅ Sans engagement</li>
</ul>
<p><a href="https://dossypro.com/plans">Voir les offres →</a></p>
```

---

## 🔒 Sécurité et permissions

### Configuration bucket R2

Dans Cloudflare Dashboard → R2 → Votre bucket → Settings :

#### Public Access (recommandé pour notifications)
```
✅ Allow Public Access (activé)
```

Cela permet aux images d'être accessibles publiquement sans authentification.

#### CORS Configuration (si nécessaire)
Si vous voulez restreindre l'accès :

```json
[
  {
    "AllowedOrigins": ["https://dossypro.com"],
    "AllowedMethods": ["GET"],
    "AllowedHeaders": ["*"],
    "MaxAgeSeconds": 3600
  }
]
```

#### Validation côté serveur
✅ Type MIME vérifié (image seulement)
✅ Taille limitée à 2 MB
✅ Noms de fichiers aléatoires (timestamp + uniqid)
✅ Pas d'exécution de code possible

---

## 📊 Avantages Cloudflare R2

### 🚀 Performance
- **CDN mondial** : Images servies depuis le serveur le plus proche
- **Cache automatique** : Moins de latence
- **Bande passante illimitée** : Pas de frais d'egress

### 💰 Coûts
- **Stockage** : $0.015/GB/mois (très bas)
- **Téléchargement** : GRATUIT (vs AWS S3 qui facture)
- **API Requests** : Classe A (write) : $4.50/million, Classe B (read) : $0.36/million

### 🛡️ Fiabilité
- **99.9% uptime** garanti
- **Redondance** : Données répliquées automatiquement
- **Backups** : Intégré dans Cloudflare

### Comparaison avec stockage local

| Critère | Stockage Local | Cloudflare R2 |
|---------|----------------|---------------|
| Performance | ⚠️ Limitée au serveur | ✅ CDN mondial |
| Scalabilité | ❌ Limitée par disque | ✅ Illimitée |
| Coût bande passante | 💰 Élevé | 🆓 Gratuit |
| Backups | ⚠️ Manuel | ✅ Automatique |
| Accès mobile | ⚠️ Dépend serveur | ✅ Rapide partout |

---

## 🐛 Troubleshooting

### ❌ Erreur "Class 'League\Flysystem\AwsS3V3\AwsS3V3Adapter' not found"

**Solution :**
```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
composer dump-autoload
```

### ❌ Erreur "Error executing 'PutObject'"

**Causes possibles :**
1. **Access Key incorrecte** : Vérifiez `R2_ACCESS_KEY_ID`
2. **Secret Key incorrecte** : Vérifiez `R2_SECRET_ACCESS_KEY`
3. **Endpoint incorrect** : Vérifiez `R2_ENDPOINT`
4. **Bucket inexistant** : Créez le bucket dans Cloudflare

**Diagnostic :**
```bash
php test_r2.php
```

### ❌ Image uploadée mais URL 404

**Cause :** Public Access non activé sur le bucket

**Solution :**
1. Cloudflare Dashboard → R2 → Votre bucket
2. Settings → Public Access
3. ✅ Activez "Allow Public Access"
4. Configurez un domaine personnalisé ou utilisez le domaine R2.dev

### ❌ URL d'image cassée dans l'éditeur

**Cause :** `R2_URL` non configuré ou incorrect

**Solution :**
```env
# .env
R2_URL=https://pub-xxxxxxxxxxxxx.r2.dev  # Ou votre domaine personnalisé
```

Puis :
```bash
php artisan config:clear
```

---

## 📝 Notes importantes

### Contenu HTML en base de données
Le champ `body` de la table `push_notifications` contient maintenant du HTML avec des balises `<img>`, `<strong>`, etc.

**Affichage dans l'app mobile :**
- Utilisez un WebView ou un widget HTML
- Librairie Flutter recommandée : `flutter_html`
- Sanitisez le HTML pour éviter XSS

### Exemple Flutter (affichage du body HTML)

```dart
import 'package:flutter_html/flutter_html.dart';

// Dans votre widget
Html(
  data: notification.body, // HTML depuis l'API
  style: {
    "body": Style(
      fontSize: FontSize(16.0),
      color: Colors.black87,
    ),
    "img": Style(
      width: Width(double.infinity),
      padding: EdgeInsets.all(8),
    ),
  },
)
```

### Migration des anciennes notifications
Si vous avez déjà des notifications avec du texte brut, elles s'afficheront correctement (pas de balises HTML).

Pour convertir en HTML :
```sql
UPDATE push_notifications 
SET body = CONCAT('<p>', REPLACE(body, '\n', '</p><p>'), '</p>')
WHERE body NOT LIKE '%<%';
```

---

## ✅ Checklist finale

### Configuration
- [ ] Variables R2 ajoutées dans `.env`
- [ ] Bucket R2 créé sur Cloudflare
- [ ] Public Access activé sur le bucket
- [ ] Domaine configuré (personnalisé ou R2.dev)
- [ ] Test `test_r2.php` réussi

### Déploiement
- [ ] `PushNotificationsController.php` uploadé avec méthode `uploadImage()`
- [ ] `create.blade.php` uploadé avec Summernote
- [ ] `routes/web.php` uploadé avec route upload-image
- [ ] `composer require league/flysystem-aws-s3-v3` exécuté
- [ ] Cache Laravel vidé

### Tests
- [ ] Création notification avec texte formaté
- [ ] Upload image inline fonctionne
- [ ] Image affichée dans éditeur
- [ ] Notification enregistrée en BDD
- [ ] URL R2 accessible publiquement
- [ ] Affichage dans app mobile (si applicable)

---

## 🎉 C'est terminé !

Vous avez maintenant un système complet de Push Notifications avec :
- ✅ Éditeur WYSIWYG professionnel (Summernote)
- ✅ Upload d'images automatique vers Cloudflare R2
- ✅ CDN mondial pour performance optimale
- ✅ Stockage sécurisé et scalable
- ✅ Contenu HTML riche avec images inline
- ✅ Coûts optimisés (bande passante gratuite)

Tous les fichiers sont **prêts à l'emploi** et **configurés pour R2** ! 🚀
