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
  final DateTime createdAt;
  
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
    required this.createdAt,
  });
  
  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      phone: json['phone'],
      avatar: json['avatar'],
      role: json['role'] ?? 'student',
      plan: json['plan'] ?? 'Gratuit',
      jurisdiction: json['jurisdiction'],
      subscriptionEnd: json['subscription_end'] != null
          ? DateTime.parse(json['subscription_end'])
          : null,
      searchesUsed: json['searches_used'] ?? 0,
      searchesLimit: json['searches_limit'] ?? 5,
      analysesUsed: json['analyses_used'] ?? 0,
      analysesLimit: json['analyses_limit'] ?? 2,
      downloadsUsed: json['downloads_used'] ?? 0,
      downloadsLimit: json['downloads_limit'] ?? 0,
      referralCount: json['referral_count'] ?? 0,
      referralCode: json['referral_code'],
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
      'subscription_end': subscriptionEnd?.toIso8601String(),
      'searches_used': searchesUsed,
      'searches_limit': searchesLimit,
      'analyses_used': analysesUsed,
      'analyses_limit': analysesLimit,
      'downloads_used': downloadsUsed,
      'downloads_limit': downloadsLimit,
      'referral_count': referralCount,
      'referral_code': referralCode,
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
    return ['Étudiant', 'Professionnel', 'Cabinet/Entreprise'].contains(plan);
  }
  
  bool get hasAnonymization {
    return ['Professionnel', 'Cabinet/Entreprise'].contains(plan);
  }
  
  bool get hasMultiAccounts {
    return plan == 'Cabinet/Entreprise';
  }
  
  bool get hasLegalAlerts {
    return ['Professionnel', 'Cabinet/Entreprise'].contains(plan);
  }
  
  bool get hasWordExport {
    return ['Professionnel', 'Cabinet/Entreprise'].contains(plan);
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
    );
  }
}
