import 'dart:async';
import 'dart:convert';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;

/// Service de gestion du mode offline et de la synchronisation
/// 
/// Fonctionnalités :
/// - Détection de la connexion Internet
/// - Cache local des données (Hive)
/// - Synchronisation automatique quand connexion rétablie
/// - File d'attente des opérations offline
/// - Gestion des conflits de synchronisation
class OfflineService {
  static final OfflineService _instance = OfflineService._internal();
  factory OfflineService() => _instance;
  OfflineService._internal();

  final Connectivity _connectivity = Connectivity();
  final StreamController<bool> _connectionStatusController = StreamController<bool>.broadcast();
  
  bool _isOnline = true;
  StreamSubscription<ConnectivityResult>? _connectivitySubscription;

  // Boxes Hive pour le cache
  Box? _cacheBox;
  Box? _pendingActionsBox;
  Box? _metadataBox;

  // Stream pour écouter le statut de connexion
  Stream<bool> get connectionStatus => _connectionStatusController.stream;
  bool get isOnline => _isOnline;

  /// Initialiser le service offline
  Future<void> initialize() async {
    try {
      // Initialiser Hive
      await Hive.initFlutter();

      // Ouvrir les boxes Hive
      _cacheBox = await Hive.openBox('cache');
      _pendingActionsBox = await Hive.openBox('pending_actions');
      _metadataBox = await Hive.openBox('metadata');

      // Vérifier la connexion initiale
      final connectivityResult = await _connectivity.checkConnectivity();
      _isOnline = connectivityResult != ConnectivityResult.none;
      _connectionStatusController.add(_isOnline);

      print('🌐 Statut connexion initial: ${_isOnline ? "ONLINE" : "OFFLINE"}');

      // Écouter les changements de connexion
      _connectivitySubscription = _connectivity.onConnectivityChanged.listen((ConnectivityResult result) {
        final wasOnline = _isOnline;
        _isOnline = result != ConnectivityResult.none;
        
        print('🌐 Changement connexion: ${_isOnline ? "ONLINE" : "OFFLINE"}');
        _connectionStatusController.add(_isOnline);

        // Si on vient de se reconnecter, synchroniser
        if (!wasOnline && _isOnline) {
          print('🔄 Connexion rétablie, synchronisation...');
          _syncPendingActions();
        }
      });

      print('✅ OfflineService initialisé avec succès');
    } catch (e) {
      print('❌ Erreur initialisation OfflineService: $e');
    }
  }

  /// Sauvegarder des données en cache
  Future<void> cacheData(String key, dynamic data) async {
    try {
      final String jsonData = jsonEncode(data);
      await _cacheBox?.put(key, jsonData);
      
      // Sauvegarder la date de mise en cache
      await _metadataBox?.put('${key}_timestamp', DateTime.now().toIso8601String());
      
      print('💾 Données cachées: $key');
    } catch (e) {
      print('❌ Erreur cache données: $e');
    }
  }

  /// Récupérer des données du cache
  Future<dynamic> getCachedData(String key, {Duration? maxAge}) async {
    try {
      final String? jsonData = _cacheBox?.get(key);
      if (jsonData == null) return null;

      // Vérifier l'âge du cache si maxAge est spécifié
      if (maxAge != null) {
        final String? timestampStr = _metadataBox?.get('${key}_timestamp');
        if (timestampStr != null) {
          final DateTime timestamp = DateTime.parse(timestampStr);
          final Duration age = DateTime.now().difference(timestamp);
          
          if (age > maxAge) {
            print('⏰ Cache expiré pour: $key (âge: ${age.inMinutes} min)');
            return null;
          }
        }
      }

      final dynamic data = jsonDecode(jsonData);
      print('📦 Données récupérées du cache: $key');
      return data;
    } catch (e) {
      print('❌ Erreur récupération cache: $e');
      return null;
    }
  }

  /// Supprimer des données du cache
  Future<void> deleteCachedData(String key) async {
    try {
      await _cacheBox?.delete(key);
      await _metadataBox?.delete('${key}_timestamp');
      print('🗑️ Cache supprimé: $key');
    } catch (e) {
      print('❌ Erreur suppression cache: $e');
    }
  }

  /// Vider tout le cache
  Future<void> clearCache() async {
    try {
      await _cacheBox?.clear();
      await _metadataBox?.clear();
      print('🗑️ Tout le cache a été vidé');
    } catch (e) {
      print('❌ Erreur vidage cache: $e');
    }
  }

  /// Ajouter une action en attente (pour synchronisation ultérieure)
  Future<void> addPendingAction({
    required String type,
    required String endpoint,
    required String method,
    required Map<String, dynamic> data,
  }) async {
    try {
      final action = {
        'id': DateTime.now().millisecondsSinceEpoch.toString(),
        'type': type,
        'endpoint': endpoint,
        'method': method,
        'data': data,
        'timestamp': DateTime.now().toIso8601String(),
        'retryCount': 0,
      };

      await _pendingActionsBox?.add(action);
      print('📝 Action ajoutée à la file d\'attente: $type');
    } catch (e) {
      print('❌ Erreur ajout action pendante: $e');
    }
  }

  /// Récupérer toutes les actions en attente
  Future<List<Map<String, dynamic>>> getPendingActions() async {
    try {
      final List<dynamic> actions = _pendingActionsBox?.values.toList() ?? [];
      return actions.map((action) => Map<String, dynamic>.from(action)).toList();
    } catch (e) {
      print('❌ Erreur récupération actions pendantes: $e');
      return [];
    }
  }

  /// Synchroniser toutes les actions en attente
  Future<void> _syncPendingActions() async {
    try {
      final List<Map<String, dynamic>> actions = await getPendingActions();
      
      if (actions.isEmpty) {
        print('ℹ️ Aucune action en attente');
        return;
      }

      print('🔄 Synchronisation de ${actions.length} action(s)...');

      final prefs = await SharedPreferences.getInstance();
      final String? apiToken = prefs.getString('api_token');
      final String? baseUrl = prefs.getString('api_base_url');

      if (apiToken == null || baseUrl == null) {
        print('⚠️ Impossible de synchroniser: pas de token API');
        return;
      }

      int successCount = 0;
      int failureCount = 0;

      for (int i = 0; i < actions.length; i++) {
        final action = actions[i];
        final bool success = await _executePendingAction(
          action: action,
          apiToken: apiToken,
          baseUrl: baseUrl,
        );

        if (success) {
          successCount++;
          // Supprimer l'action de la file d'attente
          await _pendingActionsBox?.deleteAt(i);
        } else {
          failureCount++;
          // Incrémenter le compteur de tentatives
          action['retryCount'] = (action['retryCount'] ?? 0) + 1;
          await _pendingActionsBox?.putAt(i, action);
        }
      }

      print('✅ Synchronisation terminée: $successCount réussies, $failureCount échouées');
    } catch (e) {
      print('❌ Erreur synchronisation: $e');
    }
  }

  /// Exécuter une action en attente
  Future<bool> _executePendingAction({
    required Map<String, dynamic> action,
    required String apiToken,
    required String baseUrl,
  }) async {
    try {
      final String endpoint = action['endpoint'];
      final String method = action['method'];
      final Map<String, dynamic> data = action['data'];

      http.Response response;
      final Uri uri = Uri.parse('$baseUrl$endpoint');
      final Map<String, String> headers = {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $apiToken',
      };

      switch (method.toUpperCase()) {
        case 'POST':
          response = await http.post(uri, headers: headers, body: jsonEncode(data));
          break;
        case 'PUT':
          response = await http.put(uri, headers: headers, body: jsonEncode(data));
          break;
        case 'DELETE':
          response = await http.delete(uri, headers: headers);
          break;
        default:
          print('⚠️ Méthode HTTP non supportée: $method');
          return false;
      }

      if (response.statusCode >= 200 && response.statusCode < 300) {
        print('✅ Action synchronisée: ${action['type']}');
        return true;
      } else {
        print('❌ Échec synchronisation: ${response.statusCode}');
        return false;
      }
    } catch (e) {
      print('❌ Erreur exécution action pendante: $e');
      return false;
    }
  }

  /// Forcer la synchronisation manuelle
  Future<void> forceSyncNow() async {
    if (!_isOnline) {
      print('⚠️ Impossible de synchroniser: pas de connexion');
      return;
    }

    print('🔄 Synchronisation manuelle démarrée...');
    await _syncPendingActions();
  }

  /// Vérifier si des données sont disponibles en cache
  Future<bool> hasCachedData(String key) async {
    return _cacheBox?.containsKey(key) ?? false;
  }

  /// Obtenir la taille du cache (en bytes)
  Future<int> getCacheSize() async {
    try {
      int totalSize = 0;
      
      if (_cacheBox != null) {
        for (var key in _cacheBox!.keys) {
          final String? value = _cacheBox!.get(key);
          if (value != null) {
            totalSize += value.length;
          }
        }
      }

      return totalSize;
    } catch (e) {
      print('❌ Erreur calcul taille cache: $e');
      return 0;
    }
  }

  /// Obtenir le nombre d'actions en attente
  Future<int> getPendingActionsCount() async {
    return _pendingActionsBox?.length ?? 0;
  }

  /// Stratégie de cache : Essayer d'abord le réseau, puis le cache
  Future<dynamic> fetchWithCache({
    required String key,
    required Future<dynamic> Function() fetchFunction,
    Duration maxAge = const Duration(hours: 24),
  }) async {
    // Si online, essayer de récupérer depuis le réseau
    if (_isOnline) {
      try {
        final data = await fetchFunction();
        // Sauvegarder en cache
        await cacheData(key, data);
        return data;
      } catch (e) {
        print('⚠️ Erreur réseau, tentative cache: $e');
        // En cas d'erreur, essayer le cache
        return await getCachedData(key, maxAge: maxAge);
      }
    } else {
      // Si offline, récupérer depuis le cache
      print('📦 Mode offline, utilisation du cache');
      return await getCachedData(key, maxAge: maxAge);
    }
  }

  /// Stratégie de cache : Cache d'abord, puis mise à jour en arrière-plan
  Future<dynamic> cacheFirst({
    required String key,
    required Future<dynamic> Function() fetchFunction,
    Duration maxAge = const Duration(hours: 24),
  }) async {
    // Récupérer immédiatement du cache
    final cachedData = await getCachedData(key, maxAge: maxAge);

    // Si online, mettre à jour le cache en arrière-plan
    if (_isOnline) {
      fetchFunction().then((data) {
        cacheData(key, data);
      }).catchError((e) {
        print('⚠️ Erreur mise à jour cache: $e');
      });
    }

    return cachedData;
  }

  /// Nettoyer les ressources
  void dispose() {
    _connectivitySubscription?.cancel();
    _connectionStatusController.close();
    _cacheBox?.close();
    _pendingActionsBox?.close();
    _metadataBox?.close();
  }
}
