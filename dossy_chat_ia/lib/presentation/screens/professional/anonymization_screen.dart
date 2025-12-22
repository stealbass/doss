import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';

class AnonymizationScreen extends StatefulWidget {
  const AnonymizationScreen({Key? key}) : super(key: key);
  @override
  State<AnonymizationScreen> createState() => _AnonymizationScreenState();
}
class _AnonymizationScreenState extends State<AnonymizationScreen> {
  bool _isProcessing = false;
  String? _selectedFile;
  Map<String, dynamic>? _detectedData;
  bool _showPreview = false;
  final List<Map<String, String>> _anonymizationHistory = [
    {
      'filename': 'contrat_location_2024.pdf',
      'date': '2024-01-15',
      'itemsFound': '12',
    },
    {
      'filename': 'jugement_tribunal_123.docx',
      'date': '2024-01-12',
      'itemsFound': '8',
    },
  ];
  Future<void> _pickDocument() async {
    // Simulate file picker
    await Future.delayed(const Duration(milliseconds: 500));
    setState(() {
      _selectedFile = 'contrat_vente_immeuble_2024.pdf';
      _detectedData = null;
      _showPreview = false;
    });
  }
  Future<void> _processDocument() async {
    setState(() {
      _isProcessing = true;
    });
    // Simulate AI processing
    await Future.delayed(const Duration(seconds: 3));
    setState(() {
      _detectedData = {
        'sensitiveItems': [
          {
            'type': 'Nom complet',
            'value': 'Jean-Baptiste KOUADIO',
            'occurrences': 5,
            'icon': Icons.person,
          },
          {
            'type': 'Adresse',
            'value': '12 Boulevard de la République, Abidjan',
            'occurrences': 3,
            'icon': Icons.location_on,
          },
          {
            'type': 'Numéro de téléphone',
            'value': '+225 07 XX XX XX XX',
            'occurrences': 2,
            'icon': Icons.phone,
          },
          {
            'type': 'Email',
            'value': 'jean.kouadio@email.com',
            'occurrences': 1,
            'icon': Icons.email,
          },
          {
            'type': 'Numéro CNI',
            'value': 'CI-XXXX-XXXX-XXX',
            'occurrences': 2,
            'icon': Icons.badge,
          },
          {
            'type': 'Numéro bancaire',
            'value': 'FR76 XXXX XXXX XXXX',
            'occurrences': 1,
            'icon': Icons.account_balance,
          },
        ],
        'totalOccurrences': 14,
      };
      _isProcessing = false;
      _showPreview = true;
    });
  }
  void _downloadAnonymizedDocument() {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Document anonymisé téléchargé avec succès !'),
        backgroundColor: AppColors.primaryGreen,
      ),
    );
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
        title: Text(isFr ? 'Anonymisation de Documents' : 'Document Anonymization'),
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
                      gradient: LinearGradient(
                        colors: [Colors.indigo.shade400, Colors.indigo.shade600],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.shield, size: 40, color: Colors.white),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                isFr ? 'Protection des données' : 'Data Protection',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                isFr
                                    ? 'Détection et anonymisation automatique des données sensibles'
                                    : 'Automatic detection and anonymization of sensitive data',
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
                  // Upload Section
                  Text(
                    isFr ? 'Charger un document' : 'Upload document',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  GestureDetector(
                    onTap: _pickDocument,
                    child: Container(
                      padding: const EdgeInsets.all(32),
                      decoration: BoxDecoration(
                        color: colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: AppColors.primaryGreen.withOpacity(0.3),
                          width: 2,
                          style: BorderStyle.solid,
                        ),
                      ),
                      child: Column(
                        children: [
                          Icon(
                            Icons.cloud_upload,
                            size: 60,
                            color: AppColors.primaryGreen,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            isFr ? 'Cliquez pour sélectionner un fichier' : 'Click to select a file',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            isFr ? 'PDF, DOCX, DOC (max 10 MB)' : 'PDF, DOCX, DOC (max 10 MB)',
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.grey.shade600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  if (_selectedFile != null) ...[
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.green.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.insert_drive_file, color: Colors.green.shade700),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _selectedFile!,
                                  style: TextStyle(fontWeight: FontWeight.bold),
                                ),
                                Text(
                                  isFr ? 'Fichier sélectionné' : 'File selected',
                                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            icon: Icon(Icons.close, color: Colors.red),
                            onPressed: () {
                              setState(() {
                                _selectedFile = null;
                                _detectedData = null;
                                _showPreview = false;
                              });
                            },
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _isProcessing ? null : _processDocument,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.indigo.shade600,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        child: _isProcessing
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
                                  Text(isFr ? 'Analyse en cours...' : 'Analyzing...'),
                                ],
                              )
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.search),
                                  const SizedBox(width: 8),
                                  Text(
                                    isFr ? 'Détecter les données sensibles' : 'Detect sensitive data',
                                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                      ),
                    ),
                  ],
                  // Detected Data
                  if (_detectedData != null) ...[
                    const SizedBox(height: 32),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isFr ? '🔍 Données détectées' : '🔍 Detected data',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.red.shade100,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            '${_detectedData!['totalOccurrences']} ${isFr ? 'occurrences' : 'occurrences'}',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: Colors.red.shade800,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    ..._detectedData!['sensitiveItems'].map<Widget>((item) {
                      return Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: colorScheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: Colors.orange.withOpacity(0.3),
                          ),
                        ),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: Colors.orange.shade100,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Icon(
                                item['icon'],
                                color: Colors.orange.shade700,
                                size: 24,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item['type'],
                                    style: TextStyle(
                                      fontSize: 13,
                                      color: Colors.grey.shade600,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    item['value'],
                                    style: TextStyle(
                                      fontSize: 15,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.red.shade100,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                '×${item['occurrences']}',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.red.shade800,
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    }).toList(),
                    const SizedBox(height: 24),
                    // Preview Toggle
                    SwitchListTile(
                      title: Text(
                        isFr ? 'Prévisualiser le document anonymisé' : 'Preview anonymized document',
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                      subtitle: Text(
                        isFr
                            ? 'Voir le résultat avant de télécharger'
                            : 'See the result before downloading',
                      ),
                      value: _showPreview,
                      activeColor: AppColors.primaryGreen,
                      onChanged: (value) {
                        setState(() {
                          _showPreview = value;
                        });
                      },
                    ),
                    if (_showPreview) ...[
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade100,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.grey.shade300),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isFr ? 'Aperçu du document anonymisé' : 'Anonymized document preview',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                              ),
                            ),
                            const SizedBox(height: 12),
                            Text(
                              'CONTRAT DE VENTE D\'IMMEUBLE\n\n'
                              'Entre les soussignés :\n\n'
                              'Vendeur : [ANONYMISÉ]\n'
                              'Né(e) le : [ANONYMISÉ]\n'
                              'Demeurant : [ANONYMISÉ]\n'
                              'Téléphone : [ANONYMISÉ]\n'
                              'Email : [ANONYMISÉ]\n'
                              'CNI N° : [ANONYMISÉ]\n\n'
                              'D\'une part,\n\n'
                              'Et...',
                              style: TextStyle(
                                fontSize: 13,
                                height: 1.5,
                                fontFamily: 'monospace',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                    const SizedBox(height: 24),
                    // Download Button
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _downloadAnonymizedDocument,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primaryGreen,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.download),
                            const SizedBox(width: 8),
                            Text(
                              isFr ? 'Télécharger le document anonymisé' : 'Download anonymized document',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 32),
                  // History
                  Text(
                    isFr ? 'Historique des anonymisations' : 'Anonymization history',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  ..._anonymizationHistory.map((item) {
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: colorScheme.outline.withOpacity(0.2),
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.history, color: Colors.indigo.shade600),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item['filename']!,
                                  style: TextStyle(fontWeight: FontWeight.bold),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${item['date']} • ${item['itemsFound']} ${isFr ? 'éléments masqués' : 'items hidden'}',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey.shade600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            icon: Icon(Icons.download, color: AppColors.primaryGreen),
                            onPressed: () {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(isFr ? 'Téléchargement...' : 'Downloading...')),
                              );
                            },
                          ),
                        ],
                      ),
                    );
                  }).toList(),
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
            Icon(Icons.lock, size: 80, color: Colors.grey.shade400),
            const SizedBox(height: 24),
            Text(
              isFr ? 'Fonctionnalité Professionnelle' : 'Professional Feature',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            Text(
              isFr
                  ? 'L\'anonymisation de documents est réservée aux plans Professionnel et Cabinet.'
                  : 'Document anonymization is reserved for Professional and Firm plans.',
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
