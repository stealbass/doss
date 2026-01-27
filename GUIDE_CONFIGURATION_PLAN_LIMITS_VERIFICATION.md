# Guide Complet: Configuration des Limites de Plans et Vérification Flutter

## ✅ Résumé de l'Analyse

### Limites Actuellement Implémentées
| Limite | Base de Données | Backend Laravel | API AuthController | Flutter UserModel | Status |
|--------|-----------------|-----------------|-------------------|-------------------|---------|
| **Searches** | ✅ searches_limit | ✅ canSearch() | ✅ searches_used/limit | ✅ canSearch() | **FONCTIONNEL** |
| **Analyses** | ✅ ai_analyses_limit | ✅ canUseAIAnalysis() | ✅ analyses_used/limit | ✅ canAnalyze() | **FONCTIONNEL** |
| **Downloads** | ✅ pdf_downloads_limit | ✅ canDownloadPDF() | ✅ downloads_used/limit | ✅ canDownload() | **FONCTIONNEL** |
| **Messages/Day** | ❌ **MANQUANT** | ❌ **MANQUANT** | ❌ **MANQUANT** | ❌ **MANQUANT** | **NON IMPLÉMENTÉ** |
| **Max Tokens** | ✅ max_tokens | ✅ Utilisé dans ChatController | ❌ Non retourné | ❌ Non stocké | **PARTIELLEMENT** |

---

## 📊 Configuration Recommandée des Plans

### **Plan Gratuit (Free)**
```sql
-- Recommandations
searches_limit = 5
ai_analyses_limit = 2
pdf_downloads_limit = 3
messages_per_day_limit = 10  ← À AJOUTER
max_tokens = 1000
ai_model = 'gpt-3.5-turbo'

-- Coût API estimé: $0.02/mois par utilisateur actif
-- Prix: 0 CFA
-- Marge: N/A (acquisition)
```

### **Plan Étudiant**
```sql
-- Recommandations
searches_limit = 50
ai_analyses_limit = 20
pdf_downloads_limit = 30
messages_per_day_limit = 100  ← À AJOUTER
max_tokens = 4000  ← AUGMENTER (actuellement 2000)
ai_model = 'gpt-3.5-turbo'

-- Coût API: $0.14/mois (20 analyses × 4K tokens)
-- Prix: 2000 CFA ($3.30)
-- Marge: $3.16 (96%) ✅ RENTABLE
```

### **Plan Professionnel**
```sql
-- Recommandations
searches_limit = 200
ai_analyses_limit = 100
pdf_downloads_limit = 150
messages_per_day_limit = 500  ← À AJOUTER
max_tokens = 8000  ← AUGMENTER (actuellement 4000)
ai_model = 'gpt-3.5-turbo'  ← CHANGER (actuellement gpt-4)

-- Avec gpt-4: $18/mois ❌ PERTE $9.75
-- Avec gpt-3.5-turbo: $0.70/mois ✅ RENTABLE
-- Prix: 5000 CFA ($8.25)
-- Marge: $7.55 (92%) ✅ RENTABLE
```

### **Plan Cabinet/Entreprise**
```sql
-- Recommandations
searches_limit = -1 (Illimité)
ai_analyses_limit = 300  ← FAIR-USE CAP (actuellement -1)
pdf_downloads_limit = -1 (Illimité)
messages_per_day_limit = -1 (Illimité)  ← À AJOUTER
max_tokens = 16000  ← AUGMENTER (actuellement 8000)
ai_model = 'gpt-4-turbo'

-- Sans fair-use: $80+/mois ❌ PERTE $47+
-- Avec 300 analyses fair-use: $19.20/mois ✅ RENTABLE
-- Prix: 20000 CFA ($33)
-- Marge: $13.80 (42%) ✅ RENTABLE
```

---

## 🔧 Script SQL d'Application (À Exécuter Maintenant)

### Phase 1: Ajouter Colonnes (Obligatoire)
```sql
-- 1. Ajouter messages_per_day_limit dans mobile_app_plans
ALTER TABLE mobile_app_plans 
ADD COLUMN IF NOT EXISTS messages_per_day_limit INT DEFAULT 50 
AFTER pdf_downloads_limit;

-- 2. Ajouter tracking messages dans mobile_app_subscriptions
ALTER TABLE mobile_app_subscriptions 
ADD COLUMN IF NOT EXISTS messages_sent_today INT DEFAULT 0 
AFTER pdf_downloads_used;

ALTER TABLE mobile_app_subscriptions 
ADD COLUMN IF NOT EXISTS messages_last_reset_date DATE DEFAULT NULL 
AFTER messages_sent_today;
```

### Phase 2: Mettre à Jour Plans Existants
```sql
-- Plan Gratuit
UPDATE mobile_app_plans 
SET 
    messages_per_day_limit = 10,
    searches_limit = 5,
    ai_analyses_limit = 2,
    pdf_downloads_limit = 3,
    max_tokens = 1000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'free';

-- Plan Étudiant
UPDATE mobile_app_plans 
SET 
    messages_per_day_limit = 100,
    searches_limit = 50,
    ai_analyses_limit = 20,
    pdf_downloads_limit = 30,
    max_tokens = 4000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'student';

-- Plan Professionnel (CHANGEMENT CRITIQUE: gpt-4 → gpt-3.5-turbo)
UPDATE mobile_app_plans 
SET 
    messages_per_day_limit = 500,
    searches_limit = 200,
    ai_analyses_limit = 100,
    pdf_downloads_limit = 150,
    max_tokens = 8000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'pro';

-- Plan Cabinet (CHANGEMENT CRITIQUE: ai_analyses_limit -1 → 300)
UPDATE mobile_app_plans 
SET 
    messages_per_day_limit = -1,
    searches_limit = -1,
    ai_analyses_limit = 300,
    pdf_downloads_limit = -1,
    max_tokens = 16000,
    ai_model = 'gpt-4-turbo'
WHERE name = 'cabinet';
```

### Vérification Après Mise à Jour
```sql
SELECT 
    name,
    name_fr,
    price_monthly,
    searches_limit,
    ai_analyses_limit,
    pdf_downloads_limit,
    messages_per_day_limit,
    max_tokens,
    ai_model
FROM mobile_app_plans
ORDER BY price_monthly ASC;
```

**Résultat Attendu:**
```
free     | 10  | 1000  | gpt-3.5-turbo
student  | 100 | 4000  | gpt-3.5-turbo
pro      | 500 | 8000  | gpt-3.5-turbo  ← CHANGÉ de gpt-4
cabinet  | -1  | 16000 | gpt-4-turbo (analyses: 300 fair-use)
```

---

## 🚀 Fichiers Modifiés (Déjà Prêts)

### Backend Laravel ✅
1. **Migration:** `database/migrations/2026_01_06_000001_add_messages_limit_to_mobile_app_plans.php`
2. **Model Plan:** `app/Models/MobileAppPlan.php` - ajouté `messages_per_day_limit` dans $fillable
3. **Model Subscription:** `app/Models/MobileAppSubscription.php` - ajouté `canSendMessage()` et `incrementMessage()`
4. **AuthController:** ⚠️ **À FINALISER MANUELLEMENT** (voir section suivante)

### À Finaliser Manuellement

**Fichier:** `app/Http/Controllers/Api/Mobile/AuthController.php`

Ajouter ces 2 lignes dans 4 endroits (register, login, refresh, getUserInfo):

```php
// AJOUTER APRÈS downloads_limit dans chaque endpoint:
'messages_used' => $subscription ? $subscription->messages_sent_today : 0,
'messages_limit' => $subscription && $subscription->plan ? $subscription->plan->messages_per_day_limit : 10,
```

**Endroits précis:**
1. **Ligne ~144** (register - free plan)
2. **Ligne ~239** (login)
3. **Ligne ~373** (refresh)
4. **Ligne ~501** (getUserInfo)

**Exemple pour register (ligne 144):**
```php
'downloads_used' => 0,
'downloads_limit' => $freePlan->pdf_downloads_limit ?? 0,
'messages_used' => 0,  ← AJOUTER
'messages_limit' => $freePlan->messages_per_day_limit ?? 10,  ← AJOUTER
'referral_code' => $user->referral_code,
```

**Exemple pour login (ligne 239):**
```php
'downloads_limit' => $subscription && $subscription->plan ? $subscription->plan->pdf_downloads_limit : 0,
'messages_used' => $subscription ? $subscription->messages_sent_today : 0,  ← AJOUTER
'messages_limit' => $subscription && $subscription->plan ? $subscription->plan->messages_per_day_limit : 10,  ← AJOUTER
'referral_code' => $user->referral_code,
```

---

## 🔄 Implémenter Vérification Messages dans ChatController

**Fichier:** `app/Http/Controllers/Api/Mobile/ChatController.php`

Ajouter au début de la méthode `sendMessage()`:

```php
public function sendMessage(Request $request)
{
    $user = auth()->user();
    $subscription = $user->activeSubscription();
    
    // ✅ Vérifier limite quotidienne messages
    if (!$subscription || !$subscription->canSendMessage()) {
        return response()->json([
            'success' => false,
            'message' => 'Limite quotidienne de messages atteinte. Réessayez demain ou upgradez votre plan.',
            'code' => 'DAILY_MESSAGE_LIMIT_REACHED',
        ], 429);
    }
    
    // ✅ Vérifier limite analyses IA
    if (!$subscription->canUseAIAnalysis()) {
        return response()->json([
            'success' => false,
            'message' => 'Quota mensuel d\'analyses épuisé',
            'code' => 'ANALYSIS_LIMIT_REACHED',
        ], 429);
    }
    
    // ... traitement du message existant ...
    
    // ✅ Incrémenter compteurs APRÈS succès
    $subscription->incrementMessage();
    $subscription->incrementAIAnalysis();
    
    return response()->json([...]);
}
```

---

## 📱 Côté Flutter (À Implémenter)

### 1. Modèle User

**Fichier:** `dossy_chat_ia/lib/data/models/user_model.dart`

```dart
class UserModel {
  final int id;
  final String name;
  final String email;
  // ... champs existants ...
  final int messagesUsed;     ← AJOUTER
  final int messagesLimit;    ← AJOUTER
  
  UserModel({
    required this.id,
    required this.name,
    required this.email,
    // ... champs existants ...
    required this.messagesUsed,
    required this.messagesLimit,
  });
  
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: _parseInt(json['id'], 0),
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      // ... champs existants ...
      messagesUsed: _parseInt(json['messages_used'], 0),
      messagesLimit: _parseInt(json['messages_limit'], 10),
    );
  }
  
  bool canSendMessage() {
    if (messagesLimit == -1) return true;
    return messagesUsed < messagesLimit;
  }
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      // ... champs existants ...
      'messages_used': messagesUsed,
      'messages_limit': messagesLimit,
    };
  }
}
```

### 2. Interface Chat

**Fichier:** `dossy_chat_ia/lib/presentation/screens/chat/chat_screen.dart`

**Ajouter affichage quota dans AppBar:**
```dart
appBar: AppBar(
  title: Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text('Chat IA'),
      Row(
        children: [
          Text(
            'Messages: ${user.messagesUsed}/${user.messagesLimit == -1 ? '∞' : user.messagesLimit}',
            style: TextStyle(fontSize: 10, color: Colors.white70),
          ),
          SizedBox(width: 16),
          Text(
            'Analyses: ${user.analysesUsed}/${user.analysesLimit == -1 ? '∞' : user.analysesLimit}',
            style: TextStyle(fontSize: 10, color: Colors.white70),
          ),
        ],
      ),
    ],
  ),
),
```

**Gérer erreur limite atteinte:**
```dart
Future<void> sendMessage(String message) async {
  // Vérifier côté client d'abord
  if (!user.canSendMessage()) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Limite atteinte'),
        content: Text(
          'Vous avez atteint votre limite de ${user.messagesLimit} messages par jour.\n\n'
          'Revenez demain ou upgradez votre plan.'
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
    return;
  }
  
  try {
    final response = await _apiService.sendChatMessage(message);
    
    if (response['success'] == true) {
      // Succès
      setState(() {
        _messages.add(response['data']);
      });
      
      // Rafraîchir user pour mettre à jour quotas
      await Provider.of<AuthProvider>(context, listen: false).refreshUser();
    } else if (response['code'] == 'DAILY_MESSAGE_LIMIT_REACHED') {
      // Backend a détecté dépassement
      _showLimitDialog();
    }
  } catch (e) {
    _showError(e.toString());
  }
}
```

---

## 📊 Affichage dans Admin

L'interface admin **affiche déjà** le champ Messages/Day, mais il faut:

### 1. Vérifier que le contrôleur sauvegarde correctement

**Fichier:** `app/Http/Controllers/MobileAppSettingsController.php`

La validation existe déjà (lignes 173-195), mais il stocke dans `MobileAppSetting` (settings globaux).

**⚠️ PROBLÈME:** L'admin **NE MODIFIE PAS** les plans individuels, juste les settings.

**SOLUTION:** Tu as 2 options:

**Option A: Utiliser l'écran admin "Subscription Plans"** (recommandé)
- Aller dans `/admin/mobile-app-plans` (pas `/admin/mobile-app-settings`)
- Éditer chaque plan individuellement
- Ajouter champ `messages_per_day_limit` dans le formulaire

**Option B: Modifier `MobileAppPlansController` pour ajouter le champ**
```php
// Dans MobileAppPlansController::store() et update()
$validated = $request->validate([
    'name' => 'required|string|max:50',
    'name_fr' => 'required|string|max:100',
    'price_monthly' => 'required|integer|min:0',
    'price_yearly' => 'required|integer|min:0',
    'searches_limit' => 'required|integer|min:-1',
    'ai_analyses_limit' => 'required|integer|min:-1',
    'pdf_downloads_limit' => 'required|integer|min:-1',
    'messages_per_day_limit' => 'required|integer|min:-1',  ← AJOUTER
    'has_full_history' => 'nullable',
    'has_advanced_ai' => 'nullable',
    'ai_model' => 'required|string|max:50',
    'max_tokens' => 'required|integer|min:100|max:100000',
    'is_active' => 'nullable',
]);
```

Puis ajouter dans les vues:
- `resources/views/mobile-app-plans/create.blade.php`
- `resources/views/mobile-app-plans/edit.blade.php`

---

## ✅ Checklist de Déploiement

### Backend (Urgent)
- [ ] Exécuter migration SQL: `update_plan_limits_2026_01_06.sql`
- [ ] Vérifier colonnes ajoutées: `messages_per_day_limit`, `messages_sent_today`, `messages_last_reset_date`
- [ ] Mettre à jour `AuthController.php` (4 endroits)
- [ ] Ajouter vérification dans `ChatController::sendMessage()`
- [ ] Tester API `/login` retourne `messages_used` et `messages_limit`
- [ ] Tester envoi message quand limite atteinte (retourne erreur 429)

### Flutter (Important)
- [ ] Ajouter `messagesUsed` et `messagesLimit` dans `UserModel`
- [ ] Parser `messages_used` et `messages_limit` dans `fromJson()`
- [ ] Ajouter méthode `canSendMessage()`
- [ ] Afficher quota messages dans `chat_screen.dart` AppBar
- [ ] Vérifier limite côté client avant envoi
- [ ] Gérer erreur `DAILY_MESSAGE_LIMIT_REACHED` du backend
- [ ] Afficher dialog upgrade quand limite atteinte

### Admin (Optionnel)
- [ ] Ajouter champ `messages_per_day_limit` dans formulaire edit plan
- [ ] Vérifier que l'onglet "Plan Limits" sauvegarde correctement

### Tests
- [ ] Créer compte plan gratuit → envoyer 10 messages → 11ème doit être refusé
- [ ] Attendre minuit → compteur doit se reset
- [ ] Vérifier plan cabinet illimité (-1) n'a pas de limite
- [ ] Tester API retourne bien `messages_used: 5, messages_limit: 10`

---

## 🎯 Valeurs Finales Recommandées

| Plan | Searches | Analyses | Downloads | **Messages/Day** | Max Tokens | AI Model |
|------|----------|----------|-----------|------------------|------------|----------|
| **Gratuit** | 5 | 2 | 3 | **10** | 1000 | gpt-3.5-turbo |
| **Étudiant** | 50 | 20 | 30 | **100** | **4000** | gpt-3.5-turbo |
| **Pro** | 200 | 100 | 150 | **500** | **8000** | **gpt-3.5-turbo** |
| **Cabinet** | -1 | **300*** | -1 | **-1** | **16000** | gpt-4-turbo |

*Fair-use cap, affiché comme "illimité"

---

## 📈 Rentabilité Après Changements

| Plan | Prix/Mois | Coût API | Marge | Status |
|------|-----------|----------|-------|--------|
| Gratuit | $0 | $0.02 | -$0.02 | ✅ Acquisition |
| Étudiant | $3.30 | $0.14 | $3.16 (96%) | ✅ RENTABLE |
| Pro | $8.25 | $0.70 | $7.55 (92%) | ✅ RENTABLE |
| Cabinet | $33 | $19.20 | $13.80 (42%) | ✅ RENTABLE |

**Avant les changements:**
- Pro: PERTE de $9.75/utilisateur
- Cabinet: PERTE de $47+/utilisateur

**Après les changements:**
- ✅ Tous les plans rentables
- ✅ Marges élevées (42%-96%)
- ✅ Prix restent accessibles

---

## 🚨 Actions Prioritaires Immédiates

1. **URGENT:** Exécuter le SQL pour ajouter `messages_per_day_limit`
2. **URGENT:** Changer Plan Pro de `gpt-4` → `gpt-3.5-turbo`
3. **URGENT:** Ajouter fair-use cap 300 pour Plan Cabinet
4. **IMPORTANT:** Modifier `AuthController.php` pour retourner messages_limit
5. **IMPORTANT:** Implémenter vérification dans `ChatController`
6. **MOYEN:** Mettre à jour Flutter pour afficher quota messages

---

**Questions?** Dis-moi:
1. Tu veux que je génère les modifications Flutter complètes?
2. Tu veux que je modifie les vues admin pour ajouter messages_per_day?
3. Tu préfères exécuter d'abord juste le SQL et tester?
