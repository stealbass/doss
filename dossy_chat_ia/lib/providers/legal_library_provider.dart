import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../data/models/document_model.dart';
import '../data/services/search_service.dart';
import '../core/constants/app_constants.dart';

class LegalLibraryProvider with ChangeNotifier {
  final SearchService _service;
  LegalLibraryProvider({SearchService? service}) : _service = service ?? SearchService();

  List<DocumentModel> _results = [];
  bool _isLoading = false;
  String? _error;
  int _total = 0;
  int _currentPage = 1;
  int _totalPages = 1;

  List<DocumentModel> get results => _results;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int get total => _total;
  int get currentPage => _currentPage;
  int get totalPages => _totalPages;

  Future<void> search({
    required String query,
    required String jurisdiction,
    required String token,
    String? category,
    int? categoryId,
    int? page,
    bool append = false,
  }) async {
    // Permettre recherche vide pour charger tous les documents
    _isLoading = true;
    _error = null;
    notifyListeners();

    final response = await _service.searchDocuments(
      query: query,
      jurisdiction: jurisdiction,
      category: category,
      categoryId: categoryId,
      token: token,
      page: page ?? 1,
    );

    if (response['success'] == true) {
      final list = response['results'] as List? ?? [];
      final newResults = list.map((e) => DocumentModel.fromJson(e)).toList();
      
      if (append) {
        _results.addAll(newResults);
      } else {
        _results = newResults;
      }
      
      _total = response['total'] ?? 0;
      _currentPage = int.tryParse(response['page'].toString()) ?? 1;
      _totalPages = int.tryParse(response['total_pages'].toString()) ?? 1;
    } else {
      _error = response['message'] ?? 'Erreur lors de la recherche';
      if (!append) {
        _results = [];
      }
    }

    _isLoading = false;
    notifyListeners();
  }

  /// Obtient l'URL de téléchargement d'un document juridique
  Future<String?> getDocumentDownloadUrl(int documentId, String token) async {
    try {
      print('DEBUG: getDocumentDownloadUrl called for document $documentId');
      
      if (token.isEmpty) {
        print('DEBUG: No token provided');
        return null;
      }

      // Utiliser l'endpoint API de téléchargement
      final endpoint = '${AppConstants.apiBaseUrl}/mobile/documents/legal/$documentId/download';
      print('DEBUG: Calling API endpoint: $endpoint');
      print('DEBUG: Token provided: ${token.substring(0, 20)}...');
      
      final response = await http.get(
        Uri.parse(endpoint),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      print('DEBUG: API response status: ${response.statusCode}');
      print('DEBUG: API response headers: ${response.headers}');
      print('DEBUG: API response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        print('DEBUG: API response decoded: $data');
        
        if (data['success'] == true) {
          final downloadUrl = data['data']?['download_url'];
          print('DEBUG: Got download URL from API: $downloadUrl');
          return downloadUrl;
        } else {
          print('DEBUG: API returned success=false: ${data['message']}');
        }
      } else if (response.statusCode == 403) {
        print('DEBUG: 403 Forbidden - Quota exceeded or permission denied');
        final data = json.decode(response.body);
        print('DEBUG: Error message: ${data['message']}');
        return null;
      } else if (response.statusCode == 404) {
        print('DEBUG: 404 Not Found - Document or file not found');
        final data = json.decode(response.body);
        print('DEBUG: Error message: ${data['message']}');
        return null;
      } else {
        print('DEBUG: API returned error status: ${response.statusCode}');
        print('DEBUG: Response body: ${response.body}');
      }
      
      return null;
    } catch (e, stackTrace) {
      print('DEBUG: Legal document download URL error: $e');
      print('DEBUG: Stack trace: $stackTrace');
      return null;
    }
  }
}
