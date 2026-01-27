/// Model for search history entries
class SearchHistoryModel {
  final String id;
  final String userId;
  final String query;
  final String jurisdiction;
  final String category;
  final List<String> results;
  final int resultsCount;
  final DateTime timestamp;
  final Map<String, dynamic>? filters;

  SearchHistoryModel({
    required this.id,
    required this.userId,
    required this.query,
    required this.jurisdiction,
    required this.category,
    this.results = const [],
    this.resultsCount = 0,
    required this.timestamp,
    this.filters,
  });

  factory SearchHistoryModel.fromJson(Map<String, dynamic> json) {
    return SearchHistoryModel(
      id: json['id']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      query: json['query']?.toString() ?? '',
      jurisdiction: json['jurisdiction']?.toString() ?? 'CI',
      category: json['category']?.toString() ?? 'general',
      results: (json['results'] as List?)?.map((e) => e.toString()).toList() ?? [],
      resultsCount: json['results_count'] ?? 0,
      timestamp: DateTime.parse(json['timestamp'] ?? DateTime.now().toIso8601String()),
      filters: json['filters'] as Map<String, dynamic>?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'query': query,
      'jurisdiction': jurisdiction,
      'category': category,
      'results': results,
      'results_count': resultsCount,
      'timestamp': timestamp.toIso8601String(),
      'filters': filters,
    };
  }
}
