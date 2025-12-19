# 🔧 GUIDE DE RÉSOLUTION DES ERREURS DE MIGRATION

## Dossy Pro - Corrections et Solutions

---

## ✅ ERREUR CORRIGÉE : Duplicate Column 'country'

### **Problème Rencontré**

```
SQLSTATE[23000]: Integrity constraint violation: 1060 Duplicate column 
name 'country' (Connection: mysql, SQL: alter table `legal_categories` 
add `country` varchar(100) null after `slug`)
```

**Cause** : La migration essayait d'ajouter une colonne `country` qui existait déjà dans la table `legal_categories`.

### **Solution Appliquée** ✅

La migration `2025_12_18_000001_add_country_to_legal_library_tables.php` a été corrigée pour :

1. **Vérifier l'existence des colonnes** avant de les créer
2. **Vérifier l'existence des index** avant de les créer
3. Rendre la migration **idempotente** (peut être exécutée plusieurs fois sans erreur)

**Code de vérification ajouté** :
```php
// Vérifier si la colonne existe avant de l'ajouter
if (!Schema::hasColumn('legal_categories', 'country')) {
    $table->string('country', 100)->nullable()->after('slug');
}
```

---

## 🚀 COMMENT APPLIQUER LA CORRECTION

### **Méthode 1 : Réinitialiser et Migrer (RECOMMANDÉ pour développement)**

Cette méthode supprime toutes les données et recommence à zéro :

```bash
# 1. Réinitialiser toutes les migrations
php artisan migrate:fresh

# 2. Relancer les migrations
php artisan migrate

# 3. Peupler les données de test (optionnel)
php artisan db:seed
```

**⚠️ ATTENTION** : Cette méthode **supprime toutes les données** de la base de données !

---

### **Méthode 2 : Rollback Partiel (Pour conserver les données)**

Si vous voulez conserver vos données existantes :

```bash
# 1. Identifier la dernière migration problématique
php artisan migrate:status

# 2. Rollback de la dernière batch de migrations
php artisan migrate:rollback

# 3. Relancer les migrations avec la correction
php artisan migrate
```

---

### **Méthode 3 : Correction Manuelle de la Base de Données**

Si les méthodes précédentes ne fonctionnent pas, vous pouvez corriger manuellement :

#### **Option A : Supprimer les colonnes en doublon**

```bash
# Connexion à MySQL
mysql -u root -p dossy_pro

# Vérifier les colonnes existantes
DESCRIBE legal_categories;

# Si la colonne 'country' existe déjà, la supprimer
ALTER TABLE legal_categories DROP COLUMN IF EXISTS country;
ALTER TABLE legal_categories DROP COLUMN IF EXISTS is_mobile_visible;
ALTER TABLE legal_categories DROP COLUMN IF EXISTS sort_order;

# Faire de même pour legal_documents
ALTER TABLE legal_documents DROP COLUMN IF EXISTS country;
ALTER TABLE legal_documents DROP COLUMN IF EXISTS is_mobile_visible;
ALTER TABLE legal_documents DROP COLUMN IF EXISTS language;
ALTER TABLE legal_documents DROP COLUMN IF EXISTS ai_context;

# Sortir de MySQL
exit;

# Relancer les migrations
php artisan migrate
```

#### **Option B : Marquer la migration comme exécutée**

Si les colonnes existent déjà et sont correctes :

```bash
# Marquer la migration comme exécutée sans l'exécuter
php artisan migrate --pretend

# Ou insérer manuellement dans la table migrations
mysql -u root -p dossy_pro -e "INSERT INTO migrations (migration, batch) VALUES ('2025_12_18_000001_add_country_to_legal_library_tables', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations m));"
```

---

## 🔍 VÉRIFICATION DE LA CORRECTION

Après avoir appliqué la correction, vérifiez que tout fonctionne :

### **1. Vérifier les Tables**

```bash
mysql -u root -p dossy_pro

# Vérifier la structure de legal_categories
DESCRIBE legal_categories;

# Devrait afficher :
# id, name, description, slug, country, is_mobile_visible, sort_order, created_by, created_at, updated_at

# Vérifier la structure de legal_documents
DESCRIBE legal_documents;

# Devrait afficher :
# id, category_id, country, title, content, file_path, is_mobile_visible, language, ai_context, ...

exit;
```

### **2. Vérifier les Index**

```bash
mysql -u root -p dossy_pro

# Vérifier les index de legal_categories
SHOW INDEX FROM legal_categories;

# Devrait inclure :
# legal_categories_country_index
# legal_categories_is_mobile_visible_index

# Vérifier les index de legal_documents
SHOW INDEX FROM legal_documents;

# Devrait inclure :
# legal_documents_country_index
# legal_documents_is_mobile_visible_index
# legal_documents_language_index

exit;
```

### **3. Vérifier le Statut des Migrations**

```bash
php artisan migrate:status
```

**Résultat attendu** : Toutes les migrations doivent afficher `Ran` :

```
+------+------------------------------------------------------------+-------+
| Ran? | Migration                                                  | Batch |
+------+------------------------------------------------------------+-------+
| Yes  | 2024_11_15_000001_create_legal_categories_table            | 1     |
| Yes  | 2025_12_18_000001_add_country_to_legal_library_tables      | 2     |
| Yes  | 2024_12_18_000001_create_fcm_tokens_table                  | 3     |
+------+------------------------------------------------------------+-------+
```

---

## 📋 AUTRES ERREURS COURANTES ET SOLUTIONS

### **Erreur 1 : Class Not Found**

**Message** :
```
Class 'App\Models\FcmToken' not found
```

**Solution** :
```bash
# Regénérer l'autoload
composer dump-autoload

# Nettoyer le cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

### **Erreur 2 : Foreign Key Constraint Fails**

**Message** :
```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails
```

**Solution** :
```bash
# Désactiver temporairement les contraintes de clés étrangères
mysql -u root -p dossy_pro -e "SET FOREIGN_KEY_CHECKS=0;"

# Exécuter les migrations
php artisan migrate

# Réactiver les contraintes
mysql -u root -p dossy_pro -e "SET FOREIGN_KEY_CHECKS=1;"
```

---

### **Erreur 3 : Table Already Exists**

**Message** :
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'users' already exists
```

**Solution** :
```bash
# Option 1 : Rollback et relancer
php artisan migrate:rollback
php artisan migrate

# Option 2 : Fresh migration (SUPPRIME TOUTES LES DONNÉES)
php artisan migrate:fresh
```

---

### **Erreur 4 : Column Not Found**

**Message** :
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'xyz' in 'field list'
```

**Solution** :
```bash
# Vérifier la structure de la table
mysql -u root -p dossy_pro -e "DESCRIBE table_name;"

# Si la colonne manque, l'ajouter manuellement
mysql -u root -p dossy_pro -e "ALTER TABLE table_name ADD COLUMN xyz VARCHAR(255);"

# Ou relancer les migrations
php artisan migrate:fresh
```

---

### **Erreur 5 : Access Denied for User**

**Message** :
```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'
```

**Solution** :
```bash
# Vérifier les credentials dans .env
cat .env | grep DB_

# Devrait afficher :
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=dossy_pro
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Tester la connexion MySQL
mysql -u root -p

# Si échec, réinitialiser le mot de passe MySQL
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'new_password';
FLUSH PRIVILEGES;
exit;
```

---

## 🛠️ COMMANDES UTILES

### **Gestion des Migrations**

```bash
# Voir le statut de toutes les migrations
php artisan migrate:status

# Exécuter les migrations en attente
php artisan migrate

# Rollback de la dernière batch
php artisan migrate:rollback

# Rollback de toutes les migrations
php artisan migrate:reset

# Fresh migration (SUPPRIME TOUT)
php artisan migrate:fresh

# Fresh migration + seeders
php artisan migrate:fresh --seed

# Rollback et re-migrer
php artisan migrate:refresh

# Voir le SQL sans exécuter
php artisan migrate --pretend
```

### **Gestion du Cache**

```bash
# Nettoyer tous les caches
php artisan optimize:clear

# Ou individuellement :
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Regénérer les fichiers de cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### **Vérification Base de Données**

```bash
# Se connecter à MySQL
mysql -u root -p dossy_pro

# Lister toutes les tables
SHOW TABLES;

# Voir la structure d'une table
DESCRIBE table_name;

# Voir les index d'une table
SHOW INDEX FROM table_name;

# Voir les contraintes de clés étrangères
SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'table_name';

# Sortir de MySQL
exit;
```

---

## 📞 AIDE SUPPLÉMENTAIRE

Si vous rencontrez d'autres erreurs :

1. **Vérifier les logs Laravel** :
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Activer le mode debug** :
   Dans `.env`, mettre :
   ```env
   APP_DEBUG=true
   ```

3. **Vérifier les permissions** :
   ```bash
   sudo chmod -R 775 storage
   sudo chmod -R 775 bootstrap/cache
   sudo chown -R www-data:www-data storage
   sudo chown -R www-data:www-data bootstrap/cache
   ```

4. **Consulter la documentation** :
   - Voir `NOTIFICATIONS_PUSH_OFFLINE_GUIDE.md`
   - Voir `ALERTES_GUIDE_ACTIVATION.md`
   - Voir `PROJECT_COMPLETION_FINAL.md`

---

## ✅ CHECKLIST DE VÉRIFICATION POST-MIGRATION

Après avoir résolu l'erreur, vérifiez :

- [ ] Toutes les migrations affichent `Ran` dans `php artisan migrate:status`
- [ ] Les tables `legal_categories` et `legal_documents` existent
- [ ] Les colonnes `country`, `is_mobile_visible`, etc. sont présentes
- [ ] Les index sont créés correctement
- [ ] L'application fonctionne sans erreur 500
- [ ] Les routes API fonctionnent : `/api/mobile/templates`, `/api/mobile/fcm-token`, etc.
- [ ] Le queue worker fonctionne : `php artisan queue:work`
- [ ] Le scheduler fonctionne : `php artisan schedule:list`

---

**Document créé le** : 18/12/2024  
**Version** : 1.0  
**Auteur** : GenSpark AI Developer  
**Projet** : Dossy Pro - Legal Management System

---

**✅ ERREUR CORRIGÉE ET PUSHÉE SUR GITHUB !**

Commit : `b79e6f6c`  
Branch : `genspark_ai_developer`  
Repository : https://github.com/stealbass/doss
