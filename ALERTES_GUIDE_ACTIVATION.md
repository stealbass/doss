# 🚀 GUIDE D'ACTIVATION RAPIDE - SYSTÈME D'ALERTES

## ⚡ Mise en Production - 5 Étapes Essentielles

---

## ✅ STATUT ACTUEL
- ✅ **Tous les fichiers créés** (16 fichiers)
- ✅ **Code intégré** dans les controllers
- ✅ **Templates email** prêts (FR/EN)
- ✅ **Jobs et Commandes** opérationnels
- ⏳ **Activation serveur** requise

---

## 📋 ÉTAPE 1 : CONFIGURATION EMAIL (.env)

Ouvrir le fichier `.env` et configurer les paramètres SMTP :

```env
# Configuration Email SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com          # ou smtp.office365.com, etc.
MAIL_PORT=587
MAIL_USERNAME=votre-email@example.com
MAIL_PASSWORD=votre-mot-de-passe-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@dossypro.com
MAIL_FROM_NAME="Dossy Pro Notifications"

# Configuration Queue
QUEUE_CONNECTION=database          # ou 'redis' pour plus de performance
```

### **Pour Gmail**
1. Activer l'authentification à 2 facteurs
2. Générer un "Mot de passe d'application" : https://myaccount.google.com/apppasswords
3. Utiliser ce mot de passe dans `MAIL_PASSWORD`

### **Pour Office 365 / Outlook**
```env
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@outlook.com
MAIL_PASSWORD=votre-mot-de-passe
```

### **Test de Configuration**
```bash
php artisan config:clear
php artisan config:cache

# Tester l'envoi d'email
php artisan tinker
>>> Mail::raw('Test configuration email Dossy Pro', function($msg) { 
    $msg->to('test@example.com')->subject('Test Email'); 
});
```

---

## 📋 ÉTAPE 2 : MIGRATION DE LA TABLE QUEUE

La table `jobs` est nécessaire pour stocker les emails en attente d'envoi.

```bash
# Créer la migration de la table jobs
php artisan queue:table

# Exécuter la migration
php artisan migrate

# Vérifier la table
php artisan tinker
>>> DB::table('jobs')->count();
```

**Résultat attendu** : La table `jobs` existe dans la base de données.

---

## 📋 ÉTAPE 3 : DÉMARRER LE WORKER DE QUEUE

Le worker traite les emails en arrière-plan sans bloquer l'application.

### **Option A : Mode Développement / Test**
```bash
# Exécuter dans un terminal
php artisan queue:work --tries=3 --timeout=90

# Laisser tourner (pour tests)
```

### **Option B : Mode Production (Supervisor)**

#### **1. Installer Supervisor**
```bash
# Sur Ubuntu/Debian
sudo apt-get install supervisor

# Sur CentOS/RHEL
sudo yum install supervisor
```

#### **2. Créer le fichier de configuration**
```bash
sudo nano /etc/supervisor/conf.d/dossy-queue-worker.conf
```

**Contenu du fichier** :
```ini
[program:dossy-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dossy_pro/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/dossy_pro/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**⚠️ Adapter** :
- `/var/www/dossy_pro` → Chemin réel de votre projet
- `www-data` → Utilisateur du serveur web

#### **3. Activer et démarrer**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start dossy-queue-worker:*
sudo supervisorctl status
```

**Résultat attendu** :
```
dossy-queue-worker:dossy-queue-worker_00   RUNNING   pid 12345, uptime 0:00:10
dossy-queue-worker:dossy-queue-worker_01   RUNNING   pid 12346, uptime 0:00:10
```

#### **4. Gestion du worker**
```bash
# Redémarrer
sudo supervisorctl restart dossy-queue-worker:*

# Arrêter
sudo supervisorctl stop dossy-queue-worker:*

# Voir les logs
tail -f /var/www/dossy_pro/storage/logs/queue-worker.log
```

---

## 📋 ÉTAPE 4 : ACTIVER LE SCHEDULER LARAVEL (CRON)

Le scheduler exécute les rappels automatiques quotidiens.

### **1. Éditer le crontab**
```bash
# En tant qu'utilisateur du serveur web (www-data, apache, nginx)
sudo crontab -u www-data -e

# OU si vous êtes déjà cet utilisateur
crontab -e
```

### **2. Ajouter cette ligne**
```cron
* * * * * cd /var/www/dossy_pro && php artisan schedule:run >> /dev/null 2>&1
```

**⚠️ Adapter** `/var/www/dossy_pro` au chemin réel de votre projet.

### **3. Vérifier la configuration CRON**
```bash
# Lister les tâches CRON
crontab -l

# Tester manuellement
cd /var/www/dossy_pro
php artisan schedule:run
```

### **4. Vérifier les tâches planifiées**
```bash
php artisan schedule:list
```

**Résultat attendu** :
```
0 9 * * *  reminders:hearings --days=2 .......... Next Due: 2024-12-19 09:00:00
0 9 * * *  reminders:tasks --days=2 ............. Next Due: 2024-12-19 09:00:00
0 18 * * * reminders:hearings --days=1 .......... Next Due: 2024-12-18 18:00:00
0 18 * * * reminders:tasks --days=1 ............. Next Due: 2024-12-18 18:00:00
```

---

## 📋 ÉTAPE 5 : TESTS MANUELS

### **Test 1 : Créer une Audience**

1. **Se connecter** à Dossy Pro
2. **Accéder** à un dossier existant
3. **Cliquer** sur l'onglet "Audiences/Interventions"
4. **Cliquer** sur "Créer une audience"
5. **Remplir** :
   - Date : 20/12/2025 (ou date future)
   - Remarques : Test audience système alertes
6. **Soumettre**

**✅ Vérification** :
- Message de succès affiché
- Email "Nouvelle audience créée" reçu immédiatement
- Log dans `storage/logs/laravel.log` :
  ```
  [2024-12-18 10:30:00] local.INFO: Hearing created notification sent to: user@example.com
  ```

### **Test 2 : Créer une Tâche**

1. **Accéder** à "Tâches" → "Nouvelle tâche"
2. **Remplir** :
   - Titre : Test tâche système alertes
   - Description : Vérification envoi email
   - Date d'échéance : 22/12/2025
   - Priorité : Haute
   - Assigner à : [Utilisateur]
3. **Soumettre**

**✅ Vérification** :
- Message de succès affiché
- Email "Nouvelle tâche créée" reçu immédiatement
- Log dans `storage/logs/laravel.log`

### **Test 3 : Rappels Automatiques (Manuel)**

Simuler l'exécution des rappels :

```bash
# Rappel audiences (2 jours avant)
php artisan reminders:hearings --days=2

# Rappel tâches (2 jours avant)
php artisan reminders:tasks --days=2
```

**✅ Vérification** :
- Output console indique le nombre de rappels envoyés
- Emails de rappel reçus (si audiences/tâches dans 2 jours)
- Logs dans `storage/logs/laravel.log`

### **Test 4 : Vérifier la Queue**

```bash
# Nombre de jobs en attente
php artisan queue:monitor

# Logs du worker
tail -f storage/logs/laravel.log | grep -i "notification"

# Jobs échoués
php artisan queue:failed
```

**✅ Attendu** : Aucun job échoué, tous les emails envoyés.

---

## 🔍 MONITORING ET SURVEILLANCE

### **1. Vérifier les Logs en Temps Réel**
```bash
# Logs généraux
tail -f storage/logs/laravel.log

# Filtrer les notifications
tail -f storage/logs/laravel.log | grep -E "notification|hearing|task"

# Filtrer les erreurs
tail -f storage/logs/laravel.log | grep ERROR
```

### **2. Surveiller la Queue**
```bash
# Statistiques en temps réel
watch -n 1 'php artisan queue:monitor'

# Relancer les jobs échoués
php artisan queue:retry all

# Vider la queue (attention !)
php artisan queue:flush
```

### **3. Vérifier le Scheduler**
```bash
# Liste des tâches planifiées
php artisan schedule:list

# Tester manuellement
php artisan schedule:test

# Forcer l'exécution immédiate
php artisan schedule:run
```

### **4. Dashboard Superviseur (si installé)**
```bash
# Interface web Supervisor (optionnel)
sudo nano /etc/supervisor/supervisord.conf
```

Ajouter :
```ini
[inet_http_server]
port=127.0.0.1:9001
username=admin
password=secret
```

Redémarrer : `sudo service supervisor restart`  
Accéder : `http://localhost:9001`

---

## 🚨 RÉSOLUTION DE PROBLÈMES COURANTS

### **Problème 1 : Emails non envoyés**

**Symptômes** :
- Aucun email reçu après création audience/tâche
- Logs : "Connection refused" ou "Authentication failed"

**Solutions** :
```bash
# 1. Vérifier la config
php artisan config:clear
php artisan config:cache

# 2. Tester la connexion SMTP
telnet smtp.gmail.com 587

# 3. Vérifier les logs
tail -f storage/logs/laravel.log | grep -i "mail\|smtp"

# 4. Tester l'envoi direct
php artisan tinker
>>> Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });
```

### **Problème 2 : Queue worker arrêté**

**Symptômes** :
- Jobs s'accumulent dans la table `jobs`
- Emails en retard

**Solutions** :
```bash
# Redémarrer le worker (Supervisor)
sudo supervisorctl restart dossy-queue-worker:*

# Ou manuellement
php artisan queue:restart
php artisan queue:work --tries=3

# Vérifier les jobs en attente
mysql -u root -p dossy_pro -e "SELECT COUNT(*) FROM jobs;"
```

### **Problème 3 : Scheduler ne s'exécute pas**

**Symptômes** :
- Aucun rappel envoyé à 9h00
- `php artisan schedule:list` vide

**Solutions** :
```bash
# 1. Vérifier le CRON
crontab -l

# 2. Tester manuellement
php artisan schedule:run

# 3. Vérifier les permissions
ls -la /var/www/dossy_pro/storage/logs

# 4. Vérifier le timezone
php artisan tinker
>>> config('app.timezone');  # Doit être 'Africa/Abidjan' ou équivalent
```

### **Problème 4 : Jobs échoués**

**Symptômes** :
- `php artisan queue:failed` affiche des jobs

**Solutions** :
```bash
# Relancer tous les jobs échoués
php artisan queue:retry all

# Relancer un job spécifique
php artisan queue:retry [ID]

# Voir les détails d'un job échoué
php artisan queue:failed

# Supprimer les jobs échoués (attention !)
php artisan queue:flush
```

---

## ✅ CHECKLIST DE DÉPLOIEMENT

Avant de mettre en production, vérifier :

- [ ] **Configuration Email (.env)**
  - [ ] MAIL_HOST configuré
  - [ ] MAIL_USERNAME configuré
  - [ ] MAIL_PASSWORD configuré
  - [ ] Test d'envoi email réussi

- [ ] **Queue**
  - [ ] Table `jobs` créée (`php artisan queue:table && migrate`)
  - [ ] Worker démarré (Supervisor ou manuel)
  - [ ] Test création audience → Email reçu
  - [ ] Test création tâche → Email reçu

- [ ] **Scheduler**
  - [ ] CRON configuré (`crontab -l`)
  - [ ] `php artisan schedule:list` affiche les tâches
  - [ ] Test manuel `php artisan reminders:hearings --days=2`
  - [ ] Test manuel `php artisan reminders:tasks --days=2`

- [ ] **Logs**
  - [ ] Permissions `storage/logs` (775)
  - [ ] Aucun ERROR dans `laravel.log`
  - [ ] Notifications loggées correctement

- [ ] **Tests Utilisateur**
  - [ ] Créer audience → Email reçu
  - [ ] Créer tâche → Email reçu
  - [ ] Audience J-2 → Email rappel reçu
  - [ ] Tâche J-2 → Email rappel reçu

---

## 📊 STATISTIQUES ATTENDUES

### **Après 1 semaine d'utilisation** :

| Métrique | Attendu |
|----------|---------|
| Emails de création envoyés | 100% des audiences/tâches créées |
| Emails de rappel envoyés | 100% des audiences/tâches J-2 |
| Taux de livraison | > 95% |
| Jobs échoués | < 5% |
| Temps d'envoi moyen | < 10 secondes |

### **Monitoring Production**

Créer un dashboard admin avec :
- Nombre d'emails envoyés aujourd'hui
- Nombre de jobs en queue
- Nombre de jobs échoués
- Prochaines audiences (J-2, J-1)
- Prochaines tâches (J-2, J-1)

---

## 🎯 PROCHAINES ÉTAPES (POST-ACTIVATION)

Une fois le système opérationnel :

1. **Ajuster les horaires** de rappel selon préférences utilisateurs
2. **Ajouter des rappels supplémentaires** (J-7, J-3, etc.)
3. **Créer des préférences utilisateur** (activer/désactiver emails)
4. **Intégrer les push notifications** mobile (Firebase/OneSignal)
5. **Ajouter des notifications SMS** (Twilio) pour urgences
6. **Créer un dashboard de monitoring** dans l'admin

---

## 📞 SUPPORT TECHNIQUE

En cas de blocage :

1. **Vérifier les logs** : `storage/logs/laravel.log`
2. **Tester manuellement** : `php artisan reminders:hearings --days=2`
3. **Vérifier la queue** : `php artisan queue:monitor`
4. **Vérifier le CRON** : `crontab -l`
5. **Redémarrer le worker** : `sudo supervisorctl restart dossy-queue-worker:*`

---

## 🏁 RÉSUMÉ DES COMMANDES CLÉS

```bash
# Configuration
php artisan config:clear && php artisan config:cache

# Queue
php artisan queue:table && php artisan migrate
php artisan queue:work --tries=3

# Scheduler
crontab -e  # Ajouter : * * * * * cd /path && php artisan schedule:run
php artisan schedule:list

# Tests
php artisan reminders:hearings --days=2
php artisan reminders:tasks --days=2

# Monitoring
tail -f storage/logs/laravel.log | grep notification
php artisan queue:monitor
php artisan queue:failed

# Supervisor (Production)
sudo supervisorctl status
sudo supervisorctl restart dossy-queue-worker:*
```

---

**Document créé le** : 18/12/2024  
**Version** : 1.0  
**Auteur** : GenSpark AI Developer  
**Projet** : Dossy Pro - Legal Management System

---

✅ **SYSTÈME PRÊT À L'ACTIVATION !**

Suivez ces 5 étapes et le système d'alertes automatiques sera opérationnel. Tous les utilisateurs recevront leurs notifications aux moments stratégiques.

**Bon déploiement ! 🚀**
