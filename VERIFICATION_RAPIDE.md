# ✅ Vérification Rapide - Bibliothèque Juridique

## 🎯 Guide Express pour Vérifier l'Installation

---

## ÉTAPE 1 : Vérifier les Permissions dans la Base de Données

**Ouvrez phpMyAdmin** et exécutez cette requête SQL :

```sql
SELECT * FROM permissions WHERE name LIKE '%legal library%';
```

**Résultat attendu** : Vous devez voir ces 2 permissions :

| id | name                 | guard_name | created_at | updated_at |
|----|----------------------|------------|------------|------------|
| XX | view legal library   | web        | ...        | ...        |
| XX | manage legal library | web        | ...        | ...        |

✅ **Si vous voyez les 2 permissions** → Passez à l'étape 2  
❌ **Si les permissions n'existent pas** → Consultez `FINAL_DEPLOYMENT_INSTRUCTIONS.md`

---

## ÉTAPE 2 : Vérifier les Assignations aux Rôles

**Dans phpMyAdmin**, exécutez :

```sql
SELECT 
    r.name as role_name, 
    p.name as permission_name
FROM roles r
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON p.id = rhp.permission_id
WHERE p.name LIKE '%legal library%'
ORDER BY r.name;
```

**Résultat attendu** : Au minimum ces assignations :

| role_name    | permission_name      |
|--------------|----------------------|
| advocate     | view legal library   |
| client       | view legal library   |
| co advocate  | view legal library   |
| company      | manage legal library |
| company      | view legal library   |
| team leader  | view legal library   |

✅ **Si vous voyez ces assignations** → Passez à l'étape 3  
❌ **Si manquant** → Exécutez le script SQL dans `FINAL_DEPLOYMENT_INSTRUCTIONS.md`

---

## ÉTAPE 3 : Vérifier le Dossier de Stockage

**Sur votre serveur**, vérifiez que le dossier existe :

```bash
ls -la storage/app/public/ | grep legal_documents
```

**Résultat attendu** :
```
drwxr-xr-x  2 www-data www-data  4096 Nov 15 10:00 legal_documents
```

**Créer le dossier si nécessaire** :
```bash
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents
chown -R www-data:www-data storage/app/public/legal_documents
```

✅ **Si le dossier existe et a les bonnes permissions** → Passez à l'étape 4

---

## ÉTAPE 4 : Vérifier le Lien Symbolique

**Sur votre serveur** :

```bash
ls -la public/ | grep storage
```

**Résultat attendu** :
```
lrwxrwxrwx  1 www-data www-data   28 Nov 15 10:00 storage -> ../storage/app/public
```

**Créer le lien si nécessaire** :
```bash
php artisan storage:link
```

✅ **Si le lien existe** → Passez à l'étape 5

---

## ÉTAPE 5 : Vider le Cache Laravel

**Sur votre serveur**, exécutez :

```bash
cd /home/stealbasa/www
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

**Résultat attendu** :
```
Application cache cleared successfully.
Compiled views cleared successfully.
Configuration cache cleared successfully.
Route cache cleared successfully.
```

✅ **Cache vidé** → Passez à l'étape 6

---

## ÉTAPE 6 : Se Connecter et Vérifier les Liens

### A) Test Utilisateur Normal

1. **Connectez-vous** avec un compte utilisateur (client, advocate, etc.)
2. **Regardez le menu de gauche** (sidebar)
3. **Cherchez le lien** : `Legal Library` avec icône 📖

**Position dans le menu** :
```
Dashboard
Client
Documents
Legal Library  ← ICI
Bills / Invoices
```

✅ **Vous voyez "Legal Library"** → Cliquez dessus et testez  
❌ **Vous ne voyez rien** → Allez à la section Dépannage ci-dessous

### B) Test Administrateur

1. **Connectez-vous** avec un compte admin (company)
2. **Cliquez sur le menu Settings** ⚙️
3. **Cherchez** : `Legal Library (Admin)` dans la liste déroulante

**Position dans Settings** :
```
Settings
├─ Document Type
├─ Document Sub-type
├─ Legal Library (Admin)  ← ICI
├─ Motions Types
```

✅ **Vous voyez "Legal Library (Admin)"** → Cliquez dessus et testez  
❌ **Vous ne voyez rien** → Allez à la section Dépannage ci-dessous

---

## ÉTAPE 7 : Tester les Fonctionnalités de Base

### Test Admin : Créer une Catégorie

1. Allez dans **Settings → Legal Library (Admin)**
2. Cliquez sur **"Create Category"**
3. Remplissez :
   - **Name** : "Test Category"
   - **Description** : "Catégorie de test"
4. Cliquez **"Create"**

✅ **La catégorie apparaît dans la liste** → Continuez  
❌ **Erreur** → Notez le message d'erreur et consultez le dépannage

### Test Admin : Uploader un Document

1. Dans la catégorie créée, cliquez **"Add Document"**
2. Remplissez :
   - **Title** : "Document de test"
   - **Description** : "PDF de test"
   - **File** : Sélectionnez un fichier PDF (max 20 Mo)
3. Cliquez **"Upload"**

✅ **Le document apparaît** → Continuez  
❌ **Erreur** → Vérifiez les permissions du dossier storage

### Test Utilisateur : Consulter

1. Allez dans **Legal Library** (menu principal)
2. Vérifiez que vous voyez la catégorie "Test Category"
3. Cliquez dessus
4. Vérifiez que vous voyez le document
5. Cliquez sur **"View"** pour prévisualiser
6. Testez le bouton **"Download"**

✅ **Tout fonctionne** → Installation réussie ! 🎉  
❌ **Problème** → Consultez le dépannage ci-dessous

---

## 🆘 DÉPANNAGE RAPIDE

### Problème 1 : Les Liens N'Apparaissent Pas

**Cause** : Cache Laravel ou session utilisateur

**Solution** :
```bash
# Sur le serveur
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

Puis déconnectez-vous et reconnectez-vous.

### Problème 2 : Erreur "Permission Denied"

**Cause** : Votre utilisateur n'a pas les permissions

**Solution SQL** :
```sql
-- Vérifier votre rôle
SELECT u.email, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'votre.email@domaine.com';

-- Si votre rôle n'a pas la permission, ajoutez-la
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p, roles r
WHERE p.name = 'view legal library'
  AND r.name = 'VOTRE_ROLE'
  AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions rhp2
    WHERE rhp2.permission_id = p.id AND rhp2.role_id = r.id
  );
```

### Problème 3 : Erreur lors de l'Upload

**Cause** : Permissions du dossier ou taille de fichier

**Solution** :
```bash
# Vérifier et corriger les permissions
chmod -R 775 storage/app/public/legal_documents/
chown -R www-data:www-data storage/app/public/legal_documents/

# Vérifier la configuration PHP
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

Si taille trop petite, modifiez dans `php.ini` :
```ini
upload_max_filesize = 20M
post_max_size = 25M
```

### Problème 4 : PDF Ne S'Affiche Pas

**Cause** : Lien symbolique manquant

**Solution** :
```bash
php artisan storage:link
```

### Problème 5 : Erreur 404 sur les Routes

**Cause** : Cache de routes

**Solution** :
```bash
php artisan route:clear
php artisan route:cache
```

---

## 📋 CHECKLIST COMPLÈTE

Cochez chaque étape au fur et à mesure :

- [ ] Permissions existent dans la base de données
- [ ] Permissions assignées aux rôles
- [ ] Dossier `storage/app/public/legal_documents/` existe
- [ ] Permissions du dossier correctes (775)
- [ ] Lien symbolique `public/storage` existe
- [ ] Cache Laravel vidé
- [ ] Déconnexion/reconnexion effectuée
- [ ] Lien "Legal Library" visible (utilisateur)
- [ ] Lien "Legal Library (Admin)" visible (admin)
- [ ] Catégorie de test créée
- [ ] Document de test uploadé
- [ ] Document visible côté utilisateur
- [ ] Visualisation PDF fonctionne
- [ ] Téléchargement fonctionne
- [ ] Recherche fonctionne

**Si toutes les cases sont cochées** : ✅ Installation 100% fonctionnelle !

---

## 🎯 TEMPS ESTIMÉ

- ⏱️ **Vérification complète** : 10-15 minutes
- ⏱️ **Dépannage si nécessaire** : 5-10 minutes supplémentaires

---

## 📞 RESSOURCES SUPPLÉMENTAIRES

Si vous rencontrez des difficultés :

1. **LOCALISATION_LIENS_MENU.md** - Guide détaillé de localisation
2. **ACCES_BIBLIOTHEQUE_JURIDIQUE.md** - Guide complet d'accès
3. **FINAL_DEPLOYMENT_INSTRUCTIONS.md** - Instructions de déploiement
4. **LEGAL_LIBRARY_FEATURE.md** - Documentation technique

---

**Date** : 15 novembre 2024  
**Version** : 1.0 - Guide de Vérification Rapide  
**Pull Request** : https://github.com/stealbass/doss/pull/2
