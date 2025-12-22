import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';

class LegalMonitoringScreen extends StatefulWidget {
  const LegalMonitoringScreen({Key? key}) : super(key: key);
  @override
  State<LegalMonitoringScreen> createState() => _LegalMonitoringScreenState();
}
class _LegalMonitoringScreenState extends State<LegalMonitoringScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final List<String> _selectedTopics = ['Droit Civil', 'Droit Commercial'];
  final List<String> _selectedJurisdictions = ['Côte d\'Ivoire', 'Sénégal'];
  final List<String> _availableTopics = [
    'Droit Civil',
    'Droit Pénal',
    'Droit Commercial',
    'Droit du Travail',
    'Droit Fiscal',
    'Droit Administratif',
    'Droit Constitutionnel',
  ];
  final List<Map<String, dynamic>> _newsItems = [
    {
      'title': 'Nouvelle réforme du Code du Travail en Côte d\'Ivoire',
      'source': 'Journal Officiel',
      'date': '2024-01-15',
      'category': 'Droit du Travail',
      'jurisdiction': 'Côte d\'Ivoire',
      'summary': 'Le gouvernement ivoirien a adopté une série d\'amendements au Code du Travail visant à améliorer les conditions de travail et renforcer la protection des travailleurs.',
      'isNew': true,
    },
    {
      'title': 'Arrêt de la Cour Suprême sur la prescription acquisitive',
      'source': 'Cour Suprême du Sénégal',
      'date': '2024-01-14',
      'category': 'Droit Civil',
      'jurisdiction': 'Sénégal',
      'summary': 'La Cour Suprême précise les conditions d\'application de la prescription acquisitive trentenaire dans un arrêt de principe.',
      'isNew': true,
    },
    {
      'title': 'Loi de Finances 2024 : Nouvelles mesures fiscales',
      'source': 'Ministère des Finances',
      'date': '2024-01-12',
      'category': 'Droit Fiscal',
      'jurisdiction': 'Côte d\'Ivoire',
      'summary': 'La Loi de Finances 2024 introduit plusieurs mesures fiscales importantes pour les entreprises et les particuliers.',
      'isNew': false,
    },
    {
      'title': 'Directive CEDEAO sur le commerce électronique',
      'source': 'CEDEAO',
      'date': '2024-01-10',
      'category': 'Droit Commercial',
      'jurisdiction': 'Régional',
      'summary': 'Nouvelle directive de la CEDEAO établissant un cadre juridique harmonisé pour le commerce électronique en Afrique de l\'Ouest.',
      'isNew': false,
    },
  ];
  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }
  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
  void _toggleTopic(String topic) {
    setState(() {
      if (_selectedTopics.contains(topic)) {
        _selectedTopics.remove(topic);
      } else {
        _selectedTopics.add(topic);
      }
    });
  }
  void _toggleJurisdiction(String jurisdiction) {
    setState(() {
      if (_selectedJurisdictions.contains(jurisdiction)) {
        _selectedJurisdictions.remove(jurisdiction);
      } else {
        _selectedJurisdictions.add(jurisdiction);
      }
    });
  }
  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final user = authProvider.user;
    final colorScheme = Theme.of(context).colorScheme;
    final locale = Localizations.localeOf(context);
    final isFr = locale.languageCode == 'fr';
    final hasAccess = user?.plan == 'professionnel' || user?.plan == 'cabinet';
    return Scaffold(
      appBar: AppBar(
        title: Text(isFr ? 'Veille Juridique' : 'Legal Monitoring'),
        centerTitle: true,
        bottom: hasAccess
            ? TabBar(
                controller: _tabController,
                tabs: [
                  Tab(text: isFr ? 'Actualités' : 'News'),
                  Tab(text: isFr ? 'Mes Alertes' : 'My Alerts'),
                ],
              )
            : null,
      ),
      body: hasAccess
          ? TabBarView(
              controller: _tabController,
              children: [
                _buildNewsTab(context, isFr, colorScheme),
                _buildAlertsTab(context, isFr, colorScheme),
              ],
            )
          : _buildUpgradePrompt(context, isFr),
    );
  }
  Widget _buildNewsTab(BuildContext context, bool isFr, ColorScheme colorScheme) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Colors.cyan.shade400, Colors.cyan.shade600],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Icon(Icons.newspaper, size: 40, color: Colors.white),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isFr ? 'Actualités Juridiques' : 'Legal News',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        isFr
                            ? 'Restez informé des dernières évolutions'
                            : 'Stay informed of the latest developments',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.white.withOpacity(0.9),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          // Quick filters
          Row(
            children: [
              Icon(Icons.filter_list, size: 20),
              const SizedBox(width: 8),
              Text(
                isFr ? 'Filtres rapides' : 'Quick filters',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              FilterChip(
                label: Text(isFr ? 'Nouveautés' : 'New'),
                selected: true,
                onSelected: (value) {},
                selectedColor: AppColors.primaryGreen.withOpacity(0.3),
              ),
              FilterChip(
                label: Text(isFr ? 'Cette semaine' : 'This week'),
                selected: false,
                onSelected: (value) {},
              ),
              FilterChip(
                label: Text(isFr ? 'Mes domaines' : 'My topics'),
                selected: false,
                onSelected: (value) {},
              ),
            ],
          ),
          const SizedBox(height: 24),
          // News List
          ..._newsItems.map((news) {
            return Container(
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: news['isNew']
                      ? AppColors.primaryGreen.withOpacity(0.3)
                      : colorScheme.outline.withOpacity(0.2),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header
                  Container(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            if (news['isNew'])
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  color: AppColors.primaryGreen,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  'NOUVEAU',
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                  ),
                                ),
                              ),
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.blue.shade100,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                news['category'],
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.blue.shade800,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          news['title'],
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            height: 1.3,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Icon(
                              Icons.source,
                              size: 14,
                              color: Colors.grey.shade600,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              news['source'],
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey.shade600,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Icon(
                              Icons.calendar_today,
                              size: 14,
                              color: Colors.grey.shade600,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              news['date'],
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey.shade600,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Icon(
                              Icons.location_on,
                              size: 14,
                              color: Colors.grey.shade600,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              news['jurisdiction'],
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey.shade600,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  Divider(height: 1),
                  // Content
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Text(
                      news['summary'],
                      style: TextStyle(
                        fontSize: 14,
                        height: 1.5,
                        color: Colors.grey.shade800,
                      ),
                    ),
                  ),
                  // Actions
                  Padding(
                    padding: const EdgeInsets.all(16).copyWith(top: 0),
                    child: Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () {
                              // Read full article
                            },
                            icon: Icon(Icons.article, size: 18),
                            label: Text(isFr ? 'Lire' : 'Read'),
                          ),
                        ),
                        const SizedBox(width: 8),
                        IconButton(
                          icon: Icon(Icons.bookmark_border),
                          onPressed: () {
                            // Save for later
                          },
                        ),
                        IconButton(
                          icon: Icon(Icons.share),
                          onPressed: () {
                            // Share
                          },
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }).toList(),
        ],
      ),
    );
  }
  Widget _buildAlertsTab(BuildContext context, bool isFr, ColorScheme colorScheme) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.amber.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.amber.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.notifications_active, color: Colors.amber.shade700),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    isFr
                        ? 'Configurez vos alertes pour recevoir des notifications sur les sujets qui vous intéressent'
                        : 'Configure your alerts to receive notifications on topics of interest',
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.amber.shade900,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          // Topic Selection
          Text(
            isFr ? 'Domaines de droit' : 'Legal domains',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _availableTopics.map((topic) {
              final isSelected = _selectedTopics.contains(topic);
              return FilterChip(
                label: Text(topic),
                selected: isSelected,
                onSelected: (value) => _toggleTopic(topic),
                selectedColor: AppColors.primaryGreen.withOpacity(0.3),
                checkmarkColor: AppColors.primaryGreen,
              );
            }).toList(),
          ),
          const SizedBox(height: 24),
          // Jurisdiction Selection
          Text(
            isFr ? 'Juridictions' : 'Jurisdictions',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: AppConstants.countries.map((country) {
              final countryName = country['name'] as String? ?? '';
              final isSelected = _selectedJurisdictions.contains(countryName);
              return FilterChip(
                label: Text('${country['flag']} $countryName'),
                selected: isSelected,
                onSelected: (value) => _toggleJurisdiction(countryName),
                selectedColor: AppColors.primaryGreen.withOpacity(0.3),
                checkmarkColor: AppColors.primaryGreen,
              );
            }).toList(),
          ),
          const SizedBox(height: 24),
          // Notification Settings
          Text(
            isFr ? 'Paramètres de notification' : 'Notification settings',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          SwitchListTile(
            title: Text(isFr ? 'Notifications push' : 'Push notifications'),
            subtitle: Text(isFr ? 'Recevoir des alertes en temps réel' : 'Receive real-time alerts'),
            value: true,
            activeColor: AppColors.primaryGreen,
            onChanged: (value) {},
          ),
          SwitchListTile(
            title: Text(isFr ? 'Email quotidien' : 'Daily email'),
            subtitle: Text(isFr ? 'Résumé quotidien des actualités' : 'Daily news summary'),
            value: true,
            activeColor: AppColors.primaryGreen,
            onChanged: (value) {},
          ),
          SwitchListTile(
            title: Text(isFr ? 'Alertes urgentes uniquement' : 'Urgent alerts only'),
            subtitle: Text(isFr ? 'Uniquement les changements majeurs' : 'Only major changes'),
            value: false,
            activeColor: AppColors.primaryGreen,
            onChanged: (value) {},
          ),
          const SizedBox(height: 24),
          // Save Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(isFr ? 'Alertes configurées avec succès !' : 'Alerts configured successfully!'),
                    backgroundColor: AppColors.primaryGreen,
                  ),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primaryGreen,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: Text(
                isFr ? 'Enregistrer les alertes' : 'Save alerts',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ),
          ),
        ],
      ),
    );
  }
  Widget _buildUpgradePrompt(BuildContext context, bool isFr) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.lock, size: 80, color: Colors.grey.shade400),
            const SizedBox(height: 24),
            Text(
              isFr ? 'Fonctionnalité Professionnelle' : 'Professional Feature',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            Text(
              isFr
                  ? 'La veille juridique est réservée aux plans Professionnel et Cabinet.'
                  : 'Legal monitoring is reserved for Professional and Firm plans.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 16, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 32),
            ElevatedButton(
              onPressed: () => Navigator.pushNamed(context, '/subscription-plans'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primaryGreen,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              ),
              child: Text(
                isFr ? 'Voir les plans' : 'View plans',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
