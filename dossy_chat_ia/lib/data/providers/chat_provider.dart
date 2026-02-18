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
    List<String>? documentContents,
    bool enableAnonymization = false,
    bool autoAnonymizeDocuments = false,
  }) async {
    // Add user message
    final userMessage = MessageModel(
      content: message,
      isUser: true,
      timestamp: DateTime.now(),
      selectedDocumentIds: documentIds,
    );
    _messages.add(userMessage);
    notifyListeners();
    
    _isTyping = true;
    _error = null;
    notifyListeners();
    
    print('📨 ChatProvider.sendMessage called');
    print('  - Message: $message');
    print('  - documentIds: $documentIds');
    print('  - useSimpleRag: $useSimpleRag');
    print('  - useAdvancedRag: $useAdvancedRag');
    
    try {
      // Si autoAnonymizeDocuments = true et documents fournis
      List<String>? finalDocumentContents = documentContents;
      bool finalEnableAnonymization = enableAnonymization;
      
      if (autoAnonymizeDocuments && documentContents != null && documentContents.isNotEmpty) {
        // Anonymiser chaque document
        finalDocumentContents = [];
        for (String docContent in documentContents) {
          final anonymized = await _apiService.anonymizeDocument(docContent, token);
          finalDocumentContents.add(anonymized);
        }
        finalEnableAnonymization = true; // Forcer l'anonymisation
      }
      
      print('📡 Sending to API with documentIds: $documentIds');
      
      final response = await _apiService.sendChatMessage(
        token: token,
        message: message,
        useSimpleRag: useSimpleRag,
        useAdvancedRag: useAdvancedRag,
        documentIds: documentIds,
        documentContents: finalDocumentContents,
        enableAnonymization: finalEnableAnonymization,
      );
      
      print('=== ChatProvider: Response received ===');
      print('Success: ${response['success']}');
      print('Response keys: ${response.keys.toList()}');
      if (response['data'] != null) {
        print('Data keys: ${response['data'].keys.toList()}');
        print('Response field: ${response['data']['response']}');
      }
      
      if (response['success'] == true) {
        // Parse metadata safely - could be a list or map
        Map<String, dynamic>? metadata;
        final rawMetadata = response['data']['metadata'];
        if (rawMetadata is Map<String, dynamic>) {
          metadata = rawMetadata;
        } else {
          metadata = null;
        }
        
        // Parse sources safely - only include if non-empty
        List<Map<String, dynamic>>? sources;
        if (response['data']['sources'] != null && response['data']['sources'] is List) {
          final sourcesList = response['data']['sources'] as List;
          if (sourcesList.isNotEmpty) {
            sources = sourcesList.map((source) {
              if (source is Map<String, dynamic>) {
                return source;
              } else if (source is String) {
                return {'title': source, 'type': 'text'};
              }
              return {'title': 'Unknown', 'type': 'text'};
            }).toList();
          }
        }
        
        final aiMessage = MessageModel(
          content: response['data']['response'] ?? 'Erreur: pas de réponse',
          isUser: false,
          timestamp: DateTime.now(),
          sources: sources,
          metadata: metadata,
          isAnonymized: response['data']['is_anonymized'],
          generatedDocument: response['data']['generated_document'],
          isDocumentGeneration: response['data']['is_document_generation'] ?? false,
          selectedDocumentIds: documentIds,
        );
        
        print('Message créé: ${aiMessage.content}');
        print('Sources: ${sources?.length ?? 0}');
        _messages.add(aiMessage);
        print('Message ajouté. Total messages: ${_messages.length}');
        notifyListeners();  // Notifier immédiatement après ajout
      } else {
        _error = response['message'] ?? 'Erreur lors de l\'envoi du message';
      }
    } catch (e) {
      _error = e.toString();
      print('ERROR in sendMessage: $e');
      print('StackTrace: ${StackTrace.current}');
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
