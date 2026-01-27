-- ============================================================
-- MIGRATION SQL: Ajout de pinecone_host et pinecone_verify_ssl
-- Table: mobile_app_settings
-- Date: 2026-01-21
-- ============================================================

-- 1. Vérifier la structure actuelle de la table
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'threesixty_dossypro_legal_new'
  AND TABLE_NAME = 'mobile_app_settings'
  AND COLUMN_NAME IN ('pinecone_api_key', 'pinecone_environment', 'pinecone_index_name', 'pinecone_host', 'pinecone_verify_ssl');

-- ============================================================
-- 2. AJOUTER LES NOUVELLES COLONNES
-- ============================================================

-- Ajouter la colonne pinecone_host (après pinecone_environment)
ALTER TABLE `mobile_app_settings` 
ADD COLUMN `pinecone_host` TEXT NULL 
AFTER `pinecone_environment`;

-- Ajouter la colonne pinecone_verify_ssl (après pinecone_host)
ALTER TABLE `mobile_app_settings` 
ADD COLUMN `pinecone_verify_ssl` TINYINT(1) NOT NULL DEFAULT 1 
AFTER `pinecone_host`;

-- ============================================================
-- 3. CONFIGURER LA VALEUR CORRECTE DU HOST
-- ============================================================

-- Mettre à jour avec le host correct (celui du dashboard Pinecone)
UPDATE `mobile_app_settings` 
SET 
    `pinecone_host` = 'dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io',
    `pinecone_verify_ssl` = 1
WHERE `id` = 1;

-- ============================================================
-- 4. VERIFICATION POST-MIGRATION
-- ============================================================

-- Vérifier que les colonnes ont été ajoutées
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'threesixty_dossypro_legal_new'
  AND TABLE_NAME = 'mobile_app_settings'
  AND COLUMN_NAME IN ('pinecone_host', 'pinecone_verify_ssl');

-- Vérifier les valeurs actuelles de la configuration Pinecone
SELECT 
    id,
    CONCAT(LEFT(pinecone_api_key, 20), '...', RIGHT(pinecone_api_key, 10)) AS 'API Key (masked)',
    pinecone_environment AS 'Environment',
    pinecone_index_name AS 'Index Name',
    pinecone_host AS 'Host',
    pinecone_verify_ssl AS 'Verify SSL',
    CASE 
        WHEN pinecone_host IS NULL THEN '❌ NULL - Utilisera .env ou construction auto'
        WHEN pinecone_host = '' THEN '❌ VIDE - Utilisera .env ou construction auto'
        WHEN pinecone_host LIKE '%7udtg21%' AND pinecone_host LIKE '%b74a%' THEN '✅ FORMAT CORRECT'
        ELSE '⚠️ FORMAT INCORRECT'
    END AS 'Statut Host'
FROM mobile_app_settings
ORDER BY id DESC
LIMIT 1;

-- ============================================================
-- 5. ROLLBACK (Si nécessaire - À utiliser seulement en cas de problème)
-- ============================================================

-- ATTENTION: Décommentez ces lignes UNIQUEMENT pour annuler la migration
-- ALTER TABLE `mobile_app_settings` DROP COLUMN `pinecone_verify_ssl`;
-- ALTER TABLE `mobile_app_settings` DROP COLUMN `pinecone_host`;

-- ============================================================
-- NOTES IMPORTANTES:
-- ============================================================

-- ✅ AVANT EXECUTION:
--    - Sauvegarder la base de données
--    - Vérifier que vous êtes sur la bonne base: threesixty_dossypro_legal_new

-- ✅ FORMAT DU HOST:
--    Le host doit contenir les suffixes uniques:
--    - Suffixe index: -7udtg21
--    - Suffixe environnement: -b74a
--    Format complet: dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io

-- ✅ APRES EXECUTION:
--    1. Vérifier l'interface admin (nouveaux champs visibles)
--    2. Tester une connexion Pinecone (plus d'erreur DNS)
--    3. Vider les caches Laravel:
--       php artisan config:clear
--       php artisan cache:clear

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================
