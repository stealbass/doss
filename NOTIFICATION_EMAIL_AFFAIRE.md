# 📧 Notification Email Automatique - Nouvelle Affaire

**Date**: 16 Novembre 2025  
**Commit**: `600045d5`  
**Branche**: `genspark_ai_developer`

---

## 🎯 Fonctionnalité

Envoi **automatique** d'un email de notification lorsqu'une nouvelle affaire (case) est créée dans le système.

**Destinataire**: L'utilisateur créateur (company ou advocate principal)  
**Contenu**: Résumé de l'affaire avec lien pour voir les détails complets

---

## ✨ Caractéristiques

### Email Professionnel
- ✅ Design vert cohérent avec la marque Dossy Pro
- ✅ Responsive et compatible tous clients email
- ✅ Lien cliquable pour accéder à l'affaire
- ✅ Tableau structuré avec les clients (plaignants)

### Informations Incluses
- ✅ Titre de l'affaire
- ✅ Description (si présente)
- ✅ Date de dépôt
- ✅ Année
- ✅ Numéro d'affaire
- ✅ Tribunal
- ✅ **Liste des clients (plaignants)** en tableau
- ✅ **Lien direct vers l'affaire**

### Automatisation
- ✅ Envoi automatique après création
- ✅ Utilise les paramètres SMTP configurés
- ✅ Ne bloque pas si l'email échoue
- ✅ Logs détaillés pour suivi

---

## 📦 Fichiers Créés

### 1. Classe Mailable: `app/Mail/NewCaseNotification.php`

```php
<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class NewCaseNotification extends Mailable
{
    public $case;
    public $emailData;
    
    public function __construct($case, $emailData)
    {
        $this->case = $case;
        $this->emailData = $emailData;
    }

    public function build()
    {
        return $this->subject('Nouvelle affaire créée: ' . $this->case->title)
            ->view('email.new_case')
            ->with($this->emailData);
    }
}
```

**Caractéristiques**:
- Sujet dynamique avec le titre de l'affaire
- Passe toutes les données nécessaires au template
- Structure cohérente avec les autres emails

---

### 2. Template Email: `resources/views/email/new_case.blade.php`

**Structure Complète**:

#### En-tête Personnalisé
```blade
<div style="background: linear-gradient(135deg, #28a745 0%, #218838 100%);">
    <h1>{{ $recipientName }}</h1>  <!-- Nom du destinataire -->
    <h2>📂 Nouvelle Affaire Créée</h2>
    <p>📅 {{ date('d/m/Y à H:i') }}</p>
</div>
```

#### Message d'Introduction
```blade
<div style="border-left: 5px solid #28a745;">
    ✅ Une nouvelle affaire a été créée avec succès dans votre système.
</div>
```

#### Titre de l'Affaire
```blade
<div style="border: 2px solid #e8f5e9;">
    <h3>📋 Titre de l'Affaire</h3>
    <p>{{ $case->title }}</p>
    @if($case->description)
        <p>{{ $case->description }}</p>
    @endif
</div>
```

#### Tableau des Clients (Plaignants)
```blade
<table>
    <thead>
        <tr style="background: linear-gradient(135deg, #28a745 0%, #20923d 100%);">
            <th>Nom du Client</th>
            <th>Type de Partie</th>
            <th>Rôle</th>
        </tr>
    </thead>
    <tbody>
        @foreach($clients as $index => $client)
        <tr style="background-color: {{ $index % 2 == 0 ? '#f8fff9' : '#ffffff' }};">
            <td>{{ $client['name'] }}</td>
            <td>
                <span style="background-color: #e8f5e9;">Plaignant</span>
            </td>
            <td>Partie principale</td>
        </tr>
        @endforeach
    </tbody>
</table>
```

#### Détails Supplémentaires
```blade
<table>
    <tr>
        <td>📅 Date de dépôt:</td>
        <td>{{ date('d/m/Y', strtotime($case->filing_date)) }}</td>
    </tr>
    <tr>
        <td>📆 Année:</td>
        <td>{{ $case->year }}</td>
    </tr>
    <tr>
        <td>🔢 Numéro d'affaire:</td>
        <td>{{ $case->casenumber }}</td>
    </tr>
    <tr>
        <td>⚖️ Tribunal:</td>
        <td>{{ $courtName }}</td>
    </tr>
</table>
```

#### Bouton d'Action
```blade
<a href="{{ $caseUrl }}" 
   style="padding: 15px 40px; 
          background: linear-gradient(135deg, #28a745 0%, #20923d 100%); 
          color: #ffffff; 
          border-radius: 25px;">
    📂 Voir l'Affaire Complète
</a>
```

#### Footer
```blade
<div style="background: linear-gradient(to right, #f8fff9, #e8f5e9);">
    <p>Bonne gestion de votre affaire! ⚖️</p>
    <p>📅 Notification envoyée le {{ date('d/m/Y à H:i') }}</p>
</div>

<div style="background-color: #f4f4f4;">
    <p>💡 Cet email a été envoyé automatiquement</p>
    <p>© {{ date('Y') }} <a href="https://www.dossypro.com">Dossy Pro</a></p>
</div>
```

---

### 3. Modification du Contrôleur: `app/Http/Controllers/CaseController.php`

**Ajout dans la méthode `store()`**:

```php
// Après $case->save()

try {
    // 1. Configurer SMTP depuis la BD
    Utility::getSMTPDetails(Auth::user()->creatorId());
    
    // 2. Déterminer le destinataire
    $creator = User::find(Auth::user()->creatorId());
    
    if ($creator->type == 'company') {
        $recipientEmail = $creator->email;
        $recipientName = Utility::getcompanyValByName('name');
    } else {
        $recipientEmail = $creator->email;
        $recipientName = $creator->name;
    }
    
    // 3. Préparer les clients (plaignants)
    $clients = [];
    if (!empty($case->your_party_name)) {
        $your_parties = json_decode($case->your_party_name, true);
        foreach ($your_parties as $party) {
            if (isset($party['name'])) {
                $clients[] = [
                    'name' => $party['name'],
                    'client_id' => $party['clients'] ?? null
                ];
            }
        }
    }
    
    // 4. Récupérer le nom du tribunal
    $courtName = '';
    if ($case->court) {
        $court = Court::find($case->court);
        if ($court) {
            $courtName = $court->name;
        }
    }
    
    // 5. URL pour voir l'affaire
    $caseUrl = route('cases.show', $case->id);
    
    // 6. Préparer les données
    $emailData = [
        'case' => $case,
        'recipientName' => $recipientName,
        'clients' => $clients,
        'courtName' => $courtName,
        'caseUrl' => $caseUrl,
    ];
    
    // 7. Envoyer l'email
    if (!empty($recipientEmail)) {
        Mail::to($recipientEmail)->send(new NewCaseNotification($case, $emailData));
        \Log::info('Email notification nouvelle affaire envoyé');
    }
    
} catch (\Exception $e) {
    \Log::error('Erreur envoi email notification affaire');
    // Ne bloque pas la création de l'affaire
}
```

**Imports ajoutés**:
```php
use Illuminate\Support\Facades\Mail;
use App\Mail\NewCaseNotification;
```

---

## 🎨 Aperçu de l'Email

```
┌──────────────────────────────────────┐
│  ┌────────────────────────────┐      │
│  │    CABINET MARTIN          │      │ ← Nom du destinataire
│  └────────────────────────────┘      │
│    📂 Nouvelle Affaire Créée         │
│    📅 16/11/2025 à 18:30             │
│  (Fond gradient vert)                │
├──────────────────────────────────────┤
│  ✅ Nouvelle affaire créée...        │
├──────────────────────────────────────┤
│  📋 Titre de l'Affaire               │
│  Divorce - Mme. DUPONT vs M. MARTIN  │
│  Description: Demande de divorce...  │
├──────────────────────────────────────┤
│  👤 Informations du Client           │
│  ┌─────────┬──────┬────────┐        │
│  │ Nom     │ Type │ Rôle   │        │
│  ├─────────┼──────┼────────┤        │
│  │ Mme.    │Plaig-│Partie  │        │
│  │ DUPONT  │nant  │princi. │        │
│  └─────────┴──────┴────────┘        │
├──────────────────────────────────────┤
│  📅 Date de dépôt: 15/11/2025        │
│  📆 Année: 2025                      │
│  🔢 Numéro: 2025/001                 │
│  ⚖️ Tribunal: TGI Paris              │
├──────────────────────────────────────┤
│  ┌────────────────────────┐          │
│  │  📂 Voir l'Affaire    │          │
│  │     Complète          │          │
│  └────────────────────────┘          │
│  (Bouton vert cliquable)             │
├──────────────────────────────────────┤
│  💡 Astuce: Cliquez pour accéder...  │
├──────────────────────────────────────┤
│  Bonne gestion de votre affaire! ⚖️  │
│  📅 Notification envoyée le...       │
├──────────────────────────────────────┤
│  💡 Email automatique                │
│  © 2025 Dossy Pro                    │
└──────────────────────────────────────┘
```

---

## 🔄 Flux de Fonctionnement

### 1. Création de l'Affaire
```
Utilisateur → Remplit formulaire → Clique "Créer"
```

### 2. Sauvegarde dans la BD
```
CaseController@store() → Validation → $case->save()
```

### 3. Envoi Email Automatique
```
Configuration SMTP → Préparation données → Envoi email → Logs
```

### 4. Réception de l'Email
```
Email reçu → Clic sur bouton → Redirection vers l'affaire
```

---

## 📊 Données Transmises

### Variables Disponibles dans le Template

```php
$emailData = [
    'case' => $case,              // Objet Case complet
    'recipientName' => 'Cabinet MARTIN',  // Nom du destinataire
    'clients' => [                // Tableau des clients
        [
            'name' => 'Mme. DUPONT',
            'client_id' => 5
        ],
        // ...
    ],
    'courtName' => 'TGI Paris',   // Nom du tribunal
    'caseUrl' => 'https://...',   // URL vers l'affaire
];
```

### Propriétés du Case ($case)

- `$case->title` - Titre de l'affaire
- `$case->description` - Description
- `$case->filing_date` - Date de dépôt
- `$case->year` - Année
- `$case->casenumber` - Numéro d'affaire
- `$case->court` - ID du tribunal
- `$case->your_party_name` - JSON des plaignants
- `$case->opp_party_name` - JSON des parties adverses

---

## ⚙️ Configuration

### Prérequis

1. ✅ **Paramètres SMTP configurés** dans l'interface
2. ✅ **Email du créateur** (company ou advocate) valide
3. ✅ **Route 'cases.show'** définie pour l'URL de l'affaire

### Paramètres Automatiques

- **De**: Utilise les paramètres SMTP configurés
- **À**: Email du créateur de l'affaire (company ou advocate principal)
- **Sujet**: "Nouvelle affaire créée: [Titre de l'affaire]"

---

## 🧪 Tests

### Scénario 1: Création par une Entreprise

**Données**:
- Créateur: Company (ID: 1, Email: contact@cabinet.com)
- Affaire: "Divorce - Dupont vs Martin"
- Clients: Mme. DUPONT

**Résultat Attendu**:
- ✅ Email envoyé à: contact@cabinet.com
- ✅ En-tête: Nom de l'entreprise
- ✅ Tableau: 1 ligne (Mme. DUPONT)
- ✅ Lien: Route vers l'affaire

### Scénario 2: Création par un Avocat

**Données**:
- Créateur: Avocat (ID: 5, Email: avocat@example.com)
- Affaire: "Contentieux commercial - Société A"
- Clients: Société A, M. CEO

**Résultat Attendu**:
- ✅ Email envoyé à: avocat@example.com
- ✅ En-tête: Nom de l'avocat
- ✅ Tableau: 2 lignes (Société A, M. CEO)
- ✅ Lien: Route vers l'affaire

### Scénario 3: Affaire sans Client

**Données**:
- Affaire créée sans client associé

**Résultat Attendu**:
- ✅ Email envoyé normalement
- ✅ Tableau: Message "Aucun client associé"
- ✅ Autres informations présentes

---

## 📝 Logs

### Logs de Succès

```
[INFO] Email notification nouvelle affaire envoyé
{
    "case_id": 123,
    "to": "contact@cabinet.com",
    "title": "Divorce - Dupont vs Martin"
}
```

### Logs d'Erreur

```
[ERROR] Erreur envoi email notification affaire
{
    "case_id": 123,
    "message": "SMTP connection failed"
}
```

**Note**: L'échec de l'email **ne bloque PAS** la création de l'affaire.

---

## 🔧 Personnalisation Possible

### Modifier le Template

**Fichier**: `resources/views/email/new_case.blade.php`

**Exemples de personnalisation**:

1. **Ajouter le type d'affaire**:
```blade
@if($case->casetype)
<tr>
    <td>📂 Type d'affaire:</td>
    <td>{{ $case->casetype }}</td>
</tr>
@endif
```

2. **Ajouter les avocats assignés**:
```php
// Dans le contrôleur
$advocates = [];
if ($case->advocates) {
    $advocateIds = explode(',', $case->advocates);
    foreach ($advocateIds as $advId) {
        $adv = User::find($advId);
        if ($adv) {
            $advocates[] = $adv->name;
        }
    }
}
$emailData['advocates'] = $advocates;
```

```blade
<!-- Dans le template -->
@if(count($advocates) > 0)
<tr>
    <td>👨‍⚖️ Avocats:</td>
    <td>{{ implode(', ', $advocates) }}</td>
</tr>
@endif
```

3. **Ajouter les parties adverses**:
```php
// Dans le contrôleur
$oppParties = [];
if (!empty($case->opp_party_name)) {
    $opp = json_decode($case->opp_party_name, true);
    foreach ($opp as $party) {
        if (isset($party['name'])) {
            $oppParties[] = $party['name'];
        }
    }
}
$emailData['oppParties'] = $oppParties;
```

---

## ✅ Avantages

### Pour l'Utilisateur (Company/Advocate)
- ✅ **Notification immédiate** de la création
- ✅ **Résumé clair** de l'affaire
- ✅ **Accès rapide** via le lien
- ✅ **Archivage email** pour référence

### Pour le Système
- ✅ **Automatisation** complète
- ✅ **Cohérence** avec les autres emails
- ✅ **Logs** pour traçabilité
- ✅ **Robustesse** (ne bloque pas si échec)

### Pour les Clients
- ✅ **Transparence**: Le créateur est informé
- ✅ **Rapidité**: Notification en temps réel

---

## 🚀 Déploiement

### Étape 1: Merger le PR #7

Le commit `600045d5` contient cette fonctionnalité.

### Étape 2: Déployer

```bash
git pull origin main
php artisan view:clear
php artisan cache:clear
```

### Étape 3: Tester

1. Créer une nouvelle affaire
2. Vérifier que l'email est reçu
3. Cliquer sur le bouton dans l'email
4. Vérifier que ça redirige vers l'affaire

---

## 📚 Documentation Complémentaire

### Routes Utilisées

```php
// Route pour voir une affaire
Route::get('/cases/{id}', [CaseController::class, 'show'])
    ->name('cases.show');
```

### Modèles Utilisés

- `Cases` - L'affaire
- `Court` - Le tribunal
- `User` - Créateur et clients
- `Utility` - Configuration SMTP

---

## 🎉 Résumé

**Fonctionnalité Complète**:
- ✅ Email automatique après création d'affaire
- ✅ Design professionnel vert Dossy Pro
- ✅ Tableau des clients (plaignants)
- ✅ Lien pour voir l'affaire
- ✅ Utilise la config SMTP de la BD
- ✅ Ne bloque pas si échec
- ✅ Logs détaillés

**Commit**: `600045d5`  
**Pull Request**: #7  
**Prêt à Merger**: ✅ Oui

---

## 💬 Note pour l'Utilisateur

> Maintenant, chaque fois qu'une nouvelle affaire est créée, un email est automatiquement envoyé au créateur (vous ou votre entreprise) avec un résumé complet et un lien direct pour voir l'affaire. Le tableau montre tous les clients (plaignants) associés à l'affaire. C'est pratique pour garder une trace et avoir une notification immédiate!
