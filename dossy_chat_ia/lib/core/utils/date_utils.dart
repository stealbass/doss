import 'package:intl/intl.dart';

/// Date formatting utilities for French locale
class DateUtils {
  /// Format date in French format (15 janvier 2025)
  static String formatDateFr(DateTime date) {
    final formatter = DateFormat('d MMMM yyyy', 'fr_FR');
    return formatter.format(date);
  }

  /// Format date with time (15 janv. 2025 à 14:30)
  static String formatDateTimeFr(DateTime date) {
    final formatter = DateFormat('d MMM yyyy \'à\' HH:mm', 'fr_FR');
    return formatter.format(date);
  }

  /// Format time only (14:30)
  static String formatTime(DateTime date) {
    final formatter = DateFormat('HH:mm');
    return formatter.format(date);
  }

  /// Format date short (15/01/2025)
  static String formatDateShort(DateTime date) {
    final formatter = DateFormat('dd/MM/yyyy');
    return formatter.format(date);
  }

  /// Get relative time (Il y a 2 heures, Hier, etc.)
  static String getRelativeTime(DateTime date) {
    final now = DateTime.now();
    final difference = now.difference(date);

    if (difference.inSeconds < 60) {
      return 'À l\'instant';
    } else if (difference.inMinutes < 60) {
      final minutes = difference.inMinutes;
      return 'Il y a $minutes minute${minutes > 1 ? 's' : ''}';
    } else if (difference.inHours < 24) {
      final hours = difference.inHours;
      return 'Il y a $hours heure${hours > 1 ? 's' : ''}';
    } else if (difference.inDays == 1) {
      return 'Hier';
    } else if (difference.inDays < 7) {
      final days = difference.inDays;
      return 'Il y a $days jour${days > 1 ? 's' : ''}';
    } else if (difference.inDays < 30) {
      final weeks = (difference.inDays / 7).floor();
      return 'Il y a $weeks semaine${weeks > 1 ? 's' : ''}';
    } else if (difference.inDays < 365) {
      final months = (difference.inDays / 30).floor();
      return 'Il y a $months mois';
    } else {
      final years = (difference.inDays / 365).floor();
      return 'Il y a $years an${years > 1 ? 's' : ''}';
    }
  }

  /// Check if date is today
  static bool isToday(DateTime date) {
    final now = DateTime.now();
    return date.year == now.year && 
           date.month == now.month && 
           date.day == now.day;
  }

  /// Check if date is yesterday
  static bool isYesterday(DateTime date) {
    final yesterday = DateTime.now().subtract(const Duration(days: 1));
    return date.year == yesterday.year && 
           date.month == yesterday.month && 
           date.day == yesterday.day;
  }

  /// Get day name in French
  static String getDayNameFr(DateTime date) {
    final formatter = DateFormat('EEEE', 'fr_FR');
    return formatter.format(date);
  }

  /// Get month name in French
  static String getMonthNameFr(DateTime date) {
    final formatter = DateFormat('MMMM', 'fr_FR');
    return formatter.format(date);
  }

  /// Calculate days until date
  static int daysUntil(DateTime date) {
    final now = DateTime.now();
    final difference = date.difference(now);
    return difference.inDays;
  }

  /// Format subscription expiry date
  static String formatExpiryDate(DateTime date) {
    final daysLeft = daysUntil(date);
    
    if (daysLeft < 0) {
      return 'Expiré';
    } else if (daysLeft == 0) {
      return 'Expire aujourd\'hui';
    } else if (daysLeft == 1) {
      return 'Expire demain';
    } else if (daysLeft < 7) {
      return 'Expire dans $daysLeft jours';
    } else {
      return 'Expire le ${formatDateFr(date)}';
    }
  }

  /// Parse ISO 8601 string safely
  static DateTime? parseISO(String? isoString) {
    if (isoString == null || isoString.isEmpty) return null;
    try {
      return DateTime.parse(isoString);
    } catch (e) {
      return null;
    }
  }

  /// Format duration (2h 30min)
  static String formatDuration(Duration duration) {
    final hours = duration.inHours;
    final minutes = duration.inMinutes.remainder(60);
    
    if (hours > 0) {
      return '${hours}h ${minutes}min';
    } else {
      return '${minutes}min';
    }
  }
}
