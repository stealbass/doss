import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../core/constants/api_constants.dart';

class LegalAlert {
  final int id;
  final String title;
  final String content;
  final String alertType;
  final String priority;
  final List<String> targetCountries;
  final List<String> targetPlans;
  final bool isRead;
  final DateTime createdAt;

  LegalAlert({
    required this.id,
    required this.title,
    required this.content,
    required this.alertType,
    required this.priority,
    required this.targetCountries,
    required this.targetPlans,
    required this.isRead,
    required this.createdAt,
  });

  factory LegalAlert.fromJson(Map<String, dynamic> json) {
    return LegalAlert(
      id: json['id'],
      title: json['title'],
      content: json['content'],
      alertType: json['alert_type'],
      priority: json['priority'],
      targetCountries: List<String>.from(json['target_countries'] ?? []),
      targetPlans: List<String>.from(json['target_plans'] ?? []),
      isRead: json['is_read'] ?? false,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  String get alertTypeDisplay {
    const types = {
      'legal_modification': 'Modification Légale',
      'new_law': 'Nouvelle Loi',
      'jurisprudence': 'Jurisprudence',
      'fiscal': 'Fiscal',
      'social': 'Social',
      'other': 'Autre',
    };
    return types[alertType] ?? alertType;
  }

  String get priorityDisplay {
    const priorities = {
      'urgent': 'Urgent',
      'high': 'Élevée',
      'medium': 'Moyenne',
      'low': 'Faible',
    };
    return priorities[priority] ?? priority;
  }

  String get priorityEmoji {
    const emojis = {
      'urgent': '🚨',
      'high': '🔴',
      'medium': '🟡',
      'low': '🟢',
    };
    return emojis[priority] ?? '📌';
  }
}

class LegalAlertProvider with ChangeNotifier {
  List<LegalAlert> _alerts = [];
  bool _isLoading = false;
  String? _error;

  List<LegalAlert> get alerts => _alerts;
  List<LegalAlert> get unreadAlerts => _alerts.where((a) => !a.isRead).toList();
  List<LegalAlert> get urgentAlerts => 
      _alerts.where((a) => a.priority == 'urgent' && !a.isRead).toList();
  int get unreadCount => unreadAlerts.length;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchAlerts(String token) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/mobile/legal-alerts'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _alerts = (data['data'] as List)
              .map((json) => LegalAlert.fromJson(json))
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

  Future<bool> markAsRead(int alertId, String token) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}/mobile/legal-alerts/$alertId/mark-read'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        // Update local state
        final index = _alerts.indexWhere((a) => a.id == alertId);
        if (index != -1) {
          // Create a new alert with isRead = true
          final alert = _alerts[index];
          _alerts[index] = LegalAlert(
            id: alert.id,
            title: alert.title,
            content: alert.content,
            alertType: alert.alertType,
            priority: alert.priority,
            targetCountries: alert.targetCountries,
            targetPlans: alert.targetPlans,
            isRead: true,
            createdAt: alert.createdAt,
          );
          notifyListeners();
        }
        return true;
      }
      return false;
    } catch (e) {
      print('Mark as read error: $e');
      return false;
    }
  }

  List<LegalAlert> getAlertsByType(String type) {
    return _alerts.where((a) => a.alertType == type).toList();
  }

  List<LegalAlert> getAlertsByPriority(String priority) {
    return _alerts.where((a) => a.priority == priority).toList();
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
