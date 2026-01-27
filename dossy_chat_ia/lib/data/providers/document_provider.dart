import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'dart:io';
import '../models/document_model.dart';
import '../services/api_service.dart';

class DocumentProvider with ChangeNotifier {
  final List<DocumentModel> _documents = [];
  bool _isLoading = false;
  bool _isUploading = false;
  String? _error;
  
  List<DocumentModel> get documents => _documents;
  bool get isLoading => _isLoading;
  bool get isUploading => _isUploading;
  String? get error => _error;
  
  final ApiService _apiService = ApiService();
  static const String _documentsBoxName = 'documents';
  
  DocumentProvider() {
    _initializeHive();
  }
  
  // Initialize Hive box
  Future<void> _initializeHive() async {
    try {
      if (!Hive.isBoxOpen(_documentsBoxName)) {
        await Hive.openBox<Map>(_documentsBoxName);
      }
      print('📦 Hive box initialized: $_documentsBoxName');
    } catch (e) {
      print('❌ Error initializing Hive: $e');
    }
  }

    // Load documents from Hive cache with user ID verification
    Future<void> _loadFromCache({required int userId}) async {
      try {
        final box = Hive.box<Map>(_documentsBoxName);
        _documents.clear();
      
        // Load only documents belonging to current user
        for (var docMap in box.values) {
          final docJson = Map<String, dynamic>.from(docMap);
          // Verify document belongs to current user
          if (docJson['user_id'] == userId) {
            final doc = DocumentModel.fromJson(docJson);
            _documents.add(doc);
          }
        }
        print('✅ Loaded ${_documents.length} documents from Hive cache for user $userId');
      } catch (e) {
        print('❌ Error loading from cache: $e');
      }
    }
  
    // Save documents to Hive cache with user ID to prevent cross-user contamination
    Future<void> _saveToCache({required int userId}) async {
      try {
        final box = Hive.box<Map>(_documentsBoxName);
      
        // Clear old documents from this user only
        final keysToRemove = <dynamic>[];
        for (var key in box.keys) {
          final docMap = box.get(key);
          if (docMap != null && docMap['user_id'] == userId) {
            keysToRemove.add(key);
          }
        }
        for (var key in keysToRemove) {
          await box.delete(key);
        }
      
        // Save new documents with user ID
        for (var doc in _documents) {
          final docJson = doc.toJson();
          docJson['user_id'] = userId;
          await box.put('user_${userId}_doc_${doc.id}', docJson);
        }
        print('✅ Saved ${_documents.length} documents to Hive cache for user $userId');
      } catch (e) {
        print('❌ Error saving to cache: $e');
      }
    }
  
  // Load Documents
  // Load Documents
  Future<void> loadDocuments({
      required String token,
      required int userId,
      int page = 1,
      bool forceRefresh = false,
    }) async {
      // Keep showing cached docs while fetching; show spinner only if nothing to show
      _isLoading = _documents.isEmpty;
      _error = null;
      notifyListeners();
    
      // Always load from cache first to avoid blank UI during refresh
      await _loadFromCache(userId: userId);
      _isLoading = _documents.isEmpty; // if cache had data, keep showing it
      notifyListeners();
    
      try {
        final response = await _apiService.getDocuments(
          token: token,
          page: page,
        );
      
        if (response['success'] == true) {
          _documents.clear();
          final documentsData = response['data']['documents'] as List? ?? 
                               response['data'] as List? ?? 
                               [];
          for (var docData in documentsData) {
            _documents.add(DocumentModel.fromJson(docData));
          }
        
          // Save to cache after fetching from API
          await _saveToCache(userId: userId);
          print('📡 Documents synced from API and cached');
      } else {
        _error = response['message'] ?? 'Erreur lors du chargement des documents';
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Error loading documents: $e');
      // Keep cache version if API fails
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
  
  // Upload Document
  Future<bool> uploadDocument({
      required String token,
      required File file,
      required int userId,
      String? title,
    }) async {
      _isUploading = true;
      _error = null;
      notifyListeners();
    
      print('📤 ===== UPLOAD DEBUG START =====');
      print('📁 File: ${file.path}');
      print('📊 Size: ${file.lengthSync()} bytes');
      print('🔑 Token: ${token.substring(0, 30)}...');
    
      try {
        print('🚀 Calling uploadDocument API...');
        final response = await _apiService.uploadDocument(
          token: token,
          file: file,
          title: title,
        );
      
        print('📥 Response received: $response');
      
        if (response['success'] == true) {
          // Add new document to the list
          final documentData = response['data']['document'] ?? response['data'];
          final newDoc = DocumentModel.fromJson(documentData);
          _documents.insert(0, newDoc);
        
          // Save to cache immediately
          await _saveToCache(userId: userId);
        print('✅ 📤 Document uploaded and cached: ${newDoc.name}');
        
        _isUploading = false;
        notifyListeners();
        return true;
      } else {
        final errorMsg = response['message'] ?? 
                        response['error'] ?? 
                        response['debug'] ??
                        'Erreur inconnue lors de l\'upload';
        _error = '❌ ERREUR: $errorMsg\n📋 Réponse complète: ${response.toString()}';
        print('❌ Upload failed: $_error');
        _isUploading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = '❌ EXCEPTION: ${e.toString()}\n📋 Type: ${e.runtimeType}\n⏰ ${DateTime.now()}';
      print('❌ ===== ERROR UPLOADING DOCUMENT =====');
      print('Error: $e');
      print('Type: ${e.runtimeType}');
      print('Stack: $e');
      print('❌ ===== END ERROR =====');
      _isUploading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Delete Document
  Future<bool> deleteDocument({
    required String token,
    required int documentId,
    required int userId,
  }) async {
    _error = null;
    
    try {
      final response = await _apiService.deleteDocument(
        token: token,
        documentId: documentId,
      );
      
      if (response['success'] == true) {
        _documents.removeWhere((doc) => doc.id == documentId);
        
        // Update cache
        await _saveToCache(userId: userId);
        print('🗑️ Document deleted and cache updated');
        
        notifyListeners();
        return true;
      } else {
        _error = response['message'] ?? 'Erreur lors de la suppression';
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Error deleting document: $e');
      notifyListeners();
      return false;
    }
  }
  
  /// Clear all documents and cache (use when user logs out)
  Future<void> clearCache() async {
    try {
      _documents.clear();
      final box = Hive.box<Map>(_documentsBoxName);
      await box.clear();
      print('🧹 Document cache cleared');
      notifyListeners();
    } catch (e) {
      print('❌ Error clearing cache: $e');
    }
  }
  
  // Clear Error
  void clearError() {
    _error = null;
    notifyListeners();
  }
  
  // Clear Documents
  void clearDocuments() {
    _documents.clear();
    notifyListeners();
  }
}
