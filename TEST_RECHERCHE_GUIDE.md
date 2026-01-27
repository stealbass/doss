# TEST DE LA RECHERCHE - GUIDE COMPLET

## ✅ AMÉLIORATIONS EFFECTUÉES

### 1. **Legal Documents (Bibliothèque juridique)**
- ✅ Recherche insensible à la casse (LOWER)
- ✅ Minimum 2 caractères (au lieu de 3)
- ✅ Recherche dans: title, description, file_name
- ✅ Filtrage par pays automatique

### 2. **Document Templates**
- ✅ Recherche insensible à la casse (LOWER)
- ✅ Recherche dans: name, description, slug
- ✅ Filtrage par pays automatique
- ✅ Pagination

### 3. **Fiscal Resources**
- ✅ Recherche insensible à la casse (LOWER)
- ✅ Recherche dans: title, description, slug, resource_type
- ✅ Filtrage par pays automatique
- ✅ Pagination

## 🧪 COMMENT TESTER LA RECHERCHE

### A. Via Postman/Thunder Client:

#### 1. Legal Documents
```http
POST https://dossypro.com/api/mobile/documents/search
Headers:
  Authorization: Bearer YOUR_TOKEN
  Content-Type: application/json

Body:
{
  "query": "arrete",
  "jurisdiction": "CM",
  "page": 1
}
```

**Résultat attendu:**
- Trouve tous les documents contenant "arrete" (insensible casse)
- Même "ARRETE", "Arrêté", "arrête" seront trouvés

#### 2. Templates
```http
GET https://dossypro.com/api/mobile/templates?search=contrat&page=1
Headers:
  Authorization: Bearer YOUR_TOKEN
```

**Résultat attendu:**
- Trouve "Contrat de bail", "CONTRAT", "contrat" etc.

#### 3. Fiscal Resources
```http
GET https://dossypro.com/api/mobile/fiscal-resources?search=minfi&year=2026&page=1
Headers:
  Authorization: Bearer YOUR_TOKEN
```

**Résultat attendu:**
- Trouve toutes ressources avec "minfi", "MINFI", "Minfi"

### B. Dans l'App Flutter:

#### Test 1: Legal Library
1. Ouvrir "Bibliothèque juridique"
2. Taper "ar" dans la recherche → Devrait trouver "ARRETE"
3. Taper "ACCORD" → Devrait trouver "Accord instituant"
4. Taper "code" → Devrait trouver "Code de déontologie"

#### Test 2: Templates
1. Ouvrir "Modèles de Documents"
2. Taper "bail" → Devrait trouver "Contrat de bail"
3. Taper "CONTRAT" → Devrait trouver tous les contrats

#### Test 3: Fiscal Resources
1. Ouvrir "Ressources Fiscales"
2. Taper "circulaire" → Devrait trouver "Circulaire MINFI"
3. Taper "repertoire" → Devrait trouver "REPERTOIRE DES CENTRES"

## ⚠️ PROBLÈMES POTENTIELS & SOLUTIONS

### Problème 1: "Aucun résultat" alors que documents existent

**Cause possible:** Documents n'ont pas le bon `country`

**Solution:**
```sql
-- Vérifier les pays des documents
SELECT DISTINCT country FROM legal_documents;
SELECT DISTINCT country FROM document_templates;
SELECT DISTINCT country FROM fiscal_social_resources;

-- Si vides ou mauvais pays, mettre à jour:
UPDATE legal_documents SET country = 'CM' WHERE country IS NULL OR country = '';
UPDATE document_templates SET country = 'CM' WHERE country IS NULL OR country = '';
UPDATE fiscal_social_resources SET country = 'CM' WHERE country IS NULL OR country = '';
```

### Problème 2: Recherche ne fonctionne pas côté Flutter

**Cause:** L'écran templates utilise filtrage local (pas d'appel API avec search)

**Solution:** Mettre à jour templates_list_screen.dart pour appeler fetchTemplates avec paramètre search:

```dart
Future<void> _loadTemplates() async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  final templateProvider = Provider.of<TemplateProvider>(context, listen: false);

  if (authProvider.token != null) {
    await templateProvider.fetchTemplates(
      authProvider.token!,
      search: _searchQuery.isNotEmpty ? _searchQuery : null,
    );
  }
}
```

### Problème 3: Recherche trop lente

**Cause:** Pas d'index sur les colonnes searchées

**Solution - Créer des index:**
```sql
-- Legal documents
CREATE INDEX idx_legal_docs_title ON legal_documents(title);
CREATE INDEX idx_legal_docs_country ON legal_documents(country);

-- Templates
CREATE INDEX idx_templates_name ON document_templates(name);
CREATE INDEX idx_templates_country ON document_templates(country);

-- Fiscal resources
CREATE INDEX idx_fiscal_title ON fiscal_social_resources(title);
CREATE INDEX idx_fiscal_country ON fiscal_social_resources(country);
```

## 📊 VÉRIFICATION RAPIDE

Exécuter sur le serveur pour vérifier que tout est OK:

```bash
php artisan tinker
```

```php
// Test 1: Vérifier qu'il y a des documents
DB::table('legal_documents')->count();
DB::table('document_templates')->count();
DB::table('fiscal_social_resources')->count();

// Test 2: Vérifier les pays
DB::table('legal_documents')->select('country', DB::raw('count(*) as total'))->groupBy('country')->get();

// Test 3: Test de recherche
DB::table('legal_documents')->whereRaw('LOWER(title) LIKE ?', ['%arrete%'])->count();
```

## ✅ CHECKLIST FINALE

Avant de dire que la recherche fonctionne:

- [ ] Legal documents: Recherche "arrete" trouve des résultats
- [ ] Legal documents: Recherche "ARRETE" trouve les mêmes résultats
- [ ] Templates: Recherche "contrat" trouve des résultats
- [ ] Templates: Filtré par pays de l'utilisateur
- [ ] Fiscal: Recherche "circulaire" trouve des résultats
- [ ] Fiscal: Filtré par pays et année
- [ ] Tous: Pagination fonctionne (max 20 par page)
- [ ] Tous: Total count affiché correctement

## 📝 MODIFICATIONS TEMPLATES_LIST_SCREEN (OPTIONNEL)

Si vous voulez que la recherche Templates soit côté serveur au lieu de local:

```dart
@override
void initState() {
  super.initState();
  WidgetsBinding.instance.addPostFrameCallback((_) {
    _loadTemplates();
  });
}

Future<void> _loadTemplates() async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  final templateProvider = Provider.of<TemplateProvider>(context, listen: false);

  if (authProvider.token != null) {
    await templateProvider.fetchTemplates(
      authProvider.token!,
      search: _searchQuery.isNotEmpty ? _searchQuery : null,
    );
  }
}

// Dans le TextField onChange:
TextField(
  onChanged: (value) {
    setState(() {
      _searchQuery = value;
    });
    if (value.length >= 2 || value.isEmpty) {
      _loadTemplates(); // Appeler l'API à chaque changement
    }
  },
  ...
)
```

Cela rendra la recherche plus puissante car elle utilisera l'API backend au lieu du filtrage local.
