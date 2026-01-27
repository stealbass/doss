# 🔧 Fix: Subscription non activée après paiement

## Problème identifié
Le paiement Flutterwave s'est bien passé (status="successful" dans mobile_app_payments), mais la subscription n'est pas activée dans l'app Flutter (affiche "Free" au lieu du plan acheté).

## Causes possibles
1. ❌ La subscription n'a pas été créée dans `mobile_app_subscriptions`
2. ❌ L'API backend ne retourne pas la bonne structure de données
3. ❌ Le frontend ne recharge pas correctement après le callback

## ✅ Corrections appliquées

### 1. Structure de réponse API corrigée
**Fichier**: `app/Http/Controllers/Api/Mobile/SubscriptionController.php`

L'API `getCurrentSubscription` retourne maintenant:
- `success: true`
- `subscription.plan_name` (requis par le frontend)
- `subscription.end_date`
- `data` (structure complète avec usage)

### 2. Rechargement amélioré après paiement
**Fichier**: `dossy_chat_ia/lib/presentation/screens/payment/flutterwave_payment_screen.dart`

Améliorations:
- ✅ Délai de 2 secondes avant rechargement (laisser le backend finaliser)
- ✅ Appel à `fetchCurrentSubscription(token)` avec await
- ✅ Rechargement des plans avec `loadPlans(token)`
- ✅ Message de confirmation "Abonnement activé avec succès"
- ✅ Navigation différée après rechargement

## 🔍 Diagnostic de la situation actuelle

### Étape 1: Vérifier si la subscription existe

Exécutez ce script PHP:
```bash
php diagnostic_subscription.php
```

Le script va:
1. Vérifier l'utilisateur ID 202
2. Lister tous ses paiements
3. Lister toutes ses subscriptions
4. Vérifier si une subscription active existe

### Étape 2: Si aucune subscription n'existe

**Raison**: Le callback `activateSubscription()` n'a pas été exécuté ou a échoué

**Solution immédiate**: Activer manuellement
```bash
php fix_subscription.php
```

Ce script va:
1. Récupérer le paiement ID 5 (successful)
2. Extraire les infos (plan, billing_cycle)
3. Créer la subscription avec les bonnes dates
4. Vérifier qu'elle est récupérable via l'API

### Étape 3: Vérifier dans phpMyAdmin

**SQL de vérification**:
```sql
-- Vérifier le paiement
SELECT * FROM mobile_app_payments 
WHERE id = 5 AND user_id = 202;

-- Vérifier la subscription
SELECT s.*, p.name as plan_name 
FROM mobile_app_subscriptions s
JOIN mobile_app_plans p ON s.mobile_app_plan_id = p.id
WHERE s.user_id = 202
ORDER BY s.id DESC
LIMIT 1;

-- Vérifier que la subscription est "active"
SELECT 
    id, 
    mobile_app_plan_id,
    status, 
    started_at,
    expires_at,
    CASE 
        WHEN status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) 
        THEN 'VISIBLE API' 
        ELSE 'NON VISIBLE' 
    END as api_visibility
FROM mobile_app_subscriptions
WHERE user_id = 202;
```

## 🚀 Test après correction

### Dans l'app Flutter:

1. **Déconnexion/Reconnexion**:
   - Se déconnecter de l'app
   - Se reconnecter
   - Vérifier que le plan s'affiche

2. **Force refresh**:
   - Aller dans Paramètres → Mon compte
   - Tirer pour actualiser (pull-to-refresh)
   - Vérifier le nom du plan

3. **Test API direct**:
   ```bash
   # Récupérer le token de l'utilisateur
   curl -X GET "https://threesixty.alwaysdata.net/api/mobile/subscription/current" \
     -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
     -H "Accept: application/json"
   ```

   Réponse attendue:
   ```json
   {
     "success": true,
     "subscription": {
       "plan_name": "DOSSY PRO",
       "status": "active",
       "end_date": "2026-02-04"
     }
   }
   ```

## 🔄 Pour éviter le problème à l'avenir

### Vérifier les logs Laravel

Après un paiement, vérifier:
```bash
tail -f storage/logs/laravel.log | grep -i "subscription\|payment"
```

Logs attendus:
1. ✅ "Flutterwave callback received"
2. ✅ "Flutterwave verification response"
3. ✅ "Payment completed and subscription activated"

### Si le callback ne s'exécute pas

**Cause**: La route de callback Flutterwave n'est pas accessible

**Vérification**:
```bash
# Tester l'accessibilité de la route callback
curl -X GET "https://threesixty.alwaysdata.net/mobile/payment/callback?tx_ref=DOSSY-MOBILE-202-1767551013-9247&status=successful"
```

Devrait retourner un redirect vers `dossychatia://...`

## 📝 Résumé des changements

### Backend (Laravel)
- ✅ `SubscriptionController::getCurrentSubscription()` - Structure de réponse corrigée
- ✅ Retourne maintenant `subscription.plan_name` et `subscription.end_date`

### Frontend (Flutter)  
- ✅ `FlutterwavePaymentScreen::_handlePaymentSuccess()` - Rechargement amélioré
- ✅ Ajout d'un délai de 2 secondes avant rechargement
- ✅ Rechargement des plans + subscription
- ✅ Message de confirmation utilisateur

### Scripts de diagnostic
- ✅ `diagnostic_subscription.php` - Diagnostic complet
- ✅ `fix_subscription.php` - Correction manuelle immédiate

## 🎯 Actions à faire MAINTENANT

1. **Exécutez le fix immédiat**:
   ```bash
   cd /path/to/project
   php fix_subscription.php
   ```

2. **Uploadez les fichiers modifiés**:
   - `app/Http/Controllers/Api/Mobile/SubscriptionController.php`
   - `dossy_chat_ia/lib/presentation/screens/payment/flutterwave_payment_screen.dart`

3. **Recompilez l'app Flutter**:
   ```bash
   cd dossy_chat_ia
   flutter clean
   flutter pub get
   flutter build apk --release
   ```

4. **Testez avec l'utilisateur ID 202**:
   - Demandez-lui de se déconnecter/reconnecter
   - Le plan "DOSSY PRO" devrait s'afficher

## ✅ Confirmation du fix

Une fois le fix appliqué, vous devriez voir dans l'app:
- ❌ "Gratuit" ou "Free" → ✅ "DOSSY PRO" (ou nom du plan acheté)
- ❌ Limitations gratuites → ✅ Quotas du plan payant
- ❌ Bouton "S'abonner" → ✅ Bouton "Gérer mon abonnement"
