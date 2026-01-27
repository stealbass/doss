# 🔧 Fix pour Duplicate Subscriptions

## 🐛 Problème Détecté

Dans la base de données, il y a **2 subscriptions ACTIVES** pour le même user (ID 201):
- Subscription ID 2: `plan_id = 201`, `status = active`, `expires_at = NULL`
- Subscription ID 3: `plan_id = 201`, `status = active`, `expires_at = NULL`

Quand l'app fait login, elle récupère la subscription avec `activeMobileSubscription()` qui cherche la plus récente, MAIS le filtre `expires_at > now()` excluait les subscriptions avec `expires_at = NULL`.

## ✅ Corrections Appliquées

### 1. **Backend Laravel - Modèle User (FAIT)**

Corrigé la méthode `activeMobileSubscription()` dans `app/Models/User.php`:

**Avant:**
```php
public function activeMobileSubscription()
{
    return $this->hasOne(MobileAppSubscription::class)
                ->where('status', 'active')
                ->where('expires_at', '>', now())  // ❌ Exclut expires_at = NULL
                ->latest();
}
```

**Après:**
```php
public function activeMobileSubscription()
{
    return $this->hasOne(MobileAppSubscription::class)
                ->where('status', 'active')
                ->where(function($query) {
                    // ✅ Inclut expires_at NULL OU expiration future
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->latest('created_at');
}
```

### 2. **Base de Données - Nettoyer les Subscriptions Dupliquées**

Exécutez ce script SQL dans phpMyAdmin:

```sql
-- ===== POUR L'USER 201 (ou votre user) =====

-- 1. Voir les subscriptions actuelles
SELECT id, user_id, mobile_app_plan_id, status, created_at, expires_at 
FROM mobile_app_subscriptions 
WHERE user_id = 201
ORDER BY created_at DESC;

-- 2. Marquer l'ancienne subscription comme CANCELLED
UPDATE mobile_app_subscriptions 
SET status = 'cancelled'
WHERE user_id = 201 
AND status = 'active'
AND id NOT IN (
    SELECT id FROM (
        SELECT id FROM mobile_app_subscriptions 
        WHERE user_id = 201 
        AND status = 'active'
        ORDER BY created_at DESC
        LIMIT 1
    ) AS latest
);

-- 3. Vérifier le résultat (il ne devrait rester qu'1 active)
SELECT id, user_id, mobile_app_plan_id, status, created_at, expires_at 
FROM mobile_app_subscriptions 
WHERE user_id = 201
ORDER BY created_at DESC;
```

### 3. **Script Générique pour TOUS les Users**

Si vous avez d'autres users avec subscriptions dupliquées:

```sql
-- Marquer comme cancelled TOUTES les anciennes subscriptions (garder seulement la plus récente par user)
UPDATE mobile_app_subscriptions m1
SET status = 'cancelled'
WHERE status = 'active'
AND id NOT IN (
    SELECT id FROM (
        SELECT id
        FROM mobile_app_subscriptions m2
        WHERE m2.user_id = m1.user_id
        AND m2.status = 'active'
        ORDER BY m2.created_at DESC
        LIMIT 1
    ) AS latest_per_user
);

-- Vérifier: chaque user ne devrait avoir qu'1 subscription ACTIVE
SELECT user_id, COUNT(*) as active_count
FROM mobile_app_subscriptions
WHERE status = 'active'
GROUP BY user_id
HAVING COUNT(*) > 1;
```

## 🔄 Processus de Test

Après avoir appliqué les corrections:

### Étape 1: Exécuter le Script SQL
Connectez-vous à phpMyAdmin et exécutez le script pour nettoyer les subscriptions.

### Étape 2: Redémarrer Laravel (vider le cache)
```bash
php artisan cache:clear
php artisan config:clear
```

### Étape 3: Relancer l'App Flutter
1. Fermez complètement l'app mobile
2. Relancez: `flutter run`
3. Connectez-vous avec l'user 201
4. Vérifiez que le plan affiche maintenant "Pro/Cabinet" au lieu de "Gratuit"

### Étape 4: Vérifier les Cartes de la Bibliothèque
- ✅ Veille Juridique: doit être DÉVERROUILLÉE (pas de cadenas)
- ✅ Anonymisation: doit être DÉVERROUILLÉE (pas de cadenas) 
- ✅ Multi-comptes: doit être DÉVERROUILLÉE (pas de cadenas)

## 🧪 Test API Direct

Pour tester sans l'app Flutter:

```bash
# 1. Login et récupérer le token
curl -X POST https://dossypro.com/api/mobile/login \
  -H "Content-Type: application/json" \
  -d '{"email":"votre-email@example.com","password":"votre-password"}'

# La réponse devrait inclure:
# {
#   "success": true,
#   "subscription": {
#     "plan_name": "Cabinet/Entreprise",  // ✅ Doit afficher le bon plan
#     "status": "active"
#   }
# }
```

## 📋 Checklist de Vérification

- [ ] Script SQL exécuté avec succès
- [ ] Ancien subscription marqué comme 'cancelled'
- [ ] Un seul subscription 'active' par user
- [ ] Laravel cache vidé
- [ ] App Flutter redémarrée
- [ ] Login retourne le bon plan
- [ ] UI affiche les bonnes icônes (pas de cadenas)

## 🚀 Après les Corrections

Votre user 201 devrait maintenant:

1. **Se connecter et voir "Pro/Cabinet"** au lieu de "Gratuit"
2. **Accéder à Veille Juridique** (pas verrouillé)
3. **Accéder à Anonymisation** (pas verrouillé)
4. **Accéder à Multi-comptes** (pas verrouillé)
5. **Voir les documents** (Templates, Ressources Fiscales, etc.)

## 📌 Notes Importantes

1. **Dates d'expiration NULL:** Les plans gratuit et professionnel n'ont pas de date d'expiration (`expires_at = NULL`). La correction dans le modèle User gère maintenant ce cas correctement.

2. **Ordre de récupération:** Avec `latest('created_at')`, on récupère toujours la subscription la plus récente, ce qui est ce que vous voulez.

3. **Status 'active':** Seules les subscriptions avec `status = 'active'` sont retournées, donc les anciennes annulées ('cancelled') n'interfèrent plus.

4. **Synchronisation:** Une fois le plan changé via l'admin, l'app devrait immédiatement voir le nouveau plan au prochain login.
