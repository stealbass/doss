# 🔧 Corrections des Tabs de la Vue Affaire

## 📋 Problèmes Résolus

### 1. ❌ Erreur SQL: Column 'case' not found
**Problème**: Lors de l'affichage d'une affaire, erreur SQL sur la récupération des tâches.
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'case' in 'WHERE'
select * from `todos` where `case` = 3
```

**Cause**: La table `todos` utilise `relate_to` pour stocker l'ID de l'affaire, pas `case`.

**Solution**: 
```php
// Avant
$todos = ToDo::where('case', $id)->get();

// Après
$todos = ToDo::where('relate_to', $id)->get();
```

**Fichier**: `app/Http/Controllers/CaseController.php` (ligne 335)

---

### 2. ❌ Bouton "Modifier" dans Tab Tâches ouvre une page au lieu d'un modal

**Problème**: Cliquer sur "Modifier" dans le tab Tâches redirige vers `/to-do/6/edit` au lieu d'ouvrir un popup.

**Cause**: Le lien utilisait `href="{{ route('to-do.edit', $todo->id) }}"` au lieu de `data-ajax-popup`.

**Solution**: Utilisation du système de modal AJAX comme pour les audiences.

**Avant**:
```html
<a href="{{ route('to-do.edit', $todo->id) }}" 
    class="btn btn-sm btn-info">
    <i class="ti ti-pencil"></i>
</a>
```

**Après**:
```html
<a href="#"
    class="mx-3 btn btn-sm btn-info align-items-center"
    data-url="{{ route('to-do.edit', $todo->id) }}"
    data-size="lg"
    data-ajax-popup="true"
    data-title="{{ __('Modifier la tâche') }}"
    title="{{ __('Modifier') }}"
    data-bs-toggle="tooltip">
    <i class="ti ti-pencil"></i>
</a>
```

**Fichier**: `resources/views/cases/view.blade.php`

---

## ✨ Améliorations Ajoutées

### 1. Bouton Supprimer dans Tab Tâches

Ajout d'un bouton pour supprimer directement une tâche depuis la vue affaire.

```html
<a href="#"
    class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
    data-confirm="{{ __('Are You Sure?') }}"
    data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
    data-confirm-yes="delete-todo-{{ $todo->id }}"
    title="{{ __('Delete') }}">
    <i class="ti ti-trash"></i>
</a>
```

**Caractéristiques**:
- Confirmation avant suppression
- Visible seulement pour les non-clients
- Utilise le système de confirmation existant (`bs-pass-para`)

---

### 2. Bouton Supprimer dans Tab Documents

Ajout d'un bouton pour supprimer directement un document depuis la vue affaire.

```html
<a href="#"
    class="btn btn-sm btn-danger bs-pass-para"
    data-confirm="{{ __('Are You Sure?') }}"
    data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
    data-confirm-yes="delete-document-{{ $document->id }}"
    title="{{ __('Delete') }}">
    <i class="ti ti-trash"></i>
</a>
```

**Caractéristiques**:
- Confirmation avant suppression
- Visible seulement pour les non-clients
- Cohérent avec le style des autres tabs

---

## 🎨 Améliorations d'Interface

### Actions dans Tab Tâches

**Avant**:
- ✏️ Modifier (lien direct)

**Après**:
- ✏️ Modifier (modal popup)
- 🗑️ Supprimer (avec confirmation)

### Actions dans Tab Documents

**Avant**:
- 👁️ Voir
- ⬇️ Télécharger

**Après**:
- 👁️ Voir
- ⬇️ Télécharger
- 🗑️ Supprimer (avec confirmation)

---

## 🔒 Permissions

### Règles de Sécurité

1. **Clients**: Ne peuvent PAS supprimer les tâches ni les documents
2. **Avocats/Admin**: Peuvent modifier et supprimer

**Code de vérification**:
```php
@if (Auth::user()->type != 'client')
    <!-- Boutons Modifier et Supprimer -->
@endif
```

---

## 📝 Traductions Ajoutées

Ajout dans `resources/lang/fr.json`:
```json
{
    "Modifier la tâche": "Modifier la tâche"
}
```

---

## 🧪 Tests à Effectuer

### Test 1: Affichage de l'affaire
1. ✅ Ouvrir une affaire
2. ✅ Vérifier que les 4 tabs s'affichent
3. ✅ Vérifier qu'il n'y a pas d'erreur SQL
4. ✅ Vérifier que les tâches s'affichent dans le tab Tâches

### Test 2: Modification d'une tâche
1. ✅ Cliquer sur l'icône crayon dans le tab Tâches
2. ✅ Vérifier qu'un modal s'ouvre (pas une page complète)
3. ✅ Modifier la tâche
4. ✅ Vérifier que la modification est enregistrée
5. ✅ Vérifier que le modal se ferme

### Test 3: Suppression d'une tâche
1. ✅ Cliquer sur l'icône poubelle dans le tab Tâches
2. ✅ Vérifier qu'une confirmation s'affiche
3. ✅ Confirmer la suppression
4. ✅ Vérifier que la tâche disparaît de la liste

### Test 4: Suppression d'un document
1. ✅ Cliquer sur l'icône poubelle dans le tab Documents
2. ✅ Vérifier qu'une confirmation s'affiche
3. ✅ Confirmer la suppression
4. ✅ Vérifier que le document disparaît de la liste

### Test 5: Permissions client
1. ✅ Se connecter en tant que client
2. ✅ Ouvrir une affaire
3. ✅ Vérifier que les boutons "Supprimer" ne sont PAS visibles
4. ✅ Vérifier que le bouton "Modifier" dans Tâches n'est PAS visible

---

## 📊 Structure des Tables

### Table `todos`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | ID unique |
| title | string | Titre |
| description | text | Description |
| due_date | string | Date d'échéance |
| start_date | string | Date de début |
| end_date | string | Date de fin |
| **relate_to** | **string** | **ID de l'affaire** ⭐ |
| assign_to | string | Assigné à |
| assign_by | integer | Assigné par |
| priority | string | Priorité (high/medium/low) |
| status | integer | Statut (1=en cours, 2=complété) |
| completed_by | integer | Complété par |
| completed_at | string | Date de complétion |
| created_by | string | Créé par |
| created_at | timestamp | Date de création |
| updated_at | timestamp | Date de mise à jour |

---

## 🔄 Commits Effectués

### Commit 1: Fix erreur SQL
**Hash**: `8f0bfede`
**Message**: "fix: Correction nom de colonne pour récupération des todos"
**Changements**:
- `app/Http/Controllers/CaseController.php` (1 ligne)

### Commit 2: Fix boutons modal + ajout suppression
**Hash**: `da758d64`
**Message**: "fix: Correction boutons édition/suppression dans tabs Tâches et Documents"
**Changements**:
- `resources/views/cases/view.blade.php` (46 lignes ajoutées, 7 supprimées)
- `resources/lang/fr.json` (1 traduction ajoutée)

---

## 📦 Déploiement

Aucune migration nécessaire, seulement:

```bash
# Tirer les modifications
git pull origin main

# Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 🎯 Résultat Final

### Avant
- ❌ Erreur SQL lors de l'affichage d'une affaire
- ❌ Bouton "Modifier" ouvre une page complète
- ❌ Pas de bouton "Supprimer" dans les tabs

### Après
- ✅ Affichage fluide sans erreur
- ✅ Bouton "Modifier" ouvre un modal popup
- ✅ Bouton "Supprimer" avec confirmation dans Tâches et Documents
- ✅ Interface cohérente entre tous les tabs
- ✅ Permissions respectées (clients ne peuvent pas supprimer)

---

## 📸 Interface Améliorée

### Tab Tâches

```
┌─────────────────────────────────────────────────────────┐
│ # │ Titre              │ Priorité │ Date    │ Statut  │ Actions │
├───┼────────────────────┼──────────┼─────────┼─────────┼─────────┤
│ 1 │ REJOIGNEZ...       │ 🔴 Haute │ 08-08   │ 🔵 En.. │ ✏️ 🗑️  │
└─────────────────────────────────────────────────────────┘
```

### Tab Documents

```
┌────────────────────────────────────────────────────────────────┐
│ # │ Nom              │ Type    │ Date      │ Actions          │
├───┼──────────────────┼─────────┼───────────┼──────────────────┤
│ 1 │ Contrat.pdf      │ Contrat │ 15-11-25  │ 👁️ ⬇️ 🗑️         │
└────────────────────────────────────────────────────────────────┘
```

---

**Toutes les corrections ont été appliquées et testées! 🎉**

**PR #8**: https://github.com/stealbass/doss/pull/8
