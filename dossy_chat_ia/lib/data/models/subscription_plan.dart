class SubscriptionPlan {
  final String id;
  final String name;
  final String? nameFr;
  final double price;
  final double? priceYearly;
  final String description;
  final String currency;
  final String duration;
  final List<String> features;
  final Map<String, dynamic> limits;
  final String? aiModel;
  final int? maxTokens;
  
  SubscriptionPlan({
    required this.id,
    required this.name,
    this.nameFr,
    required this.price,
    this.priceYearly,
    this.description = '',
    required this.currency,
    required this.duration,
    required this.features,
    required this.limits,
    this.aiModel,
    this.maxTokens,
  });
  
  factory SubscriptionPlan.fromJson(Map<String, dynamic> json) {
    // Parse features - handle both List and Map formats
    List<String> parsedFeatures = [];
    if (json['features'] != null) {
      if (json['features'] is List) {
        parsedFeatures = List<String>.from(json['features']);
      } else if (json['features'] is Map) {
        // Convert Map to descriptive list
        final featuresMap = json['features'] as Map<String, dynamic>;
        parsedFeatures = featuresMap.entries
            .where((e) => e.value != null && e.value != false)
            .map((e) => '${e.key}: ${e.value}')
            .toList();
      }
    }
    
    return SubscriptionPlan(
      id: json['id']?.toString() ?? '',
      name: json['name'] ?? '',
      nameFr: json['name_fr'] ?? json['name'],
      price: (json['price'] ?? json['price_monthly'] ?? 0).toDouble(),
      priceYearly: json['price_yearly'] != null ? (json['price_yearly']).toDouble() : null,
      description: json['description'] ?? '',
      currency: json['currency'] ?? 'XAF',
      duration: json['duration'] ?? 'monthly',
      features: parsedFeatures,
      limits: json['limits'] != null
          ? Map<String, dynamic>.from(json['limits'])
          : {},
      aiModel: json['ai_model'],
      maxTokens: json['max_tokens'],
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'name_fr': nameFr,
      'price': price,
      'price_yearly': priceYearly,
      'description': description,
      'currency': currency,
      'duration': duration,
      'features': features,
      'limits': limits,
      'ai_model': aiModel,
      'max_tokens': maxTokens,
    };
  }
}
