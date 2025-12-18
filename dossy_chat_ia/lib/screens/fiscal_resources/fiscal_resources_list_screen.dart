import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/fiscal_resource_provider.dart';
import '../../models/fiscal_resource_model.dart';
import '../../widgets/common_widgets.dart';
import 'fiscal_resource_detail_screen.dart';

class FiscalResourcesListScreen extends StatefulWidget {
  const FiscalResourcesListScreen({Key? key}) : super(key: key);

  @override
  State<FiscalResourcesListScreen> createState() => _FiscalResourcesListScreenState();
}

class _FiscalResourcesListScreenState extends State<FiscalResourcesListScreen> {
  String _selectedType = 'all';
  String _selectedYear = DateTime.now().year.toString();
  String _searchQuery = '';

  final List<String> _resourceTypes = [
    'all',
    'salary_grid',
    'tax_parameters',
    'social_contributions',
    'leave_rules',
    'employment_law',
    'business_creation',
    'tax_forms',
    'legal_thresholds',
    'labor_regulations',
    'accounting_standards',
    'other',
  ];

  final Map<String, String> _typeLabels = {
    'all': 'Tous les types',
    'salary_grid': 'Grilles Salariales',
    'tax_parameters': 'Paramètres Fiscaux',
    'social_contributions': 'Cotisations Sociales',
    'leave_rules': 'Règles de Congés',
    'employment_law': 'Droit du Travail',
    'business_creation': 'Création d\'Entreprise',
    'tax_forms': 'Formulaires Fiscaux',
    'legal_thresholds': 'Seuils Légaux',
    'labor_regulations': 'Réglementation du Travail',
    'accounting_standards': 'Normes Comptables',
    'other': 'Autres',
  };

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadResources();
    });
  }

  Future<void> _loadResources() async {
    await context.read<FiscalResourceProvider>().fetchResources(
      type: _selectedType == 'all' ? null : _selectedType,
      year: int.tryParse(_selectedYear),
      search: _searchQuery.isNotEmpty ? _searchQuery : null,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Ressources Fiscales & Sociales'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilters,
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Rechercher une ressource...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                filled: true,
                fillColor: Colors.grey.shade100,
              ),
              onChanged: (value) {
                setState(() => _searchQuery = value);
                _loadResources();
              },
            ),
          ),

          // Active Filters
          if (_selectedType != 'all' || _selectedYear != DateTime.now().year.toString())
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Wrap(
                spacing: 8,
                children: [
                  if (_selectedType != 'all')
                    Chip(
                      label: Text(_typeLabels[_selectedType] ?? _selectedType),
                      onDeleted: () {
                        setState(() => _selectedType = 'all');
                        _loadResources();
                      },
                    ),
                  if (_selectedYear != DateTime.now().year.toString())
                    Chip(
                      label: Text('Année: $_selectedYear'),
                      onDeleted: () {
                        setState(() => _selectedYear = DateTime.now().year.toString());
                        _loadResources();
                      },
                    ),
                ],
              ),
            ),

          // Resources List
          Expanded(
            child: Consumer<FiscalResourceProvider>(
              builder: (context, provider, child) {
                if (provider.isLoading) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (provider.error != null) {
                  return ErrorRetryWidget(
                    message: provider.error!,
                    onRetry: _loadResources,
                  );
                }

                if (provider.resources.isEmpty) {
                  return const EmptyStateWidget(
                    message: 'Aucune ressource fiscale disponible',
                    icon: Icons.article_outlined,
                  );
                }

                return RefreshIndicator(
                  onRefresh: _loadResources,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: provider.resources.length,
                    itemBuilder: (context, index) {
                      final resource = provider.resources[index];
                      return _buildResourceCard(resource);
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildResourceCard(FiscalResource resource) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => FiscalResourceDetailScreen(
                resourceId: resource.id,
              ),
            ),
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: _getTypeColor(resource.type).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(
                      _getTypeIcon(resource.type),
                      color: _getTypeColor(resource.type),
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          resource.title,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _typeLabels[resource.type] ?? resource.type,
                          style: TextStyle(
                            color: Colors.grey[600],
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (resource.version != null)
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade100,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        'v${resource.version}',
                        style: TextStyle(
                          color: Colors.blue.shade900,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                resource.description,
                style: TextStyle(
                  color: Colors.grey[700],
                  fontSize: 14,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  if (resource.applicableYear != null) ...[
                    Icon(Icons.calendar_today, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Text(
                      'Année ${resource.applicableYear}',
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(width: 16),
                  ],
                  Icon(Icons.remove_red_eye, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    '${resource.viewsCount} vues',
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 13,
                    ),
                  ),
                  const Spacer(),
                  CountryFlag(countryCode: resource.country),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showFilters() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Filtres',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  TextButton(
                    onPressed: () {
                      setModalState(() {
                        _selectedType = 'all';
                        _selectedYear = DateTime.now().year.toString();
                      });
                    },
                    child: const Text('Réinitialiser'),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              const Text(
                'Type de ressource',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: _resourceTypes.map((type) {
                  final isSelected = _selectedType == type;
                  return FilterChip(
                    label: Text(_typeLabels[type] ?? type),
                    selected: isSelected,
                    onSelected: (selected) {
                      setModalState(() {
                        _selectedType = selected ? type : 'all';
                      });
                    },
                  );
                }).toList(),
              ),
              const SizedBox(height: 24),
              const Text(
                'Année applicable',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                value: _selectedYear,
                decoration: InputDecoration(
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  filled: true,
                  fillColor: Colors.grey.shade100,
                ),
                items: List.generate(11, (index) {
                  final year = (DateTime.now().year - 5 + index).toString();
                  return DropdownMenuItem(
                    value: year,
                    child: Text(year),
                  );
                }).toList(),
                onChanged: (value) {
                  if (value != null) {
                    setModalState(() => _selectedYear = value);
                  }
                },
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    setState(() {});
                    _loadResources();
                    Navigator.pop(context);
                  },
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: const Text('Appliquer les filtres'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
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

  Color _getTypeColor(String type) {
    switch (type) {
      case 'salary_grid':
        return Colors.green;
      case 'tax_parameters':
        return Colors.blue;
      case 'social_contributions':
        return Colors.purple;
      case 'leave_rules':
        return Colors.orange;
      case 'employment_law':
        return Colors.red;
      case 'business_creation':
        return Colors.teal;
      case 'tax_forms':
        return Colors.indigo;
      case 'legal_thresholds':
        return Colors.amber;
      case 'labor_regulations':
        return Colors.brown;
      case 'accounting_standards':
        return Colors.cyan;
      default:
        return Colors.grey;
    }
  }
}
