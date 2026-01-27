-- ============================================================
-- SCRIPT SQL POUR PHPMYADMIN
-- Fix pour la migration legal_library_permissions
-- ============================================================

-- Ce script va marquer la migration comme exécutée sans la ré-exécuter
-- puisque les permissions existent déjà dans votre base de données

-- ÉTAPE 1 : Vérifier si la migration est déjà marquée comme exécutée
SELECT * FROM migrations 
WHERE migration = '2024_11_15_000003_add_legal_library_permissions_php74';

-- ÉTAPE 2 : Si la migration N'EST PAS dans la table ci-dessus, 
-- exécutez cette commande pour la marquer comme exécutée :
INSERT INTO migrations (migration, batch) 
VALUES ('2024_11_15_000003_add_legal_library_permissions_php74', 
        (SELECT MAX(batch) FROM (SELECT batch FROM migrations) AS temp_batch));

-- ÉTAPE 3 : Vérifier que les permissions existent bien
SELECT * FROM permissions 
WHERE name IN ('manage legal library', 'view legal library');

-- ÉTAPE 4 : Si les permissions N'EXISTENT PAS (peu probable), les créer :
-- ATTENTION : N'exécutez cette partie QUE si l'étape 3 ne retourne AUCUN résultat

-- Créer la permission "manage legal library" si elle n'existe pas
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'manage legal library', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE name = 'manage legal library' AND guard_name = 'web'
);

-- Créer la permission "view legal library" si elle n'existe pas
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'view legal library', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE name = 'view legal library' AND guard_name = 'web'
);

-- ÉTAPE 5 : Assigner les permissions aux rôles (optionnel)
-- Cette partie assigne les permissions aux rôles appropriés

-- Récupérer l'ID de la permission "manage legal library"
SET @manage_perm_id = (SELECT id FROM permissions WHERE name = 'manage legal library' AND guard_name = 'web' LIMIT 1);

-- Récupérer l'ID de la permission "view legal library"  
SET @view_perm_id = (SELECT id FROM permissions WHERE name = 'view legal library' AND guard_name = 'web' LIMIT 1);

-- Récupérer l'ID du rôle "company" (admin)
SET @company_role_id = (SELECT id FROM roles WHERE name = 'company' LIMIT 1);

-- Assigner les deux permissions au rôle "company" (si le rôle existe)
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT @manage_perm_id, @company_role_id
WHERE @company_role_id IS NOT NULL AND @manage_perm_id IS NOT NULL;

INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT @view_perm_id, @company_role_id
WHERE @company_role_id IS NOT NULL AND @view_perm_id IS NOT NULL;

-- Assigner "view legal library" aux autres rôles
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT @view_perm_id, id FROM roles 
WHERE name IN ('advocate', 'client', 'co advocate', 'team leader')
AND @view_perm_id IS NOT NULL;

-- ============================================================
-- VÉRIFICATIONS FINALES
-- ============================================================

-- Vérifier que la migration est bien enregistrée
SELECT 'Migration enregistrée :' AS Status;
SELECT * FROM migrations 
WHERE migration = '2024_11_15_000003_add_legal_library_permissions_php74';

-- Vérifier que les permissions existent
SELECT 'Permissions existantes :' AS Status;
SELECT * FROM permissions 
WHERE name IN ('manage legal library', 'view legal library');

-- Vérifier les assignations aux rôles
SELECT 'Assignations rôles-permissions :' AS Status;
SELECT 
    r.name AS role_name,
    p.name AS permission_name
FROM role_has_permissions rhp
INNER JOIN roles r ON rhp.role_id = r.id
INNER JOIN permissions p ON rhp.permission_id = p.id
WHERE p.name IN ('manage legal library', 'view legal library')
ORDER BY r.name, p.name;

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================
