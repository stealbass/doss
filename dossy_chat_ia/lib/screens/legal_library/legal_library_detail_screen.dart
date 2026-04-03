import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import '../../providers/legal_library_provider.dart';
import '../../providers/auth_provider.dart';
import '../../utils/download_helpers.dart';
import '../../data/models/document_model.dart';
import '../../l10n/app_localizations.dart';

class LegalLibraryDetailScreen extends StatefulWidget {
  final DocumentModel document;
  const LegalLibraryDetailScreen({super.key, required this.document});

  @override
  State<LegalLibraryDetailScreen> createState() => _LegalLibraryDetailScreenState();
}

class _LegalLibraryDetailScreenState extends State<LegalLibraryDetailScreen> {
  bool _isDownloading = false;

  @override
  void initState() {
    super.initState();
    // Track view when screen opens
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _trackView();
    });
  }

  Future<void> _trackView() async {
    try {
      final authProvider = context.read<AuthProvider>();
      final provider = context.read<LegalLibraryProvider>();
      final token = authProvider.token;
      
      if (token != null && token.isNotEmpty) {
        await provider.trackDocumentView(widget.document.id, token);
      }
    } catch (e) {
      // Silently fail - tracking is not critical
      print('DEBUG: Failed to track document view: $e');
    }
  }

  Future<void> _downloadDocument() async {
    final authProvider = context.read<AuthProvider>();
    final user = authProvider.user;
    final plan = (user?.plan ?? '').toLowerCase();
    final isFree = plan == 'free' || plan == 'gratuit';

    if (isFree && !(user?.canDownload ?? false)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Vous avez atteint la limite de 3 téléchargements du plan gratuit. Passez a un plan superieur pour continuer.',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
      return;
    }

    setState(() => _isDownloading = true);

    try {
      final provider = context.read<LegalLibraryProvider>();
      
      await DownloadHelpers.downloadLegalDocument(
        context: context,
        documentId: widget.document.id,
        documentTitle: widget.document.title,
        fileName: '${widget.document.title}.pdf',
        token: authProvider.token,
        fetchDownloadUrl: provider.getDocumentDownloadUrl,
      );

      await authProvider.refreshUser();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isDownloading = false);
      }
    }
  }

  Future<void> _shareDocument() async {
    try {
      final document = widget.document;
      final shareText = '''📄 ${document.title}

Catégorie: ${document.category}
Taille: ${document.fileSizeFormatted}

✨ Partagé depuis DOSSY Chat IA''';

      await Share.share(
        shareText,
        subject: document.title,
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur lors du partage: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final document = widget.document;
    
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.documentDetails),
        actions: [
          IconButton(
            icon: const Icon(Icons.share),
            onPressed: _shareDocument,
          ),
        ],
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header with gradient
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Colors.green.shade600,
                    Colors.green.shade400,
                  ],
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    Icons.description,
                    size: 64,
                    color: Colors.white,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    document.title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),

            // Content
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Metadata - Chips/Badges
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      if (document.category != null && document.category!.isNotEmpty)
                        Chip(
                          avatar: const Icon(Icons.category, size: 16),
                          label: Text(document.category!),
                          backgroundColor: Colors.green.withOpacity(0.1),
                        ),
                      Chip(
                        avatar: const Icon(Icons.insert_drive_file, size: 16),
                        label: Text(document.fileType.toUpperCase()),
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // Stats - Views & Downloads
                  Row(
                    children: [
                      Expanded(
                        child: _buildStatCard(
                          Icons.visibility,
                          '${document.viewsCount}',
                          l10n.views,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildStatCard(
                          Icons.download,
                          '${document.downloadsCount}',
                          l10n.downloads,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // Description
                  if (document.description != null && document.description!.isNotEmpty) ...[
                    const Text(
                      'Description',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Text(
                          document.description!,
                          style: const TextStyle(fontSize: 16, height: 1.5),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Document Info
                  _buildDocumentInfo(),

                  const SizedBox(height: 24),

                  // Usage Guide
                  Text(
                    l10n.usageGuide,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildUsageStep(
                            1,
                            l10n.download,
                            l10n.downloadDocument,
                          ),
                          const Divider(height: 24),
                          _buildUsageStep(
                            2,
                            l10n.consult,
                            'Consultez le document pour comprendre les dispositions juridiques.',
                          ),
                          const Divider(height: 24),
                          _buildUsageStep(
                            3,
                            l10n.apply,
                            'Appliquez les règles et principes du document à votre situation.',
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: ElevatedButton.icon(
            onPressed: _isDownloading ? null : _downloadDocument,
            icon: _isDownloading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                : const Icon(Icons.download),
            label: Text(
              _isDownloading ? l10n.downloading : l10n.download,
              style: const TextStyle(fontSize: 16),
            ),
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
              minimumSize: const Size(double.infinity, 50),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatCard(IconData icon, String value, String label) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Icon(icon, size: 32, color: Colors.green.shade600),
            const SizedBox(height: 8),
            Text(
              value,
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              label,
              style: TextStyle(
                color: Colors.grey[600],
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildUsageStep(int number, String title, String description) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CircleAvatar(
          radius: 16,
          backgroundColor: Colors.green.shade600,
          child: Text(
            '$number',
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                description,
                style: TextStyle(
                  color: Colors.grey[600],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildDocumentInfo() {
    return Card(
      color: Colors.green.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(
              Icons.info,
              size: 40,
              color: Colors.green.shade600,
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                'Document juridique officiel pour vous aider dans vos démarches légales.',
                style: const TextStyle(fontSize: 14),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
