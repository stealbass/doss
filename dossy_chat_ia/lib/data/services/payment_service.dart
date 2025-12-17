import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';

/// Service for Flutterwave payment integration
class PaymentService {
  final String baseUrl = AppConstants.apiBaseUrl;
  final http.Client client;

  PaymentService({http.Client? client}) : client = client ?? http.Client();

  /// Initialize payment for subscription
  /// 
  /// [planId] - Subscription plan ID
  /// [billingCycle] - 'monthly' or 'yearly'
  /// [amount] - Payment amount
  /// [currency] - Currency code (XOF for FCFA)
  /// [email] - User email
  /// [phone] - User phone number
  /// [name] - User full name
  /// [token] - User authentication token
  Future<Map<String, dynamic>> initiatePayment({
    required String planId,
    required String billingCycle,
    required double amount,
    String currency = 'XOF',
    required String email,
    required String phone,
    required String name,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/payment/initiate'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'plan_id': planId,
          'billing_cycle': billingCycle,
          'amount': amount,
          'currency': currency,
          'email': email,
          'phone': phone,
          'name': name,
        }),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'payment_link': data['payment_link'],
          'transaction_id': data['transaction_id'],
          'reference': data['reference'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de l\'initialisation',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Verify payment status
  /// 
  /// [transactionId] - Transaction ID from Flutterwave
  /// [token] - User authentication token
  Future<Map<String, dynamic>> verifyPayment({
    required String transactionId,
    required String token,
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/payment/verify/$transactionId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'status': data['status'], // success, pending, failed
          'amount': data['amount'],
          'currency': data['currency'],
          'transaction_id': data['transaction_id'],
          'payment_type': data['payment_type'],
          'created_at': data['created_at'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la vérification',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Get payment history
  /// 
  /// [limit] - Number of payments to retrieve
  /// [page] - Page number
  /// [token] - User authentication token
  Future<Map<String, dynamic>> getPaymentHistory({
    int limit = 20,
    int page = 1,
    required String token,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/payment/history').replace(
        queryParameters: {
          'limit': limit.toString(),
          'page': page.toString(),
        },
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
          'payments': data['payments'],
          'total': data['total'],
          'page': data['page'],
          'total_pages': data['total_pages'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la récupération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Cancel subscription
  /// 
  /// [subscriptionId] - Subscription ID to cancel
  /// [reason] - Cancellation reason (optional)
  /// [token] - User authentication token
  Future<Map<String, dynamic>> cancelSubscription({
    required String subscriptionId,
    String? reason,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/payment/cancel-subscription'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'subscription_id': subscriptionId,
          'reason': reason,
        }),
      ).timeout(const Duration(seconds: 15));

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
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Get available subscription plans
  /// 
  /// [token] - User authentication token
  Future<Map<String, dynamic>> getPlans({
    required String token,
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/payment/plans'),
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
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la récupération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Apply referral code during payment
  /// 
  /// [referralCode] - Referral code to apply
  /// [planId] - Plan ID
  /// [token] - User authentication token
  Future<Map<String, dynamic>> applyReferralCode({
    required String referralCode,
    required String planId,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/payment/apply-referral'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'referral_code': referralCode,
          'plan_id': planId,
        }),
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'discount': data['discount'],
          'final_amount': data['final_amount'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Code de parrainage invalide',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Get current subscription status
  /// 
  /// [token] - User authentication token
  Future<Map<String, dynamic>> getSubscriptionStatus({
    required String token,
  }) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/payment/subscription-status'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'subscription': data['subscription'],
          'status': data['status'],
          'plan': data['plan'],
          'expires_at': data['expires_at'],
          'quotas': data['quotas'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la récupération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Dispose resources
  void dispose() {
    client.close();
  }
}
