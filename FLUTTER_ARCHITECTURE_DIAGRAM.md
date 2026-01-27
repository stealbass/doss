# Flutter App Architecture - Pro/Cabinet Features

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         DOSSY FLUTTER APP                           │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                    ┌─────────────┴─────────────┐
                    │                           │
            ┌───────▼────────┐          ┌──────▼───────┐
            │   PROVIDERS    │          │    MODELS    │
            │  (State Mgmt)  │          │   (Data)     │
            └───────┬────────┘          └──────┬───────┘
                    │                           │
        ┌───────────┴───────────────────────────┴───────────┐
        │                                                    │
┌───────▼────────┐  ┌─────────────┐  ┌─────────────────────▼──┐
│  PRESENTATION  │  │   SCREENS   │  │      API LAYER         │
│    SCREENS     │  │  (Features) │  │  (HTTP Requests)       │
└────────────────┘  └─────────────┘  └────────────────────────┘
```

## 📊 Component Hierarchy

```
main.dart (Root)
│
├── MultiProvider
│   ├── AuthProvider
│   ├── SubscriptionProvider
│   ├── TemplateProvider
│   ├── FiscalResourceProvider
│   ├── LegalLibraryProvider
│   ├── CalculatorProvider
│   └── LegalAlertProvider
│
├── MaterialApp
│   ├── Routes
│   │   ├── /home → HomeScreen
│   │   ├── /library → LibraryHubScreen
│   │   ├── /templates → TemplatesListScreen
│   │   ├── /template-details → TemplateDetailScreen
│   │   ├── /fiscal-resources → FiscalResourcesListScreen
│   │   ├── /fiscal-resource-detail → FiscalResourceDetailScreen
│   │   ├── /calculators → CalculatorsListScreen
│   │   ├── /calculator-form → CalculatorFormScreen
│   │   ├── /legal-library → LegalLibraryScreen
│   │   └── /legal-alerts → AlertsListScreen
│   │
│   └── Home → HomeScreen (with BottomNavigationBar)
```

## 🔄 Data Flow

```
┌──────────────┐
│     USER     │
│   ACTIONS    │
└──────┬───────┘
       │
       ▼
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│     UI       │────▶│   PROVIDER   │────▶│      API     │
│  (Screens)   │     │  (Business   │     │   (Backend)  │
│              │     │    Logic)    │     │              │
└──────────────┘     └──────┬───────┘     └──────────────┘
       ▲                    │
       │                    ▼
       │             ┌──────────────┐
       └─────────────│     STATE    │
                     │   (Models)   │
                     └──────────────┘
```

## 🎯 Feature Flow: Templates Example

```
User Interaction:
   │
   ▼
┌─────────────────────┐
│  Library Hub        │  Tap "Templates"
│  (Entry Point)      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Templates List     │  ← Provider: TemplateProvider.fetchTemplates()
│  • Search          │  ← API: GET /api/templates
│  • Filter         │
│  • Cards           │
└──────────┬──────────┘
           │ Tap template
           ▼
┌─────────────────────┐
│  Template Detail    │  ← Receives: DocumentTemplate object
│  • Info            │
│  • Variables       │
│  • Download btn    │
└──────────┬──────────┘
           │ Tap download
           ▼
┌─────────────────────┐
│  Download Process   │  ← Provider: TemplateProvider.downloadTemplate()
│  • Auth check      │  ← API: GET /api/templates/{id}/download
│  • URL launcher    │  ← Returns: Download URL
│  • Notification    │  ← Opens: External app/browser
└─────────────────────┘
```

## 🔐 Access Control Flow

```
User Opens Feature
       │
       ▼
┌─────────────────────┐
│ Check Current Plan  │ ← SubscriptionProvider.currentPlan
└──────────┬──────────┘
           │
     ┌─────┴─────┐
     │           │
     ▼           ▼
┌─────────┐  ┌─────────┐
│  FREE   │  │ PRO/CAB │
└────┬────┘  └────┬────┘
     │            │
     ▼            ▼
┌─────────┐  ┌─────────┐
│ LOCKED  │  │UNLOCKED │
│  Show   │  │  Show   │
│  Lock   │  │ Content │
│  + CTA  │  │         │
└─────────┘  └─────────┘
```

## 📱 Screen Hierarchy

```
HomeScreen (Bottom Nav: 5 tabs)
│
├── ChatScreen (Tab 1)
│   └── AI Chat Interface
│
├── DocumentsScreen (Tab 2)
│   └── User Documents List
│
├── ToolsHubScreen (Tab 3)
│   ├── Fiche d'Arrêt
│   ├── QCM Generator
│   ├── Révision Active
│   └── Audio Transcription
│
├── ProfileScreen (Tab 4)
│   ├── Settings
│   ├── Subscription
│   └── Referral
│
└── LibraryHubScreen (Tab 5) ← NEW!
    ├── Templates Card → TemplatesListScreen
    │                    └── TemplateDetailScreen
    │
    ├── Fiscal Resources Card → FiscalResourcesListScreen
    │                           └── FiscalResourceDetailScreen
    │
    ├── Legal Library Card → LegalLibraryScreen
    │                        └── Search Results
    │
    ├── Calculators Card → CalculatorsListScreen
    │                      └── CalculatorFormScreen
    │                          └── CalculatorResultScreen
    │
    ├── Legal Monitoring Card → LegalMonitoringScreen
    │
    ├── Legal Alerts Card → AlertsListScreen
    │
    ├── Anonymization Card → AnonymizationScreen (Cabinet only)
    │
    └── Multi-accounts Card → SubAccountCreateScreen (Cabinet only)
```

## 🗂️ File Organization

```
dossy_chat_ia/
│
├── lib/
│   ├── main.dart ← App entry, providers, routes
│   │
│   ├── core/
│   │   ├── theme/
│   │   ├── constants/
│   │   └── utils/
│   │
│   ├── data/
│   │   ├── providers/ ← Core providers
│   │   │   ├── auth_provider.dart
│   │   │   ├── subscription_provider.dart
│   │   │   └── ...
│   │   │
│   │   ├── services/ ← API services
│   │   │   ├── api_service.dart
│   │   │   └── search_service.dart
│   │   │
│   │   └── repositories/
│   │
│   ├── models/ ← Data models
│   │   ├── template_model.dart
│   │   ├── fiscal_resource_model.dart
│   │   └── calculator_model.dart
│   │
│   ├── providers/ ← Feature providers
│   │   ├── template_provider.dart
│   │   ├── fiscal_resource_provider.dart
│   │   ├── calculator_provider.dart
│   │   └── legal_library_provider.dart ← NEW
│   │
│   ├── presentation/screens/ ← Main screens
│   │   ├── home/
│   │   │   └── home_screen.dart ← Updated (5 tabs)
│   │   │
│   │   └── library/
│   │       └── library_hub_screen.dart ← NEW (Hub)
│   │
│   └── screens/ ← Feature screens
│       ├── templates/
│       │   ├── templates_list_screen.dart
│       │   └── template_detail_screen.dart ← Recreated
│       │
│       ├── fiscal_resources/
│       │   ├── fiscal_resources_list_screen.dart
│       │   └── fiscal_resource_detail_screen.dart ← Recreated
│       │
│       ├── calculators/
│       │   ├── calculators_list_screen.dart
│       │   └── calculator_form_screen.dart
│       │
│       ├── legal_library/
│       │   └── legal_library_screen.dart ← NEW
│       │
│       └── legal_alerts/
│           └── alerts_list_screen.dart
│
└── pubspec.yaml ← Dependencies
```

## 🔌 Provider Integration

```
Widget Tree:
│
MultiProvider
├── AuthProvider
│   └── Provides: token, user, isAuthenticated
│       Used by: All API calls, access control
│
├── SubscriptionProvider
│   └── Provides: currentPlan, features
│       Used by: Access control, Library Hub
│
├── TemplateProvider
│   └── Provides: templates, isLoading, error
│       Methods: fetchTemplates(), downloadTemplate()
│       Used by: TemplatesListScreen, TemplateDetailScreen
│
├── FiscalResourceProvider
│   └── Provides: resources, isLoading, error
│       Methods: fetchResources(), downloadResource()
│       Used by: FiscalResourcesListScreen, FiscalResourceDetailScreen
│
├── LegalLibraryProvider (NEW)
│   └── Provides: searchResults, isLoading, error
│       Methods: searchDocuments()
│       Used by: LegalLibraryScreen
│
└── CalculatorProvider
    └── Provides: calculators, isLoading, error
        Methods: fetchCalculators(), calculateResult()
        Used by: CalculatorsListScreen, CalculatorFormScreen
```

## 🌊 Navigation Flow Diagram

```
App Launch
    │
    ▼
SplashScreen
    │
    ▼
Auth Check
    ├─ Not Logged In → LoginScreen
    │                      │
    │                      ▼
    │                  Registration
    │                      │
    └──────────────────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │  HomeScreen  │
                    │  (5 Tabs)    │
                    └──────┬───────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
  ┌─────────┐      ┌─────────────┐     ┌─────────┐
  │  Chat   │      │  Documents  │     │  Tools  │
  └─────────┘      └─────────────┘     └─────────┘
        │                  │                  │
        ▼                  ▼                  ▼
   Chat Flow         Docs Flow          Tools Flow
        
        
        ┌──────────────────┬──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
  ┌─────────┐      ┌─────────────┐     ┌─────────┐
  │ Profile │      │  Bibliothèque│     │  ...    │
  └─────────┘      └──────┬──────┘     └─────────┘
                          │
                          ▼
                  ┌───────────────┐
                  │  Library Hub  │ (8 Cards)
                  └───────┬───────┘
                          │
          ┌───────────────┼───────────────┐
          │               │               │
          ▼               ▼               ▼
    ┌──────────┐    ┌──────────┐   ┌──────────┐
    │Templates │    │ Fiscal   │   │  Legal   │
    │          │    │Resources │   │ Library  │
    └────┬─────┘    └────┬─────┘   └────┬─────┘
         │               │               │
         ▼               ▼               ▼
    List Screen    List Screen     Search Screen
         │               │               │
         ▼               ▼               ▼
   Detail Screen   Detail Screen    Results List
         │               │               │
         ▼               ▼               ▼
      Download        Download      View/Download
```

## 🎨 UI Component Structure

```
LibraryHubScreen
│
├── AppBar
│   └── Title: "Bibliothèque Pro"
│
├── Body (GridView)
│   ├── Card 1: Templates
│   │   ├── Icon
│   │   ├── Title
│   │   ├── Description
│   │   ├── Plan Badge
│   │   └── Lock Overlay (if locked)
│   │
│   ├── Card 2: Fiscal Resources
│   │   └── ... (same structure)
│   │
│   ├── Card 3: Legal Library
│   │   └── ...
│   │
│   ├── Card 4: Calculators
│   │   └── ...
│   │
│   ├── Card 5: Legal Monitoring
│   │   └── ...
│   │
│   ├── Card 6: Legal Alerts
│   │   └── ...
│   │
│   ├── Card 7: Anonymization (Cabinet)
│   │   └── ...
│   │
│   └── Card 8: Multi-accounts (Cabinet)
│       └── ...
│
└── Upgrade Button (if locked features)
    └── "Mettre à niveau"
```

## 🔄 State Management Flow

```
User Action (e.g., Tap Download)
       │
       ▼
┌──────────────────────┐
│   Widget Method      │ (e.g., _downloadTemplate())
│   setState()         │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   Provider Method    │ (e.g., templateProvider.downloadTemplate())
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   API Service        │ (HTTP request with token)
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   Backend API        │ (Returns download URL)
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   Provider Updates   │ (notifyListeners())
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   Widget Rebuilds    │ (Consumer/setState)
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   URL Launcher       │ (Opens file)
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   User Feedback      │ (SnackBar notification)
└──────────────────────┘
```

## 📦 Dependencies Graph

```
main.dart
├── provider (state management)
├── flutter_screenutil (responsive UI)
└── hive_flutter (local storage)

Screens
├── provider (access providers)
├── models (data structures)
└── url_launcher (open downloads)

Providers
├── http (API calls)
└── models (data parsing)

Models
└── (Pure Dart classes)
```

---

**Visual Guide Version:** 1.0
**Last Updated:** December 2024
