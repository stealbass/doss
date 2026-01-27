import 'dart:async';
import 'dart:io';
import 'dart:ui' show Color;
import 'package:flutter/foundation.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

/// Service de gestion des notifications push via Firebase Cloud Messaging
///
/// Fonctionnalités :
/// - Réception de notifications push (audiences, tâches, alertes)
/// - Affichage de notifications locales
/// - Gestion des tokens FCM
/// - Navigation depuis les notifications
/// - Support Android et iOS
class PushNotificationService {
  static const bool _loggingEnabled = kDebugMode;
  void _log(String message) {
    if (_loggingEnabled) debugPrint(message);
  }

  static final PushNotificationService _instance =
      PushNotificationService._internal();
  factory PushNotificationService() => _instance;
  PushNotificationService._internal();

  final FirebaseMessaging _firebaseMessaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  String? _fcmToken;
  final StreamController<RemoteMessage> _messageStreamController =
      StreamController<RemoteMessage>.broadcast();

  // Stream pour écouter les messages
  Stream<RemoteMessage> get onMessageReceived =>
      _messageStreamController.stream;

  /// Initialiser le service de notifications push
  Future<void> initialize() async {
    try {
      // Demander les permissions (iOS)
      NotificationSettings settings =
          await _firebaseMessaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        announcement: false,
        carPlay: false,
        criticalAlert: false,
        provisional: false,
      );

      if (settings.authorizationStatus == AuthorizationStatus.authorized) {
        _log('✅ Push notifications: Permissions accordées');
      } else if (settings.authorizationStatus ==
          AuthorizationStatus.provisional) {
        _log('⚠️ Push notifications: Permissions provisoires');
      } else {
        _log('❌ Push notifications: Permissions refusées');
        return;
      }

      // Initialiser les notifications locales
      await _initializeLocalNotifications();

      // Récupérer le token FCM
      _fcmToken = await _firebaseMessaging.getToken();
      if (_fcmToken != null) {
        _log('📱 FCM Token: $_fcmToken');
        await _saveFcmToken(_fcmToken!);
      }

      // Écouter les changements de token
      _firebaseMessaging.onTokenRefresh.listen((newToken) {
        _log('🔄 FCM Token refreshed: $newToken');
        _fcmToken = newToken;
        _saveFcmToken(newToken);
      });

      // Configurer les handlers de messages
      _setupMessageHandlers();

      _log('✅ PushNotificationService initialisé avec succès');
    } catch (e) {
      _log('❌ Erreur initialisation PushNotificationService: $e');
    }
  }

  /// Initialiser les notifications locales (Android/iOS)
  Future<void> _initializeLocalNotifications() async {
    const AndroidInitializationSettings androidSettings =
        AndroidInitializationSettings('@mipmap/ic_launcher');

    final DarwinInitializationSettings iosSettings =
        DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
      onDidReceiveLocalNotification: (id, title, body, payload) async {
        // iOS < 10 callback
        _log('iOS notification reçue: $title');
      },
    );

    final InitializationSettings settings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await _localNotifications.initialize(
      settings,
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        // Callback quand l'utilisateur clique sur la notification
        _log('Notification cliquée: ${response.payload}');
        _handleNotificationTap(response.payload);
      },
    );
  }

  /// Configurer les handlers de messages Firebase
  void _setupMessageHandlers() {
    // Message reçu quand l'app est au premier plan
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      _log('📬 Message reçu (foreground): ${message.notification?.title}');
      _messageStreamController.add(message);
      _showLocalNotification(message);
    });

    // Message cliqué quand l'app est en arrière-plan
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      _log(
          '🔔 Notification cliquée (background): ${message.notification?.title}');
      _messageStreamController.add(message);
      _handleNotificationData(message.data);
    });

    // Message reçu quand l'app est complètement fermée
    FirebaseMessaging.instance
        .getInitialMessage()
        .then((RemoteMessage? message) {
      if (message != null) {
        _log(
            '🚀 Notification cliquée (app fermée): ${message.notification?.title}');
        _messageStreamController.add(message);
        _handleNotificationData(message.data);
      }
    });
  }

  /// Afficher une notification locale
  Future<void> _showLocalNotification(RemoteMessage message) async {
    RemoteNotification? notification = message.notification;
    if (notification == null) return;

    // Configuration Android
    const AndroidNotificationDetails androidDetails =
        AndroidNotificationDetails(
      'dossy_pro_channel', // Channel ID
      'Dossy Pro Notifications', // Channel name
      channelDescription: 'Notifications pour audiences, tâches et alertes',
      importance: Importance.high,
      priority: Priority.high,
      showWhen: true,
      icon: '@mipmap/ic_launcher',
      color: Color(0xFF007bff), // Bleu Dossy Pro
      playSound: true,
      enableVibration: true,
    );

    // Configuration iOS
    const DarwinNotificationDetails iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const NotificationDetails platformDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    // Afficher la notification
    await _localNotifications.show(
      notification.hashCode,
      notification.title,
      notification.body,
      platformDetails,
      payload: jsonEncode(message.data),
    );
  }

  /// Gérer le clic sur une notification
  void _handleNotificationTap(String? payload) {
    if (payload == null) return;

    try {
      final Map<String, dynamic> data = jsonDecode(payload);
      _handleNotificationData(data);
    } catch (e) {
      _log('❌ Erreur parsing payload: $e');
    }
  }

  /// Gérer les données de la notification et naviguer
  void _handleNotificationData(Map<String, dynamic> data) {
    debugPrint('📊 Données notification: $data');
    _log('📊 Données notification: $data');

    final String? type = data['type'];
    final String? id = data['id'];

    if (type == null || id == null) return;

    // Navigation selon le type
    switch (type) {
      case 'hearing':
      case 'audience':
        // Naviguer vers les détails de l'audience
        _log('🏛️ Naviguer vers audience: $id');
        // TODO: Implémenter navigation
        // Navigator.pushNamed(context, '/hearing-details', arguments: id);
        break;

      case 'task':
      case 'todo':
        // Naviguer vers les détails de la tâche
        _log('📋 Naviguer vers tâche: $id');
        // TODO: Implémenter navigation
        // Navigator.pushNamed(context, '/task-details', arguments: id);
        break;

      case 'alert':
      case 'legal_alert':
        // Naviguer vers les alertes juridiques
        _log('⚖️ Naviguer vers alertes juridiques');
        // TODO: Implémenter navigation
        // Navigator.pushNamed(context, '/legal-alerts');
        break;

      case 'document':
      case 'template':
        // Naviguer vers les documents
        _log('📄 Naviguer vers documents');
        // TODO: Implémenter navigation
        // Navigator.pushNamed(context, '/documents');
        break;

      default:
        _log('⚠️ Type de notification inconnu: $type');
    }
  }

  /// Sauvegarder le token FCM dans SharedPreferences
  Future<void> _saveFcmToken(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('fcm_token', token);
      _log('💾 FCM Token sauvegardé localement');

      // Envoyer le token au backend (optionnel)
      await _sendTokenToBackend(token);
    } catch (e) {
      _log('❌ Erreur sauvegarde FCM token: $e');
    }
  }

  /// Envoyer le token FCM au backend Laravel
  Future<void> _sendTokenToBackend(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final String? apiToken = prefs.getString('api_token');
      final String? baseUrl = prefs.getString('api_base_url');

      if (apiToken == null || baseUrl == null) {
        _log('⚠️ Pas de token API ou URL backend configurés');
        return;
      }

      final response = await http.post(
        Uri.parse('$baseUrl/api/mobile/fcm-token'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $apiToken',
        },
        body: jsonEncode({
          'fcm_token': token,
          'platform': Platform.isAndroid ? 'android' : 'ios',
        }),
      );

      if (response.statusCode == 200) {
        _log('✅ FCM Token envoyé au backend avec succès');
      } else {
        _log('❌ Erreur envoi FCM token au backend: ${response.statusCode}');
      }
    } catch (e) {
      _log('❌ Erreur envoi FCM token au backend: $e');
    }
  }

  /// Récupérer le token FCM actuel
  String? get fcmToken => _fcmToken;

  /// S'abonner à un topic (pour notifications groupées)
  Future<void> subscribeToTopic(String topic) async {
    try {
      await _firebaseMessaging.subscribeToTopic(topic);
      _log('✅ Abonné au topic: $topic');
    } catch (e) {
      _log('❌ Erreur abonnement topic: $e');
    }
  }

  /// Se désabonner d'un topic
  Future<void> unsubscribeFromTopic(String topic) async {
    try {
      await _firebaseMessaging.unsubscribeFromTopic(topic);
      _log('✅ Désabonné du topic: $topic');
    } catch (e) {
      _log('❌ Erreur désabonnement topic: $e');
    }
  }

  /// Afficher une notification de test
  Future<void> showTestNotification() async {
    const AndroidNotificationDetails androidDetails =
        AndroidNotificationDetails(
      'dossy_pro_channel',
      'Dossy Pro Notifications',
      channelDescription: 'Notifications pour audiences, tâches et alertes',
      importance: Importance.high,
      priority: Priority.high,
      icon: '@mipmap/ic_launcher',
      color: Color(0xFF007bff),
    );

    const NotificationDetails platformDetails = NotificationDetails(
      android: androidDetails,
    );

    await _localNotifications.show(
      0,
      '🎉 Dossy Pro',
      'Les notifications push sont activées !',
      platformDetails,
    );
  }

  /// Nettoyer les ressources
  void dispose() {
    _messageStreamController.close();
  }
}

/// Handler pour les messages en arrière-plan (top-level function)
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  if (kDebugMode) {
    debugPrint(
        '📬 Message reçu en arrière-plan: ${message.notification?.title}');
  }
  // Traiter le message ici si nécessaire
}
