import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../models/user_model.dart';
import '../services/api_service.dart';
import '../../core/constants/app_constants.dart';

class AuthProvider with ChangeNotifier {
  UserModel? _user;
  String? _token;
  bool _isLoading = false;
  String? _error;
  
  UserModel? get user => _user;
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
      final prefs = await SharedPreferences.getInstance();
      final tokenData = prefs.getString(AppConstants.tokenKey);
      final userData = prefs.getString(AppConstants.userKey);
      
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
      final response = await _apiService.login(
        email: email,
        password: password,
      );
      
      if (response['success'] == true) {
        _token = response['data']['token'];
        _user = UserModel.fromJson(response['data']['user']);
        
        // Save to local storage
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(AppConstants.tokenKey, _token!);
        await prefs.setString(AppConstants.userKey, json.encode(_user!.toJson()));
        
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response['message'] ?? 'Erreur de connexion';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
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
        
        // Save to local storage
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(AppConstants.tokenKey, _token!);
        await prefs.setString(AppConstants.userKey, json.encode(_user!.toJson()));
        
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
      _error = e.toString();
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
    
    // Clear local storage
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(AppConstants.tokenKey);
    await prefs.remove(AppConstants.userKey);
    
    notifyListeners();
  }
  
  // Refresh User Data
  Future<void> refreshUser() async {
    if (_token == null) return;
    
    try {
      final response = await _apiService.getUserProfile(_token!);
      
      if (response['success'] == true) {
        _user = UserModel.fromJson(response['data']);
        
        // Update local storage
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(AppConstants.userKey, json.encode(_user!.toJson()));
        
        notifyListeners();
      }
    } catch (e) {
      // If refresh fails, might be token expired
      if (e.toString().contains('401')) {
        await logout();
      }
    }
  }
  
  // Update Profile
  Future<bool> updateProfile({
    String? name,
    String? phone,
    String? avatar,
  }) async {
    if (_token == null) return false;
    
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      final response = await _apiService.updateProfile(
        token: _token!,
        name: name,
        phone: phone,
        avatar: avatar,
      );
      
      if (response['success'] == true) {
        _user = UserModel.fromJson(response['data']);
        
        // Update local storage
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(AppConstants.userKey, json.encode(_user!.toJson()));
        
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response['message'] ?? 'Erreur de mise à jour';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Clear Error
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
