-- Test 1: Compter toutes les ressources fiscales
SELECT COUNT(*) as total_fiscal_resources 
FROM fiscal_social_resources;

-- Test 2: Voir toutes les valeurs de country distinctes
SELECT DISTINCT country, COUNT(*) as count 
FROM fiscal_social_resources 
GROUP BY country;

-- Test 3: Chercher spécifiquement Cameroun (toutes variantes)
SELECT id, title, country, is_mobile_visible, is_latest_version, year
FROM fiscal_social_resources
WHERE country IN ('CM', 'Cameroon', 'Cameroun', 'cm', 'cameroon', 'cameroun')
ORDER BY year DESC;

-- Test 4: Voir TOUTES les ressources (si aucune pour Cameroun)
SELECT id, title, country, is_mobile_visible, is_latest_version, year, resource_type
FROM fiscal_social_resources
LIMIT 10;

-- Test 5: Chercher par pattern sur title
SELECT id, title, country, resource_type
FROM fiscal_social_resources
WHERE title LIKE '%centre%'
   OR title LIKE '%gestion%'
   OR title LIKE '%agréé%'
   OR title LIKE '%répertoire%'
LIMIT 5;
