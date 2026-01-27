import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/legal_library_provider.dart';
import '../../l10n/app_localizations.dart';
import '../../providers/auth_provider.dart';
import '../../data/models/document_model.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';
import '../../utils/download_helpers.dart';
import 'legal_library_detail_screen.dart';

class LegalLibraryScreen extends StatefulWidget {
  const LegalLibraryScreen({super.key});

  @override
  State<LegalLibraryScreen> createState() => _LegalLibraryScreenState();
}

class _LegalLibraryScreenState extends State<LegalLibraryScreen> {
  List<Map<String, dynamic>> _categories = [];
  int? _selectedCategoryId;
  String _selectedCategoryName = 'Toutes';
  String _searchQuery = '';
  int _currentPage = 1;
  int _totalPages = 1;
  bool _isLoadingCategories = false;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadDocuments();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadCategories() async {
    setState(() {
      _isLoadingCategories = true;
    });

    final auth = context.read<AuthProvider>();
    if (auth.token == null) return;

    try {
      final response = await http.get(
        Uri.parse('${AppConstants.baseUrl}/documents/categories'),
        headers: {
          'Authorization': 'Bearer ${auth.token}',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          setState(() {
            _categories = List<Map<String, dynamic>>.from(data['data']);
            _isLoadingCategories = false;
          });
        }
      }
    } catch (e) {
      print('Error loading categories: $e');
      setState(() {
        _isLoadingCategories = false;
      });
    }
  }

  Future<void> _loadDocuments({bool loadMore = false}) async {
    final auth = context.read<AuthProvider>();
    final provider = context.read<LegalLibraryProvider>();
    
    if (auth.token == null) return;

    final page = loadMore ? _currentPage + 1 : 1;

    await provider.search(
      query: _searchQuery,
      jurisdiction: auth.user?.jurisdiction ?? 'CM',
      token: auth.token!,
      categoryId: _selectedCategoryId,
      page: page,
    );

    if (mounted) {
      setState(() {
        _currentPage = page;
        _totalPages = provider.totalPages;
      });
    }
  }

  void _onCategorySelected(int? categoryId, String categoryName) {
    setState(() {
      _selectedCategoryId = categoryId;
      _selectedCategoryName = categoryName;
      _currentPage = 1;
    });
    _loadDocuments();
  }

  void _onSearch(String query) {
    setState(() {
      _searchQuery = query;
      _currentPage = 1;
    });
    _loadDocuments();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.legalLibrary),
        elevation: 0,
        actions: [
          IconButton(
            onPressed: _loadDocuments,
            icon: const Icon(Icons.refresh),
            tooltip: 'Actualiser',
          ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _searchController,
              onSubmitted: _onSearch,
              decoration: InputDecoration(
                hintText: l10n.searchDocuments,
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        onPressed: () {
                          _searchController.clear();
                          _onSearch('');
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

          // Category Filter
          if (_categories.isNotEmpty)
            SizedBox(
              height: 50,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  _CategoryChip(
                    label: 'Toutes',
                    isSelected: _selectedCategoryId == null,
                    onTap: () => _onCategorySelected(null, 'Toutes'),
                  ),
                  const SizedBox(width: 8),
                  ..._categories.map((category) => Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: _CategoryChip(
                          label: '${category['name']} (${category['documents_count']})',
                          isSelected: _selectedCategoryId == category['id'],
                          onTap: () => _onCategorySelected(
                              category['id'], category['name']),
                        ),
                      )),
                ],
              ),
            ),

          const SizedBox(height: 8),

          // Documents List
          Expanded(
            child: Consumer<LegalLibraryProvider>(
              builder: (context, provider, child) {
                if (provider.isLoading && _currentPage == 1) {
                  return const Center(
                    child: CircularProgressIndicator(),
                  );
                }

                if (provider.error != null) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.error_outline,
                            size: 64, color: Colors.red[300]),
                        const SizedBox(height: 16),
                        Text(
                          provider.error!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 16),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: _loadDocuments,
                          icon: const Icon(Icons.refresh),
                          label: Text(l10n.retry),
                        ),
                      ],
                    ),
                  );
                }

                if (provider.results.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.search_off, size: 64, color: Colors.grey[400]),
                        const SizedBox(height: 16),
                        Text(
                          l10n.noSearchResults,
                          style: TextStyle(fontSize: 16, color: Colors.grey[600]),
                        ),
                        if (_searchQuery.isNotEmpty || _selectedCategoryId != null)
                          TextButton(
                            onPressed: () {
                              _searchController.clear();
                              _onCategorySelected(null, 'Toutes');
                              _onSearch('');
                            },
                            child: Text(l10n.showAllDocuments),
                          ),
                      ],
                    ),
                  );
                }

                return Column(
                  children: [
                    // Results count and pagination info
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            '${provider.total} document(s)',
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (_totalPages > 1)
                            Text(
                              'Page $_currentPage sur $_totalPages',
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.grey[600],
                              ),
                            ),
                        ],
                      ),
                    ),

                    // Documents list
                    Expanded(
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: provider.results.length + (_currentPage < _totalPages ? 1 : 0),
                        itemBuilder: (context, index) {
                          if (index == provider.results.length) {
                            // Load more button
                            return Center(
                              child: Padding(
                                padding: const EdgeInsets.all(16),
                                child: ElevatedButton.icon(
                                  onPressed: provider.isLoading ? null : () => _loadDocuments(loadMore: true),
                                  icon: provider.isLoading
                                      ? const SizedBox(
                                          width: 16,
                                          height: 16,
                                          child: CircularProgressIndicator(strokeWidth: 2),
                                        )
                                      : const Icon(Icons.arrow_downward),
                                  label: Text(provider.isLoading ? l10n.loading : l10n.loadMore),
                                ),
                              ),
                            );
                          }

                          final doc = provider.results[index];
                          return _DocumentCard(
                            document: doc,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => LegalLibraryDetailScreen(document: doc),
                                ),
                              );
                            },
                          );
                        },
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _CategoryChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const _CategoryChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return FilterChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (_) => onTap(),
      backgroundColor: Colors.grey[200],
      selectedColor: AppConstants.primaryGreen.withOpacity(0.2),
      checkmarkColor: AppConstants.primaryGreen,
      labelStyle: TextStyle(
        color: isSelected ? AppConstants.primaryGreen : Colors.black87,
        fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
      ),
    );
  }
}

class _DocumentCard extends StatelessWidget {
  final DocumentModel document;
  final VoidCallback onTap;

  const _DocumentCard({
    required this.document,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      child: ListTile(
        contentPadding: const EdgeInsets.all(12),
        leading: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: AppConstants.primaryGreen.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Icon(
            Icons.description,
            color: AppConstants.primaryGreen,
          ),
        ),
        title: Text(
          document.title,
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
            if (document.description != null) ...[
              const SizedBox(height: 4),
              Text(
                document.description!,
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
              children: [
                if (document.category != null) ...[
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.blue[50],
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      document.category!,
                      style: TextStyle(
                        fontSize: 10,
                        color: Colors.blue[700],
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                ],
                Text(
                  document.fileType.toUpperCase(),
                  style: TextStyle(
                    fontSize: 10,
                    color: Colors.grey[600],
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ],
        ),
        trailing: IconButton(
          onPressed: () async {
            final auth = Provider.of<AuthProvider>(context, listen: false);
            final provider = Provider.of<LegalLibraryProvider>(context, listen: false);
            
            await DownloadHelpers.downloadLegalDocument(
              context: context,
              documentId: document.id,
              documentTitle: document.title,
              fileName: '${document.title}.pdf',
              token: auth.token,
              fetchDownloadUrl: provider.getDocumentDownloadUrl,
            );
          },
          icon: const Icon(Icons.download),
          tooltip: 'Télécharger',
        ),
        onTap: onTap,
      ),
    );
  }
}
