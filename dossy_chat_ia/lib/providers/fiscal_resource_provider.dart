import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../core/constants/api_constants.dart';

class FiscalResource {
  final int id;
  final String title;
  final String? description;
  final int? categoryId;
  final String? categoryName;
  final String resourceType;
  final String country;
  final int? year;
  final String? version;
  final String? fileType;
  final String? filePath;
  final String? fileName;
  final int viewsCount;
  final int downloadsCount;
  final String? effectiveDate;
  final String? expiryDate;
  final dynamic content;

  FiscalResource({
    required this.id,
    required this.title,
    this.description,
    this.categoryId,
    this.categoryName,
    required this.resourceType,
    required this.country,
    this.year,
    this.version,
    this.fileType,
    this.filePath,
    this.fileName,
    required this.viewsCount,
    required this.downloadsCount,
    this.effectiveDate,
    this.expiryDate,
    this.content,
  });

  factory FiscalResource.fromJson(Map<String, dynamic> json) {
    try {
      // Safe parsing for id
      int id = 0;
      if (json['id'] != null) {
        if (json['id'] is int) {
          id = json['id'];
        } else if (json['id'] is String) {
          id = int.tryParse(json['id']) ?? 0;
        }
      }

      // Safe parsing for categoryId
      int? categoryId;
      final catData = json['category'];
      if (catData != null && catData is Map) {
        final catId = catData['id'];
        if (catId is int) {
          categoryId = catId;
        } else if (catId is String) {
          categoryId = int.tryParse(catId);
        }
      }

      // Safe parsing for year
      int? year;
      if (json['year'] != null) {
        if (json['year'] is int) {
          year = json['year'];
        } else {
          year = int.tryParse(json['year'].toString());
        }
      }

      // Safe parsing for viewsCount
      int viewsCount = 0;
      if (json['views_count'] != null) {
        if (json['views_count'] is int) {
          viewsCount = json['views_count'];
        } else {
          viewsCount = int.tryParse(json['views_count'].toString()) ?? 0;
        }
      }

      // Safe parsing for downloadsCount
      int downloadsCount = 0;
      if (json['downloads_count'] != null) {
        if (json['downloads_count'] is int) {
          downloadsCount = json['downloads_count'];
        } else {
          downloadsCount = int.tryParse(json['downloads_count'].toString()) ?? 0;
        }
      }

      return FiscalResource(
        id: id,
        title: json['title'] ?? 'Sans titre',
        description: json['description'],
        categoryId: categoryId,
        categoryName: json['category']?['name'],
        resourceType: json['resource_type'] ?? 'other',
        country: json['country'] ?? 'CM',
        year: year,
        version: json['version']?.toString(),
        fileType: json['file_type']?.toString(),
        filePath: json['file_path']?.toString(),
        fileName: json['file_name']?.toString(),
        viewsCount: viewsCount,
        downloadsCount: downloadsCount,
        effectiveDate: json['effective_date']?.toString(),
        expiryDate: json['expiry_date']?.toString(),
        content: json['content'],
      );
    } catch (e) {
      print('DEBUG: Critical error parsing FiscalResource: $e');
      print('DEBUG: JSON data: $json');
      // Return a default instance to avoid crashing
      return FiscalResource(
        id: 0,
        title: 'Erreur de chargement',
        resourceType: 'other',
        country: 'CM',
        viewsCount: 0,
        downloadsCount: 0,
      );
    }
  }

  String get resourceTypeDisplay {
    const types = {
      'cgi': 'Code Général des Impôts',
      'finance_law': 'Loi de Finances',
      'lpf': 'Livre de Procédures Fiscales',
      'administrative_doctrine': 'Doctrine Administrative',
      'tax_convention': 'Convention Fiscale',
      'social_security_code': 'Code Sécurité Sociale',
      'salary_tax_scale': 'Barème d\'Impôts',
      'labor_code': 'Code du Travail',
      'collective_agreement': 'Convention Collective',
      'other': 'Autre',
    };
    return types[resourceType] ?? resourceType;
  }
}

class SalaryGrid {
  final int id;
  final String country;
  final int year;
  final String category;
  final double minSalary;
  final double maxSalary;

  SalaryGrid({
    required this.id,
    required this.country,
    required this.year,
    required this.category,
    required this.minSalary,
    required this.maxSalary,
  });

  factory SalaryGrid.fromJson(Map<String, dynamic> json) {
    return SalaryGrid(
      id: json['id'],
      country: json['country'],
      year: json['year'],
      category: json['category'],
      minSalary: double.parse(json['min_salary'].toString()),
      maxSalary: double.parse(json['max_salary'].toString()),
    );
  }
}

class TaxParameter {
  final int id;
  final String country;
  final int year;
  final String taxType;
  final double rate;
  final String? description;

  TaxParameter({
    required this.id,
    required this.country,
    required this.year,
    required this.taxType,
    required this.rate,
    this.description,
  });

  factory TaxParameter.fromJson(Map<String, dynamic> json) {
    return TaxParameter(
      id: json['id'],
      country: json['country'],
      year: json['year'],
      taxType: json['tax_type'],
      rate: double.parse(json['rate'].toString()),
      description: json['description'],
    );
  }
}

class FiscalResourceProvider with ChangeNotifier {
  List<FiscalResource> _resources = [];
  List<SalaryGrid> _salaryGrids = [];
  List<TaxParameter> _taxParameters = [];
  bool _isLoading = false;
  String? _error;
  int _total = 0;
  int _currentPage = 1;
  int _totalPages = 1;

  List<FiscalResource> get resources => _resources;
  List<SalaryGrid> get salaryGrids => _salaryGrids;
  List<TaxParameter> get taxParameters => _taxParameters;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int get total => _total;
  int get currentPage => _currentPage;
  int get totalPages => _totalPages;

  Future<void> fetchResources({String? token, String? type, int? year, String? search, int? page, bool append = false}) async {
    if (!append) {
      _isLoading = true;
      _error = null;
    }
    notifyListeners();

    try {
      final params = <String, String>{};
      if (type != null) params['type'] = type;
      if (year != null) params['year'] = year.toString();
      if (search != null) params['search'] = search;
      if (page != null) params['page'] = page.toString();

      final uri = Uri.parse('${ApiConstants.baseUrl}/fiscal-resources')
          .replace(queryParameters: params.isEmpty ? null : params);

      final headers = {
        'Accept': 'application/json',
      };

      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }

      print('DEBUG: Fetching fiscal resources from: $uri');
      final response = await http.get(uri, headers: headers);
      print('DEBUG: Response status: ${response.statusCode}, body: ${response.body.substring(0, response.body.length > 200 ? 200 : response.body.length)}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          // Parse each resource with error handling
          final List<FiscalResource> newResources = [];
          final rawData = data['data'] as List;
          
          for (int i = 0; i < rawData.length; i++) {
            try {
              print('DEBUG: Parsing resource #$i: ${rawData[i]}');
              final resource = FiscalResource.fromJson(rawData[i]);
              newResources.add(resource);
            } catch (e, stackTrace) {
              print('DEBUG: Error parsing resource #$i: $e');
              print('DEBUG: Stack trace: $stackTrace');
              print('DEBUG: Problematic JSON: ${rawData[i]}');
              // Skip this resource and continue
            }
          }
          
          if (append) {
            _resources.addAll(newResources);
          } else {
            _resources = newResources;
          }
          
          // Safe parsing for pagination metadata
          try {
            _total = data['total'] is int ? data['total'] : int.tryParse(data['total']?.toString() ?? '0') ?? 0;
            _currentPage = data['page'] is int ? data['page'] : int.tryParse(data['page']?.toString() ?? '1') ?? 1;
            _totalPages = data['total_pages'] is int ? data['total_pages'] : int.tryParse(data['total_pages']?.toString() ?? '1') ?? 1;
          } catch (e) {
            print('DEBUG: Error parsing pagination metadata: $e');
            _total = 0;
            _currentPage = 1;
            _totalPages = 1;
          }
        } else {
          _error = data['message'] ?? 'Erreur lors du chargement';
        }
      } else {
        _error = 'Erreur réseau: ${response.statusCode}';
      }
    } catch (e) {
      print('DEBUG: Fiscal resources error: $e');
      _error = 'Erreur: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchSalaryGrids(String token) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/fiscal-resources/salary-grids'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _salaryGrids = (data['data'] as List)
              .map((json) => SalaryGrid.fromJson(json))
              .toList();
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('Salary grids error: $e');
    }
  }

  Future<void> fetchTaxParameters(String token) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/fiscal-resources/tax-parameters'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _taxParameters = (data['data'] as List)
              .map((json) => TaxParameter.fromJson(json))
              .toList();
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('Tax parameters error: $e');
    }
  }

  List<FiscalResource> getResourcesByType(String type) {
    return _resources.where((r) => r.resourceType == type).toList();
  }

  List<FiscalResource> getResourcesByYear(int year) {
    return _resources.where((r) => r.year == year).toList();
  }

  List<FiscalResource> getCurrentYearResources() {
    final currentYear = DateTime.now().year;
    return _resources.where((r) => r.year == currentYear).toList();
  }

  // Get a single resource by id (returns from loaded resources)
  Future<FiscalResource> getResourceById(int id) async {
    final resource = _resources.firstWhere((r) => r.id == id, orElse: () => throw Exception('Ressource introuvable'));
    return resource;
  }

  // Get download URL for a fiscal resource
  Future<String?> getResourceDownloadUrl(int id, String token) async {
    try {
      print('DEBUG: getResourceDownloadUrl called for resource $id');
      
      if (token.isEmpty) {
        print('DEBUG: No token provided');
        return null;
      }

      // Utiliser l'endpoint API de téléchargement
      print('DEBUG: Calling API endpoint /fiscal-resources/$id/download');
      
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/fiscal-resources/$id/download'),
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
      print('DEBUG: Download URL error: $e');
      print('DEBUG: Stack trace: $stackTrace');
      return null;
    }
  }
}

