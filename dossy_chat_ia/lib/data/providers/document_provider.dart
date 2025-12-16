import 'package:flutter/material.dart';
import 'dart:io';
import '../models/document_model.dart';
import '../services/api_service.dart';

class DocumentProvider with ChangeNotifier {
  final List<DocumentModel> _documents = [];
  bool _isLoading = false;
  bool _isUploading = false;
  String? _error;
  String? _selectedCategory;
  
  List<DocumentModel> get documents => _documents;
  bool get isLoading => _isLoading;
  bool get isUploading => _isUploading;
  String? get error => _error;
  String? get selectedCategory => _selectedCategory;
  
  List<DocumentModel> get filteredDocuments {
    if (_selectedCategory == null) return _documents;
    return _documents.where((doc) => doc.category == _selectedCategory).toList();
  }
  
  final ApiService _apiService = ApiService();
  
  // Load Documents
  Future<void> loadDocuments({
    required String token,
    String? category,
    int page = 1,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      final response = await _apiService.getDocuments(
        token: token,
        category: category,
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
      } else {
        _error = response['message'] ?? 'Erreur lors du chargement des documents';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
  
  // Upload Document
  Future<bool> uploadDocument({
    required String token,
    required File file,
    String? category,
    String? description,
  }) async {
    _isUploading = true;
    _error = null;
    notifyListeners();
    
    try {
      final response = await _apiService.uploadDocument(
        token: token,
        file: file,
        category: category,
        description: description,
      );
      
      if (response['success'] == true) {
        // Add new document to the list
        final newDoc = DocumentModel.fromJson(response['data']);
        _documents.insert(0, newDoc);
        
        _isUploading = false;
        notifyListeners();
        return true;
      } else {
        _error = response['message'] ?? 'Erreur lors de l\'upload';
        _isUploading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _isUploading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Delete Document
  Future<bool> deleteDocument({
    required String token,
    required int documentId,
  }) async {
    _error = null;
    
    try {
      final response = await _apiService.deleteDocument(
        token: token,
        documentId: documentId,
      );
      
      if (response['success'] == true) {
        _documents.removeWhere((doc) => doc.id == documentId);
        notifyListeners();
        return true;
      } else {
        _error = response['message'] ?? 'Erreur lors de la suppression';
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      notifyListeners();
      return false;
    }
  }
  
  // Set Category Filter
  void setCategory(String? category) {
    _selectedCategory = category;
    notifyListeners();
  }
  
  // Clear Error
  void clearError() {
    _error = null;
    notifyListeners();
  }
  
  // Clear Documents
  void clearDocuments() {
    _documents.clear();
    _selectedCategory = null;
    notifyListeners();
  }
}
