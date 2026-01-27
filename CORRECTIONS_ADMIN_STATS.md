# ✅ Corrections des statistiques Admin Mobile App

## Problème identifié
Après un achat Flutterwave réussi, les statistiques dans la partie admin Mobile App ne s'actualisaient pas correctement partout.

## Causes identifiées

### 1. **MobileUsersController** - Comptage incorrect des utilisateurs
- ❌ `User::where('type', 'client')->count()` comptait TOUS les clients
- ❌ `User::where('type', 'client')->whereYear()` pour nouveaux utilisateurs
- ✅ Corrigé: `User::whereHas('mobileSubscriptions')->count()`

### 2. **MobileDashboardController** - Type d'utilisateur incorrect
- ❌ `User::where('type', 'mobile_user')` cherchait un type qui n'existe pas
- ❌ `User::where('is_active', 1)` utilisait un champ incorrect
- ✅ Corrigé: `User::whereHas('mobileSubscriptions')` et `whereHas('activeMobileSubscription')`

### 3. **MobileAppPlansController** - Logique de subscriptions incorrecte
- ❌ `where('expires_at', '>', now())` ignorait les subscriptions sans date d'expiration
- ❌ JOIN incorrect: `plan_id` au lieu de `mobile_app_plan_id`
- ❌ Revenue calculé depuis subscriptions au lieu de payments
- ✅ Corrigé: `where(function($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })`
- ✅ Revenue pris depuis `MobileAppPayment::where('status', 'successful')->sum('amount')`

## Fichiers modifiés

### 1. MobileUsersController.php
**Ligne 78-88** - Méthode `index()`
```php
// AVANT
'total_users' => User::where('type', 'client')->count(),
'new_users_this_month' => User::where('type', 'client')->whereYear(...)->count(),

// APRÈS
'total_users' => User::whereHas('mobileSubscriptions')->count(),
'new_users_this_month' => User::whereHas('mobileSubscriptions')->whereYear(...)->count(),
```

### 2. MobileDashboardController.php
**Ligne 26-36** - Méthode `index()`
```php
// AVANT
'total_users' => User::where('type', 'mobile_user')->count(),
'active_users' => User::where('type', 'mobile_user')->where('is_active', 1)->count(),

// APRÈS
'total_users' => User::whereHas('mobileSubscriptions')->count(),
'active_users' => User::whereHas('activeMobileSubscription', function($q) {
    $q->where('status', 'active');
})->count(),
```

### 3. MobileAppPlansController.php

**Ligne 23-46** - Méthode `index()`
```php
// AVANT
'total_subscriptions' => MobileAppSubscription::where('status', 'active')
    ->where('expires_at', '>', now())->count(),
'monthly_revenue' => MobileAppSubscription::where('status', 'active')
    ->join('mobile_app_plans', 'mobile_app_subscriptions.plan_id', '=', 'mobile_app_plans.id')
    ->sum('mobile_app_plans.price_monthly'),

// APRÈS
'total_subscriptions' => MobileAppSubscription::where('status', 'active')
    ->where(function($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })->count(),
'monthly_revenue' => MobileAppPayment::where('status', 'successful')
    ->whereYear('paid_at', now()->year)
    ->whereMonth('paid_at', now()->month)
    ->sum('amount'),
```

**Ligne 248-258** - Méthode `statistics($id)`
```php
// AVANT
$activeSubscriptions = (clone $subscriptions)
    ->where('status', 'active')
    ->where('expires_at', '>', now())
    ->count();

// APRÈS
$activeSubscriptions = (clone $subscriptions)
    ->where('status', 'active')
    ->where(function($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })
    ->count();
```

## Impact des corrections

### Avant les corrections
- ❌ Total users: comptait tous les clients (SaaS + Mobile)
- ❌ Active subscriptions: ne comptait pas les subs sans expiration
- ❌ Monthly revenue: calculait basé sur prix du plan × nb subscriptions (incorrect)
- ❌ Plan statistics: counts incorrects

### Après les corrections
- ✅ Total users: compte uniquement les utilisateurs avec subscriptions mobile
- ✅ Active subscriptions: compte toutes les subs actives (avec ou sans expiration)
- ✅ Monthly revenue: somme réelle des paiements successful ce mois
- ✅ Plan statistics: counts corrects pour chaque plan

## Test des corrections

Exécutez le script de test:
```bash
php test_admin_stats.php
```

Le script vérifie:
1. ✅ Comptage des utilisateurs mobile
2. ✅ Comptage des subscriptions actives
3. ✅ Calcul du revenue (total + mensuel)
4. ✅ Que l'utilisateur ID 202 (qui vient de payer) apparaît dans tous les compteurs
5. ✅ Distribution des subscriptions par plan

## Pages admin concernées

Les corrections affectent ces pages admin:

1. **Mobile Users** (`/admin/mobile-users`)
   - Statistics cards en haut
   - Liste des utilisateurs
   - Filtre par plan

2. **Mobile Dashboard** (`/admin/mobile-dashboard`)
   - Total users
   - Active users
   - Revenue this month
   - User registration trends

3. **Mobile Plans** (`/admin/mobile-app-plans`)
   - Total subscriptions
   - Monthly revenue
   - Active subscriptions count per plan
   - Plan statistics (via AJAX)

4. **Mobile Analytics** (déjà correct)
   - Utilisait déjà `whereHas('mobileSubscriptions')`

## Vérification après déploiement

1. **Rechargez les pages admin** concernées
2. **Vérifiez que les statistiques se mettent à jour** après un nouveau paiement
3. **Comparez avec la base de données**:
   ```sql
   -- Total utilisateurs mobile
   SELECT COUNT(DISTINCT user_id) FROM mobile_app_subscriptions;
   
   -- Subscriptions actives
   SELECT COUNT(*) FROM mobile_app_subscriptions 
   WHERE status = 'active' 
   AND (expires_at IS NULL OR expires_at > NOW());
   
   -- Revenue ce mois
   SELECT SUM(amount) FROM mobile_app_payments 
   WHERE status = 'successful' 
   AND YEAR(paid_at) = YEAR(NOW()) 
   AND MONTH(paid_at) = MONTH(NOW());
   ```

## Notes importantes

### Subscriptions sans date d'expiration
Certains plans peuvent avoir `expires_at = NULL` (plans lifetime, gratuits, etc.). 
Les requêtes corrigées gèrent ce cas avec:
```php
->where(function($q) {
    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
})
```

### Revenue calculation
Le revenue est maintenant calculé depuis `mobile_app_payments` avec `status='successful'` au lieu d'estimer depuis les subscriptions actives. C'est plus précis car:
- Prend en compte les paiements réels
- Inclut les remboursements partiels
- Reflète le billing_cycle réel (monthly/yearly)

### Cache
Si vous utilisez un système de cache, videz-le après déploiement:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## ✅ Checklist déploiement

- [ ] Uploader `MobileUsersController.php` modifié
- [ ] Uploader `MobileDashboardController.php` modifié
- [ ] Uploader `MobileAppPlansController.php` modifié
- [ ] Exécuter `php test_admin_stats.php` sur le serveur
- [ ] Vider le cache Laravel
- [ ] Tester chaque page admin Mobile App
- [ ] Faire un nouveau paiement test et vérifier que les stats s'actualisent
- [ ] Vérifier que l'utilisateur ID 202 apparaît dans les compteurs
