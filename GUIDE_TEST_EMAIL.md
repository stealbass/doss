# 🧪 Guide de Test - Envoi d'Email de Facture

Ce guide vous aidera à tester et valider la fonctionnalité d'envoi d'email pour les factures.

---

## 🚀 Étape 1: Déploiement

### 1.1 Pousser les Corrections vers GitHub

**Option A: Utiliser le Script Automatique**
```bash
cd /home/user/webapp
./push_email_fixes.sh
```

**Option B: Push Manuel**
```bash
cd /home/user/webapp
git push origin genspark_ai_developer
```

### 1.2 Vérifier le Pull Request

1. Visiter: https://github.com/stealbass/doss/pull/7
2. Vérifier que vous voyez les commits:
   - `fix: Correction de l'envoi d'email - Ajout gestion AJAX et messages de retour`
   - `refactor: Envoi de facture par email avec détails complets (sans PDF)`

### 1.3 Merger le Pull Request

1. Cliquer sur "Merge pull request"
2. Confirmer le merge
3. Déployer sur votre serveur si nécessaire

---

## 🧪 Étape 2: Tests Fonctionnels

### Test 1: Vérifier que le Bouton Apparaît

**Actions:**
1. Se connecter à l'application
2. Naviguer vers "Factures" ou "Bills"
3. Cliquer sur une facture existante pour voir les détails

**Résultat Attendu:**
- ✅ Un bouton avec une icône d'enveloppe (📧) est visible
- ✅ Le survol affiche "Send by Email"

**En cas d'échec:**
- Vérifier que le fichier `resources/views/bills/show.blade.php` contient le bouton
- Vider le cache: `php artisan view:clear`

---

### Test 2: Ouvrir le Formulaire d'Envoi

**Actions:**
1. Cliquer sur le bouton d'envoi d'email

**Résultat Attendu:**
- ✅ Un modal s'ouvre avec le titre "Send Bill by Email"
- ✅ Le formulaire contient 3 champs:
  - Email du destinataire (pré-rempli avec l'email du client)
  - Objet de l'email (pré-rempli avec "Facture #[numéro]")
  - Message (texte par défaut)
- ✅ Un message info indique que les détails seront inclus
- ✅ Deux boutons: "Annuler" et "Envoyer"

**En cas d'échec:**
- Ouvrir la console du navigateur (F12)
- Rechercher des erreurs JavaScript
- Vérifier que la route existe: `/bill/{id}/send-email`

---

### Test 3: Envoyer un Email avec Succès

**Pré-requis:**
- Configuration SMTP correcte dans "Paramètres d'e-mail"
- Facture avec un client ayant un email valide

**Actions:**
1. Ouvrir le formulaire d'envoi
2. Vérifier/modifier l'email du destinataire
3. Modifier le message si souhaité
4. Cliquer sur "Envoyer"

**Résultat Attendu - Pendant l'Envoi:**
- ✅ Le bouton "Envoyer" devient désactivé
- ✅ Le texte change pour "Envoi en cours..."
- ✅ Un spinner est visible à côté du texte

**Résultat Attendu - Après l'Envoi:**
- ✅ Le modal se ferme automatiquement (après ~1 seconde)
- ✅ Un toast vert de succès apparaît en haut à droite
- ✅ Le toast contient: "Bill sent successfully to [email]"

**En cas d'échec:**
- Voir la section "Diagnostic des Problèmes" ci-dessous

---

### Test 4: Vérifier la Réception de l'Email

**Actions:**
1. Ouvrir la boîte de réception du destinataire
2. Chercher l'email (vérifier aussi les spams)

**Résultat Attendu:**
- ✅ Email reçu avec le sujet "Facture #[numéro]"
- ✅ L'email contient:
  - En-tête avec "FACTURE" et le numéro
  - Le message personnalisé
  - Section "Facturé par" (entreprise ou avocat)
  - Section "Facturé à" (client)
  - Tableau complet des articles avec:
    * Description
    * Quantité
    * Prix unitaire
    * Remise
    * Taxe
    * Montant
  - Totaux:
    * Sous-total
    * Total Taxe
    * Total Remise
    * MONTANT TOTAL (en vert)
    * Montant Dû (en rouge)

**Exemple de Structure Attendue:**
```
┌─────────────────────────────────────┐
│         FACTURE                     │
│         #FACT-001                   │
├─────────────────────────────────────┤
│ [Message personnalisé]              │
├─────────────────────────────────────┤
│ Facturé par:        Facturé à:      │
│ Cabinet XYZ         Client ABC      │
│ [adresse]           [email]         │
│                     [adresse]       │
├─────────────────────────────────────┤
│ # │ Desc │ Qté │ Prix │ ... │ Total│
│ 1 │ ...  │ ... │ ...  │ ... │ ...  │
├─────────────────────────────────────┤
│ Sous-total: 100,000 FCFA            │
│ Total Taxe: 18,000 FCFA             │
│ Total Remise: 5,000 FCFA            │
│ MONTANT TOTAL: 113,000 FCFA         │
│ Montant Dû: 113,000 FCFA            │
└─────────────────────────────────────┘
```

---

### Test 5: Gestion des Erreurs

**Test 5A: Email Invalide**

**Actions:**
1. Ouvrir le formulaire
2. Entrer un email invalide (ex: "test")
3. Cliquer sur "Envoyer"

**Résultat Attendu:**
- ✅ Toast rouge d'erreur apparaît
- ✅ Message: "The email must be a valid email address"
- ✅ Le modal reste ouvert
- ✅ Le bouton redevient actif

---

**Test 5B: Champ Email Vide**

**Actions:**
1. Ouvrir le formulaire
2. Vider le champ email
3. Cliquer sur "Envoyer"

**Résultat Attendu:**
- ✅ Toast rouge d'erreur apparaît
- ✅ Message d'erreur de validation
- ✅ Le modal reste ouvert

---

**Test 5C: Configuration SMTP Incorrecte**

**Actions:**
1. Modifier temporairement les paramètres SMTP pour les rendre incorrects
2. Essayer d'envoyer un email

**Résultat Attendu:**
- ✅ Toast rouge d'erreur apparaît
- ✅ Message: "Failed to send email. Please check email configuration."
- ✅ Entrée dans les logs avec détails de l'erreur

---

## 🔍 Étape 3: Diagnostic des Problèmes

### Problème: Pas de Message de Succès/Erreur

**Diagnostic:**
```javascript
// Ouvrir la console du navigateur (F12)
// Onglet Console
// Rechercher des erreurs JavaScript
```

**Vérifications:**
1. La fonction `show_toastr()` existe-t-elle?
   - Chercher dans les fichiers JavaScript de base
2. Y a-t-il des erreurs 404 pour des fichiers JS?
3. La réponse AJAX est-elle reçue?
   - Onglet Network > chercher la requête POST
   - Voir la réponse (devrait être JSON)

**Solution:**
- Si `show_toastr()` manque, vérifier que les fichiers JS de base sont chargés
- Vider le cache du navigateur
- Vérifier la console pour identifier l'erreur exacte

---

### Problème: Email Non Reçu mais "Succès" Affiché

**Diagnostic:**
```bash
# Consulter les logs Laravel
tail -100 storage/logs/laravel.log

# Rechercher spécifiquement les logs d'email
grep "email facture" storage/logs/laravel.log

# Voir les derniers logs en temps réel
tail -f storage/logs/laravel.log
```

**Ce que vous devriez voir dans les logs:**
```
[2025-11-16 13:00:00] local.INFO: Tentative envoi email facture {"to":"client@example.com","subject":"Facture #FACT-001","bill_id":1}
[2025-11-16 13:00:01] local.INFO: Email facture envoyé avec succès {"to":"client@example.com"}
```

**Si vous voyez "envoyé avec succès" mais pas d'email reçu:**

**Causes Possibles:**
1. **Email bloqué par le serveur SMTP**
   - Vérifier l'adresse "From" est valide et vérifiée
   - Vérifier les quotas d'envoi du service SMTP

2. **Email filtré comme spam**
   - Vérifier les dossiers spam/courrier indésirable
   - Vérifier la configuration SPF/DKIM du domaine

3. **Email rejeté par le serveur destinataire**
   - Essayer avec plusieurs adresses email différentes
   - Tester avec Gmail, Outlook, etc.

**Solutions:**
```bash
# Test 1: Vérifier la configuration SMTP
# Aller dans Paramètres d'e-mail
# Noter les paramètres:
# - Host: smtp.example.com
# - Port: 587
# - Encryption: TLS
# - Username: your@email.com

# Test 2: Envoyer vers plusieurs destinataires
# Essayer d'envoyer la même facture vers:
# - Votre email professionnel
# - Votre email personnel (Gmail)
# - Un email de test (Mailtrap, etc.)

# Test 3: Vérifier les logs SMTP détaillés
# Activer les logs SMTP dans .env
MAIL_DEBUG=true

# Puis consulter les logs
tail -f storage/logs/laravel.log
```

---

### Problème: Erreur "Failed to send email"

**Diagnostic:**
```bash
# Consulter les logs pour l'erreur exacte
grep "Échec envoi email" storage/logs/laravel.log -A 5
```

**Erreurs Communes:**

**1. Authentification échouée**
```
Swift_TransportException: Failed to authenticate on SMTP server
```
**Solution:**
- Vérifier le nom d'utilisateur SMTP
- Vérifier le mot de passe SMTP
- Certains services requièrent des "App Passwords" (Gmail, etc.)

**2. Connexion refusée**
```
Swift_TransportException: Connection could not be established
```
**Solution:**
- Vérifier le serveur SMTP (host)
- Vérifier le port (587 pour TLS, 465 pour SSL)
- Vérifier que le pare-feu autorise la connexion

**3. Certificat SSL invalide**
```
stream_socket_enable_crypto(): SSL operation failed
```
**Solution:**
- Vérifier l'encryption (TLS vs SSL)
- Essayer de désactiver la vérification SSL (pour test uniquement):
  ```env
  MAIL_ENCRYPTION=null
  ```

---

## 📊 Checklist de Test Complet

### Configuration Préalable
- [ ] Configuration SMTP vérifiée dans Paramètres d'e-mail
- [ ] Au moins une facture existe avec un client ayant un email
- [ ] Pull Request #7 mergé
- [ ] Cache vidé (`php artisan cache:clear`)

### Tests Interface Utilisateur
- [ ] Bouton email visible sur la page facture
- [ ] Modal s'ouvre au clic
- [ ] Email client pré-rempli (si existe)
- [ ] Sujet pré-rempli avec numéro facture
- [ ] Message par défaut affiché
- [ ] Bouton "Annuler" ferme le modal
- [ ] Validation des champs (email requis et valide)

### Tests Envoi Email
- [ ] Spinner affiché pendant l'envoi
- [ ] Toast de succès apparaît
- [ ] Modal se ferme automatiquement
- [ ] Email reçu dans la boîte de réception
- [ ] Contenu email correct et complet
- [ ] Design email professionnel
- [ ] Email lisible sur mobile

### Tests Gestion Erreurs
- [ ] Email invalide affiche erreur
- [ ] Champ vide affiche erreur
- [ ] Erreur SMTP affichée à l'utilisateur
- [ ] Logs enregistrent les erreurs
- [ ] Modal reste ouvert en cas d'erreur

### Tests Logs
- [ ] "Tentative envoi" enregistré
- [ ] "Envoyé avec succès" enregistré (si succès)
- [ ] "Échec envoi" enregistré (si échec)
- [ ] Détails complets dans les logs

---

## 🛠️ Commandes Utiles

### Vider les Caches
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

### Consulter les Logs
```bash
# Dernières 100 lignes
tail -100 storage/logs/laravel.log

# Logs en temps réel
tail -f storage/logs/laravel.log

# Rechercher les logs d'email
grep "email" storage/logs/laravel.log

# Logs d'aujourd'hui seulement
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log
```

### Tests SMTP Manuels
```bash
# Test de connexion SMTP (Linux/Mac)
telnet smtp.example.com 587

# Ou avec openssl pour TLS
openssl s_client -connect smtp.example.com:587 -starttls smtp
```

---

## 📧 Configuration SMTP Recommandée

### Gmail (avec App Password)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Mailtrap (Pour Tests)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=verified@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🎯 Résultats Attendus

### Succès Complet
Tous les tests passent:
- ✅ Interface fonctionne parfaitement
- ✅ Feedbacks visuels clairs
- ✅ Emails reçus avec contenu correct
- ✅ Logs complets et clairs
- ✅ Gestion d'erreurs robuste

### Succès Partiel
Interface fonctionne mais emails non reçus:
- ✅ Interface fonctionne
- ✅ Feedbacks visuels OK
- ❌ Emails non reçus
- ✅ Logs indiquent "envoyé avec succès"

**Action**: Problème de configuration SMTP ou filtrage
- Consulter la section "Email Non Reçu mais Succès Affiché"
- Vérifier avec l'hébergeur SMTP

---

## 📞 Support et Rapports

Si vous rencontrez des problèmes après avoir suivi ce guide:

### Informations à Fournir

1. **Logs Laravel** (dernières 100 lignes ou logs pertinents):
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **Console Navigateur** (si problème d'interface):
   - Capture d'écran de la console F12
   - Onglets Console et Network

3. **Configuration SMTP** (sans le mot de passe):
   - Host
   - Port
   - Encryption
   - Username (type)

4. **Description du Problème**:
   - Quelle étape échoue exactement?
   - Quel message d'erreur apparaît?
   - Capture d'écran si possible

---

**Bonne Chance avec les Tests!** 🚀

Si tout fonctionne correctement, vous aurez un système d'envoi d'email professionnel et robuste pour vos factures.
