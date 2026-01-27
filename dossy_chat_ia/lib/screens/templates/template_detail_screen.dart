import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/template_provider.dart';
import '../../providers/auth_provider.dart';
import '../../services/download_service.dart';
import '../../l10n/app_localizations.dart';

class TemplateDetailScreen extends StatefulWidget {
  final DocumentTemplate template;

  const TemplateDetailScreen({
    super.key,
    required this.template,
  });

  @override
  State<TemplateDetailScreen> createState() => _TemplateDetailScreenState();
}

class _TemplateDetailScreenState extends State<TemplateDetailScreen> {
  bool _isLoading = false;
  final DownloadService _downloadService = DownloadService();

  Future<void> _downloadTemplate() async {
    setState(() => _isLoading = true);

    final progressNotifier = ValueNotifier<double>(0.0);

    try {
      final authProvider = context.read<AuthProvider>();
      final provider = context.read<TemplateProvider>();
        final l10n = AppLocalizations.of(context)!;
      
      if (authProvider.token == null) {
        throw Exception(l10n.notAuthenticated);
      }
      
      final downloadUrl = await provider.downloadTemplate(
        widget.template.id,
        authProvider.token!,
      );
      
      if (downloadUrl != null && mounted) {
        // Afficher le dialog de progression
        DownloadService.showDownloadDialog(
          context,
          widget.template.title,
          progressNotifier,
        );

        // Télécharger le fichier
        final filePath = await _downloadService.downloadFile(
          url: downloadUrl,
          fileName: '${widget.template.title}.${widget.template.fileType}',
          token: authProvider.token,
          onProgress: (received, total) {
            if (total != -1) {
              progressNotifier.value = received / total;
            }
          },
        );

        // Fermer le dialog de progression
        if (mounted) {
          Navigator.of(context).pop();
          
          // Afficher le message de succès
          DownloadService.showDownloadSnackbar(
            context,
            widget.template.title,
            filePath,
          );
        }
      } else {
        throw Exception(l10n.downloadUrlNotAvailable);
      }
    } catch (e) {
      // Fermer le dialog si ouvert
      if (mounted && Navigator.of(context).canPop()) {
        Navigator.of(context).pop();
      }
      
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
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final template = widget.template;
    
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.documentDetails),
        actions: [
          IconButton(
            icon: const Icon(Icons.share),
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(l10n.shareToImplement)),
              );
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header Image
                  Container(
                    width: double.infinity,
                    height: 200,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Theme.of(context).primaryColor,
                          Theme.of(context).primaryColor.withOpacity(0.7),
                        ],
                      ),
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          _getFileIcon(template.fileType),
                          size: 80,
                          color: Colors.white,
                        ),
                        const SizedBox(height: 16),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: Text(
                            template.title,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                            ),
                            textAlign: TextAlign.center,
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
                        // Category and Stats
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            Chip(
                              label: Text(template.categoryName),
                              backgroundColor:
                                  Theme.of(context).primaryColor.withOpacity(0.1),
                            ),
                            Chip(
                              avatar: const Icon(Icons.download, size: 16),
                              label: Text('${template.downloadsCount} ${l10n.downloads}'),
                            ),
                            Chip(
                              avatar: const Icon(Icons.insert_drive_file, size: 16),
                              label: Text(template.fileType.toUpperCase()),
                            ),
                          ],
                        ),

                        const SizedBox(height: 24),

                        // Description
                        if (template.description != null) ...[
                          const Text(
                            'Description',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            template.description!,
                            style: const TextStyle(fontSize: 16, height: 1.5),
                          ),
                          const SizedBox(height: 24),
                        ],

                        // Usage Instructions
                        Text(
                          l10n.usageInstructions,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildInstructionStep(
                                  1,
                                  l10n.downloadTemplate,
                                  l10n.useButtonToDownload,
                                ),
                                const SizedBox(height: 12),
                                _buildInstructionStep(
                                  2,
                                  l10n.fillVariables,
                                  l10n.replaceVariables,
                                ),
                                const SizedBox(height: 12),
                                _buildInstructionStep(
                                  3,
                                  l10n.customizeIfNeeded,
                                  l10n.adaptContent,
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
            onPressed: _isLoading ? null : _downloadTemplate,
            icon: _isLoading
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
              _isLoading ? l10n.downloading : l10n.downloadTemplate,
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

  Widget _buildInstructionStep(int step, String title, String description) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CircleAvatar(
          radius: 16,
          child: Text(
            '$step',
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
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
                  fontSize: 15,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                description,
                style: TextStyle(
                  color: Colors.grey[600],
                  fontSize: 14,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  IconData _getFileIcon(String fileType) {
    switch (fileType.toLowerCase()) {
      case 'pdf':
        return Icons.picture_as_pdf;
      case 'docx':
      case 'doc':
        return Icons.description;
      case 'xlsx':
      case 'xls':
        return Icons.table_chart;
      case 'pptx':
      case 'ppt':
        return Icons.slideshow;
      default:
        return Icons.insert_drive_file;
    }
  }
}
