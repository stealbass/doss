# 🧪 GUIDE DE TEST INTERACTIF - PARRAINAGE

## 📋 TEST MANUEL COMPLET

### Prérequis:
- [ ] 2 comptes test disponibles (ou à créer)
- [ ] Accès à l'API
- [ ] Accès à la base de données (pour vérification)

---

## 🔄 SCÉNARIO DE TEST

### Phase 1: Création des comptes

```bash
# Compte 1: Parrain
POST /api/auth/register
{
  "name": "Alice Parrain",
  "email": "alice@test.dossy.fr",
  "password": "Test@1234",
  "password_confirmation": "Test@1234",
  "phone": "+221771111111"
}

Résultat attendu:
{
  "success": true,
  "user": {...},
  "token": "xxx"
}

Sauvegardez: ALICE_TOKEN = "xxx"
```

```bash
# Compte 2: Premier filleul
POST /api/auth/register
{
  "name": "Bob Filleul",
  "email": "bob@test.dossy.fr",
  "password": "Test@1234",
  "password_confirmation": "Test@1234",
  "phone": "+221771111112"
}

Résultat attendu:
{
  "success": true,
  "user": {...},
  "token": "yyy"
}

Sauvegardez: BOB_TOKEN = "yyy"
```

---

### Phase 2: Obtenir le code de parrainage d'Alice

```bash
GET /api/referral/code
Headers: Authorization: Bearer {ALICE_TOKEN}

Résultat attendu:
{
  "success": true,
  "data": {
    "referral_code": "ABC12XYZ",        # Copier ce code
    "total_referrals": 0,
    "rewards_earned": 0,
    "next_reward_at": 10,
    "progress_to_next": 0
  }
}

Sauvegardez: ALICE_CODE = "ABC12XYZ"
```

---

### Phase 3: Créer des filleuls avec le code d'Alice

**Créez 10 filleuls supplémentaires** (pour atteindre le seuil de récompense)

```bash
# Filleul #2 à #11 (boucle)
for i in {2..11}:
  POST /api/auth/register
  {
    "name": "Filleul $i",
    "email": "filleul$i@test.dossy.fr",
    "password": "Test@1234",
    "password_confirmation": "Test@1234",
    "phone": "+2217711111$i",
    "referral_code": "{ALICE_CODE}"     # ✅ IMPORTANT: Utiliser le code d'Alice
  }

  Résultat attendu:
  - Referral créé avec status='pending'
  - referrer_user_id = Alice's ID
  - referred_user_id = Filleul $i
```

---

### Phase 4: Valider les parrainages (Phase Critique) ⚠️

Cette phase teste la correction apportée !

#### 4.1 Premier filleul (Bob) - Activation de souscription

```bash
# Étape A: Vérifier le statut AVANT
SELECT * FROM referrals 
WHERE referred_user_id = {BOB_ID} 
AND referrer_user_id = {ALICE_ID};

Résultat attendu:
- status = 'pending'
- completed_at = NULL

# Étape B: Simuler un paiement
POST /api/payment/initiate
Headers: Authorization: Bearer {BOB_TOKEN}
{
  "plan_id": 1,
  "payment_method": "flutterwave",
  "billing_cycle": "monthly",
  "amount": 5000
}

Résultat attendu:
{
  "success": true,
  "payment": {
    "id": {PAYMENT_ID},
    "status": "pending",
    ...
  }
}

# Étape C: Activer la souscription (simule le callback de paiement)
POST /api/subscription/activate
Headers: Authorization: Bearer {BOB_TOKEN}
{
  "payment_id": {PAYMENT_ID},
  "transaction_id": "flw_test_12345"
}

Résultat attendu:
{
  "success": true,
  "message": "Subscription activated successfully",
  "data": {
    "subscription_id": {SUB_ID},
    "status": "active",
    "start_date": "2026-01-05",
    "end_date": "2026-02-05"
  }
}

# Étape D: Vérifier le STATUT APRÈS ✅ TEST CRITIQUE
SELECT * FROM referrals 
WHERE referred_user_id = {BOB_ID} 
AND referrer_user_id = {ALICE_ID};

Résultat ATTENDU (AVEC CORRECTION):
✅ status = 'completed'        # ÉTAIT 'pending' AVANT
✅ completed_at = '2026-01-05 XX:XX:XX'  # MAINTENANT DÉFINI
```

#### 4.2 Autres filleuls (Filleuls #2 à #11)

Répétez l'étape 4.1 pour chaque filleul restant.

**Attention**: Arrêtez après le 10ème filleul ! On va vérifier que les récompenses se déclenchent.

---

### Phase 5: Vérifier les récompenses (Après 10 parrainages)

```bash
# Vérifier le nombre de parrainages complétés d'Alice
SELECT COUNT(*) as completed_count 
FROM referrals 
WHERE referrer_user_id = {ALICE_ID} 
AND status = 'completed';

Résultat attendu:
- completed_count = 10

# Vérifier les récompenses d'Alice
SELECT * FROM referral_rewards 
WHERE user_id = {ALICE_ID}
ORDER BY created_at DESC;

Résultat ATTENDU:
✅ 1 reward avec:
   - reward_type = 'free_month'
   - value = 1
   - status = 'redeemed'
   - redeemed_at = 2026-01-05 XX:XX:XX
   - mobile_app_subscription_id = {ALICE_SUB_ID}

# Vérifier l'extension de l'abonnement d'Alice
SELECT id, expires_at FROM mobile_app_subscriptions 
WHERE user_id = {ALICE_ID}
ORDER BY expires_at DESC
LIMIT 1;

Résultat ATTENDU:
✅ expires_at allongée de +1 mois
   Avant: 2026-02-05
   Après: 2026-03-05
```

---

### Phase 6: Consulter l'historique dans l'app

```bash
# Endpoint appelé par le Frontend
GET /api/referral/history
Headers: Authorization: Bearer {ALICE_TOKEN}

Résultat ATTENDU:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "referred_user": {
        "name": "Bob Filleul",
        "email": "bob@test.dossy.fr"
      },
      "status": "completed",              ✅ IMPORTANT
      "created_at": "2026-01-01 10:00:00",
      "completed_at": "2026-01-05 14:30:00" ✅ IMPORTANT
    },
    {
      "id": 2,
      "referred_user": {
        "name": "Filleul 2",
        "email": "filleul2@test.dossy.fr"
      },
      "status": "completed",              ✅ IMPORTANT
      "created_at": "2026-01-01 10:01:00",
      "completed_at": "2026-01-05 14:31:00" ✅ IMPORTANT
    },
    ... # 10 au total
  ]
}
```

---

### Phase 7: Vérifier les statistiques dans l'app

```bash
# Endpoint appelé par le Frontend
GET /api/referral/code
Headers: Authorization: Bearer {ALICE_TOKEN}

Résultat ATTENDU:
{
  "success": true,
  "data": {
    "referral_code": "ABC12XYZ",
    "total_referrals": 10,              ✅ Augmenté de 0 à 10
    "rewards_earned": 1,                 ✅ 10 / 10 = 1
    "next_reward_at": 20,                ✅ Prochaine récompense à 20
    "progress_to_next": 0,               ✅ 10 % 10 = 0 (progression: 0/10)
    "share_message": "..."
  }
}
```

---

## 🔍 VÉRIFICATIONS SUPPLÉMENTAIRES

### Checkpoints critiques:

1. **Après activitation d'une souscription:**
   ```sql
   -- Doit voir: status = 'completed'
   SELECT * FROM referrals WHERE status='completed' LIMIT 5;
   ```

2. **Statut pending (ne doit pas devenir pending automatiquement):**
   ```sql
   -- Doit voir: VIDE ou très peu
   SELECT * FROM referrals WHERE referred_user_id IN (
     SELECT id FROM users WHERE email LIKE 'filleul%@test.dossy.fr'
   ) AND status='pending';
   ```

3. **Récompenses appliquées:**
   ```sql
   -- Après 10 parrainages: 1 récompense redeemed
   SELECT user_id, COUNT(*) as reward_count
   FROM referral_rewards 
   WHERE status='redeemed'
   GROUP BY user_id
   HAVING reward_count > 0;
   ```

4. **Extension de l'abonnement:**
   ```sql
   -- L'abonnement du parrain doit être étendu
   SELECT u.name, s.expires_at 
   FROM mobile_app_subscriptions s
   JOIN users u ON u.id = s.user_id
   WHERE u.email = 'alice@test.dossy.fr'
   ORDER BY s.expires_at DESC;
   ```

---

## 📊 MATRICE DE TEST

| # | Test | Pré-requis | Action | Résultat Attendu | Status |
|---|------|-----------|--------|------------------|--------|
| 1 | Code générée | Alice inscrite | GET /referral/code | Code unique | ⬜ |
| 2 | Code valide | Code d'Alice | GET /referral/validate | valid=true | ⬜ |
| 3 | Referral créé | Bob inscrit + code Alice | SELECT referrals | status='pending' | ⬜ |
| 4 | Referral complété | Bob souscription activée | SELECT referrals | status='completed' ✅ | ⬜ |
| 5 | Récompense gagnée | 10 parrainages complétés | SELECT referral_rewards | 1 récompense | ⬜ |
| 6 | Récompense appliquée | Récompense créée | SELECT mobile_app_subscriptions | expires_at +1 mois | ⬜ |
| 7 | Historique correct | Alice consulte | GET /referral/history | 10 'completed' | ⬜ |
| 8 | Stats correctes | Alice consulte | GET /referral/code | total_referrals=10 | ⬜ |
| 9 | Frontend affiche | Bob affiché dans historique | App Flutter | Badge "Terminé" vert | ⬜ |
| 10 | Récompense visible | Alice consulte | GET /referral/rewards | 1 mois gratuit | ⬜ |

---

## 🐛 DÉBOGAGE

### Si le test échoue à l'étape 4.2 (status reste 'pending'):

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log | grep -i referral

# Vérifier que completeReferral est appelé
grep -n "completeReferral" app/Http/Controllers/Api/Mobile/SubscriptionController.php

# Vérifier la méthode existe
grep -n "public static function completeReferral" app/Http/Controllers/Api/Mobile/ReferralController.php
```

### Si récompense non appliquée:

```bash
# Vérifier les logs de récompense
tail -f storage/logs/laravel.log | grep -i "reward"

# Vérifier manuellement
php artisan tinker
> ReferralController::grantRewardIfEligible({ALICE_ID})
```

### Si données inconsistentes:

```bash
# Nettoyer les données de test
DELETE FROM referrals WHERE referred_user_id IN (
  SELECT id FROM users WHERE email LIKE '%@test.dossy.fr'
);
DELETE FROM users WHERE email LIKE '%@test.dossy.fr';
DELETE FROM referral_rewards WHERE user_id IN (
  SELECT id FROM users WHERE email LIKE 'alice@test.dossy.fr'
);
```

---

## ✅ CHECKLIST FINALE

Avant de déployer en production:

- [ ] Test 1: Code généré pour Alice
- [ ] Test 2: Code validé correctement
- [ ] Test 3: Referral créé avec status='pending'
- [ ] Test 4: Referral devient 'completed' APRÈS souscription
- [ ] Test 5: Récompense créée après 10 parrainages
- [ ] Test 6: Récompense appliquée à l'abonnement d'Alice
- [ ] Test 7: Historique affiche 'completed' pour les 10 filleuls
- [ ] Test 8: Statistiques correctes (total_referrals=10, rewards_earned=1)
- [ ] Test 9: Frontend affiche les badges corrects
- [ ] Test 10: Récompense visible dans l'onglet "Récompenses"
- [ ] Test 11: Créer 11ème filleul et vérifier pas de 2ème récompense (20 required)
- [ ] Test 12: Suppression filleul ne casse rien

---

## 📝 RAPPORT DE TEST

À remplir et conserver:

```
Date: 2026-01-05
Testeur: [NOM]
Environnement: [DEV/STAGING/PROD]

Tests réussis: __/12
Tests échoués: __/12

Bugs trouvés:
1. ____________________________
2. ____________________________

Observations:
- __________________________
- __________________________

Signature: ____________________________
```
