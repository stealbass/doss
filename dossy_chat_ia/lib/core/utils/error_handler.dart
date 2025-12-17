import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';

/// Gestionnaire d'erreurs global
/// Centralise la gestion et le formatage des erreurs
class ErrorHandler {
  /// Obtenir un message d'erreur user-friendly
  static String getErrorMessage(dynamic error) {
    if (error is DioException) {
      return _handleDioError(error);
    }

    if (error is FormatException) {
      return 'Erreur de format des données';
    }

    if (error is TypeError) {
      return 'Erreur de type de données';
    }

    // Erreur générique
    return error.toString().replaceAll('Exception:', '').trim();
  }

  /// Gérer les erreurs Dio
  static String _handleDioError(DioException error) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
        return 'Délai de connexion dépassé. Vérifiez votre connexion internet.';

      case DioExceptionType.sendTimeout:
        return 'Délai d\'envoi dépassé. Veuillez réessayer.';

      case DioExceptionType.receiveTimeout:
        return 'Délai de réception dépassé. Le serveur ne répond pas.';

      case DioExceptionType.badResponse:
        return _handleBadResponse(error);

      case DioExceptionType.cancel:
        return 'Requête annulée';

      case DioExceptionType.connectionError:
        return 'Erreur de connexion. Vérifiez votre connexion internet.';

      case DioExceptionType.badCertificate:
        return 'Erreur de certificat SSL. Connexion non sécurisée.';

      case DioExceptionType.unknown:
      default:
        if (error.message?.contains('SocketException') == true) {
          return 'Pas de connexion internet';
        }
        return 'Erreur inconnue: ${error.message}';
    }
  }

  /// Gérer les réponses HTTP avec erreur
  static String _handleBadResponse(DioException error) {
    final statusCode = error.response?.statusCode;
    final data = error.response?.data;

    // Essayer d'extraire le message d'erreur du serveur
    String? serverMessage;
    if (data is Map) {
      serverMessage = data['message'] ?? data['error'] ?? data['msg'];
    }

    switch (statusCode) {
      case 400:
        return serverMessage ?? 'Requête invalide';

      case 401:
        return 'Non autorisé. Veuillez vous reconnecter.';

      case 403:
        return serverMessage ?? 'Accès interdit';

      case 404:
        return 'Ressource non trouvée';

      case 409:
        return serverMessage ?? 'Conflit de données';

      case 422:
        return serverMessage ?? 'Données invalides';

      case 429:
        return 'Trop de requêtes. Veuillez patienter.';

      case 500:
        return 'Erreur serveur interne';

      case 502:
        return 'Passerelle incorrecte';

      case 503:
        return 'Service temporairement indisponible';

      case 504:
        return 'Délai de réponse du serveur dépassé';

      default:
        return serverMessage ?? 'Erreur HTTP $statusCode';
    }
  }

  /// Logger une erreur
  static void logError(dynamic error, {StackTrace? stackTrace, String? context}) {
    if (kDebugMode) {
      debugPrint('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
      debugPrint('❌ ERREUR${context != null ? " [$context]" : ""}');
      debugPrint('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
      debugPrint('Type: ${error.runtimeType}');
      debugPrint('Message: $error');
      if (stackTrace != null) {
        debugPrint('Stack trace:');
        debugPrint(stackTrace.toString());
      }
      debugPrint('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');
    }

    // TODO: Envoyer à Firebase Crashlytics en production
  }

  /// Vérifier si c'est une erreur réseau
  static bool isNetworkError(dynamic error) {
    if (error is DioException) {
      return error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.connectionError ||
          error.message?.contains('SocketException') == true;
    }
    return false;
  }

  /// Vérifier si c'est une erreur d'authentification
  static bool isAuthError(dynamic error) {
    if (error is DioException) {
      return error.response?.statusCode == 401 || error.response?.statusCode == 403;
    }
    return false;
  }
}
