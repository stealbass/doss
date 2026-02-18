import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../core/constants/api_constants.dart';
import '../models/template_model.dart';

class DocumentTemplate {
  final int id;
  final String title;
  final String? description;
  final String categoryName;
  final String country;
  final String fileType;
  final String? fileUrl;
  final String? filePath;
  final String requiredPlan;
  final bool isMobileVisible;
  final int downloadsCount;

  DocumentTemplate({
    required this.id,
    required this.title,
    this.description,
    required this.categoryName,
    required this.country,
    required this.fileType,
    this.fileUrl,
    this.filePath,
    required this.requiredPlan,
    required this.isMobileVisible,
    required this.downloadsCount,
  });

  factory DocumentTemplate.fromJson(Map<String, dynamic> json) {
    return DocumentTemplate(
      id: json['id'] ?? 0,
      title: json['title'] ?? json['name'] ?? 'Sans titre',
      description: json['description'],
      categoryName: json['category_name'] ?? json['category']?['name'] ?? 'N/A',
      country: json['country'] ?? 'CM',
      fileType: json['file_type'] ?? 'pdf',
      fileUrl: json['file_url'] ?? json['url'],
      filePath: json['file_path'] ?? json['path'],
      requiredPlan: json['required_plan'] ?? 'Gratuit',
      isMobileVisible: json['is_mobile_visible'] ?? true,
      downloadsCount: json['downloads_count'] ?? 0,
    );
  }
}

class TemplateProvider with ChangeNotifier {
  List<DocumentTemplate> _templates = [];
  bool _isLoading = false;
  String? _error;
  int _total = 0;
  int _currentPage = 1;
  int _totalPages = 1;

  List<DocumentTemplate> get templates => _templates;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int get total => _total;
  int get currentPage => _currentPage;
  int get totalPages => _totalPages;

  Future<void> fetchTemplates(String token, {int? page, int? categoryId, String? search, bool append = false}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final params = <String, String>{};
      if (page != null) params['page'] = page.toString();
      if (categoryId != null) params['category_id'] = categoryId.toString();
      if (search != null) params['search'] = search;

      final uri = Uri.parse('${ApiConstants.baseUrl}/templates').replace(
        queryParameters: params.isEmpty ? null : params,
      );

      final response = await http.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          final newTemplates = (data['data'] as List)
              .map((json) => DocumentTemplate.fromJson(json))
              .toList();
          if (append) {
            _templates.addAll(newTemplates);
            final seen = <int>{};
            _templates = _templates.where((t) => seen.add(t.id)).toList();
          } else {
            _templates = newTemplates;
          }
          _total = data['total'] ?? 0;
          _currentPage = data['page'] ?? 1;
          _totalPages = data['total_pages'] ?? 1;
        } else {
          _error = data['message'] ?? 'Erreur lors du chargement';
        }
      } else {
        _error = 'Erreur réseau: ${response.statusCode}';
      }
    } catch (e) {
      _error = 'Erreur: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<String?> downloadTemplate(int templateId, [String? token]) async {
    try {
      print('DEBUG: downloadTemplate called for template $templateId');
      
      if (token == null || token.isEmpty) {
        print('DEBUG: No token provided');
        return null;
      }
      
      // Utiliser l'endpoint API de téléchargement
      print('DEBUG: Calling API endpoint /templates/$templateId/download');
      
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/templates/$templateId/download'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      print('DEBUG: API response status: ${response.statusCode}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        print('DEBUG: API response data: $data');
        
        if (data['success']) {
          final downloadUrl = data['data']['download_url'];
          print('DEBUG: Got download URL from API: $downloadUrl');
          return downloadUrl;
        } else {
          print('DEBUG: API returned success=false: ${data['message']}');
        }
      } else {
        print('DEBUG: API returned error status: ${response.statusCode}');
        print('DEBUG: Response body: ${response.body}');
      }
      
      return null;
    } catch (e, stackTrace) {
      print('DEBUG: Download template error: $e');
      print('DEBUG: Stack trace: $stackTrace');
      return null;
    }
  }

  // Compatibility helper to get a template by id
  Future<Template?> getTemplateById(int id) async {
    try {
      final doc = _templates.firstWhere((t) => t.id == id, orElse: () => throw Exception('Template introuvable'));
      return Template(
        id: doc.id,
        title: doc.title,
        type: doc.fileType,
        category: doc.categoryName,
        requiredPlan: doc.requiredPlan,
        viewsCount: doc.downloadsCount, // best effort mapping
        downloadsCount: doc.downloadsCount,
        fileType: doc.fileType,
        description: doc.description ?? '',
        usageInstructions: doc.description ?? '',
        tags: [],
        countries: [],
        formSelectIcon: '',
      );
    } catch (e) {
      return null;
    }
  }

  List<DocumentTemplate> getTemplatesByCategory(String category) {
    return _templates.where((t) => t.categoryName == category).toList();
  }

  List<DocumentTemplate> searchTemplates(String query) {
    query = query.toLowerCase();
    return _templates
        .where((t) =>
            t.title.toLowerCase().contains(query) ||
            (t.description?.toLowerCase().contains(query) ?? false))
        .toList();
  }
}
