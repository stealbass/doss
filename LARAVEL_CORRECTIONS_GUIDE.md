# Laravel Backend - Corrections de Synchronisation avec Flutter

## 📋 Résumé des Corrections

### Changements Majeurs

1. **Format de réponse de l'API**
   - ❌ Avant: `{ "success": true, "data": [...] }`
   - ✅ Après: `{ "success": true, "plans": [...] }`

2. **Structure des plans d'abonnement**
   - Aligné avec le format attendu par Flutter
   - Champs: `id`, `name`, `price`, `currency`, `duration`, `features`, `limits`

3. **Nouveaux endpoints**
   - `GET /api/mobile/user/profile` - Profil utilisateur avec stats
   - `POST /api/mobile/user/stats/increment` - Incrémenter les stats utilisateur

4. **Nouveaux champs utilisateur**
   - `summaries_generated` - Fiches générées
   - `quizzes_created` - QCM créés
   - `revision_sessions` - Sessions révision

---

## 🔧 Migration de la Base de Données

### Ajouter les champs au modèle User

```sql
-- Dans une migration Laravel
Schema::table('users', function (Blueprint $table) {
    $table->integer('summaries_generated')->default(0)->after('revision_sessions');
    $table->integer('quizzes_created')->default(0)->after('summaries_generated');
    $table->integer('revision_sessions')->default(0)->after('quizzes_created');
});
```

Ou si vous utilisez les migrations Artisan:

```bash
php artisan make:migration add_statistics_to_users_table
```

Contenu de la migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('summaries_generated')->default(0)->nullable();
            $table->integer('quizzes_created')->default(0)->nullable();
            $table->integer('revision_sessions')->default(0)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['summaries_generated', 'quizzes_created', 'revision_sessions']);
        });
    }
};
```

Exécutez:
```bash
php artisan migrate
```

---

## 🛣️ Routes API à Ajouter/Mettre à Jour

Ajouter à `routes/api.php` (groupe authentifié):

```php
Route::middleware('auth:sanctum')->group(function () {
    // Subscription endpoints
    Route::prefix('subscriptions')->group(function () {
        // GET plans - CORRIGÉ: Retourne 'plans' au lieu de 'data'
        Route::get('plans', [SubscriptionController::class, 'getPlans']);
        
        // GET current subscription for user
        Route::get('current', [SubscriptionController::class, 'getCurrentSubscription']);
        
        // POST initiate payment
        Route::post('initiate-payment', [SubscriptionController::class, 'initiateSubscription']);
        
        // POST activate subscription
        Route::post('activate', [SubscriptionController::class, 'activateSubscription']);
        
        // DELETE cancel subscription
        Route::delete('cancel', [SubscriptionController::class, 'cancelSubscription']);
        
        // GET payment history
        Route::get('payments', [SubscriptionController::class, 'getPaymentHistory']);
    });
    
    // User endpoints
    Route::prefix('user')->group(function () {
        // GET user profile with statistics - NOUVEAU
        Route::get('profile', [SubscriptionController::class, 'getUserProfile']);
        
        // POST increment user statistics - NOUVEAU
        Route::post('stats/increment', [SubscriptionController::class, 'incrementUserStats']);
    });
});

// Public route for plans
Route::get('subscriptions/plans', [SubscriptionController::class, 'getPlans']);
```

---

## 🔄 Migration du Code

### Étape 1: Sauvegarder l'ancien contrôleur
```bash
cp app/Http/Controllers/Api/Mobile/SubscriptionController.php app/Http/Controllers/Api/Mobile/SubscriptionController.backup.php
```

### Étape 2: Remplacer le contrôleur
Copier le contenu de `SubscriptionController_CORRECTED.php` vers votre fichier `app/Http/Controllers/Api/Mobile/SubscriptionController.php`

### Étape 3: Vérifier l'import du User model
```php
namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;  // IMPORTANT: Assurez-vous que le modèle User est importé
use App\Models\MobileAppPlan;
// ... autres imports
```

### Étape 4: Vérifier les relations du modèle User
Dans `app/Models/User.php`, assurez-vous que:

```php
class User extends Model
{
    // ... autres code
    
    public function mobileAppSubscription()
    {
        return $this->hasOne(MobileAppSubscription::class);
    }
    
    // ... autres relations
}
```

---

## 📝 Fichiers Modèles Requis

Vous devez avoir ces modèles:

1. **MobileAppPlan** - Plans d'abonnement
2. **MobileAppSubscription** - Abonnements utilisateur
3. **MobileAppPayment** - Historique des paiements
4. **ReferralReward** - Récompenses de parrainage (optionnel)

Si certains manquent, créez-les avec:
```bash
php artisan make:model MobileAppPlan -m
php artisan make:model MobileAppSubscription -m
php artisan make:model MobileAppPayment -m
```

---

## 🧪 Endpoints à Tester

### 1. Récupérer les plans (PUBLIC)
```bash
GET /api/subscriptions/plans

Response:
{
  "success": true,
  "plans": [
    {
      "id": "1",
      "name": "Gratuit",
      "price": 0,
      "currency": "XAF",
      "duration": "monthly",
      "features": ["5 recherches par mois", "2 analyses IA par mois", ...],
      "limits": {
        "searches": 5,
        "analyses": 2,
        "downloads": 0
      }
    },
    ...
  ]
}
```

### 2. Obtenir le profil utilisateur (AUTHENTIFIÉ)
```bash
GET /api/user/profile
Authorization: Bearer {token}

Response:
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "plan": "Étudiant",
    "summaries_generated": 12,
    "quizzes_created": 8,
    "revision_sessions": 24,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

### 3. Incrémenter une statistique (AUTHENTIFIÉ)
```bash
POST /api/user/stats/increment
Authorization: Bearer {token}
Content-Type: application/json

{
  "stat_type": "summaries_generated",
  "count": 1
}

Response:
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

### 4. Abonnement courant (AUTHENTIFIÉ)
```bash
GET /api/subscriptions/current
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "subscription_id": 123,
    "plan": {
      "id": "2",
      "name": "Étudiant",
      "price": 2000,
      "currency": "XAF"
    },
    "status": "active",
    "end_date": "2025-01-28",
    "usage": {
      "searches": {
        "used": 10,
        "limit": 50,
        "remaining": 40
      },
      ...
    }
  }
}
```

---

## ⚠️ Checklist de Vérification

- [ ] Migration de base de données exécutée
- [ ] Modèle User a les champs `summaries_generated`, `quizzes_created`, `revision_sessions`
- [ ] SubscriptionController remplacé avec la version corrigée
- [ ] Routes API ajoutées dans `routes/api.php`
- [ ] Relation `mobileAppSubscription()` existe dans le modèle User
- [ ] Tous les modèles MobileApp* existent
- [ ] Tests manuels des endpoints avec Postman ou cURL
- [ ] Flutter app testée avec les nouveaux endpoints

---

## 🐛 Dépannage

### Error: "Call to undefined method User::mobileAppSubscription()"
**Solution:** Ajouter la relation dans le modèle User:
```php
public function mobileAppSubscription()
{
    return $this->hasOne(MobileAppSubscription::class);
}
```

### Error: "Model MobileAppPlan not found"
**Solution:** Créer le modèle:
```bash
php artisan make:model MobileAppPlan -m
```

### Flutter reçoit `{"success": false, "message": "Unauthenticated"}`
**Solution:** Vérifier que:
1. Token JWT est valide
2. Header `Authorization: Bearer {token}` est présent
3. Le middleware `auth:sanctum` est correctement configuré

### Plans ne s'affichent pas dans l'app Flutter
**Solution:** Vérifier que:
1. L'endpoint retourne `"plans"` et non `"data"`
2. La base de données `mobile_app_plans` contient des enregistrements
3. Aucune erreur 500 dans les logs Laravel

---

## 📞 Nouvelles Fonctionnalités

### Tracking des Statistiques Utilisateur

Quand un utilisateur crée du contenu, appelez:

```php
// Dans vos contrôleurs qui créent du contenu
$user = Auth::user();

// Après créer une fiche d'arrêt
$user->increment('summaries_generated');

// Après créer un QCM
$user->increment('quizzes_created');

// Après une session révision
$user->increment('revision_sessions');
```

Ou via l'API depuis Flutter:
```bash
POST /api/user/stats/increment
{
  "stat_type": "summaries_generated",
  "count": 1
}
```

---

## 📊 Admin Dashboard

Pour afficher les utilisateurs mobiles dans votre admin, ajouter une page:

```php
// app/Http/Controllers/Admin/MobileUsersController.php
public function index()
{
    $users = User::whereNotNull('email_verified_at')
        ->with('mobileAppSubscription.plan')
        ->paginate(50);
    
    return view('admin.mobile-users', [
        'users' => $users,
    ]);
}
```

Vérifier que:
- Les utilisateurs de la table `users` créés via l'app mobile s'affichent
- Les stats `summaries_generated`, `quizzes_created`, `revision_sessions` apparaissent
- L'abonnement courant et ses limites d'utilisation s'affichent

---

## 🚀 Déploiement

Après les corrections:

```bash
# 1. Exécuter les migrations
php artisan migrate

# 2. Vider le cache
php artisan config:cache
php artisan cache:clear

# 3. Tester les endpoints
# Utilisez Postman ou cURL

# 4. Mettre à jour l'app Flutter et la redéployer
cd dossy_chat_ia
flutter run --release
```

---

## Questions?

Si vous avez besoin d'ajustements supplémentaires, les points à clarifier sont:

1. **Noms des tables**: Assurez-vous que `mobile_app_plans`, `mobile_app_subscriptions`, `mobile_app_payments` existent
2. **Champs du modèle MobileAppPlan**: Vérifier les noms exacts (`price_monthly`, `ai_analyses_limit`, etc.)
3. **Relations des modèles**: Confirmer que tous les hasOne/hasMany sont correctement définis
4. **Authentification**: Vérifier que `auth:sanctum` est configuré correctement

Contactez-moi si vous avez besoin de plus de détails sur les modèles ou migrations!
