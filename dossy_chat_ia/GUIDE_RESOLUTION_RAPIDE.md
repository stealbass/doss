# ✅ GUIDE DE RÉSOLUTION - Problèmes App Mobile

## 🎯 Problèmes Identifiés et Solutions

### 1. ❌ Veille Juridique et Multi-comptes Toujours Verrouillés

**Cause:** Vérification incorrecte du plan utilisateur

**Solutions Appliquées:**
- ✅ Restauré la vraie vérification `_hasProAccess()` dans [library_hub_screen.dart](dossy_chat_ia/lib/presentation/screens/library/library_hub_screen.dart)
- ✅ Anonymisation utilise maintenant `locked: !hasCabinet` au lieu de `locked: !hasPro`

**À Vérifier:**

Le plan de l'utilisateur dans la base de données **DOIT être exactement:**
```
Cabinet/Entreprise
```

**Script SQL pour vérifier/corriger:**
```sql
-- Vérifier le plan actuel
SELECT id, name, email, plan FROM users WHERE email = 'votre-email@example.com';

-- Si le plan est incorrect, le corriger
UPDATE users SET plan = 'Cabinet/Entreprise' WHERE email = 'votre-email@example.com';
```

### 2. ❌ Modèles, Ressources Fiscales, Bibliothèque N'affichent Rien

**Causes possibles:**
1. Documents pas marqués comme visibles mobile (`is_mobile_visible = 0`)
2. Pays des documents ne correspond pas au pays utilisateur
3. Année des ressources fiscales obsolète
4. Plans requis ne correspondent pas

**Solution Rapide - Activer Tous les Documents:**

```sql
-- Activer la visibilité mobile pour TOUT
UPDATE document_templates SET is_mobile_visible = 1;
UPDATE fiscal_social_resources SET is_mobile_visible = 1 WHERE year >= 2024;
UPDATE calculator_configs SET is_mobile_visible = 1, is_active = 1;
UPDATE legal_alerts SET is_mobile_visible = 1;
```

**Solution Alternative - Rendre Universal (tous pays):**

```sql
-- Retirer le filtre par pays (temporaire pour tests)
UPDATE document_templates SET country = NULL;
UPDATE fiscal_social_resources SET country = NULL;
UPDATE calculator_configs SET country = NULL;
```

### 3. ❌ Calculateurs N'affiche Rien

**Le backend attend des calculateurs avec:**
- `is_mobile_visible = 1`
- `is_active = 1`
- `required_plan` accessible (ex: 'Professionnel' ou 'Cabinet/Entreprise')

**Solution:**
```sql
-- Activer tous les calculateurs
UPDATE calculator_configs 
SET is_mobile_visible = 1, is_active = 1;

-- Vérifier qu'ils existent
SELECT id, name, calculator_type, is_mobile_visible, is_active, required_plan 
FROM calculator_configs;
```

**Si aucun calculateur n'existe, en créer un de test:**
```sql
INSERT INTO calculator_configs (
  name, description, calculator_type, country,
  calculation_formula, required_inputs,
  is_mobile_visible, is_active, required_plan,
  created_at, updated_at
) VALUES (
  'Coût d''Embauche',
  'Calculez le coût total d''une embauche',
  'hiring_cost',
  NULL,
  'total = salary + (salary * 0.21)',
  '[{"name":"salary","label":"Salaire brut mensuel","type":"number"}]',
  1, 1, 'Professionnel',
  NOW(), NOW()
);
```

## 🛠️ ACTIONS IMMÉDIATES À FAIRE

### Étape 1: Exécuter le Script de Diagnostic

1. Uploadez le fichier `diagnostic_mobile_api.php` à la racine de votre serveur Laravel
2. Accédez via navigateur: `https://dossypro.com/diagnostic_mobile_api.php`
3. Consultez les résultats et notez les problèmes détectés

### Étape 2: Corriger la Visibilité Mobile

Connectez-vous à phpMyAdmin et exécutez:

```sql
-- 1. Activer TOUS les documents pour mobile
UPDATE document_templates SET is_mobile_visible = 1;
UPDATE fiscal_social_resources SET is_mobile_visible = 1;
UPDATE calculator_configs SET is_mobile_visible = 1, is_active = 1;
UPDATE legal_alerts SET is_mobile_visible = 1;

-- 2. Vérifier les résultats
SELECT 
    'Templates' as type,
    COUNT(*) as total,
    SUM(CASE WHEN is_mobile_visible = 1 THEN 1 ELSE 0 END) as mobile_visible
FROM document_templates
UNION ALL
SELECT 
    'Ressources Fiscales',
    COUNT(*),
    SUM(CASE WHEN is_mobile_visible = 1 THEN 1 ELSE 0 END)
FROM fiscal_social_resources
UNION ALL
SELECT 
    'Calculateurs',
    COUNT(*),
    SUM(CASE WHEN is_mobile_visible = 1 AND is_active = 1 THEN 1 ELSE 0 END)
FROM calculator_configs;
```

### Étape 3: Vérifier Votre Plan Utilisateur

```sql
-- Voir votre utilisateur
SELECT id, name, email, plan, country 
FROM users 
WHERE email = 'VOTRE_EMAIL';

-- Si le plan n'est pas correct, le corriger
UPDATE users 
SET plan = 'Cabinet/Entreprise' 
WHERE email = 'VOTRE_EMAIL';
```

### Étape 4: Vérifier les Pays

Le backend filtre par pays. Assurez-vous que:

```sql
-- Voir votre pays utilisateur
SELECT country FROM users WHERE email = 'VOTRE_EMAIL';

-- Voir quels pays ont des documents
SELECT DISTINCT country FROM document_templates WHERE is_mobile_visible = 1;
SELECT DISTINCT country FROM fiscal_social_resources WHERE is_mobile_visible = 1;

-- Si les pays ne correspondent pas, option A: Corriger votre pays
UPDATE users SET country = 'Côte d''Ivoire' WHERE email = 'VOTRE_EMAIL';

-- Ou option B: Rendre les documents universels
UPDATE document_templates SET country = NULL;
UPDATE fiscal_social_resources SET country = NULL;
UPDATE calculator_configs SET country = NULL;
```

### Étape 5: Tester l'API Directement

Avec Postman ou un navigateur (avec extension REST client):

**1. Obtenir votre token d'authentification**

Connectez-vous dans l'app Flutter et récupérez le token (ajoutez temporairement un `print(token)` dans le code).

**2. Tester les endpoints:**

```
GET https://dossypro.com/api/mobile/templates
Headers:
  Authorization: Bearer VOTRE_TOKEN
  Accept: application/json

GET https://dossypro.com/api/mobile/fiscal-resources
Headers:
  Authorization: Bearer VOTRE_TOKEN
  Accept: application/json

GET https://dossypro.com/api/mobile/calculators
Headers:
  Authorization: Bearer VOTRE_TOKEN
  Accept: application/json
```

Les réponses doivent avoir ce format:
```json
{
  "success": true,
  "data": [
    { /* documents */ }
  ]
}
```

### Étape 6: Redémarrer l'App Flutter

Après avoir corrigé la base de données:

1. Fermez complètement l'app
2. Relancez avec `flutter run`
3. Connectez-vous
4. Accédez à "Bibliothèque Pro"
5. Vérifiez que les cartes ne sont pas verrouillées (pas d'icône cadenas)
6. Cliquez sur chaque section pour voir si les données s'affichent

## 🔍 DEBUG dans l'App Flutter

Si les problèmes persistent, ajoutez ces logs temporaires:

**Dans `lib/presentation/screens/library/library_hub_screen.dart` (ligne 27):**

```dart
@override
Widget build(BuildContext context) {
  final user = context.read<AuthProvider>().user;
  final hasPro = _hasProAccess(user?.plan);
  final hasCabinet = _hasCabinetAccess(user?.plan);
  final isFr = Localizations.localeOf(context).languageCode == 'fr';

  // 🔍 DEBUG - À RETIRER APRÈS
  print('═══════════════════════════════════');
  print('🔍 DEBUG Library Hub Access:');
  print('   User Plan: ${user?.plan}');
  print('   User Country: ${user?.jurisdiction}');
  print('   hasPro: $hasPro');
  print('   hasCabinet: $hasCabinet');
  print('═══════════════════════════════════');

  return Scaffold(
    // ... reste du code
```

**Dans `lib/screens/templates/templates_list_screen.dart` (méthode _loadTemplates):**

```dart
Future<void> _loadTemplates() async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  final templateProvider = Provider.of<TemplateProvider>(context, listen: false);

  // 🔍 DEBUG - À RETIRER APRÈS
  print('═══════════════════════════════════');
  print('🔍 DEBUG Loading Templates:');
  print('   Token: ${authProvider.token?.substring(0, 20)}...');
  print('   User: ${authProvider.user?.email}');
  print('   Plan: ${authProvider.user?.plan}');
  print('═══════════════════════════════════');

  if (authProvider.token != null) {
    await templateProvider.fetchTemplates(authProvider.token!);
    
    print('═══════════════════════════════════');
    print('🔍 DEBUG Templates Result:');
    print('   Count: ${templateProvider.templates.length}');
    print('   Error: ${templateProvider.error}');
    print('═══════════════════════════════════');
  }
}
```

**Dans `lib/providers/template_provider.dart` (méthode fetchTemplates):**

```dart
try {
  final response = await http.get(
    Uri.parse('${ApiConstants.baseUrl}/templates'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );

  // 🔍 DEBUG - À RETIRER APRÈS
  print('═══════════════════════════════════');
  print('🔍 DEBUG API Response:');
  print('   URL: ${ApiConstants.baseUrl}/templates');
  print('   Status: ${response.statusCode}');
  print('   Body: ${response.body}');
  print('═══════════════════════════════════');

  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    if (data['success']) {
      _templates = (data['data'] as List)
          .map((json) => DocumentTemplate.fromJson(json))
          .toList();
    }
  }
} catch (e) {
  print('❌ ERROR: $e');
  _error = e.toString();
}
```

## 📊 Checklist de Vérification

Cochez chaque élément après vérification:

- [ ] **Backend - Base de Données**
  - [ ] Script `diagnostic_mobile_api.php` exécuté
  - [ ] `document_templates.is_mobile_visible = 1` pour au moins quelques documents
  - [ ] `fiscal_social_resources.is_mobile_visible = 1` pour des ressources 2024+
  - [ ] `calculator_configs.is_mobile_visible = 1 AND is_active = 1`
  
- [ ] **Backend - Utilisateur**
  - [ ] Mon plan = `Cabinet/Entreprise` (exactement)
  - [ ] Mon pays est défini (ex: `Côte d'Ivoire`)
  - [ ] Les documents ont le même pays (ou NULL)

- [ ] **API - Tests**
  - [ ] GET `/api/mobile/templates` retourne des données
  - [ ] GET `/api/mobile/fiscal-resources` retourne des données
  - [ ] GET `/api/mobile/calculators` retourne des données
  - [ ] Toutes les réponses ont `"success": true`

- [ ] **App Flutter - Accès**
  - [ ] Veille juridique déverrouillée (pas de cadenas)
  - [ ] Multi-comptes déverrouillé (pas de cadenas)
  - [ ] Anonymisation déverrouillée (pas de cadenas)

- [ ] **App Flutter - Données**
  - [ ] Modèles de documents affiche une liste
  - [ ] Ressources fiscales affiche une liste
  - [ ] Calculateurs affiche une grille
  - [ ] Pas de message "Aucun résultat"

## 🚨 Si Ça Ne Fonctionne Toujours Pas

1. **Vérifier les logs Laravel:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Vérifier le cache Laravel:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

3. **Vérifier les logs Flutter:**
   - Console lors du `flutter run`
   - Messages d'erreur affichés

4. **Partager les informations:**
   - Résultat du script `diagnostic_mobile_api.php`
   - Logs de debug de l'app Flutter
   - Captures d'écran des erreurs

## 📝 Résumé des Fichiers Modifiés

### Flutter (Corrections Appliquées)
- ✅ `lib/presentation/screens/library/library_hub_screen.dart` - Contrôles d'accès corrigés
- ✅ `lib/data/models/user_model.dart` - Ajout getter `hasLegalMonitoring`
- ✅ `lib/presentation/screens/professional/anonymization_screen.dart` - Cabinet uniquement
- ✅ `lib/presentation/screens/professional/legal_monitoring_screen.dart` - Pro + Cabinet
- ✅ `lib/providers/*.dart` - URLs API corrigées (double `/mobile` supprimé)

### Backend (Actions Nécessaires)
- ⚠️ Exécuter script SQL pour activer `is_mobile_visible`
- ⚠️ Vérifier/corriger plan utilisateur = `Cabinet/Entreprise`
- ⚠️ Vérifier correspondance des pays
- ⚠️ Tester endpoints API directement

## 📞 Support

Si vous avez besoin d'aide:
1. Exécutez `diagnostic_mobile_api.php`
2. Copiez les résultats
3. Ajoutez les logs de debug Flutter
4. Partagez pour analyse approfondie
