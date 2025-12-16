import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:connectivity_plus/connectivity_plus.dart';

/// Helpers pour la gestion des appels API
class ApiHelpers {
  /// Vérifie la connexion internet
  static Future<bool> hasInternetConnection() async {
    try {
      final connectivityResult = await Connectivity().checkConnectivity();
      if (connectivityResult == ConnectivityResult.none) {
        return false;
      }
      
      // Double vérification avec ping
      final result = await InternetAddress.lookup('google.com');
      return result.isNotEmpty && result[0].rawAddress.isNotEmpty;
    } catch (e) {
      return false;
    }
  }

  /// Parse les erreurs API en messages utilisateur
  static String parseApiError(dynamic error) {
    if (error is DioException) {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          return 'Délai d\'attente dépassé. Vérifiez votre connexion.';
        
        case DioExceptionType.badResponse:
          final statusCode = error.response?.statusCode;
          final message = error.response?.data?['message'];
          
          if (message != null && message is String) {
            return message;
          }
          
          switch (statusCode) {
            case 400:
              return 'Requête invalide. Veuillez vérifier vos informations.';
            case 401:
              return 'Session expirée. Veuillez vous reconnecter.';
            case 403:
              return 'Accès refusé. Vous n\'avez pas les permissions nécessaires.';
            case 404:
              return 'Ressource introuvable.';
            case 422:
              return 'Données invalides. Veuillez vérifier votre saisie.';
            case 429:
              return 'Trop de requêtes. Veuillez patienter quelques instants.';
            case 500:
            case 502:
            case 503:
              return 'Erreur serveur. Veuillez réessayer plus tard.';
            default:
              return 'Une erreur s\'est produite (code: $statusCode)';
          }
        
        case DioExceptionType.cancel:
          return 'Requête annulée.';
        
        case DioExceptionType.unknown:
        default:
          if (error.error is SocketException) {
            return 'Pas de connexion internet.';
          }
          return 'Erreur de connexion. Vérifiez votre réseau.';
      }
    }
    
    return 'Une erreur inattendue s\'est produite.';
  }

  /// Retry logic avec backoff exponentiel
  static Future<T> retryWithBackoff<T>({
    required Future<T> Function() operation,
    int maxAttempts = 3,
    Duration initialDelay = const Duration(seconds: 1),
    double backoffMultiplier = 2.0,
  }) async {
    Duration delay = initialDelay;
    
    for (int attempt = 1; attempt <= maxAttempts; attempt++) {
      try {
        return await operation();
      } catch (e) {
        if (attempt == maxAttempts) {
          rethrow;
        }
        
        // Attendre avant de réessayer
        await Future.delayed(delay);
        delay *= backoffMultiplier;
      }
    }
    
    throw Exception('Maximum retry attempts exceeded');
  }

  /// Timeout wrapper
  static Future<T> withTimeout<T>({
    required Future<T> Function() operation,
    Duration timeout = const Duration(seconds: 30),
    String? timeoutMessage,
  }) async {
    try {
      return await operation().timeout(timeout);
    } on TimeoutException {
      throw Exception(timeoutMessage ?? 'Opération expirée');
    }
  }

  /// Valide le format de l'email
  static bool isValidEmail(String email) {
    final emailRegex = RegExp(
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
    );
    return emailRegex.hasMatch(email);
  }

  /// Valide le format du téléphone (format africain)
  static bool isValidPhone(String phone) {
    // Format: +225 XX XX XX XX XX ou 225XXXXXXXXXX
    final phoneRegex = RegExp(r'^\+?[0-9]{10,15}$');
    return phoneRegex.hasMatch(phone.replaceAll(RegExp(r'[\s-]'), ''));
  }

  /// Formate un numéro de téléphone
  static String formatPhone(String phone) {
    final cleaned = phone.replaceAll(RegExp(r'[\s-]'), '');
    
    if (cleaned.startsWith('+')) {
      return cleaned;
    } else if (cleaned.startsWith('0')) {
      // Remplacer le 0 initial par le code pays (ex: +225 pour CI)
      return '+225${cleaned.substring(1)}';
    }
    
    return '+$cleaned';
  }

  /// Encode les données en JSON
  static String encodeJson(Map<String, dynamic> data) {
    return jsonEncode(data);
  }

  /// Decode le JSON
  static Map<String, dynamic> decodeJson(String jsonString) {
    try {
      return jsonDecode(jsonString) as Map<String, dynamic>;
    } catch (e) {
      throw FormatException('Invalid JSON format');
    }
  }

  /// Convertit les bytes en taille lisible
  static String formatFileSize(int bytes) {
    if (bytes < 1024) {
      return '$bytes B';
    } else if (bytes < 1024 * 1024) {
      return '${(bytes / 1024).toStringAsFixed(1)} KB';
    } else if (bytes < 1024 * 1024 * 1024) {
      return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
    } else {
      return '${(bytes / (1024 * 1024 * 1024)).toStringAsFixed(1)} GB';
    }
  }

  /// Formate une date en format lisible
  static String formatDate(DateTime date, {bool includeTime = false}) {
    final months = [
      'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
      'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'
    ];
    
    final month = months[date.month - 1];
    final day = date.day.toString().padLeft(2, '0');
    final year = date.year;
    
    if (includeTime) {
      final hour = date.hour.toString().padLeft(2, '0');
      final minute = date.minute.toString().padLeft(2, '0');
      return '$day $month $year à $hour:$minute';
    }
    
    return '$day $month $year';
  }

  /// Calcule le temps relatif (il y a X minutes/heures/jours)
  static String getRelativeTime(DateTime dateTime) {
    final now = DateTime.now();
    final difference = now.difference(dateTime);
    
    if (difference.inSeconds < 60) {
      return 'À l\'instant';
    } else if (difference.inMinutes < 60) {
      final minutes = difference.inMinutes;
      return 'Il y a ${minutes}min';
    } else if (difference.inHours < 24) {
      final hours = difference.inHours;
      return 'Il y a ${hours}h';
    } else if (difference.inDays < 7) {
      final days = difference.inDays;
      return 'Il y a ${days}j';
    } else if (difference.inDays < 30) {
      final weeks = (difference.inDays / 7).floor();
      return 'Il y a ${weeks}sem';
    } else if (difference.inDays < 365) {
      final months = (difference.inDays / 30).floor();
      return 'Il y a ${months}mois';
    } else {
      final years = (difference.inDays / 365).floor();
      return 'Il y a ${years}an${years > 1 ? 's' : ''}';
    }
  }

  /// Sanitize input (prévention XSS basique)
  static String sanitizeInput(String input) {
    return input
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#x27;')
        .trim();
  }

  /// Tronque un texte avec ellipsis
  static String truncate(String text, int maxLength, {String ellipsis = '...'}) {
    if (text.length <= maxLength) {
      return text;
    }
    return '${text.substring(0, maxLength - ellipsis.length)}$ellipsis';
  }

  /// Capitalise la première lettre
  static String capitalize(String text) {
    if (text.isEmpty) return text;
    return '${text[0].toUpperCase()}${text.substring(1).toLowerCase()}';
  }

  /// Génère un code aléatoire (pour référence, etc.)
  static String generateCode(int length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    final random = DateTime.now().millisecondsSinceEpoch;
    
    String code = '';
    for (int i = 0; i < length; i++) {
      final index = (random + i) % chars.length;
      code += chars[index];
    }
    
    return code;
  }

  /// Vérifie si une chaîne est vide ou null
  static bool isEmpty(String? text) {
    return text == null || text.trim().isEmpty;
  }

  /// Vérifie si une liste est vide ou null
  static bool isListEmpty(List? list) {
    return list == null || list.isEmpty;
  }
}

/// Network Helper pour surveiller la connexion
class NetworkHelper {
  static final NetworkHelper _instance = NetworkHelper._internal();
  factory NetworkHelper() => _instance;
  NetworkHelper._internal();

  final Connectivity _connectivity = Connectivity();
  StreamSubscription<ConnectivityResult>? _subscription;
  
  bool _isConnected = true;
  
  bool get isConnected => _isConnected;

  /// Initialise la surveillance de la connexion
  void initialize({Function(bool)? onConnectionChanged}) {
    _subscription = _connectivity.onConnectivityChanged.listen((result) {
      final wasConnected = _isConnected;
      _isConnected = result != ConnectivityResult.none;
      
      if (wasConnected != _isConnected && onConnectionChanged != null) {
        onConnectionChanged(_isConnected);
      }
    });
  }

  /// Vérifie la connexion actuelle
  Future<bool> checkConnection() async {
    final result = await _connectivity.checkConnectivity();
    _isConnected = result != ConnectivityResult.none;
    return _isConnected;
  }

  /// Arrête la surveillance
  void dispose() {
    _subscription?.cancel();
  }
}

/// Dio Interceptor pour logs et gestion d'erreurs
class ApiInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    print('🌐 [API REQUEST] ${options.method} ${options.path}');
    print('📦 [DATA] ${options.data}');
    super.onRequest(options, handler);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    print('✅ [API RESPONSE] ${response.statusCode} ${response.requestOptions.path}');
    super.onResponse(response, handler);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    print('❌ [API ERROR] ${err.type} ${err.requestOptions.path}');
    print('📛 [ERROR MESSAGE] ${err.message}');
    super.onError(err, handler);
  }
}
