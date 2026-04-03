import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../l10n/app_localizations.dart';

class ToolsHubScreen extends StatefulWidget {
  const ToolsHubScreen({super.key});

  @override
  State<ToolsHubScreen> createState() => _ToolsHubScreenState();
}

class _ToolsHubScreenState extends State<ToolsHubScreen> with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    print('🔄 ToolsHubScreen initialized');
    _refreshUserData();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      print('🔄 ToolsHubScreen resumed - refreshing user data');
      _refreshUserData();
    }
  }

  Future<void> _refreshUserData() async {
    print('📡 Refreshing user data...');
    try {
      final authProvider = context.read<AuthProvider>();
      await authProvider.refreshUser();
      print('✅ User data refreshed');
    } catch (e) {
      print('❌ Error refreshing user data: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final user = authProvider.user;
    final plan = (user?.plan ?? '').toLowerCase();
    final isFree = plan == 'free' || plan == 'gratuit';
    final canUseTools = !isFree || (user?.canAnalyze ?? false);
    final colorScheme = Theme.of(context).colorScheme;
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.navTools),
        centerTitle: true,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
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
                    Icons.school,
                    size: 48,
                    color: Colors.white,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    l10n.boostYourLearning,
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    l10n.aiToolsForEffectiveRevision,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withAlpha((0.9 * 255).round()),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            // Tools Grid
            Text(
              l10n.allTools,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),

            // Fiche d'Arrêt Generator
            _ToolCard(
              icon: Icons.gavel,
              title: l10n.automaticCaseSummary,
              description: l10n.autoGenerateCaseSummaries,
              gradient: [Colors.blue.shade400, Colors.blue.shade600],
              onTap: () {
                Navigator.pushNamed(context, '/tools/fiche-arret');
              },
              isPremium: !canUseTools,
            ),

            const SizedBox(height: 12),

            // QCM Generator
            _ToolCard(
              icon: Icons.quiz,
              title: l10n.customQuiz,
              description: l10n.createCustomQuizzes,
              gradient: [Colors.orange.shade400, Colors.orange.shade600],
              onTap: () {
                Navigator.pushNamed(context, '/tools/qcm');
              },
              isPremium: !canUseTools,
            ),

            const SizedBox(height: 12),

            // Active Revision Mode
            _ToolCard(
              icon: Icons.psychology,
              title: l10n.activeRevision,
              description: l10n.reviewWithSmartFlashcards,
              gradient: [Colors.purple.shade400, Colors.purple.shade600],
              onTap: () {
                Navigator.pushNamed(context, '/tools/revision');
              },
              isPremium: !canUseTools,
            ),

            const SizedBox(height: 12),

            // Audio Transcription - HIDDEN (à activer plus tard)
            // _ToolCard(
            //   icon: Icons.mic,
            //   title: l10n.audioToTextTranscription,
            //   description: l10n.convertAudioToText,
            //   gradient: [Colors.teal.shade400, Colors.teal.shade600],
            //   onTap: () {
            //     Navigator.pushNamed(context, '/tools/audio');
            //   },
            //   isPremium: user?.plan?.toLowerCase() == 'gratuit' || user?.plan?.toLowerCase() == 'étudiant',
            // ),

            const SizedBox(height: 24),

            // Usage Stats
            if (!isFree)
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: colorScheme.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: colorScheme.outline.withAlpha((0.2 * 255).round()),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(
                          Icons.analytics,
                          color: AppConstants.primaryGreen,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          l10n.thisMonthUsage,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _UsageStat(
                      label: l10n.summariesCreated,
                      value: '${user?.summariesGenerated ?? 0}',
                      icon: Icons.description,
                    ),
                    const SizedBox(height: 8),
                    _UsageStat(
                      label: l10n.quizzesCreated,
                      value: '${user?.quizzesCreated ?? 0}',
                      icon: Icons.quiz,
                    ),
                    const SizedBox(height: 8),
                    _UsageStat(
                      label: l10n.revisionSessions,
                      value: '${user?.revisionSessions ?? 0}',
                      icon: Icons.timeline,
                    ),
                  ],
                ),
              ),

            const SizedBox(height: 16),

            // Upgrade CTA for Free Users
            if (isFree && !canUseTools)
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      Colors.amber.shade400,
                      Colors.orange.shade500,
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.stars, color: Colors.white),
                        const SizedBox(width: 8),
                        Text(
                          l10n.unlockAllTools,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      l10n.upgradeToStudentPlan,
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.white.withAlpha((0.95 * 255).round()),
                      ),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () {
                          Navigator.pushNamed(context, '/subscription-plans');
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: Colors.orange.shade600,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        child: Text(
                          l10n.viewPlans,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// Tool Card Widget
class _ToolCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String description;
  final List<Color> gradient;
  final VoidCallback onTap;
  final bool isPremium;

  const _ToolCard({
    required this.icon,
    required this.title,
    required this.description,
    required this.gradient,
    required this.onTap,
    this.isPremium = false,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: colorScheme.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: colorScheme.outline.withValues(alpha: 0.2),
            ),
          ),
          child: Row(
            children: [
              // Icon with gradient background
              Container(
                width: 60,
                height: 60,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: gradient,
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  icon,
                  color: Colors.white,
                  size: 30,
                ),
              ),
              const SizedBox(width: 16),
              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            title,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        if (isPremium)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.amber.shade100,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.lock,
                                  size: 12,
                                  color: Colors.amber.shade800,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  'PRO',
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.amber.shade800,
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      description,
                      style: TextStyle(
                        fontSize: 13,
                        color: colorScheme.onSurface
                            .withAlpha((0.7 * 255).round()),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(
                Icons.arrow_forward_ios,
                size: 16,
                color: colorScheme.onSurface.withAlpha((0.4 * 255).round()),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// Usage Stat Widget
class _UsageStat extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;

  const _UsageStat({
    required this.label,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(
          icon,
          size: 20,
          color: AppConstants.primaryGreen.withAlpha((0.7 * 255).round()),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontSize: 14),
          ),
        ),
        Text(
          value,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: AppConstants.primaryGreen,
          ),
        ),
      ],
    );
  }
}
