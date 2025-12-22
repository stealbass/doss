import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';

class ReferralScreen extends StatefulWidget {
  const ReferralScreen({Key? key}) : super(key: key);
  @override
  State<ReferralScreen> createState() => _ReferralScreenState();
}
class _ReferralScreenState extends State<ReferralScreen> {
  String _referralCode = 'DOSSY2024ABC';
  int _totalReferrals = 12;
  int _activeReferrals = 8;
  double _totalEarnings = 24000; // FCFA
  final List<Map<String, dynamic>> _referralHistory = [
    {
      'name': 'Jean Kouadio',
      'date': '2024-01-10',
      'plan': 'Étudiant',
      'status': 'Actif',
      'earnings': 2000,
    },
    {
      'name': 'Marie Diallo',
      'date': '2024-01-08',
      'plan': 'Professionnel',
      'status': 'Actif',
      'earnings': 5000,
    },
    {
      'name': 'Abdou Traoré',
      'date': '2024-01-05',
      'plan': 'Étudiant',
      'status': 'Expiré',
      'earnings': 0,
    },
  ];
  void _copyReferralCode() {
    Clipboard.setData(ClipboardData(text: _referralCode));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Code de parrainage copié !'),
        backgroundColor: AppColors.primaryGreen,
      ),
    );
  }
  void _shareReferralCode() {
    final message = '''
🎓 Rejoignez DOSSY CHAT IA - Votre Assistant Juridique IA !
Utilisez mon code de parrainage : $_referralCode
✅ Analyse juridique intelligente
✅ Outils pour étudiants & professionnels
✅ 14 pays africains francophones
Téléchargez l'app : https://dossypro.com
#DossyChatIA #DroitAfricain
''';
    Share.share(message, subject: 'Invitation DOSSY CHAT IA');
  }
  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final user = authProvider.user;
    final colorScheme = Theme.of(context).colorScheme;
    final locale = Localizations.localeOf(context);
    final isFr = locale.languageCode == 'fr';
    return Scaffold(
      appBar: AppBar(
        title: Text(isFr ? 'Programme de Parrainage' : 'Referral Program'),
        centerTitle: true,
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
                  Row(
                    children: [
                      Icon(Icons.card_giftcard, size: 40, color: Colors.white),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isFr ? 'Gagnez ensemble !' : 'Earn together!',
                              style: TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              isFr
                                  ? 'Parrainez vos amis et recevez des récompenses'
                                  : 'Refer your friends and get rewards',
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.white.withOpacity(0.9),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      children: [
                        Text(
                          isFr ? 'Votre code de parrainage' : 'Your referral code',
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey.shade700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              _referralCode,
                              style: TextStyle(
                                fontSize: 28,
                                fontWeight: FontWeight.bold,
                                color: AppColors.primaryGreen,
                                letterSpacing: 2,
                              ),
                            ),
                            IconButton(
                              icon: Icon(Icons.copy, color: AppColors.primaryGreen),
                              onPressed: _copyReferralCode,
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _shareReferralCode,
                          icon: Icon(Icons.share),
                          label: Text(isFr ? 'Partager' : 'Share'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: AppColors.primaryGreen,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            // Stats Cards
            Row(
              children: [
                Expanded(
                  child: _StatCard(
                    icon: Icons.people,
                    value: _totalReferrals.toString(),
                    label: isFr ? 'Parrainages' : 'Referrals',
                    color: Colors.blue,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _StatCard(
                    icon: Icons.check_circle,
                    value: _activeReferrals.toString(),
                    label: isFr ? 'Actifs' : 'Active',
                    color: Colors.green,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _StatCard(
                    icon: Icons.payments,
                    value: '${(_totalEarnings / 1000).toStringAsFixed(0)}K',
                    label: 'FCFA',
                    color: Colors.orange,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            // How it works
            Text(
              isFr ? 'Comment ça marche ?' : 'How it works?',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            _HowItWorksCard(
              number: '1',
              title: isFr ? 'Partagez votre code' : 'Share your code',
              description: isFr
                  ? 'Invitez vos amis via WhatsApp, SMS ou email'
                  : 'Invite your friends via WhatsApp, SMS or email',
              icon: Icons.share,
            ),
            _HowItWorksCard(
              number: '2',
              title: isFr ? 'Votre ami s\'inscrit' : 'Your friend signs up',
              description: isFr
                  ? 'Il utilise votre code lors de l\'inscription'
                  : 'They use your code during signup',
              icon: Icons.person_add,
            ),
            _HowItWorksCard(
              number: '3',
              title: isFr ? 'Vous recevez vos gains' : 'You earn rewards',
              description: isFr
                  ? '2 000 FCFA par filleul Étudiant, 5 000 FCFA par Professionnel'
                  : '2,000 FCFA per Student, 5,000 FCFA per Professional',
              icon: Icons.emoji_events,
            ),
            const SizedBox(height: 24),
            // Referral History
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  isFr ? 'Historique des parrainages' : 'Referral history',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                TextButton(
                  onPressed: () {
                    // Show all history
                  },
                  child: Text(isFr ? 'Voir tout' : 'View all'),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ..._referralHistory.map((referral) {
              final isActive = referral['status'] == 'Actif';
              return Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: colorScheme.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isActive
                        ? AppColors.primaryGreen.withOpacity(0.3)
                        : colorScheme.outline.withOpacity(0.2),
                  ),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: isActive
                          ? AppColors.primaryGreen.withOpacity(0.1)
                          : Colors.grey.shade200,
                      child: Icon(
                        Icons.person,
                        color: isActive ? AppColors.primaryGreen : Colors.grey,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            referral['name'],
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 15,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              Icon(
                                Icons.calendar_today,
                                size: 12,
                                color: Colors.grey.shade600,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                referral['date'],
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: isActive
                                      ? Colors.green.shade100
                                      : Colors.grey.shade200,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  referral['status'],
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: isActive ? Colors.green.shade800 : Colors.grey.shade700,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          referral['plan'],
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${referral['earnings']} FCFA',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: isActive ? AppColors.primaryGreen : Colors.grey,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              );
            }).toList(),
            const SizedBox(height: 24),
            // Terms & Conditions
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.blue.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.info_outline, color: Colors.blue.shade700, size: 20),
                      const SizedBox(width: 8),
                      Text(
                        isFr ? 'Conditions du programme' : 'Program terms',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.blue.shade900,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isFr
                        ? '• Les gains sont versés après 30 jours d\'abonnement actif du filleul\n'
                            '• Maximum 50 parrainages par mois\n'
                            '• Le code de parrainage ne peut être utilisé qu\'une seule fois\n'
                            '• Les gains peuvent être retirés ou utilisés comme crédit'
                        : '• Earnings are paid after 30 days of active subscription\n'
                            '• Maximum 50 referrals per month\n'
                            '• Referral code can only be used once\n'
                            '• Earnings can be withdrawn or used as credit',
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.blue.shade800,
                      height: 1.5,
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
// Stat Card Widget
class _StatCard extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;
  final Color color;
  const _StatCard({
    required this.icon,
    required this.value,
    required this.label,
    required this.color,
  });
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 28),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: color.withOpacity(0.8),
            ),
          ),
        ],
      ),
    );
  }
}
// How It Works Card
class _HowItWorksCard extends StatelessWidget {
  final String number;
  final String title;
  final String description;
  final IconData icon;
  const _HowItWorksCard({
    required this.number,
    required this.title,
    required this.description,
    required this.icon,
  });
  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: Theme.of(context).colorScheme.outline.withOpacity(0.2),
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppColors.primaryGreen.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Icon(icon, color: AppColors.primaryGreen, size: 24),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(
                        color: AppColors.primaryGreen,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          number,
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        title,
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  description,
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey.shade600,
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
