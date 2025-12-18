import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../core/constants/api_constants.dart';

class FiscalResource {
  final int id;
  final String title;
  final String? description;
  final String resourceType;
  final String country;
  final int year;
  final String version;
  final String fileType;
  final int downloadsCount;

  FiscalResource({
    required this.id,
    required this.title,
    this.description,
    required this.resourceType,
    required this.country,
    required this.year,
    required this.version,
    required this.fileType,
    required this.downloadsCount,
  });

  factory FiscalResource.fromJson(Map<String, dynamic> json) {
    return FiscalResource(
      id: json['id'],
      title: json['title'],
      description: json['description'],
      resourceType: json['resource_type'],
      country: json['country'],
      year: json['year'],
      version: json['version'],
      fileType: json['file_type'],
      downloadsCount: json['downloads_count'] ?? 0,
    );
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

  List<FiscalResource> get resources => _resources;
  List<SalaryGrid> get salaryGrids => _salaryGrids;
  List<TaxParameter> get taxParameters => _taxParameters;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchResources(String token) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/fiscal-resources'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _resources = (data['data'] as List)
              .map((json) => FiscalResource.fromJson(json))
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

  Future<void> fetchSalaryGrids(String token) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/fiscal-resources/salary-grids'),
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
      print('Salary grids error: $e');
    }
  }

  Future<void> fetchTaxParameters(String token) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/fiscal-resources/tax-parameters'),
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
      print('Tax parameters error: $e');
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
}
