import 'dart:convert';
import 'dart:async';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';

/// Service for Flutterwave payment integration
class PaymentService {
  // Use mobile base URL because payment endpoints are under /api/mobile
  final String baseUrl = AppConstants.baseUrl;
  final http.Client client;

  PaymentService({http.Client? client}) : client = client ?? http.Client();

  List<String> get _endpoints {
    final endpoints = <String>{baseUrl, ...AppConstants.fallbackBaseUrls};
    return endpoints.toList();
  }

  Future<http.Response> _postWithFallback({
    required String path,
    required Map<String, String> headers,
    required Object body,
    Duration timeout = const Duration(seconds: 30),
  }) async {
    http.Response? lastErrorResponse;
    Exception? lastException;

    for (final endpoint in _endpoints) {
      final uri = Uri.parse('$endpoint$path');
      try {
        final response = await client
            .post(uri, headers: headers, body: body)
            .timeout(timeout);
        return response;
      } on SocketException catch (e) {
        lastException = e;
        continue;
      } on TimeoutException catch (e) {
        lastException = e;
        continue;
      } on http.ClientException catch (e) {
        lastException = e;
        continue;
      } catch (e) {
        lastException = e is Exception ? e : Exception(e.toString());
        continue;
      }
    }
    throw lastException ?? Exception('Aucune réponse du serveur');
  }

  Future<http.Response> _getWithFallback({
    required String path,
    required Map<String, String> headers,
    Duration timeout = const Duration(seconds: 20),
  }) async {
    Exception? lastException;

    for (final endpoint in _endpoints) {
      final uri = Uri.parse('$endpoint$path');
      try {
        final response = await client
            .get(uri, headers: headers)
            .timeout(timeout);
        return response;
      } on SocketException catch (e) {
        lastException = e;
        continue;
      } on TimeoutException catch (e) {
        lastException = e;
        continue;
      } on http.ClientException catch (e) {
        lastException = e;
        continue;
      } catch (e) {
        lastException = e is Exception ? e : Exception(e.toString());
        continue;
      }
    }

    throw lastException ?? Exception('Aucune réponse du serveur');
  }

  /// Initialize payment for subscription
  /// 
  /// [planId] - Subscription plan ID
  /// [billingCycle] - 'monthly' or 'annual'
  /// [amount] - Payment amount
  /// [currency] - Currency code (XAF for FCFA)
  /// [email] - User email
  /// [phone] - User phone number
  /// [name] - User full name
  /// [token] - User authentication token
  /// [paymentMethod] - 'card' or 'mobile_money'
  Future<Map<String, dynamic>> initiatePayment({
    required String planId,
    required String billingCycle,
    required double amount,
    String currency = 'XAF',
    required String email,
    required String phone,
    required String name,
    required String token,
    String paymentMethod = 'card',
  }) async {
    try {
      print('🔵 PAYMENT - Initiating payment');
      print('🔵 PAYMENT - Plan: $planId, Amount: $amount, Method: $paymentMethod');
      
      final response = await _postWithFallback(
        path: '/payment/initiate',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'plan_id': planId,
          'billing_cycle': billingCycle,
          'payment_method': paymentMethod,
          'phone_number': phone,
        }),
        timeout: const Duration(seconds: 30),
      );

      print('🔵 PAYMENT - Status: ${response.statusCode}');
      print('🔵 PAYMENT - Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'data': data['data'], // Return full data object with tx_ref, public_key, etc.
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de l\'initialisation',
        };
      }
    } catch (e) {
      print('🔴 PAYMENT - Exception: $e');
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
    String? txRef,
    required String token,
  }) async {
    try {
      final query = txRef != null ? '?tx_ref=$txRef' : '';
      final response = await _getWithFallback(
        path: '/payment/verify/$transactionId$query',
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        timeout: const Duration(seconds: 20),
      );

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

  /// Initiate Mobile Money Payment (MTN, Orange, Moov, etc.)
  /// 
  /// [planId] - Plan ID to subscribe to
  /// [phone] - Phone number for mobile money
  /// [operator] - Mobile operator (mtn, orange, moov, etc.)
  /// [amount] - Payment amount
  /// [currency] - Currency (default XOF)
  /// [email] - User email
  /// [name] - User name
  /// [token] - Auth token
  Future<Map<String, dynamic>> initiateMobileMoneyPayment({
    required String planId,
    required String phone,
    required String operator,
    required double amount,
    String currency = 'XOF',
    required String email,
    required String name,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/payment/mobile-money/initiate'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'plan_id': planId,
          'phone': phone,
          'operator': operator,
          'amount': amount,
          'currency': currency,
          'email': email,
          'name': name,
        }),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'transaction_id': data['transaction_id'],
          'reference': data['reference'],
          'status': data['status'],
          'message': data['message'],
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

  /// Dispose resources
  void dispose() {
    client.close();
  }
}