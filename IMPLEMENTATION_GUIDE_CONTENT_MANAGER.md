# ✅ SOLUTION FINALE: Créer un employé "Content Manager"

## 🎯 Objectif
Créer un nouvel employé (superadmin) qui gère:
- ✅ Legal Library (Bibliothèque juridique)
- ✅ Document Templates (Modèles de documents Mobile)
- ✅ Fiscal Resources (Ressources fiscales/sociales)

---

## 📌 ÉTAPE 1: Exécuter le Seeder (une fois)

```bash
php artisan db:seed --class=CreateContentManagerRoleSeeder
```

✅ Cela crée:
- Rôle: `content_manager`
- 8 permissions associées pour les 3 modules
- Lien automatique entre rôle et permissions

**Résultat:**
```
Content Manager role created with all permissions!
```

---

## 📌 ÉTAPE 2: Mettre à jour les Contrôleurs

Les contrôleurs suivants acceptent maintenant aussi `content_manager`:

### ✅ LegalLibraryController - DÉJÀ MODIFIÉ
- Méthode helper `canManageLegalLibrary()` ajoutée
- Vérification dans `index()` mise à jour
- **À faire:** Remplacer les autres `Auth::user()->type == 'super admin'` par `$this->canManageLegalLibrary()` dans les méthodes: `create()`, `show()`, `store()`, `edit()`, `update()`, `destroy()`

### ⏳ DocumentTemplateController - À MODIFIER
Fichier: `app/Http/Controllers/DocumentTemplateController.php`

```php
// Ajouter en haut de la classe:
private function canManageDocumentTemplate()
{
    $user = Auth::user();
    return $user->type == 'super admin' || $user->hasRole('content_manager');
}

// Remplacer dans chaque méthode:
// DE:  if (Auth::user()->type !== 'super admin')
// À:   if (!$this->canManageDocumentTemplate())
```

Méthodes à modifier:
- `index()`
- `create()`
- `show()`
- `store()`
- `edit()`
- `update()`
- `destroy()`
- `duplicate()`
- `exportTemplate()`
- `getTemplateCategories()`

### ⏳ FiscalSocialResourceController - À MODIFIER
Fichier: `app/Http/Controllers/FiscalSocialResourceController.php`

```php
// Ajouter en haut de la classe:
private function canManageFiscalResources()
{
    $user = auth()->user();
    return $user->type == 'super admin' || $user->hasRole('content_manager');
}

// Remplacer dans chaque méthode:
// DE:  if (auth()->user()->type !== 'super admin')
// À:   if (!$this->canManageFiscalResources())
```

Méthodes à modifier:
- `index()` (optionnel - accessible à tous)
- `create()`
- `store()`
- `show()`
- `edit()`
- `update()`
- `destroy()`

---

## 📌 ÉTAPE 3: Assigner le rôle à l'employé (Via Admin)

### Via l'interface (Recommandé):

1. **Allez à:** Dashboard → Employees → **Create Employee**
2. **Remplissez:**
   - Name: `John Doe`
   - Email: `john@example.com`
   - Password: `SecurePassword123` (min 8 caractères)
3. **Dans "Assign Permission":**
   - Sélectionnez le rôle: **`content_manager`**
   - OU sélectionnez les permissions individuelles:
     ```
     ✅ manage legal-library
     ✅ create legal-library
     ✅ edit legal-library
     ✅ delete legal-library
     
     ✅ manage document-template
     ✅ create document-template
     ✅ edit document-template
     ✅ delete document-template
     
     ✅ manage fiscal-resources
     ✅ create fiscal-resources
     ✅ edit fiscal-resources
     ✅ delete fiscal-resources
     ```
4. **Cliquez:** Create

### Via SQL (Alternative):

```sql
-- 1. Ajouter l'utilisateur
INSERT INTO users (name, email, password, type, lang, created_at, updated_at) 
VALUES ('John Doe', 'john@example.com', BCRYPT('SecurePassword123'), 'super admin', 'fr', NOW(), NOW());

-- 2. Récupérer l'ID du dernier utilisateur créé
SET @user_id = LAST_INSERT_ID();

-- 3. Assigner le rôle 'content_manager'
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', @user_id
FROM roles r
WHERE r.name = 'content_manager' AND r.guard_name = 'web';

-- Vérifier:
SELECT u.id, u.name, u.email, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON r.id = mhr.role_id
WHERE u.email = 'john@example.com';
```

---

## 🔐 Que peut faire le Content Manager?

### ✅ Accès complet:
- **Legal Library** → Créer, éditer, supprimer catégories et documents juridiques
- **Document Templates** → Créer, éditer, supprimer modèles pour l'app mobile
- **Fiscal Resources** → Créer, éditer, supprimer ressources fiscales/sociales

### ❌ Accès REFUSÉ:
- Employees Management
- Roles & Permissions
- System Settings
- Plans & Subscriptions
- CRM
- Autres modules

---

## 🧪 Tester la Configuration

**Connexion:**
```
Email: john@example.com
Password: SecurePassword123
```

**Tests à faire:**
1. ✅ Se connecter avec le compte Content Manager
2. ✅ Allez à Legal Library → Créer une catégorie ✓
3. ✅ Allez à Mobile App → Document Templates → Créer un template ✓
4. ✅ Allez à Fiscal Resources → Créer une ressource ✓
5. ✅ Essayez d'accéder à Employees → Doit afficher "Permission Denied" ✓

---

## 📊 Vérification dans la Base de Données

```sql
-- Voir le rôle content_manager
SELECT * FROM roles WHERE name = 'content_manager';

-- Voir les permissions du rôle
SELECT p.name, p.guard_name 
FROM permissions p
JOIN role_has_permissions rhp ON p.id = rhp.permission_id
JOIN roles r ON r.id = rhp.role_id
WHERE r.name = 'content_manager'
ORDER BY p.name;

-- Voir les utilisateurs avec ce rôle
SELECT u.id, u.name, u.email, r.name as role_name
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON r.id = mhr.role_id
WHERE r.name = 'content_manager';
```

---

## 📋 Checklist d'Implémentation

### Préparation:
- [ ] ✅ Seeder créé: `database/seeders/CreateContentManagerRoleSeeder.php`
- [ ] ✅ Document solution: `SOLUTION_CONTENT_MANAGER_ROLE.md`

### Déploiement:
- [ ] Exécuter: `php artisan db:seed --class=CreateContentManagerRoleSeeder`
- [ ] Mettre à jour LegalLibraryController (complètement)
- [ ] Mettre à jour DocumentTemplateController (7 méthodes)
- [ ] Mettre à jour FiscalSocialResourceController (7 méthodes)

### Validation:
- [ ] Créer un employé avec le rôle `content_manager`
- [ ] Tester l'accès aux 3 modules
- [ ] Tester les restrictions (modules interdits)
- [ ] Vérifier les logs si erreurs

---

## 🚀 Support Supplémentaire

Si vous avez besoin de modifier rapidement tous les contrôleurs, utilisez find & replace:

**Find:** `if (Auth::user()->type !== 'super admin')`
**Replace:** `if (!$this->canManageYourModule())`

Puis ajouter la méthode helper dans chaque classe.

---

**Questions?** Consultez `SOLUTION_CONTENT_MANAGER_ROLE.md` pour plus de détails téchniques.
