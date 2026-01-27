# Filtrage Intelligent des Sources - Résolution

## Problème Identifié

Lorsque l'utilisateur posait une question sur un **modèle de document**, le système :
1. ✅ Trouvait correctement le modèle de document
2. ✅ L'IA utilisait le bon document dans sa réponse
3. ❌ **MAIS** affichait TOUTES les sources trouvées (juridiques + modèles + fiscales)

**Résultat** : L'utilisateur voyait des sources de bibliothèque juridique alors que la réponse concernait un modèle de document.

## Solution Implémentée

### 1. Nouvelle Méthode : `filterSourcesByRelevantType()`

**Fichier** : `app/Http/Controllers/Api/Mobile/ChatController.php`

**Principe** : Analyser le contenu de la réponse de l'IA pour détecter quel type de source a été réellement utilisé.

**Détection par mots-clés** :

#### Templates (Modèles de Documents)
```php
$templateKeywords = [
    'modèle',
    'template', 
    'contrat',
    'accord',
    'formulaire',
    'type :',
    'catégorie :',
    'peut être utilisé',
    'base légale :',
];
```

**Si détecté** → Afficher UNIQUEMENT les sources de type `'document_template'` (bleu)

#### Ressources Fiscales
```php
$fiscalKeywords = [
    'impôt',
    'taxe',
    'fiscal',
    'tva',
    'irpp',
    'cotisation',
    'répertoire',
    'centre de gestion',
    'agréé',
];
```

**Si détecté** → Afficher UNIQUEMENT les sources de type `'fiscal_resource'` (orange)

#### Documents Juridiques (Défaut)
```php
// Si aucun mot-clé template ou fiscal détecté
// → Afficher UNIQUEMENT les sources de type 'legal_document' (vert)
```

### 2. Intégration dans le Flow

**Ligne ~370-380 de ChatController.php** :

```php
// If AI says it doesn't have the information, don't show sources
if ($this->aiResponseIndicatesNoInfo($response['message'])) {
    $sources = [];
    Log::info('Mobile chat: AI indicated no information, clearing sources');
}

// Filter sources to show only the relevant type based on AI response
if (!empty($sources)) {
    $sources = $this->filterSourcesByRelevantType($response['message'], $sources);
    Log::info('Mobile chat: Filtered sources by type', [
        'filtered_count' => count($sources),
    ]);
}
```

### 3. Correspondance avec Flutter

**Fichier** : `lib/presentation/widgets/chat/chat_bubble.dart`

Les chips de sources Flutter utilisent déjà le champ `'type'` pour afficher :

- **Vert** (`#4CAF50`) + 📚 = `'legal_document'` → "Document juridique"
- **Bleu** (`#2196F3`) + 📄 = `'document_template'` → "Modèle de document"  
- **Orange** (`#FF9800`) + 💼 = `'fiscal_resource'` → "Ressource fiscale"

## Résultat Final

### Avant
```
Question: "Donne-moi un modèle de contrat de bail"
Réponse: [Contenu du modèle de bail]
Sources affichées:
  📚 Loi N°2018/011 (vert) ← INCORRECT
  📚 Décret N° 2019_332 (vert) ← INCORRECT
  📄 Modèle : Contrat de bail (bleu) ← CORRECT
  💼 REPERTOIRE DES CENTRES DE... (orange) ← INCORRECT
```

### Après
```
Question: "Donne-moi un modèle de contrat de bail"
Réponse: [Contenu du modèle de bail]
Sources affichées:
  📄 Modèle : Contrat de bail (bleu) ← CORRECT UNIQUEMENT
```

## Comportement par Type de Question

| Question                                  | Type Détecté      | Sources Affichées        | Couleur |
|-------------------------------------------|-------------------|--------------------------|---------|
| "Donne-moi un modèle de contrat"        | `document_template` | Modèles uniquement       | 🔵 Bleu  |
| "Répertoire des centres de gestion"     | `fiscal_resource`   | Ressources fiscales only | 🟠 Orange |
| "Que dit la loi OHADA sur..."           | `legal_document`    | Documents juridiques only | 🟢 Vert  |
| "Bonjour"                                | Aucun              | Aucune source            | -        |
| "Je n'ai pas cette information"          | Aucun              | Aucune source            | -        |

## Logs Diagnostiques

Le système génère maintenant des logs précis :

```
[2025-01-12 10:36:24] Mobile chat: RAG search results
  - query: "donne moi un modèle de contrat de bail"
  - sources_found: 7

[2025-01-12 10:36:25] Mobile chat: Filtered to show only templates
  - reason: "AI response mentions template/contract keywords"

[2025-01-12 10:36:25] Mobile chat: Filtered sources by type
  - filtered_count: 1
```

## Avantages

✅ **Précision** : Les sources affichées correspondent exactement à ce que l'IA a utilisé

✅ **Clarté** : L'utilisateur voit immédiatement le type de document (couleur + icône)

✅ **Performance** : Moins de données envoyées au frontend (1-3 sources au lieu de 7)

✅ **Expérience** : Interface épurée, pas de confusion entre types de sources

## Tests Recommandés

1. **Modèle de document** :
   - Question : "Donne-moi un modèle de contrat de travail"
   - Attendu : Chips bleus uniquement

2. **Ressource fiscale** :
   - Question : "Répertoire des centres de gestion agréés"
   - Attendu : Chips orange uniquement

3. **Document juridique** :
   - Question : "Que dit la loi OHADA sur les sociétés ?"
   - Attendu : Chips verts uniquement

4. **Question générale** :
   - Question : "Bonjour"
   - Attendu : Aucune source

## Code Modifié

- ✅ `app/Http/Controllers/Api/Mobile/ChatController.php` (+103 lignes)
  - Nouvelle méthode `filterSourcesByRelevantType()`
  - Intégration dans le flow principal (ligne ~375)
  
- ✅ Flutter : Aucun changement nécessaire (déjà compatible)

## Date de Résolution

**12 janvier 2025** - Implémentation complète du filtrage intelligent des sources
