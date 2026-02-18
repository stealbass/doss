import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/fiscal_resource_provider.dart';
import '../../providers/auth_provider.dart';
import '../../l10n/app_localizations.dart';

class ResourcesListScreen extends StatefulWidget {
  const ResourcesListScreen({super.key});

  @override
  State<ResourcesListScreen> createState() => _ResourcesListScreenState();
}

class _ResourcesListScreenState extends State<ResourcesListScreen> {
  String _selectedType = 'all';
  int _selectedYear = DateTime.now().year;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadResources();
    });
  }

  Future<void> _loadResources() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final resourceProvider = Provider.of<FiscalResourceProvider>(context, listen: false);

    if (authProvider.token != null) {
      await Future.wait([
        resourceProvider.fetchResources(token: authProvider.token!, year: DateTime.now().year),
        resourceProvider.fetchSalaryGrids(authProvider.token!),
        resourceProvider.fetchTaxParameters(authProvider.token!),
      ]);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.fiscalSocialResources),
        elevation: 0,
      ),
      body: Consumer<FiscalResourceProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.error != null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
                  const SizedBox(height: 16),
                  Text(provider.error!, textAlign: TextAlign.center),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _loadResources,
                    icon: const Icon(Icons.refresh),
                    label: Text(l10n.retry),
                  ),
                ],
              ),
            );
          }

          final filteredResources = _selectedType == 'all'
              ? provider.resources
              : provider.getResourcesByType(_selectedType);

          return Column(
            children: [
              // Filters
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    DropdownButtonFormField<String>(
                      initialValue: _selectedType,
                      decoration: const InputDecoration(
                        labelText: 'Type de ressource',
                        border: OutlineInputBorder(),
                      ),
                      items: [
                        const DropdownMenuItem(value: 'all', child: Text('Tous les types')),
                        DropdownMenuItem(value: 'cgi', child: Text(l10n.taxCodeCGI)),
                        const DropdownMenuItem(value: 'finance_law', child: Text('Loi de Finances')),
                        DropdownMenuItem(value: 'lpf', child: Text(l10n.taxCodeLPF)),
                        const DropdownMenuItem(value: 'labor_code', child: Text('Code du Travail')),
                        const DropdownMenuItem(value: 'collective_agreement', child: Text('Convention Collective')),
                      ],
                      onChanged: (value) {
                        setState(() => _selectedType = value!);
                      },
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<int>(
                            initialValue: _selectedYear,
                            decoration: InputDecoration(
                              labelText: l10n.year,
                              border: const OutlineInputBorder(),
                            ),
                            items: List.generate(6, (index) {
                              final year = DateTime.now().year - index;
                              return DropdownMenuItem(
                                value: year,
                                child: Text('$year'),
                              );
                            }),
                            onChanged: (value) {
                              setState(() => _selectedYear = value!);
                            },
                          ),
                        ),
                        const SizedBox(width: 12),
                        ElevatedButton.icon(
                          onPressed: _loadResources,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Actualiser'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Tabs
              DefaultTabController(
                length: 3,
                child: Expanded(
                  child: Column(
                    children: [
                      TabBar(
                        tabs: [
                          const Tab(text: 'Documents'),
                          const Tab(text: 'Grilles Salariales'),
                          Tab(text: l10n.taxParameters),
                        ],
                      ),
                      Expanded(
                        child: TabBarView(
                          children: [
                            // Documents tab
                            _buildDocumentsList(filteredResources, l10n),
                            // Salary grids tab
                            _buildSalaryGridsList(provider.salaryGrids),
                            // Tax parameters tab
                            _buildTaxParametersList(provider.taxParameters, l10n),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildDocumentsList(List<dynamic> resources, AppLocalizations l10n) {
    if (resources.isEmpty) {
      return Center(child: Text(l10n.noDocumentFound));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: resources.length,
      itemBuilder: (context, index) {
        final resource = resources[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            leading: const Icon(Icons.insert_drive_file, color: Colors.blue),
            title: Text(resource.title),
            subtitle: Text('${resource.resourceTypeDisplay} - ${resource.year}'),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(resource.fileType.toUpperCase()),
                const SizedBox(height: 4),
                Text('${resource.downloadsCount} DL'),
              ],
            ),
            onTap: () {
              // View resource
            },
          ),
        );
      },
    );
  }

  Widget _buildSalaryGridsList(List<dynamic> grids) {
    if (grids.isEmpty) {
      return const Center(child: Text('Aucune grille salariale'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: grids.length,
      itemBuilder: (context, index) {
        final grid = grids[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            title: Text(grid.category),
            subtitle: Text('${grid.country} - ${grid.year}'),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text('Min: ${grid.minSalary.toStringAsFixed(0)} FCFA'),
                Text('Max: ${grid.maxSalary.toStringAsFixed(0)} FCFA'),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildTaxParametersList(List<dynamic> parameters, AppLocalizations l10n) {
    if (parameters.isEmpty) {
      return Center(child: Text(l10n.noTaxParameters));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: parameters.length,
      itemBuilder: (context, index) {
        final param = parameters[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            title: Text(param.taxType),
            subtitle: Text('${param.country} - ${param.year}\n${param.description ?? ""}'),
            isThreeLine: param.description != null,
            trailing: Text(
              '${param.rate.toStringAsFixed(1)}%',
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        );
      },
    );
  }
}
