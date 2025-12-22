import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';
import '../models/search_history_model.dart';

/// Service for legal search functionality
class SearchService {
  final String baseUrl = AppConstants.apiBaseUrl;
  final http.Client client;

  SearchService({http.Client? client}) : client = client ?? http.Client();

  /// Search legal documents with RAG (Retrieval Augmented Generation)
  /// 
  /// [query] - Search query
  /// [jurisdiction] - Jurisdiction filter (CI, SN, etc.)
  /// [category] - Legal category filter
  /// [dateFrom] - Optional start date filter
  /// [dateTo] - Optional end date filter
  /// [page] - Page number for pagination
  /// [limit] - Results per page
  /// [token] - User authentication token
  Future<Map<String, dynamic>> searchDocuments({
    required String query,
    required String jurisdiction,
    String? category,
    DateTime? dateFrom,
    DateTime? dateTo,
    int page = 1,
    int limit = 10,
    required String token,
  }) async {
    try {
      final Map<String, dynamic> params = {
        'query': query,
        'jurisdiction': jurisdiction,
        'page': page.toString(),
        'limit': limit.toString(),
      };

      if (category != null) params['category'] = category;
      if (dateFrom != null) params['date_from'] = dateFrom.toIso8601String();
      if (dateTo != null) params['date_to'] = dateTo.toIso8601String();

      final uri = Uri.parse('$baseUrl/search').replace(queryParameters: params);

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'results': data['results'],
          'total': data['total'],
          'page': data['page'],
          'total_pages': data['total_pages'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la recherche',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Search with vector similarity (Pinecone)
  /// 
  /// [query] - Search query
  /// [jurisdiction] - Jurisdiction filter
  /// [topK] - Number of similar results to return
  /// [token] - User authentication token
  Future<Map<String, dynamic>> vectorSearch({
    required String query,
    required String jurisdiction,
    int topK = 5,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/search/vector'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'query': query,
          'jurisdiction': jurisdiction,
          'top_k': topK,
        }),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'results': data['results'],
          'scores': data['scores'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la recherche vectorielle',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Get search history for current user
  /// 
  /// [limit] - Number of history items to return
  /// [token] - User authentication token
  Future<Map<String, dynamic>> getSearchHistory({
    int limit = 20,
    required String token,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/search/history').replace(
        queryParameters: {'limit': limit.toString()},
      );

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final List<SearchHistoryModel> history = (data['history'] as List)
            .map((item) => SearchHistoryModel.fromJson(item))
            .toList();
        
        return {
          'success': true,
          'history': history,
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la récupération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Clear search history
  /// 
  /// [token] - User authentication token
  Future<Map<String, dynamic>> clearSearchHistory({
    required String token,
  }) async {
    try {
      final response = await client.delete(
        Uri.parse('$baseUrl/search/history'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        return {'success': true};
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la suppression',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Get suggested queries based on partial input
  /// 
  /// [partial] - Partial query text
  /// [jurisdiction] - Jurisdiction filter
  /// [token] - User authentication token
  Future<Map<String, dynamic>> getSuggestions({
    required String partial,
    required String jurisdiction,
    required String token,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/search/suggestions').replace(
        queryParameters: {
          'q': partial,
          'jurisdiction': jurisdiction,
        },
      );

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 5));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'suggestions': data['suggestions'],
        };
      } else {
        return {
          'success': false,
          'message': 'Erreur lors des suggestions',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Get trending search queries
  /// 
  /// [jurisdiction] - Jurisdiction filter
  /// [limit] - Number of trending queries to return
  /// [token] - User authentication token
  Future<Map<String, dynamic>> getTrendingSearches({
    required String jurisdiction,
    int limit = 10,
    required String token,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/search/trending').replace(
        queryParameters: {
          'jurisdiction': jurisdiction,
          'limit': limit.toString(),
        },
      );

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'trending': data['trending'],
        };
      } else {
        return {
          'success': false,
          'message': 'Erreur lors de la récupération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Get legal categories for filtering
  /// 
  /// [jurisdiction] - Jurisdiction filter
  /// [token] - User authentication token
  Future<Map<String, dynamic>> getCategories({
    required String jurisdiction,
    required String token,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/search/categories').replace(
        queryParameters: {'jurisdiction': jurisdiction},
      );

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'categories': data['categories'],
        };
      } else {
        return {
          'success': false,
          'message': 'Erreur lors de la récupération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  // =====================================================
  // Alias Methods (pour compatibilité)
  // =====================================================
  
  /// Full text search (alias de searchDocuments)
  Future<List<dynamic>> fullTextSearch({
    required String query,
    required String token,
    String? jurisdiction,
  }) async {
    final result = await searchDocuments(
      query: query,
      jurisdiction: jurisdiction ?? 'CI',
      token: token,
    );
    
    if (result['success'] == true) {
      return result['results'] as List<dynamic>;
    }
    return [];
  }
  
  /// Save search history (appelé automatiquement par l'API)
  Future<void> saveSearchHistory(String query, {required String token}) async {
    // L'historique est automatiquement sauvegardé côté serveur lors d'une recherche
    // Cette méthode est un no-op pour compatibilité
    return;
  }
  
  /// Get search suggestions (alias de getSuggestions)
  Future<List<String>> getSearchSuggestions(String query, {String? token}) async {
    if (token == null) return [];
    
    final result = await getSuggestions(
      partial: query,
      jurisdiction: 'CI',
      token: token,
    );
    
    if (result['success'] == true) {
      return (result['suggestions'] as List).cast<String>();
    }
    return [];
  }

  /// Dispose resources
  void dispose() {
    client.close();
  }
}
