import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/fiscal_resource_provider.dart';
import '../../providers/auth_provider.dart';
import '../../core/constants/app_constants.dart';
import '../../l10n/app_localizations.dart';
import 'fiscal_resource_detail_screen.dart';

class FiscalResourcesListScreen extends StatefulWidget {
  const FiscalResourcesListScreen({super.key});

  @override
  State<FiscalResourcesListScreen> createState() => _FiscalResourcesListScreenState();
}

class _FiscalResourcesListScreenState extends State<FiscalResourcesListScreen> {
  String _selectedCategory = 'Tous';
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();
  final int _currentYear = DateTime.now().year;
  int _currentDisplayPage = 1;
  final int _itemsPerPage = 10;

  List<String> _buildCategories(List<FiscalResource> resources) {
    final allLabel = AppLocalizations.of(context)!.all;
    final set = <String>{allLabel};
    for (final r in resources) {
      if (r.categoryName != null && r.categoryName!.isNotEmpty) {
        set.add(r.categoryName!);
      }
    }
    return set.toList();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      setState(() {
        _selectedCategory = AppLocalizations.of(context)!.all;
      });
      _loadAllResources();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  /// Load all resources from all pages to ensure all categories are shown
  Future<void> _loadAllResources() async {
    final authProvider = context.read<AuthProvider>();
    if (authProvider.token == null) return;
    
    final provider = context.read<FiscalResourceProvider>();

    // Load first page to get total pages
    await provider.fetchResources(
      token: authProvider.token,
      type: null,
      year: _currentYear,
      search: _searchQuery.isNotEmpty ? _searchQuery : null,
      page: 1,
    );

    // If there are more pages, load them all
    if (provider.totalPages > 1) {
      for (int page = 2; page <= provider.totalPages; page++) {
        await provider.fetchResources(
          token: authProvider.token,
          type: null,
          year: _currentYear,
          search: _searchQuery.isNotEmpty ? _searchQuery : null,
          page: page,
          append: true,
        );
      }
    }

    // Reset to first page display
    setState(() => _currentDisplayPage = 1);
  }

  Future<void> _loadResources() async {
    setState(() => _currentDisplayPage = 1);
    await _loadAllResources();
  }

  List<FiscalResource> _getFilteredResources(List<FiscalResource> resources) {
    var filtered = resources;
    final allLabel = AppLocalizations.of(context)!.all;

    if (_selectedCategory != allLabel) {
      filtered = filtered
          .where((r) => r.categoryName == _selectedCategory)
          .toList();
    }

    if (_searchQuery.isNotEmpty) {
      filtered = filtered.where((r) =>
          r.title.toLowerCase().contains(_searchQuery.toLowerCase()) ||
          (r.description?.toLowerCase().contains(_searchQuery.toLowerCase()) ?? false)
      ).toList();
    }

    return filtered;
  }

  @override
  Widget build(BuildContext context) {
      final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.fiscalSocialResources),
        elevation: 0,
        actions: [
          IconButton(
            onPressed: _loadResources,
            icon: const Icon(Icons.refresh),
            tooltip: l10n.refresh,
          ),
        ],
      ),
      body: Consumer<FiscalResourceProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.resources.isEmpty) {
            return const Center(child: CircularProgressIndicator());
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
                    onPressed: _loadResources,
                    icon: const Icon(Icons.refresh),
                    label: Text(l10n.retry),
                  ),
                ],
              ),
            );
          }

          final categories = _buildCategories(provider.resources);
          final filteredResources = _getFilteredResources(provider.resources);

          // Calculate pagination
          final totalPages = (filteredResources.length / _itemsPerPage).ceil();
          if (_currentDisplayPage > totalPages && totalPages > 0) {
            _currentDisplayPage = totalPages;
          }
          
          final startIndex = (_currentDisplayPage - 1) * _itemsPerPage;
          final endIndex = startIndex + _itemsPerPage;
          final paginatedResources = filteredResources.sublist(
            startIndex,
            endIndex > filteredResources.length ? filteredResources.length : endIndex,
          );

          return Column(
            children: [
              // Search Bar
              Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  controller: _searchController,
                  onChanged: (value) => setState(() => _searchQuery = value),
                  onSubmitted: (_) => _loadResources(),
                  decoration: InputDecoration(
                    hintText: l10n.searchResources,
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _searchQuery = '');
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
                          backgroundColor: Colors.grey[200],
                          selectedColor: AppConstants.primaryGreen.withOpacity(0.2),
                          checkmarkColor: AppConstants.primaryGreen,
                          labelStyle: TextStyle(
                            color: isSelected ? AppConstants.primaryGreen : Colors.black87,
                            fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
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

              // Resources List
              Expanded(
                child: filteredResources.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.inbox, size: 64, color: Colors.grey[400]),
                            const SizedBox(height: 16),
                            Text(
                              l10n.noResourceFound,
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadResources,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: paginatedResources.length,
                          itemBuilder: (context, index) {
                            final resource = paginatedResources[index];
                            return _ResourceCard(
                              resource: resource,
                              onTap: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => FiscalResourceDetailScreen(
                                      resource: resource,
                                    ),
                                  ),
                                );
                              },
                            );
                          },
                        ),
                      ),
              ),

              // Pagination Controls
              if (totalPages > 1)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.chevron_left),
                        onPressed: _currentDisplayPage > 1
                            ? () => setState(() => _currentDisplayPage--)
                            : null,
                      ),
                      Text(
                        'Page $_currentDisplayPage / $totalPages',
                        style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.chevron_right),
                        onPressed: _currentDisplayPage < totalPages
                            ? () => setState(() => _currentDisplayPage++)
                            : null,
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

class _ResourceCard extends StatelessWidget {
  final FiscalResource resource;
  final VoidCallback onTap;

  const _ResourceCard({
    required this.resource,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      child: ListTile(
        contentPadding: const EdgeInsets.all(12),
        leading: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: Colors.blue.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Icon(
            Icons.description,
            color: Colors.blue,
          ),
        ),
        title: Text(
          resource.title,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (resource.description != null) ...[
              const SizedBox(height: 4),
              Text(
                resource.description!,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey[600],
                ),
              ),
            ],
            const SizedBox(height: 4),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Flexible(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.orange[50],
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      '${l10n.year} ${resource.year}',
                      overflow: TextOverflow.ellipsis,
                      maxLines: 1,
                      style: TextStyle(
                        fontSize: 10,
                        color: Colors.orange[700],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(
                    '${resource.viewsCount} vues',
                    overflow: TextOverflow.ellipsis,
                    maxLines: 1,
                    style: TextStyle(
                      fontSize: 10,
                      color: Colors.grey[600],
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }
}
