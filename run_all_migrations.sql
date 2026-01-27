-- ============================================
-- SCRIPT COMPLET - Toutes les Migrations RAG & Anonymisation
-- Date: 2026-01-06
-- ============================================
-- Ce script exécute toutes les migrations nécessaires dans le bon ordre
-- Pour l'utilisation: copier-coller dans phpMyAdmin ou MySQL CLI
-- ============================================

-- ============================================
-- MIGRATION 1/2: Update Processing Status Default
-- ============================================
-- Modifier la valeur par défaut de processing_status de 'completed' à 'pending'
ALTER TABLE `submitted_documents` 
MODIFY COLUMN `processing_status` VARCHAR(255) DEFAULT 'pending' COMMENT 'Statut du traitement: pending, processing, completed, failed';

SELECT 'Migration 1/2 - Processing Status: OK' AS status;

-- ============================================
-- MIGRATION 2/2: Add Anonymization Columns
-- ============================================
-- Ajouter les colonnes d'anonymisation à la table submitted_documents
ALTER TABLE `submitted_documents` 
ADD COLUMN `anonymization_detections` JSON NULL COMMENT 'Détections PII (noms, adresses, téléphones, etc.) au format JSON' AFTER `extracted_text_length`,
ADD COLUMN `has_sensitive_data` TINYINT(1) DEFAULT 0 COMMENT 'Indique si des données sensibles ont été détectées' AFTER `anonymization_detections`,
ADD COLUMN `detections_count` INT UNSIGNED DEFAULT 0 COMMENT 'Nombre total de détections PII' AFTER `has_sensitive_data`;

SELECT 'Migration 2/2 - Anonymization Columns: OK' AS status;

-- ============================================
-- Création des Index pour Performance
-- ============================================
CREATE INDEX `idx_has_sensitive_data` ON `submitted_documents` (`has_sensitive_data`);
CREATE INDEX `idx_detections_count` ON `submitted_documents` (`detections_count`);

SELECT 'Index Creation: OK' AS status;

-- ============================================
-- Vérification Finale
-- ============================================
-- Afficher la structure complète de la table
DESCRIBE submitted_documents;

-- Statistiques des documents
SELECT 
    processing_status,
    COUNT(*) AS count,
    SUM(CASE WHEN has_sensitive_data = 1 THEN 1 ELSE 0 END) AS with_sensitive_data
FROM submitted_documents
GROUP BY processing_status;

SELECT '========================================' AS separator;
SELECT 'MIGRATIONS TERMINEES AVEC SUCCES!' AS result;
SELECT '========================================' AS separator;
