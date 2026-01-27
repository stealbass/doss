# Checklist de Synchronisation Laravel ↔️ Flutter

## ✅ État actuel

- [x] Utilisateurs enregistrés dans table `users`
- [x] App Flutter peut créer des comptes
- [ ] Statistiques utilisateur synchronisées
- [ ] Plans d'abonnement synchronisés avec le format Flutter
- [ ] Endpoints API retournent le bon format

---

## 🔄 Synchronisation Requise

### 1. Modèle User - Champs à Ajouter

**Status:** ⚠️ À faire

Ajouter ces 3 champs à la table `users`:

```sql
ALTER TABLE users ADD COLUMN summaries_generated INT DEFAULT 0;
ALTER TABLE users ADD COLUMN quizzes_created INT DEFAULT 0;
ALTER TABLE users ADD COLUMN revision_sessions INT DEFAULT 0;
```

**Ou via migration Laravel:**

```php
php artisan make:migration add_user_statistics_to_users_table

# Puis dans la migration:
Schema::table('users', function (Blueprint $table) {
    $table->integer('summaries_generated')->default(0);
    $table->integer('quizzes_created')->default(0);
    $table->integer('revision_sessions')->default(0);
});

php artisan migrate
```

**Vérification:**
```bash
# Dans Laravel Tinker
php artisan tinker
> Schema::getColumnListing('users')
# Devrait afficher 'summaries_generated', 'quizzes_created', 'revision_sessions'
```

---

### 2. Endpoint `/api/subscriptions/plans` 

**Status:** ⚠️ À corriger

**Problème actuel:**
```json
{
  "success": true,
  "data": [...]  // ❌ Flutter attend "plans" pas "data"
}
```

**Correction à appliquer:**

Remplacer le contenu de `app/Http/Controllers/Api/Mobile/SubscriptionController.php` par le fichier `SubscriptionController_CORRECTED.php` fourni.

**Points clés:**
1. La clé de retour doit être `"plans"` et non `"data"`
2. La structure de chaque plan doit être:
   ```json
   {
     "id": "1",
     "name": "Gratuit",
     "price": 0,
     "currency": "XAF",
     "duration": "monthly",
     "features": [...],
     "limits": {
       "searches": 5,
       "analyses": 2,
       "downloads": 0
     }
   }
   ```

**Vérification:**
```bash
# Tester l'endpoint
curl http://localhost:8000/api/subscriptions/plans | jq '.plans'

# Ou depuis PHP
$response = Http::get('api/subscriptions/plans');
dd($response->json()); // Vérifier que 'plans' est présent
```

---

### 3. Nouveau Endpoint: `/api/user/profile`

**Status:** ❌ À créer

**Cet endpoint doit retourner:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+237690000000",
    "role": "student",
    "plan": "Étudiant",
    "jurisdiction": "CM",
    "subscription_end": "2025-01-28T23:59:59Z",
    "searches_used": 5,
    "searches_limit": 50,
    "analyses_used": 2,
    "analyses_limit": 20,
    "downloads_used": 1,
    "downloads_limit": 10,
    "referral_count": 3,
    "referral_code": "JOHN123ABC",
    "summaries_generated": 12,
    "quizzes_created": 8,
    "revision_sessions": 24,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

**Route à ajouter dans `routes/api.php`:**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [SubscriptionController::class, 'getUserProfile']);
});
```

**Vérification:**
```bash
# Avec token d'authentification
curl -H "Authorization: Bearer {token}" http://localhost:8000/api/user/profile | jq '.user'
```

---

### 4. Nouveau Endpoint: `/api/user/stats/increment`

**Status:** ❌ À créer

**Cet endpoint permet à Flutter d'incrémenter les stats quand du contenu est créé:**

```bash
POST /api/user/stats/increment
Authorization: Bearer {token}
Content-Type: application/json

{
  "stat_type": "summaries_generated",
  "count": 1
}
```

**Réponse attendue:**
```json
{
  "success": true,
  "message": "Statistic 'summaries_generated' incremented by 1",
  "data": {
    "summaries_generated": 13,
    "quizzes_created": 8,
    "revision_sessions": 24
  }
}
```

**Route à ajouter dans `routes/api.php`:**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/stats/increment', [SubscriptionController::class, 'incrementUserStats']);
});
```

**Vérification:**
```bash
# Test avec cURL
curl -X POST http://localhost:8000/api/user/stats/increment \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"stat_type": "summaries_generated", "count": 1}'
```

---

## 🧪 Tests d'Intégration Complets

### Test 1: Vérifier que les champs User existent

```php
// Dans Laravel Tinker
php artisan tinker

> $user = User::first();
> $user->summaries_generated; // Devrait retourner un entier
> $user->increment('summaries_generated');
> $user->summaries_generated; // Devrait avoir augmenté
```

### Test 2: Vérifier l'endpoint plans

```bash
# Via cURL
curl http://localhost:8000/api/subscriptions/plans | jq '.plans | length'
# Devrait retourner 4 (ou le nombre de plans dans votre BD)

curl http://localhost:8000/api/subscriptions/plans | jq '.plans[0]'
# Devrait avoir les champs: id, name, price, currency, duration, features, limits
```

### Test 3: Vérifier le profil utilisateur

```bash
# Via cURL avec token
TOKEN="votre_token_jwt_ici"
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/user/profile | jq '.user'
# Devrait avoir les champs avec stats (summaries_generated, etc.)
```

### Test 4: Incrémenter une stat

```bash
# Via cURL avec token
TOKEN="votre_token_jwt_ici"
curl -X POST http://localhost:8000/api/user/stats/increment \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"stat_type": "quizzes_created"}'
# Devrait retourner success: true
```

---

## 🚀 Plan d'Implémentation

### Phase 1: Base de Données (5 min)
- [ ] Exécuter la migration pour ajouter les champs de stats utilisateur
- [ ] Vérifier que les colonnes existent: `summaries_generated`, `quizzes_created`, `revision_sessions`

### Phase 2: Contrôleur (10 min)
- [ ] Remplacer `SubscriptionController.php` par `SubscriptionController_CORRECTED.php`
- [ ] Vérifier les imports et relations du modèle User

### Phase 3: Routes (5 min)
- [ ] Ajouter les 4 routes dans `routes/api.php`:
  1. `GET /api/subscriptions/plans` (déjà existant, juste correction)
  2. `GET /api/user/profile` (nouveau)
  3. `POST /api/user/stats/increment` (nouveau)
  4. `GET /api/subscriptions/current` (déjà existant)

### Phase 4: Tests (10 min)
- [ ] Tester chaque endpoint avec cURL ou Postman
- [ ] Vérifier que les données retournées correspondent au format attendu

### Phase 5: Redéploiement Flutter (5 min)
- [ ] Lancer `flutter run --release` sur la machine de test
- [ ] Vérifier que:
  - Documents screen affiche les documents (ou message vide)
  - Tools Hub affiche les stats dynamiques
  - Subscription Plans affiche les prix de l'admin
  - Admin Dashboard reçoit les stats des utilisateurs mobiles

---

## 📋 Fichiers à Modifier

1. **Création Migration:**
   ```bash
   php artisan make:migration add_user_statistics_to_users_table
   ```

2. **Remplacement du Contrôleur:**
   - Copier `SubscriptionController_CORRECTED.php`
   - → `app/Http/Controllers/Api/Mobile/SubscriptionController.php`

3. **Mise à jour des Routes:**
   - Éditer `routes/api.php`
   - Ajouter les 3 routes manquantes (voir section Routes ci-dessus)

4. **Vérification du Modèle User:**
   - Éditer `app/Models/User.php`
   - Ajouter relation `mobileAppSubscription()` si manquante

---

## ⚠️ Points Critiques à Vérifier

- [ ] La table `users` a les 3 nouveaux champs
- [ ] L'endpoint `/api/subscriptions/plans` retourne `"plans"` et non `"data"`
- [ ] Le format de chaque plan a les champs exacts: `id`, `name`, `price`, `currency`, `duration`, `features`, `limits`
- [ ] Les nouveaux endpoints `/api/user/profile` et `/api/user/stats/increment` sont accessibles
- [ ] Aucune erreur 500 quand on appelle les endpoints
- [ ] Les tokens JWT sont valides et l'authentification fonctionne

---

## 🐛 Dépannage

**Q: J'ai une erreur "Column 'summaries_generated' doesn't exist"**
A: La migration n'a pas été exécutée. Lancez `php artisan migrate`

**Q: L'endpoint retourne "Unauthenticated"**
A: Assurez-vous que le header `Authorization: Bearer {token}` est présent

**Q: Flutter dit "Provider<DocumentProvider> not found"**
A: C'est résolu côté Flutter (voir FLUTTER_FIXES_RUNTIME_ISSUES.md)

**Q: Les plans retournent des prix incorrects**
A: Vérifiez que `MobileAppPlan` a les bons prix dans la BD

---

## ✅ Validation Finale

Après tout déploiement, tester depuis l'app Flutter:

1. Ouvrir Subscription Plans → Les prix doivent être à jour
2. Naviguer vers Documents → Pas de crash "Provider not found"
3. Créer du contenu → Tools Hub affiche les stats qui augmentent
4. Vérifier admin → Les utilisateurs et leurs stats apparaissent

Bon courage! 🚀
