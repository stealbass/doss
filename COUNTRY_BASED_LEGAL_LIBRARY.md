# 🌍 Système de Catégorisation des Documents Juridiques par Pays

## 📋 Vue d'ensemble

Cette fonctionnalité permet de catégoriser les documents juridiques par pays pour permettre à l'IA de fournir des réponses juridiques précises et spécifiques à chaque juridiction.

## 🎯 Objectif

Permettre aux utilisateurs de l'application mobile **DOSSY Chat IA** de sélectionner leur pays et de recevoir des conseils juridiques basés **EXCLUSIVEMENT** sur les lois de leur pays.

### Exemple de cas d'usage

**Scénario** : Un utilisateur au Sénégal pose une question sur le divorce.

**Comportement attendu de l'IA** :
```
Tu es un assistant juridique expert en droit africain. 
Si l'utilisateur pose une question sur un divorce et qu'il a sélectionné 'Sénégal', 
utilise EXCLUSIVEMENT le Code de la Famille du Sénégal. 
Ne mélange pas les juridictions.
```

## 🗺️ Pays Supportés (14 pays)

### Afrique de l'Ouest (UEMOA - 8 pays)
- 🇧🇯 **Bénin** (BJ)
- 🇧🇫 **Burkina Faso** (BF)
- 🇨🇮 **Côte d'Ivoire** (CI)
- 🇬🇼 **Guinée-Bissau** (GW)
- 🇲🇱 **Mali** (ML)
- 🇳🇪 **Niger** (NE)
- 🇸🇳 **Sénégal** (SN)
- 🇹🇬 **Togo** (TG)

### Afrique Centrale (CEMAC - 3 pays)
- 🇨🇲 **Cameroun** (CM)
- 🇨🇩 **RD Congo** (CD)
- 🇬🇦 **Gabon** (GA)

### Océan Indien (1 pays)
- 🇲🇬 **Madagascar** (MG)

### Afrique du Nord (2 pays)
- 🇲🇦 **Maroc** (MA)
- 🇹🇳 **Tunisie** (TN)

## 🏗️ Architecture Technique

### 1. Migration de Base de Données

**Fichier** : `database/migrations/2025_12_18_000001_add_country_to_legal_library_tables.php`

**Ajouts à la table `legal_categories`** :
- `country` (string, 100) - Code pays ISO (ex: SN, CM, MA)
- `is_mobile_visible` (boolean) - Visibilité sur mobile
- `sort_order` (integer) - Ordre d'affichage

**Ajouts à la table `legal_documents`** :
- `country` (string, 100) - Code pays ISO
- `is_mobile_visible` (boolean) - Visibilité sur mobile
- `language` (string, 10) - Langue du document (fr, en, ar, etc.)
- `ai_context` (text) - Contexte pour le prompt AI

### 2. Configuration des Pays

**Fichier** : `config/mobile_countries.php`

```php
'supported_countries' => [
    'SN' => [
        'code' => 'SN',
        'name' => 'Sénégal',
        'flag' => '🇸🇳',
        'region' => 'West Africa',
        'legal_systems' => ['OHADA', 'Civil Law'],
        'official_languages' => ['fr'],
    ],
    // ... autres pays
]
```

### 3. Contrôleur Mis à Jour

**Fichier** : `app/Http/Controllers/MobileLegalLibraryController.php`

**Nouvelles méthodes** :
- `updateDocumentCountry($id)` - Assigner un pays à un document
- `updateCategoryCountry($id)` - Assigner un pays à une catégorie
- `bulkUpdateCountry()` - Mise à jour en masse
- `countryStatistics($countryCode)` - Statistiques par pays
- `getCountryAIContext($countryCode)` - Contexte AI par pays

### 4. Routes API

**Fichier** : `routes/web.php`

```php
// Country Management Routes
Route::post('mobile-legal-library/document/{id}/update-country', ...);
Route::post('mobile-legal-library/category/{id}/update-country', ...);
Route::post('mobile-legal-library/bulk-update-country', ...);
Route::get('mobile-legal-library/country/{country}/statistics', ...);
Route::get('mobile-legal-library/country/{country}/ai-context', ...);
```

## 🎨 Interface Admin

### Dashboard Mobile Legal Library

**URL** : `https://dossypro.com/mobile-legal-library`

**Nouvelles fonctionnalités** :

1. **Carte de statistiques par pays**
   - Nombre de pays avec documents
   - Distribution des documents par pays

2. **Filtre par pays**
   - Dropdown avec drapeaux emoji
   - Filtrage rapide par pays

3. **Tableau de documents**
   - Colonne "Country" avec drapeau et nom
   - Bouton "Assign Country" pour documents non catégorisés

4. **Actions rapides**
   - Assigner un pays à un document
   - Mise à jour en masse par catégorie

## 🤖 Système de Prompts AI Intelligents

### Contexte par Défaut (OHADA)

```
Tu es un assistant juridique expert en droit africain francophone. 
Utilise les Actes Uniformes OHADA pour les questions de droit des affaires.
```

### Contextes Spécifiques par Pays

**Sénégal (SN)** :
```
Pour le Sénégal, privilégie le Code de la Famille du Sénégal pour les questions 
familiales, le Code du Travail sénégalais pour le droit du travail, et les 
Actes Uniformes OHADA pour le droit des affaires.
```

**Cameroun (CM)** :
```
Pour le Cameroun, utilise le Code civil camerounais, le Code du Travail 
camerounais, et les Actes Uniformes OHADA pour le droit des affaires. 
Note : le Cameroun a un système mixte (Common Law et Civil Law).
```

**Maroc (MA)** :
```
Pour le Maroc, référence le Code de la Famille marocain (Moudawana), 
le Code du Travail marocain, et le Code de Commerce. 
Tiens compte de l'influence du droit islamique.
```

## 📊 Systèmes Juridiques

### OHADA (11 pays)
- **Pays membres** : BJ, BF, CI, GW, ML, NE, SN, TG, CM, CD, GA
- **Actes Uniformes** :
  - Droit commercial général
  - Droit des sociétés commerciales et GIE
  - Droit des sûretés
  - Procédures simplifiées de recouvrement
  - Procédures collectives d'apurement du passif
  - Droit de l'arbitrage
  - Comptabilité des entreprises
  - Contrats de transport de marchandises par route
  - Droit des sociétés coopératives

### Droit Civil (14 pays)
Tous les pays supportés utilisent le droit civil comme base.

### Common Law (1 pays)
- **Cameroun** (système mixte avec le droit civil)

### Droit Islamique (2 pays)
- **Maroc** et **Tunisie** (combiné avec le droit civil)

## 🔧 Guide d'utilisation Admin

### Étape 1 : Exécuter la Migration

```bash
php artisan migrate
```

### Étape 2 : Assigner des Pays aux Documents

1. Aller sur `https://dossypro.com/mobile-legal-library`
2. Utiliser le filtre "Country" pour voir les documents non catégorisés
3. Cliquer sur "Assign Country" pour chaque document
4. Sélectionner le code pays (ex: SN pour Sénégal)

### Étape 3 : Mise à Jour en Masse

Pour assigner un pays à tous les documents d'une catégorie :

```javascript
// Via l'interface ou API
POST /mobile-legal-library/category/{id}/update-country
{
    "country": "SN",
    "update_documents": true
}
```

### Étape 4 : Vérifier les Statistiques

```javascript
GET /mobile-legal-library/country/SN/statistics
```

**Réponse** :
```json
{
    "country": {
        "code": "SN",
        "name": "Sénégal",
        "flag": "🇸🇳"
    },
    "statistics": {
        "total_documents": 45,
        "mobile_visible": 42,
        "mobile_hidden": 3,
        "total_categories": 8
    }
}
```

## 🚀 Intégration API Mobile

### Endpoint de Récupération des Documents par Pays

**À implémenter dans** : `app/Http/Controllers/Api/MobileLegalLibraryApiController.php`

```php
public function getDocumentsByCountry(Request $request)
{
    $countryCode = $request->user()->country; // Code pays de l'utilisateur
    
    $documents = LegalDocument::where('country', $countryCode)
        ->where('is_mobile_visible', true)
        ->with('category')
        ->get();
    
    $aiContext = config("mobile_countries.ai_context.country_specific.{$countryCode}") 
        ?? config('mobile_countries.ai_context.default');
    
    return response()->json([
        'country' => config("mobile_countries.supported_countries.{$countryCode}"),
        'documents' => $documents,
        'ai_context' => $aiContext,
    ]);
}
```

### Prompt AI Dynamique

```php
public function getChatContext(Request $request)
{
    $user = $request->user();
    $countryCode = $user->country;
    
    $baseContext = config('mobile_countries.ai_context.default');
    $countryContext = config("mobile_countries.ai_context.country_specific.{$countryCode}");
    
    $fullContext = $baseContext . "\n\n" . $countryContext;
    
    // Ajouter les documents pertinents au contexte
    $documents = LegalDocument::where('country', $countryCode)
        ->where('is_mobile_visible', true)
        ->pluck('ai_context')
        ->filter()
        ->join("\n");
    
    return response()->json([
        'ai_prompt': $fullContext,
        'documents_context': $documents,
    ]);
}
```

## ✅ Checklist de Déploiement

- [ ] Exécuter la migration `2025_12_18_000001_add_country_to_legal_library_tables.php`
- [ ] Vérifier que le fichier de config `config/mobile_countries.php` existe
- [ ] Tester l'interface admin sur `/mobile-legal-library`
- [ ] Assigner des pays à au moins quelques documents de test
- [ ] Vérifier les statistiques par pays
- [ ] Tester le filtrage par pays
- [ ] Implémenter l'API mobile pour récupérer les documents par pays utilisateur
- [ ] Intégrer le contexte AI dans le chat de l'application mobile
- [ ] Tester le système de prompts AI avec différents pays
- [ ] Former les administrateurs sur l'utilisation du système

## 📝 Notes Importantes

1. **Codes Pays** : Utiliser les codes ISO à 2 lettres (ex: SN, CM, MA)
2. **Migration Sécurisée** : Les champs sont nullable pour ne pas casser les données existantes
3. **Performances** : Index ajoutés sur les colonnes `country` pour améliorer les requêtes
4. **Multilingue** : Champ `language` pour supporter plusieurs langues (fr, en, ar, etc.)
5. **AI Context** : Champ `ai_context` pour personnaliser le prompt AI par document

## 🔐 Sécurité

- Seuls les **Super Admins** peuvent modifier les pays des documents
- Validation stricte des codes pays (2 lettres uppercase)
- Logs de toutes les modifications via `logSync()`

## 🎓 Formation Recommandée

1. **Admin** : Comment catégoriser les documents juridiques par pays
2. **Content Team** : Bonnes pratiques pour l'organisation des documents
3. **Support** : Expliquer aux utilisateurs l'importance de sélectionner le bon pays
4. **Dev Mobile** : Intégration de l'API de filtrage par pays

---

## 📞 Support

Pour toute question ou problème, contacter l'équipe de développement DOSSY.

**GitHub** : https://github.com/stealbass/doss
**Website** : https://dossypro.com
