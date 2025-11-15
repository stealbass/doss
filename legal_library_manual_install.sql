-- ============================================================================
-- BIBLIOTHÈQUE JURIDIQUE - INSTALLATION MANUELLE
-- Pour Dossy Pro - À exécuter dans phpMyAdmin
-- ============================================================================

-- 1. CRÉATION DE LA TABLE legal_categories
-- ============================================================================

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


-- 2. CRÉATION DE LA TABLE legal_documents
-- ============================================================================

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


-- 3. PERMISSIONS
-- ============================================================================

-- Insérer les permissions
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) 
VALUES 
  ('manage legal library', 'web', NOW(), NOW()),
  ('view legal library', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();


-- Attribuer les permissions au rôle 'company' (admin)
-- ============================================================================

-- Récupérer l'ID du rôle company
SET @company_role_id = (SELECT id FROM roles WHERE name = 'company' LIMIT 1);

-- Attribuer toutes les permissions de la bibliothèque juridique
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, @company_role_id
FROM permissions p
WHERE p.name IN ('manage legal library', 'view legal library')
AND @company_role_id IS NOT NULL;


-- Attribuer 'view legal library' aux autres rôles
-- ============================================================================

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM permissions p
CROSS JOIN roles r
WHERE p.name = 'view legal library'
  AND r.name IN ('advocate', 'client', 'co advocate', 'team leader');


-- 4. ENREGISTRER LES MIGRATIONS (optionnel)
-- ============================================================================

-- Calculer le prochain batch number
SET @next_batch = (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations);

-- Ajouter les migrations
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('2024_11_15_000001_create_legal_categories_table', @next_batch),
('2024_11_15_000002_create_legal_documents_table', @next_batch),
('2024_11_15_000003_add_legal_library_permissions', @next_batch);


-- ============================================================================
-- VÉRIFICATION
-- ============================================================================

-- Vérifier que les tables ont été créées
SELECT 
  'legal_categories' AS table_name, 
  COUNT(*) AS column_count 
FROM information_schema.columns 
WHERE table_name = 'legal_categories' 
  AND table_schema = DATABASE()
UNION ALL
SELECT 
  'legal_documents' AS table_name, 
  COUNT(*) AS column_count 
FROM information_schema.columns 
WHERE table_name = 'legal_documents' 
  AND table_schema = DATABASE();

-- Vérifier les permissions
SELECT * FROM permissions WHERE name LIKE '%legal library%';

-- Vérifier les assignments de rôles
SELECT r.name AS role_name, p.name AS permission_name
FROM role_has_permissions rhp
JOIN roles r ON r.id = rhp.role_id
JOIN permissions p ON p.id = rhp.permission_id
WHERE p.name LIKE '%legal library%'
ORDER BY r.name, p.name;


-- ============================================================================
-- INSTALLATION TERMINÉE !
-- ============================================================================
-- 
-- PROCHAINES ÉTAPES :
-- 
-- 1. Créer le répertoire de stockage via FTP :
--    storage/app/public/legal_documents/
--    Permissions : 775
--
-- 2. Créer le lien symbolique :
--    public/storage → ../storage/app/public
--
-- 3. Tester l'accès :
--    Admin  : https://votre-domaine.com/legal-library
--    Users  : https://votre-domaine.com/library
--
-- ============================================================================
