import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../l10n/app_localizations.dart';
import '../../../data/services/api_service.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:path_provider/path_provider.dart';
import 'package:open_file/open_file.dart';
import 'dart:io';
import 'dart:typed_data';
import 'dart:convert';

class FicheArretScreen extends StatefulWidget {
  const FicheArretScreen({super.key});

  @override
  State<FicheArretScreen> createState() => _FicheArretScreenState();
}

class _FicheArretScreenState extends State<FicheArretScreen> {
  final _formKey = GlobalKey<FormState>();
  final _decisionController = TextEditingController();
  
  String? _selectedJurisdiction;
  bool _isGenerating = false;
  bool _isExporting = false;
  Map<String, dynamic>? _generatedFiche;

  @override
  void dispose() {
    _decisionController.dispose();
    super.dispose();
  }

  Future<void> _generateFiche() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isGenerating = true);

    try {
      final auth = context.read<AuthProvider>();
      final token = auth.token;
      if (token == null) {
        throw Exception('Session expirée. Veuillez vous reconnecter.');
      }

      final api = ApiService();
      final jurisdiction = _selectedJurisdiction ?? 'Non précisé';
      final decisionText = _decisionController.text.trim();

      final prompt = '''Tu es un assistant juridique expert.
Analyse le texte de décision ci-dessous et produis une FICHE D'ARRÊT précise et concise.

EXIGENCE: Réponds STRICTEMENT en JSON valide sans Markdown ni texte additionnel, au format EXACT suivant:
{
  "juridiction": string,
  "numero": string,
  "date": string,
  "parties": {"demandeur": string, "defendeur": string},
  "faits": string,
  "procedure": string,
  "pretentions": {"demandeur": string, "defendeur": string},
  "moyens": {"demandeur": string, "defendeur": string},
  "solution": string,
  "portee": string,
  "domaine": string
}

Contraintes:
- Déduis le domaine de droit le plus pertinent (ex: Droit civil, pénal, commercial...).
- Si le texte est incomplet (seulement les faits sans la décision), analyse ce qui est fourni et indique \"Décision non fournie dans l'extrait\" pour les champs manquants.
- Pour Solution: si le texte ne contient pas la décision finale, écris \"Solution non fournie dans l'extrait - il s'agit d'un extrait partiel de l'arrêt\".
- Pour Portée: si la solution n'est pas fournie, écris \"Impossible de déterminer la portée sans la décision complète\".
- N'invente PAS de faits.
- Style clair et technique adapté aux étudiants en droit OHADA.

Pays/Juridiction sélectionné: $jurisdiction
Texte de la décision:
"""
$decisionText
"""''';

      final resp = await api.sendChatMessage(
        token: token,
        message: prompt,
        useSimpleRag: false,
        useAdvancedRag: true,
      );

      if (resp['success'] == true) {
        // Parse response - could be either Map or JSON string
        Map<String, dynamic> parsed;
        final response = resp['data']['response'];
        
        if (response is String) {
          // If it's a string, decode it
          parsed = jsonDecode(response) as Map<String, dynamic>;
        } else if (response is Map<String, dynamic>) {
          // Already a Map
          parsed = response;
        } else {
          throw Exception('Format de réponse invalide');
        }
        
        setState(() {
          _generatedFiche = parsed;
          _isGenerating = false;
        });
        
        // Increment summaries generated counter
        final authProvider = context.read<AuthProvider>();
        print('🔔 Calling incrementStat for summaries_generated');
        authProvider.incrementStat('summaries_generated').catchError((e) {
          print('❌ Error incrementing summaries: $e');
        });
      } else {
        throw Exception(resp['message'] ?? 'Erreur lors de la génération');
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isGenerating = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: ${e.toString()}')),
        );
      }
    }
  }

  void _copyToClipboard(String text) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Copié dans le presse-papier'),
        backgroundColor: AppConstants.primaryGreen,
      ),
    );
  }

    Future<void> _exportToPDF() async {
      if (_generatedFiche == null) return;

      setState(() {
        _isExporting = true;
      });

      try {
        // Create PDF document
        final pdf = pw.Document();
        final ficheText = _formatFicheAsText();

        // Split content into paragraphs
        final paragraphs = ficheText.split('\n').where((p) => p.isNotEmpty).toList();

        // Add content to PDF
        pdf.addPage(
          pw.MultiPage(
            pageFormat: PdfPageFormat.a4,
            margin: pw.EdgeInsets.all(20),
            build: (pw.Context context) {
              final List<pw.Widget> widgets = [];

              // Title
              widgets.add(
                pw.Text(
                  'FICHE D\'ARRÊT',
                  style: pw.TextStyle(
                    fontSize: 24,
                    fontWeight: pw.FontWeight.bold,
                  ),
                ),
              );
              widgets.add(pw.SizedBox(height: 20));

              // Content
              for (final para in paragraphs) {
                if (para.contains('=====') || para.contains('-----')) {
                  widgets.add(pw.Divider(height: 10));
                } else if (para.isNotEmpty) {
                  widgets.add(
                    pw.Text(
                      para,
                      style: const pw.TextStyle(fontSize: 11, height: 1.4),
                    ),
                  );
                }
                widgets.add(pw.SizedBox(height: 5));
              }

              return widgets;
            },
            footer: (pw.Context context) {
              return pw.Container(
                alignment: pw.Alignment.centerRight,
                child: pw.Text(
                  'Page ${context.pageNumber}/${context.pagesCount}',
                  style: const pw.TextStyle(fontSize: 10),
                ),
              );
            },
          ),
        );

        // Save PDF
        final directory = await getApplicationDocumentsDirectory();
        final documentsDir = Directory('${directory.path}/Documents');
        if (!await documentsDir.exists()) {
          await documentsDir.create(recursive: true);
        }

        final fileName = 'Fiche_Arret_${DateTime.now().millisecondsSinceEpoch}.pdf';
        final filePath = '${documentsDir.path}/$fileName';
        final pdfBytes = await pdf.save();
        final file = File(filePath);
        await file.writeAsBytes(pdfBytes);

        if (mounted) {
          // Show success dialog
          showDialog(
            context: context,
            builder: (context) => AlertDialog(
              title: const Text('PDF Téléchargé'),
              content: Text('Le fichier "$fileName" a été enregistré avec succès.\n\nTaille: ${(pdfBytes.length / 1024).toStringAsFixed(1)} Ko'),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Fermer'),
                ),
                ElevatedButton.icon(
                  onPressed: () async {
                    Navigator.pop(context);
                    final result = await OpenFile.open(filePath);
                    if (result.type != ResultType.done) {
                      if (mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text('Erreur: ${result.message}')),
                        );
                      }
                    }
                  },
                  icon: const Icon(Icons.open_in_new),
                  label: const Text('Ouvrir'),
                ),
              ],
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Erreur: ${e.toString()}')),
          );
        }
      } finally {
        if (mounted) {
          setState(() {
            _isExporting = false;
          });
        }
      }
    }

    Future<void> _exportToWord() async {
      if (_generatedFiche == null) return;

      setState(() {
        _isExporting = true;
      });

      try {
        // Create DOCX content as formatted text (Word-like format)
        final ficheText = _formatFicheAsText();

        // For Word export, we'll save as a rich text document
        // Using a simple HTML format that Word can read
        final htmlContent = '''
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="UTF-8">
    <style>
      body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
      h1 { color: #1a4d7a; text-align: center; }
      h2 { color: #2e5c8a; margin-top: 20px; border-bottom: 2px solid #2e5c8a; }
      .section { margin: 20px 0; }
      .info-row { margin: 8px 0; }
      .label { font-weight: bold; display: inline-block; width: 120px; }
    </style>
  </head>
  <body>
    <h1>FICHE D'ARRÊT</h1>
  
    <h2>Informations Juridiques</h2>
    <div class="section">
      <div class="info-row"><span class="label">Juridiction:</span> ${_generatedFiche!['juridiction']}</div>
      <div class="info-row"><span class="label">Numéro:</span> ${_generatedFiche!['numero']}</div>
      <div class="info-row"><span class="label">Date:</span> ${_generatedFiche!['date']}</div>
    </div>

    <h2>Parties</h2>
    <div class="section">
      <div class="info-row"><span class="label">Demandeur:</span> ${_generatedFiche!['parties']['demandeur']}</div>
      <div class="info-row"><span class="label">Défendeur:</span> ${_generatedFiche!['parties']['defendeur']}</div>
    </div>

    <h2>Faits</h2>
    <div class="section">
      <p>${_generatedFiche!['faits']}</p>
    </div>

    <h2>Procédure</h2>
    <div class="section">
      <p>${_generatedFiche!['procedure']}</p>
    </div>

    <h2>Prétentions</h2>
    <div class="section">
      <div class="info-row"><span class="label">Demandeur:</span> ${_generatedFiche!['pretentions']['demandeur']}</div>
      <div class="info-row"><span class="label">Défendeur:</span> ${_generatedFiche!['pretentions']['defendeur']}</div>
    </div>

    <h2>Moyens</h2>
    <div class="section">
      <div class="info-row"><span class="label">Demandeur:</span> ${_generatedFiche!['moyens']['demandeur']}</div>
      <div class="info-row"><span class="label">Défendeur:</span> ${_generatedFiche!['moyens']['defendeur']}</div>
    </div>

    <h2>Solution</h2>
    <div class="section">
      <p>${_generatedFiche!['solution']}</p>
    </div>

    <h2>Portée</h2>
    <div class="section">
      <p>${_generatedFiche!['portee']}</p>
    </div>
  </body>
  </html>
  ''';

        // Save as HTML file (can be opened in Word)
        final directory = await getApplicationDocumentsDirectory();
        final documentsDir = Directory('${directory.path}/Documents');
        if (!await documentsDir.exists()) {
          await documentsDir.create(recursive: true);
        }

        final fileName = 'Fiche_Arret_${DateTime.now().millisecondsSinceEpoch}.html';
        final filePath = '${documentsDir.path}/$fileName';
        final file = File(filePath);
        await file.writeAsString(htmlContent);

        if (mounted) {
          // Show success dialog
          showDialog(
            context: context,
            builder: (context) => AlertDialog(
              title: const Text('Fichier WORD Créé'),
              content: Text('Le fichier "$fileName" a été enregistré avec succès.'),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Fermer'),
                ),
                ElevatedButton.icon(
                  onPressed: () async {
                    Navigator.pop(context);
                    final result = await OpenFile.open(filePath);
                    if (result.type != ResultType.done) {
                      if (mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text('Erreur: ${result.message}')),
                        );
                      }
                    }
                  },
                  icon: const Icon(Icons.open_in_new),
                  label: const Text('Ouvrir'),
                ),
              ],
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Erreur: ${e.toString()}')),
          );
        }
      } finally {
        if (mounted) {
          setState(() {
            _isExporting = false;
          });
        }
      }
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
    final l10n = AppLocalizations.of(context)!;

    // Check if user has access
    final hasAccess = user?.plan != 'free';

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.automaticCaseSummary),
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
                                l10n.automaticCaseSummary,
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.blue.shade900,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                l10n.pasteDecisionAIGenerates,
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
                          l10n.jurisdiction,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue: _selectedJurisdiction,
                          decoration: InputDecoration(
                            hintText: l10n.selectJurisdiction,
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
                              return l10n.pleaseSelectJurisdiction;
                            }
                            return null;
                          },
                        ),

                        const SizedBox(height: 20),



                        // Decision text input
                        Text(
                          l10n.decisionText,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: _decisionController,
                          maxLines: 8,
                          decoration: InputDecoration(
                            hintText: l10n.pasteCompleteTextHere,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                            contentPadding: const EdgeInsets.all(16),
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return l10n.pleaseEnterDecisionText;
                            }
                            if (value.trim().length < 100) {
                              return l10n.textMustContainMin100Chars;
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
                                      const SizedBox(
                                        width: 20,
                                        height: 20,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          color: Colors.white,
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Text(
                                        l10n.generating,
                                        style: const TextStyle(fontSize: 16),
                                      ),
                                    ],
                                  )
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      const Icon(Icons.auto_awesome),
                                      const SizedBox(width: 8),
                                      Text(
                                        l10n.generateSummary,
                                        style: const TextStyle(
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
                          l10n.generatedSummary,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.copy),
                          onPressed: () => _copyToClipboard(_formatFicheAsText()),
                          tooltip: l10n.copyAll,
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    _FicheSection(
                      title: l10n.jurisdictionReference,
                      icon: Icons.account_balance,
                      children: [
                        _InfoRow('Juridiction', _generatedFiche!['juridiction']),
                        _InfoRow('Numéro', _generatedFiche!['numero']),
                        _InfoRow('Date', _generatedFiche!['date']),
                      ],
                    ),

                    _FicheSection(
                      title: l10n.parties,
                      icon: Icons.people,
                      children: [
                        _InfoRow('Demandeur', _generatedFiche!['parties']['demandeur']),
                        _InfoRow('Défendeur', _generatedFiche!['parties']['defendeur']),
                      ],
                    ),

                    _FicheSection(
                      title: l10n.facts,
                      icon: Icons.description,
                      content: _generatedFiche!['faits'],
                    ),

                    _FicheSection(
                      title: l10n.procedure,
                      icon: Icons.timeline,
                      content: _generatedFiche!['procedure'],
                    ),

                    _FicheSection(
                      title: l10n.claims,
                      icon: Icons.gavel,
                      children: [
                        _InfoRow('Demandeur', _generatedFiche!['pretentions']['demandeur']),
                        _InfoRow('Défendeur', _generatedFiche!['pretentions']['defendeur']),
                      ],
                    ),

                    _FicheSection(
                      title: l10n.legalGrounds,
                      icon: Icons.book,
                      children: [
                        _InfoRow('Demandeur', _generatedFiche!['moyens']['demandeur']),
                        _InfoRow('Défendeur', _generatedFiche!['moyens']['defendeur']),
                      ],
                    ),

                    _FicheSection(
                      title: l10n.decision,
                      icon: Icons.check_circle,
                      content: _generatedFiche!['solution'],
                    ),

                    _FicheSection(
                      title: l10n.scope,
                      icon: Icons.lightbulb,
                      content: _generatedFiche!['portee'],
                    ),

                    const SizedBox(height: 24),

                    // Export buttons
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                              onPressed: _isExporting ? null : _exportToPDF,
                            icon: const Icon(Icons.picture_as_pdf),
                              label: _isExporting
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(strokeWidth: 2),
                                    )
                                  : const Text('PDF'),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                              onPressed: _isExporting ? null : _exportToWord,
                            icon: const Icon(Icons.description),
                              label: _isExporting
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(strokeWidth: 2),
                                    )
                                  : const Text('WORD'),
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
          : _buildUpgradePrompt(context),
    );
  }

  Widget _buildUpgradePrompt(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
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
              l10n.proFeature,
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              l10n.upgradeToStudentPlan,
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
                backgroundColor: AppConstants.primaryGreen,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(
                  horizontal: 32,
                  vertical: 16,
                ),
              ),
              child: Text(
                l10n.viewPlans,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
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
    required this.title,
    required this.icon,
    this.content,
    this.children,
  });

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
          color: colorScheme.outline.withAlpha((0.2 * 255).round()),
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
              style: const TextStyle(fontSize: 14, height: 1.5),
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

  const _InfoRow(this.label, this.value);

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
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 13,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}
