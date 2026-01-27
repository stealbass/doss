# 🧪 Guide de Test Complet - Système d'Email Factures

## 📋 Préparation de l'Environnement

### 1. Lancer la Queue
```bash
# Terminal 1
php artisan queue:work --verbose
```

### 2. Suivre les Logs
```bash
# Terminal 2
tail -f storage/logs/laravel.log | grep -E "(Bill|bill)"
```

### 3. Vérifier SMTP (Optionnel - Mailpit)
```bash
# Terminal 3 - Docker
docker run -d --name mailpit -p 1025:1025 -p 8026:8025 axllent/mailpit

# Interface Web : http://localhost:8026
```

### 4. Configuration .env (Pour Mailpit)
```env
MAIL_DRIVER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_NAME="Dossy Pro"
```

---

## 🔍 Scénario de Test Complet

### Test 1 : Facture Simple (Happy Path)

**Préparation** :
- Admin connecté
- Client créé (ID: 2, email: client@test.local)

**Étapes** :

1. **Créer la facture**
   ```
   Menu → Factures → Créer
   - Titre : "Test Email Simple"
   - Numéro : Auto-généré (FAC-001)
   - De : Company
   - À : Client Test (ID 2)
   - Montant HT : 1000€
   - Taxe : 200€ (10%)
   - Remise : 0€
   - Description : "Test pour validation email"
   - Date d'échéance : 2025-02-08
   ```

2. **Cliquer "Créer"**

3. **Vérifier les logs** (5-10 secondes)
   ```
   Look for:
   ✅ SendBillCreatedNotification job started
   ✅ Bill notification - Admin only
   ✅ admin_email: admin@example.com
   ✅ Bill created notification sent to admin
   ```

4. **Vérifier l'email**

   **Option A - Mailpit** :
   - Ouvrir http://localhost:8026
   - Chercher email depuis "Dossy Pro"
   - À : admin@example.com
   - Sujet : "Nouvelle Facture Créée - FAC-001"

   **Option B - Logs** (si MAIL_DRIVER=log)
   ```bash
   grep -A 30 "Bill created notification" storage/logs/laravel.log
   ```

5. **Valider le Contenu**
   - ✅ Header : "💰 Nouvelle Facture Créée"
   - ✅ Numéro : FAC-001 (gras)
   - ✅ Titre : "Test Email Simple"
   - ✅ De : Company
   - ✅ À : Client Test
   - ✅ Date création : Aujourd'hui
   - ✅ Date échéance : 2025-02-08
   - ✅ Sous-total : €1000.00
   - ✅ Taxe : €200.00
   - ✅ Montant Total : €1200.00 (gros, vert)
   - ✅ Bouton "Voir toutes les Factures"

---

### Test 2 : Facture avec Remise

**Facture** :
- Titre : "Test avec Remise"
- Montant HT : 1000€
- Remise : 100€
- Taxe : 180€
- **Montant Total : 1080€**

**Validation** :
- ✅ Section remise affichée : "Remise : -€100.00"
- ✅ Calcul correct : 1000 - 100 + 180 = 1080
- ✅ Montant total : €1080.00

---

### Test 3 : Facture sans Taxe

**Facture** :
- Titre : "Test sans Taxe"
- Montant HT : 500€
- Taxe : 0€
- Remise : 0€
- **Montant Total : 500€**

**Validation** :
- ✅ Section taxe PAS affichée (si = 0)
- ✅ Section remise PAS affichée (si = 0)
- ✅ Affiche seulement sous-total = total

---

### Test 4 : Facture avec Remarques

**Facture** :
- Titre : "Test avec Description"
- Description : "Veuillez payer selon les conditions convenues. Merci."
- Autres champs : Standard

**Validation** :
- ✅ Section "Remarques" affichée
- ✅ Background bleu pâle
- ✅ Texte visible et lisible
- ✅ Formatage correct

---

### Test 5 : Facture d'Avocat

**Facture** :
- De : Avocat (sélectionner un avocat)
- À : Client
- Titre : "Facturation Avocat"

**Validation** :
- ✅ Section "De" affiche le nom de l'avocat
- ✅ Email reçu par admin (creatorId)
- ✅ Tous les détails corrects

---

## 📧 Vérification de l'Email Reçu

### Checklist Template

```
📧 EMAIL REÇU
├─ ✅ Expéditeur : Dossy Pro
├─ ✅ À : admin@example.com
├─ ✅ Sujet : "Nouvelle Facture Créée - FAC-XXX"
│
├─ 📋 HEADER
│  └─ ✅ "💰 Nouvelle Facture Créée"
│
├─ 📝 INFOS FACTURE
│  ├─ ✅ Numéro (gras, 18px)
│  ├─ ✅ Titre
│  ├─ ✅ De (tiers)
│  ├─ ✅ À (client)
│  ├─ ✅ Date création
│  └─ ✅ Date échéance (rouge)
│
├─ 💵 DÉTAILS FINANCIERS
│  ├─ ✅ Sous-total
│  ├─ ✅ Remise (si > 0)
│  ├─ ✅ Taxe (si > 0)
│  └─ ✅ Montant Total (vert, gros)
│
├─ 📍 REMARQUES (si présentes)
│  └─ ✅ Text visible, background bleu
│
├─ ⚠️ ACTION REQUISE
│  └─ ✅ Message d'avertissement affiché
│
├─ 🔘 BOUTON
│  └─ ✅ "Voir toutes les Factures" cliquable
│
└─ 📄 FOOTER
   ├─ ✅ "Ceci est un email automatique"
   ├─ ✅ © Dossy Pro
   └─ ✅ Note de confidentialité
```

---

## 📊 Vérification des Logs

### Log Attendu - Succès

```
[2025-01-08 15:30:45] local.INFO: SendBillCreatedNotification job started
{
  "bill_id": 1,
  "bill_from": "company",
  "bill_to": 2,
  "bill_number": "FAC-001",
  "created_by": 1
}

[2025-01-08 15:30:46] local.INFO: Bill notification - Admin only
{
  "bill_id": 1,
  "admin_email": "admin@example.com",
  "admin_name": "Admin Name",
  "bill_number": "FAC-001",
  "bill_from": "Company",
  "bill_to": "Client Name"
}

[2025-01-08 15:30:47] local.INFO: Bill created notification sent to admin
{
  "bill_id": 1,
  "admin_email": "admin@example.com",
  "bill_number": "FAC-001"
}
```

### Log Attendu - Erreur SMTP

```
[2025-01-08 15:30:47] local.ERROR: Failed to send bill notification email to admin
{
  "bill_id": 1,
  "admin_email": "admin@example.com",
  "error": "Expected response code 220 but got code \"\" with message \"\"",
  "trace": "..."
}
```

**Cause** : Config SMTP invalide, serveur pas accessible

---

## 🔧 Cas de Test Avancés

### Test 6 : Admin sans Email

**Préparation** :
- Admin (ID: 1) sans email configuré

**Résultat Attendu** :
- ✅ Log error : "Bill creator (admin) not found or has no email"
- ✅ Email NOT envoyé
- ✅ Facture créée (notification toujours ok)

---

### Test 7 : Queue Failure

**Simuler une erreur** :
```bash
# Modifier temporairement BillCreatedMail pour causer une erreur
# Puis créer une facture
```

**Validation** :
- ✅ Facture créée
- ✅ Job en erreur dans queue
- ✅ Log error complet avec trace
- ✅ Peut être rejouée : `php artisan queue:retry all`

---

### Test 8 : Facture Duplicata

**Test** :
- Créer 2 factures rapidement
- Vérifier que 2 emails distincts sont envoyés

**Validation** :
- ✅ Email 1 : FAC-001
- ✅ Email 2 : FAC-002
- ✅ Les 2 reçus en quelques secondes

---

## 🐛 Debugging Guide

### Si Email N'est Pas Reçu

**Étape 1 : Vérifier les Logs**
```bash
tail -f storage/logs/laravel.log | grep -A 5 "SendBillCreatedNotification"
```

Chercher :
- ✅ "job started"
- ✅ "sent to admin"
- ❌ "error" ou "ERROR"

**Étape 2 : Vérifier Queue**
```bash
# Jobs échoués
php artisan queue:failed

# Voir un job échoué
php artisan queue:failed

# Rejoueur tous
php artisan queue:retry all
```

**Étape 3 : Tester SMTP Manuelle**
```bash
php artisan tinker

# Tester config
>>> Utility::getSMTPDetails(1);

# Tester envoi
>>> Mail::raw('Test', fn($msg) => $msg->to('admin@example.com'));
>>> // Vérifier Mailpit ou logs
```

**Étape 4 : Vérifier Template**
```bash
# Fichier existe
ls -la resources/views/emails/bill-created.blade.php

# Vérifier syntaxe Blade
php artisan view:clear
php artisan config:clear
```

---

### Si Email est Mal Formaté

**Checklist** :
1. Client email (Gmail, Outlook, etc.) peut changer CSS
2. Vérifier le même email dans Mailpit vs client réel
3. Tester sur mobile
4. Vérifier que toutes les variables sont passées

**Debugging** :
```bash
# Voir le contenu de l'email dans les logs
MAIL_DRIVER=log

# Créer une facture
# Vérifier dans storage/logs/laravel.log
# Chercher "Message from" et copier le HTML
```

---

## ✅ Checklist Finale de Validation

### Configuration
- [ ] Queue worker actif
- [ ] SMTP configuré (MAIL_DRIVER=smtp)
- [ ] Mailpit actif (optionnel)
- [ ] Logs suivis en temps réel

### Code
- [ ] SendBillCreatedNotification.php existe et est correct
- [ ] BillCreatedMail.php existe et est correct
- [ ] bill-created.blade.php existe et est correct
- [ ] BillController.php a getSMTPDetails() + dispatch

### Test Happy Path
- [ ] Créer facture simple
- [ ] Email reçu dans 5-10 secondes
- [ ] Email contient tous les infos
- [ ] Logs montrent succès

### Test Cas Limites
- [ ] Facture avec remise : remise affichée
- [ ] Facture sans taxe : taxe pas affichée
- [ ] Facture avec description : affichée
- [ ] Facture d'avocat : nom correct
- [ ] Admin sans email : error log

### Robustesse
- [ ] Queue failure : job peut être rejouée
- [ ] SMTP error : log complet capturé
- [ ] Template error : page d'erreur grace
- [ ] Multi-tenant : chaque admin sa config

---

## 🎯 Résultats Attendus

Après tests réussis :
- ✅ 1 email reçu par l'admin
- ✅ Uniquement l'admin (pas le client)
- ✅ HTML professionnel et formaté
- ✅ Tous les détails de facture présents
- ✅ Logs complets pour debugging
- ✅ < 10 secondes de délai (avec queue locale)

---

## 📞 Commandes Utiles

```bash
# Queue management
php artisan queue:work --verbose
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush

# Testing
php artisan tinker

# Logs
tail -f storage/logs/laravel.log | grep Bill
grep "SendBillCreatedNotification" storage/logs/laravel.log

# Cache clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

---

**Guide de Test** : ✅ COMPLET
**Date** : 2025-01-08
**Version** : 1.0
