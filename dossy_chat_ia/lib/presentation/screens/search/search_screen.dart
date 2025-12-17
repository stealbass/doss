import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/services/search_service.dart';
import '../../../data/models/document_model.dart';
import '../../widgets/search/search_filter_widget.dart';
import '../../widgets/search/search_history_widget.dart';
import '../../widgets/search/search_result_card.dart';

/// Écran de recherche avancée avec filtres et historique
/// Fonctionnalités :
/// - Recherche plein texte et vectorielle
/// - Filtres par juridiction, type, date
/// - Historique et suggestions
/// - Pagination et résultats en temps réel
class SearchScreen extends StatefulWidget {
  const SearchScreen({Key? key}) : super(key: key);

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> with SingleTickerProviderStateMixin {
  final SearchService _searchService = SearchService();
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  
  late TabController _tabController;
  
  List<Document> _searchResults = [];
  List<String> _searchHistory = [];
  List<String> _suggestions = [];
  
  bool _isLoading = false;
  bool _hasMore = true;
  int _currentPage = 1;
  
  // Filtres
  String? _selectedJurisdiction;
  String? _selectedCategory;
  DateTime? _startDate;
  DateTime? _endDate;
  bool _useVectorSearch = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadSearchHistory();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    _tabController.dispose();
    super.dispose();
  }

  /// Charger l'historique de recherche
  Future<void> _loadSearchHistory() async {
    try {
      final history = await _searchService.getSearchHistory();
      setState(() {
        _searchHistory = history.map((h) => h.query).toList();
      });
    } catch (e) {
      debugPrint('Erreur chargement historique: $e');
    }
  }

  /// Effectuer une recherche
  Future<void> _performSearch({bool isLoadMore = false}) async {
    if (_searchController.text.trim().isEmpty) return;

    setState(() {
      _isLoading = true;
      if (!isLoadMore) {
        _searchResults = [];
        _currentPage = 1;
      }
    });

    try {
      final results = _useVectorSearch
          ? await _searchService.vectorSearch(
              query: _searchController.text,
              jurisdiction: _selectedJurisdiction,
              category: _selectedCategory,
              startDate: _startDate,
              endDate: _endDate,
              page: _currentPage,
              limit: 20,
            )
          : await _searchService.fullTextSearch(
              query: _searchController.text,
              jurisdiction: _selectedJurisdiction,
              category: _selectedCategory,
              startDate: _startDate,
              endDate: _endDate,
              page: _currentPage,
              limit: 20,
            );

      setState(() {
        if (isLoadMore) {
          _searchResults.addAll(results);
        } else {
          _searchResults = results;
        }
        _hasMore = results.length >= 20;
        _isLoading = false;
      });

      // Sauvegarder dans l'historique
      await _searchService.saveSearchHistory(
        query: _searchController.text,
        resultsCount: results.length,
        filters: {
          'jurisdiction': _selectedJurisdiction,
          'category': _selectedCategory,
          'useVectorSearch': _useVectorSearch,
        },
      );
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      _showErrorSnackbar('Erreur lors de la recherche: $e');
    }
  }

  /// Charger les suggestions
  Future<void> _loadSuggestions(String query) async {
    if (query.length < 2) {
      setState(() {
        _suggestions = [];
      });
      return;
    }

    try {
      final suggestions = await _searchService.getSearchSuggestions(query);
      setState(() {
        _suggestions = suggestions;
      });
    } catch (e) {
      debugPrint('Erreur suggestions: $e');
    }
  }

  /// Défilement infini
  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent * 0.9) {
      if (!_isLoading && _hasMore) {
        _currentPage++;
        _performSearch(isLoadMore: true);
      }
    }
  }

  /// Afficher un message d'erreur
  void _showErrorSnackbar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  /// Réinitialiser les filtres
  void _resetFilters() {
    setState(() {
      _selectedJurisdiction = null;
      _selectedCategory = null;
      _startDate = null;
      _endDate = null;
      _useVectorSearch = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Recherche Juridique'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(icon: Icon(Icons.search), text: 'Recherche'),
            Tab(icon: Icon(Icons.filter_list), text: 'Filtres'),
            Tab(icon: Icon(Icons.history), text: 'Historique'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildSearchTab(),
          _buildFiltersTab(),
          _buildHistoryTab(),
        ],
      ),
    );
  }

  /// Onglet de recherche
  Widget _buildSearchTab() {
    return Column(
      children: [
        // Barre de recherche
        Padding(
          padding: EdgeInsets.all(16.w),
          child: Column(
            children: [
              TextField(
                controller: _searchController,
                decoration: InputDecoration(
                  hintText: 'Rechercher des documents juridiques...',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear),
                          onPressed: () {
                            _searchController.clear();
                            setState(() {
                              _searchResults = [];
                              _suggestions = [];
                            });
                          },
                        )
                      : null,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12.r),
                  ),
                ),
                onChanged: (value) {
                  _loadSuggestions(value);
                },
                onSubmitted: (value) {
                  _performSearch();
                },
              ),
              SizedBox(height: 12.h),
              
              // Toggle recherche vectorielle
              Row(
                children: [
                  Switch(
                    value: _useVectorSearch,
                    onChanged: (value) {
                      setState(() {
                        _useVectorSearch = value;
                      });
                    },
                  ),
                  SizedBox(width: 8.w),
                  Text(
                    'Recherche sémantique (IA)',
                    style: TextStyle(fontSize: 14.sp),
                  ),
                  const Spacer(),
                  if (_selectedJurisdiction != null || _selectedCategory != null)
                    TextButton.icon(
                      onPressed: _resetFilters,
                      icon: const Icon(Icons.clear_all),
                      label: const Text('Réinitialiser'),
                    ),
                ],
              ),
            ],
          ),
        ),

        // Suggestions
        if (_suggestions.isNotEmpty && _searchController.text.isNotEmpty)
          Container(
            height: 50.h,
            padding: EdgeInsets.symmetric(horizontal: 16.w),
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: _suggestions.length,
              itemBuilder: (context, index) {
                return Padding(
                  padding: EdgeInsets.only(right: 8.w),
                  child: ActionChip(
                    label: Text(_suggestions[index]),
                    onPressed: () {
                      _searchController.text = _suggestions[index];
                      _performSearch();
                    },
                  ),
                );
              },
            ),
          ),

        // Résultats
        Expanded(
          child: _isLoading && _searchResults.isEmpty
              ? const Center(child: CircularProgressIndicator())
              : _searchResults.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.search_off, size: 64.sp, color: Colors.grey),
                          SizedBox(height: 16.h),
                          Text(
                            _searchController.text.isEmpty
                                ? 'Commencez votre recherche'
                                : 'Aucun résultat trouvé',
                            style: TextStyle(
                              fontSize: 16.sp,
                              color: Colors.grey,
                            ),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      controller: _scrollController,
                      padding: EdgeInsets.all(16.w),
                      itemCount: _searchResults.length + (_hasMore ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index >= _searchResults.length) {
                          return const Center(
                            child: Padding(
                              padding: EdgeInsets.all(16.0),
                              child: CircularProgressIndicator(),
                            ),
                          );
                        }

                        return SearchResultCard(
                          document: _searchResults[index],
                          query: _searchController.text,
                        );
                      },
                    ),
        ),
      ],
    );
  }

  /// Onglet des filtres
  Widget _buildFiltersTab() {
    return SearchFilterWidget(
      selectedJurisdiction: _selectedJurisdiction,
      selectedCategory: _selectedCategory,
      startDate: _startDate,
      endDate: _endDate,
      onJurisdictionChanged: (value) {
        setState(() {
          _selectedJurisdiction = value;
        });
      },
      onCategoryChanged: (value) {
        setState(() {
          _selectedCategory = value;
        });
      },
      onStartDateChanged: (value) {
        setState(() {
          _startDate = value;
        });
      },
      onEndDateChanged: (value) {
        setState(() {
          _endDate = value;
        });
      },
      onApplyFilters: () {
        _tabController.animateTo(0);
        _performSearch();
      },
      onResetFilters: _resetFilters,
    );
  }

  /// Onglet de l'historique
  Widget _buildHistoryTab() {
    return SearchHistoryWidget(
      searchHistory: _searchHistory,
      onHistoryItemTap: (query) {
        _searchController.text = query;
        _tabController.animateTo(0);
        _performSearch();
      },
      onClearHistory: () async {
        try {
          await _searchService.clearSearchHistory();
          _loadSearchHistory();
        } catch (e) {
          _showErrorSnackbar('Erreur lors de la suppression de l\'historique');
        }
      },
    );
  }
}
