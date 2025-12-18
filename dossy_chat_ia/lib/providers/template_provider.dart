import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../core/constants/api_constants.dart';

class DocumentTemplate {
  final int id;
  final String title;
  final String? description;
  final String categoryName;
  final String country;
  final String fileType;
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
    required this.requiredPlan,
    required this.isMobileVisible,
    required this.downloadsCount,
  });

  factory DocumentTemplate.fromJson(Map<String, dynamic> json) {
    return DocumentTemplate(
      id: json['id'],
      title: json['title'],
      description: json['description'],
      categoryName: json['category_name'] ?? 'N/A',
      country: json['country'],
      fileType: json['file_type'],
      requiredPlan: json['required_plan'],
      isMobileVisible: json['is_mobile_visible'] ?? false,
      downloadsCount: json['downloads_count'] ?? 0,
    );
  }
}

class TemplateProvider with ChangeNotifier {
  List<DocumentTemplate> _templates = [];
  bool _isLoading = false;
  String? _error;

  List<DocumentTemplate> get templates => _templates;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchTemplates(String token) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/templates'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _templates = (data['data'] as List)
              .map((json) => DocumentTemplate.fromJson(json))
              .toList();
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

  Future<String?> downloadTemplate(int templateId, String token) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/templates/$templateId/download'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          return data['download_url'];
        }
      }
      return null;
    } catch (e) {
      print('Download error: $e');
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
