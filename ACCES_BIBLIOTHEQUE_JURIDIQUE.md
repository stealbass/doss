# 📚 Accès à la Bibliothèque Juridique - Guide Complet

## ✅ État de l'Installation

Toutes les modifications ont été **poussées sur GitHub** et le **Pull Request #2** a été mis à jour.

🔗 **Lien du Pull Request** : https://github.com/stealbass/doss/pull/2

---

## 🎯 Où Trouver les Liens de Navigation

### 📖 Pour les Utilisateurs Normaux

**Emplacement** : Menu principal (sidebar gauche)

**Nom du menu** : `Legal Library`

**Icône** : 📖 (livre)

**Route** : `/library`

**Permission requise** : `view legal library`

**Utilisateurs autorisés** :
- Clients
- Avocats (Advocates)
- Co-Advocates
- Team Leaders
- Tous les rôles avec permission "view legal library"

**Actions disponibles** :
- ✅ Parcourir les catégories
- ✅ Rechercher des documents par titre/description
- ✅ Visualiser les PDFs dans le navigateur
- ✅ Télécharger les documents

---

### 🔧 Pour les Administrateurs

**Emplacement** : Menu Settings → Sous-menu

**Nom du menu** : `Legal Library (Admin)`

**Route** : `/legal-library`

**Permission requise** : `manage legal library`

**Utilisateurs autorisés** :
- Company (rôle admin principal)
- Tous les rôles avec permission "manage legal library"

**Actions disponibles** :
- ✅ Créer/modifier/supprimer des catégories
- ✅ Uploader des documents PDF (max 20 Mo)
- ✅ Modifier les métadonnées des documents
- ✅ Supprimer des documents
- ✅ Voir les statistiques de téléchargement

---

## 🔍 Emplacement Exact dans le Code

### Lien Utilisateur
**Fichier** : `resources/views/partision/sidebar.blade.php`

**Lignes** : 155-162

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

### Lien Administrateur
**Fichier** : `resources/views/partision/sidebar.blade.php`

**Lignes** : 374-377 (dans le menu Settings)

```blade
@can('manage legal library')
    <li class="dash-item ">
        <a class="dash-link"
            href="{{ route('legal-library.index') }}">{{ __('Legal Library (Admin)') }}</a>
    </li>
@endcan
```

---

## 🚀 Utilisation Immédiate

### Étape 1 : Vérifier les Permissions
Assurez-vous que les permissions ont été créées dans votre base de données :

```sql
SELECT * FROM permissions WHERE name LIKE '%legal library%';
```

Vous devriez voir :
- `view legal library`
- `manage legal library`

### Étape 2 : Assigner les Permissions aux Rôles

**Pour voir si les rôles ont les permissions** :
```sql
SELECT r.name as role_name, p.name as permission_name
FROM roles r
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON p.id = rhp.permission_id
WHERE p.name LIKE '%legal library%';
```

### Étape 3 : Vérifier le Stockage

**Dossier de stockage** : `storage/app/public/legal_documents/`

**Lien symbolique** : Doit pointer de `public/storage` vers `storage/app/public`

Vérifiez avec :
```bash
ls -la public/ | grep storage
```

### Étape 4 : Accéder à l'Interface

1. **Connectez-vous** à votre application Dossy Pro
2. **Utilisateurs** : Cherchez "Legal Library" dans le menu principal
3. **Administrateurs** : Allez dans Settings → "Legal Library (Admin)"

---

## 🎨 Captures d'Écran des Menus

### Menu Utilisateur
```
┌─────────────────────────┐
│ 📊 Dashboard            │
│ 👥 Client               │
│ 📄 Documents            │
│ 📖 Legal Library  ← ICI │
│ 💰 Bills / Invoices     │
└─────────────────────────┘
```

### Menu Administrateur (Settings)
```
┌─────────────────────────────┐
│ ⚙️  Settings                 │
│   ├─ Document Type          │
│   ├─ Document Sub-type      │
│   ├─ Legal Library (Admin)  │ ← ICI
│   ├─ Motions Types          │
│   └─ ...                    │
└─────────────────────────────┘
```

---

## 🔄 Prochaines Étapes

### ✅ Déjà Fait
- [x] Code développé
- [x] Fichiers de migration créés
- [x] Contrôleurs créés
- [x] Vues créées
- [x] Routes configurées
- [x] Liens de navigation ajoutés
- [x] Base de données créée manuellement
- [x] Permissions configurées
- [x] Dossier de stockage créé
- [x] Code poussé sur GitHub
- [x] Pull Request mis à jour

### 🎯 À Faire
1. **Tester la fonctionnalité** :
   - Se connecter en tant qu'utilisateur normal
   - Vérifier que le lien "Legal Library" apparaît
   - Se connecter en tant qu'administrateur
   - Vérifier que "Legal Library (Admin)" apparaît dans Settings
   
2. **Créer la première catégorie** :
   - Aller dans Settings → Legal Library (Admin)
   - Cliquer sur "Create Category"
   - Exemple : Catégorie "Lois Civiles"
   
3. **Uploader le premier document** :
   - Dans la catégorie créée, cliquer sur "Add Document"
   - Uploader un fichier PDF (max 20 Mo)
   - Ajouter titre et description
   
4. **Tester côté utilisateur** :
   - Se connecter en tant qu'utilisateur
   - Aller dans Legal Library
   - Vérifier que la catégorie et le document apparaissent
   - Tester la visualisation et le téléchargement

---

## 🆘 Support et Dépannage

### Problème : Les liens ne s'affichent pas

**Solution 1** : Vider le cache Laravel
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

**Solution 2** : Vérifier les permissions utilisateur
```sql
-- Voir les permissions de votre utilisateur
SELECT u.email, r.name as role, p.name as permission
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON rhp.permission_id = p.id
WHERE u.id = VOTRE_USER_ID;
```

### Problème : Erreur lors de l'upload

**Solution** : Vérifier les permissions du dossier
```bash
chmod -R 775 storage/app/public/legal_documents/
chown -R www-data:www-data storage/app/public/legal_documents/
```

### Problème : Les PDFs ne s'affichent pas

**Solution** : Vérifier le lien symbolique
```bash
php artisan storage:link
```

---

## 📞 Contact

Pour toute question ou problème, référez-vous à :
- **Documentation technique** : `LEGAL_LIBRARY_FEATURE.md`
- **Guide de déploiement** : `FINAL_DEPLOYMENT_INSTRUCTIONS.md`
- **Pull Request** : https://github.com/stealbass/doss/pull/2

---

**Date de mise à jour** : 15 novembre 2024
**Version** : 1.0 - Fonctionnalité complète avec navigation
