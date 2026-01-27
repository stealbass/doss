-- Script SQL pour activer le compte contact@dossypro.com
-- Exécutez ce script dans votre base de données MySQL/MariaDB

-- Vérifier l'état actuel du compte
SELECT id, name, email, type, is_active, created_at
FROM users
WHERE email = 'contact@dossypro.com';

-- Activer le compte
UPDATE users
SET is_active = 1, updated_at = NOW()
WHERE email = 'contact@dossypro.com';

-- Vérifier que l'activation a réussi
SELECT id, name, email, type, is_active, updated_at
FROM users
WHERE email = 'contact@dossypro.com';

-- Optionnel : Vérifier si l'utilisateur a une subscription active
SELECT 
    mas.id,
    mas.user_id,
    mas.status,
    map.name as plan_name,
    mas.started_at,
    mas.expires_at
FROM mobile_app_subscriptions mas
LEFT JOIN mobile_app_plans map ON mas.mobile_app_plan_id = map.id
WHERE mas.user_id = (SELECT id FROM users WHERE email = 'contact@dossypro.com' LIMIT 1);
