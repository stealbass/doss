import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';

class FicheArretScreen extends StatefulWidget {
  const FicheArretScreen({Key? key}) : super(key: key);
  @override
  State<FicheArretScreen> createState() => _FicheArretScreenState();
}
class _FicheArretScreenState extends State<FicheArretScreen> {
  final _formKey = GlobalKey<FormState>();
  final _decisionController = TextEditingController();
  String? _selectedJurisdiction;
  String? _selectedDomain;
  bool _isGenerating = false;
  Map<String, dynamic>? _generatedFiche;
  final List<String> _domains = [
    'Droit Civil',
    'Droit Pénal',
    'Droit Commercial',
    'Droit Administratif',
    'Droit du Travail',
    'Droit Constitutionnel',
    'Droit International',
    'Autre',
  ];
  @override
  void dispose() {
    _decisionController.dispose();
    super.dispose();
  }
  Future<void> _generateFiche() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isGenerating = true;
    });
    // Simulate API call
    await Future.delayed(const Duration(seconds: 3));
    // Mock generated fiche
    setState(() {
      _generatedFiche = {
        'juridiction': _selectedJurisdiction ?? 'Cour Suprême',
        'numero': 'N° 123/2024',
        'date': '15 Janvier 2024',
        'parties': {
          'demandeur': 'Société ABC SARL',
          'defendeur': 'Monsieur Jean DUPONT',
        },
        'faits': 'Le 10 mars 2023, la société ABC SARL a conclu un contrat de prestation de services avec Monsieur DUPONT. Ce dernier s\'est engagé à fournir des services de conseil juridique moyennant rémunération. Toutefois, après plusieurs mois de collaboration, la société estime que les prestations n\'ont pas été conformes aux attentes et refuse le paiement du solde.',
        'procedure': 'Assignation devant le Tribunal de Première Instance le 15 juin 2023. Audiences des 12 juillet, 5 septembre et 20 octobre 2023. Délibéré mis en délibéré au 15 janvier 2024.',
        'pretentions': {
          'demandeur': 'Demande de condamnation au paiement de 5.000.000 FCFA représentant le solde des honoraires.',
          'defendeur': 'Demande de rejet pour non-conformité des prestations et malfaçons.',
        },
        'moyens': {
          'demandeur': 'Article 1103 du Code Civil sur la force obligatoire des contrats.',
          'defendeur': 'Article 1217 du Code Civil sur l\'exception d\'inexécution.',
        },
        'solution': 'La Cour condamne la société ABC SARL à payer la somme de 3.500.000 FCFA à Monsieur DUPONT, avec intérêts de droit à compter du jugement.',
        'portee': 'L\'arrêt rappelle que l\'exception d\'inexécution ne peut être invoquée de manière disproportionnée et que le juge conserve un pouvoir d\'appréciation sur l\'équité contractuelle.',
      };
      _isGenerating = false;
    });
  }
  void _copyToClipboard(String text) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Copié dans le presse-papier'),
        backgroundColor: AppColors.primaryGreen,
      ),
    );
  }
  String _formatFicheAsText() {
    if (_generatedFiche == null) return '';
    return '''
FICHE D'ARRÊT
=============
📍 JURIDICTION: ${_generatedFiche!['juridiction']}
📋 RÉFÉRENCE: ${_generatedFiche!['numero']}
📅 DATE: ${_generatedFiche!['date']}
👥 PARTIES
----------
• Demandeur: ${_generatedFiche!['parties']['demandeur']}
• Défendeur: ${_generatedFiche!['parties']['defendeur']}
📖 FAITS
--------
${_generatedFiche!['faits']}
⚖️ PROCÉDURE
------------
${_generatedFiche!['procedure']}
🎯 PRÉTENTIONS
--------------
Demandeur: ${_generatedFiche!['pretentions']['demandeur']}
Défendeur: ${_generatedFiche!['pretentions']['defendeur']}
📚 MOYENS
---------
Demandeur: ${_generatedFiche!['moyens']['demandeur']}
Défendeur: ${_generatedFiche!['moyens']['defendeur']}
✅ SOLUTION
-----------
${_generatedFiche!['solution']}
💡 PORTÉE
---------
${_generatedFiche!['portee']}
''';
  }
  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final user = authProvider.user;
    final colorScheme = Theme.of(context).colorScheme;
    final locale = Localizations.localeOf(context);
    final isFr = locale.languageCode == 'fr';
    // Check if user has access
    final hasAccess = user?.plan != 'free';
    return Scaffold(
      appBar: AppBar(
        title: Text(isFr ? 'Générateur de Fiche d\'Arrêt' : 'Case Summary Generator'),
        centerTitle: true,
      ),
      body: hasAccess
          ? SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.blue.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.gavel,
                          size: 40,
                          color: Colors.blue.shade700,
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                isFr
                                    ? 'Fiche d\'Arrêt Automatique'
                                    : 'Automatic Case Summary',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.blue.shade900,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                isFr
                                    ? 'Collez la décision et l\'IA génère la fiche structurée'
                                    : 'Paste the decision and AI generates structured summary',
                                style: TextStyle(
                                  fontSize: 13,
                                  color: Colors.blue.shade700,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  // Form
                  Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Jurisdiction dropdown
                        Text(
                          isFr ? 'Juridiction' : 'Jurisdiction',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          value: _selectedJurisdiction,
                          decoration: InputDecoration(
                            hintText: isFr ? 'Sélectionnez la juridiction' : 'Select jurisdiction',
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                            contentPadding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 12,
                            ),
                          ),
                          items: AppConstants.countries.map((country) {
                            return DropdownMenuItem(
                              value: country['name'],
                              child: Text(
                                '${country['flag']} ${country['name']}',
                              ),
                            );
                          }).toList(),
                          onChanged: (value) {
                            setState(() {
                              _selectedJurisdiction = value;
                            });
                          },
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return isFr
                                  ? 'Veuillez sélectionner une juridiction'
                                  : 'Please select a jurisdiction';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 20),
                        // Domain dropdown
                        Text(
                          isFr ? 'Domaine du droit' : 'Legal domain',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          value: _selectedDomain,
                          decoration: InputDecoration(
                            hintText: isFr ? 'Sélectionnez le domaine' : 'Select domain',
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                            contentPadding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 12,
                            ),
                          ),
                          items: _domains.map((domain) {
                            return DropdownMenuItem(
                              value: domain,
                              child: Text(domain),
                            );
                          }).toList(),
                          onChanged: (value) {
                            setState(() {
                              _selectedDomain = value;
                            });
                          },
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return isFr
                                  ? 'Veuillez sélectionner un domaine'
                                  : 'Please select a domain';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 20),
                        // Decision text input
                        Text(
                          isFr ? 'Texte de la décision' : 'Decision text',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: _decisionController,
                          maxLines: 8,
                          decoration: InputDecoration(
                            hintText: isFr
                                ? 'Collez ici le texte complet de l\'arrêt ou du jugement...'
                                : 'Paste the complete text of the judgment here...',
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                            contentPadding: const EdgeInsets.all(16),
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return isFr
                                  ? 'Veuillez saisir le texte de la décision'
                                  : 'Please enter the decision text';
                            }
                            if (value.trim().length < 100) {
                              return isFr
                                  ? 'Le texte doit contenir au moins 100 caractères'
                                  : 'Text must contain at least 100 characters';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 24),
                        // Generate button
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _isGenerating ? null : _generateFiche,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.blue.shade600,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                            ),
                            child: _isGenerating
                                ? Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      SizedBox(
                                        width: 20,
                                        height: 20,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          color: Colors.white,
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Text(
                                        isFr ? 'Génération en cours...' : 'Generating...',
                                        style: TextStyle(fontSize: 16),
                                      ),
                                    ],
                                  )
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.auto_awesome),
                                      const SizedBox(width: 8),
                                      Text(
                                        isFr ? 'Générer la fiche' : 'Generate summary',
                                        style: TextStyle(
                                          fontSize: 16,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ],
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Generated Fiche
                  if (_generatedFiche != null) ...[
                    const SizedBox(height: 32),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isFr ? '📋 Fiche générée' : '📋 Generated summary',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        IconButton(
                          icon: Icon(Icons.copy),
                          onPressed: () => _copyToClipboard(_formatFicheAsText()),
                          tooltip: isFr ? 'Copier tout' : 'Copy all',
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    _FicheSection(
                      title: isFr ? 'Juridiction & Référence' : 'Jurisdiction & Reference',
                      icon: Icons.account_balance,
                      children: [
                        _InfoRow('Juridiction', _generatedFiche!['juridiction']),
                        _InfoRow('Numéro', _generatedFiche!['numero']),
                        _InfoRow('Date', _generatedFiche!['date']),
                      ],
                    ),
                    _FicheSection(
                      title: isFr ? 'Parties' : 'Parties',
                      icon: Icons.people,
                      children: [
                        _InfoRow('Demandeur', _generatedFiche!['parties']['demandeur']),
                        _InfoRow('Défendeur', _generatedFiche!['parties']['defendeur']),
                      ],
                    ),
                    _FicheSection(
                      title: isFr ? 'Faits' : 'Facts',
                      icon: Icons.description,
                      content: _generatedFiche!['faits'],
                    ),
                    _FicheSection(
                      title: isFr ? 'Procédure' : 'Procedure',
                      icon: Icons.timeline,
                      content: _generatedFiche!['procedure'],
                    ),
                    _FicheSection(
                      title: isFr ? 'Prétentions' : 'Claims',
                      icon: Icons.gavel,
                      children: [
                        _InfoRow('Demandeur', _generatedFiche!['pretentions']['demandeur']),
                        _InfoRow('Défendeur', _generatedFiche!['pretentions']['defendeur']),
                      ],
                    ),
                    _FicheSection(
                      title: isFr ? 'Moyens juridiques' : 'Legal grounds',
                      icon: Icons.book,
                      children: [
                        _InfoRow('Demandeur', _generatedFiche!['moyens']['demandeur']),
                        _InfoRow('Défendeur', _generatedFiche!['moyens']['defendeur']),
                      ],
                    ),
                    _FicheSection(
                      title: isFr ? 'Solution' : 'Decision',
                      icon: Icons.check_circle,
                      content: _generatedFiche!['solution'],
                    ),
                    _FicheSection(
                      title: isFr ? 'Portée de l\'arrêt' : 'Scope',
                      icon: Icons.lightbulb,
                      content: _generatedFiche!['portee'],
                    ),
                    const SizedBox(height: 24),
                    // Export buttons
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () {
                              // Export as PDF
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text('Export PDF en cours...')),
                              );
                            },
                            icon: Icon(Icons.picture_as_pdf),
                            label: Text('PDF'),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () {
                              // Export as DOCX
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text('Export DOCX en cours...')),
                              );
                            },
                            icon: Icon(Icons.description),
                            label: Text('DOCX'),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            )
          : _buildUpgradePrompt(context, isFr),
    );
  }
  Widget _buildUpgradePrompt(BuildContext context, bool isFr) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.lock,
              size: 80,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 24),
            Text(
              isFr ? 'Fonctionnalité Premium' : 'Premium Feature',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              isFr
                  ? 'Le générateur de Fiche d\'Arrêt est réservé aux abonnés Étudiant et supérieurs.'
                  : 'The Case Summary Generator is reserved for Student subscribers and above.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey.shade600,
              ),
            ),
            const SizedBox(height: 32),
            ElevatedButton(
              onPressed: () {
                Navigator.pushNamed(context, '/subscription-plans');
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primaryGreen,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(
                  horizontal: 32,
                  vertical: 16,
                ),
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
// Fiche Section Widget
class _FicheSection extends StatelessWidget {
  final String title;
  final IconData icon;
  final String? content;
  final List<Widget>? children;
  const _FicheSection({
    Key? key,
    required this.title,
    required this.icon,
    this.content,
    this.children,
  }) : super(key: key);
  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
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
              Icon(icon, size: 20, color: Colors.blue.shade600),
              const SizedBox(width: 8),
              Text(
                title,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.blue.shade900,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (content != null)
            Text(
              content!,
              style: TextStyle(fontSize: 14, height: 1.5),
            ),
          if (children != null) ...children!,
        ],
      ),
    );
  }
}
// Info Row Widget
class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value, {Key? key}) : super(key: key);
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              '$label:',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 13,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}
