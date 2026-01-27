import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_analytics/firebase_analytics.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

/// Service Firebase pour Analytics, Messaging et Crashlytics
/// Gestion centralisée de toutes les fonctionnalités Firebase
class FirebaseService {
  static final FirebaseService _instance = FirebaseService._internal();
  factory FirebaseService() => _instance;
  FirebaseService._internal();

  FirebaseAnalytics? _analytics;
  FirebaseMessaging? _messaging;
  String? _fcmToken;

  /// Obtenir l'instance Analytics
  FirebaseAnalytics? get analytics => _analytics;

  /// Obtenir le token FCM
  String? get fcmToken => _fcmToken;

  /// Initialiser Firebase
  Future<void> initialize() async {
    try {
      await Firebase.initializeApp();
      _analytics = FirebaseAnalytics.instance;
      _messaging = FirebaseMessaging.instance;

      await _setupAnalytics();
      await _setupMessaging();

      debugPrint('✅ Firebase initialisé avec succès');
    } catch (e) {
      debugPrint('❌ Erreur d\'initialisation Firebase: $e');
    }
  }

  /// Configurer Analytics
  Future<void> _setupAnalytics() async {
    try {
      await _analytics?.setAnalyticsCollectionEnabled(true);
      debugPrint('✅ Analytics configuré');
    } catch (e) {
      debugPrint('❌ Erreur Analytics: $e');
    }
  }

  /// Configurer Messaging
  Future<void> _setupMessaging() async {
    try {
      // Demander la permission
      final settings = await _messaging?.requestPermission(
        alert: true,
        announcement: false,
        badge: true,
        carPlay: false,
        criticalAlert: false,
        provisional: false,
        sound: true,
      );

      if (settings?.authorizationStatus == AuthorizationStatus.authorized) {
        debugPrint('✅ Permission notifications accordée');

        // Obtenir le token
        _fcmToken = await _messaging?.getToken();
        debugPrint('📱 FCM Token: $_fcmToken');

        // Écouter les changements de token
        _messaging?.onTokenRefresh.listen((newToken) {
          _fcmToken = newToken;
          debugPrint('🔄 Nouveau FCM Token: $newToken');
          // TODO: Envoyer au serveur
        });

        // Configurer les handlers
        _setupMessageHandlers();
      } else {
        debugPrint('⚠️ Permission notifications refusée');
      }
    } catch (e) {
      debugPrint('❌ Erreur Messaging: $e');
    }
  }

  /// Configurer les handlers de messages
  void _setupMessageHandlers() {
    // Message en foreground
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      debugPrint('📨 Message reçu (foreground): ${message.notification?.title}');
      // TODO: Afficher une notification locale
    });

    // Message quand l'app est en background mais ouverte
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      debugPrint('📬 Message ouvert (background): ${message.notification?.title}');
      // TODO: Navigation vers l'écran approprié
    });
  }

  /// Logger un événement Analytics
  Future<void> logEvent({
    required String name,
    Map<String, dynamic>? parameters,
  }) async {
    try {
      await _analytics?.logEvent(
        name: name,
        parameters: parameters,
      );
      debugPrint('📊 Event: $name ${parameters ?? ""}');
    } catch (e) {
      debugPrint('❌ Erreur log event: $e');
    }
  }

  /// Logger une page vue
  Future<void> logScreenView({
    required String screenName,
    String? screenClass,
  }) async {
    try {
      await _analytics?.logScreenView(
        screenName: screenName,
        screenClass: screenClass,
      );
      debugPrint('📄 Screen: $screenName');
    } catch (e) {
      debugPrint('❌ Erreur screen view: $e');
    }
  }

  /// Définir l'utilisateur
  Future<void> setUserId(String userId) async {
    try {
      await _analytics?.setUserId(id: userId);
      debugPrint('👤 User ID set: $userId');
    } catch (e) {
      debugPrint('❌ Erreur set user: $e');
    }
  }

  /// Définir une propriété utilisateur
  Future<void> setUserProperty({
    required String name,
    required String value,
  }) async {
    try {
      await _analytics?.setUserProperty(name: name, value: value);
      debugPrint('🏷️ User property: $name = $value');
    } catch (e) {
      debugPrint('❌ Erreur user property: $e');
    }
  }

  /// S'abonner à un topic de notification
  Future<void> subscribeToTopic(String topic) async {
    try {
      await _messaging?.subscribeToTopic(topic);
      debugPrint('🔔 Abonné au topic: $topic');
    } catch (e) {
      debugPrint('❌ Erreur subscribe: $e');
    }
  }

  /// Se désabonner d'un topic
  Future<void> unsubscribeFromTopic(String topic) async {
    try {
      await _messaging?.unsubscribeFromTopic(topic);
      debugPrint('🔕 Désabonné du topic: $topic');
    } catch (e) {
      debugPrint('❌ Erreur unsubscribe: $e');
    }
  }
}

/// Handler pour les messages en background
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  debugPrint('📩 Message background: ${message.notification?.title}');
}
