# 📋 PLAN CORRECT - Ajouter rôle Content Manager pour SuperAdmin

## 🏗️ Architecture Comprise

### Structure SAAS Multi-Interface:
```
┌─ SUPERADMIN INTERFACE (superadmin type)
│  └─ Gère les employés (type: superAdminEmployee)
│     └─ Permissions stockées en JSON dans `users.permission_json`
│     └─ Actuellement: "manage crm", "manage support ticket"
│
└─ COMPANY INTERFACE (company type)
   └─ Gère les membres (type: user / advocate / client)
      └─ Permissions basées sur Spatie\Permission (table roles/permissions)
```

## ✅ SOLUTION CORRECTE

**Objectif :** Ajouter 3 nouvelles permissions pour les employés SuperAdmin :
- `manage legal-library`
- `manage document-template`  
- `manage fiscal-resources`

### ÉTAPE 1 : Modifier EmployeeController.php

**Fichier:** `app/Http/Controllers/EmployeeController.php`

Dans la méthode `permission_arr()`, **REMPLACER** :

```php
public function permission_arr()
{
    $arr = [
        1 => 'manage crm',
        2 => 'manage support ticket'
    ];
    return $arr;
}
```

**PAR :**

```php
public function permission_arr()
{
    $arr = [
        1 => 'manage crm',
        2 => 'manage support ticket',
        3 => 'manage legal-library',
        4 => 'manage document-template',
        5 => 'manage fiscal-resources'
    ];
    return $arr;
}
```

### ÉTAPE 2 : Mettre à jour les contrôleurs pour vérifier les permissions SuperAdmin

**Fichiers à modifier :**
- `app/Http/Controllers/LegalLibraryController.php`
- `app/Http/Controllers/DocumentTemplateController.php`
- `app/Http/Controllers/FiscalSocialResourceController.php`

**Pour chaque fichier, modifier les vérifications de permission :**

**DE (ancien code) :**
```php
if (Auth::user()->type == 'super admin') {
    // accès
}
```

**À (nouveau code) :**
```php
if ($this->canManageModule()) {
    // accès
}
```

Puis ajouter la méthode helper :
```php
private function canManageModule()
{
    $user = Auth::user();
    
    // SuperAdmin a accès total
    if ($user->type === 'super admin') {
        return true;
    }
    
    // SuperAdmin Employee avec permission spécifique
    if ($user->type === 'superAdminEmployee') {
        $permissions = json_decode($user->permission_json, true) ?? [];
        return in_array('manage legal-library', $permissions); // adapter pour chaque module
    }
    
    return false;
}
```

### ÉTAPE 3 : Mettre à jour l'interface créer/éditer employé

**Fichier:** `resources/views/employee/create.blade.php` (et `edit.blade.php`)

Ajouter les nouveaux checkboxes :

```html
<div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" id="permission_3" name="permissions[]" value="3">
    <label class="form-check-label" for="permission_3">
        Legal Library
    </label>
</div>

<div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" id="permission_4" name="permissions[]" value="4">
    <label class="form-check-label" for="permission_4">
        Document Template
    </label>
</div>

<div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" id="permission_5" name="permissions[]" value="5">
    <label class="form-check-label" for="permission_5">
        Fiscal Resources
    </label>
</div>
```

---

## 🔑 Points Importants

1. **Les SuperAdmin Employees** n'utilisent PAS les rôles Spatie (ces rôles sont pour les Company)
2. **Les permissions sont stockées** dans `users.permission_json` en tant que simple tableau
3. **Chaque contrôleur** doit vérifier les permissions d'une manière compatible avec cette structure

---

## 📝 Checklist

- [ ] Modifier `permission_arr()` dans EmployeeController.php
- [ ] Ajouter méthodes helpers dans LegalLibraryController.php
- [ ] Ajouter méthodes helpers dans DocumentTemplateController.php
- [ ] Ajouter méthodes helpers dans FiscalSocialResourceController.php
- [ ] Mettre à jour les vues `employee/create.blade.php` et `employee/edit.blade.php`
- [ ] Tester la création d'un employé avec les nouvelles permissions
- [ ] Vérifier l'accès aux 3 modules avec ce nouvel employé

