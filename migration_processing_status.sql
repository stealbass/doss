-- ============================================
-- Migration: Update Submitted Documents Processing Status Default
-- Date: 2026-01-06
-- ============================================

-- Modifier la valeur par défaut de processing_status de 'completed' à 'pending'
-- Cela permet aux nouveaux documents d'être traités en background
ALTER TABLE `submitted_documents` 
MODIFY COLUMN `processing_status` VARCHAR(255) DEFAULT 'pending' COMMENT 'Statut du traitement: pending, processing, completed, failed';

-- ============================================
-- Vérification
-- ============================================
-- Pour vérifier le changement:
-- SHOW COLUMNS FROM submitted_documents WHERE Field = 'processing_status';

-- Pour voir les documents en attente de traitement:
-- SELECT id, original_filename, processing_status, created_at 
-- FROM submitted_documents 
-- WHERE processing_status = 'pending'
-- ORDER BY created_at DESC;

-- Pour voir la répartition des statuts:
-- SELECT processing_status, COUNT(*) as count 
-- FROM submitted_documents 
-- GROUP BY processing_status;
