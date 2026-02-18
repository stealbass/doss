class DocumentModel {
  final int id;
  final String name;
  final String? description;
  final String? category;
  final String fileUrl;
  final String fileType;
  final int fileSize; // in bytes
  final DateTime uploadedAt;
  final bool isProcessed;
  final String? processingStatus;
  
  // Propriétés supplémentaires pour search/legal documents
  final String? jurisdiction;
  final String? summary;
  final DateTime? publishedAt;
  final DateTime? createdAt;
  final String? language;
  
  // Statistiques d'utilisation
  final int viewsCount;
  final int downloadsCount;

  DocumentModel({
    required this.id,
    required this.name,
    this.description,
    this.category,
    required this.fileUrl,
    required this.fileType,
    required this.fileSize,
    required this.uploadedAt,
    this.isProcessed = false,
    this.processingStatus,
    this.jurisdiction,
    this.summary,
    this.publishedAt,
    this.createdAt,
    this.language,
    this.viewsCount = 0,
    this.downloadsCount = 0,
  });
  
  // Alias pour compatibilité avec search widgets
  String get title => name;
  String get type => fileType;

  factory DocumentModel.fromJson(Map<String, dynamic> json) {
    // Extract file extension from filename if present
    String fileType = 'pdf'; // default
    
    if (json['file_type'] != null) {
      fileType = json['file_type'];
    } else if (json['type'] != null) {
      fileType = json['type'];
    } else if (json['mime_type'] != null) {
      // Extract from mime type (e.g., application/pdf -> pdf)
      final mimeType = json['mime_type'] as String;
      if (mimeType.contains('pdf')) {
        fileType = 'pdf';
      } else if (mimeType.contains('word') || mimeType.contains('document')) fileType = 'docx';
      else if (mimeType.contains('spreadsheet') || mimeType.contains('sheet')) fileType = 'xlsx';
      else if (mimeType.contains('image')) fileType = 'image';
      else fileType = mimeType.split('/').last;
    } else if (json['file_name'] != null) {
      // Try to extract from filename
      final parts = (json['file_name'] as String).split('.');
      if (parts.length > 1) {
        fileType = parts.last;
      }
    }
    
    // Safe parsing for viewsCount
    int viewsCount = 0;
    if (json['views_count'] != null) {
      if (json['views_count'] is int) {
        viewsCount = json['views_count'];
      } else {
        viewsCount = int.tryParse(json['views_count'].toString()) ?? 0;
      }
    }

    // Safe parsing for downloadsCount
    int downloadsCount = 0;
    if (json['downloads_count'] != null) {
      if (json['downloads_count'] is int) {
        downloadsCount = json['downloads_count'];
      } else {
        downloadsCount = int.tryParse(json['downloads_count'].toString()) ?? 0;
      }
    }
    
    return DocumentModel(
      id: (json['id'] is String) ? int.tryParse(json['id']) ?? 0 : json['id'] ?? 0,
      name: json['name'] ?? json['file_name'] ?? json['title'] ?? 'Document sans titre',
      description: json['description'],
      category: json['category'],
      fileUrl: json['file_url'] ?? json['url'] ?? '',
      fileType: fileType,
      fileSize: (json['file_size'] is String) ? int.tryParse(json['file_size']) ?? 0 : json['file_size'] ?? 0,
      uploadedAt: json['uploaded_at'] != null
          ? DateTime.parse(json['uploaded_at'])
          : json['created_at'] != null
              ? DateTime.parse(json['created_at'])
              : DateTime.now(),
      isProcessed: json['is_processed'] ?? false,
      processingStatus: json['processing_status'],
      jurisdiction: json['jurisdiction'],
      summary: json['summary'] ?? json['excerpt'],
      publishedAt: json['published_at'] != null
          ? DateTime.parse(json['published_at'])
          : null,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
      language: json['language'] ?? json['lang'],
      viewsCount: viewsCount,
      downloadsCount: downloadsCount,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'category': category,
      'file_url': fileUrl,
      'file_type': fileType,
      'file_size': fileSize,
      'uploaded_at': uploadedAt.toIso8601String(),
      'is_processed': isProcessed,
      'processing_status': processingStatus,
      'jurisdiction': jurisdiction,
      'summary': summary,
      'published_at': publishedAt?.toIso8601String(),
      'created_at': createdAt?.toIso8601String(),
      'language': language,
      'views_count': viewsCount,
      'downloads_count': downloadsCount,
    };
  }

  String get fileSizeFormatted {
    if (fileSize < 1024) {
      return '$fileSize B';
    } else if (fileSize < 1024 * 1024) {
      return '${(fileSize / 1024).toStringAsFixed(1)} KB';
    } else {
      return '${(fileSize / (1024 * 1024)).toStringAsFixed(1)} MB';
    }
  }

  String get fileExtension {
    return fileType.toUpperCase();
  }

  DocumentModel copyWith({
    int? id,
    String? name,
    String? description,
    String? category,
    String? fileUrl,
    String? fileType,
    int? fileSize,
    DateTime? uploadedAt,
    bool? isProcessed,
    String? processingStatus,
    String? jurisdiction,
    String? summary,
    DateTime? publishedAt,
    DateTime? createdAt,
    String? language,
    int? viewsCount,
    int? downloadsCount,
  }) {
    return DocumentModel(
      id: id ?? this.id,
      name: name ?? this.name,
      description: description ?? this.description,
      category: category ?? this.category,
      fileUrl: fileUrl ?? this.fileUrl,
      fileType: fileType ?? this.fileType,
      fileSize: fileSize ?? this.fileSize,
      uploadedAt: uploadedAt ?? this.uploadedAt,
      isProcessed: isProcessed ?? this.isProcessed,
      processingStatus: processingStatus ?? this.processingStatus,
      jurisdiction: jurisdiction ?? this.jurisdiction,
      summary: summary ?? this.summary,
      publishedAt: publishedAt ?? this.publishedAt,
      createdAt: createdAt ?? this.createdAt,
      language: language ?? this.language,
      viewsCount: viewsCount ?? this.viewsCount,
      downloadsCount: downloadsCount ?? this.downloadsCount,
    );
  }
}