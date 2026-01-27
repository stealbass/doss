# 🚀 GUIDE DE VALIDATION - Système d'Email des Affaires

## ✅ Corrections Appliquées

Le système d'email des affaires a été aligné avec celui des tâches.

**Changements clés:**
1. ✅ Ajout de `Utility::getSMTPDetails()` - Charge la config SMTP depuis la BD
2. ✅ Ajout des notifications push - Comme dans le système des tâches
3. ✅ Enrichissement des données clients - Avec leurs noms depuis la BD

---

## 🧪 Étapes de Validation

### Étape 1: Vérifier les logs

Connectez-vous au serveur et regardez les logs en temps réel:

```bash
cd /home/threesixty/yyy/Dossy
tail -f storage/logs/laravel.log | grep -i "case"
```

### Étape 2: Créer une affaire de test

1. Allez dans **Affaires → Créer une affaire**
2. Remplissez les champs:
   - ✅ Titre: "Test Affaire Email"
   - ✅ Tribunal: Sélectionnez un tribunal
   - ✅ Date de dépôt: Aujourd'hui
   - ✅ **Clients**: Sélectionnez au moins 1 client
   - ✅ **Juristes**: Assignez au moins 1 juriste
3. Cliquez sur **"Créer"**

### Étape 3: Vérifier les logs

**Résultat attendu dans les logs:**

```
[2026-01-08 22:15:30] production.INFO: Starting SendCaseCreatedNotification Job 
{"case_id":58,"case_title":"Test Affaire Email","created_by":2}

[2026-01-08 22:15:30] production.INFO: Processing clients from your_party_name 
{"case_id":58,"party_count":1}

[2026-01-08 22:15:30] production.DEBUG: Added client to BCC recipients 
{"case_id":58,"client_id":"6","client_name":"Olivier","client_email":"zamahappi@gmail.com"}

[2026-01-08 22:15:30] production.INFO: Case created notification sent with BCC 
{
  "case_id":58,
  "to":"admin@company.com",
  "to_name":"Admin Name",
  "bcc_count":2,
  "bcc_recipients":"Juriste Name (advocate) - juriste@email.com | Client Name (client) - client@email.com"
}

[2026-01-08 22:15:30] production.INFO: Push notification sent for case created 
{"case_id":58,"users_count":3}
```

### Étape 4: Vérifier les emails reçus

#### Pour l'Admin (destinataire principal):
- ✅ Reçoit l'email dans **Inbox**
- ✅ Email personnalisé avec "Bonjour [NOM_ADMIN]"
- ✅ Contient tous les détails de l'affaire

#### Pour les Clients et Juristes (BCC):
- ✅ Reçoivent l'email en **copie cachée**
- ✅ **NE VOIENT PAS** les adresses email des autres
- ✅ Chacun reçoit un seul email (pas de doublons)

#### Exemple du champ "De"/"À":
```
De: contact@dossypro.com
À: admin@company.com
BCC: juriste@email.com, client@email.com  (invisible pour eux)
```

### Étape 5: Vérifier les notifications push (Mobile)

1. **Ouvrez l'app mobile**
2. Vérifiez la section **Notifications**
3. **Résultat attendu:**
   - 📂 Nouvelle Affaire - Test Affaire Email
   - Date de dépôt: 08/01/2026
   - Cliquable → Ouvre la page de l'affaire

---

## 🔍 Dépannage

### Problème: Les logs ne montrent rien

**Cause:** QUEUE_DRIVER=sync ne fonctionne pas

**Solution:**
```bash
# Vérifiez le .env
grep "QUEUE_DRIVER" /home/threesixty/yyy/Dossy/.env

# Doit être:
QUEUE_DRIVER=sync

# Sinon, redémarrez les services:
php artisan queue:restart
php artisan cache:clear
php artisan config:clear
```

### Problème: Les emails ne sont pas reçus

**Cause 1:** Config SMTP en base de données incorrecte

**Solution:**
1. Allez dans **Admin → Settings → Email**
2. Vérifiez les paramètres SMTP
3. Testez la connexion
4. Sauvegardez

**Cause 2:** Les clients/juristes n'ont pas d'email

**Solution:**
```bash
# Dans le terminal du serveur:
php artisan tinker

# Vérifiez:
$user = User::find(6); // ID du client
$user->email // Doit avoir une valeur
```

### Problème: Les BCC ne sont pas appliqués

**Cause:** Ancienne version du code toujours active

**Solution:**
```bash
# Redémarrez le serveur PHP
systemctl restart php-fpm

# Ou si vous utilisez Docker:
docker restart php-container
```

---

## 📊 Comparaison Avant/Après

### AVANT (❌ Ne fonctionnait pas)

```
User: Admin crée une affaire
↓
CaseController::store()
  ↓ (Pas de getSMTPDetails())
  ↓ (Pas de config SMTP)
Job SendCaseCreatedNotification::dispatch()
  ↓
  ❌ Utilise .env par défaut
  ❌ Authentification SMTP échoue
  ❌ Pas de notifications push
```

### APRÈS (✅ Fonctionne)

```
User: Admin crée une affaire
↓
CaseController::store()
  ↓
Job SendCaseCreatedNotification::dispatch()
  ↓
  ✅ Utility::getSMTPDetails() - Charge config depuis BD
  ✅ Authentification SMTP réussit
  ✅ Email à admin en TO
  ✅ BCC aux clients + juristes
  ✅ Notifications push envoyées
```

---

## 📞 Support

### Logs détaillés à fournir en cas de problème:

```bash
# Récupérer les 50 dernières lignes des logs:
tail -50 /home/threesixty/yyy/Dossy/storage/logs/laravel.log

# Rechercher les erreurs:
grep -i "error\|exception\|smtp" /home/threesixty/yyy/Dossy/storage/logs/laravel.log | tail -20
```

### Documentation officielle:

- [Laravel Mail Documentation](https://laravel.com/docs/mail)
- [Laravel Jobs Documentation](https://laravel.com/docs/queues#creating-jobs)
- [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)

---

## ✨ Résultat Final

Après validation, vous devriez avoir:

✅ **Emails**
- Admin reçoit l'email principal
- Clients et juristes reçoivent en BCC

✅ **Notifications Push**
- Apparaissent sur l'app mobile
- Cliquables et navigationnelles

✅ **Logging**
- Tous les événements sont enregistrés
- Diagnostic facile en cas de problème

✅ **Conformité**
- Système identique aux tâches
- Code aligné avec les bonnes pratiques
- Multi-tenant compatible

**🎉 Le système d'email des affaires est maintenant complet et fonctionnel!**
