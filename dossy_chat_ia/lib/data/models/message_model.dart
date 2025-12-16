class MessageModel {
  final int? id;
  final String content;
  final bool isUser;
  final DateTime timestamp;
  final List<String>? sources;
  final Map<String, dynamic>? metadata;
  final bool? isAnonymized;
  final String? audioUrl;
  
  MessageModel({
    this.id,
    required this.content,
    required this.isUser,
    required this.timestamp,
    this.sources,
    this.metadata,
    this.isAnonymized,
    this.audioUrl,
  });
  
  factory MessageModel.fromJson(Map<String, dynamic> json) {
    return MessageModel(
      id: json['id'],
      content: json['content'] ?? json['message'] ?? '',
      isUser: json['is_user'] ?? json['role'] == 'user',
      timestamp: json['timestamp'] != null
          ? DateTime.parse(json['timestamp'])
          : json['created_at'] != null
              ? DateTime.parse(json['created_at'])
              : DateTime.now(),
      sources: json['sources'] != null
          ? List<String>.from(json['sources'])
          : null,
      metadata: json['metadata'],
      isAnonymized: json['is_anonymized'],
      audioUrl: json['audio_url'],
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
    };
  }
  
  MessageModel copyWith({
    int? id,
    String? content,
    bool? isUser,
    DateTime? timestamp,
    List<String>? sources,
    Map<String, dynamic>? metadata,
    bool? isAnonymized,
    String? audioUrl,
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
    );
  }
}
