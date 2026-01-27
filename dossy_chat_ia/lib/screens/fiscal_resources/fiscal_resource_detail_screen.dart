import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/fiscal_resource_provider.dart';
import '../../providers/auth_provider.dart';
import '../../utils/download_helpers.dart';
import '../../l10n/app_localizations.dart';

class FiscalResourceDetailScreen extends StatefulWidget {
  final FiscalResource resource;

  const FiscalResourceDetailScreen({
    super.key,
    required this.resource,
  });

  @override
  State<FiscalResourceDetailScreen> createState() => _FiscalResourceDetailScreenState();
}

class _FiscalResourceDetailScreenState extends State<FiscalResourceDetailScreen> {
  bool _isDownloading = false;

  Future<void> _downloadResource() async {
    setState(() => _isDownloading = true);

    try {
      final authProvider = context.read<AuthProvider>();
      final provider = context.read<FiscalResourceProvider>();
      
      await DownloadHelpers.downloadFiscalResource(
        context: context,
        resourceId: widget.resource.id,
        resourceTitle: widget.resource.title,
        fileType: widget.resource.fileType ?? 'pdf',
        token: authProvider.token,
        fetchDownloadUrl: provider.getResourceDownloadUrl,
      );
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

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final resource = widget.resource;
    
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
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Theme.of(context).primaryColor,
                    Theme.of(context).primaryColor.withOpacity(0.7),
                  ],
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    _getResourceIcon(resource.resourceType),
                    size: 64,
                    color: Colors.white,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    resource.title,
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
                  // Metadata
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      Chip(
                        avatar: const Icon(Icons.category, size: 16),
                        label: Text(resource.resourceType),
                        backgroundColor: Theme.of(context).primaryColor.withOpacity(0.1),
                      ),
                      Chip(
                        avatar: const Icon(Icons.flag, size: 16),
                        label: Text(resource.country),
                      ),
                      if (resource.year != null)
                        Chip(
                          avatar: const Icon(Icons.calendar_today, size: 16),
                          label: Text('${resource.year}'),
                        ),
                      if (resource.version != null)
                        Chip(
                          avatar: const Icon(Icons.numbers, size: 16),
                          label: Text('v${resource.version}'),
                        ),
                      if (resource.fileType != null)
                        Chip(
                          avatar: const Icon(Icons.insert_drive_file, size: 16),
                          label: Text(resource.fileType!.toUpperCase()),
                        ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // Stats
                  Row(
                    children: [
                      Expanded(
                        child: _buildStatCard(
                          Icons.visibility,
                          '${resource.viewsCount}',
                          'Vues',
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildStatCard(
                          Icons.download,
                          '${resource.downloadsCount}',
                          l10n.downloads,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // Description
                  if (resource.description != null && resource.description!.isNotEmpty) ...[
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
                          resource.description!,
                          style: const TextStyle(fontSize: 16, height: 1.5),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Resource Type Information
                  _buildResourceTypeInfo(resource.resourceType),

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
                            l10n.readFiscalInfo,
                          ),
                          const Divider(height: 24),
                          _buildUsageStep(
                            3,
                            l10n.apply,
                            l10n.useForDeclarations,
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
            onPressed: _isDownloading ? null : _downloadResource,
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
            Icon(icon, size: 32, color: Theme.of(context).primaryColor),
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
          child: Text('$number'),
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

  Widget _buildResourceTypeInfo(String type) {
    final l10n = AppLocalizations.of(context)!;
    String info;
    IconData icon;
    
    switch (type.toLowerCase()) {
      case 'déclarations':
        info = l10n.declarationsInfo;
        icon = Icons.description;
        break;
      case 'barèmes':
        info = l10n.scalesInfo;
        icon = Icons.table_chart;
        break;
      case 'formulaires':
        info = l10n.formsInfo;
        icon = Icons.assignment;
        break;
      case 'guides':
        info = 'Guide pratique pour vous accompagner dans vos obligations fiscales.';
        icon = Icons.menu_book;
        break;
      default:
        info = 'Ressource fiscale officielle pour votre juridiction.';
        icon = Icons.info;
    }

    return Card(
      color: Theme.of(context).primaryColor.withOpacity(0.05),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(icon, size: 40, color: Theme.of(context).primaryColor),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                info,
                style: const TextStyle(fontSize: 14),
              ),
            ),
          ],
        ),
      ),
    );
  }

  IconData _getResourceIcon(String type) {
    switch (type.toLowerCase()) {
      case 'déclarations':
        return Icons.description;
      case 'barèmes':
        return Icons.table_chart;
      case 'formulaires':
        return Icons.assignment;
      case 'guides':
        return Icons.menu_book;
      default:
        return Icons.folder;
    }
  }
}
