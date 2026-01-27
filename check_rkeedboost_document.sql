-- Vérifier si le document rkeedboost.pdf a du texte extrait
SELECT 
    id,
    file_name,
    file_size,
    processing_status,
    extracted_text_length,
    processing_error,
    created_at,
    processed_at
FROM submitted_documents 
WHERE file_name LIKE '%rkeed%'
ORDER BY created_at DESC;

-- Si le document existe, afficher les premiers 500 caractères du texte extrait
SELECT 
    id,
    file_name,
    LEFT(extracted_text, 500) as text_preview
FROM submitted_documents 
WHERE file_name LIKE '%rkeed%'
AND extracted_text IS NOT NULL;
