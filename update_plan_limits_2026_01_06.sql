-- Migration complète: Limites messages + Mise à jour tokens/models pour rentabilité
-- Date: 2026-01-06
-- IMPORTANT: Exécuter sur production pour activer les nouvelles limites

-- 1. Ajouter colonne messages_per_day_limit dans mobile_app_plans
ALTER TABLE mobile_app_plans 
ADD COLUMN messages_per_day_limit INT DEFAULT 50 AFTER pdf_downloads_limit;

-- 2. Ajouter colonne fair_use_monthly_cap (pour plans "illimités" avec cap caché)
ALTER TABLE mobile_app_plans 
ADD COLUMN fair_use_monthly_cap INT DEFAULT NULL AFTER ai_analyses_limit;

-- 3. Ajouter colonnes tracking messages dans mobile_app_subscriptions
ALTER TABLE mobile_app_subscriptions 
ADD COLUMN messages_sent_today INT DEFAULT 0 AFTER pdf_downloads_used,
ADD COLUMN messages_last_reset_date DATE DEFAULT NULL AFTER messages_sent_today;

-- 3. Mettre à jour Plan Gratuit
UPDATE mobile_app_plans 
SET 
    messages_per_day_limit = 10,
    searches_limit = 5,
    ai_analyses_limit = 2,
    pdf_downloads_limit = 3,
    max_tokens = 1000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'free';

-- 4. Mettre à jour Plan Étudiant
UPDATE mobile_app_plans 
SET 
    messages_per_day_limit = 100,
    searches_limit = 50,
    ai_analyses_limit = 20,
    pdf_downloads_limit = 30,
    max_tokens = 4000,  -- Augmenté de 2000 à 4000
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'student';

-- 5. Mettre à jour Plan Professionnel
UPDATE mobile_app_plans 
SET 
    messages_per_day_limit = 500,
    searches_limit = 200,
    ai_analyses_limit = 100,
    pdf_downloads_limit = 150,
    max_tokens = 8000,  -- Augmenté de 4000 à 8000
    ai_model = 'gpt-3.5-turbo'  -- ⚠️ CHANGÉ DE gpt-4 à gpt-3.5-turbo pour rentabilité
WHERE name = 'pro';

-- 6. Mettre à jour Plan Cabinet/Entreprise
UPDATE mobile_app_plans 
SET 
    messages_per_day_limit = -1,  -- Illimité
    searches_limit = -1,
    ai_analyses_limit = -1,  -- ← Reste -1 pour afficher "illimité" dans Flutter
    fair_use_monthly_cap = 300,  -- ← Cap technique backend-only (caché de Flutter)
    pdf_downloads_limit = -1,
    max_tokens = 16000,  -- Augmenté de 8000 à 16000
    ai_model = 'gpt-4-turbo'
WHERE name = 'cabinet';

-- 7. Vérification après migration
SELECT 
    name,
    name_fr,
    price_monthly,
    searches_limit,
    fair_use_monthly_cap,
    pdf_downloads_limit,
    messages_per_day_limit,
    ai_model,
    max_tokens
FROM mobile_app_plans
ORDER BY price_monthly ASC;

-- Résultat attendu:
-- +----------+------------------------+---------------+----------------+-------------------+----------------------+---------------------+-----------------------+----------------+------------+
-- | name     | name_fr                | price_monthly | searches_limit | ai_analyses_limit | fair_use_monthly_cap | pdf_downloads_limit | messages_per_day_limit| ai_model       | max_tokens |
-- +----------+------------------------+---------------+----------------+-------------------+----------------------+---------------------+-----------------------+----------------+------------+
-- | free     | Plan Gratuit           |             0 |              5 |                 2 |                 NULL |                   3 |                    10 | gpt-3.5-turbo  |       1000 |
-- | student  | Plan Étudiant          |          2000 |             50 |                20 |                 NULL |                  30 |                   100 | gpt-3.5-turbo  |       4000 |
-- | pro      | Plan Professionnel     |          5000 |            200 |               100 |                 NULL |                 150 |                   500 | gpt-3.5-turbo  |       8000 |
-- | cabinet  | Plan Cabinet/Entreprise|         20000 |             -1 |                -1 |                  300 |                  -1 |                    -1 | gpt-4-turbo    |      16000 |
-- +----------+------------------------+---------------+----------------+-------------------+----------------------+---------------------+-----------------------+----------------+------------+
-- 
-- NOTE: Plan Cabinet affichera "∞ illimité" dans Flutter car ai_analyses_limit = -1,
--       mais le backend vérifiera fair_use_monthly_cap = 300 en silence.
-- +----------+------------------------+---------------+----------------+-------------------+---------------------+-----------------------+----------------+------------+
