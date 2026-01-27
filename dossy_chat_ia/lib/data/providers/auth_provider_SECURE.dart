import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../models/user_model.dart';
import '../services/api_service.dart';
import '../../core/utils/api_helpers.dart';
import 'package:flutter/material.dart';

class AuthProvider with ChangeNotifier {
  UserModel? _user;
  String? _token;
  bool _isLoading = false;
  String? _error;
  
  // ✅ NOUVEAU: Secure Storage pour tokens
  final _secureStorage = const FlutterSecureStorage();
  
  UserModel? get user => _user;
  UserModel? get currentUser => _user;
  String? get token => _token;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _user != null && _token != null;
  
  final ApiService _apiService = ApiService();
  
  // Initialize - Check if user is already logged in
  Future<void> initialize() async {
    _isLoading = true;
    notifyListeners();
    
    try {
      // ✅ NOUVEAU: Lire depuis FlutterSecureStorage au lieu de SharedPreferences
      final tokenData = await _secureStorage.read(key: 'auth_token');
      final userData = await _secureStorage.read(key: 'user_data');
      
      if (tokenData != null && userData != null) {
        _token = tokenData;
        _user = UserModel.fromJson(json.decode(userData));
        
        // Verify token is still valid
        await refreshUser();
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
      // ✅ NOUVEAU: Input validation avant API call
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
        
        // ✅ NOUVEAU: Stocker en FlutterSecureStorage (encrypted)
        await _secureStorage.write(
          key: 'auth_token',
          value: _token!,
        );
        await _secureStorage.write(
          key: 'user_data',
          value: json.encode(_user!.toJson()),
        );
        
        // ✅ Garder aussi SharedPreferences pour non-sensitive preferences
        final prefs = await SharedPreferences.getInstance();
        await prefs.setBool('is_logged_in', true);
        
        _isLoading = false;
        notifyListeners();
        
        // Fetch current subscription from API and update SubscriptionProvider
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
    String? referralCode,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      // ✅ NOUVEAU: Input validation
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
        referralCode: referralCode,
      );
      
      if (response['success'] == true) {
        _token = response['data']['token'];
        _user = UserModel.fromJson(response['data']['user']);
        
        // ✅ NOUVEAU: Stocker en FlutterSecureStorage
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
    
    // ✅ NOUVEAU: Clear FlutterSecureStorage
    await _secureStorage.delete(key: 'auth_token');
    await _secureStorage.delete(key: 'user_data');
    
    // Clear SharedPreferences
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    
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
        
        // ✅ NOUVEAU: Update FlutterSecureStorage au lieu de SharedPreferences
        await _secureStorage.write(
          key: 'user_data',
          value: json.encode(_user!.toJson()),
        );
        
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
      );
      
      print('🟢 AUTH PROVIDER - Response: $response');
      
      if (response['success'] == true) {
        // Parse user data properly from the nested structure
        final userData = response['data']['user'] ?? response['data'];
        _user = UserModel.fromJson(userData);
        
        // ✅ NOUVEAU: Update FlutterSecureStorage
        await _secureStorage.write(
          key: 'user_data',
          value: json.encode(_user!.toJson()),
        );
        
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
  
  // Update subscription after login
  void _updateSubscriptionAfterLogin() {
    print('DEBUG: Auth login complete, subscription will be fetched by app');
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
}
