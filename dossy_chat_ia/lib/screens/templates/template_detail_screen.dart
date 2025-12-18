import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/template_provider.dart';
import '../../models/template_model.dart';
import '../../widgets/common_widgets.dart';

class TemplateDetailScreen extends StatefulWidget {
  final int templateId;

  const TemplateDetailScreen({
    Key? key,
    required this.templateId,
  }) : super(key: key);

  @override
  State<TemplateDetailScreen> createState() => _TemplateDetailScreenState();
}

class _TemplateDetailScreenState extends State<TemplateDetailScreen> {
  bool _isLoading = false;
  Template? _template;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadTemplateDetail();
  }

  Future<void> _loadTemplateDetail() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final provider = context.read<TemplateProvider>();
      final template = await provider.getTemplateById(widget.templateId);
      
      setState(() {
        _template = template;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _downloadTemplate() async {
    if (_template == null) return;

    setState(() => _isLoading = true);

    try {
      final provider = context.read<TemplateProvider>();
      final downloadUrl = await provider.downloadTemplate(_template!.id);
      
      // In a real app, you would use url_launcher or download the file
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Téléchargement disponible: ${_template!.title}'),
          backgroundColor: Colors.green,
          action: SnackBarAction(
            label: 'OUVRIR',
            textColor: Colors.white,
            onPressed: () {
              // Open download URL
            },
          ),
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Erreur: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Détails du Template'),
        actions: [
          if (_template != null)
            IconButton(
              icon: const Icon(Icons.share),
              onPressed: () {
                // Share functionality
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Partage à implémenter')),
                );
              },
            ),
        ],
      ),
      body: _buildBody(),
      bottomNavigationBar: _template != null
          ? _buildBottomBar()
          : null,
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return ErrorRetryWidget(
        message: _error!,
        onRetry: _loadTemplateDetail,
      );
    }

    if (_template == null) {
      return const EmptyStateWidget(
        message: 'Template introuvable',
        icon: Icons.description_outlined,
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        backgroundColor: Theme.of(context).primaryColor.withOpacity(0.1),
                        child: Icon(
                          _getTemplateIcon(_template!.type),
                          color: Theme.of(context).primaryColor,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _template!.title,
                              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              _template!.category,
                              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      ),
                      PlanBadge(planName: _template!.requiredPlan),
                    ],
                  ),
                  const SizedBox(height: 16),
                  const Divider(),
                  const SizedBox(height: 16),
                  // Stats
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _buildStat(
                        icon: Icons.remove_red_eye,
                        label: 'Vues',
                        value: _template!.viewsCount.toString(),
                      ),
                      _buildStat(
                        icon: Icons.download,
                        label: 'Téléchargements',
                        value: _template!.downloadsCount.toString(),
                      ),
                      _buildStat(
                        icon: Icons.insert_drive_file,
                        label: 'Type',
                        value: _template!.fileType.toUpperCase(),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          
          // Description
          Text(
            'Description',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _template!.description,
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 24),
          
          // Usage Instructions
          if (_template!.usageInstructions.isNotEmpty) ...[
            Text(
              'Instructions d\'utilisation',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Card(
              color: Colors.blue.shade50,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Text(
                  _template!.usageInstructions,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],
          
          // Tags
          if (_template!.tags.isNotEmpty) ...[
            Text(
              'Tags',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _template!.tags.map((tag) => Chip(
                label: Text(tag),
                backgroundColor: Colors.grey.shade200,
              )).toList(),
            ),
            const SizedBox(height: 24),
          ],
          
          // Countries
          Text(
            'Pays supportés',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _template!.countries.map((country) => CountryFlag(
              countryCode: country,
            )).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildStat({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Column(
      children: [
        Icon(icon, color: Colors.grey[600]),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 16,
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
    );
  }

  Widget _buildBottomBar() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 4,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        child: ElevatedButton.icon(
          onPressed: _isLoading ? null : _downloadTemplate,
          icon: _isLoading
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.download),
          label: Text(_isLoading ? 'Téléchargement...' : 'Télécharger le Template'),
          style: ElevatedButton.styleFrom(
            padding: const EdgeInsets.symmetric(vertical: 16),
            minimumSize: const Size.fromHeight(50),
          ),
        ),
      ),
    );
  }

  IconData _getTemplateIcon(String type) {
    switch (type.toLowerCase()) {
      case 'contrat':
        return Icons.description;
      case 'statuts':
        return Icons.business;
      case 'declaration':
        return Icons.assignment;
      case 'courrier':
        return Icons.mail;
      case 'rapport':
        return Icons.analytics;
      case 'formulaire':
        return Icons.form_select;
      default:
        return Icons.insert_drive_file;
    }
  }
}
