# 🎯 Fair-Use Cap: Solution pour Afficher "Illimité" avec Limite Cachée

## ❓ Le Problème

**Tu as raison!** Si tu mets `ai_analyses_limit = 300` dans la base de données:
- ✅ Backend respecte la limite de 300
- ❌ **Flutter affiche "300" au lieu de "∞ Illimité"**

```dart
// Code Flutter actuel
'Analyses: ${user.analysesUsed}/${user.analysesLimit == -1 ? '∞' : user.analysesLimit}'
//                                                             ↑
//                              Si analysesLimit = 300, affiche "300"
//                              Si analysesLimit = -1, affiche "∞"
```

---

## ✅ La Solution: Fair-Use Cap Caché

### Concept

**2 colonnes distinctes:**
1. **`ai_analyses_limit`** = -1 → **Vue par Flutter** = Affiche "∞ Illimité"
2. **`fair_use_monthly_cap`** = 300 → **Utilisée par Backend uniquement** = Limite réelle

**Résultat:**
- 🎨 **Frontend:** Utilisateur voit "Analyses illimitées ∞"
- 🔒 **Backend:** Bloque après 300 analyses
- 📋 **Légal:** Mention "fair-use" dans CGV

---

## 📊 Configuration Finale des Plans

| Plan | ai_analyses_limit | fair_use_monthly_cap | Affichage Flutter | Limite Réelle Backend |
|------|-------------------|----------------------|-------------------|-----------------------|
| **Gratuit** | 2 | NULL | "2" | 2 |
| **Étudiant** | 20 | NULL | "20" | 20 |
| **Pro** | 100 | NULL | "100" | 100 |
| **Cabinet** | **-1** | **300** | **"∞ Illimité"** | **300** |

---

## 🚀 Implémentation

### 1. Structure Base de Données

```sql
CREATE TABLE mobile_app_plans (
    id BIGINT PRIMARY KEY,
    name VARCHAR(50),
    name_fr VARCHAR(100),
    
    -- Limites affichées (ce que voit Flutter)
    searches_limit INT DEFAULT 5,
    ai_analyses_limit INT DEFAULT 2,        -- -1 = "∞" dans Flutter
    pdf_downloads_limit INT DEFAULT 3,
    messages_per_day_limit INT DEFAULT 10,
    
    -- Fair-use cap (backend-only, caché de Flutter)
    fair_use_monthly_cap INT DEFAULT NULL,  -- NULL = pas de fair-use
    
    -- Config IA
    ai_model VARCHAR(50),
    max_tokens INT,
    
    -- Features
    has_full_history BOOLEAN,
    has_advanced_ai BOOLEAN,
    is_active BOOLEAN,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. Script SQL d'Application

```sql
-- Étape 1: Ajouter colonnes
ALTER TABLE mobile_app_plans 
ADD COLUMN IF NOT EXISTS messages_per_day_limit INT DEFAULT 50 
AFTER pdf_downloads_limit;

ALTER TABLE mobile_app_plans 
ADD COLUMN IF NOT EXISTS fair_use_monthly_cap INT DEFAULT NULL 
AFTER ai_analyses_limit;

ALTER TABLE mobile_app_subscriptions 
ADD COLUMN IF NOT EXISTS messages_sent_today INT DEFAULT 0 
AFTER pdf_downloads_used;

ALTER TABLE mobile_app_subscriptions 
ADD COLUMN IF NOT EXISTS messages_last_reset_date DATE DEFAULT NULL 
AFTER messages_sent_today;

-- Étape 2: Configurer Plan Cabinet avec fair-use caché
UPDATE mobile_app_plans 
SET 
    ai_analyses_limit = -1,        -- ← Flutter affiche "∞"
    fair_use_monthly_cap = 300,    -- ← Backend limite à 300
    searches_limit = -1,
    pdf_downloads_limit = -1,
    messages_per_day_limit = -1,
    max_tokens = 16000,
    ai_model = 'gpt-4-turbo'
WHERE name = 'cabinet';

-- Étape 3: Autres plans (pas de fair-use)
UPDATE mobile_app_plans 
SET 
    fair_use_monthly_cap = NULL,
    messages_per_day_limit = 10,
    max_tokens = 1000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'free';

UPDATE mobile_app_plans 
SET 
    fair_use_monthly_cap = NULL,
    messages_per_day_limit = 100,
    max_tokens = 4000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'student';

UPDATE mobile_app_plans 
SET 
    fair_use_monthly_cap = NULL,
    messages_per_day_limit = 500,
    max_tokens = 8000,
    ai_model = 'gpt-3.5-turbo'  -- ← Changé de gpt-4
WHERE name = 'pro';
```

### 3. Logique Backend (Laravel)

**Fichier: `app/Models/MobileAppSubscription.php`**

```php
/**
 * Vérifier si l'utilisateur peut faire une analyse IA
 */
public function canUseAIAnalysis()
{
    if (!$this->plan) {
        return false;
    }
    
    // Cas 1: Limite explicite (pas -1)
    if ($this->plan->ai_analyses_limit !== -1) {
        return $this->ai_analyses_used < $this->plan->ai_analyses_limit;
    }
    
    // Cas 2: Plan "illimité" (-1) MAIS avec fair-use cap
    if ($this->plan->fair_use_monthly_cap !== null) {
        return $this->ai_analyses_used < $this->plan->fair_use_monthly_cap;
    }
    
    // Cas 3: Vraiment illimité (rare, pour plans futurs ou VIP)
    return true;
}
```

**Exemple de Flow:**

```php
// Utilisateur Plan Cabinet (ai_analyses_limit = -1, fair_use_cap = 300)
$subscription = auth()->user()->activeSubscription();

if (!$subscription->canUseAIAnalysis()) {
    // ai_analyses_used = 300 → Bloqué par fair-use cap
    return response()->json([
        'success' => false,
        'message' => 'Limite mensuelle atteinte (fair-use 300 analyses). Contactez-nous pour augmenter.',
        'code' => 'FAIR_USE_LIMIT_REACHED',
    ], 429);
}

// ✅ Continuer traitement...
$subscription->incrementAIAnalysis();
```

---

## 🎨 Affichage Flutter (Aucun Changement Nécessaire!)

**Flutter continue de fonctionner tel quel:**

```dart
// UserModel.dart - Parse depuis API
class UserModel {
  final int analysesUsed;
  final int analysesLimit;  // Reçoit -1 pour plan Cabinet
  
  // Pas besoin de parser fair_use_monthly_cap (caché côté backend)
  
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      analysesUsed: json['analyses_used'] ?? 0,
      analysesLimit: json['analyses_limit'] ?? 2,  // -1 pour Cabinet
    );
  }
}

// Chat Screen - Affichage
Text(
  'Analyses: ${user.analysesUsed}/${user.analysesLimit == -1 ? '∞' : user.analysesLimit}',
  // Plan Cabinet: "Analyses: 245/∞" ← Affiche illimité
  // Plan Pro:     "Analyses: 45/100" ← Affiche limite
)
```

**Si utilisateur atteint fair-use (300):**

```dart
// Backend renvoie erreur 429 avec code FAIR_USE_LIMIT_REACHED
if (response['code'] == 'FAIR_USE_LIMIT_REACHED') {
  showDialog(
    context: context,
    builder: (context) => AlertDialog(
      title: Text('Limite d\'utilisation atteinte'),
      content: Text(
        'Vous avez utilisé votre quota mensuel de 300 analyses.\n\n'
        'Votre plan reprend le ${nextResetDate} ou contactez-nous '
        'pour augmenter votre limite.'
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text('OK'),
        ),
        ElevatedButton(
          onPressed: () => _contactSupport(),
          child: Text('Contacter Support'),
        ),
      ],
    ),
  );
}
```

---

## 📋 Récapitulatif Avantages

### ✅ Avantages de cette Approche

1. **Marketing Positif:**
   - Utilisateurs voient "Analyses illimitées ∞"
   - Plus attractif que "300 analyses/mois"
   - Encourage upgrades vers plan Cabinet

2. **Protection Financière:**
   - Backend limite réellement à 300
   - Évite coûts API excessifs ($80+ sans limite)
   - Fair-use légalement valide si mentionné dans CGV

3. **Flexibilité:**
   - Tu peux ajuster `fair_use_monthly_cap` sans changer Flutter
   - Ex: Passer de 300 à 500 pour client VIP
   - Ou mettre NULL pour vraiment illimité

4. **Transparence Optionnelle:**
   - Tu peux afficher "300 analyses utilisées ce mois" dans profil
   - Sans changer l'affichage principal "∞"

5. **Pas de Refonte Flutter:**
   - Code Flutter existant continue de marcher
   - Pas besoin de redéployer l'app mobile

---

## 🔄 Flow Complet Exemple

### Utilisateur Plan Cabinet

**État Initial:**
```
Plan: Cabinet/Entreprise
ai_analyses_limit: -1
fair_use_monthly_cap: 300
ai_analyses_used: 0
```

**Dans Flutter:**
```
Affichage: "Analyses: 0/∞"
```

**Après 250 analyses:**
```
ai_analyses_used: 250
Affichage: "Analyses: 250/∞"  ← Tout va bien
```

**Après 300 analyses (atteint fair-use):**
```
ai_analyses_used: 300
Affichage: "Analyses: 300/∞"
```

**Tentative 301ème analyse:**
```php
// Backend
if (!$subscription->canUseAIAnalysis()) {
    // 300 >= 300 (fair_use_cap) → Bloqué
    return 429 FAIR_USE_LIMIT_REACHED
}
```

**Flutter reçoit erreur 429:**
```dart
showDialog(
  title: 'Limite mensuelle atteinte',
  content: 'Votre quota de 300 analyses est utilisé...'
);
```

**Après reset mensuel (1er du mois):**
```sql
-- Job automatique Laravel
UPDATE mobile_app_subscriptions 
SET ai_analyses_used = 0 
WHERE quota_reset_at < NOW();
```

```
ai_analyses_used: 0  ← Reset
Affichage: "Analyses: 0/∞"  ← Reparti!
```

---

## 🔧 Configuration Admin

### Dans l'Admin Panel

**Onglet "Plan Limits" affiche:**
```
Plan Cabinet/Entreprise:
  Searches:      -1 (Illimité)
  Analyses:      -1 (Illimité)
  Downloads:     -1 (Illimité)
  Messages/Day:  -1 (Illimité)
```

**Le fair-use cap (300) est caché**, géré uniquement en base:

```sql
-- Pour voir/modifier le fair-use cap:
SELECT name_fr, ai_analyses_limit, fair_use_monthly_cap 
FROM mobile_app_plans 
WHERE name = 'cabinet';

-- Ajuster le fair-use cap si nécessaire:
UPDATE mobile_app_plans 
SET fair_use_monthly_cap = 500  -- Augmenter à 500
WHERE name = 'cabinet';
```

---

## 📊 Comparaison Approches

| Critère | **Approche 1: Limite Visible** | **Approche 2: Fair-Use Caché** |
|---------|----------------------------------|--------------------------------|
| **Valeur DB** | ai_analyses_limit = 300 | ai_analyses_limit = -1<br>fair_use_cap = 300 |
| **Affichage Flutter** | "300 analyses/mois" | **"∞ Illimité"** ✅ |
| **Limite Backend** | 300 | 300 |
| **Marketing** | Limite visible | **Plus attractif** ✅ |
| **Légal** | Clair et simple | Nécessite clause CGV |
| **Flexibilité** | Modifier = update UI | **Modifier = update DB only** ✅ |
| **Transparence** | 100% | ~90% (fair-use mentionné) |

---

## 📜 Clause CGV Recommandée

**À ajouter dans Conditions Générales de Vente:**

> ### Plan Cabinet/Entreprise - Utilisation Équitable (Fair-Use)
> 
> Le Plan Cabinet/Entreprise offre un accès "illimité" aux fonctionnalités de Dossy IA, 
> sous réserve d'une utilisation raisonnable et équitable (fair-use).
> 
> **Limite Fair-Use:**
> - Analyses IA: jusqu'à 300 analyses par mois
> - Recherches: illimitées
> - Téléchargements: illimités
> - Messages chat: illimités
> 
> En cas de dépassement de la limite fair-use, Dossy Pro se réserve le droit de:
> - Contacter le client pour discuter d'un plan personnalisé
> - Limiter temporairement l'accès jusqu'au prochain cycle de facturation
> - Proposer un upgrade vers un plan Entreprise+ adapté aux besoins
> 
> Cette politique garantit la qualité de service pour tous nos utilisateurs et la 
> pérennité de la plateforme.

---

## ✅ Checklist d'Implémentation

### Phase 1: Base de Données ✅
- [x] Ajouter colonne `fair_use_monthly_cap` dans `mobile_app_plans`
- [x] Mettre Plan Cabinet: `ai_analyses_limit = -1`, `fair_use_cap = 300`
- [x] Ajouter colonnes `messages_per_day_limit`, `messages_sent_today`, `messages_last_reset_date`
- [x] Vérifier avec SELECT que les valeurs sont correctes

### Phase 2: Backend Laravel ✅
- [x] Modifier `MobileAppPlan.php` - ajouter `fair_use_monthly_cap` dans $fillable
- [x] Modifier `MobileAppSubscription::canUseAIAnalysis()` - vérifier fair-use cap
- [ ] Modifier `ChatController::sendMessage()` - vérifier limites + incrémenter
- [ ] Modifier `AuthController` (4 endroits) - retourner messages_limit

### Phase 3: Flutter 
- [ ] **Aucun changement nécessaire!** ✅
- [ ] Optionnel: Gérer erreur `FAIR_USE_LIMIT_REACHED`
- [ ] Optionnel: Afficher "300 analyses utilisées ce mois" dans profil

### Phase 4: Admin
- [ ] Optionnel: Ajouter champ fair-use dans formulaire edit plan
- [ ] Documenter comment modifier le fair-use cap en SQL

### Phase 5: Légal & Communication
- [ ] Ajouter clause fair-use dans CGV
- [ ] Email aux clients Cabinet existants expliquant le fair-use
- [ ] Page FAQ: "Qu'est-ce que la limite fair-use?"

---

## 🎯 Résultat Final

**Utilisateur voit dans Flutter:**
```
┌─────────────────────────────────┐
│  Plan: Cabinet/Entreprise       │
│  ────────────────────────────   │
│  Recherches:     125/∞           │
│  Analyses IA:    287/∞           │  ← Affiche illimité!
│  Téléchargements: 45/∞           │
│  Messages/jour:   32/∞           │
└─────────────────────────────────┘
```

**Backend protège:**
```php
if ($subscription->ai_analyses_used >= 300) {
    return 429 FAIR_USE_LIMIT_REACHED;
}
```

**Tout le monde est content:**
- ✅ **Client:** Voit "illimité", se sent premium
- ✅ **Toi:** Coûts contrôlés ($19.20 vs $80+)
- ✅ **Légal:** Fair-use clause dans CGV
- ✅ **Support:** ~1% utilisateurs dépassent → contact personnalisé

---

**C'est exactement ce que tu cherchais!** 🎉

Dis-moi si tu veux que je:
1. Génère les modifications complètes pour AuthController et ChatController
2. Crée le code Flutter pour gérer FAIR_USE_LIMIT_REACHED
3. Rédige l'email à envoyer aux clients Cabinet existants
4. Autre chose?
