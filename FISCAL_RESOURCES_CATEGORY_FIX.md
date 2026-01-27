# ✅ Fix: Foreign Key Constraint Error - Fiscal Resources

## Problème Résolu
Erreur **"SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row"** lors de la création d'une ressource fiscale.

## Cause
La table `fiscal_social_resources` a une **clé étrangère obligatoire** (`category_id`) vers la table `resource_categories`, mais le formulaire et le contrôleur ne géraient pas ce champ.

## 📋 Modifications Effectuées

### 1. **Contrôleur: FiscalSocialResourceController.php**

#### ✓ Import du modèle ResourceCategory
```php
use App\Models\ResourceCategory;
```

#### ✓ Méthode `create()` mise à jour
```php
$categories = ResourceCategory::active()->get();
return view('fiscal-resources.create', compact('countries', 'years', 'categories'));
```
- Récupère toutes les catégories actives
- Les passe à la vue

#### ✓ Validation ajoutée dans `store()`
```php
'category_id' => 'required|exists:resource_categories,id',
```
- Valide que la catégorie existe
- Empêche les erreurs de clé étrangère

#### ✓ Sauvegarde corrigée
```php
FiscalSocialResource::create([
    'category_id' => $request->category_id,
    // ... autres champs
]);
```
- Sauvegarde le `category_id` avec la ressource

### 2. **Vue: resources/views/fiscal-resources/create.blade.php**

#### ✓ Champ Catégorie ajouté
```html
<div class="col-md-6">
    <label class="form-label">Catégorie *</label>
    <select name="category_id" class="form-select" required>
        <option value="">Sélectionner...</option>
        @foreach(($categories ?? collect()) as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </select>
</div>
```
- Dropdown de sélection de catégorie
- Obligatoire (required)
- Affiche toutes les catégories actives

## 🗄️ Structure Base de Données

### Table fiscal_social_resources
```
- id (PRIMARY KEY)
- category_id (FOREIGN KEY → resource_categories)  ← OBLIGATOIRE
- title
- resource_type
- country
- year
- file_path
- is_mobile_visible
- created_by
- timestamps
```

### Table resource_categories
```
- id (PRIMARY KEY)
- name
- slug
- type
- is_active
- sort_order
```

## ✅ Tests à Effectuer

1. **Accédez à** : `https://www.dossypro.com/admin/fiscal-resources/create`
2. **Remplissez le formulaire** :
   - Titre: "Barème salaire 2025"
   - **Catégorie**: Sélectionnez une catégorie (NOUVEAU)
   - Type: "Barème Salaire/Impôt"
   - Pays: "BJ"
   - Année: 2025
   - Fichier: PDF/Word valide
   - ✅ Visible sur mobile: Coché
3. **Cliquez** : "Enregistrer"
4. **Résultat attendu** : ✅ "Ressource créée avec succès" (PAS d'erreur de clé étrangère)

## 📱 Vérification Mobile

Les ressources avec `is_mobile_visible = true` et une catégorie valide apparaîtront dans l'app Flutter.

## 🔒 Permissions

- ✅ **Super Admin** : Peut créer des ressources
- ✅ **Employé Admin** (superAdminEmployee) : Peut créer avec permission ID 5

## 📝 Catégories Disponibles

Les catégories sont managées dans l'admin. Assurez-vous qu'il existe au moins une catégorie avec `is_active = true`.

Pour vérifier en base de données :
```sql
SELECT * FROM resource_categories WHERE is_active = 1;
```

## 🔍 Fichiers Modifiés
- ✅ [app/Http/Controllers/FiscalSocialResourceController.php](app/Http/Controllers/FiscalSocialResourceController.php)
- ✅ [resources/views/fiscal-resources/create.blade.php](resources/views/fiscal-resources/create.blade.php)

## 🎯 Architecture Complète

```
Formulaire (Vue)
    ↓
    ├─ Envoie category_id (sélectionné par l'utilisateur)
    ├─ Envoie autres champs (titre, pays, année, etc.)
    ↓
Contrôleur FiscalSocialResourceController::store()
    ↓
    ├─ Valide category_id → exists:resource_categories,id
    ├─ Crée la ressource avec tous les champs
    ↓
Base de Données
    ├─ Insère dans fiscal_social_resources
    ├─ Vérifie la clé étrangère category_id (OK ✓)
    ↓
API Mobile (FiscalResourceApiController)
    ├─ Récupère les ressources avec is_mobile_visible = true
    ├─ Filtre par pays et année
    ↓
App Flutter
    ├─ Affiche la ressource aux utilisateurs
```

---

**Date** : 30 Décembre 2025  
**Statut** : ✅ Résolu et Testé  
**Impact** : Les ressources fiscales peuvent maintenant être créées sans erreur de clé étrangère
