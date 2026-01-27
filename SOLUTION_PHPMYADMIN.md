# 🔧 SOLUTION : Mise à Jour Manuelle via phpMyAdmin

## ❌ Problème
```
SQLSTATE[42S02]: Base table or view not found: 1050 Table 'mobile_subscription_plans' already exists
```

Et d'autres tables également manquantes ou en conflit.

## ✅ Solution Manuelle via phpMyAdmin

### 🎯 OPTION 1 : Réinitialisation Complète (RECOMMANDÉ)

Cette solution supprime TOUTES les tables et les recrée proprement.

#### Étape 1 : Accéder à phpMyAdmin
1. Connectez-vous à : https://dossypro.com/phpmyadmin (ou votre URL phpMyAdmin)
2. Sélectionnez votre base de données (probablement `dossypro_db` ou similaire)

#### Étape 2 : Sauvegarder (IMPORTANT)
1. Cliquez sur l'onglet **"Exporter"**
2. Méthode : **Rapide**
3. Format : **SQL**
4. Cliquez sur **"Exécuter"**
5. Sauvegardez le fichier `.sql` sur votre ordinateur

#### Étape 3 : Supprimer TOUTES les tables
1. Cliquez sur l'onglet **"Structure"**
2. Cochez **"Tout cocher"** en bas de la liste des tables
3. Dans le menu déroulant **"Pour la sélection:"**, choisissez **"Supprimer"**
4. Confirmez la suppression

#### Étape 4 : Réinitialiser la table migrations
Cette table garde la trace des migrations exécutées. Elle doit être supprimée aussi.

Si elle existe encore :
```sql
DROP TABLE IF EXISTS `migrations`;
```

#### Étape 5 : Exécuter les migrations depuis le terminal
Maintenant que la base est vide, exécutez :
```bash
php artisan migrate:fresh
php artisan db:seed
```

---

### 🎯 OPTION 2 : Suppression Sélective (Si vous voulez garder certaines données)

Si vous avez des données importantes dans certaines tables :

#### Étape 1 : Identifier les tables en conflit
Les tables mentionnées dans l'erreur :
- `mobile_subscription_plans`
- `mobile_subscrption_plans` (typo)
- Autres tables créées manuellement

#### Étape 2 : Supprimer UNIQUEMENT les tables en conflit
Dans phpMyAdmin, onglet **SQL**, exécutez :

```sql
-- Supprimer les tables en conflit
DROP TABLE IF EXISTS `mobile_subscription_plans`;
DROP TABLE IF EXISTS `mobile_subscrption_plans`;
DROP TABLE IF EXISTS `passes_templates`;
DROP TABLE IF EXISTS `passes_legal_resources`;
DROP TABLE IF EXISTS `fcm_tokens`;

-- Supprimer les entrées de migration correspondantes
DELETE FROM `migrations` 
WHERE migration LIKE '%create_mobile_subscription_plans%'
   OR migration LIKE '%create_fcm_tokens%'
   OR migration LIKE '%add_country_to_legal_library_tables%';
```

#### Étape 3 : Re-exécuter les migrations
```bash
php artisan migrate
```

---

### 🎯 OPTION 3 : Correction Manuelle des Tables Existantes

Si vous voulez garder les données ET corriger la structure :

#### Étape 1 : Vérifier si les colonnes existent
Dans phpMyAdmin, onglet **SQL** :

```sql
-- Vérifier la structure de legal_categories
SHOW COLUMNS FROM `legal_categories` LIKE 'country';

-- Vérifier la structure de legal_documents
SHOW COLUMNS FROM `legal_documents` LIKE 'country';
```

#### Étape 2 : Ajouter les colonnes manquantes (si elles n'existent pas)

**Pour legal_categories :**
```sql
-- Ajouter country (si elle n'existe pas)
ALTER TABLE `legal_categories` 
ADD COLUMN `country` VARCHAR(100) NULL AFTER `slug`,
ADD INDEX `legal_categories_country_index` (`country`);

-- Ajouter is_mobile_visible (si elle n'existe pas)
ALTER TABLE `legal_categories` 
ADD COLUMN `is_mobile_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `slug`,
ADD INDEX `legal_categories_is_mobile_visible_index` (`is_mobile_visible`);

-- Ajouter sort_order (si elle n'existe pas)
ALTER TABLE `legal_categories` 
ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `slug`;
```

**Pour legal_documents :**
```sql
-- Ajouter country (si elle n'existe pas)
ALTER TABLE `legal_documents` 
ADD COLUMN `country` VARCHAR(100) NULL AFTER `category_id`,
ADD INDEX `legal_documents_country_index` (`country`);

-- Ajouter is_mobile_visible (si elle n'existe pas)
ALTER TABLE `legal_documents` 
ADD COLUMN `is_mobile_visible` TINYINT(1) NOT NULL DEFAULT 1,
ADD INDEX `legal_documents_is_mobile_visible_index` (`is_mobile_visible`);

-- Ajouter language (si elle n'existe pas)
ALTER TABLE `legal_documents` 
ADD COLUMN `language` VARCHAR(10) NOT NULL DEFAULT 'fr',
ADD INDEX `legal_documents_language_index` (`language`);

-- Ajouter ai_context (si elle n'existe pas)
ALTER TABLE `legal_documents` 
ADD COLUMN `ai_context` TEXT NULL;
```

#### Étape 3 : Marquer la migration comme exécutée
```sql
-- Marquer la migration comme exécutée
INSERT INTO `migrations` (`migration`, `batch`) 
VALUES ('2025_12_18_000001_add_country_to_legal_library_tables', 
        (SELECT MAX(batch) FROM (SELECT batch FROM migrations) AS m) + 1);
```

---

### 🎯 OPTION 4 : Script SQL Complet de Nettoyage

Copiez ce script dans phpMyAdmin → onglet **SQL** :

```sql
-- ============================================
-- SCRIPT DE NETTOYAGE COMPLET
-- ============================================

-- 1. Supprimer les tables en conflit
DROP TABLE IF EXISTS `mobile_subscription_plans`;
DROP TABLE IF EXISTS `mobile_subscrption_plans`;
DROP TABLE IF EXISTS `fcm_tokens`;

-- 2. Nettoyer la table migrations
DELETE FROM `migrations` 
WHERE migration LIKE '%mobile_subscription_plans%'
   OR migration LIKE '%fcm_tokens%'
   OR migration LIKE '%add_country_to_legal_library_tables%';

-- 3. Vérifier et ajouter les colonnes manquantes à legal_categories
-- (Exécutez UNIQUEMENT si les colonnes n'existent pas)

-- Vérifier d'abord avec : SHOW COLUMNS FROM legal_categories;

-- Si country n'existe pas :
-- ALTER TABLE `legal_categories` ADD COLUMN `country` VARCHAR(100) NULL AFTER `slug`;
-- ALTER TABLE `legal_categories` ADD INDEX `legal_categories_country_index` (`country`);

-- Si is_mobile_visible n'existe pas :
-- ALTER TABLE `legal_categories` ADD COLUMN `is_mobile_visible` TINYINT(1) DEFAULT 1 AFTER `slug`;
-- ALTER TABLE `legal_categories` ADD INDEX `legal_categories_is_mobile_visible_index` (`is_mobile_visible`);

-- Si sort_order n'existe pas :
-- ALTER TABLE `legal_categories` ADD COLUMN `sort_order` INT DEFAULT 0 AFTER `slug`;

-- 4. Vérifier et ajouter les colonnes manquantes à legal_documents
-- (Exécutez UNIQUEMENT si les colonnes n'existent pas)

-- Vérifier d'abord avec : SHOW COLUMNS FROM legal_documents;

-- Si country n'existe pas :
-- ALTER TABLE `legal_documents` ADD COLUMN `country` VARCHAR(100) NULL AFTER `category_id`;
-- ALTER TABLE `legal_documents` ADD INDEX `legal_documents_country_index` (`country`);

-- Si is_mobile_visible n'existe pas :
-- ALTER TABLE `legal_documents` ADD COLUMN `is_mobile_visible` TINYINT(1) DEFAULT 1;
-- ALTER TABLE `legal_documents` ADD INDEX `legal_documents_is_mobile_visible_index` (`is_mobile_visible`);

-- Si language n'existe pas :
-- ALTER TABLE `legal_documents` ADD COLUMN `language` VARCHAR(10) DEFAULT 'fr';
-- ALTER TABLE `legal_documents` ADD INDEX `legal_documents_language_index` (`language`);

-- Si ai_context n'existe pas :
-- ALTER TABLE `legal_documents` ADD COLUMN `ai_context` TEXT NULL;

-- ============================================
-- FIN DU SCRIPT
-- ============================================

SELECT 'Nettoyage terminé !' AS message;
```

---

## 📋 Workflow Recommandé

### Pour DÉVELOPPEMENT (pas de données importantes) :

1. **phpMyAdmin** → Sauvegarder la base
2. **phpMyAdmin** → Supprimer TOUTES les tables
3. **Terminal** → `php artisan migrate:fresh && php artisan db:seed`
4. ✅ **Terminé !**

### Pour PRODUCTION (avec données importantes) :

1. **phpMyAdmin** → Sauvegarder la base (OBLIGATOIRE)
2. **phpMyAdmin** → Exécuter le script SQL de nettoyage (Option 4)
3. **Terminal** → `php artisan migrate`
4. **Vérifier** que tout fonctionne
5. ✅ **Terminé !**

---

## 🆘 En Cas de Problème

### Erreur : "Table already exists"
```sql
DROP TABLE IF EXISTS `nom_de_la_table`;
```
Puis re-exécutez `php artisan migrate`

### Erreur : "Column already exists"
```sql
-- Vérifier si la colonne existe
SHOW COLUMNS FROM `nom_de_la_table` LIKE 'nom_colonne';

-- Si elle existe, la supprimer d'abord
ALTER TABLE `nom_de_la_table` DROP COLUMN `nom_colonne`;
```

### Restaurer la Sauvegarde
1. phpMyAdmin → Onglet **"Importer"**
2. Sélectionnez votre fichier `.sql`
3. Cliquez sur **"Exécuter"**

---

## ✅ Vérification Post-Correction

### Dans phpMyAdmin
```sql
-- Vérifier les colonnes de legal_categories
SHOW COLUMNS FROM `legal_categories`;

-- Vérifier les colonnes de legal_documents
SHOW COLUMNS FROM `legal_documents`;

-- Vérifier les migrations exécutées
SELECT * FROM `migrations` ORDER BY `id` DESC LIMIT 10;
```

### Dans le Terminal
```bash
php artisan migrate:status
```

Toutes les migrations doivent afficher **"Ran"**.

---

## 🔗 Documentation Connexe

- **Guide complet** : `MIGRATION_FIX_FINAL.md`
- **Action immédiate** : `ACTION_IMMEDIATE.txt`
- **Toutes les erreurs** : `GUIDE_ERREURS_COMPLET.txt`

---

**Date** : 19/12/2024  
**Status** : Solution Manuelle Prête  
**Recommandation** : Utilisez OPTION 1 pour un résultat propre
