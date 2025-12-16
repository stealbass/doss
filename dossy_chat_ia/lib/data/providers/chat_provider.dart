import 'package:flutter/material.dart';
import '../models/message_model.dart';
import '../services/api_service.dart';

class ChatProvider with ChangeNotifier {
  final List<MessageModel> _messages = [];
  bool _isLoading = false;
  bool _isTyping = false;
  String? _error;
  
  List<MessageModel> get messages => _messages;
  bool get isLoading => _isLoading;
  bool get isTyping => _isTyping;
  String? get error => _error;
  
  final ApiService _apiService = ApiService();
  
  // Send Message
  Future<void> sendMessage({
    required String message,
    required String token,
    bool useSimpleRag = false,
    bool useAdvancedRag = false,
    List<int>? documentIds,
    bool enableAnonymization = false,
  }) async {
    // Add user message
    final userMessage = MessageModel(
      content: message,
      isUser: true,
      timestamp: DateTime.now(),
    );
    _messages.add(userMessage);
    notifyListeners();
    
    _isTyping = true;
    _error = null;
    notifyListeners();
    
    try {
      final response = await _apiService.sendChatMessage(
        token: token,
        message: message,
        useSimpleRag: useSimpleRag,
        useAdvancedRag: useAdvancedRag,
        documentIds: documentIds,
        enableAnonymization: enableAnonymization,
      );
      
      if (response['success'] == true) {
        final aiMessage = MessageModel(
          content: response['data']['response'],
          isUser: false,
          timestamp: DateTime.now(),
          sources: response['data']['sources'] != null
              ? List<String>.from(response['data']['sources'])
              : null,
          metadata: response['data']['metadata'],
          isAnonymized: response['data']['is_anonymized'],
        );
        
        _messages.add(aiMessage);
      } else {
        _error = response['message'] ?? 'Erreur lors de l\'envoi du message';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isTyping = false;
      notifyListeners();
    }
  }
  
  // Load Chat History
  Future<void> loadChatHistory({
    required String token,
    int? conversationId,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      final response = await _apiService.getChatHistory(
        token: token,
        conversationId: conversationId,
      );
      
      if (response['success'] == true) {
        _messages.clear();
        final messagesData = response['data']['messages'] as List;
        for (var messageData in messagesData) {
          _messages.add(MessageModel.fromJson(messageData));
        }
      } else {
        _error = response['message'] ?? 'Erreur lors du chargement de l\'historique';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
  
  // Clear Chat
  void clearChat() {
    _messages.clear();
    _error = null;
    notifyListeners();
  }
  
  // Delete Message
  void deleteMessage(int index) {
    if (index >= 0 && index < _messages.length) {
      _messages.removeAt(index);
      notifyListeners();
    }
  }
  
  // Clear Error
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
