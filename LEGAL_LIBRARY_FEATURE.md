# 📚 Bibliothèque Juridique - Documentation

## Vue d'ensemble

Cette fonctionnalité ajoute une bibliothèque juridique complète à l'application Dossy Pro, permettant aux administrateurs de gérer des documents PDF juridiques organisés par catégories, et aux utilisateurs de les consulter et télécharger.

## 🎯 Fonctionnalités

### Pour les Administrateurs (`manage legal library`)

1. **Gestion des Catégories**
   - Créer des catégories de documents juridiques
   - Modifier les catégories existantes
   - Supprimer des catégories (supprime également tous les documents associés)
   - Voir le nombre de documents par catégorie

2. **Gestion des Documents**
   - Uploader des fichiers PDF (max 20MB)
   - Ajouter titre et description aux documents
   - Modifier les informations des documents
   - Remplacer les fichiers PDF
   - Supprimer des documents
   - Voir les statistiques de téléchargement

### Pour les Utilisateurs (`view legal library`)

1. **Recherche de Documents**
   - Recherche par titre ou description
   - Résultats affichés avec toutes les informations pertinentes

2. **Navigation par Catégorie**
   - Vue en grille des catégories disponibles
   - Nombre de documents par catégorie
   - Accès aux documents d'une catégorie

3. **Consultation de Documents**
   - Prévisualisation PDF intégrée dans le navigateur
   - Téléchargement de fichiers
   - Statistiques de téléchargement

## 📁 Structure des Fichiers

### Migrations
- `database/migrations/2024_11_15_000001_create_legal_categories_table.php`
- `database/migrations/2024_11_15_000002_create_legal_documents_table.php`
- `database/migrations/2024_11_15_000003_add_legal_library_permissions.php`

### Modèles
- `app/Models/LegalCategory.php` - Gestion des catégories
- `app/Models/LegalDocument.php` - Gestion des documents

### Contrôleurs
- `app/Http/Controllers/LegalLibraryController.php` - Administration
- `app/Http/Controllers/UserLegalLibraryController.php` - Accès utilisateur

### Vues Administration
- `resources/views/legal-library/index.blade.php` - Liste des catégories
- `resources/views/legal-library/create-category.blade.php` - Créer catégorie
- `resources/views/legal-library/edit-category.blade.php` - Modifier catégorie
- `resources/views/legal-library/documents.blade.php` - Liste des documents
- `resources/views/legal-library/create-document.blade.php` - Upload document
- `resources/views/legal-library/edit-document.blade.php` - Modifier document

### Vues Utilisateur
- `resources/views/user-legal-library/index.blade.php` - Page d'accueil avec recherche
- `resources/views/user-legal-library/category.blade.php` - Documents d'une catégorie
- `resources/views/user-legal-library/view.blade.php` - Prévisualisation PDF

## 🔗 Routes

### Routes Administration
```
GET     /legal-library                                    - Liste des catégories
GET     /legal-library/category/create                    - Formulaire création catégorie
POST    /legal-library/category/store                     - Enregistrer catégorie
GET     /legal-library/category/{id}/edit                 - Formulaire édition catégorie
PUT     /legal-library/category/{id}                      - Mettre à jour catégorie
DELETE  /legal-library/category/{id}                      - Supprimer catégorie
GET     /legal-library/category/{categoryId}/documents    - Liste des documents
GET     /legal-library/category/{categoryId}/document/create - Formulaire upload
POST    /legal-library/category/{categoryId}/document/store - Upload document
GET     /legal-library/document/{id}/edit                 - Formulaire édition document
PUT     /legal-library/document/{id}                      - Mettre à jour document
DELETE  /legal-library/document/{id}                      - Supprimer document
GET     /legal-library/document/{id}/download             - Télécharger document
```

### Routes Utilisateur
```
GET     /library                            - Page d'accueil et recherche
GET     /library/category/{categoryId}      - Documents d'une catégorie
GET     /library/document/{id}/view         - Prévisualiser document
GET     /library/document/{id}/download     - Télécharger document
```

## 🔐 Permissions

### `manage legal library`
- Accordée aux administrateurs (role: `company`)
- Permet la gestion complète des catégories et documents

### `view legal library`
- Accordée aux utilisateurs (roles: `advocate`, `client`, `co advocate`, `team leader`)
- Permet de consulter et télécharger les documents

## 💾 Base de Données

### Table `legal_categories`
| Colonne      | Type    | Description                    |
|--------------|---------|--------------------------------|
| id           | bigint  | Clé primaire                   |
| name         | string  | Nom de la catégorie            |
| description  | text    | Description (nullable)         |
| slug         | string  | Slug unique (auto-généré)      |
| created_by   | int     | ID du créateur                 |
| created_at   | timestamp | Date de création             |
| updated_at   | timestamp | Date de mise à jour          |

### Table `legal_documents`
| Colonne          | Type    | Description                    |
|------------------|---------|--------------------------------|
| id               | bigint  | Clé primaire                   |
| category_id      | bigint  | FK vers legal_categories       |
| title            | string  | Titre du document              |
| description      | text    | Description (nullable)         |
| file_path        | string  | Chemin du fichier              |
| file_name        | string  | Nom original du fichier        |
| file_size        | bigint  | Taille en bytes                |
| downloads_count  | int     | Nombre de téléchargements      |
| created_by       | int     | ID du créateur                 |
| created_at       | timestamp | Date de création             |
| updated_at       | timestamp | Date de mise à jour          |

## 📦 Stockage

Les fichiers PDF sont stockés dans :
```
storage/app/public/legal_documents/
```

Assurez-vous que le lien symbolique est créé :
```bash
php artisan storage:link
```

## 🚀 Installation

### 1. Exécuter les migrations
```bash
php artisan migrate
```

### 2. Créer le lien symbolique (si ce n'est pas déjà fait)
```bash
php artisan storage:link
```

### 3. Configurer les permissions
Les permissions sont automatiquement créées lors de la migration. Vous pouvez les ajuster manuellement depuis l'interface d'administration des rôles et permissions.

## 📝 Utilisation

### Pour l'administrateur

1. **Accéder à la bibliothèque**
   - Cliquer sur "Legal Library" dans le menu principal

2. **Créer une catégorie**
   - Cliquer sur "Create Category"
   - Remplir le nom et la description
   - Enregistrer

3. **Ajouter des documents**
   - Cliquer sur une catégorie
   - Cliquer sur "Upload Document"
   - Remplir les informations et sélectionner le PDF
   - Upload

### Pour l'utilisateur

1. **Rechercher un document**
   - Accéder à "Library" dans le menu
   - Utiliser la barre de recherche
   - Cliquer sur "View" ou "Download"

2. **Parcourir par catégorie**
   - Accéder à "Library"
   - Cliquer sur une catégorie
   - Consulter les documents disponibles

## ⚠️ Limitations

- Taille maximale des fichiers : **20MB**
- Format accepté : **PDF uniquement**
- La suppression d'une catégorie supprime tous ses documents

## 🔧 Personnalisation

### Modifier la taille maximale des fichiers

Dans `app/Http/Controllers/LegalLibraryController.php`, ligne 185 :
```php
'file' => 'required|file|mimes:pdf|max:20480', // 20MB max
```

### Ajouter d'autres formats de fichiers

Modifier la validation dans le même fichier :
```php
'file' => 'required|file|mimes:pdf,doc,docx|max:20480',
```

## 🐛 Dépannage

### Les fichiers ne s'affichent pas
- Vérifier que `php artisan storage:link` a été exécuté
- Vérifier les permissions du dossier `storage/app/public/`

### Erreur d'upload
- Vérifier la taille maximale dans `php.ini` :
  - `upload_max_filesize`
  - `post_max_size`

### Permissions manquantes
- Vérifier que les migrations ont été exécutées
- Assigner manuellement les permissions aux rôles

## 📞 Support

Pour toute question ou problème, veuillez créer une issue dans le repository GitHub.
