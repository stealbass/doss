-- Diagnostic et réparation des chemins de fichiers juridiques
-- Ce script répare les documents avec file_path vide

-- 1. Vérifier les documents avec file_path vide
SELECT 'BEFORE FIX - Documents with empty file_path:' as diagnostic;
SELECT id, title, file_name, IFNULL(file_path, 'NULL') as file_path 
FROM legal_documents 
WHERE file_path IS NULL OR file_path = '' 
LIMIT 20;

-- 2. Compter le total
SELECT COUNT(*) as empty_count FROM legal_documents WHERE file_path IS NULL OR file_path = '';

-- 3. Réparer les chemins vides en reconstruisant à partir du file_name
UPDATE legal_documents 
SET file_path = CONCAT('legal_documents/', file_name) 
WHERE (file_path IS NULL OR file_path = '') AND file_name IS NOT NULL;

-- 4. Vérifier que la réparation a fonctionné
SELECT 'AFTER FIX - Documents still with empty file_path:' as diagnostic;
SELECT COUNT(*) as still_empty FROM legal_documents WHERE file_path IS NULL OR file_path = '';

-- 5. Afficher un échantillon des documents réparés
SELECT 'Sample of repaired documents:' as diagnostic;
SELECT id, title, file_path FROM legal_documents WHERE file_path LIKE 'legal_documents/%' LIMIT 10;

-- 6. Vérification d'intégrité - tous les documents doivent avoir file_path
SELECT 'Final verification:' as diagnostic;
SELECT 
    COUNT(*) as total_docs,
    SUM(CASE WHEN file_path IS NOT NULL AND file_path != '' THEN 1 ELSE 0 END) as with_path,
    SUM(CASE WHEN file_path IS NULL OR file_path = '' THEN 1 ELSE 0 END) as without_path
FROM legal_documents;
