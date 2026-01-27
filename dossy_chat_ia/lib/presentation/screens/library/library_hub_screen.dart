import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../l10n/app_localizations.dart';

class LibraryHubScreen extends StatelessWidget {
  const LibraryHubScreen({super.key});

  bool _hasProAccess(String? plan) {
    if (plan == null) return false;
    final p = plan.toLowerCase();
    return p != 'free' && p != 'gratuit';
  }

  bool _hasCabinetAccess(String? plan) {
    if (plan == null) return false;
    final p = plan.toLowerCase();
    return p.contains('cabinet') || p.contains('entreprise');
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, authProvider, child) {
        final user = authProvider.user;
        final hasPro = _hasProAccess(user?.plan);
        final hasCabinet = _hasCabinetAccess(user?.plan);
        final isFr = Localizations.localeOf(context).languageCode == 'fr';

        // Debug print to check plan value
        print('DEBUG LibraryHub: user plan = ${user?.plan}, hasPro=$hasPro, hasCabinet=$hasCabinet');

        return Scaffold(
          appBar: AppBar(
            title: Text(isFr ? 'Bibliothèque Pro' : 'Pro Library'),
            centerTitle: true,
            elevation: 0,
          ),
          body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    AppConstants.primaryGreen,
                    AppConstants.primaryGreen.withAlpha((0.7 * 255).round()),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(
                    Icons.library_books,
                    size: 48,
                    color: Colors.white,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isFr
                        ? 'Ressources professionnelles'
                        : 'Professional Resources',
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    isFr
                        ? 'Accédez aux documents et outils professionnels'
                        : 'Access documents and professional tools',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withAlpha((0.9 * 255).round()),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            _SectionHeader(title: isFr ? 'Documents' : 'Documents'),
          _HubCard(
            title: isFr ? 'Modèles de documents' : 'Document Templates',
            description: isFr
                ? 'Téléchargez les modèles selon votre plan.'
                : 'Download templates available for your plan.',
            icon: Icons.description,
            locked: !hasPro,
            onTap: () => Navigator.pushNamed(context, '/templates'),
          ),
          _HubCard(
            title: isFr ? 'Ressources fiscales & sociales' : 'Fiscal Resources',
            description: isFr
                ? 'Accès aux codes, lois de finances, barèmes.'
                : 'Access tax codes, finance laws, salary grids.',
            icon: Icons.balance,
            locked: !hasPro,
            onTap: () => Navigator.pushNamed(context, '/fiscal-resources'),
          ),
          _HubCard(
            title: isFr ? 'Bibliothèque juridique' : 'Legal Library',
            description: isFr
                ? 'Recherchez dans la base juridique.'
                : 'Search the legal knowledge base.',
            icon: Icons.menu_book,
            locked: !hasPro,
            onTap: () => Navigator.pushNamed(context, '/legal-library'),
          ),

          const SizedBox(height: 24),
          if (!hasPro)
            _UpgradeHint(text: isFr ? 'Passez au plan Pro pour débloquer ces ressources.' : 'Upgrade to Pro to unlock these resources.'),
          ],
        ),
      ),
    );
      },
    );
  }
}

class _HubCard extends StatelessWidget {
  final String title;
  final String description;
  final IconData icon;
  final bool locked;
  final VoidCallback onTap;

  const _HubCard({
    required this.title,
    required this.description,
    required this.icon,
    required this.locked,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: AppConstants.primaryGreen.withAlpha((0.12 * 255).round()),
          child: Icon(icon, color: AppConstants.primaryGreen),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(description),
        trailing: locked
            ? const Icon(Icons.lock, color: Colors.redAccent)
            : Icon(Icons.chevron_right, color: colorScheme.primary),
        onTap: locked ? null : onTap,
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;
  const _SectionHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Text(
        title,
        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
      ),
    );
  }
}

class _UpgradeHint extends StatelessWidget {
  final String text;
  const _UpgradeHint({required this.text});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppConstants.primaryGreen.withAlpha((0.1 * 255).round()),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppConstants.primaryGreen.withAlpha((0.3 * 255).round())),
      ),
      child: Row(
        children: [
          const Icon(Icons.info_outline, color: AppConstants.primaryGreen),
          const SizedBox(width: 8),
          Expanded(child: Text(text)),
          TextButton(
            onPressed: () => Navigator.pushNamed(context, '/subscription-plans'),
              child: Text(l10n.viewPlans),
          ),
        ],
      ),
    );
  }
}
