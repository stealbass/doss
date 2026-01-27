# 🔍 Diagnostic des Problèmes d'Intégration API

## 📋 Problèmes Rapportés

1. ❌ **Veille juridique** et **Multi-comptes** toujours verrouillés avec accès Cabinet
2. ❌ **Modèles de documents**, **Ressources fiscales**, **Bibliothèque juridique** n'affichent aucun document
3. ❌ **Calculateurs** n'affiche rien

## ✅ Corrections Appliquées

### 1. Contrôles d'Accès Library Hub
- ✅ Restauré vérification réelle `_hasProAccess()` (pas de `return true` forcé)
- ✅ Anonymisation utilise maintenant `!hasCabinet` au lieu de `!hasPro`

## 🔎 Points à Vérifier

### A. Vérification du Plan Utilisateur

**Dans l'app Flutter**, vérifiez que votre utilisateur a bien le bon plan:

1. Affichez le plan dans le profil:
```dart
// Dans profile_settings_screen.dart ou similaire
Text('Plan actuel: ${user?.plan}')
```

Le plan devrait être exactement: `Cabinet/Entreprise` (avec majuscules et slash)

**Dans le backend Laravel**, vérifiez la base de données:
```sql
SELECT id, name, email, plan, country FROM users WHERE email = 'votre-email@example.com';
```

Le champ `plan` doit être: `Cabinet/Entreprise`

### B. Vérification Backend - Documents

**Templates (Modèles de Documents)**

Dans phpMyAdmin ou MySQL:
```sql
-- Vérifier qu'il y a des templates
SELECT id, title, country, is_mobile_visible, required_plan, allowed_plans 
FROM document_templates 
LIMIT 10;
```

Points critiques:
- ✅ `is_mobile_visible` doit être `1` (true)
- ✅ `country` doit correspondre au pays de l'utilisateur (ou NULL pour tous)
- ✅ `allowed_plans` doit contenir `"Cabinet/Entreprise"` ou `"Professionnel"`

**Ressources Fiscales**

```sql
-- Vérifier qu'il y a des ressources
SELECT id, title, country, year, is_mobile_visible, resource_type
FROM fiscal_social_resources
WHERE year = 2024 OR year = 2025
LIMIT 10;
```

Points critiques:
- ✅ `is_mobile_visible` doit être `1` (true)
- ✅ `country` doit correspondre au pays de l'utilisateur
- ✅ `year` doit être l'année actuelle (2025)

**Calculateurs**

```sql
-- Vérifier qu'il y a des calculateurs
SELECT id, name, country, is_mobile_visible, is_active, required_plan
FROM calculator_configs
LIMIT 10;
```

Points critiques:
- ✅ `is_mobile_visible` doit être `1` (true)
- ✅ `is_active` doit être `1` (true)
- ✅ `country` doit correspondre au pays de l'utilisateur (ou NULL)
- ✅ `required_plan` doit être accessible au plan Cabinet

### C. Test des URLs API Directement

Ouvrez Postman ou votre navigateur avec les URLs suivantes:

**1. Vérifier l'authentification**
```
GET https://dossypro.com/api/mobile/user
Headers: 
  Authorization: Bearer VOTRE_TOKEN
  Accept: application/json
```

Devrait retourner vos infos utilisateur avec le plan correct.

**2. Tester Templates**
```
GET https://dossypro.com/api/mobile/templates
Headers: 
  Authorization: Bearer VOTRE_TOKEN
  Accept: application/json
```

**3. Tester Ressources Fiscales**
```
GET https://dossypro.com/api/mobile/fiscal-resources
Headers: 
  Authorization: Bearer VOTRE_TOKEN
  Accept: application/json
```

**4. Tester Calculateurs**
```
GET https://dossypro.com/api/mobile/calculators
Headers: 
  Authorization: Bearer VOTRE_TOKEN
  Accept: application/json
```

### D. Vérification des Pays

Le backend filtre par pays. Vérifiez que:

1. **L'utilisateur a un pays défini**:
```sql
SELECT id, name, email, country FROM users WHERE id = VOTRE_USER_ID;
```

2. **Les documents ont le bon pays** (ou NULL pour tous):
```sql
-- Templates
SELECT COUNT(*) as total, country 
FROM document_templates 
WHERE is_mobile_visible = 1 
GROUP BY country;

-- Ressources Fiscales
SELECT COUNT(*) as total, country 
FROM fiscal_social_resources 
WHERE is_mobile_visible = 1 AND year >= 2024
GROUP BY country;

-- Calculateurs
SELECT COUNT(*) as total, country 
FROM calculator_configs 
WHERE is_mobile_visible = 1 AND is_active = 1
GROUP BY country;
```

Les pays doivent correspondre (exemple: `Côte d'Ivoire`, `Sénégal`, `Bénin`, etc.)

## 🛠️ Scripts de Correction

### Script 1: Activer is_mobile_visible pour tous les documents

```sql
-- Templates
UPDATE document_templates 
SET is_mobile_visible = 1 
WHERE is_mobile_visible = 0 OR is_mobile_visible IS NULL;

-- Ressources Fiscales
UPDATE fiscal_social_resources 
SET is_mobile_visible = 1 
WHERE is_mobile_visible = 0 OR is_mobile_visible IS NULL;

-- Calculateurs
UPDATE calculator_configs 
SET is_mobile_visible = 1 
WHERE is_mobile_visible = 0 OR is_mobile_visible IS NULL;
```

### Script 2: Vérifier/Corriger le plan utilisateur

```sql
-- Voir tous les plans disponibles
SELECT DISTINCT plan FROM users;

-- Si votre plan est incorrect, le corriger
UPDATE users 
SET plan = 'Cabinet/Entreprise' 
WHERE email = 'votre-email@example.com';
```

### Script 3: Corriger les allowed_plans des templates

```sql
-- Voir les plans autorisés actuels
SELECT id, title, required_plan, allowed_plans 
FROM document_templates;

-- Si allowed_plans est vide ou incorrect, le corriger
UPDATE document_templates 
SET allowed_plans = JSON_ARRAY('Professionnel', 'Cabinet/Entreprise')
WHERE required_plan = 'Professionnel';

UPDATE document_templates 
SET allowed_plans = JSON_ARRAY('Cabinet/Entreprise')
WHERE required_plan = 'Cabinet/Entreprise';
```

## 📱 Debug dans l'App Flutter

Ajoutez ces logs temporaires pour diagnostiquer:

**Dans templates_list_screen.dart** (ligne ~30):
```dart
Future<void> _loadTemplates() async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  final templateProvider = Provider.of<TemplateProvider>(context, listen: false);

  print('🔍 DEBUG: User plan = ${authProvider.user?.plan}');
  print('🔍 DEBUG: User country = ${authProvider.user?.jurisdiction}');
  print('🔍 DEBUG: Token = ${authProvider.token?.substring(0, 20)}...');

  if (authProvider.token != null) {
    await templateProvider.fetchTemplates(authProvider.token!);
    print('🔍 DEBUG: Templates loaded = ${templateProvider.templates.length}');
    print('🔍 DEBUG: Error = ${templateProvider.error}');
  }
}
```

**Dans template_provider.dart** (après la requête HTTP):
```dart
if (response.statusCode == 200) {
  final data = json.decode(response.body);
  print('🔍 DEBUG API Response: ${data}');
  if (data['success']) {
    _templates = (data['data'] as List)
        .map((json) => DocumentTemplate.fromJson(json))
        .toList();
    print('🔍 DEBUG: Parsed ${_templates.length} templates');
  }
} else {
  print('🔍 DEBUG: HTTP ${response.statusCode}');
  print('🔍 DEBUG: Response body: ${response.body}');
  _error = 'Erreur réseau: ${response.statusCode}';
}
```

## 🎯 Checklist de Diagnostic

Suivez cette checklist dans l'ordre:

- [ ] **1. Vérifier que l'utilisateur est bien authentifié**
  - Token présent et valide
  - User info accessible

- [ ] **2. Vérifier le plan utilisateur**
  - Plan = `Cabinet/Entreprise` (exactement)
  - Pays défini (ex: `Côte d'Ivoire`)

- [ ] **3. Vérifier la base de données**
  - Documents existent avec `is_mobile_visible = 1`
  - Pays des documents correspond au pays utilisateur
  - `allowed_plans` contient le plan utilisateur

- [ ] **4. Tester les API directement**
  - URLs correctes sans double `/mobile`
  - Headers avec Bearer token
  - Réponses JSON avec `success: true`

- [ ] **5. Vérifier les logs Flutter**
  - Nombre de documents chargés
  - Messages d'erreur éventuels

- [ ] **6. Contrôles d'accès UI**
  - `_hasProAccess()` retourne vrai pour Cabinet
  - `_hasCabinetAccess()` retourne vrai pour Cabinet
  - Cartes non verrouillées (`locked: false`)

## 🚀 Solutions Rapides

### Si aucun document ne s'affiche

**Solution A: Activer la visibilité mobile**
```sql
UPDATE document_templates SET is_mobile_visible = 1;
UPDATE fiscal_social_resources SET is_mobile_visible = 1;
UPDATE calculator_configs SET is_mobile_visible = 1, is_active = 1;
```

**Solution B: Rendre les documents universels (tous pays)**
```sql
UPDATE document_templates SET country = NULL;
UPDATE fiscal_social_resources SET country = NULL;
UPDATE calculator_configs SET country = NULL;
```

**Solution C: Ouvrir l'accès à tous les plans (temporaire test)**
```sql
UPDATE document_templates 
SET allowed_plans = JSON_ARRAY('Gratuit', 'Étudiant', 'Professionnel', 'Cabinet/Entreprise');

UPDATE calculator_configs 
SET required_plan = 'Gratuit';
```

### Si Veille/Multi-comptes toujours verrouillés

Dans l'app Flutter, vérifiez le plan:
```dart
// Ajouter dans library_hub_screen.dart build()
print('🔍 User plan: ${user?.plan}');
print('🔍 hasPro: $hasPro');
print('🔍 hasCabinet: $hasCabinet');
```

Le plan doit être **exactement** `Cabinet/Entreprise` (sensible à la casse).

## 📞 Support Backend

Si après ces vérifications les problèmes persistent, vérifiez:

1. **Logs Laravel** (`storage/logs/laravel.log`)
2. **Middleware d'authentification** fonctionne
3. **CORS** configuré pour accepter les requêtes mobile
4. **Base de données** accessible et contient des données

## 📝 Exemple de Données Test

Si votre base est vide, insérez des données de test:

```sql
-- Template de test
INSERT INTO document_templates (
  title, description, category_id, template_type, 
  file_path, file_type, country, is_mobile_visible, 
  required_plan, allowed_plans, created_at, updated_at
) VALUES (
  'Contrat de Travail CDI', 
  'Modèle de contrat à durée indéterminée',
  1, 'contract', 
  'templates/contrat_cdi.docx', 
  'docx',
  NULL, -- tous pays
  1, -- visible mobile
  'Professionnel',
  JSON_ARRAY('Professionnel', 'Cabinet/Entreprise'),
  NOW(), NOW()
);

-- Ressource fiscale de test
INSERT INTO fiscal_social_resources (
  title, description, category_id, resource_type,
  file_path, file_type, country, year, version,
  is_mobile_visible, created_at, updated_at
) VALUES (
  'Code Général des Impôts 2025',
  'Version complète du CGI',
  1, 'code',
  'fiscal/cgi_2025.pdf',
  'pdf',
  NULL, -- tous pays
  2025,
  '1.0',
  1, -- visible mobile
  NOW(), NOW()
);

-- Calculateur de test
INSERT INTO calculator_configs (
  name, description, calculator_type, country,
  calculation_formula, required_inputs,
  is_mobile_visible, is_active, required_plan,
  created_at, updated_at
) VALUES (
  'Coût d\'Embauche',
  'Calculez le coût total d\'une embauche',
  'hiring_cost',
  NULL, -- tous pays
  'total = salary + (salary * 0.21)',
  JSON_ARRAY(
    JSON_OBJECT('name', 'salary', 'label', 'Salaire brut mensuel', 'type', 'number')
  ),
  1, -- visible mobile
  1, -- actif
  'Professionnel',
  NOW(), NOW()
);
```
