import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../core/constants/api_constants.dart';

class SubAccount {
  final int id;
  final String name;
  final String email;
  final String role;
  final bool isActive;
  final DateTime createdAt;

  SubAccount({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.isActive,
    required this.createdAt,
  });

  factory SubAccount.fromJson(Map<String, dynamic> json) {
    return SubAccount(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      role: json['role'],
      isActive: json['is_active'] ?? true,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  String get roleDisplayName {
    switch (role) {
      case 'dg':
        return 'Directeur Général';
      case 'hr':
        return 'Responsable RH';
      case 'accountant':
        return 'Comptable';
      case 'legal':
        return 'Juriste';
      default:
        return 'Autre';
    }
  }
}

class EnterpriseDashboard {
  final int totalSubAccounts;
  final int activeSubAccounts;
  final int inactiveSubAccounts;
  final int remainingSlots;

  EnterpriseDashboard({
    required this.totalSubAccounts,
    required this.activeSubAccounts,
    required this.inactiveSubAccounts,
    required this.remainingSlots,
  });

  factory EnterpriseDashboard.fromJson(Map<String, dynamic> json) {
    return EnterpriseDashboard(
      totalSubAccounts: json['total_sub_accounts'] ?? 0,
      activeSubAccounts: json['active_sub_accounts'] ?? 0,
      inactiveSubAccounts: json['inactive_sub_accounts'] ?? 0,
      remainingSlots: json['remaining_slots'] ?? 0,
    );
  }
}

class EnterpriseProvider with ChangeNotifier {
  List<SubAccount> _subAccounts = [];
  EnterpriseDashboard? _dashboard;
  bool _isLoading = false;
  String? _error;

  List<SubAccount> get subAccounts => _subAccounts;
  EnterpriseDashboard? get dashboard => _dashboard;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchDashboard(String token) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/enterprise/dashboard'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _dashboard = EnterpriseDashboard.fromJson(data['data']);
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

  Future<void> fetchSubAccounts(String token) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/enterprise/sub-accounts'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _subAccounts = (data['data']['sub_accounts'] as List)
              .map((json) => SubAccount.fromJson(json))
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

  Future<bool> createSubAccount(
      String name, String email, String role, String token,
      {String? department}) async {
    try {
      final Map<String, dynamic> payload = {
        'name': name,
        'email': email,
        'role': role,
      };

      if (department != null) {
        payload['department'] = department;
      }

      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}/mobile/enterprise/sub-accounts'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode(payload),
      );

      if (response.statusCode == 201) {
        await fetchSubAccounts(token);
        await fetchDashboard(token);
        return true;
      }
      return false;
    } catch (e) {
      debugPrint('Create sub-account error: $e');
      return false;
    }
  }

  Future<bool> toggleSubAccountStatus(int id, String token) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}/mobile/enterprise/sub-accounts/$id/toggle'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        await fetchSubAccounts(token);
        return true;
      }
      return false;
    } catch (e) {
      debugPrint('Toggle status error: $e');
      return false;
    }
  }

  Future<bool> deleteSubAccount(int id, String token) async {
    try {
      final response = await http.delete(
        Uri.parse('${ApiConstants.baseUrl}/mobile/enterprise/sub-accounts/$id'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        await fetchSubAccounts(token);
        await fetchDashboard(token);
        return true;
      }
      return false;
    } catch (e) {
      debugPrint('Delete sub-account error: $e');
      return false;
    }
  }
}
