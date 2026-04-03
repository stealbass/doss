import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/services/api_service.dart';

class ReferralScreen extends StatefulWidget {
  const ReferralScreen({super.key});

  @override
  State<ReferralScreen> createState() => _ReferralScreenState();
}

class _ReferralScreenState extends State<ReferralScreen> {
  final ApiService _apiService = ApiService();
  String _referralCode = '';
  int _totalReferrals = 0;
  int _activeReferrals = 0;
  double _commissionTotal = 0;
  double _commissionPending = 0;
  double _commissionPaid = 0;
  String _commissionCurrency = 'XAF';
  double _commissionRate = 20;
  bool _commissionEligible = false;
  int _completedReferrals = 0;

  List<Map<String, dynamic>> _referralHistory = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadReferralData();
  }

  Future<void> _loadReferralData() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final token = authProvider.token;

    if (token == null) {
      setState(() {
        _error = 'Session expirée';
        _loading = false;
      });
      return;
    }

    try {
      final data = await _apiService.getReferralInfo(token);
      if (data['success'] == true) {
        setState(() {
          _referralCode = data['data']['code'] ?? '';
          _totalReferrals = data['data']['total_referrals'] ?? 0;
          _activeReferrals = data['data']['active_referrals'] ?? 0;

          final commissionData = data['data']['commission'] ?? {};
          _commissionRate = _parseDouble(commissionData['rate'], defaultValue: 20);
          _commissionEligible = commissionData['eligible'] == true;
          _completedReferrals = commissionData['completed_referrals'] ?? _activeReferrals;
          _commissionCurrency = commissionData['currency'] ?? 'XAF';

          final rewardsData = data['data']['rewards'] ?? {};
          final summary = rewardsData['summary'] ?? {};
          _commissionTotal = _parseDouble(summary['total_commission']);
          _commissionPending = _parseDouble(summary['pending_commission']);
          _commissionPaid = _parseDouble(summary['paid_commission']);
          _commissionCurrency = summary['currency'] ?? _commissionCurrency;

          _referralHistory = List<Map<String, dynamic>>.from(
            data['data']['history'] ?? [],
          );
          _loading = false;
        });
      } else {
        setState(() {
          _error = data['message'] ?? 'Impossible de récupérer les données';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  void _copyReferralCode() {
    Clipboard.setData(ClipboardData(text: _referralCode));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Code de parrainage copié !'),
        backgroundColor: AppConstants.primaryGreen,
      ),
    );
  }

  void _shareReferralCode() {
    final hasReferralCode = _referralCode.trim().isNotEmpty;
    final storeLink = hasReferralCode
        ? 'https://play.google.com/store/apps/details?id=com.dossy.chatia&referrer=${Uri.encodeComponent('referral_code=$_referralCode')}'
        : 'https://play.google.com/store/apps/details?id=com.dossy.chatia';

    final isFr = Localizations.localeOf(context).languageCode == 'fr';

    final message = isFr
        ? '''
🎓 Rejoins DOSSY CHAT IA - Ton assistant juridique IA !

Clique sur le lien en dessous pour avoir gratuitement accès à ces fonctionnalités :

✅ Bibliothèque OHADA & nationale : tous tes codes à jour dans la poche
✅ Analyse juridique intelligente
✅ Outils pour étudiants et professionnels
✅ Couverture de 14 pays africains francophones

Télécharge l'app ici : $storeLink

#DossyChatIA #DroitAfricain
'''
        : '''
🎓 Join DOSSY CHAT IA - Your AI legal assistant!

Click the link below to get free access to these features:

✅ OHADA & national law library: keep all your legal codes up to date
✅ Smart legal analysis
✅ Tools for students and professionals
✅ Coverage across 14 French-speaking African countries

Download the app here: $storeLink

#DossyChatIA #AfricanLaw
''';

    Share.share(
      message,
      subject: isFr ? 'Invitation DOSSY CHAT IA' : 'DOSSY CHAT IA Invitation',
    );
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final locale = Localizations.localeOf(context);
    final isFr = locale.languageCode == 'fr';

    if (_loading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_error != null) {
      return Scaffold(
        appBar: AppBar(
          title: Text(isFr ? 'Programme de Parrainage' : 'Referral Program'),
          centerTitle: true,
        ),
        body: Center(
          child: Text(
            _error!,
            style: const TextStyle(color: Colors.red),
          ),
        ),
      );
    }

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
                  Row(
                    children: [
                      const Icon(Icons.card_giftcard, size: 40, color: Colors.white),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isFr ? 'Gagnez ensemble !' : 'Earn together!',
                              style: const TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              isFr
                                  ? 'Parrainez vos amis et gagnez 20% de commission'
                                  : 'Refer your friends and earn 20% commission',
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.white.withAlpha((0.9 * 255).round()),
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
                              _referralCode.isEmpty ? '--------' : _referralCode,
                              style: TextStyle(
                                fontSize: 28,
                                fontWeight: FontWeight.bold,
                                color: _referralCode.isEmpty ? Colors.grey : AppConstants.primaryGreen,
                                letterSpacing: 2,
                              ),
                            ),
                            IconButton(
                              icon: Icon(
                                Icons.copy,
                                color: _referralCode.isEmpty ? Colors.grey : AppConstants.primaryGreen,
                              ),
                              onPressed: _referralCode.isEmpty ? null : _copyReferralCode,
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
                          icon: const Icon(Icons.share),
                          label: Text(isFr ? 'Partager' : 'Share'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: AppConstants.primaryGreen,
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
                    icon: Icons.percent,
                    value: '${_formatAmount(_commissionPending)} $_commissionCurrency',
                    label: isFr ? 'Commission à payer' : 'Commission due',
                    color: Colors.orange,
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: colorScheme.outline.withAlpha((0.2 * 255).round())),
              ),
              child: Row(
                children: [
                  Icon(Icons.account_balance_wallet, color: AppConstants.primaryGreen),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isFr ? 'Total commissions' : 'Total commissions',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${_formatAmount(_commissionTotal)} $_commissionCurrency',
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          isFr
                              ? 'Payé: ${_formatAmount(_commissionPaid)} $_commissionCurrency'
                              : 'Paid: ${_formatAmount(_commissionPaid)} $_commissionCurrency',
                          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                    decoration: BoxDecoration(
                      color: _commissionEligible ? Colors.green.shade100 : Colors.orange.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      _commissionEligible
                          ? (isFr ? 'Actif' : 'Active')
                          : (isFr ? 'Dès 10 parrainages' : 'After 10 referrals'),
                      style: TextStyle(
                        color: _commissionEligible ? Colors.green.shade800 : Colors.orange.shade800,
                        fontWeight: FontWeight.bold,
                        fontSize: 11,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            Text(
              isFr ? 'Comment ça marche ?' : 'How it works?',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
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
                  ? 'Après 10 abonnements réussis, vous gagnez 20% de commission sur les paiements de vos filleuls'
                  : 'After 10 successful subscriptions, you earn 20% commission on your referrals payments',
              icon: Icons.emoji_events,
            ),

            const SizedBox(height: 24),

            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  isFr ? 'Historique des parrainages' : 'Referral history',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                TextButton(
                  onPressed: _showFullHistory,
                  child: Text(isFr ? 'Voir tout' : 'View all'),
                ),
              ],
            ),
            const SizedBox(height: 12),

            if (_referralHistory.isEmpty)
              Text(
                isFr ? 'Aucun parrainage pour le moment' : 'No referrals yet',
                style: TextStyle(color: Colors.grey.shade600),
              )
            else
              Column(
                children: _referralHistory.take(5).map((referral) {
                  final status = (referral['status'] ?? '').toString();
                  final isCompleted = status == 'completed';
                  final name = referral['referred_user']?['name'] ??
                      referral['referred_user']?['email'] ??
                      'Invité';
                  final createdAt = referral['created_at'] ?? '';
                  final completedAt = referral['completed_at'];
                  final statusLabel = isCompleted
                      ? (isFr ? 'Terminé' : 'Completed')
                      : (status == 'pending'
                          ? (isFr ? 'En attente' : 'Pending')
                          : (isFr ? 'Enregistré' : 'Registered'));

                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: colorScheme.surface,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: isCompleted
                            ? AppConstants.primaryGreen.withAlpha((0.3 * 255).round())
                            : colorScheme.outline.withAlpha((0.2 * 255).round()),
                      ),
                    ),
                    child: Row(
                      children: [
                        CircleAvatar(
                          backgroundColor: isCompleted
                              ? AppConstants.primaryGreen.withAlpha((0.1 * 255).round())
                              : Colors.grey.shade200,
                          child: Icon(
                            Icons.person,
                            color: isCompleted ? AppConstants.primaryGreen : Colors.grey,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                name,
                                style: const TextStyle(
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
                                    completedAt ?? createdAt,
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
                                      color: isCompleted
                                          ? Colors.green.shade100
                                          : Colors.grey.shade200,
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: Text(
                                      statusLabel,
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                        color: isCompleted
                                            ? Colors.green.shade800
                                            : Colors.grey.shade700,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),

            const SizedBox(height: 24),

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
                        isFr ? 'Règles du parrainage' : 'Referral rules',
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
                        ? '• La commission est de 20% sur chaque paiement réussi de vos filleuls\n'
                            '• Les commissions s\'activent après 10 abonnements réussis\n'
                            '• Le montant total à payer est cumulé dans votre espace parrainage'
                        : '• Commission is 20% on each successful payment by your referrals\n'
                            '• Commissions start after 10 successful subscriptions\n'
                            '• The total payable amount is tracked in your referral space',
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

  double _parseDouble(dynamic value, {double defaultValue = 0}) {
    if (value == null) return defaultValue;
    if (value is num) return value.toDouble();
    return double.tryParse(value.toString()) ?? defaultValue;
  }

  String _formatAmount(double value) {
    if (value == value.roundToDouble()) {
      return value.toStringAsFixed(0);
    }
    return value.toStringAsFixed(2);
  }

  void _showFullHistory() {
    if (_referralHistory.isEmpty) return;
    final isFr = Localizations.localeOf(context).languageCode == 'fr';
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          expand: false,
          builder: (context, scrollController) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        isFr ? 'Historique complet' : 'Full history',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close),
                      )
                    ],
                  ),
                  const SizedBox(height: 12),
                  Expanded(
                    child: ListView.builder(
                      controller: scrollController,
                      itemCount: _referralHistory.length,
                      itemBuilder: (context, index) {
                        final referral = _referralHistory[index];
                        final status = (referral['status'] ?? '').toString();
                        final isCompleted = status == 'completed';
                        final name = referral['referred_user']?['name'] ??
                            referral['referred_user']?['email'] ??
                            'Invité';
                        final createdAt = referral['created_at'] ?? '';
                        final completedAt = referral['completed_at'];
                        final statusLabel = isCompleted
                            ? (isFr ? 'Terminé' : 'Completed')
                            : (status == 'pending'
                                ? (isFr ? 'En attente' : 'Pending')
                                : (isFr ? 'Enregistré' : 'Registered'));

                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Theme.of(context).colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isCompleted
                                  ? AppConstants.primaryGreen.withAlpha((0.3 * 255).round())
                                  : Theme.of(context).colorScheme.outline.withAlpha((0.2 * 255).round()),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    name,
                                    style: const TextStyle(fontWeight: FontWeight.bold),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: isCompleted
                                          ? Colors.green.shade100
                                          : Colors.grey.shade200,
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: Text(
                                      statusLabel,
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                        color: isCompleted
                                            ? Colors.green.shade800
                                            : Colors.grey.shade700,
                                      ),
                                    ),
                                  )
                                ],
                              ),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  const Icon(Icons.calendar_today, size: 12, color: Colors.grey),
                                  const SizedBox(width: 4),
                                  Text(
                                    completedAt ?? createdAt,
                                    style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}

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
        color: color.withAlpha((0.1 * 255).round()),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withAlpha((0.3 * 255).round())),
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
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: color.withAlpha((0.8 * 255).round()),
            ),
          ),
        ],
      ),
    );
  }
}

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
          color: Theme.of(context).colorScheme.outline.withAlpha((0.2 * 255).round()),
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppConstants.primaryGreen.withAlpha((0.1 * 255).round()),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Icon(icon, color: AppConstants.primaryGreen, size: 24),
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
                      decoration: const BoxDecoration(
                        color: AppConstants.primaryGreen,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          number,
                          style: const TextStyle(
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
                        style: const TextStyle(
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
