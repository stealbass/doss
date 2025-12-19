# 🔧 SOLUTION : Erreur "Duplicate column name 'country'"

## ❌ Problème
```
SQLSTATE[23000]: Integrity constraint violation: 1060 Duplicate column name 'country'
```

## ✅ Solution Immédiate (3 Options)

### **Option 1 : Réinitialiser les Migrations (RECOMMANDÉ pour DEV)**

```bash
# 1. Sauvegarder les données importantes
php artisan db:seed --class=BackupSeeder  # Si vous avez des données importantes

# 2. Réinitialiser toutes les migrations
php artisan migrate:fresh

# 3. Réexécuter les seeders
php artisan db:seed
```

⚠️ **ATTENTION** : Ceci supprime TOUTES les données. À utiliser uniquement en développement.

---

### **Option 2 : Rollback Ciblé (SÛRE)**

```bash
# 1. Voir l'historique des migrations
php artisan migrate:status

# 2. Rollback la dernière migration
php artisan migrate:rollback --step=1

# 3. Réexécuter la migration (maintenant corrigée)
php artisan migrate
```

---

### **Option 3 : Correction Manuelle de la Base de Données**

#### **Méthode A : Via phpMyAdmin/MySQL**

1. Connectez-vous à votre base de données
2. Exécutez cette requête SQL :

```sql
-- Vérifier si la colonne existe
SHOW COLUMNS FROM legal_categories LIKE 'country';

-- Si elle existe déjà, marquez la migration comme exécutée
-- Trouvez d'abord le nom de la migration
SELECT * FROM migrations WHERE migration LIKE '%add_country_to_legal_library_tables%';

-- Si elle n'existe pas dans la table 'migrations', ajoutez-la manuellement
INSERT INTO migrations (migration, batch) 
VALUES ('2025_12_18_000001_add_country_to_legal_library_tables', 
        (SELECT MAX(batch) FROM migrations) + 1);
```

#### **Méthode B : Via Artisan Tinker**

```bash
php artisan tinker
```

Puis dans Tinker :
```php
// Vérifier si la colonne existe
Schema::hasColumn('legal_categories', 'country');

// Si TRUE (colonne existe), marquez la migration comme exécutée
DB::table('migrations')->insert([
    'migration' => '2025_12_18_000001_add_country_to_legal_library_tables',
    'batch' => DB::table('migrations')->max('batch') + 1
]);

exit
```

---

## 🔍 Pourquoi cette Erreur ?

La migration `2025_12_18_000001_add_country_to_legal_library_tables.php` essaie d'ajouter la colonne `country` à `legal_categories`, mais :

1. **La colonne existe déjà** (probablement ajoutée manuellement ou par une ancienne migration)
2. **La migration n'était pas "idempotente"** (elle ne vérifiait pas si la colonne existait)

## ✅ Correction Appliquée

J'ai modifié la migration pour qu'elle vérifie d'abord si les colonnes existent :

```php
// AVANT (causait l'erreur)
$table->string('country', 100)->nullable()->after('slug');

// APRÈS (vérifie d'abord)
if (!Schema::hasColumn('legal_categories', 'country')) {
    $table->string('country', 100)->nullable()->after('slug');
}
```

---

## 📋 Étapes de Vérification

Après avoir appliqué une solution, vérifiez :

```bash
# 1. Voir le statut des migrations
php artisan migrate:status

# 2. Vérifier la structure de la table
php artisan tinker
Schema::getColumnListing('legal_categories');
exit

# 3. Tester la migration
php artisan migrate --pretend
```

---

## 🚀 Pour la Production

**SI VOUS ÊTES EN PRODUCTION**, utilisez **UNIQUEMENT l'Option 3** (correction manuelle) :

```bash
# 1. Sauvegarder la base de données
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Vérifier si la colonne existe
mysql -u username -p database_name -e "SHOW COLUMNS FROM legal_categories LIKE 'country';"

# 3. Si la colonne existe, marquer la migration comme exécutée
mysql -u username -p database_name -e "INSERT INTO migrations (migration, batch) VALUES ('2025_12_18_000001_add_country_to_legal_library_tables', (SELECT MAX(batch) FROM (SELECT batch FROM migrations) AS m) + 1);"
```

---

## 📞 Support

- **Fichier modifié** : `database/migrations/2025_12_18_000001_add_country_to_legal_library_tables.php`
- **Commit GitHub** : `fix: Prevent duplicate column errors in migrations`
- **Branch** : `genspark_ai_developer`
- **Documentation** : `GUIDE_RESOLUTION_ERREURS_MIGRATION.md`

---

**Mis à jour** : 19/12/2024  
**Status** : ✅ Correction Disponible
