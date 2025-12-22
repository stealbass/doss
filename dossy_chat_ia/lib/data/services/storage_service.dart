import 'package:hive_flutter/hive_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

/// Service for local storage management
class StorageService {
  static const String _authBoxName = 'auth_box';
  static const String _userBoxName = 'user_box';
  static const String _cacheBoxName = 'cache_box';
  
  // SharedPreferences keys
  static const String _keyToken = 'auth_token';
  static const String _keyUserId = 'user_id';
  static const String _keyThemeMode = 'theme_mode';
  static const String _keyLocale = 'locale';
  static const String _keyOnboardingComplete = 'onboarding_complete';
  static const String _keyJurisdiction = 'jurisdiction';
  
  late SharedPreferences _prefs;
  late Box _authBox;
  late Box _userBox;
  late Box _cacheBox;

  /// Initialize storage
  Future<void> initialize() async {
    // Initialize SharedPreferences
    _prefs = await SharedPreferences.getInstance();
    
    // Initialize Hive
    await Hive.initFlutter();
    
    // Open Hive boxes
    _authBox = await Hive.openBox(_authBoxName);
    _userBox = await Hive.openBox(_userBoxName);
    _cacheBox = await Hive.openBox(_cacheBoxName);
  }

  // =====================================================
  // Authentication Storage
  // =====================================================

  /// Save authentication token
  Future<void> saveToken(String token) async {
    await _prefs.setString(_keyToken, token);
    await _authBox.put('token', token);
  }

  /// Get authentication token
  String? getToken() {
    return _prefs.getString(_keyToken) ?? _authBox.get('token');
  }

  /// Remove authentication token
  Future<void> removeToken() async {
    await _prefs.remove(_keyToken);
    await _authBox.delete('token');
  }

  /// Save user ID
  Future<void> saveUserId(String userId) async {
    await _prefs.setString(_keyUserId, userId);
    await _authBox.put('user_id', userId);
  }

  /// Get user ID
  String? getUserId() {
    return _prefs.getString(_keyUserId) ?? _authBox.get('user_id');
  }

  /// Check if user is logged in
  bool isLoggedIn() {
    return getToken() != null && getUserId() != null;
  }

  /// Logout (clear all auth data)
  Future<void> logout() async {
    await removeToken();
    await _prefs.remove(_keyUserId);
    await _authBox.clear();
    await _userBox.clear();
  }

  // =====================================================
  // User Data Storage
  // =====================================================

  /// Save user data
  Future<void> saveUserData(Map<String, dynamic> userData) async {
    await _userBox.put('user_data', jsonEncode(userData));
  }

  /// Get user data
  Map<String, dynamic>? getUserData() {
    final data = _userBox.get('user_data');
    if (data != null) {
      return jsonDecode(data);
    }
    return null;
  }

  /// Update user profile field
  Future<void> updateUserField(String key, dynamic value) async {
    final userData = getUserData() ?? {};
    userData[key] = value;
    await saveUserData(userData);
  }

  // =====================================================
  // App Settings Storage
  // =====================================================

  /// Save theme mode
  Future<void> saveThemeMode(String mode) async {
    await _prefs.setString(_keyThemeMode, mode);
  }

  /// Get theme mode
  String getThemeMode() {
    return _prefs.getString(_keyThemeMode) ?? 'system';
  }

  /// Save locale
  Future<void> saveLocale(String locale) async {
    await _prefs.setString(_keyLocale, locale);
  }

  /// Get locale
  String getLocale() {
    return _prefs.getString(_keyLocale) ?? 'fr';
  }

  /// Mark onboarding as complete
  Future<void> setOnboardingComplete() async {
    await _prefs.setBool(_keyOnboardingComplete, true);
  }

  /// Check if onboarding is complete
  bool isOnboardingComplete() {
    return _prefs.getBool(_keyOnboardingComplete) ?? false;
  }

  /// Save user's jurisdiction
  Future<void> saveJurisdiction(String jurisdiction) async {
    await _prefs.setString(_keyJurisdiction, jurisdiction);
  }

  /// Get user's jurisdiction
  String? getJurisdiction() {
    return _prefs.getString(_keyJurisdiction);
  }

  // =====================================================
  // Cache Management
  // =====================================================

  /// Cache search results
  Future<void> cacheSearchResults(String query, List<dynamic> results) async {
    final cacheKey = 'search_$query';
    await _cacheBox.put(cacheKey, {
      'results': results,
      'timestamp': DateTime.now().millisecondsSinceEpoch,
    });
  }

  /// Get cached search results
  List<dynamic>? getCachedSearchResults(String query) {
    final cacheKey = 'search_$query';
    final cached = _cacheBox.get(cacheKey);
    
    if (cached != null) {
      final timestamp = cached['timestamp'] as int;
      final now = DateTime.now().millisecondsSinceEpoch;
      final age = now - timestamp;
      
      // Cache valid for 1 hour
      if (age < 3600000) {
        return cached['results'] as List<dynamic>;
      } else {
        _cacheBox.delete(cacheKey);
      }
    }
    return null;
  }

  /// Cache document
  Future<void> cacheDocument(String documentId, Map<String, dynamic> document) async {
    await _cacheBox.put('doc_$documentId', {
      'data': document,
      'timestamp': DateTime.now().millisecondsSinceEpoch,
    });
  }

  /// Get cached document
  Map<String, dynamic>? getCachedDocument(String documentId) {
    final cached = _cacheBox.get('doc_$documentId');
    
    if (cached != null) {
      final timestamp = cached['timestamp'] as int;
      final now = DateTime.now().millisecondsSinceEpoch;
      final age = now - timestamp;
      
      // Cache valid for 24 hours
      if (age < 86400000) {
        return cached['data'] as Map<String, dynamic>;
      } else {
        _cacheBox.delete('doc_$documentId');
      }
    }
    return null;
  }

  /// Clear all cache
  Future<void> clearCache() async {
    await _cacheBox.clear();
  }

  /// Clear old cache entries (older than 7 days)
  Future<void> clearOldCache() async {
    final now = DateTime.now().millisecondsSinceEpoch;
    final sevenDaysAgo = now - (7 * 24 * 3600000);
    
    final keysToDelete = <dynamic>[];
    
    for (var key in _cacheBox.keys) {
      final value = _cacheBox.get(key);
      if (value is Map && value['timestamp'] != null) {
        final timestamp = value['timestamp'] as int;
        if (timestamp < sevenDaysAgo) {
          keysToDelete.add(key);
        }
      }
    }
    
    for (var key in keysToDelete) {
      await _cacheBox.delete(key);
    }
  }

  // =====================================================
  // Offline Storage
  // =====================================================

  /// Save conversation for offline access
  Future<void> saveOfflineConversation(String conversationId, Map<String, dynamic> data) async {
    await _cacheBox.put('conversation_$conversationId', data);
  }

  /// Get offline conversation
  Map<String, dynamic>? getOfflineConversation(String conversationId) {
    return _cacheBox.get('conversation_$conversationId');
  }

  /// Save document for offline access
  Future<void> saveOfflineDocument(String documentId, Map<String, dynamic> data) async {
    await _cacheBox.put('offline_doc_$documentId', data);
  }

  /// Get offline document
  Map<String, dynamic>? getOfflineDocument(String documentId) {
    return _cacheBox.get('offline_doc_$documentId');
  }

  /// Get all offline documents
  List<Map<String, dynamic>> getAllOfflineDocuments() {
    final documents = <Map<String, dynamic>>[];
    
    for (var key in _cacheBox.keys) {
      if (key.toString().startsWith('offline_doc_')) {
        final doc = _cacheBox.get(key);
        if (doc != null) {
          documents.add(doc as Map<String, dynamic>);
        }
      }
    }
    
    return documents;
  }

  // =====================================================
  // Statistics & Usage Tracking
  // =====================================================

  /// Increment search count
  Future<void> incrementSearchCount() async {
    final count = _prefs.getInt('search_count') ?? 0;
    await _prefs.setInt('search_count', count + 1);
  }

  /// Get search count
  int getSearchCount() {
    return _prefs.getInt('search_count') ?? 0;
  }

  /// Increment analysis count
  Future<void> incrementAnalysisCount() async {
    final count = _prefs.getInt('analysis_count') ?? 0;
    await _prefs.setInt('analysis_count', count + 1);
  }

  /// Get analysis count
  int getAnalysisCount() {
    return _prefs.getInt('analysis_count') ?? 0;
  }

  /// Save last sync timestamp
  Future<void> saveLastSync() async {
    await _prefs.setInt('last_sync', DateTime.now().millisecondsSinceEpoch);
  }

  /// Get last sync timestamp
  DateTime? getLastSync() {
    final timestamp = _prefs.getInt('last_sync');
    if (timestamp != null) {
      return DateTime.fromMillisecondsSinceEpoch(timestamp);
    }
    return null;
  }

  // =====================================================
  // App Settings (Extended)
  // =====================================================
  
  /// Get app settings
  Future<Map<String, dynamic>> getAppSettings() async {
    final settings = _prefs.getString('app_settings');
    if (settings != null) {
      return jsonDecode(settings) as Map<String, dynamic>;
    }
    return {};
  }
  
  /// Save app settings
  Future<void> saveAppSettings(Map<String, dynamic> settings) async {
    await _prefs.setString('app_settings', jsonEncode(settings));
  }
  
  // =====================================================
  // Favorites Management
  // =====================================================
  
  /// Get favorites list
  Future<List<String>> getFavorites() async {
    return _prefs.getStringList('favorites') ?? [];
  }
  
  /// Save favorites list
  Future<void> saveFavorites(List<String> favorites) async {
    await _prefs.setStringList('favorites', favorites);
  }
  
  /// Add to favorites
  Future<void> addFavorite(String id) async {
    final favorites = await getFavorites();
    if (!favorites.contains(id)) {
      favorites.add(id);
      await saveFavorites(favorites);
    }
  }
  
  /// Remove from favorites
  Future<void> removeFavorite(String id) async {
    final favorites = await getFavorites();
    favorites.remove(id);
    await saveFavorites(favorites);
  }
  
  // =====================================================
  // Generic Cache Data
  // =====================================================
  
  /// Get cached data by key
  Future<String?> getCachedData(String key) async {
    return _prefs.getString('cache_$key');
  }
  
  /// Set cached data by key
  Future<void> setCachedData(String key, String data) async {
    await _prefs.setString('cache_$key', data);
  }
  
  /// Remove cached data by key
  Future<void> removeCachedData(String key) async {
    await _prefs.remove('cache_$key');
  }

  // =====================================================
  // Utility Methods
  // =====================================================

  /// Get storage size (approximate)
  Future<Map<String, int>> getStorageSize() async {
    return {
      'auth_box': _authBox.length,
      'user_box': _userBox.length,
      'cache_box': _cacheBox.length,
      'total': _authBox.length + _userBox.length + _cacheBox.length,
    };
  }

  /// Clear all data (for debugging/reset)
  Future<void> clearAllData() async {
    await _prefs.clear();
    await _authBox.clear();
    await _userBox.clear();
    await _cacheBox.clear();
  }

  /// Dispose and close boxes
  Future<void> dispose() async {
    await _authBox.close();
    await _userBox.close();
    await _cacheBox.close();
  }
}
