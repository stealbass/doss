import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';
import '../models/document_model.dart';
import '../services/storage_service.dart';

/// Repository for document operations
class DocumentRepository {
  final String baseUrl = AppConstants.apiBaseUrl;
  final http.Client client;
  final StorageService storageService;

  DocumentRepository({
    http.Client? client,
    required this.storageService,
  }) : client = client ?? http.Client();

  /// Upload document
  Future<Map<String, dynamic>> uploadDocument({
    required String token,
    required String filePath,
    String? title,
    String? category,
  }) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/documents/upload'),
      );
      
      request.headers['Authorization'] = 'Bearer $token';
      request.files.add(await http.MultipartFile.fromPath('document', filePath));
      
      if (title != null) request.fields['title'] = title;
      if (category != null) request.fields['category'] = category;

      final streamedResponse = await request.send().timeout(const Duration(seconds: 60));
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'document': DocumentModel.fromJson(data['document']),
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors du téléchargement',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Get user documents list
  Future<Map<String, dynamic>> getDocuments({
    required String token,
    String? category,
    int page = 1,
    int limit = 20,
  }) async {
    try {
      final Map<String, String> params = {
        'page': page.toString(),
        'limit': limit.toString(),
      };
      if (category != null) params['category'] = category;

      final uri = Uri.parse('$baseUrl/documents').replace(queryParameters: params);

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final documents = (data['documents'] as List)
            .map((doc) => DocumentModel.fromJson(doc))
            .toList();
        
        return {
          'success': true,
          'documents': documents,
          'total': data['total'],
          'page': data['page'],
          'total_pages': data['total_pages'],
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

  /// Get document details
  Future<Map<String, dynamic>> getDocument({
    required String token,
    required String documentId,
  }) async {
    try {
      // Check cache first
      final cached = storageService.getCachedDocument(documentId);
      
      final response = await client.get(
        Uri.parse('$baseUrl/documents/$documentId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final document = DocumentModel.fromJson(data['document']);
        
        // Cache document
        await storageService.cacheDocument(documentId, data['document']);
        
        return {
          'success': true,
          'document': document,
        };
      } else {
        // Return cached if available
        if (cached != null) {
          return {
            'success': true,
            'document': DocumentModel.fromJson(cached),
            'cached': true,
          };
        }
        
        return {
          'success': false,
          'message': 'Document introuvable',
        };
      }
    } catch (e) {
      // Try cache on error
      final cached = storageService.getCachedDocument(documentId);
      if (cached != null) {
        return {
          'success': true,
          'document': DocumentModel.fromJson(cached),
          'cached': true,
        };
      }
      
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Delete document
  Future<Map<String, dynamic>> deleteDocument({
    required String token,
    required String documentId,
  }) async {
    try {
      final response = await client.delete(
        Uri.parse('$baseUrl/documents/$documentId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        return {'success': true};
      } else {
        return {
          'success': false,
          'message': 'Erreur lors de la suppression',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Download document
  Future<Map<String, dynamic>> downloadDocument({
    required String token,
    required String documentId,
    required String savePath,
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/documents/$documentId/download'),
        headers: {
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 60));

      if (response.statusCode == 200) {
        final file = File(savePath);
        await file.writeAsBytes(response.bodyBytes);
        
        return {
          'success': true,
          'path': savePath,
        };
      } else {
        return {
          'success': false,
          'message': 'Erreur lors du téléchargement',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Share document
  Future<Map<String, dynamic>> shareDocument({
    required String token,
    required String documentId,
    required String email,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/documents/$documentId/share'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({'email': email}),
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        return {'success': true};
      } else {
        return {
          'success': false,
          'message': 'Erreur lors du partage',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Update document metadata
  Future<Map<String, dynamic>> updateDocument({
    required String token,
    required String documentId,
    String? title,
    String? category,
    List<String>? tags,
  }) async {
    try {
      final Map<String, dynamic> updates = {};
      if (title != null) updates['title'] = title;
      if (category != null) updates['category'] = category;
      if (tags != null) updates['tags'] = tags;

      final response = await client.put(
        Uri.parse('$baseUrl/documents/$documentId'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode(updates),
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'document': DocumentModel.fromJson(data['document']),
        };
      } else {
        return {
          'success': false,
          'message': 'Erreur lors de la mise à jour',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Search documents
  Future<Map<String, dynamic>> searchDocuments({
    required String token,
    required String query,
    String? category,
  }) async {
    try {
      final Map<String, String> params = {'query': query};
      if (category != null) params['category'] = category;

      final uri = Uri.parse('$baseUrl/documents/search').replace(queryParameters: params);

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final documents = (data['documents'] as List)
            .map((doc) => DocumentModel.fromJson(doc))
            .toList();
        
        return {
          'success': true,
          'documents': documents,
        };
      } else {
        return {
          'success': false,
          'message': 'Erreur lors de la recherche',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Get document categories
  Future<Map<String, dynamic>> getCategories({
    required String token,
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/documents/categories'),
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

  /// Save document for offline access
  Future<void> saveForOffline(DocumentModel document) async {
    await storageService.saveOfflineDocument(
      document.id,
      document.toJson(),
    );
  }

  /// Get offline documents
  List<DocumentModel> getOfflineDocuments() {
    final docs = storageService.getAllOfflineDocuments();
    return docs.map((doc) => DocumentModel.fromJson(doc)).toList();
  }

  /// Dispose resources
  void dispose() {
    client.close();
  }
}
