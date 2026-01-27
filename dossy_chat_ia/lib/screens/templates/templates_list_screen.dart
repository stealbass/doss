import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/template_provider.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/common_widgets.dart';
import '../../utils/download_helpers.dart';
import '../../l10n/app_localizations.dart';

class TemplatesListScreen extends StatefulWidget {
  const TemplatesListScreen({super.key});

  @override
  State<TemplatesListScreen> createState() => _TemplatesListScreenState();
}

class _TemplatesListScreenState extends State<TemplatesListScreen> {
  String _selectedCategory = 'Tous';
  String _searchQuery = '';
  int _currentDisplayPage = 1;
  final int _itemsPerPage = 10;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadAllTemplates();
    });
  }

  /// Load all templates from all pages to ensure all categories are shown
  Future<void> _loadAllTemplates() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final templateProvider = Provider.of<TemplateProvider>(context, listen: false);

    if (authProvider.token == null) return;

    // Load first page to get total pages
    await templateProvider.fetchTemplates(authProvider.token!);

    // If there are more pages, load them all
    if (templateProvider.totalPages > 1) {
      for (int page = 2; page <= templateProvider.totalPages; page++) {
        await templateProvider.fetchTemplates(
          authProvider.token!,
          page: page,
        );
      }
    }

    // Reset to first page display
    setState(() => _currentDisplayPage = 1);
  }

  Future<void> _loadTemplates() async {
    setState(() => _currentDisplayPage = 1);
    await _loadAllTemplates();
  }

  List<DocumentTemplate> _getFilteredTemplates(
      List<DocumentTemplate> templates) {
    var filtered = templates;

    // Filter by category
    if (_selectedCategory != 'Tous') {
      filtered =
          filtered.where((t) => t.categoryName == _selectedCategory).toList();
    }

    // Filter by search query
    if (_searchQuery.isNotEmpty) {
      filtered = filtered
          .where((t) =>
              t.title.toLowerCase().contains(_searchQuery.toLowerCase()) ||
              (t.description
                      ?.toLowerCase()
                      .contains(_searchQuery.toLowerCase()) ??
                  false))
          .toList();
    }

    return filtered;
  }

  Set<String> _getCategories(List<DocumentTemplate> templates) {
    final categories = templates.map((t) => t.categoryName).toSet();
    return {'Tous', ...categories};
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.documentTemplates),
        elevation: 0,
        actions: [
          IconButton(
            onPressed: _loadTemplates,
            icon: const Icon(Icons.refresh),
            tooltip: 'Actualiser',
          ),
        ],
      ),
      body: Consumer<TemplateProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return const Center(
              child: CircularProgressIndicator(),
            );
          }

          if (provider.error != null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
                  const SizedBox(height: 16),
                  Text(
                    provider.error!,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 16),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _loadTemplates,
                    icon: const Icon(Icons.refresh),
                    label: Text(l10n.retry),
                  ),
                ],
              ),
            );
          }

          final filteredTemplates = _getFilteredTemplates(provider.templates);
          final categories = _getCategories(provider.templates);

          // Calculate pagination
          final totalPages = (filteredTemplates.length / _itemsPerPage).ceil();
          if (_currentDisplayPage > totalPages && totalPages > 0) {
            _currentDisplayPage = totalPages;
          }
          
          final startIndex = (_currentDisplayPage - 1) * _itemsPerPage;
          final endIndex = startIndex + _itemsPerPage;
          final paginatedTemplates = filteredTemplates.sublist(
            startIndex,
            endIndex > filteredTemplates.length ? filteredTemplates.length : endIndex,
          );

          return Column(
            children: [
              // Search Bar
              Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) {
                    setState(() {
                      _searchQuery = value;
                    });
                  },
                  decoration: InputDecoration(
                    hintText: l10n.searchTemplates,
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            onPressed: () {
                              setState(() {
                                _searchQuery = '';
                              });
                            },
                            icon: const Icon(Icons.clear),
                          )
                        : null,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    filled: true,
                    fillColor: Colors.grey[100],
                  ),
                ),
              ),

              // Category Filter - Scrollable horizontally
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  children: [
                    ...categories.map((category) {
                      final isSelected = category == _selectedCategory;
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: FilterChip(
                          label: Text(
                            category,
                            overflow: TextOverflow.ellipsis,
                            maxLines: 1,
                          ),
                          selected: isSelected,
                          onSelected: (selected) {
                            setState(() {
                              _selectedCategory = category;
                              _currentDisplayPage = 1;
                            });
                          },
                          selectedColor: Theme.of(context).primaryColor,
                          labelStyle: TextStyle(
                            color: isSelected ? Colors.white : Colors.black,
                          ),
                          materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        ),
                      );
                    }),
                  ],
                ),
              ),

              const SizedBox(height: 8),

              // Templates List with Pagination
              Expanded(
                child: filteredTemplates.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.inbox,
                                size: 64, color: Colors.grey[400]),
                            const SizedBox(height: 16),
                            Text(
                              l10n.noTemplateFound,
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadTemplates,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: paginatedTemplates.length,
                          itemBuilder: (context, index) {
                            final template = paginatedTemplates[index];
                            return TemplateCard(
                              title: template.title,
                              description: template.description,
                              categoryName: template.categoryName,
                              fileType: template.fileType,
                              downloads: template.downloadsCount,
                              onTap: () {
                                // Navigate to details
                                Navigator.pushNamed(
                                  context,
                                  '/template-details',
                                  arguments: template,
                                );
                              },
                              onDownload: () async {
                                final authProvider = Provider.of<AuthProvider>(
                                    context,
                                    listen: false);
                                final provider = Provider.of<TemplateProvider>(
                                    context,
                                    listen: false);
                                
                                await DownloadHelpers.downloadTemplate(
                                  context: context,
                                  templateId: template.id,
                                  templateTitle: template.title,
                                  fileType: template.fileType,
                                  token: authProvider.token,
                                  fetchDownloadUrl: provider.downloadTemplate,
                                );
                              },
                            );
                          },
                        ),
                      ),
              ),

              // Pagination Controls
              if (filteredTemplates.isNotEmpty && totalPages > 1)
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                  color: Colors.grey[100],
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      ElevatedButton.icon(
                        onPressed: _currentDisplayPage > 1
                            ? () => setState(() => _currentDisplayPage--)
                            : null,
                        icon: const Icon(Icons.chevron_left),
                        label: Text(l10n.previous),
                      ),
                      Text(
                        'Page $_currentDisplayPage / $totalPages',
                        style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                      ),
                      ElevatedButton.icon(
                        onPressed: _currentDisplayPage < totalPages
                            ? () => setState(() => _currentDisplayPage++)
                            : null,
                        label: const Text('Suivant'),
                        icon: const Icon(Icons.chevron_right),
                      ),
                    ],
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}
