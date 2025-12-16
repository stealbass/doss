import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';
import '../../core/utils/api_helpers.dart';

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
  
  // Helper method to get headers
  Map<String, String> _getHeaders({String? token}) {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    
    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }
    
    return headers;
  }
  
  // Error handler
  Map<String, dynamic> _handleError(dynamic error) {
    if (error is SocketException) {
      return {
        'success': false,
        'message': 'Pas de connexion internet',
      };
    } else if (error is http.Response) {
      try {
        final body = json.decode(error.body);
        return {
          'success': false,
          'message': body['message'] ?? 'Erreur serveur',
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
      'message': error.toString(),
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
    try {
      final response = await http.post(
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
      );
      
      if (response.statusCode == 200 || response.statusCode == 201) {
        return json.decode(response.body);
      } else {
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
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: _getHeaders(),
        body: json.encode({
          'email': email,
          'password': password,
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
  
  // Logout
  Future<Map<String, dynamic>> logout(String token) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/logout'),
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
  
  // Get User Profile
  Future<Map<String, dynamic>> getUserProfile(String token) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/profile'),
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
  
  // Update Profile
  Future<Map<String, dynamic>> updateProfile({
    required String token,
    String? name,
    String? phone,
    String? avatar,
  }) async {
    try {
      final body = <String, dynamic>{};
      if (name != null) body['name'] = name;
      if (phone != null) body['phone'] = phone;
      if (avatar != null) body['avatar'] = avatar;
      
      final response = await http.put(
        Uri.parse('$baseUrl/profile'),
        headers: _getHeaders(token: token),
        body: json.encode(body),
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
  
  // CHAT
  
  // Send Chat Message
  Future<Map<String, dynamic>> sendChatMessage({
    required String token,
    required String message,
    bool useSimpleRag = false,
    bool useAdvancedRag = false,
    List<int>? documentIds,
    bool enableAnonymization = false,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/chat'),
        headers: _getHeaders(token: token),
        body: json.encode({
          'message': message,
          'use_simple_rag': useSimpleRag,
          'use_advanced_rag': useAdvancedRag,
          'document_ids': documentIds,
          'enable_anonymization': enableAnonymization,
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
  
  // DOCUMENTS
  
  // Upload Document
  Future<Map<String, dynamic>> uploadDocument({
    required String token,
    required File file,
    String? category,
    String? description,
  }) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/documents/upload'),
      );
      
      request.headers.addAll(_getHeaders(token: token));
      request.files.add(await http.MultipartFile.fromPath('file', file.path));
      
      if (category != null) request.fields['category'] = category;
      if (description != null) request.fields['description'] = description;
      
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
    String? category,
    int page = 1,
  }) async {
    try {
      final uri = category != null
          ? Uri.parse('$baseUrl/documents?category=$category&page=$page')
          : Uri.parse('$baseUrl/documents?page=$page');
      
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
  
  // Get Subscription Plans
  Future<Map<String, dynamic>> getSubscriptionPlans() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/subscriptions/plans'),
        headers: _getHeaders(),
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
  
  // Get Referral Info
  Future<Map<String, dynamic>> getReferralInfo(String token) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/referral/info'),
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
}
