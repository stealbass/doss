# 🧪 Guide de Test Complet - Système d'Email Tâches

## 📋 Préparation de l'Environnement

### 1. Vérifier la Queue
```bash
# Terminal 1 - Lancer le worker
php artisan queue:work

# Terminal 2 - Lancer l'observateur de fichiers (optionnel)
php artisan tinker
```

### 2. Vérifier les Logs
```bash
# Terminal 3 - Suivre les logs en temps réel
tail -f storage/logs/laravel.log

# Ou avec grep pour filtrer
tail -f storage/logs/laravel.log | grep "Task notification"
```

### 3. Configuration SMTP
Vérifier que votre configuration SMTP est valide :
```bash
# Dans Utility::getSMTPDetails()
- Username/Password valides
- Host accessible
- Port correct (465 SSL, 587 TLS, 25)
```

---

## 🔍 Scénario de Test Complet

### Étape 1 : Créer les Utilisateurs de Test

**Via Interface Admin** :
1. Allez sur `/admin/users`
2. Créez les utilisateurs suivants :

| ID | Nom | Email | Type | Rôle |
|----|-----|-------|------|------|
| 1 | Admin Test | admin@test.local | admin | Admin |
| 2 | User Avocat 1 | avocat1@test.local | advocate | Avocat |
| 3 | User Avocat 2 | avocat2@test.local | advocate | Avocat |
| 4 | User Assigné 1 | assigned1@test.local | user | Utilisateur |
| 5 | User Assigné 2 | assigned2@test.local | user | Utilisateur |
| 6 | Client 1 | client1@test.local | client | Client |
| 7 | Client 2 | client2@test.local | client | Client |

### Étape 2 : Créer une Affaire de Test

1. Admin crée une affaire :
   - Titre : "Affaire Test - Emails"
   - Numéro : "TEST-2025-001"
   - Avocats assignés : User Avocat 1, User Avocat 2
   - Clients (your_party_name) : Client 1, Client 2

2. Note l'ID de l'affaire : `case_id = X`

### Étape 3 : Créer une Tâche

**Via Interface ou API** :

```php
// Via Controller (Formulaire)
POST /todo/store

Payload:
{
    'description': 'Vérifier les documents importants',
    'title': 'Vérification Documents',
    'assign_to': '4,5',  // CSV : User Assigné 1, 2
    'relate_to': X,      // ID de l'affaire
    'priority': 'high',
    'due_date': '2025-01-31',
    'start_date': '2025-01-23'
}
```

**Ou via Tinker** :

```php
php artisan tinker

$todo = new \App\Models\ToDo();
$todo->description = 'Vérifier les documents importants';
$todo->title = 'Vérification Documents';
$todo->assign_to = '4,5';          // CSV
$todo->relate_to = X;               // ID affaire
$todo->priority = 'high';
$todo->due_date = '2025-01-31';
$todo->start_date = '2025-01-23';
$todo->created_by = 1;              // Admin
$todo->save();

// Dispatcher le job manuellement pour test
\App\Jobs\SendTaskCreatedNotification::dispatch($todo);
```

### Étape 4 : Vérifier les Logs

Recherchez dans `storage/logs/laravel.log` :

```
[2025-01-23 14:30:45] local.INFO: SendTaskCreatedNotification job started 
{
  "task_id": 1,
  "assign_to": "4,5",
  "relate_to": X,
  "created_by": 1
}

[2025-01-23 14:30:46] local.INFO: Task notification recipients 
{
  "task_id": 1,
  "case_id": X,
  "to_user": {
    "id": 1,
    "email": "admin@test.local",
    "name": "Admin Test"
  },
  "bcc_recipients": [
    {
      "id": 4,
      "email": "assigned1@test.local",
      "name": "User Assigné 1"
    },
    {
      "id": 5,
      "email": "assigned2@test.local",
      "name": "User Assigné 2"
    },
    {
      "id": 2,
      "email": "avocat1@test.local",
      "name": "User Avocat 1"
    },
    {
      "id": 3,
      "email": "avocat2@test.local",
      "name": "User Avocat 2"
    },
    {
      "id": 6,
      "email": "client1@test.local",
      "name": "Client 1"
    },
    {
      "id": 7,
      "email": "client2@test.local",
      "name": "Client 2"
    }
  ],
  "bcc_count": 6
}

[2025-01-23 14:30:47] local.INFO: Task created notification sent 
{
  "task_id": 1,
  "case_id": X,
  "to_email": "admin@test.local",
  "bcc_count": 6
}

[2025-01-23 14:30:48] local.INFO: Push notification sent for task created 
{
  "task_id": 1,
  "case_id": X,
  "users_count": 7
}
```

---

## 📧 Vérifications Email

### Cas 1 : Avec Mailpit (Recommended)

**Installation** :
```bash
# Via Docker
docker run -d --name mailpit -p 8025:1025 -p 8026:8025 axllent/mailpit

# Ou télécharger : https://mailpit.axllent.me/
```

**Configuration .env** :
```
MAIL_DRIVER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_NAME="Dossy Pro"
```

**Interface Web** :
```
http://localhost:8026
```

**Vérifications** :

1. **Email reçu** :
   - Vérifier 1 email depuis "Dossy Pro"
   - À : `admin@test.local`
   - Sujet : "Nouvelle Tâche Créée - Vérifier les documents importants"

2. **BCC Headers** :
   - Cliquer sur l'email
   - Chercher dans les headers :
   ```
   BCC: assigned1@test.local, assigned2@test.local, 
        avocat1@test.local, avocat2@test.local, 
        client1@test.local, client2@test.local
   ```

3. **HTML Content** :
   - Vérifier la section "👥 Destinataires en Copie Conforme"
   - Vérifier les 6 noms avec emojis
   - Vérifier la section "📋 Affaire Liée"

### Cas 2 : Avec Service Email Réel

Si vous utilisez Gmail, SendGrid, Mailgun, etc. :

**Pour Gmail** :
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-app-password
```

**Vérifier dans Gmail** :
1. Vérifier le spam (au cas où)
2. Afficher les détails de l'email
3. Chercher "BCC" dans les headers

### Cas 3 : Avec Log Mail Driver (Testing)

```env
MAIL_DRIVER=log
```

Les emails seront logués dans `storage/logs/laravel.log` :
```
[2025-01-23 14:30:47] local.INFO: 
Message from admin@test.local (Admin Test) to:
[
  "assigned1@test.local",
  "assigned2@test.local",
  ...
]

BCC:
[
  "assigned1@test.local",
  "assigned2@test.local",
  ...
]

Body: <html>...</html>
```

---

## 🔧 Tests Spécifiques

### Test 1 : Tâche Sans Affaire

```php
$todo = new \App\Models\ToDo();
$todo->description = 'Tâche sans affaire';
$todo->assign_to = '4,5';
$todo->relate_to = null;  // ❌ Pas d'affaire
$todo->created_by = 1;
$todo->save();
```

**Résultat attendu** :
- ✅ Email envoyé à admin seul
- ✅ BCC : User Assigné 1 et 2 SEULEMENT
- ✅ Section "Affaire Liée" : PAS AFFICHÉE
- ✅ BCC count : 2

### Test 2 : Tâche Avec Affaire Sans Clients

```php
// Créer une affaire sans clients
$case = new \App\Models\Cases();
$case->title = 'Affaire Sans Clients';
$case->advocates_id = '2,3';  // Juste les avocats
$case->your_party_name = '[]'; // Pas de clients
$case->save();

$todo = new \App\Models\ToDo();
$todo->description = 'Tâche pour affaire sans clients';
$todo->assign_to = null;        // Pas d'assignés
$todo->relate_to = $case->id;
$todo->created_by = 1;
$todo->save();
```

**Résultat attendu** :
- ✅ Email envoyé à admin
- ✅ BCC : Avocat 1 et 2 SEULEMENT
- ✅ BCC count : 2

### Test 3 : Tâche Avec Format JSON

```php
// Si assign_to est en JSON
$todo = new \App\Models\ToDo();
$todo->description = 'Tâche avec JSON assignés';
$todo->assign_to = '[4, 5]';    // JSON array
$todo->relate_to = X;
$todo->created_by = 1;
$todo->save();
```

**Résultat attendu** :
- ✅ Job traite le JSON correctement
- ✅ User Assigné 1 et 2 dans BCC
- ✅ Pas d'erreur de parsing

### Test 4 : Assigné Unique

```php
$todo = new \App\Models\ToDo();
$todo->description = 'Tâche assignée à un';
$todo->assign_to = '4';         // Un seul ID
$todo->relate_to = X;
$todo->created_by = 1;
$todo->save();
```

**Résultat attendu** :
- ✅ Job traite le numéro seul
- ✅ User Assigné 1 dans BCC
- ✅ BCC count : 3+ (assigné + avocats + clients)

---

## 🔗 URLs Utiles

### Admin & Logs
- Admin Panel : `http://localhost/admin`
- Tasks List : `http://localhost/todo`
- Laravel Logs : `storage/logs/laravel.log`

### Queue Management
```bash
# Voir les jobs en attente
php artisan queue:failed

# Rejouer les jobs échoués
php artisan queue:retry all

# Nettoyer les jobs réussis
php artisan queue:flush
```

### Testing Utilities

```bash
# Lancer les tests unitaires
php artisan test

# Lancer un test spécifique
php artisan test --filter SendTaskCreatedNotification

# Code coverage
php artisan test --coverage
```

---

## 📊 Checklist de Validation

### Configuration ✓
- [ ] Queue worker actif
- [ ] Logs accessible
- [ ] SMTP testé
- [ ] Utilisateurs créés
- [ ] Affaire de test créée

### Fonctionnalité ✓
- [ ] Tâche créée avec succès
- [ ] Job exécuté (logs visibles)
- [ ] Email reçu par admin
- [ ] BCC contient 6 personnes
- [ ] Pas de doublon
- [ ] Section BCC visible
- [ ] Section Affaire visible
- [ ] Émojis affichés correctement

### Contenu Email ✓
- [ ] Titre : "Nouvelle Tâche Créée"
- [ ] Description correcte
- [ ] Priorité affichée avec badge
- [ ] Date échéance formatée
- [ ] Statut visible
- [ ] Affaire liée affichée
- [ ] BCC box avec noms et emails
- [ ] Types (Client, Avocat, User) corrects
- [ ] Bouton "Voir la Tâche" fonctionne

### Logs ✓
- [ ] Log de démarrage du job
- [ ] Log des destinataires
- [ ] Log du succès d'envoi
- [ ] Log des push notifications
- [ ] Pas d'erreurs

### Cas Limites ✓
- [ ] Tâche sans affaire
- [ ] Tâche sans assignés
- [ ] Affaire sans clients
- [ ] Format JSON pour assignés
- [ ] ID d'assigné unique
- [ ] Utilisateur assigné = créateur (pas dupé)

---

## 🐛 Debugging

### Si Email N'est Pas Reçu

```php
// 1. Vérifier le Job dispatch
Log::info('Job dispatched', ['todo_id' => $todo->id]);
SendTaskCreatedNotification::dispatch($todo);

// 2. Vérifier queue processing
php artisan queue:work --verbose

// 3. Vérifier SMTP
php artisan tinker
>>> Utility::getSMTPDetails(1);
>>> Mail::raw('Test', function($msg) { $msg->to('test@test.com'); });

// 4. Vérifier les erreurs de base de données
DB::listen(function($query) { Log::info($query->sql); });
```

### Si BCC Est Vide

```php
// 1. Vérifier les assignés
$todo = ToDo::find(1);
dd($todo->assign_to); // Devrait être "4,5" ou "[4,5]"

// 2. Vérifier l'affaire
$case = Cases::find($todo->relate_to);
dd($case->your_party_name); // JSON avec clients
dd($case->advocates_id);     // Avocats

// 3. Tester le parsing
$assignedIds = explode(',', $todo->assign_to);
dd($assignedIds); // [4, 5]
```

### Si Déduplication Échoue

```php
// Vérifier les IDs dupliqués
$bccUsers->pluck('id')->toArray();
// [4, 5, 2, 3, 6, 7] - OK
// [4, 4, 5, 2, 3, 6, 7] - PROBLÈME

// Ajouter logs dans le Job
Log::info('Before dedup', ['count' => $bccUsers->count()]);
$bccUsers = $bccUsers->unique('id');
Log::info('After dedup', ['count' => $bccUsers->count()]);
```

---

## 📝 Notes Importants

1. **Queue Worker** : Doit rester actif pour traiter les jobs
2. **getSMTPDetails** : Appelé 2x (sécurité queue)
3. **CSV vs JSON** : Le job gère les deux formats
4. **Déduplication** : Automatique avec `.unique('id')`
5. **Logs Verbeux** : Pour debugging facile

---

## 🎯 Résultats Attendus Finaux

Après création d'une tâche :
- ✅ 1 email TO admin
- ✅ 6 emails BCC (assignés + avocats + clients)
- ✅ 7 notifications push (tous les utilisateurs)
- ✅ 4 logs d'information
- ✅ 0 erreur
- ✅ Email HTML parfaitement formaté
- ✅ BCC recipients visibles et typés
- ✅ Case name affiché

**Temps d'exécution** : < 5 secondes (avec queue locale)

---

**Statut** : ✅ Guide Complet
**Date** : 2025-01-23
**Version** : 1.0
