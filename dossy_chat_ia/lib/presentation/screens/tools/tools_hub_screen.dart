import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';

class ToolsHubScreen extends StatelessWidget {
  const ToolsHubScreen({Key? key}) : super(key: key);
  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final user = authProvider.user;
    final colorScheme = Theme.of(context).colorScheme;
    final locale = Localizations.localeOf(context);
    final isFr = locale.languageCode == 'fr';
    return Scaffold(
      appBar: AppBar(
        title: Text(isFr ? 'Outils Étudiants' : 'Student Tools'),
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
                    AppColors.primaryGreen,
                    AppColors.primaryGreen.withOpacity(0.7),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    Icons.school,
                    size: 48,
                    color: Colors.white,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isFr
                        ? 'Boostez votre apprentissage'
                        : 'Boost your learning',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    isFr
                        ? 'Des outils IA pour réviser efficacement'
                        : 'AI tools for effective revision',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withOpacity(0.9),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            // Tools Grid
            Text(
              isFr ? 'Tous les outils' : 'All tools',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            // Fiche d'Arrêt Generator
            _ToolCard(
              icon: Icons.gavel,
              title: isFr ? 'Fiche d\'Arrêt' : 'Case Summary',
              description: isFr
                  ? 'Générez automatiquement des fiches d\'arrêt structurées'
                  : 'Automatically generate structured case summaries',
              gradient: [Colors.blue.shade400, Colors.blue.shade600],
              onTap: () {
                Navigator.pushNamed(context, '/fiche-arret');
              },
              isPremium: user?.plan == 'free',
            ),
            const SizedBox(height: 12),
            // QCM Generator
            _ToolCard(
              icon: Icons.quiz,
              title: isFr ? 'Générateur de QCM' : 'MCQ Generator',
              description: isFr
                  ? 'Créez des quiz personnalisés à partir de vos cours'
                  : 'Create custom quizzes from your courses',
              gradient: [Colors.orange.shade400, Colors.orange.shade600],
              onTap: () {
                Navigator.pushNamed(context, '/qcm-generator');
              },
              isPremium: user?.plan == 'free',
            ),
            const SizedBox(height: 12),
            // Active Revision Mode
            _ToolCard(
              icon: Icons.psychology,
              title: isFr ? 'Révision Active' : 'Active Revision',
              description: isFr
                  ? 'Révisez avec des flashcards intelligentes et adaptatives'
                  : 'Review with smart, adaptive flashcards',
              gradient: [Colors.purple.shade400, Colors.purple.shade600],
              onTap: () {
                Navigator.pushNamed(context, '/revision-active');
              },
              isPremium: user?.plan == 'free',
            ),
            const SizedBox(height: 12),
            // Audio Transcription
            _ToolCard(
              icon: Icons.mic,
              title: isFr ? 'Transcription Audio' : 'Audio Transcription',
              description: isFr
                  ? 'Convertissez vos cours audio en texte exploitable'
                  : 'Convert your audio lectures to actionable text',
              gradient: [Colors.teal.shade400, Colors.teal.shade600],
              onTap: () {
                Navigator.pushNamed(context, '/audio-transcription');
              },
              isPremium: user?.plan == 'free' || user?.plan == 'etudiant',
            ),
            const SizedBox(height: 24),
            // Usage Stats
            if (user?.plan != 'free')
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: colorScheme.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: colorScheme.outline.withOpacity(0.2),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          Icons.analytics,
                          color: AppColors.primaryGreen,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          isFr ? 'Utilisation ce mois' : 'This month usage',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _UsageStat(
                      label: isFr ? 'Fiches générées' : 'Summaries created',
                      value: '12',
                      icon: Icons.description,
                    ),
                    const SizedBox(height: 8),
                    _UsageStat(
                      label: isFr ? 'QCM créés' : 'Quizzes created',
                      value: '8',
                      icon: Icons.quiz,
                    ),
                    const SizedBox(height: 8),
                    _UsageStat(
                      label: isFr ? 'Sessions révision' : 'Revision sessions',
                      value: '24',
                      icon: Icons.timeline,
                    ),
                  ],
                ),
              ),
            const SizedBox(height: 16),
            // Upgrade CTA for Free Users
            if (user?.plan == 'free')
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
                        Icon(Icons.stars, color: Colors.white),
                        const SizedBox(width: 8),
                        Text(
                          isFr ? 'Débloquez tous les outils' : 'Unlock all tools',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      isFr
                          ? 'Passez au plan Étudiant pour accéder à tous les outils et réussir vos examens'
                          : 'Upgrade to Student plan to access all tools and ace your exams',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.white.withOpacity(0.95),
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
                          isFr ? 'Voir les plans' : 'View plans',
                          style: TextStyle(
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
    Key? key,
    required this.icon,
    required this.title,
    required this.description,
    required this.gradient,
    required this.onTap,
    this.isPremium = false,
  }) : super(key: key);
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
              color: colorScheme.outline.withOpacity(0.2),
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
                            style: TextStyle(
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
                        color: colorScheme.onSurface.withOpacity(0.7),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(
                Icons.arrow_forward_ios,
                size: 16,
                color: colorScheme.onSurface.withOpacity(0.4),
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
    Key? key,
    required this.label,
    required this.value,
    required this.icon,
  }) : super(key: key);
  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(
          icon,
          size: 20,
          color: AppColors.primaryGreen.withOpacity(0.7),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            label,
            style: TextStyle(fontSize: 14),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: AppColors.primaryGreen,
          ),
        ),
      ],
    );
  }
}
