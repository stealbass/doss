# 📝 Installation Manuelle - Bibliothèque Juridique

## ⚠️ À Utiliser Si PHP/Composer Ne Fonctionne Pas

Ce guide vous permet d'installer la bibliothèque juridique **sans utiliser SSH ou artisan**.

---

## 🗄️ ÉTAPE 1 : Créer les Tables via phpMyAdmin

### **Option A : Importer le Fichier SQL Complet** ⭐ (Recommandé)

1. **Télécharger** : `legal_library_manual_install.sql`
2. **Aller dans phpMyAdmin**
3. **Sélectionner votre base de données** : `threesixty_dossy`
4. **Onglet "Importer"**
5. **Choisir le fichier** : `legal_library_manual_install.sql`
6. **Cliquer sur "Exécuter"**

✅ **C'est tout !** Les tables, permissions et tout le reste seront créés automatiquement.

---

### **Option B : Exécuter les Requêtes SQL une par une**

Si l'import ne fonctionne pas, allez dans **SQL** et exécutez ces requêtes :

#### **1. Table legal_categories**

```sql
CREATE TABLE IF NOT EXISTS `legal_categories` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `legal_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **2. Table legal_documents**

```sql
CREATE TABLE IF NOT EXISTS `legal_documents` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `downloads_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `legal_documents_category_id_foreign` (`category_id`),
  CONSTRAINT `legal_documents_category_id_foreign` 
    FOREIGN KEY (`category_id`) 
    REFERENCES `legal_categories` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **3. Permissions**

```sql
-- Créer les permissions
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) 
VALUES 
  ('manage legal library', 'web', NOW(), NOW()),
  ('view legal library', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Assigner au rôle admin (company)
SET @company_role_id = (SELECT id FROM roles WHERE name = 'company' LIMIT 1);

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, @company_role_id
FROM permissions p
WHERE p.name IN ('manage legal library', 'view legal library')
AND @company_role_id IS NOT NULL;

-- Assigner 'view' aux utilisateurs
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM permissions p
CROSS JOIN roles r
WHERE p.name = 'view legal library'
  AND r.name IN ('advocate', 'client', 'co advocate', 'team leader');
```

#### **4. Enregistrer les migrations (optionnel)**

```sql
SET @next_batch = (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations);

INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('2024_11_15_000001_create_legal_categories_table', @next_batch),
('2024_11_15_000002_create_legal_documents_table', @next_batch),
('2024_11_15_000003_add_legal_library_permissions', @next_batch);
```

---

## 📁 ÉTAPE 2 : Créer le Répertoire de Stockage

### **Via FTP (FileZilla, WinSCP, etc.)**

1. **Se connecter en FTP** à votre serveur
2. **Naviguer vers** : `/home/threesixty/yyy/Dossy/legal/storage/app/public/`
3. **Créer un nouveau dossier** : `legal_documents`
4. **Clic droit sur le dossier** → **Permissions**
5. **Définir** : `775` (ou cocher : Owner: Read/Write/Execute, Group: Read/Write/Execute, Public: Read/Execute)

### **Via cPanel File Manager**

1. **Aller dans** : File Manager
2. **Naviguer vers** : `storage/app/public/`
3. **Créer un dossier** : `legal_documents`
4. **Clic droit** → **Change Permissions**
5. **Définir** : `775`

---

## 🔗 ÉTAPE 3 : Créer le Lien Symbolique

### **Option A : Via SSH (si possible malgré l'erreur PHP)**

```bash
cd /home/threesixty/yyy/Dossy/legal
ln -s ../storage/app/public public/storage
```

### **Option B : Via .htaccess (workaround)**

1. **Créer le dossier** `public/storage` (via FTP ou File Manager)
2. **Créer un fichier** `.htaccess` dans `public/storage/`
3. **Contenu du fichier** :

```apache
Options +FollowSymLinks
RewriteEngine On

# Rediriger vers le vrai répertoire de stockage
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /storage/app/public/$1 [L]
```

### **Option C : Créer un fichier PHP de redirection**

1. **Créer** `public/storage/index.php`
2. **Contenu** :

```php
<?php
// Redirection vers le stockage réel
$requestPath = $_SERVER['REQUEST_URI'];
$fileName = basename($requestPath);
$realPath = __DIR__ . '/../../storage/app/public/' . $fileName;

if (file_exists($realPath)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $realPath);
    finfo_close($finfo);
    
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($realPath));
    readfile($realPath);
    exit;
} else {
    header("HTTP/1.0 404 Not Found");
    echo "File not found";
}
```

---

## 🧹 ÉTAPE 4 : Vider les Caches (si possible)

### **Via Navigateur Web**

Créez un fichier `clear-cache.php` à la racine :

```php
<?php
// Aller dans le répertoire du projet
chdir(__DIR__);

// Vider les caches
$commands = [
    'cache:clear',
    'config:clear',
    'route:clear',
    'view:clear'
];

echo "<h1>Nettoyage des Caches</h1>";

foreach ($commands as $cmd) {
    echo "<p>Exécution: php artisan $cmd</p>";
    $output = shell_exec("php artisan $cmd 2>&1");
    echo "<pre>$output</pre>";
}

echo "<h2>✅ Terminé !</h2>";
```

Accédez à : `https://votre-domaine.com/clear-cache.php`

**⚠️ Supprimez ce fichier après utilisation !**

### **Via File Manager (manuel)**

1. **Aller dans** : `bootstrap/cache/`
2. **Supprimer tous les fichiers** sauf `.gitignore`
3. **Aller dans** : `storage/framework/cache/`
4. **Supprimer tous les fichiers** dans les sous-dossiers

---

## ✅ ÉTAPE 5 : Vérification

### **1. Vérifier les Tables**

Dans phpMyAdmin, onglet **SQL** :

```sql
SHOW TABLES LIKE 'legal_%';
```

Vous devriez voir :
- `legal_categories`
- `legal_documents`

### **2. Vérifier les Permissions**

```sql
SELECT * FROM permissions WHERE name LIKE '%legal library%';
```

Vous devriez voir 2 permissions.

### **3. Vérifier le Stockage**

Via FTP, vérifiez que ce dossier existe :
```
storage/app/public/legal_documents/
```

### **4. Tester dans le Navigateur**

**Admin** :
```
https://votre-domaine.com/legal-library
```

**Users** :
```
https://votre-domaine.com/library
```

Si vous voyez une page (même vide), ça marche ! ✅

---

## 🆘 Dépannage

### **Erreur 404 sur /legal-library**

→ Les routes ne sont pas chargées.  
→ Solution : Assurez-vous que le fichier `routes/web.php` contient les routes de la bibliothèque.

### **Erreur "Table doesn't exist"**

→ Les tables n'ont pas été créées.  
→ Solution : Réexécutez le SQL dans phpMyAdmin.

### **Upload ne fonctionne pas**

→ Permissions du dossier incorrectes.  
→ Solution : Mettre les permissions à `775` ou `777` temporairement.

### **Images/PDFs ne s'affichent pas**

→ Le lien symbolique ne fonctionne pas.  
→ Solution : Utilisez l'Option B ou C pour le lien symbolique.

---

## 📋 Checklist Finale

- [ ] Tables créées dans phpMyAdmin
- [ ] Permissions insérées
- [ ] Dossier `storage/app/public/legal_documents/` créé
- [ ] Permissions 775 sur le dossier
- [ ] Lien symbolique ou workaround créé
- [ ] Caches vidés (si possible)
- [ ] Test admin : `/legal-library` fonctionne
- [ ] Test user : `/library` fonctionne

---

## 🎉 C'est Terminé !

Votre bibliothèque juridique est maintenant installée **sans utiliser SSH** !

**Vous pouvez maintenant** :
1. Créer des catégories
2. Uploader des PDFs
3. Permettre aux utilisateurs de consulter les documents

---

## 📞 Fichiers Fournis

- ✅ `legal_library_manual_install.sql` - Script SQL complet
- ✅ `INSTALLATION_MANUELLE.md` - Ce guide
- ✅ Tous les fichiers du code sur GitHub

---

**Bon courage ! 🚀**
