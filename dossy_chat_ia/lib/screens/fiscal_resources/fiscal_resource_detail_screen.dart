import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../providers/fiscal_resource_provider.dart';
import '../../models/fiscal_resource_model.dart';
import '../../widgets/common_widgets.dart';

class FiscalResourceDetailScreen extends StatefulWidget {
  final int resourceId;

  const FiscalResourceDetailScreen({
    Key? key,
    required this.resourceId,
  }) : super(key: key);

  @override
  State<FiscalResourceDetailScreen> createState() => _FiscalResourceDetailScreenState();
}

class _FiscalResourceDetailScreenState extends State<FiscalResourceDetailScreen> {
  bool _isLoading = false;
  FiscalResource? _resource;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadResourceDetail();
  }

  Future<void> _loadResourceDetail() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final provider = context.read<FiscalResourceProvider>();
      final resource = await provider.getResourceById(widget.resourceId);
      
      setState(() {
        _resource = resource;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Détails de la Ressource'),
        actions: [
          if (_resource != null)
            IconButton(
              icon: const Icon(Icons.bookmark_border),
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Favori à implémenter')),
                );
              },
            ),
          if (_resource != null)
            IconButton(
              icon: const Icon(Icons.share),
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Partage à implémenter')),
                );
              },
            ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return ErrorRetryWidget(
        message: _error!,
        onRetry: _loadResourceDetail,
      );
    }

    if (_resource == null) {
      return const EmptyStateWidget(
        message: 'Ressource introuvable',
        icon: Icons.article_outlined,
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
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Theme.of(context).primaryColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(
                          _getTypeIcon(_resource!.type),
                          color: Theme.of(context).primaryColor,
                          size: 28,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _resource!.title,
                              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                CountryFlag(countryCode: _resource!.country),
                                const SizedBox(width: 8),
                                if (_resource!.applicableYear != null)
                                  Text(
                                    'Année ${_resource!.applicableYear}',
                                    style: TextStyle(
                                      color: Colors.grey[600],
                                      fontSize: 14,
                                    ),
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      if (_resource!.version != null)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.blue.shade100,
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Text(
                            'v${_resource!.version}',
                            style: TextStyle(
                              color: Colors.blue.shade900,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  const Divider(),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _buildStat(
                        icon: Icons.remove_red_eye,
                        label: 'Vues',
                        value: _resource!.viewsCount.toString(),
                      ),
                      if (_resource!.effectiveDate != null)
                        _buildStat(
                          icon: Icons.calendar_today,
                          label: 'En vigueur',
                          value: DateFormat('dd/MM/yyyy').format(_resource!.effectiveDate!),
                        ),
                      if (_resource!.expiryDate != null)
                        _buildStat(
                          icon: Icons.event_busy,
                          label: 'Expire le',
                          value: DateFormat('dd/MM/yyyy').format(_resource!.expiryDate!),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Description
          Text(
            'Description',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _resource!.description,
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 24),

          // Content/Data
          if (_resource!.content != null && _resource!.content!.isNotEmpty) ...[
            Text(
              'Contenu de la Ressource',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            _buildContentSection(_resource!.content!),
            const SizedBox(height: 24),
          ],

          // Legal References
          if (_resource!.legalReferences.isNotEmpty) ...[
            Text(
              'Références Légales',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Card(
              color: Colors.blue.shade50,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: _resource!.legalReferences.map((ref) {
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(Icons.article, size: 16, color: Colors.blue.shade700),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              ref,
                              style: TextStyle(color: Colors.blue.shade900),
                            ),
                          ),
                        ],
                      ),
                    );
                  }).toList(),
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],

          // Tags
          if (_resource!.tags.isNotEmpty) ...[
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
              children: _resource!.tags.map((tag) => Chip(
                label: Text(tag),
                backgroundColor: Colors.grey.shade200,
              )).toList(),
            ),
          ],
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
        Icon(icon, color: Colors.grey[600], size: 20),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 14,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            color: Colors.grey[600],
            fontSize: 11,
          ),
        ),
      ],
    );
  }

  Widget _buildContentSection(Map<String, dynamic> content) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: content.entries.map((entry) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    entry.key.replaceAll('_', ' ').toUpperCase(),
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                      color: Colors.grey,
                    ),
                  ),
                  const SizedBox(height: 4),
                  if (entry.value is Map)
                    ..._buildMapContent(entry.value as Map<String, dynamic>)
                  else if (entry.value is List)
                    ..._buildListContent(entry.value as List)
                  else
                    Text(
                      entry.value.toString(),
                      style: const TextStyle(fontSize: 14),
                    ),
                ],
              ),
            );
          }).toList(),
        ),
      ),
    );
  }

  List<Widget> _buildMapContent(Map<String, dynamic> map) {
    return map.entries.map((entry) {
      return Padding(
        padding: const EdgeInsets.only(left: 16, top: 4),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${entry.key}: ',
              style: const TextStyle(
                fontWeight: FontWeight.w500,
                fontSize: 14,
              ),
            ),
            Expanded(
              child: Text(
                entry.value.toString(),
                style: const TextStyle(fontSize: 14),
              ),
            ),
          ],
        ),
      );
    }).toList();
  }

  List<Widget> _buildListContent(List list) {
    return list.map((item) {
      return Padding(
        padding: const EdgeInsets.only(left: 16, top: 4),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('• ', style: TextStyle(fontSize: 14)),
            Expanded(
              child: Text(
                item.toString(),
                style: const TextStyle(fontSize: 14),
              ),
            ),
          ],
        ),
      );
    }).toList();
  }

  IconData _getTypeIcon(String type) {
    switch (type) {
      case 'salary_grid':
        return Icons.table_chart;
      case 'tax_parameters':
        return Icons.calculate;
      case 'social_contributions':
        return Icons.account_balance;
      case 'leave_rules':
        return Icons.beach_access;
      case 'employment_law':
        return Icons.gavel;
      case 'business_creation':
        return Icons.business;
      case 'tax_forms':
        return Icons.description;
      case 'legal_thresholds':
        return Icons.trending_up;
      case 'labor_regulations':
        return Icons.work;
      case 'accounting_standards':
        return Icons.receipt_long;
      default:
        return Icons.article;
    }
  }
}
