
/// Model for user subscription
class SubscriptionModel {
  final String id;
  final String userId;
  final String planId;
  final String planName;
  final double amount;
  final String currency;
  final DateTime startDate;
  final DateTime endDate;
  final String status; // active, expired, cancelled, pending
  final String? paymentMethod;
  final String? transactionId;
  
  // Quotas
  final int searchesLimit;
  final int searchesRemaining;
  final int analysesLimit;
  final int analysesRemaining;
  final int pdfsLimit;
  final int pdfsRemaining;
  
  // Features
  final bool hasAdvancedAI;
  final bool hasFullHistory;
  final bool hasAnonymization;
  final bool hasTranscription;
  final bool hasLegalMonitoring;
  
  // Metadata
  final DateTime createdAt;
  final DateTime? cancelledAt;
  final String? cancellationReason;

  SubscriptionModel({
    required this.id,
    required this.userId,
    required this.planId,
    required this.planName,
    required this.amount,
    this.currency = 'FCFA',
    required this.startDate,
    required this.endDate,
    required this.status,
    this.paymentMethod,
    this.transactionId,
    required this.searchesLimit,
    required this.searchesRemaining,
    required this.analysesLimit,
    required this.analysesRemaining,
    required this.pdfsLimit,
    required this.pdfsRemaining,
    this.hasAdvancedAI = false,
    this.hasFullHistory = false,
    this.hasAnonymization = false,
    this.hasTranscription = false,
    this.hasLegalMonitoring = false,
    required this.createdAt,
    this.cancelledAt,
    this.cancellationReason,
  });

  /// Create from JSON
  factory SubscriptionModel.fromJson(Map<String, dynamic> json) {
    return SubscriptionModel(
      id: json['id']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      planId: json['plan_id']?.toString() ?? '',
      planName: json['plan_name']?.toString() ?? 'Free',
      amount: (json['amount'] ?? 0.0).toDouble(),
      currency: json['currency']?.toString() ?? 'FCFA',
      startDate: DateTime.parse(json['start_date'] ?? DateTime.now().toIso8601String()),
      endDate: DateTime.parse(json['end_date'] ?? DateTime.now().add(const Duration(days: 30)).toIso8601String()),
      status: json['status']?.toString() ?? 'pending',
      paymentMethod: json['payment_method']?.toString(),
      transactionId: json['transaction_id']?.toString(),
      searchesLimit: json['searches_limit'] ?? 0,
      searchesRemaining: json['searches_remaining'] ?? 0,
      analysesLimit: json['analyses_limit'] ?? 0,
      analysesRemaining: json['analyses_remaining'] ?? 0,
      pdfsLimit: json['pdfs_limit'] ?? 0,
      pdfsRemaining: json['pdfs_remaining'] ?? 0,
      hasAdvancedAI: json['has_advanced_ai'] ?? false,
      hasFullHistory: json['has_full_history'] ?? false,
      hasAnonymization: json['has_anonymization'] ?? false,
      hasTranscription: json['has_transcription'] ?? false,
      hasLegalMonitoring: json['has_legal_monitoring'] ?? false,
      createdAt: DateTime.parse(json['created_at'] ?? DateTime.now().toIso8601String()),
      cancelledAt: json['cancelled_at'] != null ? DateTime.parse(json['cancelled_at']) : null,
      cancellationReason: json['cancellation_reason']?.toString(),
    );
  }

  /// Convert to JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'plan_id': planId,
      'plan_name': planName,
      'amount': amount,
      'currency': currency,
      'start_date': startDate.toIso8601String(),
      'end_date': endDate.toIso8601String(),
      'status': status,
      'payment_method': paymentMethod,
      'transaction_id': transactionId,
      'searches_limit': searchesLimit,
      'searches_remaining': searchesRemaining,
      'analyses_limit': analysesLimit,
      'analyses_remaining': analysesRemaining,
      'pdfs_limit': pdfsLimit,
      'pdfs_remaining': pdfsRemaining,
      'has_advanced_ai': hasAdvancedAI,
      'has_full_history': hasFullHistory,
      'has_anonymization': hasAnonymization,
      'has_transcription': hasTranscription,
      'has_legal_monitoring': hasLegalMonitoring,
      'created_at': createdAt.toIso8601String(),
      'cancelled_at': cancelledAt?.toIso8601String(),
      'cancellation_reason': cancellationReason,
    };
  }

  /// Check if subscription is active
  bool get isActive => status == 'active' && endDate.isAfter(DateTime.now());

  /// Check if subscription is expired
  bool get isExpired => endDate.isBefore(DateTime.now());

  /// Days remaining until expiration
  int get daysRemaining => endDate.difference(DateTime.now()).inDays;

  /// Check if user can perform a search
  bool get canSearch => searchesRemaining > 0 || searchesLimit == -1;

  /// Check if user can perform an analysis
  bool get canAnalyze => analysesRemaining > 0 || analysesLimit == -1;

  /// Check if user can download PDF
  bool get canDownloadPdf => pdfsRemaining > 0 || pdfsLimit == -1;

  /// Get search usage percentage
  double get searchUsagePercentage {
    if (searchesLimit == -1) return 0; // Unlimited
    if (searchesLimit == 0) return 1.0;
    return 1.0 - (searchesRemaining / searchesLimit);
  }

  /// Get analysis usage percentage
  double get analysisUsagePercentage {
    if (analysesLimit == -1) return 0; // Unlimited
    if (analysesLimit == 0) return 1.0;
    return 1.0 - (analysesRemaining / analysesLimit);
  }

  /// Copy with modifications
  SubscriptionModel copyWith({
    String? id,
    String? userId,
    String? planId,
    String? planName,
    double? amount,
    String? currency,
    DateTime? startDate,
    DateTime? endDate,
    String? status,
    String? paymentMethod,
    String? transactionId,
    int? searchesLimit,
    int? searchesRemaining,
    int? analysesLimit,
    int? analysesRemaining,
    int? pdfsLimit,
    int? pdfsRemaining,
    bool? hasAdvancedAI,
    bool? hasFullHistory,
    bool? hasAnonymization,
    bool? hasTranscription,
    bool? hasLegalMonitoring,
    DateTime? createdAt,
    DateTime? cancelledAt,
    String? cancellationReason,
  }) {
    return SubscriptionModel(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      planId: planId ?? this.planId,
      planName: planName ?? this.planName,
      amount: amount ?? this.amount,
      currency: currency ?? this.currency,
      startDate: startDate ?? this.startDate,
      endDate: endDate ?? this.endDate,
      status: status ?? this.status,
      paymentMethod: paymentMethod ?? this.paymentMethod,
      transactionId: transactionId ?? this.transactionId,
      searchesLimit: searchesLimit ?? this.searchesLimit,
      searchesRemaining: searchesRemaining ?? this.searchesRemaining,
      analysesLimit: analysesLimit ?? this.analysesLimit,
      analysesRemaining: analysesRemaining ?? this.analysesRemaining,
      pdfsLimit: pdfsLimit ?? this.pdfsLimit,
      pdfsRemaining: pdfsRemaining ?? this.pdfsRemaining,
      hasAdvancedAI: hasAdvancedAI ?? this.hasAdvancedAI,
      hasFullHistory: hasFullHistory ?? this.hasFullHistory,
      hasAnonymization: hasAnonymization ?? this.hasAnonymization,
      hasTranscription: hasTranscription ?? this.hasTranscription,
      hasLegalMonitoring: hasLegalMonitoring ?? this.hasLegalMonitoring,
      createdAt: createdAt ?? this.createdAt,
      cancelledAt: cancelledAt ?? this.cancelledAt,
      cancellationReason: cancellationReason ?? this.cancellationReason,
    );
  }

  @override
  String toString() {
    return 'SubscriptionModel(id: $id, plan: $planName, status: $status, '
        'expires: ${endDate.toIso8601String()}, '
        'searches: $searchesRemaining/$searchesLimit)';
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubscriptionModel &&
          runtimeType == other.runtimeType &&
          id == other.id &&
          userId == other.userId;

  @override
  int get hashCode => id.hashCode ^ userId.hashCode;
}
