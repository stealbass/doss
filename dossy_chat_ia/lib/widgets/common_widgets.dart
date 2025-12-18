import 'package:flutter/material.dart';

/// Country Flag Widget - Display country flag emoji
class CountryFlag extends StatelessWidget {
  final String countryCode;
  final double size;

  const CountryFlag({
    Key? key,
    required this.countryCode,
    this.size = 24.0,
  }) : super(key: key);

  String _getFlag(String code) {
    const flags = {
      'Bénin': '🇧🇯',
      'Burkina Faso': '🇧🇫',
      'Cameroun': '🇨🇲',
      'Côte d\'Ivoire': '🇨🇮',
      'RD Congo': '🇨🇩',
      'Gabon': '🇬🇦',
      'Guinée-Bissau': '🇬🇼',
      'Madagascar': '🇲🇬',
      'Mali': '🇲🇱',
      'Maroc': '🇲🇦',
      'Niger': '🇳🇪',
      'Sénégal': '🇸🇳',
      'Togo': '🇹🇬',
      'Tunisie': '🇹🇳',
    };
    return flags[code] ?? '🌍';
  }

  @override
  Widget build(BuildContext context) {
    return Text(
      _getFlag(countryCode),
      style: TextStyle(fontSize: size),
    );
  }
}

/// Plan Badge Widget - Display subscription plan badge
class PlanBadge extends StatelessWidget {
  final String planName;
  final bool small;

  const PlanBadge({
    Key? key,
    required this.planName,
    this.small = false,
  }) : super(key: key);

  Color _getPlanColor(String plan) {
    switch (plan) {
      case 'Gratuit':
        return Colors.grey;
      case 'Étudiant':
        return Colors.blue;
      case 'Professionnel':
        return Colors.orange;
      case 'Cabinet/Entreprise':
        return Colors.purple;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: small ? 8 : 12,
        vertical: small ? 4 : 6,
      ),
      decoration: BoxDecoration(
        color: _getPlanColor(planName).withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: _getPlanColor(planName),
          width: 1,
        ),
      ),
      child: Text(
        planName,
        style: TextStyle(
          color: _getPlanColor(planName),
          fontSize: small ? 10 : 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

/// Template Card Widget
class TemplateCard extends StatelessWidget {
  final String title;
  final String? description;
  final String categoryName;
  final String fileType;
  final int downloads;
  final VoidCallback onTap;
  final VoidCallback? onDownload;

  const TemplateCard({
    Key? key,
    required this.title,
    this.description,
    required this.categoryName,
    required this.fileType,
    required this.downloads,
    required this.onTap,
    this.onDownload,
  }) : super(key: key);

  IconData _getFileIcon(String type) {
    switch (type.toLowerCase()) {
      case 'docx':
      case 'doc':
        return Icons.description;
      case 'pdf':
        return Icons.picture_as_pdf;
      case 'xlsx':
      case 'xls':
        return Icons.table_chart;
      default:
        return Icons.insert_drive_file;
    }
  }

  Color _getFileColor(String type) {
    switch (type.toLowerCase()) {
      case 'docx':
      case 'doc':
        return Colors.blue;
      case 'pdf':
        return Colors.red;
      case 'xlsx':
      case 'xls':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // File Icon
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: _getFileColor(fileType).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  _getFileIcon(fileType),
                  color: _getFileColor(fileType),
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (description != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        description!,
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey[600],
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.grey[200],
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            categoryName,
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[700],
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Icon(Icons.download, size: 14, color: Colors.grey[600]),
                        const SizedBox(width: 4),
                        Text(
                          '$downloads',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              // Download Button
              if (onDownload != null)
                IconButton(
                  onPressed: onDownload,
                  icon: Icon(
                    Icons.download_rounded,
                    color: Theme.of(context).primaryColor,
                  ),
                  tooltip: 'Télécharger',
                ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Calculator Card Widget
class CalculatorCard extends StatelessWidget {
  final String name;
  final String description;
  final String calculatorType;
  final VoidCallback onTap;

  const CalculatorCard({
    Key? key,
    required this.name,
    required this.description,
    required this.calculatorType,
    required this.onTap,
  }) : super(key: key);

  IconData _getCalculatorIcon(String type) {
    switch (type) {
      case 'hiring_cost':
        return Icons.person_add;
      case 'severance_pay':
        return Icons.payments;
      case 'net_salary':
        return Icons.calculate;
      case 'taxes':
        return Icons.account_balance;
      case 'social_charges':
        return Icons.people;
      case 'leave_indemnity':
        return Icons.beach_access;
      case 'overtime':
        return Icons.access_time;
      default:
        return Icons.calculate;
    }
  }

  Color _getCalculatorColor(String type) {
    switch (type) {
      case 'hiring_cost':
        return Colors.blue;
      case 'severance_pay':
        return Colors.orange;
      case 'net_salary':
        return Colors.green;
      case 'taxes':
        return Colors.red;
      case 'social_charges':
        return Colors.purple;
      case 'leave_indemnity':
        return Colors.teal;
      case 'overtime':
        return Colors.indigo;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final color = _getCalculatorColor(calculatorType);
    
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  _getCalculatorIcon(calculatorType),
                  color: color,
                  size: 28,
                ),
              ),
              const SizedBox(height: 16),
              Text(
                name,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 8),
              Text(
                description,
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.grey[600],
                ),
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Stat Card Widget
class StatCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color? color;

  const StatCard({
    Key? key,
    required this.label,
    required this.value,
    required this.icon,
    this.color,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final cardColor = color ?? Theme.of(context).primaryColor;

    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: cardColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(
                icon,
                color: cardColor,
                size: 24,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.grey[600],
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    value,
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Sub Account Card Widget
class SubAccountCard extends StatelessWidget {
  final String name;
  final String email;
  final String role;
  final bool isActive;
  final VoidCallback? onTap;
  final VoidCallback? onToggle;
  final VoidCallback? onDelete;

  const SubAccountCard({
    Key? key,
    required this.name,
    required this.email,
    required this.role,
    required this.isActive,
    this.onTap,
    this.onToggle,
    this.onDelete,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              CircleAvatar(
                radius: 24,
                backgroundColor: isActive ? Colors.green[100] : Colors.grey[300],
                child: Text(
                  name[0].toUpperCase(),
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: isActive ? Colors.green[700] : Colors.grey[700],
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      name,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      email,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[600],
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      role,
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[500],
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: isActive ? Colors.green[50] : Colors.red[50],
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      isActive ? 'Actif' : 'Inactif',
                      style: TextStyle(
                        fontSize: 11,
                        color: isActive ? Colors.green[700] : Colors.red[700],
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  if (onToggle != null || onDelete != null)
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (onToggle != null)
                          IconButton(
                            onPressed: onToggle,
                            icon: Icon(
                              isActive ? Icons.pause_circle : Icons.play_circle,
                              size: 20,
                            ),
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                        if (onDelete != null)
                          IconButton(
                            onPressed: onDelete,
                            icon: const Icon(
                              Icons.delete,
                              size: 20,
                              color: Colors.red,
                            ),
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                      ],
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Alert Card Widget
class AlertCard extends StatelessWidget {
  final String title;
  final String content;
  final String priority;
  final bool isRead;
  final DateTime createdAt;
  final VoidCallback onTap;

  const AlertCard({
    Key? key,
    required this.title,
    required this.content,
    required this.priority,
    required this.isRead,
    required this.createdAt,
    required this.onTap,
  }) : super(key: key);

  String _getPriorityEmoji(String priority) {
    const emojis = {
      'urgent': '🚨',
      'high': '🔴',
      'medium': '🟡',
      'low': '🟢',
    };
    return emojis[priority] ?? '📌';
  }

  Color _getPriorityColor(String priority) {
    switch (priority) {
      case 'urgent':
        return Colors.red;
      case 'high':
        return Colors.orange;
      case 'medium':
        return Colors.yellow[700]!;
      case 'low':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final difference = now.difference(date);

    if (difference.inDays == 0) {
      return 'Aujourd\'hui';
    } else if (difference.inDays == 1) {
      return 'Hier';
    } else if (difference.inDays < 7) {
      return 'Il y a ${difference.inDays} jours';
    } else {
      return '${date.day}/${date.month}/${date.year}';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: isRead ? 1 : 3,
      margin: const EdgeInsets.only(bottom: 12),
      color: isRead ? null : Colors.blue[50],
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: isRead
            ? BorderSide.none
            : BorderSide(color: Colors.blue[200]!, width: 1),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Priority Emoji
              Text(
                _getPriorityEmoji(priority),
                style: const TextStyle(fontSize: 24),
              ),
              const SizedBox(width: 12),
              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            title,
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: isRead ? FontWeight.w500 : FontWeight.bold,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (!isRead)
                          Container(
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: Colors.blue,
                              shape: BoxShape.circle,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      content,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[600],
                      ),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: _getPriorityColor(priority).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            priority.toUpperCase(),
                            style: TextStyle(
                              fontSize: 10,
                              color: _getPriorityColor(priority),
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _formatDate(createdAt),
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
