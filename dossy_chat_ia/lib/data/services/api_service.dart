import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';
import '../../core/utils/api_helpers.dart';

// Add a default network timeout for HTTP calls
const Duration _kNetworkTimeout = Duration(seconds: 60);

/// Service API principal pour toutes les requêtes backend
///
/// Utilitaires disponibles (lib/core/utils/api_helpers.dart):
/// - ApiHelpers.hasInternetConnection() - Vérifier la connexion
/// - ApiHelpers.parseApiError(error) - Parser les erreurs
/// - ApiHelpers.retryWithBackoff() - Retry automatique
/// - ApiHelpers.isValidEmail() / isValidPhone() - Validation
/// - NetworkHelper() - Surveillance de la connexion
/// - ApiInterceptor (pour Dio) - Logs automatiques
class ApiService {
  final String baseUrl = AppConstants.baseUrl;

  // Check internet connection before making requests
  Future<bool> _checkConnection() async {
    return await ApiHelpers.hasInternetConnection();
  }

  // Helper method to get headers
  Map<String, String> _getHeaders({String? token, bool jsonContent = true}) {
    final headers = <String, String>{
      'Accept': 'application/json',
    };

    // Only set JSON content-type for non-multipart requests
    if (jsonContent) {
      headers['Content-Type'] = 'application/json';
    }

    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }

    return headers;
  }

  // Error handler
  Map<String, dynamic> _handleError(dynamic error) {
    if (error is TimeoutException) {
      return {
        'success': false,
        'message': 'Requête expirée. Vérifiez votre connexion internet.',
      };
    } else if (error is SocketException) {
      return {
        'success': false,
        'message':
            'Pas de connexion internet. Vérifiez votre réseau Wi-Fi ou données mobiles.',
      };
    } else if (error is http.Response) {
      try {
        final body = json.decode(error.body);
        return {
          'success': false,
          'message': body['message'] ?? 'Erreur serveur (${error.statusCode})',
        };
      } catch (e) {
        return {
          'success': false,
          'message': 'Erreur serveur : ${error.statusCode}',
        };
      }
    }
    return {
      'success': false,
      'message': ApiHelpers.parseApiError(error),
    };
  }

  // AUTHENTICATION

  // Register
  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String phone,
    String? jurisdiction,
    String? referralCode,
  }) async {
    // Check internet connection first
    if (!await _checkConnection()) {
      return {
        'success': false,
        'message':
            'Pas de connexion internet. Vérifiez votre réseau Wi-Fi ou données mobiles.',
      };
    }

    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/register'),
            headers: _getHeaders(),
            body: json.encode({
              'name': name,
              'email': email,
              'password': password,
              'password_confirmation': passwordConfirmation,
              'phone': phone,
              'jurisdiction': jurisdiction,
              'referral_code': referralCode,
            }),
          )
          .timeout(_kNetworkTimeout);

      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = json.decode(response.body);
        return {
          'success': true,
          ...data,
        };
      } else {
        if (kDebugMode) {
          debugPrint(
              'API REGISTER ERROR: ${response.statusCode} ${response.body}');
          return {
            'success': false,
            'message': 'HTTP ${response.statusCode}: ${response.body}',
          };
        }
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Login
  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    // Check internet connection first
    if (!await _checkConnection()) {
      return {
        'success': false,
        'message':
            'Pas de connexion internet. Vérifiez votre réseau Wi-Fi ou données mobiles.',
      };
    }

    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/login'),
            headers: _getHeaders(),
            body: json.encode({
              'email': email,
              'password': password,
            }),
          )
          .timeout(_kNetworkTimeout);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return {
          'success': true,
          ...data,
        };
      } else {
        if (kDebugMode) {
          // Provide detailed information in debug builds to help troubleshooting
          debugPrint(
              'API LOGIN ERROR: ${response.statusCode} ${response.body}');
          return {
            'success': false,
            'message': 'HTTP ${response.statusCode}: ${response.body}',
          };
        }
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Logout
  Future<Map<String, dynamic>> logout(String token) async {
    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/logout'),
            headers: _getHeaders(token: token),
          )
          .timeout(_kNetworkTimeout);

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Get User Profile
  Future<Map<String, dynamic>> getUserProfile(String token) async {
    try {
      final response = await http
          .get(
            Uri.parse('$baseUrl/profile'),
            headers: _getHeaders(token: token),
          )
          .timeout(_kNetworkTimeout);

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Forgot Password - request a password reset email
  Future<Map<String, dynamic>> forgotPassword({
    required String email,
  }) async {
    // Check internet connection first
    if (!await _checkConnection()) {
      return {
        'success': false,
        'message':
            'Pas de connexion internet. Vérifiez votre réseau Wi-Fi ou données mobiles.',
      };
    }

    Future<Map<String, dynamic>> tryEndpoint(String url) async {
      try {
        final response = await http
            .post(
              Uri.parse(url),
              headers: _getHeaders(),
              body: json.encode({'email': email}),
            )
            .timeout(_kNetworkTimeout);

        if (response.statusCode == 200 || response.statusCode == 201) {
          final data = json.decode(response.body);
          return {
            'success': true,
            ...data,
          };
        } else {
          if (kDebugMode) {
            debugPrint(
                'API FORGOT PASSWORD ERROR: ${response.statusCode} ${response.body}');
            return {
              'success': false,
              'statusCode': response.statusCode,
              'message': 'HTTP ${response.statusCode}: ${response.body}',
            };
          }
          return _handleError(response);
        }
      } catch (e) {
        return _handleError(e);
      }
    }

    // Primary endpoint (mobile base)
    final primaryUrl = '$baseUrl/password/forgot';

    final primaryResult = await tryEndpoint(primaryUrl);

    // If primary failed with 404 (route not found), try common alternatives
    if (primaryResult['success'] == false &&
        primaryResult['statusCode'] == 404) {
      final alternatives = [
        '${AppConstants.apiBaseUrl}/password/email',
        '${AppConstants.apiBaseUrl}/forgot-password',
        '${AppConstants.apiBaseUrl}/password/forgot',
      ];

      for (final url in alternatives) {
        final altResult = await tryEndpoint(url);
        if (altResult['success'] == true) {
          return altResult;
        }
      }
    }

    return primaryResult;
  }

  // Ping the API root (useful for debugging connectivity)
  Future<Map<String, dynamic>> ping() async {
    try {
      final response = await http
          .get(
            Uri.parse(baseUrl),
          )
          .timeout(_kNetworkTimeout);

      return {
        'success': response.statusCode == 200,
        'statusCode': response.statusCode,
        'body': response.body,
      };
    } catch (e) {
      return _handleError(e);
    }
  }

  // Update Profile
  Future<Map<String, dynamic>> updateProfile({
    required String token,
    String? name,
    String? phone,
    String? address,
    String? city,
    String? avatar,
  }) async {
    try {
      final body = <String, dynamic>{};
      if (name != null) body['name'] = name;
      if (phone != null) body['phone'] = phone;
      if (address != null) body['address'] = address;
      if (city != null) body['city'] = city;
      if (avatar != null) body['avatar'] = avatar;

      print('🔵 UPDATE PROFILE - URL: $baseUrl/profile');
      print('🔵 UPDATE PROFILE - Body: $body');
      print('🔵 UPDATE PROFILE - Token: ${token.substring(0, 20)}...');

      final response = await http
          .put(
            Uri.parse('$baseUrl/profile'),
            headers: _getHeaders(token: token),
            body: json.encode(body),
          )
          .timeout(_kNetworkTimeout);

      print('🔵 UPDATE PROFILE - Status: ${response.statusCode}');
      print('🔵 UPDATE PROFILE - Response: ${response.body}');

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      print('🔴 UPDATE PROFILE - Exception: $e');
      return _handleError(e);
    }
  }

  // CHAT

  // Send Chat Message
  Future<Map<String, dynamic>> sendChatMessage({
    required String token,
    required String message,
    bool useSimpleRag = false,
    bool useAdvancedRag = false,
    List<int>? documentIds,
    List<String>? documentContents,
    bool enableAnonymization = false,
  }) async {
    try {
      // Determine RAG type based on flags
      String ragType = 'both';
      if (useSimpleRag && !useAdvancedRag) {
        ragType = 'simple';
      } else if (!useSimpleRag && useAdvancedRag) {
        ragType = 'advanced';
      }
      
      final useRag = useSimpleRag || useAdvancedRag;

      final url = '$baseUrl/chat';
      if (kDebugMode) {
        debugPrint('=== CHAT REQUEST ===');
        debugPrint('URL: $url');
        debugPrint('RAG: useRag=$useRag, ragType=$ragType');
        debugPrint('Message: $message');
      }

      final response = await http
          .post(
            Uri.parse(url),
            headers: _getHeaders(token: token),
            body: json.encode({
              'message': message,
              'use_rag': useRag,
              'rag_type': ragType,
              'document_ids': documentIds,
              'document_contents': documentContents,
              'enable_anonymization': enableAnonymization,
            }),
          )
          .timeout(_kNetworkTimeout);

      if (kDebugMode) {
        debugPrint('=== CHAT RESPONSE ===');
        debugPrint('Status: ${response.statusCode}');
        debugPrint('Body: ${response.body}');
      }

      if (response.statusCode == 200) {
        final decodedResponse = json.decode(response.body);
        if (kDebugMode) {
          debugPrint('Decoded: ${decodedResponse.toString()}');
        }
        return decodedResponse;
      } else {
        return _handleError(response);
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('Chat error: $e');
      }
      return _handleError(e);
    }
  }

  /// Anonymiser le contenu d'un document
  /// Détecte et remplace automatiquement les données sensibles
  Future<String> anonymizeDocument(String content, String token) async {
    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/documents/anonymize'),
            headers: _getHeaders(token: token),
            body: json.encode({'content': content}),
          )
          .timeout(_kNetworkTimeout);

      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        if (decoded['success'] == true && decoded['data'] != null) {
          return decoded['data']['anonymized_content'] ?? content;
        }
      }
      
      // Fallback: retourner le contenu original en cas d'erreur
      return content;
    } catch (e) {
      print('Erreur anonymisation: $e');
      // Fallback: retourner le contenu original
      return content;
    }
  }

  // Get Chat History
  Future<Map<String, dynamic>> getChatHistory({
    required String token,
    int? conversationId,
  }) async {
    try {
      final uri = conversationId != null
          ? Uri.parse('$baseUrl/chat/history?conversation_id=$conversationId')
          : Uri.parse('$baseUrl/chat/history');

      final response = await http.get(
        uri,
        headers: _getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Get Conversations List
  Future<Map<String, dynamic>> getConversations({
    required String token,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/chat/conversations'),
        headers: _getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Delete Conversation
  Future<Map<String, dynamic>> deleteConversation({
    required String token,
    required int conversationId,
  }) async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/chat/conversation/$conversationId'),
        headers: _getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // DOCUMENTS

  // Upload Document
  Future<Map<String, dynamic>> uploadDocument({
    required String token,
    required File file,
    String? title,
  }) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/documents/upload'),
      );

      // Multipart: avoid forcing JSON content-type so boundary is set correctly
      request.headers.addAll(_getHeaders(token: token, jsonContent: false));
      request.files.add(await http.MultipartFile.fromPath('file', file.path));

      if (title != null) request.fields['title'] = title;

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200 || response.statusCode == 201) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Get Documents
  Future<Map<String, dynamic>> getDocuments({
    required String token,
    int page = 1,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/documents?page=$page');

      final response = await http.get(
        uri,
        headers: _getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Delete Document
  Future<Map<String, dynamic>> deleteDocument({
    required String token,
    required int documentId,
  }) async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/documents/$documentId'),
        headers: _getHeaders(token: token),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // SUBSCRIPTION

  // Get Subscription Plans (prefers new endpoint with up-to-date pricing)
  Future<Map<String, dynamic>> getSubscriptionPlans({String? token}) async {
    // Try the new authenticated endpoint first (has freshest prices)
    if (token != null && token.isNotEmpty) {
      try {
        final response = await http
            .get(
              Uri.parse('$baseUrl/subscription-plans'),
              headers: _getHeaders(token: token),
            )
            .timeout(_kNetworkTimeout);

        if (response.statusCode == 200) {
          return json.decode(response.body);
        }
      } catch (_) {
        // Swallow and fall back to legacy endpoint
      }
    }

    // Legacy public endpoint as fallback
    try {
      final response = await http
          .get(
            Uri.parse('$baseUrl/subscriptions/plans'),
            headers: _getHeaders(),
          )
          .timeout(_kNetworkTimeout);

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Get Current Subscription
  Future<Map<String, dynamic>> getCurrentSubscription({required String token}) async {
    if (!await _checkConnection()) {
      return {
        'success': false,
        'message': 'Pas de connexion internet.',
      };
    }

    try {
      final response = await http
          .get(
            Uri.parse('$baseUrl/subscription/current'),
            headers: _getHeaders(token: token),
          )
          .timeout(_kNetworkTimeout);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return {
          'success': true,
          'subscription': data['subscription'],
          'quotas': data['quotas'],
        };
      } else if (response.statusCode == 404) {
        // No active subscription found
        return {
          'success': true,
          'subscription': null,
          'message': 'Aucun abonnement actif',
        };
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // Initiate Payment
  Future<Map<String, dynamic>> initiatePayment({
    required String token,
    required String planId,
    required String duration,
    String? couponCode,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/subscriptions/initiate-payment'),
        headers: _getHeaders(token: token),
        body: json.encode({
          'plan_id': planId,
          'duration': duration,
          'coupon_code': couponCode,
        }),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return _handleError(response);
      }
    } catch (e) {
      return _handleError(e);
    }
  }

  // REFERRAL

  // Get Referral Info (aggregate code + history + rewards from available endpoints)
  Future<Map<String, dynamic>> getReferralInfo(String token) async {
    Map<String, dynamic> referralCode = {};
    List<dynamic> history = [];
    Map<String, dynamic> rewards = {};

    // Fetch referral code and counters
    try {
      final res = await http
          .get(
            Uri.parse('$baseUrl/referral/code'),
            headers: _getHeaders(token: token),
          )
          .timeout(_kNetworkTimeout);
      if (res.statusCode == 200) {
        referralCode = json.decode(res.body)['data'] ?? {};
      }
    } catch (_) {}

    // Fetch history
    try {
      final res = await http
          .get(
            Uri.parse('$baseUrl/referral/history'),
            headers: _getHeaders(token: token),
          )
          .timeout(_kNetworkTimeout);
      if (res.statusCode == 200) {
        history = json.decode(res.body)['data'] ?? [];
      }
    } catch (_) {}

    // Fetch rewards summary
    try {
      final res = await http
          .get(
            Uri.parse('$baseUrl/referral/rewards'),
            headers: _getHeaders(token: token),
          )
          .timeout(_kNetworkTimeout);
      if (res.statusCode == 200) {
        rewards = json.decode(res.body)['data'] ?? {};
      }
    } catch (_) {}

    if (referralCode.isEmpty && history.isEmpty && rewards.isEmpty) {
      return {
        'success': false,
        'message': 'Aucune donnée de parrainage trouvée',
      };
    }

    return {
      'success': true,
      'data': {
        'code': referralCode['referral_code'] ?? '',
        'total_referrals': referralCode['total_referrals'] ?? history.length,
        'active_referrals': history
            .whereType<Map<String, dynamic>>()
            .where((h) => (h['status'] ?? '').toString().toLowerCase() == 'completed')
            .length,
        'rewards_earned': referralCode['rewards_earned'] ?? 0,
        'rewards': rewards,
        'history': history,
      },
    };
  }

  // Generic POST method
  Future<Map<String, dynamic>> post({
    required String endpoint,
    required Map<String, dynamic> data,
    String? token,
  }) async {
    if (!await _checkConnection()) {
      return {
        'success': false,
        'message': 'Pas de connexion internet.',
      };
    }

    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl$endpoint'),
            headers: _getHeaders(token: token),
            body: json.encode(data),
          )
          .timeout(_kNetworkTimeout);

      final responseBody = json.decode(response.body);

      if (response.statusCode == 200 || response.statusCode == 201) {
        return responseBody;
      } else {
        return {
          'success': false,
          'message': responseBody['message'] ?? 'Erreur serveur',
        };
      }
    } catch (e) {
      return _handleError(e);
    }
  }}