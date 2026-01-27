# Guide de Test - Email Mot de Passe Oublié

## 📋 Pré-requis

Avant de tester la fonctionnalité "Mot de passe oublié", assurez-vous que :

1. ✅ Les paramètres SMTP sont correctement configurés dans l'admin
2. ✅ Le serveur SMTP est accessible et fonctionnel
3. ✅ L'application Flutter est connectée au backend
4. ✅ Un utilisateur de test existe dans la base de données

## 🔧 Étape 1 : Vérifier la Configuration SMTP

### Via le Panneau Admin

1. Connectez-vous au panneau d'administration : `https://votre-domaine.com/admin`
2. Allez dans **Paramètres** > **Settings** > **Email Settings**
3. Vérifiez les paramètres suivants :

```
Mail Driver: smtp
Mail Host: [votre serveur SMTP]
Mail Port: 587 (TLS) ou 465 (SSL)
Mail Username: [votre email]
Mail Password: [votre mot de passe]
Mail Encryption: tls ou ssl
Mail From Address: [email expéditeur]
Mail From Name: DossuPro
```

4. Cliquez sur **Test Mail** pour vérifier que la configuration fonctionne

### Via le Script de Test

Exécutez le script de test depuis le terminal :

```bash
cd /chemin/vers/doss-genspark_ai_developer
php test_smtp_config.php
```

Le script va :
- Afficher la configuration SMTP actuelle
- Vous demander un email de test
- Envoyer un email de test
- Tester la fonction forgotPassword si l'utilisateur existe

Exemple de sortie réussie :
```
=== Test de Configuration SMTP pour DossuPro ===

📧 Paramètres SMTP trouvés:
   Mail Driver: smtp
   Mail Host: smtp.gmail.com
   Mail Port: 587
   Mail Username: contact@dossupro.com
   Mail Encryption: tls
   Mail From Address: contact@dossupro.com
   Mail From Name: DossuPro

✅ Configuration SMTP chargée avec succès.

Entrez une adresse email pour le test: test@example.com

📤 Envoi d'un email de test à test@example.com...
✅ Email envoyé avec succès !
```

## 🧪 Étape 2 : Tester depuis Flutter

### 2.1 Ouvrir l'Application Flutter

1. Lancez l'application Flutter sur un émulateur ou un appareil physique
2. Sur l'écran de connexion, trouvez le bouton "Mot de passe oublié ?"
3. Cliquez sur "Mot de passe oublié ?"

### 2.2 Entrer l'Email

1. Entrez une adresse email valide qui existe dans la base de données
   - Exemple : `test@example.com`
2. Cliquez sur le bouton "Envoyer"

### 2.3 Vérifier la Réponse

**Cas de succès (200) :**
- Message affiché : "Un email de réinitialisation a été envoyé à votre adresse."
- Vérifiez votre boîte de réception (et le dossier spam)

**Cas d'échec - Email inexistant (422) :**
- Message : "Impossible d'envoyer l'email de réinitialisation. Veuillez vérifier votre adresse email."

**Cas d'erreur SMTP (500) :**
- Message : "Une erreur est survenue lors de l'envoi de l'email. Veuillez réessayer plus tard."
- Vérifiez les logs Laravel pour plus de détails

### 2.4 Vérifier l'Email Reçu

L'email devrait contenir :
- ✉️ Expéditeur : DossuPro (ou le nom configuré)
- 📧 Sujet : "Reset Password Notification" (par défaut Laravel)
- 🔗 Un lien de réinitialisation valide pendant 60 minutes

### 2.5 Cliquer sur le Lien

1. Cliquez sur le lien de réinitialisation dans l'email
2. Vous serez redirigé vers une page web (pas l'app Flutter)
3. Entrez le nouveau mot de passe deux fois
4. Cliquez sur "Reset Password"

### 2.6 Tester la Connexion

1. Retournez à l'application Flutter
2. Essayez de vous connecter avec le nouveau mot de passe
3. La connexion devrait réussir

## 📊 Étape 3 : Vérifier les Logs

### Logs Laravel

Consultez les logs dans `storage/logs/laravel.log` :

```bash
tail -f storage/logs/laravel.log
```

**Logs de succès :**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: SMTP configured for password reset {"email":"test@example.com"}
[YYYY-MM-DD HH:MM:SS] local.INFO: Password reset link sent successfully {"email":"test@example.com"}
```

**Logs d'erreur :**
```
[YYYY-MM-DD HH:MM:SS] local.ERROR: Password reset error {"email":"test@example.com","error":"..."}
```

### Logs Flutter

Côté Flutter, vérifiez les logs de la console :

```
I/flutter (12345): Requesting password reset for: test@example.com
I/flutter (12345): Password reset response: 200
I/flutter (12345): Success: Un email de réinitialisation a été envoyé
```

## 🐛 Dépannage

### Problème 1 : Erreur SMTP 535 (Authentication Failed)

**Symptômes :**
```
Expected response code "235" but got code "535"
```

**Solutions :**
1. Vérifiez que le mot de passe SMTP est correct
2. Si vous utilisez Gmail :
   - Activez "Accès aux applications moins sécurisées"
   - OU utilisez un "Mot de passe d'application"
3. Vérifiez que le username est l'adresse email complète

### Problème 2 : Connexion Refusée

**Symptômes :**
```
Connection could not be established with host
```

**Solutions :**
1. Vérifiez que le port est correct (587 pour TLS, 465 pour SSL)
2. Vérifiez que le serveur SMTP est accessible
3. Testez avec telnet :
   ```bash
   telnet smtp.votre-serveur.com 587
   ```
4. Vérifiez les règles de pare-feu

### Problème 3 : Email Non Reçu

**Solutions :**
1. Vérifiez le dossier spam
2. Vérifiez les logs Laravel pour confirmer l'envoi
3. Attendez quelques minutes (délai de livraison)
4. Vérifiez que l'adresse email du destinataire est valide
5. Vérifiez les limites d'envoi de votre serveur SMTP

### Problème 4 : Lien de Réinitialisation Expiré

**Symptômes :**
```
This password reset token is invalid.
```

**Solutions :**
1. Le lien est valide pendant 60 minutes seulement
2. Demandez un nouveau lien de réinitialisation
3. Vérifiez la configuration dans `config/auth.php` :
   ```php
   'passwords' => [
       'users' => [
           'expire' => 60, // minutes
       ],
   ],
   ```

### Problème 5 : Configuration SMTP Non Appliquée

**Symptômes :**
- Les emails utilisent la configuration par défaut au lieu de la BDD

**Solution :**
```bash
# Vider le cache de configuration
php artisan config:clear
php artisan cache:clear

# Reconstruire le cache
php artisan config:cache
```

## ✅ Checklist de Test Complet

### Configuration
- [ ] Paramètres SMTP configurés dans l'admin
- [ ] Test Mail envoyé avec succès depuis l'admin
- [ ] Script `test_smtp_config.php` exécuté avec succès
- [ ] Cache Laravel vidé

### Tests Fonctionnels
- [ ] Email envoyé pour un utilisateur existant
- [ ] Message d'erreur pour un email inexistant
- [ ] Email reçu dans la boîte de réception
- [ ] Lien de réinitialisation fonctionnel
- [ ] Nouveau mot de passe défini avec succès
- [ ] Connexion réussie avec le nouveau mot de passe

### Tests de Sécurité
- [ ] Le lien expire après 60 minutes
- [ ] Le lien ne peut être utilisé qu'une seule fois
- [ ] Les mots de passe sont hashés correctement
- [ ] Les logs ne contiennent pas de mots de passe en clair

### Tests de Logs
- [ ] Logs de succès visibles dans `laravel.log`
- [ ] Logs d'erreur détaillés en cas d'échec
- [ ] Logs Flutter confirment la réponse de l'API

## 📞 Support

Si vous rencontrez des problèmes persistants :

1. **Vérifiez les fichiers modifiés :**
   - `app/Http/Controllers/Api/Mobile/AuthController.php`

2. **Consultez la documentation :**
   - `CORRECTION_EMAIL_MOT_DE_PASSE_OUBLIE.md`

3. **Exécutez les commandes de diagnostic :**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php test_smtp_config.php
   ```

4. **Vérifiez les logs détaillés :**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

## 🎉 Résultat Attendu

Une fois tous les tests réussis, vous devriez avoir :

✅ Une configuration SMTP fonctionnelle
✅ Des emails de réinitialisation envoyés avec succès
✅ Des utilisateurs capables de réinitialiser leur mot de passe
✅ Des logs détaillés pour le débogage
✅ Une expérience utilisateur fluide depuis Flutter

---

**Date de création :** 6 janvier 2026
**Version :** 1.0
**Testé sur :** Laravel 10.x, Flutter 3.x
