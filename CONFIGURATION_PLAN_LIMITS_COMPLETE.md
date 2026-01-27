# Configuration Complète des Limites de Plans Mobile

## 📊 Configuration Recommandée pour Chaque Plan

### 1. **Plan Gratuit (Free)**
```
Searches: 5
Analyses: 2
Downloads: 3
Messages/Day: 10
Max Tokens: 1000
AI Model: gpt-3.5-turbo
Prix: 0 CFA
```
**Rationnelle:**
- Découverte de l'app avec limites strictes
- Incitation à upgrader rapidement
- Coût API minimal: ~$0.02/mois par utilisateur actif

---

### 2. **Plan Étudiant**
```
Searches: 50
Analyses: 20
Downloads: 30
Messages/Day: 100
Max Tokens: 4000
AI Model: gpt-3.5-turbo
Prix: 2000 CFA/mois ($3.30)
```
**Rationnelle:**
- 20 analyses × 4K tokens × GPT-3.5 = **$0.14 coût API**
- Marge: $3.30 - $0.14 = **$3.16 bénéfice (96% marge)** ✅
- Idéal pour étudiants: révisions, QCM, fiches
- Limite quotidienne protège contre abus

---

### 3. **Plan Professionnel (Pro)**
```
Searches: 200
Analyses: 100
Downloads: 150
Messages/Day: 500
Max Tokens: 8000
AI Model: gpt-3.5-turbo (IMPORTANT: Pas GPT-4)
Prix: 5000 CFA/mois ($8.25)
```
**Rationnelle:**
- Avec GPT-4: 100 analyses × 8K = **$18 coût** ❌ Perte de $9.75
- **Solution: Utiliser GPT-3.5-turbo au lieu de GPT-4**
- 100 analyses × 8K tokens × GPT-3.5 = **$0.70 coût API**
- Marge: $8.25 - $0.70 = **$7.55 bénéfice (92% marge)** ✅
- Professionnels ont besoin de plus de volume, pas forcément GPT-4

---

### 4. **Plan Cabinet/Entreprise**
```
Searches: -1 (Illimité)
Analyses: 300 (Fair-use mensuel, affiché comme "illimité")
Downloads: -1 (Illimité)
Messages/Day: -1 (Illimité)
Max Tokens: 16000
AI Model: gpt-4-turbo
Prix: 20000 CFA/mois ($33)
```
**Rationnelle:**
- SANS fair-use: utilisateur heavy = **$80+ coût** ❌ Perte $47+
- **Solution: Fair-use cap de 300 analyses/mois**
- 300 analyses × 16K tokens × GPT-4-Turbo = **$19.20 coût API**
- Marge: $33 - $19.20 = **$13.80 bénéfice (42% marge)** ✅
- Marketing: "Illimité*" avec clause fair-use dans CGV
- Si dépassement: throttling doux ou upgrade vers plan entreprise+

---

## 🔍 Vérification de l'Implémentation Actuelle

### ✅ **Limites Implémentées Correctement**

#### Backend (Laravel)
- ✅ `searches_limit` → `MobileAppPlan::searches_limit`
- ✅ `ai_analyses_limit` → `MobileAppPlan::ai_analyses_limit`
- ✅ `pdf_downloads_limit` → `MobileAppPlan::pdf_downloads_limit`
- ✅ `max_tokens` → `MobileAppPlan::max_tokens`
- ✅ `ai_model` → `MobileAppPlan::ai_model`

**Vérifications Backend:**
```php
// MobileAppSubscription.php - Lines 142-210
public function canSearch() {
    if ($this->plan->searches_limit === -1) return true;
    return $this->searches_used < $this->plan->searches_limit;
}

public function canUseAIAnalysis() {
    if ($this->plan->ai_analyses_limit === -1) return true;
    return $this->ai_analyses_used < $this->plan->ai_analyses_limit;
}

public function canDownloadPDF() {
    if ($this->plan->pdf_downloads_limit === -1) return true;
    return $this->pdf_downloads_used < $this->plan->pdf_downloads_limit;
}
```

#### Flutter
- ✅ `user.searchesUsed` / `user.searchesLimit`
- ✅ `user.analysesUsed` / `user.analysesLimit`
- ✅ `user.downloadsUsed` / `user.downloadsLimit`

**Affichage Flutter:**
```dart
// chat_screen.dart - Line 140
'Analyses: ${user.analysesUsed}/${user.analysesLimit == -1 ? '∞' : user.analysesLimit}'

// documents_screen.dart - Line 128
'Uploads: ${user.downloadsUsed}/${user.downloadsLimit == -1 ? '∞' : user.downloadsLimit}'

// user_model.dart - Lines 138-148
bool canSearch() => searchesUsed < searchesLimit;
bool canAnalyze() => analysesUsed < analysesLimit;
bool canDownload() => downloadsUsed < downloadsLimit;
```

---

### ❌ **Limite NON Implémentée: Messages/Day**

**PROBLÈME CRITIQUE:** La limite "Messages/Day" est affichée dans l'admin mais **n'existe PAS** dans:
- ❌ Base de données (`mobile_app_plans` table)
- ❌ Modèle Laravel (`MobileAppPlan.php`)
- ❌ Contrôleur API (`ChatController.php`)
- ❌ Modèle Flutter (`UserModel`)
- ❌ Interface Flutter (pas d'affichage du quota)

**Impact:**
- Utilisateurs peuvent spammer l'API chat sans limite
- Risque d'abus sur plans gratuits/étudiants
- Coûts API non contrôlés pour conversations longues

---

## 🛠️ Implémentation de Messages/Day Limit

### Étape 1: Migration Base de Données

```sql
-- Ajouter colonne messages_per_day_limit
ALTER TABLE mobile_app_plans 
ADD COLUMN messages_per_day_limit INT DEFAULT 50 AFTER max_tokens;

-- Ajouter colonne messages_sent_today sur subscriptions
ALTER TABLE mobile_app_subscriptions 
ADD COLUMN messages_sent_today INT DEFAULT 0 AFTER pdf_downloads_used,
ADD COLUMN messages_last_reset_date DATE DEFAULT NULL;

-- Update plans existants
UPDATE mobile_app_plans SET messages_per_day_limit = 10 WHERE name = 'free';
UPDATE mobile_app_plans SET messages_per_day_limit = 100 WHERE name = 'student';
UPDATE mobile_app_plans SET messages_per_day_limit = 500 WHERE name = 'pro';
UPDATE mobile_app_plans SET messages_per_day_limit = -1 WHERE name = 'cabinet'; -- Illimité
```

### Étape 2: Modèle Laravel

**MobileAppPlan.php** - Ajouter à `$fillable`:
```php
protected $fillable = [
    'name', 'name_fr', 'price_monthly', 'price_yearly',
    'searches_limit', 'ai_analyses_limit', 'pdf_downloads_limit',
    'messages_per_day_limit', // ← AJOUTER
    'has_full_history', 'has_advanced_ai', 
    'ai_model', 'max_tokens', 'is_active'
];
```

**MobileAppSubscription.php** - Ajouter méthodes:
```php
/**
 * Check if subscription can send message today
 */
public function canSendMessage()
{
    if (!$this->plan) return false;
    
    // Unlimited messages
    if ($this->plan->messages_per_day_limit === -1) {
        return true;
    }
    
    // Reset counter if new day
    $today = now()->toDateString();
    if ($this->messages_last_reset_date !== $today) {
        $this->messages_sent_today = 0;
        $this->messages_last_reset_date = $today;
        $this->save();
    }
    
    return $this->messages_sent_today < $this->plan->messages_per_day_limit;
}

/**
 * Increment message count
 */
public function incrementMessage()
{
    // Reset if new day
    $today = now()->toDateString();
    if ($this->messages_last_reset_date !== $today) {
        $this->messages_sent_today = 1;
        $this->messages_last_reset_date = $today;
    } else {
        $this->increment('messages_sent_today');
    }
    
    $this->save();
}
```

### Étape 3: Contrôleur API

**ChatController.php** - Vérifier avant chaque message:
```php
public function sendMessage(Request $request)
{
    $user = auth()->user();
    $subscription = $user->activeSubscription();
    
    // Check message limit
    if (!$subscription || !$subscription->canSendMessage()) {
        return response()->json([
            'success' => false,
            'message' => 'Limite quotidienne de messages atteinte. Veuillez réessayer demain ou upgrader votre plan.',
            'code' => 'DAILY_MESSAGE_LIMIT_REACHED'
        ], 429);
    }
    
    // Check analysis limit
    if (!$subscription->canUseAIAnalysis()) {
        return response()->json([
            'success' => false,
            'message' => 'Quota d\'analyses épuisé',
            'code' => 'ANALYSIS_LIMIT_REACHED'
        ], 429);
    }
    
    // Process message...
    
    // Increment counters
    $subscription->incrementMessage();
    $subscription->incrementAIAnalysis();
    
    return response()->json([...]);
}
```

**AuthController.php** - Ajouter dans réponse user:
```php
'user' => [
    'id' => $user->id,
    'name' => $user->name,
    // ... autres champs
    'searches_limit' => $subscription?->plan->searches_limit ?? 5,
    'analyses_limit' => $subscription?->plan->ai_analyses_limit ?? 2,
    'downloads_limit' => $subscription?->plan->pdf_downloads_limit ?? 0,
    'messages_limit' => $subscription?->plan->messages_per_day_limit ?? 10, // ← AJOUTER
    'searches_used' => $subscription?->searches_used ?? 0,
    'analyses_used' => $subscription?->ai_analyses_used ?? 0,
    'downloads_used' => $subscription?->pdf_downloads_used ?? 0,
    'messages_used' => $subscription?->messages_sent_today ?? 0, // ← AJOUTER
],
```

### Étape 4: Modèle Flutter

**user_model.dart** - Ajouter propriétés:
```dart
class UserModel {
  final int id;
  final String name;
  final String email;
  // ... autres champs existants
  final int messagesUsed;     // ← AJOUTER
  final int messagesLimit;    // ← AJOUTER
  
  UserModel({
    required this.id,
    required this.name,
    required this.email,
    // ... autres champs
    required this.messagesUsed,
    required this.messagesLimit,
  });
  
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: _parseInt(json['id'], 0),
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      // ... autres champs
      messagesUsed: _parseInt(json['messages_used'], 0),
      messagesLimit: _parseInt(json['messages_limit'], 10),
    );
  }
  
  bool canSendMessage() {
    if (messagesLimit == -1) return true; // Illimité
    return messagesUsed < messagesLimit;
  }
}
```

### Étape 5: Interface Flutter

**chat_screen.dart** - Ajouter affichage quota:
```dart
// Dans l'AppBar après le titre
Padding(
  padding: EdgeInsets.only(right: 8),
  child: Column(
    mainAxisSize: MainAxisSize.min,
    crossAxisAlignment: CrossAxisAlignment.end,
    children: [
      Text(
        'Messages: ${user.messagesUsed}/${user.messagesLimit == -1 ? '∞' : user.messagesLimit}',
        style: TextStyle(fontSize: 10, color: Colors.white70),
      ),
      Text(
        'Analyses: ${user.analysesUsed}/${user.analysesLimit == -1 ? '∞' : user.analysesLimit}',
        style: TextStyle(fontSize: 10, color: Colors.white70),
      ),
    ],
  ),
),
```

**Gérer erreur limite atteinte:**
```dart
Future<void> sendMessage(String message) async {
  try {
    final response = await _apiService.sendChatMessage(message);
    
    if (response['success'] == true) {
      // Success
    } else if (response['code'] == 'DAILY_MESSAGE_LIMIT_REACHED') {
      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          title: Text('Limite quotidienne atteinte'),
          content: Text(
            'Vous avez atteint votre limite de ${user.messagesLimit} messages par jour.\n\n'
            'Revenez demain ou upgradez votre plan pour continuer.'
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text('OK'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.pop(context);
                Navigator.pushNamed(context, '/subscription');
              },
              child: Text('Upgrader'),
            ),
          ],
        ),
      );
    }
  } catch (e) {
    // Error handling
  }
}
```

---

## 📋 Seeder Mis à Jour

**database/seeders/MobileAppPlansSeeder.php:**
```php
$plans = [
    [
        'name' => 'free',
        'name_fr' => 'Plan Gratuit',
        'price_monthly' => 0,
        'price_yearly' => 0,
        'searches_limit' => 5,
        'ai_analyses_limit' => 2,
        'pdf_downloads_limit' => 3,
        'messages_per_day_limit' => 10, // ← AJOUTER
        'has_full_history' => false,
        'has_advanced_ai' => false,
        'ai_model' => 'gpt-3.5-turbo',
        'max_tokens' => 1000,
        'is_active' => true,
    ],
    [
        'name' => 'student',
        'name_fr' => 'Plan Étudiant',
        'price_monthly' => 2000,
        'price_yearly' => 22000,
        'searches_limit' => 50,
        'ai_analyses_limit' => 20,
        'pdf_downloads_limit' => 30,
        'messages_per_day_limit' => 100, // ← AJOUTER
        'has_full_history' => true,
        'has_advanced_ai' => false,
        'ai_model' => 'gpt-3.5-turbo',
        'max_tokens' => 4000,
        'is_active' => true,
    ],
    [
        'name' => 'pro',
        'name_fr' => 'Plan Professionnel',
        'price_monthly' => 5000,
        'price_yearly' => 55000,
        'searches_limit' => 200,
        'ai_analyses_limit' => 100,
        'pdf_downloads_limit' => 150,
        'messages_per_day_limit' => 500, // ← AJOUTER
        'has_full_history' => true,
        'has_advanced_ai' => true,
        'ai_model' => 'gpt-3.5-turbo', // ← CHANGER DE GPT-4 à GPT-3.5
        'max_tokens' => 8000, // ← CHANGER DE 4000 à 8000
        'is_active' => true,
    ],
    [
        'name' => 'cabinet',
        'name_fr' => 'Plan Cabinet/Entreprise',
        'price_monthly' => 20000,
        'price_yearly' => 220000,
        'searches_limit' => -1, // Illimité
        'ai_analyses_limit' => 300, // ← Fair-use (affiché comme illimité)
        'pdf_downloads_limit' => -1, // Illimité
        'messages_per_day_limit' => -1, // ← Illimité
        'has_full_history' => true,
        'has_advanced_ai' => true,
        'ai_model' => 'gpt-4-turbo',
        'max_tokens' => 16000, // ← CHANGER DE 8000 à 16000
        'is_active' => true,
    ],
];
```

---

## 🎯 Plan d'Action Complet

### Phase 1: Mise à Jour Immédiate (Pas de Messages/Day)

**Si tu veux déployer rapidement sans Messages/Day:**

```sql
-- Mettre à jour les plans existants
UPDATE mobile_app_plans 
SET 
  searches_limit = 50,
  ai_analyses_limit = 20,
  pdf_downloads_limit = 30,
  max_tokens = 4000
WHERE name = 'student';

UPDATE mobile_app_plans 
SET 
  searches_limit = 200,
  ai_analyses_limit = 100,
  pdf_downloads_limit = 150,
  ai_model = 'gpt-3.5-turbo',  -- IMPORTANT: Pas GPT-4
  max_tokens = 8000
WHERE name = 'pro';

UPDATE mobile_app_plans 
SET 
  searches_limit = -1,
  ai_analyses_limit = 300,  -- Fair-use cap
  pdf_downloads_limit = -1,
  ai_model = 'gpt-4-turbo',
  max_tokens = 16000
WHERE name = 'cabinet';
```

### Phase 2: Implémentation Messages/Day (Recommandé)

1. **Migration** → Ajouter colonnes `messages_per_day_limit`, `messages_sent_today`, `messages_last_reset_date`
2. **Modèles** → Ajouter méthodes `canSendMessage()` et `incrementMessage()`
3. **API** → Vérifier limite avant chaque message chat
4. **Flutter** → Afficher quota et gérer erreur limite atteinte
5. **Test** → Vérifier reset quotidien, affichage, upgrade prompt

---

## 📊 Récapitulatif des Changements Critiques

| Plan | Ancien max_tokens | **Nouveau max_tokens** | Ancien AI Model | **Nouveau AI Model** | Impact |
|------|-------------------|------------------------|-----------------|----------------------|--------|
| Gratuit | 1000 | ✅ 1000 (OK) | gpt-3.5-turbo | ✅ gpt-3.5-turbo (OK) | Aucun |
| Étudiant | 2000 | **4000** ⬆️ | gpt-3.5-turbo | ✅ gpt-3.5-turbo (OK) | +Valeur pour étudiants |
| Pro | 4000 | **8000** ⬆️ | **gpt-4** | **gpt-3.5-turbo** ⬇️ | -Qualité mais +Rentable |
| Cabinet | 8000 | **16000** ⬆️ | gpt-4-turbo | ✅ gpt-4-turbo (OK) | +Valeur entreprises |

**Justification Plan Pro (changement de GPT-4 → GPT-3.5):**
- Utilisateurs Pro cherchent **VOLUME**, pas forcément top qualité
- GPT-3.5 @ 8K tokens = meilleure valeur que GPT-4 @ 4K
- Coût: $0.70/mois vs $18/mois (économie de 96%)
- Si clients veulent GPT-4 → upgrade vers Cabinet
- Pour 5000 CFA ($8.25), c'est déjà excellent service

---

## ⚠️ Recommandations Finales

### 1. **Déployer Messages/Day Limit** (Priorité Haute)
Sans cette limite, tu risques:
- Spam API sur plans gratuits
- Coûts incontrôlés si utilisateurs chattent toute la journée
- Pas de protection contre abus

### 2. **Changer Plan Pro de GPT-4 → GPT-3.5**
Actuellement tu perds $9.75 par utilisateur Pro actif. C'est insoutenable.

### 3. **Implémenter Fair-Use pour Cabinet**
Affiché comme "illimité*" mais cap à 300 analyses/mois. Protège tes marges.

### 4. **Monitoring Obligatoire**
Tracker dans admin:
- Coûts API réels par plan
- Utilisateurs proches des limites
- Utilisateurs dépassant fair-use
- Revenue vs API costs ratio

### 5. **Clause dans CGV**
```
* Usage illimité: fair-use de 300 analyses/mois. Au-delà, nous nous 
réservons le droit de throttler ou demander upgrade vers plan supérieur.
```

### 6. **Plan Entreprise+ (Optionnel)**
Si clients veulent vraiment illimité:
- Prix: 50000-80000 CFA/mois ($80-130)
- GPT-4-Turbo
- 500-1000 analyses/mois
- Support prioritaire

---

## 🔗 Fichiers à Modifier

**Backend:**
- [ ] `database/migrations/XXXX_add_messages_limit_to_plans.php` (nouveau)
- [ ] `app/Models/MobileAppPlan.php` (ajouter messages_per_day_limit)
- [ ] `app/Models/MobileAppSubscription.php` (ajouter canSendMessage, incrementMessage)
- [ ] `app/Http/Controllers/Api/Mobile/ChatController.php` (vérifier limite)
- [ ] `app/Http/Controllers/Api/Mobile/AuthController.php` (retourner messages_limit)
- [ ] `database/seeders/MobileAppPlansSeeder.php` (update valeurs)

**Flutter:**
- [ ] `lib/data/models/user_model.dart` (ajouter messagesUsed/messagesLimit)
- [ ] `lib/presentation/screens/chat/chat_screen.dart` (afficher quota, gérer erreur)
- [ ] `lib/data/providers/auth_provider.dart` (parser messages_limit)

---

**Questions?** Dis-moi si tu veux que je génère:
1. Les fichiers de migration SQL complets
2. Le code Laravel complet pour messages/day
3. Le code Flutter complet pour messages/day
4. Ou si tu veux d'abord déployer Phase 1 (sans messages/day)
