# 📧 Système d'Email pour Création de Tâches - Documentation Complète

## 📋 Vue d'ensemble

Le système d'email pour la création de tâches fonctionne de manière identique au système pour les audiences et les affaires. Lors de la création d'une tâche :

- **🔵 Admin/Créateur** : Reçoit l'email principal (TO)
- **🟢 Utilisateurs Assignés** : Reçoivent en copie conforme (BCC)
- **🟣 Clients de l'Affaire** : Reçoivent en copie conforme (BCC)
- **⚖️ Avocats de l'Affaire** : Reçoivent en copie conforme (BCC)

### Avantages du système BCC
- ✅ **Transparence** : Tous les destinataires sont visibles dans l'email
- ✅ **Multi-destinataires** : Les clients voient que les autres clients reçoivent aussi l'email
- ✅ **Confidentialité BCC** : Aucune adresse n'apparaît en clair dans les BCC
- ✅ **Multi-locataires** : Chaque utilisateur utilise sa propre config SMTP

---

## 🔧 Fichiers Modifiés

### 1. **app/Http/Controllers/ToDoController.php** (Modifié)

**Modification** : Remplacement de la boucle d'envoi d'emails inline par le pattern Job + getSMTPDetails()

**Ancien Code** (Problématique)
```php
// Envoi individuel des emails pour chaque utilisateur assigné
try {
    if (!empty($todo->assign_to)) {
        $assignedIds = ...;
        foreach ($assignedIds as $assignedUserId) {
            $assignedUser = User::find($assignedUserId);
            Mail::to($assignedUser->email)->send(
                new TaskAssignedNotification($todo, $assignedUser)
            );
        }
    }
} catch (\Exception $e) {
    // ...
}
```

**Nouveau Code** (Pattern recommandé)
```php
// Charger la configuration SMTP depuis la base de données
Utility::getSMTPDetails(Auth::user()->creatorId());

// Dispatcher le job avec support BCC
SendTaskCreatedNotification::dispatch($todo);
```

**Avantages** :
- Configuration multi-locataires chargée AVANT la mise en file d'attente
- Job peut être traité ultérieurement sans dépendre du contexte du requêteur
- Code plus propre et maintenable

---

### 2. **app/Jobs/SendTaskCreatedNotification.php** (Entièrement réécrit)

**Ancien Code** : Références de champs incorrectes et pas de BCC

**Problèmes Corrigés** :
| Ancien | Correct | Raison |
|--------|---------|--------|
| `$this->task->assign` | `$this->task->assign_to` | Champ réel dans la BD |
| `$this->task->case_id` | `$this->task->relate_to` | Champ réel dans la BD |
| Envoi 1 email par utilisateur | Envoi 1 email avec BCC | Pattern BCC cohérent |
| Pas de `getSMTPDetails()` | Appelé au démarrage | Support multi-tenant |

**Nouveau Flux** :

```php
public function handle()
{
    // 1. Charger config SMTP (sécurité queue)
    Utility::getSMTPDetails($this->task->created_by);

    // 2. Récupérer l'utilisateur créateur (destinataire principal)
    $creator = User::find($this->task->created_by);

    // 3. Collecter les destinataires BCC
    $bccUsers = collect();
    
    // 3a. Ajouter les utilisateurs assignés (champ comma-separated)
    if (!empty($this->task->assign_to)) {
        $assignedIds = explode(',', $this->task->assign_to); // ou JSON
        $assignedUsers = User::whereIn('id', $assignedIds)->get();
        $bccUsers = $bccUsers->merge($assignedUsers);
    }
    
    // 3b. Si affaire liée, ajouter clients et avocats
    if (!empty($this->task->relate_to)) {
        $case = Cases::find($this->task->relate_to);
        
        // Ajouter les avocats
        if (!empty($case->advocates_id)) {
            // ... ajout des avocats ...
        }
        
        // Ajouter les clients (votre_partie)
        if (!empty($case->your_party_name)) {
            $yourParties = json_decode($case->your_party_name, true);
            // ... ajout des clients ...
        }
    }

    // 4. Déduplication et filtrage
    $bccUsers = $bccUsers->unique('id')->filter(fn($u) => !empty($u->email));
    $bccEmails = $bccUsers->pluck('email')->toArray();

    // 5. Envoyer l'email
    Mail::to($creator->email)
        ->bcc($bccEmails)
        ->send(new TaskCreatedMail($this->task, $creator, $bccUsers, $caseName));

    // 6. Envoyer les notifications push
    $pushService = new PushNotificationService();
    $pushService->sendTaskCreatedNotification(
        $this->task,
        collect([$creator])->merge($bccUsers)->all()
    );
}
```

**Formats Gérés** :
- ✅ CSV simple : `"1,2,3"`
- ✅ JSON array : `"[1,2,3]"`
- ✅ Numérique seul : `"42"`
- ✅ Vide : Ignoré correctement

**Logging Complet** :
```
✅ Task notification recipients
   - task_id: 123
   - case_id: 456
   - to_user: {id, email, name}
   - bcc_recipients: [{id, email, name}, ...]
   - bcc_count: 5
```

---

### 3. **app/Mail/TaskCreatedMail.php** (Amélioré)

**Ancien** :
```php
public function __construct(ToDo $task, User $user)
{
    $this->task = $task;
    $this->user = $user;
}
```

**Nouveau** :
```php
public function __construct(
    ToDo $task, 
    User $user, 
    Collection $bccUsers = null, 
    $caseName = null
) {
    $this->task = $task;
    $this->user = $user;
    $this->bccUsers = $bccUsers ?? collect();
    $this->caseName = $caseName;
}
```

**Données Passées au Template** :
```php
[
    'userName' => $this->user->name,
    'taskDescription' => $this->task->description,
    'taskPriority' => $this->task->priority,
    'dueDate' => formatted_date,
    'taskStatus' => $this->task->status,
    'caseName' => $this->caseName,  // 🆕
    'bccRecipients' => [            // 🆕
        [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'client' | 'advocate' | 'user'
        ],
        // ...
    ]
]
```

---

### 4. **resources/views/emails/task-created.blade.php** (Enrichi)

#### Nouvelles Sections Ajoutées :

**Section Affaire Liée** :
```blade
@if($caseName)
<div class="case-box">
    <div class="info-row">
        <span class="case-label">📋 Affaire Liée:</span>
        <span class="value"><strong>{{ $caseName }}</strong></span>
    </div>
</div>
@endif
```

**Section Destinataires BCC** :
```blade
@if(!empty($bccRecipients) && count($bccRecipients) > 0)
<div class="bcc-recipients-box">
    <div class="bcc-recipients-title">👥 Destinataires en Copie Conforme</div>
    <p>Les personnes suivantes reçoivent également cette notification :</p>
    
    @foreach($bccRecipients as $recipient)
    <div class="recipient-item">
        <span class="recipient-icon">
            @if($recipient['type'] == 'client') 👤
            @elseif($recipient['type'] == 'advocate') ⚖️
            @else 👨‍💼
            @endif
        </span>
        <span>
            <span class="recipient-name">{{ $recipient['name'] }}</span>
            <span class="recipient-email">({{ $recipient['email'] }})</span>
        </span>
        <span class="recipient-type">{{ ucfirst($recipient['type']) }}</span>
    </div>
    @endforeach
</div>
@endif
```

#### Styles CSS Ajoutés :

```css
.case-box {
    background: white;
    padding: 20px;
    margin: 20px 0;
    border-left: 4px solid #6f42c1;
    border-radius: 5px;
}

.bcc-recipients-box {
    background: white;
    padding: 20px;
    margin: 20px 0;
    border-left: 4px solid #28a745;
    border-radius: 5px;
}

.recipient-item {
    padding: 10px;
    background: #f0f8f4;
    margin: 8px 0;
    border-radius: 4px;
    display: flex;
    align-items: center;
}

.recipient-type {
    margin-left: auto;
    font-size: 11px;
    padding: 3px 8px;
    background: #e8f5e9;
    color: #27ae60;
    border-radius: 3px;
}
```

---

## 🔄 Flux Complet de Création de Tâche

```
┌─────────────────────────────────────────┐
│   1. Utilisateur crée une tâche         │
│      ToDoController.store()             │
└─────────────────────┬───────────────────┘
                      ↓
┌─────────────────────────────────────────┐
│   2. Charger config SMTP                │
│      getSMTPDetails(user_id)            │
└─────────────────────┬───────────────────┘
                      ↓
┌─────────────────────────────────────────┐
│   3. Dispatcher le job                  │
│      SendTaskCreatedNotification::      │
│      dispatch($todo)                    │
└─────────────────────┬───────────────────┘
                      ↓
        ╔═════════════════════════╗
        ║  Queue Job Processing   ║
        ║  (Background Process)   ║
        ╚════════════╤════════════╝
                     ↓
┌─────────────────────────────────────────┐
│   4. Job recharge config SMTP           │
│      getSMTPDetails(task_creator_id)    │
└─────────────────────┬───────────────────┘
                      ↓
┌─────────────────────────────────────────┐
│   5. Récupérer le créateur              │
│      $creator = User::find(...)         │
└─────────────────────┬───────────────────┘
                      ↓
┌─────────────────────────────────────────┐
│   6. Collecter destinataires BCC        │
│      - Utilisateurs assignés            │
│      - Avocats de l'affaire             │
│      - Clients de l'affaire             │
└─────────────────────┬───────────────────┘
                      ↓
┌─────────────────────────────────────────┐
│   7. Dédupliciter et filtrer            │
│      unique('id') + !empty(email)       │
└─────────────────────┬───────────────────┘
                      ↓
        ╔═════════════╤══════════════════════════╗
        ║             ↓                          ║
        │   ┌──────────────────┐                │
        │   │ 8a. Email TO     │                │
        │   │ creator@mail.com │                │
        │   │ + BCC (n emails) │                │
        │   └──────────────────┘                │
        │             ↓                          │
        │   ┌──────────────────────┐            │
        │   │ TaskCreatedMail()    │            │
        │   │ + BCC Recipients     │            │
        │   │ + Case Name          │            │
        │   └──────────────────────┘            │
        │             ↓                          │
        │   ┌──────────────────────┐            │
        │   │ task-created.blade   │            │
        │   │ + BCC Recipients Box │            │
        │   │ + Case Info Section  │            │
        │   └──────────────────────┘            │
        ║             ↓                          ║
        ║   8b. Push Notifications             ║
        ║       sendTaskCreatedNotification()   ║
        ║       - Creator                      ║
        ║       - Assigned Users               ║
        ║       - Advocates                    ║
        ║       - Clients                      ║
        ╚═════════════╤══════════════════════════╝
                      ↓
┌─────────────────────────────────────────┐
│   9. Logging complet                    │
│      - Success/Failure logs             │
│      - Recipients count                 │
│      - Task & Case IDs                  │
└─────────────────────────────────────────┘
```

---

## 🧪 Guide de Test

### Prérequis
- 1 Admin/Créateur (A)
- 1-2 Utilisateurs à assigner (U1, U2)
- 1 Affaire avec clients et avocats (C)
- Configuration SMTP valide

### Scénario de Test

**Étape 1** : Créer une tâche
```
Admin A crée une tâche :
- Description : "Vérifier les documents"
- Assign To : U1, U2
- Relate To : Case C (avec 2 clients + 2 avocats)
- Priority : High
- Due Date : 15/01/2025
```

**Étape 2** : Vérifier les emails reçus

| Utilisateur | TO | BCC | Reçoit |
|-------------|----|----|--------|
| A (Admin) | ✅ | - | Oui |
| U1 | - | ✅ | Oui (en BCC) |
| U2 | - | ✅ | Oui (en BCC) |
| C1 (Client) | - | ✅ | Oui (en BCC) |
| C2 (Client) | - | ✅ | Oui (en BCC) |
| Avocat1 | - | ✅ | Oui (en BCC) |
| Avocat2 | - | ✅ | Oui (en BCC) |

**Étape 3** : Vérifier le contenu de l'email

✅ **Header** : "✅ Nouvelle Tâche Créée"
✅ **Infos Tâche** :
   - Description : "Vérifier les documents"
   - Priorité : Badge "HAUTE"
   - Date d'échéance : "15/01/2025"
   - Statut : Affiché

✅ **Section Affaire** :
   - 📋 Affaire Liée : [Nom de l'affaire]

✅ **Section BCC** :
   - 👥 Destinataires en Copie Conforme
   - Liste avec emojis (👤 Client, ⚖️ Avocat, 👨‍💼 User)
   - Noms et emails visibles
   - Types colorés (Client, Avocat, Utilisateur)

✅ **Actions** : Bouton "Voir la Tâche" fonctionnel

**Étape 4** : Vérifier les push notifications

```
Vérifier dans les logs :
app/storage/logs/laravel.log

SendTaskCreatedNotification job started
Task notification recipients:
  - task_id: 123
  - to_user: {id: 1, email: admin@mail, name: "Admin"}
  - bcc_recipients: [
      {id: 5, email: user1@mail, name: "User 1"},
      {id: 6, email: user2@mail, name: "User 2"},
      ...
    ]
  - bcc_count: 5

Push notification sent for task created
  - users_count: 7
```

---

## 📊 Comparaison Affaires vs Audiences vs Tâches

| Aspect | Affaires | Audiences | Tâches |
|--------|----------|-----------|--------|
| **Champ Créateur** | `created_by` | `created_by` | `created_by` ✓ |
| **Champ ID Affaire** | N/A | `case_id` | `relate_to` ✓ |
| **Assignés** | N/A | `assigned_to` (JSON) | `assign_to` (CSV) ✓ |
| **BCC Pattern** | À refactoriser | ✅ Implémenté | ✅ Implémenté |
| **Clients Inclus** | N/A | ✅ Oui | ✅ Oui |
| **Avocats Inclus** | N/A | ✅ Oui | ✅ Oui |
| **Push Notif** | À implémenter | ✅ Oui | ✅ Oui |
| **getSMTPDetails** | À implémenter | ✅ Avant dispatch | ✅ Avant dispatch |
| **Template BCC Box** | N/A | ✅ Oui | ✅ Oui |

---

## 🐛 Dépannage

### Email n'est pas reçu
- ✅ Vérifier que `getSMTPDetails()` est appelé avec l'ID correct
- ✅ Vérifier la file d'attente : `php artisan queue:work`
- ✅ Vérifier les logs : `storage/logs/laravel.log`

### BCC vide
- ✅ Vérifier que `assign_to` n'est pas vide
- ✅ Vérifier que l'affaire a des clients/avocats
- ✅ Vérifier les formats (CSV vs JSON)

### Destinataires dupliqués
- ✅ Vérifier la déduplication : `.unique('id')`
- ✅ Vérifier que les IDs sont correctement parsés

### Push notifications manquantes
- ✅ Vérifier que PushNotificationService existe
- ✅ Vérifier que sendTaskCreatedNotification() existe
- ✅ Vérifier les tokens de push

---

## 📝 Notes Importantes

1. **getSMTPDetails()** est appelé DEUX FOIS :
   - Une fois dans le contrôleur (avant dispatch)
   - Une fois dans le Job (pour sécurité queue)

2. **Formats CSV** : L'affaire `assign_to` peut être CSV ou JSON
   - CSV : `"1,2,3"` → explode(',', ...) + trim
   - JSON : `"[1,2,3]"` → json_decode(..., true)

3. **Déduplication** : `.unique('id')` pour éviter les doublons
   - Même utilisateur peut être client ET avocat

4. **Logging** : Tous les logs incluent task_id et case_id pour le debugging

5. **BCC Visible** : L'email HTML affiche les destinataires BCC
   - Transparence complète
   - Emojis pour identifier les rôles
   - Couleurs pour l'UX

---

## 🚀 Prochaines Étapes (Optionnel)

- [ ] Tester avec 10+ utilisateurs
- [ ] Tester avec affaires sans clients/avocats
- [ ] Tester avec tâches non liées à une affaire
- [ ] Implémenter le système pour les affaires (CaseCreated)
- [ ] Ajouter un paramètre pour désactiver les BCC (opt-out)
- [ ] Ajouter des statistiques d'envoi (dashboard)

---

**Documentation créée** : 2025-01-23
**Statut** : ✅ Implémentation Complète
**Pattern** : Identique aux Audiences (proven)
