import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';
import '../models/subscription_model.dart';
import '../services/storage_service.dart';

/// Repository for subscription operations
class SubscriptionRepository {
  final String baseUrl = AppConstants.apiBaseUrl;
  final http.Client client;
  final StorageService storageService;

  SubscriptionRepository({
    http.Client? client,
    required this.storageService,
  }) : client = client ?? http.Client();

  /// Get current subscription
  Future<Map<String, dynamic>> getCurrentSubscription({
    required String token,
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/subscription/current'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['subscription'] != null) {
          final subscription = SubscriptionModel.fromJson(data['subscription']);
          return {
            'success': true,
            'subscription': subscription,
            'quotas': data['quotas'],
          };
        } else {
          return {
            'success': true,
            'subscription': null,
            'message': 'Aucun abonnement actif',
          };
        }
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

  /// Get available subscription plans
  Future<Map<String, dynamic>> getPlans({
    required String token,
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/subscription/plans'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'plans': data['plans'],
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

  /// Subscribe to a plan
  Future<Map<String, dynamic>> subscribe({
    required String token,
    required String planId,
    required String billingCycle,
  }) async {
    try {
      final response = await client
          .post(
            Uri.parse('$baseUrl/subscription/subscribe'),
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $token',
              'Accept': 'application/json',
            },
            body: jsonEncode({
              'plan_id': planId,
              'billing_cycle': billingCycle,
            }),
          )
          .timeout(const Duration(seconds: 20));

      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'subscription': SubscriptionModel.fromJson(data['subscription']),
          'payment_url': data['payment_url'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de l\'abonnement',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Cancel subscription
  Future<Map<String, dynamic>> cancelSubscription({
    required String token,
    String? reason,
  }) async {
    try {
      final response = await client
          .post(
            Uri.parse('$baseUrl/subscription/cancel'),
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $token',
              'Accept': 'application/json',
            },
            body: jsonEncode({
              'reason': reason,
            }),
          )
          .timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        return {'success': true};
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de l\'annulation',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Upgrade subscription
  Future<Map<String, dynamic>> upgrade({
    required String token,
    required String newPlanId,
  }) async {
    try {
      final response = await client
          .post(
            Uri.parse('$baseUrl/subscription/upgrade'),
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $token',
              'Accept': 'application/json',
            },
            body: jsonEncode({
              'plan_id': newPlanId,
            }),
          )
          .timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'subscription': SubscriptionModel.fromJson(data['subscription']),
          'payment_url': data['payment_url'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la mise à niveau',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Get subscription history
  Future<Map<String, dynamic>> getHistory({
    required String token,
    int limit = 20,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/subscription/history').replace(
        queryParameters: {'limit': limit.toString()},
      );

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final subscriptions = (data['subscriptions'] as List)
            .map((sub) => SubscriptionModel.fromJson(sub))
            .toList();

        return {
          'success': true,
          'subscriptions': subscriptions,
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

  /// Check if user can perform action based on quotas
  Future<Map<String, dynamic>> checkQuota({
    required String token,
    required String action, // 'search', 'analysis', 'pdf'
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/subscription/check-quota/$action'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'can_perform': data['can_perform'],
          'remaining': data['remaining'],
          'limit': data['limit'],
        };
      } else {
        return {
          'success': false,
          'message': 'Erreur lors de la vérification',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Consume quota (called after action)
  Future<Map<String, dynamic>> consumeQuota({
    required String token,
    required String action,
  }) async {
    try {
      final response = await client
          .post(
            Uri.parse('$baseUrl/subscription/consume-quota'),
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $token',
              'Accept': 'application/json',
            },
            body: jsonEncode({'action': action}),
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        // Update local usage tracking
        if (action == 'search') {
          await storageService.incrementSearchCount();
        } else if (action == 'analysis') {
          await storageService.incrementAnalysisCount();
        }

        return {
          'success': true,
          'remaining': data['remaining'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Quota épuisé',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Get invoices
  Future<Map<String, dynamic>> getInvoices({
    required String token,
    int limit = 20,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/subscription/invoices').replace(
        queryParameters: {'limit': limit.toString()},
      );

      final response = await client.get(
        uri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'invoices': data['invoices'],
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

  /// Download invoice PDF
  Future<Map<String, dynamic>> downloadInvoice({
    required String token,
    required String invoiceId,
    required String savePath,
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/subscription/invoices/$invoiceId/pdf'),
        headers: {
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 30));

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

  /// Renew subscription
  Future<Map<String, dynamic>> renew({
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/subscription/renew'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'payment_url': data['payment_url'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors du renouvellement',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur: ${e.toString()}',
      };
    }
  }

  /// Dispose resources
  void dispose() {
    client.close();
  }
}

/// Import File for downloadInvoice
