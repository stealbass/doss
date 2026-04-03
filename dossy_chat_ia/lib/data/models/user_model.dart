class UserModel {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? avatar;
  final String role; // student, lawyer, enterprise, admin
  final String plan; // Gratuit, Étudiant, Professionnel, Cabinet/Entreprise
  final String? jurisdiction; // Country code
  final DateTime? subscriptionEnd;
  final int searchesUsed;
  final int searchesLimit;
  final int analysesUsed;
  final int analysesLimit;
  final int downloadsUsed;
  final int downloadsLimit;
  final int referralCount;
  final String? referralCode;
  final int summariesGenerated; // New: fiches générées
  final int quizzesCreated; // New: QCM créés
  final int revisionSessions; // New: Sessions révision
  final String? address;
  final String? city;
  final DateTime createdAt;

  // Getter for compatibility
  String get fullName => name;

  UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.avatar,
    required this.role,
    required this.plan,
    this.jurisdiction,
    this.subscriptionEnd,
    required this.searchesUsed,
    required this.searchesLimit,
    required this.analysesUsed,
    required this.analysesLimit,
    required this.downloadsUsed,
    required this.downloadsLimit,
    required this.referralCount,
    this.referralCode,
    this.summariesGenerated = 0,
    this.quizzesCreated = 0,
    this.revisionSessions = 0,
    this.address,
    this.city,
    required this.createdAt,
  });

  // Helper function to parse int from dynamic (can be int or String)
  static int _parseInt(dynamic value, int defaultValue) {
    if (value == null) return defaultValue;
    if (value is int) return value;
    if (value is String) {
      return int.tryParse(value) ?? defaultValue;
    }
    return defaultValue;
  }

  // Helper to coerce any value to String
  static String? _parseString(dynamic value) {
    if (value == null) return null;
    return value.toString();
  }

  factory UserModel.fromJson(Map<String, dynamic> json) {
    final planValue = _parseString(json['plan']) ?? 'Gratuit';
    final parsedDownloadsLimit = _parseInt(json['downloads_limit'], 0);
    final planLower = planValue.toLowerCase();
    final effectiveDownloadsLimit = (planLower == 'free' || planLower == 'gratuit')
        ? (parsedDownloadsLimit > 0 ? parsedDownloadsLimit : 3)
        : parsedDownloadsLimit;

    return UserModel(
      id: _parseInt(json['id'], 0),
      name: _parseString(json['name']) ?? '',
      email: _parseString(json['email']) ?? '',
      phone: _parseString(json['phone']),
      avatar: _parseString(json['avatar']),
      role: _parseString(json['role']) ?? 'student',
      plan: planValue,
      jurisdiction: _parseString(json['jurisdiction']),
      address: _parseString(json['address']),
      city: _parseString(json['city']),
      subscriptionEnd: json['subscription_end'] != null
          ? DateTime.parse(json['subscription_end'])
          : null,
      searchesUsed: _parseInt(json['searches_used'], 0),
      searchesLimit: _parseInt(json['searches_limit'], 5),
      analysesUsed: _parseInt(json['analyses_used'], 0),
      analysesLimit: _parseInt(json['analyses_limit'], 2),
      downloadsUsed: _parseInt(json['downloads_used'], 0),
      downloadsLimit: effectiveDownloadsLimit,
      referralCount: _parseInt(json['referral_count'], 0),
      referralCode: _parseString(json['referral_code']),
      summariesGenerated: _parseInt(json['summaries_generated'], 0),
      quizzesCreated: _parseInt(json['quizzes_created'], 0),
      revisionSessions: _parseInt(json['revision_sessions'], 0),
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'avatar': avatar,
      'role': role,
      'plan': plan,
      'jurisdiction': jurisdiction,
      'address': address,
      'city': city,
      'subscription_end': subscriptionEnd?.toIso8601String(),
      'searches_used': searchesUsed,
      'searches_limit': searchesLimit,
      'analyses_used': analysesUsed,
      'analyses_limit': analysesLimit,
      'downloads_used': downloadsUsed,
      'downloads_limit': downloadsLimit,
      'referral_count': referralCount,
      'referral_code': referralCode,
      'summaries_generated': summariesGenerated,
      'quizzes_created': quizzesCreated,
      'revision_sessions': revisionSessions,
      'created_at': createdAt.toIso8601String(),
    };
  }

  bool get isSubscriptionActive {
    if (subscriptionEnd == null) return false;
    return subscriptionEnd!.isAfter(DateTime.now());
  }

  bool get canSearch {
    if (searchesLimit == -1) return true; // unlimited
    return searchesUsed < searchesLimit;
  }

  bool get canAnalyze {
    if (analysesLimit == -1) return true; // unlimited
    return analysesUsed < analysesLimit;
  }

  bool get canDownload {
    if (downloadsLimit == -1) return true; // unlimited
    return downloadsUsed < downloadsLimit;
  }

  bool get hasAudioTranscription {
    final p = plan.toLowerCase();
    return p.contains('étudiant') || p.contains('professionnel') || p.contains('cabinet') || p.contains('entreprise');
  }

  bool get hasAnonymization {
    final p = plan.toLowerCase();
    return p.contains('professionnel') || p.contains('cabinet') || p.contains('entreprise');
  }

  bool get hasLegalMonitoring {
    final p = plan.toLowerCase();
    return p.contains('professionnel') || p.contains('cabinet') || p.contains('entreprise');
  }

  bool get hasMultiAccounts {
    final p = plan.toLowerCase();
    return p.contains('cabinet') || p.contains('entreprise');
  }

  bool get hasLegalAlerts {
    final p = plan.toLowerCase();
    return p.contains('professionnel') || p.contains('cabinet') || p.contains('entreprise');
  }

  bool get hasWordExport {
    final p = plan.toLowerCase();
    return p.contains('professionnel') || p.contains('cabinet') || p.contains('entreprise');
  }

  UserModel copyWith({
    int? id,
    String? name,
    String? email,
    String? phone,
    String? avatar,
    String? role,
    String? plan,
    String? jurisdiction,
    DateTime? subscriptionEnd,
    int? searchesUsed,
    int? searchesLimit,
    int? analysesUsed,
    int? analysesLimit,
    int? downloadsUsed,
    int? downloadsLimit,
    int? referralCount,
    String? referralCode,
    DateTime? createdAt,
    String? address,
    String? city,
    int? summariesGenerated,
    int? quizzesCreated,
    int? revisionSessions,
  }) {
    return UserModel(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      avatar: avatar ?? this.avatar,
      role: role ?? this.role,
      plan: plan ?? this.plan,
      jurisdiction: jurisdiction ?? this.jurisdiction,
      address: address ?? this.address,
      city: city ?? this.city,
      subscriptionEnd: subscriptionEnd ?? this.subscriptionEnd,
      searchesUsed: searchesUsed ?? this.searchesUsed,
      searchesLimit: searchesLimit ?? this.searchesLimit,
      analysesUsed: analysesUsed ?? this.analysesUsed,
      analysesLimit: analysesLimit ?? this.analysesLimit,
      downloadsUsed: downloadsUsed ?? this.downloadsUsed,
      downloadsLimit: downloadsLimit ?? this.downloadsLimit,
      referralCount: referralCount ?? this.referralCount,
      referralCode: referralCode ?? this.referralCode,
      createdAt: createdAt ?? this.createdAt,
      summariesGenerated: summariesGenerated ?? this.summariesGenerated,
      quizzesCreated: quizzesCreated ?? this.quizzesCreated,
      revisionSessions: revisionSessions ?? this.revisionSessions,
    );
  }
}
