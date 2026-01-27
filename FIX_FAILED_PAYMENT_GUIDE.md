# 🔧 Guide de correction des paiements "Failed"

## Problème
Un paiement Flutterwave réussi est marqué comme "Failed" dans l'admin, et l'abonnement n'est pas activé.

## Cause identifiée
L'ancienne API Flutterwave (Ravepay v2) utilisée pour la vérification des paiements est **dépréciée** et peut échouer:
```php
// ❌ Ancienne API (dépréciée)
https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify
```

## Solution 1: Correction manuelle d'un paiement spécifique

### Étape 1: Identifier le transaction_id
Depuis l'admin, dans **Mobile Users → Voir utilisateur → Payment History**, notez le `TRANSACTION ID` du paiement marqué "Failed".

Exemple: `952843682`

### Étape 2: Exécuter le script de correction
```bash
php fix_failed_payment.php
```

Le script va:
1. ✅ Trouver le paiement avec le transaction_id `952843682`
2. ✅ Mettre à jour le statut: `failed` → `successful`
3. ✅ Créer/activer l'abonnement pour l'utilisateur
4. ✅ Configurer les quotas et la date d'expiration

### Résultat attendu
```
=== CORRECTION PAIEMENT FAILED ===

Recherche du paiement avec transaction_id: 952843682
✅ Paiement trouvé:
  - ID: 5
  - User ID: 202
  - Plan ID: 1
  - Amount: 1000 XAF
  - Status actuel: failed

🔧 Correction du statut du paiement...
✅ Statut mis à jour: successful

🔧 Activation de l'abonnement...
  Plan: test

✅ Abonnement activé avec succès!
  - Subscription ID: 1
  - User ID: 202
  - Plan: test
  - Status: active
  - Started: 2026-01-04 23:55:00
  - Expires: 2026-02-04 23:55:00

🎉 Correction terminée!
```

## Solution 2: Mise à jour de l'API Flutterwave (permanent)

### Fichier modifié
`app/Http/Controllers/Api/Mobile/PaymentController.php`

### Ancien code (lignes 164-181)
```php
// ❌ Utilise l'ancienne API Ravepay v2
$data = [
    'txref' => $txRef,
    'SECKEY' => $settings->flutterwave_secret_key,
];

$headers = ['Content-Type' => 'application/json'];
$body = \Unirest\Request\Body::json($data);
$url = "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify";

$response = \Unirest\Request::post($url, $headers, $body);

if (!empty($response)) {
    $response = json_decode($response->raw_body, true);
}

if (isset($response['status']) && $response['status'] == 'success') {
    $paydata = $response['data'];
```

### Nouveau code (corrigé)
```php
// ✅ Utilise la nouvelle API Flutterwave v3
$url = "https://api.flutterwave.com/v3/transactions/{$transactionId}/verify";

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $settings->flutterwave_secret_key,
    'Content-Type' => 'application/json',
])->get($url);

$responseData = $response->json();

Log::info('Flutterwave verification response', ['response' => $responseData]);

// Check if verification was successful
if ($responseData['status'] === 'success' && 
    isset($responseData['data']) && 
    $responseData['data']['status'] === 'successful' &&
    $responseData['data']['amount'] >= $payment->amount &&
    $responseData['data']['currency'] === $payment->currency) {
    
    $paydata = $responseData['data'];
```

### Améliorations apportées
1. ✅ Utilise l'API v3 de Flutterwave (supportée)
2. ✅ Utilise Laravel HTTP client au lieu de Unirest
3. ✅ Vérifie le montant et la devise pour éviter les fraudes
4. ✅ Meilleure gestion des erreurs

## Solution 3: Vérification dans les logs

Pour diagnostiquer pourquoi un callback a échoué:

```bash
tail -f storage/logs/laravel.log | grep "Flutterwave"
```

Recherchez:
- `Flutterwave callback received` - Le callback est arrivé
- `Flutterwave verification response` - La réponse de l'API
- `Payment completed and subscription activated` - Succès
- `Payment verification failed` - Échec

## Prévention future

### 1. Uploader le fichier corrigé
Uploadez `PaymentController.php` avec la nouvelle API v3

### 2. Tester avec un nouveau paiement
1. Faites un nouveau paiement test (1000 XAF par exemple)
2. Vérifiez dans les logs: `tail -f storage/logs/laravel.log`
3. Vérifiez dans l'admin que le statut est "Successful" (pas "Failed")
4. Vérifiez que l'abonnement est activé automatiquement

### 3. Configuration Flutterwave requise
Dans **Admin → Mobile App → Settings**, assurez-vous d'avoir:
- ✅ Flutterwave Public Key (commence par `FLWPUBK-`)
- ✅ Flutterwave Secret Key (commence par `FLWSECK-`)
- ✅ Mode: `test` ou `live` selon l'environnement

## FAQ

### Q: Pourquoi le paiement est marqué "Failed" alors que Flutterwave dit "Successful"?
**R:** L'ancienne API Ravepay v2 est dépréciée. La vérification échoue même si le paiement a réussi côté Flutterwave.

### Q: Est-ce que je dois corriger tous les anciens paiements "Failed"?
**R:** Non, corrigez seulement ceux pour lesquels l'utilisateur a vraiment payé. Vérifiez sur le dashboard Flutterwave.

### Q: L'utilisateur peut-il utiliser l'app pendant que c'est marqué "Failed"?
**R:** Non, car l'abonnement n'est pas activé. Il faut corriger le paiement pour activer l'abonnement.

### Q: Le script peut-il être exécuté plusieurs fois?
**R:** Oui, le script est idempotent. Si l'abonnement est déjà actif, il ne fait rien.

### Q: Que se passe-t-il si le plan n'existe plus?
**R:** Le script affichera une erreur. Vous devrez créer le plan ou assigner un autre plan manuellement.

## Vérification après correction

### 1. Dans l'admin (Mobile Users → Voir utilisateur 202)
- ✅ Payment History: Status doit être "Successful" (vert)
- ✅ Subscription History: Status "Active" (vert)
- ✅ Subscription expires_at: Date dans le futur

### 2. Dans l'app mobile
- ✅ Profile → Plan actuel: Affiche le nom du plan payé
- ✅ Profile → Quotas: Affiche les limites du plan
- ✅ Fonctionnalités: Débloquées selon le plan

### 3. Dans la base de données
```sql
-- Vérifier le paiement
SELECT id, user_id, amount, status, paid_at, transaction_id 
FROM mobile_app_payments 
WHERE transaction_id = '952843682';

-- Vérifier l'abonnement
SELECT id, user_id, mobile_app_plan_id, status, started_at, expires_at 
FROM mobile_app_subscriptions 
WHERE user_id = 202;
```

## Checklist de déploiement

- [ ] Uploader `PaymentController.php` avec la nouvelle API v3
- [ ] Uploader `fix_failed_payment.php` (pour corrections manuelles)
- [ ] Tester un nouveau paiement de bout en bout
- [ ] Vérifier les logs: `tail -f storage/logs/laravel.log`
- [ ] Corriger les anciens paiements "Failed" valides avec le script
- [ ] Vérifier que les abonnements sont bien activés
- [ ] Tester dans l'app mobile (affichage du plan)

## Support

Si le problème persiste après la mise à jour:
1. Vérifiez les logs Laravel: `storage/logs/laravel.log`
2. Vérifiez que la Secret Key Flutterwave est correcte
3. Testez l'API manuellement:
```bash
curl -X GET \
  https://api.flutterwave.com/v3/transactions/952843682/verify \
  -H 'Authorization: Bearer FLWSECK-xxxxx'
```
