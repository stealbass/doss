import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/legal_library_provider.dart';
import '../../providers/auth_provider.dart';
import '../../core/constants/app_constants.dart';
import '../../data/models/document_model.dart';
import '../../l10n/app_localizations.dart';
import 'legal_library_detail_screen.dart';

class LegalLibraryScreen extends StatefulWidget {
  const LegalLibraryScreen({super.key});

  @override
  State<LegalLibraryScreen> createState() => _LegalLibraryScreenState();
}

class _LegalLibraryScreenState extends State<LegalLibraryScreen> {
  String _selectedCategory = 'All'; // Valeur par défaut temporaire
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();
  int _currentDisplayPage = 1;
  final int _itemsPerPage = 10;
  int? _highlightedDocumentId; // ID du document à mettre en évidence

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      // Initialiser avec le texte localisé après la construction
      setState(() {
        _selectedCategory = AppLocalizations.of(context)!.all;
      });
      
      // Récupérer l'argument documentId si présent
      final args = ModalRoute.of(context)?.settings.arguments as Map<String, dynamic>?;
      if (args != null && args['documentId'] != null) {
        setState(() {
          _highlightedDocumentId = args['documentId'] as int;
        });
      }
      _loadAllDocuments();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  /// Load all documents from all pages to ensure all categories are shown
  Future<void> _loadAllDocuments() async {
    final auth = context.read<AuthProvider>();
    if (auth.token == null) return;
    
    final provider = context.read<LegalLibraryProvider>();
    
    // Load first page to get total pages
    await provider.search(
      query: _searchQuery,
      jurisdiction: auth.user?.jurisdiction ?? 'CM',
      token: auth.token!,
      page: 1,
    );

    // If there are more pages, load them all
    if (provider.totalPages > 1) {
      for (int page = 2; page <= provider.totalPages; page++) {
        await provider.search(
          query: _searchQuery,
          jurisdiction: auth.user?.jurisdiction ?? 'CM',
          token: auth.token!,
          page: page,
          append: true,
        );
      }
    }
    
    // Reset to first page display
    setState(() => _currentDisplayPage = 1);
    
    // Si un document est à mettre en évidence, scroll vers lui
    if (_highlightedDocumentId != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _scrollToHighlightedDocument();
      });
    }
  }
  
  void _scrollToHighlightedDocument() {
    final provider = context.read<LegalLibraryProvider>();
    final allDocs = _getFilteredDocuments(provider.results);
    final docIndex = allDocs.indexWhere((doc) => doc.id == _highlightedDocumentId);
    
    if (docIndex != -1) {
      // Calculer la page où se trouve le document
      final targetPage = (docIndex ~/ _itemsPerPage) + 1;
      setState(() => _currentDisplayPage = targetPage);
      
      // Afficher un message pour indiquer le document
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Document "${allDocs[docIndex].title}" mis en évidence'),
          duration: const Duration(seconds: 3),
          backgroundColor: AppConstants.primaryGreen,
        ),
      );
    }
  }

  Future<void> _loadDocuments() async {
    setState(() => _currentDisplayPage = 1);
    await _loadAllDocuments();
  }

  List<DocumentModel> _getFilteredDocuments(List<DocumentModel> documents) {
    var filtered = documents;
    final allLabel = AppLocalizations.of(context)!.all;

    // Filter by category
    if (_selectedCategory != allLabel) {
      filtered = filtered.where((d) => d.category == _selectedCategory).toList();
    }

    // Filter by search query
    if (_searchQuery.isNotEmpty) {
      filtered = filtered.where((d) =>
        d.title.toLowerCase().contains(_searchQuery.toLowerCase()) ||
        (d.summary?.toLowerCase().contains(_searchQuery.toLowerCase()) ?? false)
      ).toList();
    }

    return filtered;
  }

  Set<String> _getCategories(List<DocumentModel> documents) {
    final allLabel = AppLocalizations.of(context)!.all;
    final categories = documents
      .where((d) => d.category != null && d.category!.isNotEmpty)
      .map((d) => d.category!)
      .toSet();
    return {allLabel, ...categories};
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
            tooltip: l10n.refresh,
          ),
        ],
      ),
      body: Consumer<LegalLibraryProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading && provider.results.isEmpty) {
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
                    onPressed: _loadDocuments,
                    icon: const Icon(Icons.refresh),
                    label: Text(l10n.retry),
                  ),
                ],
              ),
            );
          }

          final filteredDocuments = _getFilteredDocuments(provider.results);
          final categories = _getCategories(provider.results);

          // Calculate pagination
          final totalPages = (filteredDocuments.length / _itemsPerPage).ceil();
          if (_currentDisplayPage > totalPages && totalPages > 0) {
            _currentDisplayPage = totalPages;
          }
          
          final startIndex = (_currentDisplayPage - 1) * _itemsPerPage;
          final endIndex = startIndex + _itemsPerPage;
          final paginatedDocuments = filteredDocuments.sublist(
            startIndex,
            endIndex > filteredDocuments.length ? filteredDocuments.length : endIndex,
          );

          return Column(
            children: [
              // Search Bar
              Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  controller: _searchController,
                  onChanged: (value) => setState(() => _searchQuery = value),
                  onSubmitted: (_) => _loadDocuments(),
                  decoration: InputDecoration(
                    hintText: l10n.searchDocuments,
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
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
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
                          selectedColor: AppConstants.primaryGreen,
                          labelStyle: TextStyle(
                            color: isSelected ? Colors.white : Colors.black,
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

              // Documents List avec scroll
              Expanded(
                child: filteredDocuments.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.inbox, size: 64, color: Colors.grey[400]),
                            const SizedBox(height: 16),
                            Text(
                              l10n.noResults,
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadDocuments,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: paginatedDocuments.length,
                          itemBuilder: (context, index) {
                            final doc = paginatedDocuments[index];
                            final isHighlighted = _highlightedDocumentId != null && doc.id == _highlightedDocumentId;
                            
                            return Card(
                              margin: const EdgeInsets.only(bottom: 12),
                              elevation: isHighlighted ? 8 : 2,
                              color: isHighlighted ? Colors.yellow[50] : null,
                              shape: isHighlighted 
                                ? RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: Colors.amber, width: 2))
                                : RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              child: ListTile(
                                title: Text(doc.title, maxLines: 2, overflow: TextOverflow.ellipsis),
                                subtitle: Text(doc.category ?? '', maxLines: 1, overflow: TextOverflow.ellipsis),
                                trailing: Icon(Icons.arrow_forward_ios, size: 16),
                                onTap: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => LegalLibraryDetailScreen(document: doc),
                                    ),
                                  );
                                },
                              ),
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
                      Text('Page $_currentDisplayPage / $totalPages',
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
