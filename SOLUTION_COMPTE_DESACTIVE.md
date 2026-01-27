# 🔧 Solution Rapide : Compte Désactivé

## ❌ Problème

Après avoir réinitialisé le mot de passe via l'email reçu, lors de la connexion, le message suivant apparaît :

```
"Votre compte est désactivé, veuillez contacter votre administrateur"
```

## 🎯 Cause

Le champ `is_active` de l'utilisateur est à `0` (désactivé) dans la table `users` de la base de données.

## ✅ Solutions

### Solution 1 : Via PhpMyAdmin / Base de données (RAPIDE)

1. **Connectez-vous à votre base de données** (PhpMyAdmin, MySQL Workbench, etc.)

2. **Exécutez cette requête SQL :**

```sql
UPDATE users
SET is_active = 1
WHERE email = 'contact@dossypro.com';
```

3. **Vérifiez l'activation :**

```sql
SELECT id, name, email, is_active
FROM users
WHERE email = 'contact@dossypro.com';
```

Le résultat devrait montrer `is_active = 1`

4. **Essayez de vous connecter** à nouveau depuis Flutter

---

### Solution 2 : Via le script SQL fourni

Utilisez le fichier `activate_contact_account.sql` qui contient toutes les requêtes nécessaires.

---

### Solution 3 : Via le script PHP

Si PHP est disponible en ligne de commande :

```bash
cd /chemin/vers/doss-genspark_ai_developer
php activate_user.php
```

Suivez les instructions et entrez l'email `contact@dossypro.com`

---

### Solution 4 : Via le panneau Admin Web

1. Connectez-vous au panneau d'administration : `https://dossypro.com/admin`
2. Allez dans **Users** ou **Mobile Users**
3. Recherchez l'utilisateur `contact@dossypro.com`
4. Cliquez sur **Edit** ou l'icône de modification
5. Cochez la case **Active** ou **Is Active**
6. Sauvegardez

---

## 🔍 Vérification Post-Activation

### 1. Vérifier dans la base de données

```sql
SELECT 
    u.id,
    u.name,
    u.email,
    u.is_active,
    mas.status as subscription_status,
    map.name as plan_name
FROM users u
LEFT JOIN mobile_app_subscriptions mas ON u.id = mas.user_id AND mas.status = 'active'
LEFT JOIN mobile_app_plans map ON mas.mobile_app_plan_id = map.id
WHERE u.email = 'contact@dossypro.com';
```

**Résultat attendu :**
- `is_active` = 1
- `subscription_status` = 'active'
- `plan_name` devrait afficher un nom de plan

### 2. Tester la connexion depuis Flutter

1. Ouvrez l'application Flutter
2. Entrez :
   - **Email :** contact@dossypro.com
   - **Mot de passe :** [Le nouveau mot de passe que vous avez défini]
3. Cliquez sur **Se connecter**
4. La connexion devrait réussir

---

## 🚨 Si le problème persiste

### Vérifier les logs Laravel

```bash
tail -f storage/logs/laravel.log
```

### Vérifier l'état du compte en détail

```sql
SELECT 
    id,
    name,
    email,
    type,
    is_active,
    email_verified_at,
    created_at,
    updated_at
FROM users
WHERE email = 'contact@dossypro.com';
```

### Vérifier la subscription

```sql
SELECT * FROM mobile_app_subscriptions
WHERE user_id = (SELECT id FROM users WHERE email = 'contact@dossypro.com' LIMIT 1);
```

Si aucune subscription n'existe, créez-en une :

```sql
-- D'abord, récupérer l'ID du plan gratuit
SELECT id, name, price_monthly FROM mobile_app_plans WHERE price_monthly = 0;

-- Ensuite, créer la subscription (remplacez USER_ID et PLAN_ID)
INSERT INTO mobile_app_subscriptions (
    user_id, 
    mobile_app_plan_id, 
    status, 
    started_at, 
    expires_at,
    auto_renew,
    searches_used,
    ai_analyses_used,
    pdf_downloads_used,
    created_at,
    updated_at
) VALUES (
    (SELECT id FROM users WHERE email = 'contact@dossypro.com' LIMIT 1),
    1, -- ID du plan gratuit (à vérifier)
    'active',
    NOW(),
    NULL,
    0,
    0,
    0,
    0,
    NOW(),
    NOW()
);
```

---

## 📋 Checklist de Résolution

- [ ] Compte activé (`is_active = 1`)
- [ ] Mot de passe réinitialisé avec succès
- [ ] Subscription active existe
- [ ] Connexion réussie depuis Flutter
- [ ] Aucune erreur dans les logs Laravel
- [ ] L'utilisateur peut accéder à l'application

---

## 💡 Prévention Future

### Pour activer automatiquement les nouveaux utilisateurs

Dans `AuthController.php`, lors de l'inscription (fonction `register`), assurez-vous que :

```php
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'type' => 'client',
    'lang' => 'fr',
    'is_active' => 1, // ✅ IMPORTANT : Activé par défaut
    'referral_code' => $this->generateUniqueReferralCode(),
]);
```

### Pour les comptes existants

Exécutez cette requête pour activer tous les comptes clients mobiles :

```sql
UPDATE users
SET is_active = 1
WHERE type = 'client'
  AND email LIKE '%@%'
  AND is_active = 0;
```

⚠️ **Attention :** Cette requête activera TOUS les comptes clients désactivés. Utilisez-la avec précaution.

---

## 📞 Support

Si vous avez besoin d'aide supplémentaire :

1. Vérifiez les fichiers de documentation :
   - `CORRECTION_EMAIL_MOT_DE_PASSE_OUBLIE.md`
   - `GUIDE_TEST_EMAIL_MOT_DE_PASSE_OUBLIE.md`

2. Consultez les logs :
   - `storage/logs/laravel.log`

3. Utilisez les scripts fournis :
   - `activate_user.php`
   - `activate_contact_account.sql`
   - `test_smtp_config.php`

---

**Date :** 6 janvier 2026
**Problème :** Compte désactivé après réinitialisation du mot de passe
**Solution :** Activer le compte avec `is_active = 1`
**Statut :** ✅ Résolu
