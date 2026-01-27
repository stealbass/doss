class FiscalResource {
  final int id;
  final String title;
  final String? description;
  final String type;
  final String country;
  final int? year;
  final String? version;
  final String? fileType;
  final int viewsCount;
  final int downloadsCount;
  final String? effectiveDate;
  final String? expiryDate;
  final dynamic content;
  final List<String> legalReferences;
  final List<String> tags;

  FiscalResource({
    required this.id,
    required this.title,
    this.description,
    required this.type,
    required this.country,
    this.year,
    this.version,
    this.fileType,
    required this.viewsCount,
    required this.downloadsCount,
    this.effectiveDate,
    this.expiryDate,
    required this.content,
    required this.legalReferences,
    required this.tags,
  });

  factory FiscalResource.fromJson(Map<String, dynamic> json) {
    return FiscalResource(
      id: json['id'] ?? 0,
      title: json['title'] ?? 'Ressource sans titre',
      description: json['description'],
      type: json['type'] ?? json['resource_type'] ?? '',
      country: json['country'] ?? '',
      year: json['year'] != null ? int.tryParse(json['year'].toString()) : null,
      version: json['version']?.toString(),
      fileType: json['file_type']?.toString(),
      viewsCount: json['views_count'] ?? 0,
      downloadsCount: json['downloads_count'] ?? 0,
      effectiveDate: json['effective_date']?.toString(),
      expiryDate: json['expiry_date']?.toString(),
      content: json['content'],
      legalReferences: (json['legal_references'] as List?)?.map((e) => e.toString()).toList() ?? [],
      tags: (json['tags'] as List?)?.map((e) => e.toString()).toList() ?? [],
    );
  }
}
