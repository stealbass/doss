import 'dart:io';
import 'dart:async';
import 'dart:convert';
import 'dart:math';
import 'package:connectivity_plus/connectivity_plus.dart';

/// Helper class for network connectivity checks
class NetworkHelper {
  static final NetworkHelper _instance = NetworkHelper._internal();
  factory NetworkHelper() => _instance;
  NetworkHelper._internal();

  final Connectivity _connectivity = Connectivity();

  /// Check if device has internet connection
  Future<bool> hasInternetConnection() async {
    try {
      final connectivityResult = await _connectivity.checkConnectivity();
      
      if (connectivityResult == ConnectivityResult.none) {
        return false;
      }

      // Try to ping a reliable server
      try {
        final result = await InternetAddress.lookup('google.com')
            .timeout(const Duration(seconds: 5));
        return result.isNotEmpty && result[0].rawAddress.isNotEmpty;
      } catch (_) {
        return false;
      }
    } catch (e) {
      return false;
    }
  }

  /// Check if a specific host is reachable
  Future<bool> canReachHost(String host) async {
    try {
      final result = await InternetAddress.lookup(host)
          .timeout(const Duration(seconds: 5));
      return result.isNotEmpty && result[0].rawAddress.isNotEmpty;
    } catch (e) {
      return false;
    }
  }

  /// Stream to listen for connectivity changes
  Stream<ConnectivityResult> get onConnectivityChanged =>
      _connectivity.onConnectivityChanged;
}

/// Helper methods for API error handling
class ApiHelpers {
  /// Check if there's internet connection before making API calls
  static Future<bool> hasInternetConnection() async {
    return await NetworkHelper().hasInternetConnection();
  }

  /// Parse API error response and return user-friendly message
  static String parseApiError(dynamic error) {
    if (error is TimeoutException) {
      return 'La requête a expiré. Vérifiez votre connexion internet.';
    } else if (error is SocketException) {
      return 'Pas de connexion internet. Vérifiez votre réseau.';
    } else if (error is String) {
      if (error.contains('401') || error.contains('Unauthorized')) {
        return 'Session expirée. Veuillez vous reconnecter.';
      } else if (error.contains('404') || error.contains('Not Found')) {
        return 'Service non disponible. Veuillez réessayer plus tard.';
      } else if (error.contains('500') || error.contains('Server Error')) {
        return 'Erreur serveur. Veuillez réessayer plus tard.';
      }
      return error;
    }
    return 'Une erreur est survenue. Veuillez réessayer.';
  }

  /// Validate email format
  static bool isValidEmail(String email) {
    final emailRegex = RegExp(
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
    );
    return emailRegex.hasMatch(email);
  }

  /// Validate phone format (international)
  static bool isValidPhone(String phone) {
    // Remove spaces, dashes, parentheses
    final cleanPhone = phone.replaceAll(RegExp(r'[\s\-\(\)]'), '');
    // Check if it contains only digits and + at the start
    final phoneRegex = RegExp(r'^\+?[0-9]{8,15}$');
    return phoneRegex.hasMatch(cleanPhone);
  }

  /// Validate password strength (basic)
  static bool isValidPassword(String password) {
    // At least 8 chars, with letters and numbers (and allows special chars)
    final passRegex = RegExp(r'^(?=.{8,})(?=.*[A-Za-z])(?=.*\d).*$');
    return passRegex.hasMatch(password);
  }

  /// Format file size in human readable form
  static String formatFileSize(int bytes) {
    if (bytes < 1024) return '$bytes B';
    final kb = bytes / 1024;
    if (kb < 1024) return '${kb.toStringAsFixed(2)} KB';
    final mb = kb / 1024;
    if (mb < 1024) return '${mb.toStringAsFixed(2)} MB';
    final gb = mb / 1024;
    return '${gb.toStringAsFixed(2)} GB';
  }

  /// Simple date formatting
  static String formatDate(DateTime date) {
    const months = {
      1: 'janv.',
      2: 'févr.',
      3: 'mars',
      4: 'avr.',
      5: 'mai',
      6: 'juin',
      7: 'juil.',
      8: 'août',
      9: 'sept.',
      10: 'oct.',
      11: 'nov.',
      12: 'déc.',
    };
    final monthStr = months[date.month] ?? date.month.toString();
    return '${date.day} $monthStr ${date.year}';
  }

  /// Get relative time (French)
  static String getRelativeTime(DateTime date) {
    final diff = DateTime.now().difference(date);
    if (diff.inSeconds < 60) return "À l'instant";
    if (diff.inMinutes < 60) return 'Il y a ${diff.inMinutes} minutes';
    if (diff.inHours < 24) return 'Il y a ${diff.inHours} heures';
    if (diff.inDays < 7) return 'Il y a ${diff.inDays} jours';
    if (diff.inDays < 30) return 'Il y a ${ (diff.inDays / 7).floor() } semaines';
    // For months/years return a formatted date which includes the year
    return formatDate(date);
  }

  /// Truncate text
  static String truncate(String text, int length) {
    if (text.isEmpty) return '';
    if (text.length <= length) return text;
    final sub = text.substring(0, length);
    final lastSpace = sub.lastIndexOf(' ');
    if (lastSpace > 0) {
      final trimmed = sub.substring(0, lastSpace);
      return '$trimmed ...';
    }
    return '$sub...';
  }

  /// Capitalize
  static String capitalize(String text) {
    if (text.isEmpty) return text;
    return text[0].toUpperCase() + text.substring(1);
  }

  /// Sanitize input
  static String sanitizeInput(String input) {
    return input.trim();
  }

  /// Parse JSON safely
  static dynamic parseJson(String jsonString) {
    try {
      return json.decode(jsonString);
    } catch (e) {
      return <String, dynamic>{};
    }
  }

  /// Generate a random code (simple)
  static String generateCode(int length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    final rnd = Random.secure();
    return List.generate(length, (_) => chars[rnd.nextInt(chars.length)]).join();
  }

  /// Return the first non-null value
  static T? getFirstNonNull<T>(List<T?> values) {
    for (final v in values) {
      if (v != null) return v;
    }
    return null;
  }

  /// Run a future with timeout helper (positional future + timeoutSeconds)
  static Future<T> withTimeout<T>(Future<T> future, {required int timeoutSeconds}) async {
    return await future.timeout(Duration(seconds: timeoutSeconds));
  }

  /// Backwards compatible signature using function and Duration
  static Future<T> withTimeoutFn<T>({
    required Future<T> Function() function,
    required Duration timeout,
  }) async {
    return await function().timeout(timeout);
  }

  /// Retry a function with exponential backoff
  static Future<T> retryWithBackoff<T>(Future<T> Function() function, {int maxAttempts = 3, Duration initialDelay = const Duration(seconds: 1)}) async {
    int attempt = 0;
    Duration delay = initialDelay;

    while (attempt < maxAttempts) {
      try {
        return await function();
      } catch (e) {
        attempt++;
        if (attempt >= maxAttempts) {
          rethrow;
        }
        await Future.delayed(delay);
        delay *= 2; // Exponential backoff
      }
    }

    throw Exception('Max retry attempts reached');
  }

  /// Backwards compatible named-parameter variant
  static Future<T> retryWithBackoffFn<T>({required Future<T> Function() function, int maxAttempts = 3, Duration initialDelay = const Duration(seconds: 1)}) async {
    return retryWithBackoff(function, maxAttempts: maxAttempts, initialDelay: initialDelay);
  }
}
