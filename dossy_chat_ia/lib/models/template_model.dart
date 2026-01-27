class Template {
  final int id;
  final String title;
  final String type;
  final String category;
  final String requiredPlan;
  final int viewsCount;
  final int downloadsCount;
  final String fileType;
  final String description;
  final String usageInstructions;
  final List<String> tags;
  final List<String> countries;
  final String formSelectIcon;

  Template({
    required this.id,
    required this.title,
    required this.type,
    required this.category,
    required this.requiredPlan,
    required this.viewsCount,
    required this.downloadsCount,
    required this.fileType,
    required this.description,
    required this.usageInstructions,
    required this.tags,
    required this.countries,
    required this.formSelectIcon,
  });

  factory Template.fromJson(Map<String, dynamic> json) {
    return Template(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      type: json['type'] ?? '',
      category: json['category'] ?? '',
      requiredPlan: json['required_plan'] ?? 'Gratuit',
      viewsCount: json['views_count'] ?? 0,
      downloadsCount: json['downloads_count'] ?? 0,
      fileType: json['file_type'] ?? '',
      description: json['description'] ?? '',
      usageInstructions: json['usage_instructions'] ?? '',
      tags: (json['tags'] as List?)?.map((e) => e.toString()).toList() ?? [],
      countries: (json['countries'] as List?)?.map((e) => e.toString()).toList() ?? [],
      formSelectIcon: json['form_select_icon'] ?? '',
    );
  }
}
