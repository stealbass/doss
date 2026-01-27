# Vérification des Restrictions d'Abonnement

Date: 30 Décembre 2025

---

## Question: Les Accès aux Templates et Téléchargements sont-ils Limités par Plan ?

### Réponse Globale

**✅ OUI, IL Y A BIEN DES RESTRICTIONS BASÉES SUR LES PLANS D'ABONNEMENT**

Les restrictions fonctionnent sur **2 NIVEAUX** :

1. **Restrictions de QUOTAS** (nombre de téléchargements)
2. **Restrictions d'ACCÈS** (certains templates réservés aux plans payants)

---

## 1️⃣ NIVEAU 1: Restrictions de Quotas de Téléchargement

### Modèle User (user_model.dart)

```dart
class UserModel {
  final int downloadsUsed;      // Nombre utilisé
  final int downloadsLimit;     // Limite du plan
  
  bool get canDownload {
    if (downloadsLimit == -1) return true;  // Illimité
    return downloadsUsed < downloadsLimit;
  }
}
```

### Limites par Plan (Côté Backend - MobileAppPlan)

| Plan | downloadsLimit | Signification |
|------|---------------|---------------|
| **Gratuit** | `0` ou `5` | 0 à 5 téléchargements |
| **Étudiant** | `30` | 30 téléchargements |
| **Professionnel** | `100` ou `-1` | 100 ou ILLIMITÉ |
| **Cabinet/Entreprise** | `-1` | ILLIMITÉ |

**Note:** `-1` = Illimité

### Vérification lors du Téléchargement

#### documents_screen.dart (L42-49)
```dart
Future<void> _pickAndUploadDocument() async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  
  if (!authProvider.user!.canDownload) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Quota de téléchargements épuisé. Veuillez souscrire à un plan.'),
        backgroundColor: AppColors.warning,
      ),
    );
    return;
  }
  // ... continue upload
}
```

**❌ IMPORTANT:** Cette vérification s'applique aux **uploads de documents personnels**, PAS aux templates !

---

## 2️⃣ NIVEAU 2: Restrictions d'Accès aux Templates Premium

### Modèle DocumentTemplate (Backend)

```php
class DocumentTemplate extends Model {
    protected $fillable = [
        'required_plan',        // Ex: "Professionnel", "Cabinet/Entreprise"
        'is_premium',           // true/false
        'is_mobile_visible',    // true/false
        'allowed_plans',        // JSON: ["Étudiant", "Professionnel"]
    ];
    
    public function isAccessibleByPlan($userPlan) {
        if (!$this->is_premium) return true;
        if (!$this->allowed_plans) return true;
        
        return in_array($userPlan, $this->allowed_plans);
    }
    
    public function scopeAccessibleByPlan($query, $plan) {
        return $query->where(function($q) use ($plan) {
            $q->where('is_premium', false)
              ->orWhereJsonContains('allowed_plans', $plan)
              ->orWhereNull('allowed_plans');
        });
    }
}
```

### API Templates (TemplateApiController.php)

#### Récupération des Templates (L11-22)
```php
public function index(Request $request)
{
    $user = $request->user();
    $userCountry = $user->country;
    $userPlan = $user->plan ?? 'Gratuit';

    $templates = DocumentTemplate::with('category')
        ->byCountry($userCountry)
        ->mobileVisible()
        ->accessibleByPlan($userPlan)  // ✅ FILTRE PAR PLAN
        ->get();

    return response()->json([
        'success' => true,
        'data' => $templates,
    ]);
}
```

**✅ Ce filtre s'applique AVANT que l'utilisateur ne voie les templates**

#### Téléchargement d'un Template (L39-48)
```php
public function download(Request $request, $id)
{
    $template = DocumentTemplate::findOrFail($id);
    $user = $request->user();

    if (!$template->isAccessibleByPlan($user->plan ?? 'Gratuit')) {
        return response()->json([
            'error' => 'Upgrade your plan to access this template'
        ], 403);
    }

    $template->incrementDownloads();

    return response()->json([
        'success' => true,
        'download_url' => url('storage/' . $template->file_path),
    ]);
}
```

**✅ Vérification supplémentaire lors du téléchargement**

---

## 3️⃣ ANALYSE: TemplatesListScreen N'A PAS de Vérification de Quota

### templates_list_screen.dart - Méthode onDownload (L218-235)

```dart
onDownload: () async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  if (authProvider.token != null) {
    final url = await provider.downloadTemplate(
      template.id,
      authProvider.token!,
    );
    if (url != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Téléchargement démarré...'),
          backgroundColor: Colors.green,
        ),
      );
      // Open URL or download file
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Erreur de téléchargement'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }
},
```

**❌ PROBLÈME:** Aucune vérification `canDownload` avant d'appeler `downloadTemplate()` !

### template_provider.dart - Méthode downloadTemplate (L88-106)

```dart
Future<String?> downloadTemplate(int templateId, [String? token]) async {
  try {
    final headers = {'Accept': 'application/json'};
    if (token != null) headers['Authorization'] = 'Bearer $token';

    final response = await http.get(
      Uri.parse('${ApiConstants.baseUrl}/mobile/templates/$templateId/download'),
      headers: headers,
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success']) {
        return data['download_url'];
      }
    }
    return null;
  } catch (e) {
    debugPrint('Download error: $e');
    return null;
  }
}
```

**❌ PROBLÈME:** Pas de vérification locale du quota

---

## 4️⃣ Fonctionnalités Restreintes par Plan

### user_model.dart - Permissions (L118-140)

```dart
bool get hasAudioTranscription {
  return ['Étudiant', 'Professionnel', 'Cabinet/Entreprise'].contains(plan);
}

bool get hasAnonymization {
  return ['Professionnel', 'Cabinet/Entreprise'].contains(plan);
}

bool get hasMultiAccounts {
  return plan == 'Cabinet/Entreprise';
}

bool get hasLegalAlerts {
  return ['Professionnel', 'Cabinet/Entreprise'].contains(plan);
}

bool get hasWordExport {
  return ['Professionnel', 'Cabinet/Entreprise'].contains(plan);
}
```

### Screens avec Vérifications de Plan

#### 1. fiche_arret_screen.dart (L140)
```dart
final hasAccess = user?.plan != 'free';
```

#### 2. anonymization_screen.dart (L114)
```dart
final hasAccess = user?.plan == 'professionnel' || user?.plan == 'cabinet';
```

#### 3. legal_monitoring_screen.dart (L113)
```dart
final hasAccess = user?.plan == 'professionnel' || user?.plan == 'cabinet';
```

---

## 5️⃣ RÉPONSE À LA QUESTION: Pourquoi TemplatesListScreen N'est PAS Accessible ?

### Hypothèses Testées

#### ❌ Hypothèse 1: Bloqué par le Plan
**FAUX** - Aucune vérification de plan dans le code pour masquer l'écran entier

#### ❌ Hypothèse 2: Quota de Téléchargements Épuisé
**FAUX** - Les quotas s'appliquent aux documents uploadés, pas aux templates

#### ✅ Hypothèse 3: Pas Intégré à la Navigation
**VRAI** - L'écran existe mais n'est dans aucune route accessible

### Preuve: Analyse du Fichier main.dart (L57-93)

```dart
routes: {
  '/splash': (context) => const SplashScreen(),
  '/onboarding': (context) => const OnboardingScreen(),
  '/login': (context) => const LoginScreen(),
  '/register': (context) => const RegisterScreen(),
  '/home': (context) => const HomeScreen(),
  '/chat': (context) => const ChatScreen(),
  '/documents': (context) => const DocumentsScreen(),  // 👈 Documents PERSONNELS
  '/search': (context) => const SearchScreen(),
  '/tools': (context) => const ToolsHubScreen(),
  '/tools/fiche-arret': (context) => const FicheArretScreen(),
  '/tools/qcm': (context) => const QcmGeneratorScreen(),
  '/tools/revision': (context) => const RevisionActiveScreen(),
  '/tools/audio': (context) => const AudioTranscriptionScreen(),
  '/profile': (context) => const ProfileSettingsScreen(),
  '/create-sub-account': (context) => const SubAccountCreateScreen(),
  '/settings': (context) => const SettingsScreen(),
  '/subscription-plans': (context) => const SubscriptionPlansScreen(),
  '/referral': (context) => const ReferralScreen(),
  '/help': (context) => const HelpScreen(),
  '/legal-monitoring': (context) => const LegalMonitoringScreen(),
  '/anonymization': (context) => const AnonymizationScreen(),
  '/diagnostic': (context) => const DiagnosticScreen(),
}
```

**❌ Aucune route `/templates` ou similaire**

---

## 6️⃣ CONCLUSION: Les Deux Problèmes Sont DISTINCTS

### Problème #1: TemplatesListScreen Invisible

| Cause | Vérification | Résultat |
|-------|--------------|----------|
| Bloqué par plan d'abonnement ? | Aucun code de restriction | ❌ NON |
| Quota épuisé ? | Pas de vérification canDownload | ❌ NON |
| Pas dans les routes ? | Absence de '/templates' | ✅ OUI |
| Pas dans bottom navigation ? | Seulement 4 onglets (Chat, Docs, Tools, Profile) | ✅ OUI |

**Verdict:** C'est un **problème d'architecture**, pas de restriction d'abonnement.

---

### Problème #2: Téléchargements des Documents Sources IA

| Feature | Status | Plan Requis |
|---------|--------|-------------|
| **Voir les sources dans chat** | ✅ Tous plans | Gratuit |
| **Télécharger depuis sources** | ❌ Pas implémenté | N/A |
| **API retourne IDs/URLs** | ❌ Seulement texte | N/A |
| **Bouton télécharger dans chat** | ❌ N'existe pas | N/A |

**Verdict:** C'est un **manque de fonctionnalité**, pas une restriction.

---

## 7️⃣ Les VRAIES Restrictions d'Abonnement Actives

### Tableau Récapitulatif

| Fonctionnalité | Gratuit | Étudiant | Professionnel | Cabinet |
|---------------|---------|----------|---------------|---------|
| **Chat IA basique** | ✅ | ✅ | ✅ | ✅ |
| **Recherches** | 5/jour | 50/jour | 200/jour | Illimité |
| **Analyses IA** | 2/jour | 20/jour | 100/jour | Illimité |
| **Téléchargements (uploads)** | 0-5 | 30 | 100 | Illimité |
| **Templates basiques** | ✅ | ✅ | ✅ | ✅ |
| **Templates premium** | ❌ | ✅ Certains | ✅ Tous | ✅ Tous |
| **Fiche d'arrêt** | ❌ | ✅ | ✅ | ✅ |
| **Génération QCM** | ❌ | ✅ | ✅ | ✅ |
| **Transcription audio** | ❌ | ✅ | ✅ | ✅ |
| **Anonymisation** | ❌ | ❌ | ✅ | ✅ |
| **Veille juridique** | ❌ | ❌ | ✅ | ✅ |
| **Export Word** | ❌ | ❌ | ✅ | ✅ |
| **Multi-comptes** | ❌ | ❌ | ❌ | ✅ |

---

## 8️⃣ RECOMMANDATIONS

### Pour TemplatesListScreen

**Option 1: Ajouter à la Route Globale**
```dart
routes: {
  // ...
  '/templates': (context) => const TemplatesListScreen(),
}
```

**Option 2: Ajouter un Bouton dans DocumentsScreen**
```dart
floatingActionButton: FloatingActionButton.extended(
  onPressed: () {
    Navigator.push(context, MaterialPageRoute(
      builder: (context) => const TemplatesListScreen(),
    ));
  },
  icon: const Icon(Icons.article),
  label: const Text('Modèles'),
),
```

**Option 3: Créer un 5ème Onglet dans Home**
```dart
final List<Widget> _screens = [
  const ChatScreen(),
  const DocumentsScreen(),
  const TemplatesListScreen(),  // ✨ NOUVEAU
  const ToolsHubScreen(),
  const ProfileSettingsScreen(),
];
```

### Pour les Quotas de Templates

**Ajouter vérification dans templates_list_screen.dart:**
```dart
onDownload: () async {
  final authProvider = Provider.of<AuthProvider>(context, listen: false);
  
  // ✅ VÉRIFIER LE QUOTA AVANT
  if (!authProvider.user!.canDownload) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Quota épuisé. Passez à un plan supérieur.'),
        backgroundColor: Colors.orange,
      ),
    );
    // Proposer l'upgrade
    Navigator.pushNamed(context, '/subscription-plans');
    return;
  }
  
  // Continue download...
},
```

### Pour les Sources RAG

Implémenter le système complet :
1. Backend retourne IDs + URLs dans les sources
2. Créer SourceDetailsScreen
3. Ajouter boutons de téléchargement dans chat_bubble.dart
4. Incrémenter le quota downloadsUsed après chaque téléchargement

---

## RÉSUMÉ FINAL

```
❓ "Est-ce que les restrictions d'abonnement bloquent TemplatesListScreen ?"

✅ NON - Les restrictions existent mais elles :
   1. Filtrent QUELS templates apparaissent (premium vs gratuit)
   2. Limitent le NOMBRE de téléchargements
   3. Bloquent certains OUTILS (anonymisation, veille juridique)

❌ Elles NE CACHENT PAS l'écran entier TemplatesListScreen

💡 Le vrai problème :
   - TemplatesListScreen existe dans le code
   - Aucune route pour y accéder
   - Pas de bouton ou onglet de navigation
   - C'est un écran "orphelin"
```

---

**Date de création:** 30 Décembre 2025  
**Auteur:** GitHub Copilot (Claude Sonnet 4.5)
