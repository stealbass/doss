# 🎯 Guide de Localisation des Liens - Bibliothèque Juridique

## 📍 Où Sont les Liens dans l'Interface ?

---

## 👤 ACCÈS UTILISATEUR

### Position dans le Menu
Le lien **"Legal Library"** se trouve dans le **menu principal gauche** (sidebar).

### Ordre d'Apparition
```
Dashboard
├─ Users
├─ Client  
├─ Advocate
├─ Cases
├─ To-Do
├─ Case Diary/Calendar
├─ Documents
├─ 📖 Legal Library  ← VOUS ÊTES ICI (ligne 155-162 du sidebar)
├─ Bills / Invoices
├─ Cause List
└─ ...
```

### Icône et Texte
- **Icône** : 📖 (ti ti-book - icône de livre)
- **Texte** : "Legal Library"
- **Couleur** : Suit le thème de l'application

### Code Exact (sidebar.blade.php lignes 155-162)
```blade
@can('view legal library')
    <li class="dash-item dash-hasmenu {{ in_array(Request::segment(1), ['library']) ? ' active' : '' }}">
        <a href="{{ route('user.legal-library.index') }}" class="dash-link">
            <span class="dash-micon"><i class="ti ti-book"></i></span>
            <span class="dash-mtext">{{ __('Legal Library') }}</span>
        </a>
    </li>
@endcan
```

### URL Résultante
```
https://votre-domaine.com/library
```

### Qui Peut Voir Ce Lien ?
✅ Tous les utilisateurs avec la permission **`view legal library`** :
- Clients
- Advocates (Avocats)
- Co-Advocates
- Team Leaders
- Autres rôles si la permission leur est assignée

❌ Les utilisateurs **sans** cette permission ne verront **pas** le lien.

---

## 🔧 ACCÈS ADMINISTRATEUR

### Position dans le Menu
Le lien **"Legal Library (Admin)"** se trouve dans le **menu Settings** (Paramètres).

### Navigation Complète
```
Settings (⚙️)
├─ Company Settings
├─ System Settings
├─ Email Settings
├─ Payment Settings
├─ Tax
├─ Case Type
├─ Document Type
├─ Document Sub-type
├─ 📚 Legal Library (Admin)  ← VOUS ÊTES ICI (ligne 374-377)
├─ Motions Types
├─ Pipeline
├─ Lead Stage
└─ ...
```

### Icône et Texte
- **Menu parent** : Settings (⚙️ ti ti-settings)
- **Texte du lien** : "Legal Library (Admin)"
- **Type** : Élément de sous-menu

### Code Exact (sidebar.blade.php lignes 374-377)
```blade
@can('manage legal library')
    <li class="dash-item ">
        <a class="dash-link"
            href="{{ route('legal-library.index') }}">{{ __('Legal Library (Admin)') }}</a>
    </li>
@endcan
```

### URL Résultante
```
https://votre-domaine.com/legal-library
```

### Qui Peut Voir Ce Lien ?
✅ Uniquement les utilisateurs avec la permission **`manage legal library`** :
- Company (rôle administrateur principal)
- Autres rôles si vous leur assignez manuellement cette permission

❌ Les utilisateurs **sans** cette permission ne verront **pas** le lien.

---

## 🔍 Vérification Visuelle

### Test Simple
1. **Ouvrez votre application Dossy Pro**
2. **Connectez-vous** avec votre compte
3. **Regardez le menu de gauche** (sidebar)

### Cas 1 : Vous Voyez "Legal Library"
✅ **Vous avez la permission `view legal library`**
- Vous pouvez consulter, rechercher et télécharger des documents
- Vous ne pouvez PAS créer de catégories ni uploader de fichiers

### Cas 2 : Vous Voyez "Settings → Legal Library (Admin)"
✅ **Vous avez la permission `manage legal library`**
- Vous avez tous les droits administrateur
- Vous pouvez créer des catégories, uploader, modifier, supprimer

### Cas 3 : Vous Ne Voyez Rien
❌ **Vous n'avez aucune permission Legal Library**

**Solution** : Demandez à l'administrateur système de :
1. Vérifier que les permissions existent dans la base de données
2. Assigner les permissions à votre rôle

---

## 🛠️ Dépannage : "Je Ne Vois Pas les Liens"

### Étape 1 : Vérifier les Permissions dans la Base de Données

**Requête SQL** :
```sql
-- Vérifier que les permissions existent
SELECT * FROM permissions WHERE name LIKE '%legal library%';
```

**Résultat attendu** :
```
| id | name                   | guard_name |
|----|------------------------|------------|
| XX | view legal library     | web        |
| XX | manage legal library   | web        |
```

### Étape 2 : Vérifier les Assignations de Rôles

**Requête SQL** :
```sql
-- Voir quels rôles ont les permissions
SELECT 
    r.name as role_name, 
    p.name as permission_name
FROM roles r
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON p.id = rhp.permission_id
WHERE p.name LIKE '%legal library%'
ORDER BY r.name;
```

**Résultat attendu** :
```
| role_name    | permission_name        |
|--------------|------------------------|
| company      | manage legal library   |
| company      | view legal library     |
| advocate     | view legal library     |
| client       | view legal library     |
| co advocate  | view legal library     |
| team leader  | view legal library     |
```

### Étape 3 : Vérifier Votre Rôle Personnel

**Requête SQL** :
```sql
-- Remplacez VOTRE_EMAIL par votre email de connexion
SELECT 
    u.name as user_name,
    u.email,
    r.name as role_name,
    p.name as permission_name
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON rhp.permission_id = p.id
WHERE u.email = 'VOTRE_EMAIL'
  AND p.name LIKE '%legal library%';
```

### Étape 4 : Assigner Manuellement si Nécessaire

**Si votre rôle n'a pas les permissions**, exécutez :

```sql
-- Pour ajouter "view legal library" au rôle "client" par exemple
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p, roles r
WHERE p.name = 'view legal library'
  AND r.name = 'client'
  AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions rhp2
    WHERE rhp2.permission_id = p.id AND rhp2.role_id = r.id
  );
```

### Étape 5 : Vider le Cache Laravel

```bash
cd /chemin/vers/votre/projet
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

### Étape 6 : Se Déconnecter et Reconnecter

1. Cliquez sur votre profil → Logout
2. Reconnectez-vous avec vos identifiants
3. Vérifiez à nouveau le menu

---

## 📊 Tableau Récapitulatif

| Élément | Utilisateur Normal | Administrateur |
|---------|-------------------|----------------|
| **Nom du lien** | Legal Library | Legal Library (Admin) |
| **Icône** | 📖 ti ti-book | (dans Settings ⚙️) |
| **Position** | Menu principal | Sous-menu Settings |
| **Ligne dans sidebar** | 155-162 | 374-377 |
| **Route** | /library | /legal-library |
| **Permission** | view legal library | manage legal library |
| **Actions** | Voir, rechercher, télécharger | Gérer catégories et documents |

---

## 🎬 Actions Après Avoir Trouvé les Liens

### Première Utilisation - Administrateur

1. **Cliquez sur Settings** (⚙️) dans le menu
2. **Cherchez "Legal Library (Admin)"** dans la liste déroulante
3. **Cliquez dessus**
4. **Créez votre première catégorie** :
   - Cliquez "Create Category"
   - Nom : "Code Civil"
   - Description : "Articles du Code Civil"
   - Cliquez "Create"
5. **Uploadez votre premier document** :
   - Dans la catégorie créée, cliquez "Add Document"
   - Titre : "Code Civil - Articles 1-100"
   - Description : "Premiers articles du Code Civil"
   - Fichier : Sélectionnez un PDF (max 20 Mo)
   - Cliquez "Upload"

### Première Utilisation - Utilisateur

1. **Cliquez sur "Legal Library"** 📖 dans le menu principal
2. **Parcourez les catégories** disponibles
3. **Cliquez sur une catégorie** pour voir ses documents
4. **Testez les fonctionnalités** :
   - 🔍 Recherche par mot-clé
   - 👁️ Visualisation PDF dans le navigateur
   - ⬇️ Téléchargement de document

---

## 📝 Notes Importantes

### Sécurité
- Les permissions sont vérifiées à **deux niveaux** :
  1. **Affichage du lien** : directive `@can` dans Blade
  2. **Accès aux routes** : middleware dans les contrôleurs
  
- Un utilisateur sans permission ne peut **ni voir** ni **accéder** aux fonctionnalités

### Performance
- Les liens sont générés **dynamiquement** selon les permissions
- Le menu est **mis en cache** pour optimiser les performances
- Après modification des permissions, **videz le cache**

### Personnalisation
- Les icônes utilisent **Tabler Icons** (https://tabler-icons.io/)
- Les textes utilisent la fonction `__()` pour la **traduction**
- Le style suit le **thème de l'application** automatiquement

---

**Date** : 15 novembre 2024  
**Auteur** : GenSpark AI Developer  
**Version** : 1.0 - Guide Complet de Localisation
