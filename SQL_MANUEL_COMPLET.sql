-- ============================================================================
-- SCRIPT SQL COMPLET POUR CORRIGER LA BASE DE DONNÉES MANUELLEMENT
-- DOSSY PRO - 19 Décembre 2024
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. CRÉER LA TABLE fcm_tokens (pour notifications push)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `fcm_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `device_type` VARCHAR(50) NULL DEFAULT 'android',
  `device_id` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fcm_tokens_token_unique` (`token`),
  KEY `fcm_tokens_user_id_foreign` (`user_id`),
  KEY `fcm_tokens_is_active_index` (`is_active`),
  CONSTRAINT `fcm_tokens_user_id_foreign` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 2. CRÉER LA TABLE mobile_subscription_plans
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `mobile_subscription_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'XOF',
  `duration_days` INT NOT NULL DEFAULT 30,
  `features` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile_subscription_plans_slug_unique` (`slug`),
  KEY `mobile_subscription_plans_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 3. AJOUTER LES COLONNES MANQUANTES À legal_categories
-- ----------------------------------------------------------------------------

-- Vérifier et ajouter 'country'
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_categories' 
  AND column_name = 'country';

SET @query = IF(@col_exists = 0,
  'ALTER TABLE `legal_categories` ADD COLUMN `country` VARCHAR(100) NULL AFTER `slug`, ADD INDEX `legal_categories_country_index` (`country`)',
  'SELECT "Column country already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter 'is_mobile_visible'
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_categories' 
  AND column_name = 'is_mobile_visible';

SET @query = IF(@col_exists = 0,
  'ALTER TABLE `legal_categories` ADD COLUMN `is_mobile_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `slug`, ADD INDEX `legal_categories_is_mobile_visible_index` (`is_mobile_visible`)',
  'SELECT "Column is_mobile_visible already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter 'sort_order'
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_categories' 
  AND column_name = 'sort_order';

SET @query = IF(@col_exists = 0,
  'ALTER TABLE `legal_categories` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `slug`',
  'SELECT "Column sort_order already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ----------------------------------------------------------------------------
-- 4. AJOUTER LES COLONNES MANQUANTES À legal_documents
-- ----------------------------------------------------------------------------

-- Vérifier et ajouter 'country'
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_documents' 
  AND column_name = 'country';

SET @query = IF(@col_exists = 0,
  'ALTER TABLE `legal_documents` ADD COLUMN `country` VARCHAR(100) NULL AFTER `category_id`, ADD INDEX `legal_documents_country_index` (`country`)',
  'SELECT "Column country already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter 'is_mobile_visible'
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_documents' 
  AND column_name = 'is_mobile_visible';

SET @query = IF(@col_exists = 0,
  'ALTER TABLE `legal_documents` ADD COLUMN `is_mobile_visible` TINYINT(1) NOT NULL DEFAULT 1, ADD INDEX `legal_documents_is_mobile_visible_index` (`is_mobile_visible`)',
  'SELECT "Column is_mobile_visible already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter 'language'
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_documents' 
  AND column_name = 'language';

SET @query = IF(@col_exists = 0,
  'ALTER TABLE `legal_documents` ADD COLUMN `language` VARCHAR(10) NOT NULL DEFAULT ''fr'', ADD INDEX `legal_documents_language_index` (`language`)',
  'SELECT "Column language already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter 'ai_context'
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_documents' 
  AND column_name = 'ai_context';

SET @query = IF(@col_exists = 0,
  'ALTER TABLE `legal_documents` ADD COLUMN `ai_context` TEXT NULL',
  'SELECT "Column ai_context already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ----------------------------------------------------------------------------
-- 5. MARQUER LES MIGRATIONS COMME EXÉCUTÉES
-- ----------------------------------------------------------------------------

-- Récupérer le dernier batch
SET @last_batch = (SELECT IFNULL(MAX(batch), 0) FROM `migrations`);
SET @new_batch = @last_batch + 1;

-- Ajouter fcm_tokens migration
INSERT IGNORE INTO `migrations` (`migration`, `batch`) 
VALUES ('2024_12_18_000001_create_fcm_tokens_table', @new_batch);

-- Ajouter mobile_subscription_plans migration
INSERT IGNORE INTO `migrations` (`migration`, `batch`) 
VALUES ('2024_11_20_000001_create_mobile_subscription_plans_table', @new_batch);

-- Ajouter country migration
INSERT IGNORE INTO `migrations` (`migration`, `batch`) 
VALUES ('2025_12_18_000001_add_country_to_legal_library_tables', @new_batch);


-- ----------------------------------------------------------------------------
-- 6. VÉRIFICATION FINALE
-- ----------------------------------------------------------------------------

-- Vérifier les tables créées
SELECT 
  'Tables créées' AS verification,
  COUNT(*) AS count 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
  AND table_name IN ('fcm_tokens', 'mobile_subscription_plans');

-- Vérifier les colonnes de legal_categories
SELECT 
  'Colonnes legal_categories' AS verification,
  GROUP_CONCAT(column_name ORDER BY ordinal_position) AS columns
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_categories';

-- Vérifier les colonnes de legal_documents
SELECT 
  'Colonnes legal_documents' AS verification,
  GROUP_CONCAT(column_name ORDER BY ordinal_position) AS columns
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'legal_documents';

-- Vérifier les migrations
SELECT 
  'Migrations ajoutées' AS verification,
  COUNT(*) AS count
FROM `migrations` 
WHERE migration IN (
  '2024_12_18_000001_create_fcm_tokens_table',
  '2024_11_20_000001_create_mobile_subscription_plans_table',
  '2025_12_18_000001_add_country_to_legal_library_tables'
);

-- ============================================================================
-- FIN DU SCRIPT
-- ============================================================================

SELECT '✅ Script terminé avec succès !' AS message;
