import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'dart:convert';
import '../models/user_model.dart';
import '../services/api_service.dart';
import '../../core/utils/api_helpers.dart';
import '../../core/services/push_notification_service.dart';

class AuthProvider with ChangeNotifier {
  UserModel? _user;
  String? _token;
  bool _isLoading = false;
  String? _error;
  bool _needsProfileCompletion = false;
  List<String> _missingProfileFields = [];
  
  // ✅ SECURE: Utilise FlutterSecureStorage au lieu de SharedPreferences
  final _secureStorage = const FlutterSecureStorage();
  
  UserModel? get user => _user;
  UserModel? get currentUser => _user; // Alias for compatibility
  String? get token => _token;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _user != null && _token != null;
  bool get needsProfileCompletion => _needsProfileCompletion;
  List<String> get missingProfileFields => List.unmodifiable(_missingProfileFields);
  
  final ApiService _apiService = ApiService();
  
  // Initialize - Check if user is already logged in
  Future<void> initialize() async {
    _isLoading = true;
    notifyListeners();
    
    try {
      // ✅ SECURE: Lire depuis FlutterSecureStorage
      final tokenData = await _secureStorage.read(key: 'auth_token');
      final userData = await _secureStorage.read(key: 'user_data');
      
      if (tokenData != null && userData != null) {
        _token = tokenData;
        _user = UserModel.fromJson(json.decode(userData));
        
        // Verify token is still valid
        await refreshUser();

        // Re-synchroniser le token/topic push au démarrage si session restaurée.
        try {
          final pushService = PushNotificationService();
          await pushService.initialize();
          await pushService.syncAfterAuth();
        } catch (e) {
          debugPrint('Push init after session restore failed: $e');
        }
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
  
  // Login
  Future<bool> login({
    required String email,
    required String password,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      // ✅ SECURE: Input validation avant API call
      if (!ApiHelpers.isValidEmail(email)) {
        _error = 'Adresse email invalide';
        _isLoading = false;
        notifyListeners();
        return false;
      }
      
      if (password.isEmpty || password.length < 6) {
        _error = 'Le mot de passe doit contenir au moins 6 caractères';
        _isLoading = false;
        notifyListeners();
        return false;
      }
      
      final response = await _apiService.login(
        email: email,
        password: password,
      );
      
      if (response['success'] == true) {
        _token = response['data']['token'];
        
        // Parse user data from response
        final userData = response['data']['user'];
        _user = UserModel.fromJson(userData);

        _missingProfileFields =
            List<String>.from(response['data']['missing_fields'] ?? []);
        _needsProfileCompletion = _missingProfileFields.isNotEmpty;
        
        // ✅ SECURE: Stocker en FlutterSecureStorage (encrypted)
        await _secureStorage.write(
          key: 'auth_token',
          value: _token!,
        );
        await _secureStorage.write(
          key: 'user_data',
          value: json.encode(_user!.toJson()),
        );
        
        // Garder aussi SharedPreferences pour les préférences non-sensibles
        final prefs = await SharedPreferences.getInstance();
        await prefs.setBool('is_logged_in', true);
        await prefs.setString('api_token', _token!);

        // Initialiser/synchroniser les notifications push après login.
        try {
          final pushService = PushNotificationService();
          await pushService.initialize();
          await pushService.syncAfterAuth();
        } catch (e) {
          debugPrint('Push init after login failed: $e');
        }
        
        _isLoading = false;
        notifyListeners();
        
        // Fetch current subscription from API and update SubscriptionProvider
        // This ensures the app shows the correct plan after login
        _updateSubscriptionAfterLogin();
        
        return true;
      } else {
        _error = response['message'] ?? 'Erreur de connexion';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      // Amélioration du message d'erreur
      if (e.toString().contains('SocketException')) {
        _error = 'Pas de connexion Internet';
      } else if (e.toString().contains('TimeoutException')) {
        _error = 'La connexion a expiré. Veuillez réessayer.';
      } else if (e.toString().contains('FormatException')) {
        _error = 'Erreur de format de données';
      } else {
        _error = 'Erreur: ${e.toString().replaceAll('Exception: ', '')}';
      }
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Register
  Future<bool> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String phone,
    String? jurisdiction,
    String? mobileRole,
    String? referralCode,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      // ✅ SECURE: Input validation
      if (name.isEmpty || name.length < 3) {
        _error = 'Le nom doit contenir au moins 3 caractères';
        _isLoading = false;
        notifyListeners();
        return false;
      }
      
      if (!ApiHelpers.isValidEmail(email)) {
        _error = 'Adresse email invalide';
        _isLoading = false;
        notifyListeners();
        return false;
      }
      
      if (password.isEmpty || password.length < 6) {
        _error = 'Le mot de passe doit contenir au moins 6 caractères';
        _isLoading = false;
        notifyListeners();
        return false;
      }
      
      if (password != passwordConfirmation) {
        _error = 'Les mots de passe ne correspondent pas';
        _isLoading = false;
        notifyListeners();
        return false;
      }
      
      if (!ApiHelpers.isValidPhone(phone)) {
        _error = 'Numéro de téléphone invalide';
        _isLoading = false;
        notifyListeners();
        return false;
      }
      
      final response = await _apiService.register(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        phone: phone,
        jurisdiction: jurisdiction,
        mobileRole: mobileRole,
        referralCode: referralCode,
      );
      
      if (response['success'] == true) {
        _token = response['data']['token'];
        _user = UserModel.fromJson(response['data']['user']);
        
        // ✅ SECURE: Stocker en FlutterSecureStorage
        await _secureStorage.write(
          key: 'auth_token',
          value: _token!,
        );
        await _secureStorage.write(
          key: 'user_data',
          value: json.encode(_user!.toJson()),
        );
        
        final prefs = await SharedPreferences.getInstance();
        await prefs.setBool('is_logged_in', true);
        await prefs.setString('api_token', _token!);

        // Initialiser/synchroniser les notifications push après inscription.
        try {
          final pushService = PushNotificationService();
          await pushService.initialize();
          await pushService.syncAfterAuth();
        } catch (e) {
          debugPrint('Push init after register failed: $e');
        }
        
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response['message'] ?? 'Erreur d\'inscription';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      // Amélioration du message d'erreur
      if (e.toString().contains('SocketException')) {
        _error = 'Pas de connexion Internet';
      } else if (e.toString().contains('TimeoutException')) {
        _error = 'La connexion a expiré. Veuillez réessayer.';
      } else if (e.toString().contains('FormatException')) {
        _error = 'Erreur de format de données';
      } else {
        _error = 'Erreur: ${e.toString().replaceAll('Exception: ', '')}';
      }
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Logout
  Future<void> logout() async {
    try {
      if (_token != null) {
        await _apiService.logout(_token!);
      }
    } catch (e) {
      // Ignore logout errors
    }
    
    _user = null;
    _token = null;
    
    // Clear secure storage
    await _secureStorage.delete(key: 'auth_token');
    await _secureStorage.delete(key: 'user_data');
    
    // Clear non-sensitive data
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('is_logged_in');
    await prefs.remove('api_token');
    await prefs.remove('chat_user_id');
    
    // CRITICAL: Clear Hive cache to prevent data leakage between users
    try {
      final documentsBox = Hive.box<Map>('documents');
      await documentsBox.clear();

      if (Hive.isBoxOpen('auth_box')) {
        await Hive.box('auth_box').clear();
      }
      if (Hive.isBoxOpen('user_box')) {
        await Hive.box('user_box').clear();
      }
      if (Hive.isBoxOpen('cache_box')) {
        await Hive.box('cache_box').clear();
      }

      print('✅ Hive cache cleared on logout');
    } catch (e) {
      print('⚠️ Could not clear Hive cache: $e');
    }
    
    notifyListeners();
  }
  
  // Refresh User Data
  Future<void> refreshUser() async {
    if (_token == null) return;
    
    try {
      final response = await _apiService.getUserProfile(_token!);
      
      if (response['success'] == true) {
        // Parse user data properly from the nested structure
        final userData = response['data']['user'] ?? response['data'];
        _user = UserModel.fromJson(userData);

        _missingProfileFields =
            List<String>.from(response['data']['missing_fields'] ?? []);
        _needsProfileCompletion = _missingProfileFields.isNotEmpty;
        
        // Update secure storage
        await _secureStorage.write(key: 'user_data', value: json.encode(_user!.toJson()));
        
        notifyListeners();
      } else if (response['message']?.contains('401') == true || 
                 response['message']?.contains('Unauthenticated') == true) {
        // Only logout on authentication errors
        await logout();
      }
    } catch (e) {
      // Only logout on clear authentication errors (401, 403)
      final errorMessage = e.toString().toLowerCase();
      if (errorMessage.contains('401') || 
          errorMessage.contains('403') || 
          errorMessage.contains('unauthenticated') ||
          errorMessage.contains('unauthorized')) {
        await logout();
      }
      // Ignore other errors (network, timeout, etc.) - keep user logged in
    }
  }
  
  // Update Profile
  Future<bool> updateProfile({
    String? name,
    String? phone,
    String? avatar,
    String? address,
    String? city,
    String? jurisdiction,
    String? mobileRole,
  }) async {
    if (_token == null) return false;
    
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      print('🟢 AUTH PROVIDER - Calling updateProfile');
      final response = await _apiService.updateProfile(
        token: _token!,
        name: name,
        phone: phone,
        avatar: avatar,
        address: address,
        city: city,
        jurisdiction: jurisdiction,
        mobileRole: mobileRole,
      );
      
      print('🟢 AUTH PROVIDER - Response: $response');
      
      if (response['success'] == true) {
        // Parse user data properly from the nested structure
        final userData = response['data']['user'] ?? response['data'];
        _user = UserModel.fromJson(userData);

        _missingProfileFields =
            List<String>.from(response['data']['missing_fields'] ?? []);
        _needsProfileCompletion = _missingProfileFields.isNotEmpty;
        
        // Update secure storage
        await _secureStorage.write(key: 'user_data', value: json.encode(_user!.toJson()));
        
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response['message'] ?? 'Erreur de mise à jour';
        print('🔴 AUTH PROVIDER - Error: $_error');
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      // Amélioration du message d'erreur
      if (e.toString().contains('SocketException')) {
        _error = 'Pas de connexion Internet';
      } else if (e.toString().contains('TimeoutException')) {
        _error = 'La connexion a expiré. Veuillez réessayer.';
      } else if (e.toString().contains('FormatException')) {
        _error = 'Erreur de format de données';
      } else {
        _error = 'Erreur de connexion: ${e.toString()}';
      }
      print('🔴 AUTH PROVIDER - Exception: $e');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Update subscription after login - fetch current subscription from API
  // This is called after successful login to sync the app's subscription state with backend
  void _updateSubscriptionAfterLogin() {
    // We need to access SubscriptionProvider but avoid circular dependencies
    // So we'll use a callback or direct provider access via context
    // For now, this method can be extended to handle subscription updates
    // The actual subscription sync will happen in the app initialization
  }
  
  // Send password reset email
  Future<Map<String, dynamic>> forgotPassword({
    required String email,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.forgotPassword(email: email);

      _isLoading = false;
      if (response['success'] == true) {
        notifyListeners();
        return {'success': true, 'message': response['message'] ?? 'Email envoyé'};
      } else {
        _error = response['message'] ?? 'Erreur lors de la demande';
        notifyListeners();
        return {'success': false, 'message': response['message'] ?? 'Erreur lors de la demande'};
      }
    } catch (e) {
      _isLoading = false;
      _error = ApiHelpers.parseApiError(e);
      notifyListeners();
      return {'success': false, 'message': _error};
    }
  }
  
  // Clear Error
  void clearError() {
    _error = null;
    notifyListeners();
  }

  // Increment stats counters
  Future<void> incrementStat(String statType) async {
    if (_user == null) {
      print('⚠️ Cannot increment stat: user is null');
      return;
    }

    if (_token == null) {
      print('⚠️ Cannot increment stat: token is null');
      return;
    }

    print('📊 Incrementing stat: $statType');
    try {
      final api = ApiService();
      print('📡 Calling API endpoint: /mobile/user/stats/increment');
      
      final response = await api.post(
        endpoint: '/mobile/user/stats/increment',
        data: {'stat_type': statType},
        token: _token,
      );

      print('📥 API Response success: ${response['success']}');

      if (response['success'] == true) {
        // Update local user object with new stats from response
        final data = response['data'];
        print('✅ Stat incremented successfully');
        
        // Create a new UserModel with updated stats
        _user = _user!.copyWith(
          summariesGenerated: data['summaries_generated'] ?? _user!.summariesGenerated,
          quizzesCreated: data['quizzes_created'] ?? _user!.quizzesCreated,
          revisionSessions: data['revision_sessions'] ?? _user!.revisionSessions,
        );
        
        // Update secure storage
        await _secureStorage.write(
          key: 'user_data',
          value: json.encode(_user!.toJson()),
        );
        
        notifyListeners();
      } else {
        print('❌ API returned success=false: ${response['message']}');
      }
    } catch (e) {
      print('❌ Error incrementing stat: $e');
    }
  }
}