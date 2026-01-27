# Solution: Créer un rôle "Content Manager" pour l'administration du contenu

## 📋 Vue d'ensemble
Ce document explique comment créer un nouvel employé (superadmin) qui peut gérer:
1. **Legal Library** - Bibliothèque juridique
2. **Document Template** - Modèles de documents (Mobile App)
3. **Fiscal Resources** - Ressources fiscales et sociales

## 🔧 Étapes d'implémentation

### 1. Exécuter le Seeder pour créer le rôle

```bash
php artisan db:seed --class=CreateContentManagerRoleSeeder
```

Cela créera:
- Rôle: `content_manager`
- Permissions associées pour les 3 modules

### 2. Mettre à jour les contrôleurs

Les contrôleurs suivants doivent être modifiés pour accepter le rôle `content_manager`:

#### A. LegalLibraryController (app/Http/Controllers/LegalLibraryController.php)

**Avant:**
```php
public function index()
{
    if (Auth::user()->type == 'super admin') {
        // ...
    } else {
        return redirect()->back()->with('error', __('Permission Denied.'));
    }
}
```

**Après:**
```php
public function index()
{
    $user = Auth::user();
    if ($user->type == 'super admin' || $user->hasRole('content_manager')) {
        // ...
    } else {
        return redirect()->back()->with('error', __('Permission Denied.'));
    }
}
```

#### B. DocumentTemplateController (app/Http/Controllers/DocumentTemplateController.php)

**Avant:**
```php
public function index(Request $request)
{
    if (Auth::user()->type !== 'super admin') {
        return redirect()->back()->with('error', __('Permission Denied.'));
    }
    // ...
}
```

**Après:**
```php
public function index(Request $request)
{
    $user = Auth::user();
    if ($user->type !== 'super admin' && !$user->hasRole('content_manager')) {
        return redirect()->back()->with('error', __('Permission Denied.'));
    }
    // ...
}
```

Répéter pour: `create()`, `edit()`, `show()`, `store()`, `update()`, `destroy()`

#### C. FiscalSocialResourceController (app/Http/Controllers/FiscalSocialResourceController.php)

**Avant:**
```php
public function create()
{
    if (auth()->user()->type !== 'super admin') {
        return redirect()->route('fiscal-resources.index')->with('error', __('Permission Denied.'));
    }
    // ...
}
```

**Après:**
```php
public function create()
{
    $user = auth()->user();
    if ($user->type !== 'super admin' && !$user->hasRole('content_manager')) {
        return redirect()->route('fiscal-resources.index')->with('error', __('Permission Denied.'));
    }
    // ...
}
```

Répéter pour: `show()`, `edit()`, `store()`, `update()`, `destroy()`

#### D. MobileDashboardController (optionnel - pour accès Mobile App)

Si l'employé doit accéder au dashboard Mobile:
```php
public function index()
{
    $user = Auth::user();
    if ($user->type !== 'super admin' && !$user->hasRole('content_manager')) {
        return redirect()->back()->with('error', __('Permission Denied.'));
    }
    // ...
}
```

### 3. Assigner le rôle à l'employé (via l'interface Admin)

1. Allez dans **Employees** (Screenshot montré)
2. Cliquez sur **Create Employee**
3. Remplissez les champs:
   - **Name**: Nom de l'employé
   - **Email**: Email professionnel
   - **Password**: Mot de passe sécurisé (min 8 caractères)
4. Dans **Assign Permission**:
   - Sélectionnez le rôle **"content_manager"** (à créer via le rôle management si pas automatique)
   - OU assignez les permissions individuelles:
     - ✅ `view legal-library`
     - ✅ `manage legal-library`
     - ✅ `create legal-library`
     - ✅ `edit legal-library`
     - ✅ `delete legal-library`
     - ✅ `view document-template`
     - ✅ `manage document-template`
     - ✅ `create document-template`
     - ✅ `edit document-template`
     - ✅ `delete document-template`
     - ✅ `view fiscal-resources`
     - ✅ `manage fiscal-resources`
     - ✅ `create fiscal-resources`
     - ✅ `edit fiscal-resources`
     - ✅ `delete fiscal-resources`
     - ✅ `view mobile-app`
     - ✅ `manage mobile-app`

5. Cliquez sur **Create** ou **Save**

### 4. Accès de l'employé

Une fois assigné avec le rôle **content_manager**, l'employé peut:

**Menu accessible:**
- ✅ Legal Library (édition complète)
- ✅ Mobile App > Document Templates (édition complète)
- ✅ Settings > Fiscal Resources (édition complète)

**Menu restreint:**
- ❌ Employees
- ❌ Roles & Permissions  
- ❌ System Settings
- ❌ Plans & Subscriptions
- ❌ Autres modules d'administration

## 🔐 Sécurité

- Le rôle `content_manager` est limité à 3 modules spécifiques
- Toutes les actions (create, edit, delete) sont vérifiées côté serveur
- Les permissions sont basées sur Spatie Permission (plus sûr que les vérifications type)

## ✅ Checklist d'implémentation

- [ ] Exécuter le seeder: `php artisan db:seed --class=CreateContentManagerRoleSeeder`
- [ ] Mettre à jour LegalLibraryController (index, create, show, store, update, destroy)
- [ ] Mettre à jour DocumentTemplateController (index, create, show, store, update, destroy)
- [ ] Mettre à jour FiscalSocialResourceController (index, create, show, store, update, destroy)
- [ ] Mettre à jour MobileDashboardController (si nécessaire)
- [ ] Tester la création d'un nouvel employé avec le rôle "content_manager"
- [ ] Vérifier que l'employé peut accéder et éditer les 3 modules
- [ ] Vérifier que l'employé ne peut pas accéder aux autres modules

## 📝 Notes supplémentaires

**Si vous voulez créer directement l'employé en SQL:**

```sql
-- 1. Créer l'utilisateur
INSERT INTO users (name, email, password, type, created_at, updated_at)
VALUES ('John Doe', 'john@example.com', BCRYPT('password123'), 'super admin', NOW(), NOW());

-- 2. Assigner le rôle (récupérer l'ID de l'utilisateur créé)
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT id, 'App\\Models\\User', LAST_INSERT_ID() FROM roles WHERE name = 'content_manager';
```

**Pour vérifier les rôles/permissions d'un utilisateur:**

```sql
-- Voir tous les rôles
SELECT * FROM roles WHERE name = 'content_manager';

-- Voir toutes les permissions du rôle content_manager
SELECT p.* FROM permissions p
JOIN role_has_permissions rhp ON p.id = rhp.permission_id
JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'content_manager';

-- Voir les rôles d'un utilisateur
SELECT r.* FROM roles r
JOIN model_has_roles mhr ON r.id = mhr.role_id
WHERE mhr.model_id = USER_ID AND mhr.model_type = 'App\\Models\\User';
```
