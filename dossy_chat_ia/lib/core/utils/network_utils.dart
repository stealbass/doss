import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';

/// Utilitaires réseau pour gérer la connectivité
class NetworkUtils {
  static final NetworkUtils _instance = NetworkUtils._internal();
  factory NetworkUtils() => _instance;
  NetworkUtils._internal();

  final Connectivity _connectivity = Connectivity();
  bool _isOnline = true;

  /// Statut de connexion
  bool get isOnline => _isOnline;

  /// Initialiser le monitoring de la connexion
  Future<void> initialize() async {
    // Vérifier l'état initial
    _isOnline = await checkConnection();

    // Écouter les changements
    _connectivity.onConnectivityChanged.listen((ConnectivityResult result) {
      final isConnected = result != ConnectivityResult.none;
      
      if (_isOnline != isConnected) {
        _isOnline = isConnected;
        debugPrint(_isOnline ? '✅ Connexion rétablie' : '❌ Connexion perdue');
        // TODO: Notifier les listeners
      }
    });
  }

  /// Vérifier la connexion
  Future<bool> checkConnection() async {
    try {
      final result = await _connectivity.checkConnectivity();
      final hasConnection = result != ConnectivityResult.none;
      
      _isOnline = hasConnection;
      debugPrint('🌐 Statut connexion: ${_isOnline ? "En ligne" : "Hors ligne"}');
      return _isOnline;
    } catch (e) {
      debugPrint('❌ Erreur vérification connexion: $e');
      return false;
    }
  }

  /// Obtenir le type de connexion
  Future<String> getConnectionType() async {
    try {
      final result = await _connectivity.checkConnectivity();
      
      switch (result) {
        case ConnectivityResult.wifi:
          return 'wifi';
        case ConnectivityResult.mobile:
          return 'mobile';
        case ConnectivityResult.ethernet:
          return 'ethernet';
        case ConnectivityResult.bluetooth:
          return 'bluetooth';
        case ConnectivityResult.vpn:
          return 'vpn';
        case ConnectivityResult.other:
          return 'other';
        case ConnectivityResult.none:
        default:
          return 'none';
      }
    } catch (e) {
      debugPrint('❌ Erreur type connexion: $e');
      return 'unknown';
    }
  }

  /// Vérifier si on est en WiFi
  Future<bool> isWifi() async {
    final result = await _connectivity.checkConnectivity();
    return result == ConnectivityResult.wifi;
  }

  /// Vérifier si on est en mobile data
  Future<bool> isMobileData() async {
    final result = await _connectivity.checkConnectivity();
    return result == ConnectivityResult.mobile;
  }
}
