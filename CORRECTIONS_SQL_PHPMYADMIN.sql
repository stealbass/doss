-- ============================================================================
-- CORRECTIONS SQL POUR DOSSY PRO - ERREURS 500
-- À exécuter via phpMyAdmin
-- Date: 2024-12-22
-- ============================================================================

-- ============================================================================
-- 1. CORRECTION: mobile-app-plans (colonne plan_id manquante)
-- ============================================================================
-- Erreur: SQLSTATE[42S22]: Column not found: 1054 Unknown column 
-- 'mobile_app_subscriptions.plan_id' in 'ON'

ALTER TABLE `mobile_app_subscriptions` 
ADD COLUMN `plan_id` BIGINT UNSIGNED NULL AFTER `id`,
ADD INDEX `idx_plan_id` (`plan_id`);

-- ============================================================================
-- 2. CORRECTION: calculators (colonne created_at manquante)
-- ============================================================================
-- Erreur: SQLSTATE[42S22]: Column not found: 1054 Unknown column 
-- 'created_at' in 'WHERE'

ALTER TABLE `calculator_logs` 
ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL;

-- ============================================================================
-- 3. VÉRIFICATION: Routes manquantes pour fiscal-resources
-- ============================================================================
-- Créer la table fiscal_social_resources si elle n'existe pas

CREATE TABLE IF NOT EXISTS `fiscal_social_resources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `country` VARCHAR(2) NULL,
  `resource_type` VARCHAR(50) NULL COMMENT 'fiscal, social, mixed',
  `year` INT NULL,
  `file_path` VARCHAR(255) NULL,
  `is_mobile_visible` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_country` (`country`),
  INDEX `idx_resource_type` (`resource_type`),
  INDEX `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. VÉRIFICATION: Table calculators
-- ============================================================================
-- Créer la table calculator_configs si elle n'existe pas

CREATE TABLE IF NOT EXISTS `calculator_configs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `calculator_type` VARCHAR(50) NOT NULL,
  `country` VARCHAR(2) NULL,
  `config_data` JSON NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `is_mobile_visible` TINYINT(1) DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_calculator_type` (`calculator_type`),
  INDEX `idx_country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. VÉRIFICATION: Table template_categories pour document-templates
-- ============================================================================
-- Créer la table template_categories si elle n'existe pas

CREATE TABLE IF NOT EXISTS `template_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(100) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. VÉRIFICATION: Migration pour Legal Library (country, is_mobile_visible, sort_order)
-- ============================================================================
-- Ajouter les colonnes country, is_mobile_visible et sort_order si elles n'existent pas

-- Pour legal_categories
SET @dbname = DATABASE();
SET @tablename = 'legal_categories';
SET @columnname = 'country';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " VARCHAR(2) NULL AFTER `description`;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ajouter is_mobile_visible
SET @columnname = 'is_mobile_visible';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " TINYINT(1) DEFAULT 1 AFTER `country`;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ajouter sort_order
SET @columnname = 'sort_order';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " INT DEFAULT 0 AFTER `is_mobile_visible`;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Pour legal_documents
SET @tablename = 'legal_documents';
SET @columnname = 'country';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " VARCHAR(2) NULL;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ajouter is_mobile_visible
SET @columnname = 'is_mobile_visible';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " TINYINT(1) DEFAULT 1;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ajouter language
SET @columnname = 'language';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " VARCHAR(5) DEFAULT 'fr';")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================================
-- FIN DES CORRECTIONS SQL
-- ============================================================================
-- 
-- INSTRUCTIONS D'EXÉCUTION:
-- 1. Ouvrez phpMyAdmin
-- 2. Sélectionnez votre base de données (probablement 'dossypro_db' ou similaire)
-- 3. Cliquez sur l'onglet "SQL"
-- 4. Copiez-collez TOUT ce fichier
-- 5. Cliquez sur "Exécuter"
-- 6. Vérifiez qu'il n'y a pas d'erreurs
-- 7. Videz le cache Laravel: https://dossypro.com/clear-cache.php?token=DOSSY2024CLEAR
-- 8. Testez les URLs
--
-- ============================================================================
