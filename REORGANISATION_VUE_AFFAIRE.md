# 📑 Réorganisation de la Vue Affaire avec Tabs + Notes/Commentaires

## 🎯 Objectif

Réorganiser complètement la page de détails d'une affaire avec un système d'onglets pour une meilleure organisation des informations, et ajouter une nouvelle fonctionnalité de Notes/Commentaires pour la collaboration sur les affaires.

---

## ✨ Nouvelles Fonctionnalités

### 1. 📑 Système d'Onglets (Tabs)

La page de détails d'affaire est maintenant organisée en 4 onglets principaux:

#### **Tab 1: Audiences/Interventions** 🎯
- **Bouton "Créer une audience"** : Ouvre un modal pour créer une nouvelle audience
- **Bouton "Importer"** : Permet d'importer des audiences en masse
- **Liste des audiences** avec colonnes:
  - Numéro
  - Date
  - Remarques
  - Order Sheet (avec lien vers le document)
  - Actions (Modifier, Supprimer)

#### **Tab 2: Documents** 📄
- **Bouton "Ajouter un document"** : Redirige vers la page de gestion des documents
- **Liste des documents** avec colonnes:
  - Numéro
  - Nom du document
  - Type
  - Date de création
  - Actions (Voir, Télécharger)

#### **Tab 3: Tâches** ✅
- **Bouton "Ajouter une tâche"** : Redirige vers la page de création de tâches
- **Liste des tâches** avec colonnes:
  - Numéro
  - Titre
  - Priorité (badge coloré: rouge=haute, orange=moyenne, bleu=basse)
  - Date limite
  - Statut (badge coloré: vert=complété, gris=en cours)
  - Actions (Modifier)

#### **Tab 4: Notes/Commentaires** 💬 **NOUVEAU**
- **Bouton "Ajouter une note"** : Ouvre un modal pour créer une note
- **Affichage des notes** avec:
  - Nom de l'auteur
  - Date et heure de création
  - Contenu de la note
  - Bouton "Répondre"
  - Bouton "Supprimer" (visible seulement pour le créateur ou admin)
- **Réponses hiérarchiques** :
  - Affichées en cascade sous chaque note
  - Bordure à gauche pour visualiser la hiérarchie
  - Même format que les notes principales

---

## 🔧 Implémentation Technique

### Base de Données

#### Table `case_notes`
```sql
CREATE TABLE case_notes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    case_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,  -- Pour les réponses
    note TEXT NOT NULL,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES case_notes(id) ON DELETE CASCADE
);
```

### Modèle CaseNote

**Fichier**: `app/Models/CaseNote.php`

```php
class CaseNote extends Model
{
    protected $fillable = [
        'case_id',
        'user_id',
        'parent_id',
        'note',
        'created_by',
    ];

    // Relation avec l'affaire
    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relation avec la note parente
    public function parent()
    {
        return $this->belongsTo(CaseNote::class, 'parent_id');
    }

    // Relation avec les réponses
    public function replies()
    {
        return $this->hasMany(CaseNote::class, 'parent_id')
            ->orderBy('created_at', 'asc');
    }

    // Vérifier si c'est une réponse
    public function isReply()
    {
        return !is_null($this->parent_id);
    }

    // Récupérer les notes principales d'une affaire
    public static function getMainNotes($case_id)
    {
        return self::where('case_id', $case_id)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
```

### Contrôleur CaseNoteController

**Fichier**: `app/Http/Controllers/CaseNoteController.php`

#### Méthodes principales:

1. **`create($case_id)`** : Affiche le formulaire de création de note
2. **`store(Request $request)`** : Enregistre une nouvelle note
3. **`replyForm($note_id)`** : Affiche le formulaire de réponse
4. **`reply(Request $request)`** : Enregistre une réponse
5. **`destroy($id)`** : Supprime une note (avec vérification des permissions)

### Routes

**Fichier**: `routes/web.php`

```php
// Case Notes Routes
Route::get('case-notes/create/{case_id}', [CaseNoteController::class, 'create'])
    ->name('case-notes.create');
Route::post('case-notes/store', [CaseNoteController::class, 'store'])
    ->name('case-notes.store');
Route::get('case-notes/reply-form/{note_id}', [CaseNoteController::class, 'replyForm'])
    ->name('case-notes.reply-form');
Route::post('case-notes/reply', [CaseNoteController::class, 'reply'])
    ->name('case-notes.reply');
Route::delete('case-notes/{id}', [CaseNoteController::class, 'destroy'])
    ->name('case-notes.destroy');
```

### Vues

#### 1. Vue principale: `resources/views/cases/view.blade.php`

Structure avec Bootstrap Tabs:
```html
<ul class="nav nav-tabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#hearings">
            Audiences/Interventions
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents-content">
            Documents
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tasks">
            Tâches
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes-content">
            Notes/Commentaires
        </button>
    </li>
</ul>
```

#### 2. Formulaire création note: `resources/views/case_notes/create.blade.php`

```blade
{{ Form::open(['route' => 'case-notes.store', 'method' => 'post']) }}
<div class="form-group">
    {{ Form::label('note', __('Note / Commentaire')) }}
    {{ Form::textarea('note', null, [
        'class' => 'form-control',
        'rows' => 4,
        'required' => 'required',
        'placeholder' => __('Saisissez votre note...')
    ]) }}
</div>
<input type="hidden" name="case_id" value="{{ $case->id }}">
{{ Form::close() }}
```

#### 3. Formulaire réponse: `resources/views/case_notes/reply.blade.php`

```blade
{{ Form::open(['route' => 'case-notes.reply', 'method' => 'post']) }}
<div class="form-group">
    {{ Form::label('note', __('Répondre')) }}
    {{ Form::textarea('note', null, [
        'class' => 'form-control',
        'rows' => 3,
        'required' => 'required'
    ]) }}
</div>
<input type="hidden" name="case_id" value="{{ $note->case_id }}">
<input type="hidden" name="parent_id" value="{{ $note->id }}">
{{ Form::close() }}
```

---

## 🎨 Design et Interface

### Couleurs et Style
- **Tabs Bootstrap** : Style natif avec icônes
- **Badges de priorité** :
  - Haute: `badge bg-danger` (rouge)
  - Moyenne: `badge bg-warning` (orange)
  - Basse: `badge bg-info` (bleu)
- **Badges de statut** :
  - Complété: `badge bg-success` (vert)
  - En cours: `badge bg-secondary` (gris)
- **Notes** :
  - Carte avec bordure
  - Réponses: fond clair (`bg-light`), bordure à gauche

### Responsive
- Tableaux avec `table-responsive`
- Grille Bootstrap pour les informations de l'affaire
- Tabs s'adaptent aux petits écrans

---

## 🔒 Permissions et Sécurité

### Création de Notes
- ✅ **Tous les utilisateurs assignés** à l'affaire peuvent créer des notes
- Les utilisateurs sont identifiés via:
  - Champ `advocates` (liste des avocats assignés)
  - Champ `your_party_name` (clients/plaignants)

### Réponses
- ✅ **Tous les utilisateurs assignés** peuvent répondre à n'importe quelle note

### Suppression
- ✅ **Créateur de la note** : Peut supprimer sa propre note
- ✅ **Admin (type='company')** : Peut supprimer n'importe quelle note
- ❌ **Autres utilisateurs** : Ne peuvent pas supprimer

**Code de vérification**:
```php
if ($note->user_id != Auth::user()->id && Auth::user()->type != 'company') {
    return redirect()->back()->with('error', __('Permission refusée.'));
}
```

---

## 📊 Flux de Données

### Création d'une Note

```
1. Utilisateur clique sur "Ajouter une note"
2. Modal s'ouvre avec formulaire
3. Utilisateur saisit la note
4. Submit → CaseNoteController@store
5. Validation des données
6. Création de la note en BD
7. Redirection avec message de succès
8. Note apparaît dans la liste
```

### Réponse à une Note

```
1. Utilisateur clique sur "Répondre" sous une note
2. Modal s'ouvre avec formulaire de réponse
3. Utilisateur saisit la réponse
4. Submit → CaseNoteController@reply
5. Validation (case_id, parent_id, note)
6. Création de la réponse avec parent_id
7. Redirection avec message de succès
8. Réponse apparaît sous la note parente
```

### Affichage des Notes

```
1. CaseController@show récupère l'affaire
2. Appel CaseNote::getMainNotes($case_id)
3. Récupération des notes principales avec leurs réponses
4. Eager loading: user, replies.user
5. Tri par date décroissante
6. Passage au view
7. Boucle sur les notes
8. Pour chaque note, boucle sur les réponses
```

---

## 🌐 Traductions (Français)

Toutes les chaînes ont été ajoutées dans `resources/lang/fr.json`:

```json
{
    "Détails de l'affaire": "Détails de l'affaire",
    "Audiences/Interventions": "Audiences/Interventions",
    "Documents": "Documents",
    "Tâches": "Tâches",
    "Notes/Commentaires": "Notes/Commentaires",
    "Créer une audience": "Créer une audience",
    "Importer": "Importer",
    "Ajouter un document": "Ajouter un document",
    "Ajouter une tâche": "Ajouter une tâche",
    "Ajouter une note": "Ajouter une note",
    "Note / Commentaire": "Note / Commentaire",
    "Répondre": "Répondre",
    "Saisissez votre note ou commentaire...": "Saisissez votre note ou commentaire...",
    "Note ajoutée avec succès.": "Note ajoutée avec succès.",
    "Réponse ajoutée avec succès.": "Réponse ajoutée avec succès.",
    "Note supprimée avec succès.": "Note supprimée avec succès.",
    "Aucune note ou commentaire pour le moment": "Aucune note ou commentaire pour le moment"
}
```

---

## 📱 Utilisation

### Pour les Utilisateurs

#### Ajouter une Note
1. Ouvrir une affaire
2. Cliquer sur l'onglet "Notes/Commentaires"
3. Cliquer sur "Ajouter une note"
4. Saisir le texte de la note
5. Cliquer sur "Ajouter la note"
6. ✅ La note apparaît immédiatement dans la liste

#### Répondre à une Note
1. Dans l'onglet "Notes/Commentaires"
2. Trouver la note à laquelle répondre
3. Cliquer sur "Répondre"
4. Saisir la réponse
5. Cliquer sur "Répondre"
6. ✅ La réponse apparaît en dessous de la note

#### Supprimer une Note
1. Trouver sa propre note (ou n'importe quelle note si admin)
2. Cliquer sur l'icône poubelle (🗑️)
3. Confirmer la suppression
4. ✅ La note (et ses réponses) sont supprimées

### Navigation entre les Tabs
- Cliquer sur les onglets pour naviguer
- Les données sont déjà chargées (pas de rechargement)
- L'onglet actif est mis en évidence

---

## 🚀 Déploiement

### Étapes sur le Serveur

```bash
# 1. Tirer les dernières modifications
git pull origin main

# 2. Exécuter la migration
php artisan migrate

# 3. Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 4. Optimiser (facultatif)
php artisan optimize
```

### Vérifications Post-Déploiement

- ✅ Table `case_notes` créée dans la BD
- ✅ Onglets visibles dans la vue affaire
- ✅ Boutons "Ajouter une note" et "Répondre" fonctionnels
- ✅ Notes affichées avec auteur et date
- ✅ Réponses hiérarchiques correctes
- ✅ Permissions de suppression respectées
- ✅ Messages de succès/erreur affichés

---

## 🐛 Dépannage

### La migration échoue
```bash
# Vérifier si la table existe déjà
SHOW TABLES LIKE 'case_notes';

# Si elle existe, supprimer et recréer
DROP TABLE case_notes;
php artisan migrate
```

### Les notes ne s'affichent pas
1. Vérifier que `$notes` est passé au view dans `CaseController@show`
2. Vérifier la relation `user` dans le modèle `CaseNote`
3. Vérifier les permissions de l'utilisateur connecté

### Erreur 404 sur les routes
```bash
# Vider le cache des routes
php artisan route:clear
php artisan route:cache
```

### Les traductions ne fonctionnent pas
```bash
# Vider le cache de traduction
php artisan cache:clear
php artisan config:clear
```

---

## 📈 Améliorations Futures Possibles

1. **Mentions (@)** : Mentionner d'autres utilisateurs dans les notes
2. **Pièces jointes** : Ajouter des fichiers aux notes
3. **Édition de notes** : Permettre de modifier les notes existantes
4. **Notifications** : Notifier par email lors de nouvelles notes/réponses
5. **Recherche** : Rechercher dans les notes d'une affaire
6. **Export** : Exporter les notes en PDF
7. **Tags** : Catégoriser les notes avec des tags

---

## ✅ Résumé des Changements

### Fichiers Créés
- ✅ `app/Models/CaseNote.php`
- ✅ `app/Http/Controllers/CaseNoteController.php`
- ✅ `database/migrations/2025_11_16_000001_create_case_notes_table.php`
- ✅ `resources/views/case_notes/create.blade.php`
- ✅ `resources/views/case_notes/reply.blade.php`

### Fichiers Modifiés
- ✅ `app/Http/Controllers/CaseController.php` (ajout $todos et $notes)
- ✅ `resources/views/cases/view.blade.php` (réorganisation complète)
- ✅ `routes/web.php` (ajout routes notes)
- ✅ `resources/lang/fr.json` (ajout traductions)

### Fonctionnalités
- ✅ Système d'onglets Bootstrap
- ✅ Tab Audiences/Interventions
- ✅ Tab Documents
- ✅ Tab Tâches
- ✅ Tab Notes/Commentaires avec réponses hiérarchiques
- ✅ Permissions de création, réponse et suppression
- ✅ Interface utilisateur intuitive
- ✅ Traductions françaises complètes

---

**Cette fonctionnalité est maintenant prête à être utilisée en production! 🎉**

Pour toute question ou suggestion, n'hésitez pas à créer une issue sur GitHub.
