-- Vérification configuration Pinecone dans mobile_app_settings
-- Date: 2026-01-21

SELECT 
    id,
    pinecone_api_key IS NOT NULL AND pinecone_api_key != '' AS 'API Key Configured',
    CONCAT(LEFT(pinecone_api_key, 20), '...', RIGHT(pinecone_api_key, 10)) AS 'API Key (masked)',
    pinecone_environment AS 'Environment',
    pinecone_index AS 'Index Name',
    pinecone_host AS 'Host (CRITIQUE)',
    CASE 
        WHEN pinecone_host IS NULL THEN '❌ NULL - Utilisera .env ou construction automatique'
        WHEN pinecone_host = '' THEN '❌ VIDE - Utilisera .env ou construction automatique'
        WHEN pinecone_host LIKE '%7udtg21%' AND pinecone_host LIKE '%b74a%' THEN '✅ FORMAT CORRECT'
        ELSE '⚠️ FORMAT INCORRECT - manque suffixes'
    END AS 'Statut Host',
    pinecone_verify_ssl AS 'Verify SSL',
    openai_api_key IS NOT NULL AND openai_api_key != '' AS 'OpenAI Configured',
    created_at,
    updated_at
FROM mobile_app_settings
ORDER BY id DESC
LIMIT 1;

-- Si la table est vide:
SELECT 
    CASE 
        WHEN COUNT(*) = 0 THEN '❌ Aucun enregistrement - Tous les paramètres viendront de .env'
        ELSE CONCAT('✅ ', COUNT(*), ' enregistrement(s) trouvé(s)')
    END AS 'Résultat'
FROM mobile_app_settings;

-- Détails complets (sans masquage):
SELECT *
FROM mobile_app_settings
ORDER BY id DESC
LIMIT 1;
