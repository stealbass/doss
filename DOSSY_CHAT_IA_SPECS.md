# 📱 DOSSY CHAT IA - Spécifications Complètes

## 🎯 Informations Générales

**Nom de l'application** : DOSSY CHAT IA  
**Slogan** : "Analyse et Assistant Juridique, Fiscal & Social"  
**Couleur dominante** : Vert  
**Langues** : Français, Anglais  
**Plateformes** : Android, iOS

---

## 🎨 Design & UX

### Principes de Design
- ✅ UI/UX impeccable et moderne
- ✅ Couleur dominante : Vert (#00C853, #4CAF50, #2E7D32)
- ✅ Design épuré et professionnel
- ✅ Animations fluides et micro-interactions
- ✅ Dark mode support
- ✅ Responsive design (mobile, tablet)

### Thème Couleur
```dart
Primary Green: #00C853
Secondary Green: #4CAF50
Dark Green: #2E7D32
Light Green: #A5D6A7
Success: #00C853
Warning: #FFC107
Error: #F44336
Background: #F5F5F5
Card: #FFFFFF
```

---

## 👥 Types d'Utilisateurs

### 1. **Étudiant en Droit**
- Fiches d'arrêts automatiques
- Fiches de révision
- Générateur de problématiques et plans
- QCM personnalisés
- Mode révision active (questions guidées)
- Enregistrement audio de cours (plans 5000+)

### 2. **Avocat / Juriste**
- Analyse de documents juridiques
- Anonymisation automatique des données clients
- Accès bibliothèque juridique par juridiction
- Génération de contrats et actes
- Veille juridique

### 3. **Entreprise**
- Multi-comptes (DG, RH, Comptable)
- Assistant Fiscal & Social
- Modèles de contrats téléchargeables (Word)
- Simulateurs (coût embauche, indemnités)
- Alertes juridiques par email/WhatsApp
- Prix négociable

### 4. **Admin**
- Gestion contenu (textes juridiques, modèles)
- Gestion juridictions (pays africains)
- Création/gestion coupons de promotion
- Gestion abonnements
- Email marketing et rétention
- Analytics et KPIs
- Rédaction Terms & Conditions / Privacy Policy

---

## 🌍 Juridictions Supportées

### Pays Cibles (Extensible via Admin)
- 🇨🇲 Cameroun
- 🇨🇮 Côte d'Ivoire
- 🇸🇳 Sénégal
- 🇲🇱 Mali
- 🇹🇬 Togo
- 🇨🇩 RDC (Congo)
- + Autres pays à ajouter via admin

### Lors de l'Inscription
L'utilisateur **doit choisir sa juridiction** (pays) pour :
- Charger les textes juridiques spécifiques
- Adapter les réponses AI au droit local
- Utiliser les conventions et codes locaux

---

## 💳 Plans d'Abonnement

### Cycles de Paiement
- ✅ Mensuel
- ✅ Annuel (réduction)

### Plans Proposés

| Plan | Prix/mois | Recherches | Analyses AI | PDF | Audio | Modèles |
|------|-----------|------------|-------------|-----|-------|---------|
| **Gratuit** | 0 FCFA | 10 | 10 | 5 | ❌ | ❌ |
| **Étudiant** | 2,000 FCFA | 100 | 50 | 30 | ❌ | Limité |
| **Pro** | 5,000 FCFA | 500 | 200 | 100 | ✅ | ✅ |
| **Cabinet** | 15,000 FCFA | Illimité | 1000 | 500 | ✅ | ✅ |
| **Entreprise** | À négocier | Illimité | Illimité | Illimité | ✅ | ✅ Premium |

### Fonctionnalités par Plan

#### Plan Gratuit
- Chat AI basique (10 analyses/mois)
- Recherche bibliothèque (10/mois)
- Téléchargement PDF (5/mois)

#### Plan Étudiant (2,000 FCFA/mois)
- Générateur fiches d'arrêts (50/mois)
- Générateur fiches de révision (50/mois)
- Générateur QCM (30/mois)
- Générateur plans dissertation (20/mois)
- Mode révision active

#### Plan Pro (5,000 FCFA/mois)
- Toutes fonctionnalités Étudiant
- **Enregistrement audio cours** → Transcription + Fiche auto
- Accès modèles de contrats
- Anonymisation automatique
- Recherche avancée

#### Plan Cabinet (15,000 FCFA/mois)
- Toutes fonctionnalités Pro
- Multi-utilisateurs (3 comptes)
- Bibliothèque modèles complète
- Veille juridique

#### Plan Entreprise (Prix négociable)
- Tout illimité
- Multi-comptes illimités
- Modèles Word téléchargeables
- Assistant Fiscal & Social complet
- Alertes Email/WhatsApp
- Simulateurs (Paie, Licenciement)
- Support dédié

---

## 💬 Interface Chat

### Fonctionnalités Chat

#### Au-dessus du Chat
**Suggestions de Prompts** (exemples) :
```
"Explique-moi la prescription acquisitive"
"Génère une fiche d'arrêt sur..."
"Crée un QCM sur le droit des contrats"
"Quelle est la durée du préavis de licenciement ?"
"Génère un brouillon de contrat de travail CDI"
```

#### Zone de Chat
- Messages user et AI
- Typing indicator
- Support Markdown
- Code blocks pour actes juridiques
- Boutons d'action rapide (Copier, Télécharger PDF)

#### En-dessous du Chat
**Disclaimer** :
```
⚠️ Vérifiez toujours les réponses avec un professionnel.
L'IA peut faire des erreurs. Ce n'est pas un conseil juridique officiel.
```

### Mode Révision Active (Étudiants)
Au lieu de donner directement la réponse, l'IA pose des questions :

**Exemple** :
```
User: "Qu'est-ce qu'un contrat synallagmatique ?"

AI: "Excellente question ! Avant de te répondre :
1. Sais-tu ce que signifie 'obligations réciproques' ?
2. Peux-tu me donner un exemple de contrat dans la vie quotidienne ?
3. Selon toi, dans un contrat de vente, qui a des obligations ?"
```

---

## 🎓 Outils Étudiant

### 1. Générateur de Fiches d'Arrêts
**Input** : URL arrêt ou texte copié-collé  
**Output** : Fiche structurée
```
FICHE D'ARRÊT
━━━━━━━━━━━━━━━━━━━━━
📅 Date: ...
🏛️ Juridiction: ...
📝 Numéro: ...
👥 Parties: ... vs ...
⚖️ Faits: ...
❓ Procédure: ...
💡 Problème de droit: ...
📖 Solution: ...
🎯 Portée: ...
```

### 2. Générateur de Fiches de Révision
**Input** : Thème juridique  
**Output** : Fiche synthétique avec :
- Définitions clés
- Principes essentiels
- Exceptions
- Jurisprudence importante
- Schémas/Mind maps

### 3. Générateur de Problématiques et Plans
**Input** : Sujet de dissertation  
**Output** :
- Problématique juridique
- Plan détaillé (I. A. 1. 2. B. 1. 2. II. etc.)
- Idées de développement

### 4. Générateur de QCM
**Input** : Thème juridique  
**Output** : QCM de 10-20 questions avec :
- Questions à choix multiples
- Correction détaillée
- Références textes de loi

### 5. Transcription Audio → Fiche
**Pour plans 5000+ uniquement**  
**Input** : Enregistrement audio du cours  
**Output** : Fiche de révision automatique

Workflow :
1. User enregistre le cours (audio)
2. Upload vers backend
3. Transcription automatique (Whisper AI)
4. Génération fiche de révision structurée
5. Téléchargement PDF

---

## 🔒 Anonymisation Automatique (Avocats)

### Problème
Les avocats ne peuvent pas partager des documents clients avec des noms réels vers une IA externe.

### Solution
**Anonymisation avant envoi à l'IA** :

**Avant** :
```
M. Jean DUPONT poursuit Mme Marie MARTIN pour...
```

**Après anonymisation** :
```
M. [X] poursuit Mme [Y] pour...
```

**Workflow** :
1. User upload document ou copie texte
2. Détection automatique des noms propres (NLP)
3. Remplacement par `[X]`, `[Y]`, `[Z]`, etc.
4. Envoi du texte anonymisé à l'IA
5. Réponse de l'IA avec `[X]`, `[Y]`
6. Option : Ré-identification dans la réponse finale

**Toggle dans Settings** :
```
[✓] Activer l'anonymisation automatique
```

---

## 🎁 Système de Parrainage

### Règles
- Chaque utilisateur a un **code de parrainage unique** (ex: `DOSSY-A7B9C2`)
- Partage du code via lien ou copier-coller
- **Validation** : Lorsque **10 filleuls** ont souscrit un abonnement payant
- **Récompense** : 1 mois gratuit du plan actuel

### UI
```
┌──────────────────────────────────────────┐
│ 🎁 PROGRAMME DE PARRAINAGE               │
├──────────────────────────────────────────┤
│ Ton code : DOSSY-A7B9C2                  │
│ [Copier]  [Partager]                     │
│                                          │
│ Progression : 7/10 parrainages validés   │
│ ████████░░ 70%                           │
│                                          │
│ Prochain palier : 1 mois gratuit 🎉      │
└──────────────────────────────────────────┘
```

---

## 📧 Système Email (Admin)

### Email Marketing
L'admin peut créer et envoyer des emails depuis l'espace admin Dossy Pro.

### Templates Email

#### 1. Email de Rétention (5 jours d'inactivité)
```
Objet : Ton partiel approche, voici un QCM pour t'entraîner 📚

Bonjour [Prénom],

On remarque que tu n'as pas ouvert DOSSY CHAT IA depuis 5 jours.
Tes révisions avancent-elles bien ?

Pour t'aider, voici un QCM sur [Thème] :
[Lien QCM]

Bon courage ! 💪
L'équipe DOSSY
```

#### 2. Email Promotion
```
Objet : 🔥 -30% sur le Plan Pro jusqu'à dimanche !

Bonjour [Prénom],

Pour fêter [Événement], profite de -30% sur le Plan Pro.
Code promo : PROMO30

[S'abonner maintenant]
```

#### 3. Email Nouveaux Inscrits
```
Objet : Bienvenue sur DOSSY CHAT IA ! 🎉

Bonjour [Prénom],

Merci d'avoir rejoint DOSSY CHAT IA.
Voici comment démarrer :
1. Choisis ta juridiction
2. Pose ta première question
3. Explore les outils étudiants

[Commencer]
```

### Déclencheurs Automatiques
- Inscription → Email bienvenue (immédiat)
- Inactivité 5 jours → Email rétention
- 3 jours avant expiration abonnement → Email renouvellement
- Après expiration → Email re-engagement (-20%)

---

## 🏢 Fonctionnalités Entreprise

### Multi-Comptes
Une entreprise peut créer plusieurs comptes liés :
- **DG** : Accès complet, analytics
- **RH** : Outils RH, contrats de travail
- **Comptable** : Assistant fiscal, simulateurs paie

### Modèles Téléchargeables (Word/Excel)

#### Catégorie RH & Paie
- Contrat CDI / CDD
- Contrat consultant
- Lettre de licenciement
- Règlement intérieur
- **Excel** : Simulateur coût embauche
- **Excel** : Calculateur indemnités licenciement

#### Catégorie Fiscale
- Lettre de réclamation contentieuse
- Demande de moratoire
- Demande de sursis de paiement
- Checklist contrôle fiscal (PDF)
- Calendrier fiscal PME (PDF)

#### Catégorie Corporate
- PV Assemblée Générale
- Rapport de gestion
- Conventions réglementées

### Veille Juridique (Alertes)
L'entreprise s'abonne à des alertes par secteur :

**Exemple** :
```
🔔 Alerte BTP : Le salaire minimum des ouvriers de chantier
vient d'augmenter de 5%. Voici la nouvelle grille à appliquer
dès lundi.

[Télécharger la grille]
```

**Canaux** :
- Email
- WhatsApp (via API WhatsApp Business)
- Push notification

---

## 📚 Bibliothèque de Contenu (Admin)

### Input Data (Ce que l'admin charge)

#### 1. Textes Juridiques par Juridiction
- Code Général des Impôts (CGI)
- Loi de Finances (année en cours)
- Code de Prévoyance Sociale
- Conventions Collectives
- Actes Uniformes OHADA
- Code de la Famille (par pays)
- Code du Travail (par pays)

#### 2. Modèles d'Actes et Contrats
- Statuts de sociétés (SARL, SA, SAS)
- Contrats commerciaux
- Contrats de travail
- Baux
- PV Assemblées

#### 3. Ressources Fiscales
- Barème impôt sur salaires
- Notes circulaires DGI
- Instructions administratives
- Conventions fiscales internationales

#### 4. Outils Excel/PDF
- Simulateurs
- Checklists
- Calendriers

### Interface Admin
```
┌────────────────────────────────────────────┐
│ 📚 GESTION BIBLIOTHÈQUE JURIDIQUE          │
├────────────────────────────────────────────┤
│ Juridiction : [Cameroun ▼]                 │
│                                            │
│ ┌──────────────────────────────────────┐   │
│ │ 📁 Code Général des Impôts           │   │
│ │    Version 2025 (Mise à jour)        │   │
│ │    [Modifier] [Supprimer]            │   │
│ └──────────────────────────────────────┘   │
│                                            │
│ [+ Ajouter un nouveau texte]               │
└────────────────────────────────────────────┘
```

---

## 🤖 Prompt Engineering AI

### Contexte Juridiction
Lors du chat, l'IA reçoit ce contexte :

```
Tu es un assistant juridique expert en droit africain.
L'utilisateur a sélectionné la juridiction : [Cameroun].

Règles strictes :
1. Si question sur création d'entreprise → Utilise EXCLUSIVEMENT
   les Actes Uniformes OHADA.
2. Si question sur divorce et juridiction = Sénégal →
   Utilise EXCLUSIVEMENT le Code de la Famille du Sénégal.
3. Ne JAMAIS mélanger les juridictions.
4. Cite toujours les articles de loi avec les références exactes.
5. Si tu ne sais pas, dis "Je n'ai pas cette information
   pour [pays]" au lieu d'inventer.

Documents disponibles :
[Liste des textes chargés pour cette juridiction]
```

---

## 🎟️ Système de Coupons (Admin)

### Création Coupon
```
┌────────────────────────────────────────────┐
│ 🎫 CRÉER UN COUPON                         │
├────────────────────────────────────────────┤
│ Code : [PROMO30________]                   │
│ Type : [Pourcentage ▼] 30%                 │
│ Plans : [☑ Étudiant] [☑ Pro] [ ] Cabinet   │
│ Date début : [01/01/2025]                  │
│ Date fin : [31/01/2025]                    │
│ Utilisations max : [100]                   │
│ Statut : [✓ Actif]                         │
│                                            │
│ [Créer le coupon]                          │
└────────────────────────────────────────────┘
```

### Validation Coupon (App)
User entre le code → Réduction appliquée avant paiement.

---

## 📊 Admin Dashboard

### Analytics à Afficher
- Total utilisateurs inscrits
- Utilisateurs actifs (7 jours, 30 jours)
- Taux de conversion (gratuit → payant)
- Churn rate (désabonnements)
- MRR (Monthly Recurring Revenue)
- Utilisateurs par juridiction
- Dates d'inscription récentes

### Actions Admin
- ✅ Activer/Désactiver abonnement user
- ✅ Créer/Modifier/Supprimer coupons
- ✅ Envoyer email promotionnel
- ✅ Voir dernières inscriptions
- ✅ Gérer juridictions (ajouter pays)
- ✅ Charger textes juridiques
- ✅ Rédiger Terms & Privacy Policy

---

## 🔔 Tracking & Analytics (Admin)

### Intégration APIs Tracking
L'admin peut intégrer :
- **Google Analytics** : Tracking web/app
- **Mixpanel** : Product analytics
- **Amplitude** : User behavior
- **Firebase Analytics** : Mobile events

### KPIs à Mesurer
- DAU/MAU (Daily/Monthly Active Users)
- Retention rate (J1, J7, J30)
- Conversion funnel (inscription → payant)
- Taux d'utilisation chat
- Taux d'utilisation outils étudiants
- Churn rate par plan

---

## ⚖️ Terms & Conditions / Privacy Policy

### Lors de l'Inscription
Checkbox obligatoire :
```
☑ J'accepte les Conditions Générales d'Utilisation
   et la Politique de Confidentialité
```

### Interface Admin
```
┌────────────────────────────────────────────┐
│ 📄 GESTION LÉGALE                          │
├────────────────────────────────────────────┤
│ ┌──────────────────────────────────────┐   │
│ │ Terms & Conditions (FR)              │   │
│ │ [Éditeur WYSIWYG]                    │   │
│ │ Dernière mise à jour : 01/01/2025    │   │
│ │ [Sauvegarder]                        │   │
│ └──────────────────────────────────────┘   │
│                                            │
│ ┌──────────────────────────────────────┐   │
│ │ Privacy Policy (EN)                  │   │
│ │ [Éditeur WYSIWYG]                    │   │
│ │ [Sauvegarder]                        │   │
│ └──────────────────────────────────────┘   │
└────────────────────────────────────────────┘
```

---

## 📱 Écrans de l'Application

### 1. Splash Screen
- Logo DOSSY CHAT IA
- Slogan

### 2. Onboarding (3 slides)
- Slide 1 : Présentation assistant IA
- Slide 2 : Outils étudiants
- Slide 3 : Plans d'abonnement

### 3. Choix Juridiction
**Après inscription, avant d'accéder au chat** :
```
Sélectionnez votre juridiction :
🇨🇲 Cameroun
🇨🇮 Côte d'Ivoire
🇸🇳 Sénégal
...
```

### 4. Login / Register
- Email + Password
- Social login (Google, Apple)
- Forgot password

### 5. Home / Chat
- Liste conversations
- Nouvelle conversation
- Suggestions prompts

### 6. Conversation
- Messages
- Input chat
- Disclaimer en bas

### 7. Outils Étudiant
- Générateur fiches arrêts
- Générateur fiches révision
- Générateur QCM
- Générateur plans

### 8. Bibliothèque
- Catégories juridiques
- Recherche
- Téléchargement PDF

### 9. Profil
- Informations user
- Plan actuel + quotas
- Code parrainage
- Paramètres

### 10. Plans & Abonnement
- Liste plans
- Comparatif
- Choix cycle (mensuel/annuel)
- Paiement (Flutterwave)

### 11. Paramètres
- Langue (FR/EN)
- Juridiction
- Notifications
- Anonymisation (toggle)
- Dark mode

---

## 🌐 Site Web / Landing Page

### Structure
1. **Hero Section**
   - Titre : "DOSSY CHAT IA"
   - Sous-titre : "Analyse et Assistant Juridique, Fiscal & Social"
   - CTA : "Essayer gratuitement"

2. **Fonctionnalités**
   - Pour Étudiants
   - Pour Avocats
   - Pour Entreprises

3. **Plans & Pricing**
   - Comparatif des plans
   - CTA "S'abonner"

4. **Juridictions**
   - Pays supportés

5. **Témoignages**

6. **FAQ**

7. **Footer**
   - Liens Terms & Privacy
   - Contact
   - Social media

---

## 🛠️ Stack Technique Recommandé

### Frontend (Flutter)
- **Flutter** 3.24+
- **State Management** : Riverpod / Bloc
- **HTTP Client** : Dio
- **Local Storage** : Hive / SharedPreferences
- **Audio Recording** : flutter_sound
- **PDF Viewer** : syncfusion_flutter_pdfviewer
- **Markdown** : flutter_markdown
- **Localization** : flutter_localizations

### Backend
- **API** : Laravel (déjà créé Phase 2)
- **Base de données** : MySQL
- **Storage** : Cloudflare R2
- **AI** : OpenAI GPT-4
- **Embeddings** : OpenAI + Pinecone

### Paiement
- **Flutterwave** (Mobile Money, Carte)

### Notifications
- **Firebase Cloud Messaging** (Push)
- **Email** : SMTP / SendGrid
- **WhatsApp** : WhatsApp Business API

---

**Date** : 2025-11-27  
**Version** : 1.0.0  
**Auteur** : DOSSY Team
