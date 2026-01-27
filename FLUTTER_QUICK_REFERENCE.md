# Quick Reference - Flutter Pro/Cabinet Features

## 🚀 Quick Start

```bash
cd dossy_chat_ia
flutter pub get
flutter run
```

## 📱 Navigation Structure

```
Bottom Navigation (5 tabs)
├── 💬 Chat
├── 📄 Documents
├── 🔧 Outils
├── 👤 Profil
└── 📚 Bibliothèque ← NEW!
    └── Library Hub (8 cards)
        ├── Templates de Documents (Pro)
        ├── Ressources Fiscales (Pro)
        ├── Bibliothèque Juridique (Pro)
        ├── Calculateurs & Simulateurs (Pro)
        ├── Veille Juridique (Pro)
        ├── Alertes Juridiques (Pro)
        ├── Anonymisation (Cabinet)
        └── Multi-comptes (Cabinet)
```

## 🔐 Access Control

```dart
// In Library Hub Screen
bool _isPlanAllowed(String requiredPlan) {
  final currentPlan = subscriptionProvider.currentPlan?.toLowerCase() ?? 'free';
  if (requiredPlan == 'pro') {
    return currentPlan == 'pro' || currentPlan == 'cabinet';
  }
  return currentPlan == requiredPlan;
}
```

**Plan Hierarchy:**
- `free` → Basic features only
- `pro` → All Pro features + Basic
- `cabinet` → All features (Pro + Cabinet)

## 🛣️ Routes

```dart
// Navigation Examples

// Library Hub
Navigator.pushNamed(context, '/library');

// Templates
Navigator.pushNamed(context, '/templates');
Navigator.pushNamed(context, '/template-details', arguments: template);

// Fiscal Resources
Navigator.pushNamed(context, '/fiscal-resources');
Navigator.pushNamed(context, '/fiscal-resource-detail', arguments: resource);

// Calculators
Navigator.pushNamed(context, '/calculators');
Navigator.pushNamed(context, '/calculator-form', arguments: calculator);

// Legal Library
Navigator.pushNamed(context, '/legal-library');

// Legal Alerts
Navigator.pushNamed(context, '/legal-alerts');
```

## 🎯 Key Providers

```dart
// Auth & Token
final authProvider = context.read<AuthProvider>();
final token = authProvider.token;

// Subscription & Plan
final subscriptionProvider = context.read<SubscriptionProvider>();
final currentPlan = subscriptionProvider.currentPlan;

// Templates
final templateProvider = context.read<TemplateProvider>();
await templateProvider.fetchTemplates(token!);
final url = await templateProvider.downloadTemplate(id, token);

// Fiscal Resources
final fiscalProvider = context.read<FiscalResourceProvider>();
await fiscalProvider.fetchResources(token!, country);
final url = await fiscalProvider.downloadResource(id, token);

// Legal Library
final legalLibraryProvider = context.read<LegalLibraryProvider>();
await legalLibraryProvider.searchDocuments(query, token, jurisdiction);

// Calculators
final calculatorProvider = context.read<CalculatorProvider>();
await calculatorProvider.fetchCalculators(token!);
final result = await calculatorProvider.calculateResult(id, inputs, token);
```

## 📥 Download Pattern

```dart
Future<void> _download() async {
  setState(() => _isLoading = true);
  
  try {
    final authProvider = context.read<AuthProvider>();
    if (authProvider.token == null) {
      throw Exception('Non authentifié');
    }
    
    final url = await provider.downloadResource(id, authProvider.token!);
    
    if (url != null) {
      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Téléchargement réussi'), backgroundColor: Colors.green),
        );
      }
    }
  } catch (e) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
    );
  } finally {
    if (mounted) setState(() => _isLoading = false);
  }
}
```

## 🎨 UI Components

### Resource Card (Library Hub)
```dart
_buildResourceCard(
  title: 'Templates de Documents',
  description: 'Modèles de documents juridiques',
  icon: Icons.description,
  color: Colors.blue,
  route: '/templates',
  planBadge: 'Pro',
  isLocked: !_isPlanAllowed('pro'),
)
```

### Loading State
```dart
if (_isLoading) {
  return Center(child: CircularProgressIndicator());
}
```

### Error State
```dart
if (_error != null) {
  return Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
        SizedBox(height: 16),
        Text(_error!),
        SizedBox(height: 16),
        ElevatedButton(
          onPressed: _retry,
          child: Text('Réessayer'),
        ),
      ],
    ),
  );
}
```

### Empty State
```dart
if (items.isEmpty) {
  return Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.inbox, size: 64, color: Colors.grey[400]),
        SizedBox(height: 16),
        Text('Aucun élément disponible'),
      ],
    ),
  );
}
```

## 🔍 Search Pattern

```dart
// Legal Library Search
final provider = context.read<LegalLibraryProvider>();
final authProvider = context.read<AuthProvider>();

await provider.searchDocuments(
  _searchController.text,
  authProvider.token!,
  authProvider.user?.jurisdiction ?? 'FR',
);

// Access results
final results = provider.searchResults;
```

## 🎭 Plan Badge Widget

```dart
Widget _buildPlanBadge(String plan) {
  return Container(
    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: plan == 'Cabinet' ? Colors.purple[100] : Colors.blue[100],
      borderRadius: BorderRadius.circular(12),
    ),
    child: Text(
      plan,
      style: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.bold,
        color: plan == 'Cabinet' ? Colors.purple[900] : Colors.blue[900],
      ),
    ),
  );
}
```

## 🔒 Lock Overlay

```dart
Widget _buildLockedOverlay() {
  return Container(
    color: Colors.black54,
    child: Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.lock, size: 48, color: Colors.white),
          SizedBox(height: 8),
          Text(
            'Fonctionnalité verrouillée',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    ),
  );
}
```

## 📊 File Structure

```
lib/
├── main.dart                                    ← Providers, Routes
├── models/
│   ├── template_model.dart
│   ├── fiscal_resource_model.dart
│   └── calculator_model.dart
├── providers/
│   ├── template_provider.dart
│   ├── fiscal_resource_provider.dart
│   ├── calculator_provider.dart
│   └── legal_library_provider.dart             ← NEW
├── presentation/screens/
│   ├── home/home_screen.dart                   ← 5th Tab
│   └── library/library_hub_screen.dart         ← NEW
└── screens/
    ├── templates/
    │   ├── templates_list_screen.dart
    │   └── template_detail_screen.dart         ← RECREATED
    ├── fiscal_resources/
    │   ├── fiscal_resources_list_screen.dart
    │   └── fiscal_resource_detail_screen.dart  ← RECREATED
    ├── calculators/
    │   ├── calculators_list_screen.dart
    │   └── calculator_form_screen.dart
    ├── legal_library/
    │   └── legal_library_screen.dart           ← NEW
    └── legal_alerts/
        └── alerts_list_screen.dart
```

## 🐛 Common Issues & Fixes

### Issue: Route not found
**Fix:** Check route registration in `main.dart`

### Issue: Arguments casting error
**Fix:** Ensure correct model type:
```dart
final template = ModalRoute.of(context)!.settings.arguments as DocumentTemplate;
```

### Issue: Provider not found
**Fix:** Verify provider is registered in `main.dart`:
```dart
ChangeNotifierProvider(create: (_) => TemplateProvider()),
```

### Issue: Download not working
**Fix:** Check url_launcher configuration in pubspec.yaml and platform-specific setup

### Issue: Access control not working
**Fix:** Verify SubscriptionProvider has correct plan value

## ⚡ Performance Tips

1. **Use Consumer for local updates:**
```dart
Consumer<TemplateProvider>(
  builder: (context, provider, child) {
    return ListView.builder(...);
  },
)
```

2. **Use context.read for one-time actions:**
```dart
final provider = context.read<TemplateProvider>();
await provider.fetchTemplates(token);
```

3. **Check mounted before setState:**
```dart
if (mounted) {
  setState(() => _isLoading = false);
}
```

## 📞 API Endpoints Reference

```dart
// Templates
GET  /api/templates
GET  /api/templates/{id}
GET  /api/templates/{id}/download

// Fiscal Resources
GET  /api/fiscal-resources
GET  /api/fiscal-resources/{id}
GET  /api/fiscal-resources/{id}/download

// Legal Library
POST /api/search
     Body: { query, jurisdiction }

// Calculators
GET  /api/calculators
POST /api/calculators/{id}/calculate
     Body: { inputs }

// Legal Alerts
GET  /api/legal-alerts
POST /api/legal-alerts
PUT  /api/legal-alerts/{id}
```

## ✅ Testing Checklist

- [ ] All 5 tabs visible in bottom nav
- [ ] Library Hub shows 8 cards
- [ ] Free users see locks on Pro features
- [ ] Pro users see locks on Cabinet features
- [ ] Cabinet users see no locks
- [ ] Template download works
- [ ] Fiscal resource download works
- [ ] Legal library search works
- [ ] Calculator form works
- [ ] Back navigation works everywhere
- [ ] Error states display properly
- [ ] Loading states show during async operations

## 📚 Dependencies Used

```yaml
dependencies:
  flutter:
    sdk: flutter
  provider: ^6.0.0              # State management
  http: ^1.1.0                  # API calls
  url_launcher: ^6.2.0          # Download/open files
  flutter_screenutil: ^5.9.0    # Responsive UI
  hive_flutter: ^1.1.0          # Local storage
```

---

**Last Updated:** December 2024
**Version:** 1.0.0
**Status:** ✅ Production Ready
