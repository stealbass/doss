-- ============================================
-- Migration: Add Anonymization to Submitted Documents
-- Date: 2026-01-06
-- Ordre: 2/2 (à exécuter APRÈS migration_processing_status.sql)
-- ============================================

-- Ajouter les colonnes d'anonymisation à la table submitted_documents
ALTER TABLE `submitted_documents` 
ADD COLUMN `anonymization_detections` JSON NULL COMMENT 'Détections PII (noms, adresses, téléphones, etc.) au format JSON' AFTER `extracted_text_length`,
ADD COLUMN `has_sensitive_data` TINYINT(1) DEFAULT 0 COMMENT 'Indique si des données sensibles ont été détectées' AFTER `anonymization_detections`,
ADD COLUMN `detections_count` INT UNSIGNED DEFAULT 0 COMMENT 'Nombre total de détections PII' AFTER `has_sensitive_data`;

-- Ajouter des index pour optimiser les requêtes
CREATE INDEX `idx_has_sensitive_data` ON `submitted_documents` (`has_sensitive_data`);
CREATE INDEX `idx_detections_count` ON `submitted_documents` (`detections_count`);

-- ============================================
-- Vérification
-- ============================================
-- Pour vérifier que les colonnes ont été ajoutées:
-- DESCRIBE submitted_documents;

-- Pour voir les documents avec données sensibles:
-- SELECT id, original_filename, has_sensitive_data, detections_count 
-- FROM submitted_documents 
-- WHERE has_sensitive_data = 1;
