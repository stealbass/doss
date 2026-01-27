# 🔧 VIDER LE CACHE LARAVEL MANUELLEMENT VIA FTP

## ✅ **SOLUTION COMPLÈTE SANS SSH**

Vous pouvez vider tous les caches Laravel manuellement en **supprimant des dossiers** via FTP.

---

## 📁 **ÉTAPE 1 : Connectez-vous à votre FTP**

Utilisez FileZilla, WinSCP, ou le gestionnaire de fichiers de cPanel.

**Accédez au dossier** :
```
/home/dossypro/public_html
```

---

## 🗑️ **ÉTAPE 2 : Supprimer les dossiers de cache**

### **Dossiers à supprimer** :

Naviguez vers chaque dossier et **supprimez-le complètement** :

#### **1. Cache de l'application**
```
📁 storage/framework/cache/data/
```
**Action** : Supprimer TOUT le contenu du dossier `data/` (ou le dossier lui-même)

#### **2. Cache des vues**
```
📁 storage/framework/views/
```
**Action** : Supprimer TOUS les fichiers `.php` dans ce dossier
- **NE PAS** supprimer le fichier `.gitignore` s'il existe
- Supprimez uniquement les fichiers qui ressemblent à : `1a2b3c4d5e6f7g8h.php`

#### **3. Cache de la configuration**
```
📁 bootstrap/cache/
```
**Action** : Supprimer ces fichiers :
- `config.php`
- `routes.php` (ou `routes-v7.php`)
- `services.php`
- `packages.php`
- **GARDER** : `.gitignore`

#### **4. Cache des sessions (optionnel)**
```
📁 storage/framework/sessions/
```
**Action** : Supprimer tous les fichiers de session

---

## 📂 **STRUCTURE VISUELLE**

Voici comment naviguer dans votre FTP :

```
public_html/
├── bootstrap/
│   └── cache/              ← SUPPRIMER config.php, routes.php, etc.
│
└── storage/
    └── framework/
        ├── cache/
        │   └── data/       ← SUPPRIMER TOUT
        ├── sessions/       ← SUPPRIMER tout (optionnel)
        └── views/          ← SUPPRIMER les fichiers .php
```

---

## ⚠️ **IMPORTANT : Ne supprimez PAS ces fichiers**

**À GARDER** :
- `.gitignore` (dans tous les dossiers)
- Le dossier `storage/` lui-même
- Le dossier `bootstrap/` lui-même
- Le fichier `storage/framework/.gitignore`

**NE SUPPRIMEZ QUE** :
- Le **contenu** des dossiers mentionnés
- **PAS** les dossiers eux-mêmes

---

## 🎯 **ÉTAPE 3 : Créer un fichier PHP pour vider les caches**

Si vous ne voulez pas supprimer manuellement, créez ce fichier.

### **Via FTP : Créez le fichier `clear-cache.php`**

**Chemin** : `/home/dossypro/public_html/clear-cache.php`

**Contenu du fichier** :

```php
<?php
/**
 * Script de nettoyage des caches Laravel
 * Accès : https://dossypro.com/clear-cache.php
 */

// Sécurité : Protéger avec un token
$token = $_GET['token'] ?? '';
$validToken = 'DOSSY2024CLEAR'; // Changez ce token !

if ($token !== $validToken) {
    die('❌ Accès refusé. Token invalide.');
}

echo "<pre>";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         🔧 NETTOYAGE DES CACHES LARAVEL                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$cleared = [];
$errors = [];

// 1. Nettoyer le cache de l'application
$cacheDataPath = __DIR__ . '/storage/framework/cache/data';
if (is_dir($cacheDataPath)) {
    $files = glob($cacheDataPath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    $cleared[] = "✅ Cache de l'application vidé (" . count($files) . " fichiers)";
} else {
    $errors[] = "⚠️  Dossier cache/data introuvable";
}

// 2. Nettoyer les vues compilées
$viewsPath = __DIR__ . '/storage/framework/views';
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*.php');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) !== '.gitignore' && is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    $cleared[] = "✅ Vues compilées vidées (" . $count . " fichiers)";
} else {
    $errors[] = "⚠️  Dossier views introuvable";
}

// 3. Nettoyer le cache de configuration
$bootstrapCachePath = __DIR__ . '/bootstrap/cache';
if (is_dir($bootstrapCachePath)) {
    $filesToDelete = [
        'config.php',
        'routes.php',
        'routes-v7.php',
        'services.php',
        'packages.php'
    ];
    $count = 0;
    foreach ($filesToDelete as $filename) {
        $file = $bootstrapCachePath . '/' . $filename;
        if (file_exists($file) && is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    $cleared[] = "✅ Cache de configuration vidé (" . $count . " fichiers)";
} else {
    $errors[] = "⚠️  Dossier bootstrap/cache introuvable";
}

// 4. Nettoyer les sessions (optionnel)
$sessionsPath = __DIR__ . '/storage/framework/sessions';
if (is_dir($sessionsPath)) {
    $files = glob($sessionsPath . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) !== '.gitignore' && is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    $cleared[] = "✅ Sessions vidées (" . $count . " fichiers)";
}

// 5. Nettoyer les logs anciens (garder les 100 dernières lignes)
$logPath = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lines = file($logPath);
    $lastLines = array_slice($lines, -100);
    file_put_contents($logPath, implode('', $lastLines));
    $cleared[] = "✅ Logs nettoyés (gardé 100 dernières lignes)";
}

// Affichage des résultats
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "RÉSULTATS DU NETTOYAGE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

foreach ($cleared as $message) {
    echo $message . "\n";
}

if (!empty($errors)) {
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "AVERTISSEMENTS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    foreach ($errors as $error) {
        echo $error . "\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 NETTOYAGE TERMINÉ !\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "Maintenant, testez vos URLs :\n";
echo "• https://dossypro.com/mobile-dashboard\n";
echo "• https://dossypro.com/mobile-analytics\n";
echo "• https://dossypro.com/document-templates\n";
echo "• https://dossypro.com/fiscal-resources\n";
echo "• https://dossypro.com/calculators\n";
echo "• https://dossypro.com/legal-library/category/create\n\n";

echo "⚠️  IMPORTANT : Rechargez les pages avec CTRL+F5\n\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  ⚠️  SÉCURITÉ : Supprimez ce fichier après utilisation !     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "</pre>";
?>
```

---

## 🌐 **ÉTAPE 4 : Utiliser le script**

### **1. Uploadez le fichier via FTP**
- Uploadez `clear-cache.php` dans `/home/dossypro/public_html/`

### **2. Accédez au script dans votre navigateur**
```
https://dossypro.com/clear-cache.php?token=DOSSY2024CLEAR
```

### **3. Le script affichera** :
```
✅ Cache de l'application vidé (45 fichiers)
✅ Vues compilées vidées (127 fichiers)
✅ Cache de configuration vidé (3 fichiers)
✅ Sessions vidées (8 fichiers)
✅ Logs nettoyés

🎉 NETTOYAGE TERMINÉ !
```

### **4. SUPPRIMEZ le fichier après utilisation**
⚠️ **IMPORTANT** : Supprimez `clear-cache.php` via FTP pour des raisons de sécurité.

---

## 🎯 **ALTERNATIVE : Renommer les dossiers**

Si vous avez peur de supprimer, **renommez** les dossiers :

**Via FTP** :
1. `storage/framework/cache/data/` → `storage/framework/cache/data_old/`
2. `storage/framework/views/` → `storage/framework/views_old/`
3. `bootstrap/cache/` → `bootstrap/cache_old/`

Puis **créez de nouveaux dossiers vides** :
1. Créer `storage/framework/cache/data/`
2. Créer `storage/framework/views/`
3. Créer `bootstrap/cache/`

Laravel recréera automatiquement le cache.

---

## ✅ **APRÈS LE NETTOYAGE**

### **1. Testez les URLs** :
- https://dossypro.com/mobile-dashboard
- https://dossypro.com/mobile-analytics
- https://dossypro.com/document-templates
- https://dossypro.com/fiscal-resources
- https://dossypro.com/calculators
- https://dossypro.com/legal-library/category/create

### **2. Rechargez avec CTRL+F5**
Forcez le rechargement du navigateur pour éviter le cache navigateur.

### **3. Si les erreurs persistent**
Vérifiez les logs via FTP :
- Téléchargez : `/storage/logs/laravel.log`
- Ouvrez avec un éditeur de texte
- Regardez les **dernières lignes** pour voir l'erreur exacte

---

## 🔧 **VÉRIFIER QUE LES FICHIERS SONT BIEN UPLOADÉS**

Via FTP, vérifiez que ces fichiers existent :

**Contrôleurs** :
- ✅ `app/Http/Controllers/MobileDashboardController.php`
- ✅ `app/Http/Controllers/MobileAnalyticsController.php`
- ✅ `app/Http/Controllers/DocumentTemplateController.php`
- ✅ `app/Http/Controllers/FiscalSocialResourceController.php`
- ✅ `app/Http/Controllers/CalculatorController.php`

**Vues** :
- ✅ `resources/views/legal-library/bulk-assign-countries.blade.php`

**Routes** :
- ✅ Ouvrez `routes/web.php` et vérifiez qu'il contient :
  - `use App\Http\Controllers\MobileDashboardController;`
  - `use App\Http\Controllers\DocumentTemplateController;`
  - etc.

Si ces fichiers **manquent**, c'est que le `git pull` n'a pas fonctionné.

**Solution** : Téléchargez-les depuis GitHub :
```
https://github.com/stealbass/doss/tree/main
```

---

## 📞 **SI ÇA NE MARCHE TOUJOURS PAS**

Envoyez-moi :
1. Une capture d'écran de votre structure FTP dans `/public_html/app/Http/Controllers/`
2. Le contenu des **dernières 50 lignes** du fichier `/storage/logs/laravel.log`

Je vous dirai exactement quel fichier est manquant ou mal configuré.

---

## ✅ **RÉSUMÉ RAPIDE**

**Méthode 1 - Manuelle** :
1. Connectez-vous FTP
2. Supprimez le contenu de :
   - `storage/framework/cache/data/`
   - `storage/framework/views/` (fichiers .php seulement)
   - `bootstrap/cache/` (config.php, routes.php)
3. Testez les URLs

**Méthode 2 - Script PHP** :
1. Créez `clear-cache.php` avec le code ci-dessus
2. Accédez à `https://dossypro.com/clear-cache.php?token=DOSSY2024CLEAR`
3. Supprimez le fichier après
4. Testez les URLs

**Les deux méthodes fonctionnent !** 🎉
