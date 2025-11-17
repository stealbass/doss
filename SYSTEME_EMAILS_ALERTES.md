# 📧 Système Complet d'Emails et d'Alertes

## 🎯 Vue d'Ensemble

Ce document décrit le système complet d'emails automatiques et d'alertes pour les tâches et les abonnements implémenté dans Dossy Pro.

---

## ✨ Fonctionnalités Implémentées

### 1. 📋 Email de Notification de Tâche Assignée

**Quand**: Lors de la création d'une tâche (to-do)  
**À qui**: Tous les utilisateurs assignés à la tâche  
**Contenu**:
- Titre de la tâche
- Description
- Date de début
- Date d'échéance
- Priorité (badge coloré)
- Affaire liée (si applicable)
- Lien direct vers la tâche

**Design**: Email professionnel avec header vert Dossy Pro

---

### 2. ⚠️ Email d'Alerte Expiration Abonnement (Utilisateur)

**Quand**: 7, 3, et 1 jour avant l'expiration  
**À qui**: L'abonné dont l'abonnement va expirer  
**Contenu**:
- Compte à rebours (jours restants)
- Date d'expiration
- Détails du plan actuel (nom, prix, durée)
- Bouton "Renouveler Mon Abonnement"
- Liste des avantages à conserver

**Design**: Header rouge/orange pour attirer l'attention

---

### 3. ⚠️ Email d'Alerte Expiration Abonnement (Admin)

**Quand**: 7, 3, et 1 jour avant l'expiration  
**À qui**: Administrateur SaaS (email des paramètres)  
**Contenu**:
- Nom et email de l'abonné
- Plan et montant
- Date d'expiration
- Jours restants
- Lien vers le dashboard admin

**Design**: Header rouge/orange avec badge alerte

---

### 4. ✅ Email de Confirmation Souscription (Utilisateur)

**Quand**: Lors de la souscription à un abonnement  
**À qui**: L'abonné qui vient de souscrire  
**Contenu**:
- Plan souscrit
- Montant payé
- Date d'activation
- Date d'expiration
- Période de validité
- Méthode de paiement
- Liste des avantages
- Bouton "Accéder à Mon Compte"

**Design**: Header vert avec emoji célébration 🎉

---

### 5. 🎉 Email de Notification Souscription (Admin)

**Quand**: Lors de chaque nouvelle souscription  
**À qui**: Administrateur SaaS (email des paramètres)  
**Contenu**:
- Nom et email du nouvel abonné
- Plan souscrit
- Montant
- Date de souscription
- Date d'expiration
- Méthode de paiement
- Lien vers le dashboard admin

**Design**: Header vert avec emoji célébration 🎉

---

### 6. 🚫 Modal Alerte Abonnement Expiré

**Quand**: À chaque navigation après expiration de l'abonnement  
**Où**: Au milieu de l'écran sur toutes les pages (sauf Plans)  
**Contenu**:
- Message d'alerte en rouge
- Date d'expiration
- Explication accès limité
- Liste des fonctionnalités perdues
- Bouton "Renouveler Mon Abonnement"
- Ne peut pas être fermé (modal statique)

**Comportement**:
- Bloque l'accès à toutes les pages sauf la page Plans
- Utilisateur peut toujours voir son profil
- S'affiche automatiquement sur chaque page visitée

---

## 🏗️ Architecture Technique

### Mailables Créés

| Classe | Fichier | Usage |
|--------|---------|-------|
| **TaskAssignedNotification** | `app/Mail/TaskAssignedNotification.php` | Notification tâche assignée |
| **SubscriptionExpiringNotification** | `app/Mail/SubscriptionExpiringNotification.php` | Alerte expiration utilisateur |
| **SubscriptionConfirmation** | `app/Mail/SubscriptionConfirmation.php` | Confirmation souscription utilisateur |
| **AdminSubscriptionNotification** | `app/Mail/AdminSubscriptionNotification.php` | Notifications admin (nouveau/expirant) |

### Templates Email

| Template | Fichier | Description |
|----------|---------|-------------|
| **task_assigned** | `resources/views/email/task_assigned.blade.php` | Email tâche avec détails complets |
| **subscription_expiring** | `resources/views/email/subscription_expiring.blade.php` | Alerte avec compte à rebours |
| **subscription_confirmation** | `resources/views/email/subscription_confirmation.blade.php` | Confirmation avec récapitulatif |
| **admin_subscription** | `resources/views/email/admin_subscription.blade.php` | Notifications admin (dynamique) |

### Middleware

**CheckSubscriptionExpired**  
- Fichier: `app/Http/Middleware/CheckSubscriptionExpired.php`
- Enregistré dans: `bootstrap/app.php` (groupe 'web')
- Fonction: Vérifie si l'abonnement est expiré à chaque requête
- Action: Flash session 'subscription_expired' si expiré

### Composant Vue

**subscription-expired-alert**  
- Fichier: `resources/views/components/subscription-expired-alert.blade.php`
- Inclus dans: `resources/views/layouts/app.blade.php`
- Type: Modal Bootstrap statique
- Affichage: Conditionnel basé sur session flash

### Commande Artisan

**CheckExpiringSubscriptions**  
- Fichier: `app/Console/Commands/CheckExpiringSubscriptions.php`
- Commande: `php artisan subscriptions:check-expiring`
- Fonction: Vérifie les abonnements expirant dans 7, 3, et 1 jours
- Action: Envoie emails utilisateurs + admin

---

## 🔧 Intégrations

### 1. ToDoController

**Méthode modifiée**: `store()`  
**Fichier**: `app/Http/Controllers/ToDoController.php`

**Ajout**:
```php
// Configure SMTP
Utility::getSMTPDetails(Auth::user()->creatorId());

// Get assigned users
$assignedUserIds = $request->assign_to;

// Send email to each assigned user
foreach ($assignedUserIds as $userId) {
    $user = User::find($userId);
    Mail::to($user->email)->send(new TaskAssignedNotification($todo, $emailData));
}
```

**Déclenchement**: Automatique à chaque création de tâche

---

### 2. User Model

**Méthode modifiée**: `assignPlan()`  
**Fichier**: `app/Models/User.php`

**Ajout**:
```php
// Send subscription confirmation to user
Mail::to($user->email)->send(
    new SubscriptionConfirmation($user, $plan, $userEmailData)
);

// Send notification to admin
$adminEmail = Utility::getValByName('mail_from_address');
Mail::to($adminEmail)->send(
    new AdminSubscriptionNotification($user, $plan, $adminEmailData, 'new')
);
```

**Déclenchement**: Automatique lors de la souscription/renouvellement d'un plan

---

### 3. Bootstrap App

**Fichier**: `bootstrap/app.php`

**Ajout au groupe 'web'**:
```php
$middleware->appendToGroup('web', [
    // ... autres middlewares
    \App\Http\Middleware\CheckSubscriptionExpired::class,
]);
```

**Effet**: Vérification automatique sur toutes les routes web

---

### 4. Layout Principal

**Fichier**: `resources/views/layouts/app.blade.php`

**Ajout avant `</body>`**:
```blade
<!-- Subscription Expired Alert Modal -->
@include('components.subscription-expired-alert')
```

**Effet**: Modal s'affiche automatiquement si session flash 'subscription_expired'

---

## 📅 Configuration Cron

Pour que les alertes d'expiration fonctionnent automatiquement, configurez un cron job:

### Sur le Serveur

```bash
# Ouvrir crontab
crontab -e

# Ajouter cette ligne
0 8 * * * cd /chemin/vers/dossy && php artisan subscriptions:check-expiring >> /dev/null 2>&1
```

**Explication**:
- `0 8 * * *` : Tous les jours à 8h00
- `subscriptions:check-expiring` : Commande à exécuter
- `>> /dev/null 2>&1` : Supprime la sortie

### Alternative: Laravel Scheduler

**Fichier**: `routes/console.php`

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:check-expiring')->daily()->at('08:00');
```

Puis ajoutez dans le cron:
```bash
* * * * * cd /chemin/vers/dossy && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Tests

### Test 1: Email Tâche Assignée

```bash
1. Créer une nouvelle tâche depuis l'interface
2. Assigner la tâche à un ou plusieurs utilisateurs
3. Sauvegarder
4. Vérifier la boîte email des utilisateurs assignés
```

**Email attendu**:
- ✅ Header vert avec nom de l'assignateur
- ✅ Détails complets de la tâche
- ✅ Badge priorité coloré
- ✅ Lien "Voir la Tâche" fonctionnel

---

### Test 2: Email Expiration Abonnement

**Simulation manuelle**:

```sql
-- Modifier la date d'expiration d'un utilisateur pour dans 3 jours
UPDATE users 
SET plan_expire_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
WHERE id = 123;
```

```bash
# Exécuter la commande
php artisan subscriptions:check-expiring
```

**Emails attendus**:
- ✅ Email à l'utilisateur avec compte à rebours
- ✅ Email à l'admin avec détails de l'utilisateur

---

### Test 3: Email Confirmation Abonnement

```bash
1. Souscrire à un abonnement via n'importe quelle méthode de paiement
2. Compléter le paiement
```

**Emails attendus**:
- ✅ Email de confirmation au client
- ✅ Email de notification à l'admin

---

### Test 4: Modal Abonnement Expiré

**Simulation**:

```sql
-- Expirer l'abonnement d'un utilisateur
UPDATE users 
SET plan_expire_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
WHERE id = 123;
```

```bash
1. Se connecter avec cet utilisateur
2. Essayer d'accéder à n'importe quelle page
```

**Résultat attendu**:
- ✅ Modal s'affiche au milieu de l'écran
- ✅ Message "ABONNEMENT EXPIRÉ" en rouge
- ✅ Bouton "Renouveler Mon Abonnement" visible
- ✅ Clic sur le bouton mène à la page Plans

---

## 🎨 Design des Emails

### Palette de Couleurs

| État/Type | Couleur Principale | Usage |
|-----------|-------------------|-------|
| **Confirmation** | #28a745 (Vert Dossy Pro) | Headers positifs |
| **Alerte** | #ff6b6b (Rouge) | Headers d'avertissement |
| **Info** | #f8f9fa (Gris clair) | Backgrounds |
| **Priorité Haute** | #dc3545 (Rouge) | Badge tâche |
| **Priorité Moyenne** | #ffc107 (Orange) | Badge tâche |
| **Priorité Basse** | #17a2b8 (Bleu) | Badge tâche |

### Structure Commune

Tous les emails suivent cette structure:

1. **Header avec gradient** (vert ou rouge selon le type)
2. **Salutation** avec nom de l'utilisateur
3. **Contenu principal** dans un encadré coloré
4. **Tableau de détails** avec informations structurées
5. **Call-to-action** avec bouton gradient
6. **Message de fermeture**
7. **Footer** avec copyright Dossy Pro

### Responsive

- Largeur maximale: 600px
- Padding adaptatifs
- Tableaux compatibles email clients
- Inline CSS pour maximum de compatibilité

---

## 📊 Flux de Données

### Flux 1: Création de Tâche

```
Utilisateur crée tâche
    ↓
ToDoController@store()
    ↓
$todo->save()
    ↓
Utility::getSMTPDetails()
    ↓
Pour chaque utilisateur assigné:
    - Récupérer User
    - Préparer emailData
    - Mail::send(TaskAssignedNotification)
    ↓
Email reçu par utilisateurs assignés
```

---

### Flux 2: Souscription Abonnement

```
Utilisateur paie abonnement
    ↓
PaymentController (n'importe lequel)
    ↓
$user->assignPlan($planId)
    ↓
User Model: assignPlan()
    ↓
Configuration SMTP
    ↓
Email 1: Confirmation → Utilisateur
Email 2: Notification → Admin
    ↓
Emails reçus
```

---

### Flux 3: Vérification Expiration

```
Cron (8h00 chaque jour)
    ↓
php artisan subscriptions:check-expiring
    ↓
CheckExpiringSubscriptions Command
    ↓
Pour chaque jour d'alerte (7, 3, 1):
    - Trouver users avec expiration = aujourd'hui + X jours
    - Pour chaque user:
        * Email → Utilisateur
        * Email → Admin
    ↓
Emails envoyés
```

---

### Flux 4: Navigation avec Abonnement Expiré

```
Utilisateur se connecte
    ↓
Naviguer vers une page
    ↓
Middleware CheckSubscriptionExpired
    ↓
Vérifier plan_expire_date
    ↓
Si expiré ET route != Plans:
    - session()->flash('subscription_expired', true)
    ↓
View rendue
    ↓
Layout app.blade.php
    ↓
Component subscription-expired-alert
    ↓
Modal s'affiche
```

---

## 🔒 Sécurité

### Configuration SMTP

Toujours utiliser `Utility::getSMTPDetails()` avant d'envoyer un email:

```php
Utility::getSMTPDetails(Auth::user()->creatorId());
```

**Pourquoi**: Configure les paramètres SMTP depuis la base de données au lieu de .env

### Gestion des Erreurs

Tous les envois d'emails sont wrappés dans try-catch:

```php
try {
    Mail::to($email)->send(new Mailable($data));
    Log::info('Email sent successfully');
} catch (\Exception $e) {
    Log::error('Email sending failed', ['error' => $e->getMessage()]);
    // Don't block main functionality
}
```

**Avantage**: Si l'email échoue, l'action principale (création tâche, souscription) n'est pas bloquée

### Permissions Middleware

Le middleware CheckSubscriptionExpired:
- ✅ Skip pour super admin
- ✅ Vérifie uniquement les comptes 'company'
- ✅ Autorise accès page Plans même si expiré
- ✅ Autorise logout
- ✅ Flash session au lieu de redirect (meilleure UX)

---

## 📝 Variables d'Environnement Requises

Assurez-vous que ces paramètres sont configurés dans "Paramètres d'email":

- `mail_driver` : smtp
- `mail_host` : smtp.example.com
- `mail_port` : 587
- `mail_username` : contact@dossypro.com
- `mail_password` : ********
- `mail_encryption` : tls
- `mail_from_address` : contact@dossypro.com
- `mail_from_name` : Dossy Pro

**Email Admin**: `mail_from_address` est utilisé pour recevoir les notifications admin

---

## 🚀 Déploiement

### Étapes sur le Serveur

```bash
# 1. Tirer les modifications
git pull origin main

# 2. Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 3. Installer la commande cron
crontab -e
# Ajouter: 0 8 * * * cd /var/www/dossy && php artisan subscriptions:check-expiring

# 4. Tester la commande manuellement
php artisan subscriptions:check-expiring

# 5. Vérifier les logs
tail -f storage/logs/laravel.log
```

---

## 🎓 Guide Utilisateur Final

### Pour les Administrateurs

1. **Configuration Initiale**:
   - Vérifier les paramètres SMTP dans "Paramètres d'email"
   - S'assurer que l'email admin est correct
   - Tester l'envoi d'un email test

2. **Surveillance**:
   - Recevoir emails quotidiens pour abonnements expirant
   - Recevoir notification pour chaque nouveau abonnement
   - Consulter les logs si nécessaire

3. **Maintenance**:
   - Vérifier que le cron tourne quotidiennement
   - Monitorer les bounced emails
   - Ajuster la fréquence d'alerte si nécessaire

### Pour les Utilisateurs

1. **Tâches**:
   - Recevoir email immédiatement quand une tâche est assignée
   - Cliquer sur le lien pour voir la tâche
   - Gérer les tâches depuis l'interface

2. **Abonnements**:
   - Recevoir email de confirmation après souscription
   - Recevoir alertes 7, 3, et 1 jours avant expiration
   - Renouveler via le lien dans l'email ou le modal

3. **Abonnement Expiré**:
   - Modal s'affiche automatiquement
   - Cliquer sur "Renouveler Mon Abonnement"
   - Choisir un plan et payer

---

## 📞 Support

En cas de problème avec les emails:

1. **Vérifier les logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i email
   ```

2. **Tester la commande manuellement**:
   ```bash
   php artisan subscriptions:check-expiring
   ```

3. **Vérifier la configuration SMTP**:
   - Page "Paramètres d'email"
   - Envoyer un email test

4. **Vérifier le cron**:
   ```bash
   crontab -l
   ```

---

**Documentation créée le**: {{ date('d/m/Y') }}  
**Version**: 1.0  
**Auteur**: GenSpark AI Developer

---

**Système complet fonctionnel et prêt pour la production! 🎉**
