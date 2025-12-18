import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../core/constants/api_constants.dart';

class Calculator {
  final int id;
  final String name;
  final String description;
  final String calculatorType;
  final String country;
  final String requiredPlan;

  Calculator({
    required this.id,
    required this.name,
    required this.description,
    required this.calculatorType,
    required this.country,
    required this.requiredPlan,
  });

  factory Calculator.fromJson(Map<String, dynamic> json) {
    return Calculator(
      id: json['id'],
      name: json['name'],
      description: json['description'] ?? '',
      calculatorType: json['calculator_type'],
      country: json['country'],
      requiredPlan: json['required_plan'],
    );
  }
}

class CalculationResult {
  final Map<String, dynamic> result;
  final String breakdown;

  CalculationResult({required this.result, required this.breakdown});

  factory CalculationResult.fromJson(Map<String, dynamic> json) {
    return CalculationResult(
      result: json['result'],
      breakdown: json['breakdown'] ?? '',
    );
  }
}

class CalculatorProvider with ChangeNotifier {
  List<Calculator> _calculators = [];
  Map<String, CalculationResult?> _results = {};
  bool _isLoading = false;
  String? _error;

  List<Calculator> get calculators => _calculators;
  bool get isLoading => _isLoading;
  String? get error => _error;

  CalculationResult? getResult(String calculatorType) {
    return _results[calculatorType];
  }

  Future<void> fetchCalculators(String token) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/calculators'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _calculators = (data['data'] as List)
              .map((json) => Calculator.fromJson(json))
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

  Future<bool> calculate(
      int calculatorId, Map<String, dynamic> inputs, String token) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}/mobile/calculators/$calculatorId/calculate'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({'inputs': inputs}),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          final result = CalculationResult.fromJson(data['data']);
          _results[calculatorId.toString()] = result;
          _isLoading = false;
          notifyListeners();
          return true;
        } else {
          _error = data['message'] ?? 'Erreur de calcul';
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

    return false;
  }

  void clearResults() {
    _results.clear();
    notifyListeners();
  }
}
