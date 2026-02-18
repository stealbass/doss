class MessageModel {
  final int? id;
  final String content;
  final bool isUser;
  final DateTime timestamp;
  final List<Map<String, dynamic>>? sources;  // Changed from List<String> to List<Map>
  final Map<String, dynamic>? metadata;
  final bool? isAnonymized;
  final String? audioUrl;
  final Map<String, dynamic>? generatedDocument;  // NEW: Generated document info
  final bool? isDocumentGeneration;  // NEW: Flag for document generation response
  final List<int>? selectedDocumentIds; // Document IDs used for this message
  
  MessageModel({
    this.id,
    required this.content,
    required this.isUser,
    required this.timestamp,
    this.sources,
    this.metadata,
    this.isAnonymized,
    this.audioUrl,
    this.generatedDocument,
    this.isDocumentGeneration,
    this.selectedDocumentIds,
  });
  
  factory MessageModel.fromJson(Map<String, dynamic> json) {
    // Parse sources - could be List<String> or List<Map>
    List<Map<String, dynamic>>? parsedSources;
    if (json['sources'] != null && json['sources'] is List) {
      final sourcesList = json['sources'] as List;
      parsedSources = sourcesList.map((source) {
        if (source is Map<String, dynamic>) {
          return source;
        } else if (source is String) {
          // Legacy string format
          return {'title': source, 'type': 'text'};
        }
        return {'title': 'Unknown', 'type': 'text'};
      }).toList();
    }
    
    return MessageModel(
      id: json['id'],
      content: json['content'] ?? json['message'] ?? '',
      isUser: json['is_user'] ?? json['role'] == 'user',
      timestamp: json['timestamp'] != null
          ? DateTime.parse(json['timestamp'])
          : json['created_at'] != null
              ? DateTime.parse(json['created_at'])
              : DateTime.now(),
      sources: parsedSources,
      metadata: json['metadata'],
      isAnonymized: json['is_anonymized'],
      audioUrl: json['audio_url'],
      generatedDocument: json['generated_document'],
      isDocumentGeneration: json['is_document_generation'] ?? false,
      selectedDocumentIds: (json['selected_document_ids'] is List)
          ? List<int>.from(json['selected_document_ids'])
          : null,
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'content': content,
      'is_user': isUser,
      'timestamp': timestamp.toIso8601String(),
      'sources': sources,
      'metadata': metadata,
      'is_anonymized': isAnonymized,
      'audio_url': audioUrl,
      'generated_document': generatedDocument,
      'is_document_generation': isDocumentGeneration,
      'selected_document_ids': selectedDocumentIds,
    };
  }
  
  MessageModel copyWith({
    int? id,
    String? content,
    bool? isUser,
    DateTime? timestamp,
    List<Map<String, dynamic>>? sources,
    Map<String, dynamic>? metadata,
    bool? isAnonymized,
    String? audioUrl,
    Map<String, dynamic>? generatedDocument,
    bool? isDocumentGeneration,
    List<int>? selectedDocumentIds,
  }) {
    return MessageModel(
      id: id ?? this.id,
      content: content ?? this.content,
      isUser: isUser ?? this.isUser,
      timestamp: timestamp ?? this.timestamp,
      sources: sources ?? this.sources,
      metadata: metadata ?? this.metadata,
      isAnonymized: isAnonymized ?? this.isAnonymized,
      audioUrl: audioUrl ?? this.audioUrl,
      generatedDocument: generatedDocument ?? this.generatedDocument,
      isDocumentGeneration: isDocumentGeneration ?? this.isDocumentGeneration,
      selectedDocumentIds: selectedDocumentIds ?? this.selectedDocumentIds,
    );
  }
}
