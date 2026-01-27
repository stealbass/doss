# Configuration des Plans - Valeurs Exactes

## 📋 À Configurer dans l'Admin "Plan Limits"

### Plan Gratuit (Free)
```
Searches:      5
Analyses:      2
Downloads:     3
Messages/Day: 10  ← À AJOUTER via SQL
```

### Plan Étudiant
```
Searches:     50
Analyses:     20
Downloads:    30
Messages/Day: 100 ← À AJOUTER via SQL
```

### Plan Professionnel (Pro)
```
Searches:     200
Analyses:     100
Downloads:    150
Messages/Day: 500 ← À AJOUTER via SQL
```

### Plan Cabinet/Entreprise
```
Searches:      -1 (Illimité)
Analyses:     300 ← CHANGER de -1 à 300
Downloads:     -1 (Illimité)
Messages/Day:  -1 (Illimité) ← À AJOUTER via SQL
```

---

## 🔧 Valeurs Cachées (Max Tokens & AI Model)

Ces valeurs ne sont PAS visibles dans l'interface admin "Plan Limits".  
**Tu dois les modifier via SQL:**

```sql
-- Plan Gratuit
UPDATE mobile_app_plans 
SET max_tokens = 1000, ai_model = 'gpt-3.5-turbo' 
WHERE name = 'free';

-- Plan Étudiant
UPDATE mobile_app_plans 
SET max_tokens = 4000, ai_model = 'gpt-3.5-turbo' 
WHERE name = 'student';

-- Plan Pro (CRITIQUE: changer GPT-4 → GPT-3.5)
UPDATE mobile_app_plans 
SET max_tokens = 8000, ai_model = 'gpt-3.5-turbo' 
WHERE name = 'pro';

-- Plan Cabinet
UPDATE mobile_app_plans 
SET max_tokens = 16000, ai_model = 'gpt-4-turbo' 
WHERE name = 'cabinet';
```

---

## ⚠️ Pourquoi Ces Valeurs?

### Max Tokens
- **Gratuit:** 1000 tokens = ~750 mots (conversation courte)
- **Étudiant:** 4000 tokens = ~3000 mots (document moyen)
- **Pro:** 8000 tokens = ~6000 mots (long document)
- **Cabinet:** 16000 tokens = ~12000 mots (très long)

### AI Model
- **GPT-3.5-Turbo:** Rapide, économique, bonne qualité
- **GPT-4-Turbo:** Meilleure qualité, plus cher

### Pourquoi Pro utilise GPT-3.5 au lieu de GPT-4?
**Avec GPT-4:**
- Coût: $18/mois par utilisateur
- Prix: $8.25/mois
- **PERTE: -$9.75** ❌

**Avec GPT-3.5:**
- Coût: $0.70/mois par utilisateur  
- Prix: $8.25/mois
- **PROFIT: +$7.55** ✅

Pour les clients qui veulent GPT-4, ils peuvent upgrader vers Cabinet.

---

## 📊 Récapitulatif Final

| Plan | Searches | Analyses | Downloads | Messages/Day | Max Tokens | AI Model | Prix CFA |
|------|----------|----------|-----------|--------------|------------|----------|----------|
| **Gratuit** | 5 | 2 | 3 | 10 | 1000 | GPT-3.5 | 0 |
| **Étudiant** | 50 | 20 | 30 | 100 | 4000 | GPT-3.5 | 2000 |
| **Pro** | 200 | 100 | 150 | 500 | 8000 | GPT-3.5 | 5000 |
| **Cabinet** | -1 | 300* | -1 | -1 | 16000 | GPT-4 Turbo | 20000 |

*Fair-use cap (affiché comme illimité)

---

## ✅ Script SQL Complet (Copier-Coller)

```sql
-- 1. AJOUTER COLONNES
ALTER TABLE mobile_app_plans 
ADD COLUMN IF NOT EXISTS messages_per_day_limit INT DEFAULT 50;

ALTER TABLE mobile_app_subscriptions 
ADD COLUMN IF NOT EXISTS messages_sent_today INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS messages_last_reset_date DATE DEFAULT NULL;

-- 2. CONFIGURER PLAN GRATUIT
UPDATE mobile_app_plans 
SET 
    searches_limit = 5,
    ai_analyses_limit = 2,
    pdf_downloads_limit = 3,
    messages_per_day_limit = 10,
    max_tokens = 1000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'free';

-- 3. CONFIGURER PLAN ÉTUDIANT
UPDATE mobile_app_plans 
SET 
    searches_limit = 50,
    ai_analyses_limit = 20,
    pdf_downloads_limit = 30,
    messages_per_day_limit = 100,
    max_tokens = 4000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'student';

-- 4. CONFIGURER PLAN PRO (CHANGEMENTS CRITIQUES)
UPDATE mobile_app_plans 
SET 
    searches_limit = 200,
    ai_analyses_limit = 100,
    pdf_downloads_limit = 150,
    messages_per_day_limit = 500,
    max_tokens = 8000,
    ai_model = 'gpt-3.5-turbo'
WHERE name = 'pro';

-- 5. CONFIGURER PLAN CABINET
UPDATE mobile_app_plans 
SET 
    searches_limit = -1,
    ai_analyses_limit = 300,
    pdf_downloads_limit = -1,
    messages_per_day_limit = -1,
    max_tokens = 16000,
    ai_model = 'gpt-4-turbo'
WHERE name = 'cabinet';

-- 6. VÉRIFICATION
SELECT 
    name_fr AS 'Plan',
    searches_limit AS 'Searches',
    ai_analyses_limit AS 'Analyses',
    pdf_downloads_limit AS 'Downloads',
    messages_per_day_limit AS 'Messages',
    max_tokens AS 'Tokens',
    ai_model AS 'Model',
    price_monthly AS 'Prix'
FROM mobile_app_plans
ORDER BY price_monthly ASC;
```

---

## 💡 Notes Importantes

1. **Messages/Day** n'est pas encore implémenté dans le backend/Flutter, mais le SQL prépare la base de données

2. **Plan Pro**: Le changement GPT-4 → GPT-3.5 est CRITIQUE pour la rentabilité

3. **Plan Cabinet**: Le cap de 300 analyses est un "fair-use" pour protéger tes marges

4. **Tous les prix** restent identiques (2000, 5000, 20000 CFA) - seules les limites changent

5. Après le SQL, il faudra:
   - Modifier AuthController.php (retourner messages_limit)
   - Modifier ChatController.php (vérifier limite)
   - Modifier Flutter UserModel (ajouter messagesUsed/Limit)

---

**Questions?** Dis-moi si tu veux:
- Le guide pas-à-pas pour exécuter le SQL
- Les modifications Backend en détail
- Les modifications Flutter complètes
- Un script de test
