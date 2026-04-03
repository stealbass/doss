import 'dart:async';
import 'dart:io';
import 'dart:ui' show Color;
import 'package:flutter/foundation.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../constants/app_constants.dart';

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
  static bool _backgroundHandlerRegistered = false;
  static bool _firebaseInitialized = false;

  void _log(String message) {
    if (_loggingEnabled) debugPrint(message);
  }

  static final PushNotificationService _instance =
      PushNotificationService._internal();
  factory PushNotificationService() => _instance;
  PushNotificationService._internal();

  late final FirebaseMessaging _firebaseMessaging;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();

  String? _fcmToken;
  bool _messageHandlersConfigured = false;
  bool _localNotificationsInitialized = false;
  final StreamController<RemoteMessage> _messageStreamController =
      StreamController<RemoteMessage>.broadcast();
  static const String _inboxStorageKey = 'push_inbox_messages';
  static const int _maxInboxItems = 50;

  // Stream pour écouter les messages
  Stream<RemoteMessage> get onMessageReceived =>
      _messageStreamController.stream;

  /// Initialiser le service de notifications push
  Future<void> initialize() async {
    try {
      if (!_firebaseInitialized) {
        await Firebase.initializeApp();
        _firebaseInitialized = true;
      }

      _firebaseMessaging = FirebaseMessaging.instance;

      // Handler global pour messages reçus lorsque l'app est en arrière-plan/tuée.
      if (!_backgroundHandlerRegistered) {
        FirebaseMessaging.onBackgroundMessage(
            firebaseMessagingBackgroundHandler);
        _backgroundHandlerRegistered = true;
      }

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
        // Sur Android, on continue pour demander explicitement la permission
        // via flutter_local_notifications (Android 13+).
        if (!Platform.isAndroid) {
          return;
        }
      }

      // Initialiser les notifications locales
      if (!_localNotificationsInitialized) {
        await _initializeLocalNotifications();
        _localNotificationsInitialized = true;
      }

      if (Platform.isAndroid) {
        final androidPlugin =
            _localNotifications.resolvePlatformSpecificImplementation<
                AndroidFlutterLocalNotificationsPlugin>();
        await androidPlugin?.requestNotificationsPermission();
      }

      // Récupérer le token FCM (avec retry pour éviter les null intermittents).
      _fcmToken = await _getFcmTokenWithRetry();
      if (_fcmToken != null && _fcmToken!.isNotEmpty) {
        _log('📱 FCM Token: $_fcmToken');
        await _saveFcmToken(_fcmToken!);
      } else {
        // Dernier fallback: tenter d'envoyer un token local déjà sauvegardé.
        final prefs = await SharedPreferences.getInstance();
        final String? cachedToken = prefs.getString('fcm_token');
        if (cachedToken != null && cachedToken.isNotEmpty) {
          _log('♻️ Utilisation du token FCM cache pour sync backend');
          _fcmToken = cachedToken;
          await _sendTokenToBackend(cachedToken);
        } else {
          _log('❌ Aucun token FCM disponible après retries');
        }
      }

      // Écouter les changements de token
      _firebaseMessaging.onTokenRefresh.listen((newToken) {
        _log('🔄 FCM Token refreshed: $newToken');
        _fcmToken = newToken;
        _saveFcmToken(newToken);
      });

      // Configurer les handlers de messages
      if (!_messageHandlersConfigured) {
        _setupMessageHandlers();
        _messageHandlersConfigured = true;
      }

      // Abonner l'app au topic utilisateur pour fallback serveur sans token DB.
      await _subscribeToUserTopicFromSession();

      _log('✅ PushNotificationService initialisé avec succès');
    } catch (e) {
      _log('❌ Erreur initialisation PushNotificationService: $e');
    }
  }

  /// Resynchroniser explicitement token + topic après authentification.
  /// Utile si initialize() a été déclenché avant login.
  Future<void> syncAfterAuth() async {
    try {
      await _firebaseMessaging.setAutoInitEnabled(true);

      final String? token =
          _fcmToken ?? await _getFcmTokenWithRetry(maxAttempts: 3);
      if (token != null && token.isNotEmpty) {
        _fcmToken = token;
        await _saveFcmToken(token);
      } else {
        _log('⚠️ syncAfterAuth: aucun token FCM disponible');
      }

      await _subscribeToUserTopicFromSession();
    } catch (e) {
      _log('❌ syncAfterAuth failed: $e');
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

    if (Platform.isAndroid) {
      final androidPlugin =
          _localNotifications.resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>();

      const AndroidNotificationChannel dossyChannel =
          AndroidNotificationChannel(
        'dossy_pro_channel',
        'Dossy Pro Notifications',
        description: 'Notifications pour audiences, taches et alertes',
        importance: Importance.high,
        playSound: true,
      );

      await androidPlugin?.createNotificationChannel(dossyChannel);
    }
  }

  /// Configurer les handlers de messages Firebase
  void _setupMessageHandlers() {
    // Message reçu quand l'app est au premier plan
    FirebaseMessaging.onMessage.listen((RemoteMessage message) async {
      _log('📬 Message reçu (foreground): ${message.notification?.title}');
      await _persistInboxMessage(message, isRead: false);
      _messageStreamController.add(message);
      _showLocalNotification(message);
    });

    // Message cliqué quand l'app est en arrière-plan
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) async {
      _log(
          '🔔 Notification cliquée (background): ${message.notification?.title}');
      await _persistInboxMessage(message, isRead: true);
      final openedId = (message.data['notification_id']?.toString() ??
              message.data['id']?.toString() ??
              '')
          .trim();
      if (openedId.isNotEmpty) {
        await trackNotificationOpened(openedId);
      }
      _messageStreamController.add(message);
      _handleNotificationData(message.data);
    });

    // Message reçu quand l'app est complètement fermée
    FirebaseMessaging.instance
        .getInitialMessage()
        .then((RemoteMessage? message) async {
      if (message != null) {
        _log(
            '🚀 Notification cliquée (app fermée): ${message.notification?.title}');
        await _persistInboxMessage(message, isRead: true);
        final openedId = (message.data['notification_id']?.toString() ??
                message.data['id']?.toString() ??
                '')
            .trim();
        if (openedId.isNotEmpty) {
          await trackNotificationOpened(openedId);
        }
        _messageStreamController.add(message);
        _handleNotificationData(message.data);
      }
    });
  }

  Future<bool> trackNotificationOpened(String notificationId) async {
    try {
      final String id = notificationId.trim();
      if (id.isEmpty) {
        return false;
      }

      final prefs = await SharedPreferences.getInstance();
      final String? sharedApiToken = prefs.getString('api_token');
      final String? secureAuthToken =
          await _secureStorage.read(key: 'auth_token');
      final String? secureTokenAlt = await _secureStorage.read(key: 'token');
      final String? sharedTokenAlt = prefs.getString('token');
      String? apiToken =
          secureAuthToken ?? secureTokenAlt ?? sharedApiToken ?? sharedTokenAlt;
      final String? baseUrl =
          prefs.getString('api_base_url') ?? AppConstants.apiBaseUrl;

      if (apiToken == null || baseUrl == null) {
        return false;
      }

      apiToken = apiToken.trim();
      if (apiToken.toLowerCase().startsWith('bearer ')) {
        apiToken = apiToken.substring(7).trim();
      }
      if (apiToken.isEmpty) {
        return false;
      }

      final String normalizedBaseUrl = baseUrl.endsWith('/')
          ? baseUrl.substring(0, baseUrl.length - 1)
          : baseUrl;

      final String canonicalMobileBase = AppConstants.baseUrl.endsWith('/')
          ? AppConstants.baseUrl.substring(0, AppConstants.baseUrl.length - 1)
          : AppConstants.baseUrl;
      final String canonicalApiBase = AppConstants.apiBaseUrl.endsWith('/')
          ? AppConstants.apiBaseUrl
              .substring(0, AppConstants.apiBaseUrl.length - 1)
          : AppConstants.apiBaseUrl;

      String? baseOrigin;
      try {
        final uri = Uri.parse(normalizedBaseUrl);
        if (uri.scheme.isNotEmpty && uri.host.isNotEmpty) {
          baseOrigin = '${uri.scheme}://${uri.host}';
        }
      } catch (_) {
        baseOrigin = null;
      }

      final List<String> candidateEndpoints = [
        if (normalizedBaseUrl.endsWith('/api/mobile'))
          '$normalizedBaseUrl/push-notifications/$id/opened',
        if (normalizedBaseUrl.endsWith('/api'))
          '$normalizedBaseUrl/mobile/push-notifications/$id/opened',
        '$normalizedBaseUrl/api/mobile/push-notifications/$id/opened',
        if (baseOrigin != null)
          '$baseOrigin/api/mobile/push-notifications/$id/opened',
        if (baseOrigin != null)
          '$baseOrigin/legalnew/api/mobile/push-notifications/$id/opened',
        '$canonicalMobileBase/push-notifications/$id/opened',
        '$canonicalApiBase/mobile/push-notifications/$id/opened',
      ].toSet().toList();

      for (final endpoint in candidateEndpoints) {
        try {
          final response = await http.post(
            Uri.parse(endpoint),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': 'Bearer $apiToken',
            },
          );

          if (response.statusCode == 200) {
            return true;
          }
        } catch (_) {
          continue;
        }
      }

      return false;
    } catch (e) {
      _log('⚠️ Erreur tracking opened notification: $e');
      return false;
    }
  }

  Future<void> _persistInboxMessage(RemoteMessage message,
      {required bool isRead}) async {
    try {
      final String title = (message.notification?.title ??
              message.data['title']?.toString() ??
              'Notification')
          .trim();
      final String body = (_extractNotificationBody(message) ?? '').trim();
      final String notificationId = message.messageId ??
          '${DateTime.now().millisecondsSinceEpoch}_${title.hashCode}_${body.hashCode}';

      if (title.isEmpty && body.isEmpty) {
        return;
      }

      await _upsertInboxItem(
        PushInboxItem(
          id: notificationId,
          notificationId: message.data['notification_id']?.toString() ??
              message.data['id']?.toString(),
          title: title.isEmpty ? 'Notification' : title,
          body: body,
          receivedAt: DateTime.now(),
          isRead: isRead,
        ),
      );
    } catch (e) {
      _log(
          '⚠️ Impossible de persister la notification dans l\'inbox locale: $e');
    }
  }

  Future<void> _persistInboxFromPayloadMap(Map<String, dynamic> inboxMap,
      {required bool isRead}) async {
    try {
      final String id = inboxMap['id']?.toString().trim() ?? '';
      final String title = inboxMap['title']?.toString().trim() ?? '';
      final String body = inboxMap['body']?.toString().trim() ?? '';
      final DateTime receivedAt =
          DateTime.tryParse(inboxMap['receivedAt']?.toString() ?? '') ??
              DateTime.now();

      if (id.isEmpty && title.isEmpty && body.isEmpty) {
        return;
      }

      final String safeId = id.isNotEmpty
          ? id
          : '${receivedAt.millisecondsSinceEpoch}_${title.hashCode}_${body.hashCode}';

      await _upsertInboxItem(
        PushInboxItem(
          id: safeId,
          notificationId: inboxMap['notification_id']?.toString() ??
              inboxMap['id']?.toString(),
          title: title.isEmpty ? 'Notification' : title,
          body: body,
          receivedAt: receivedAt,
          isRead: isRead,
        ),
      );
    } catch (e) {
      _log('⚠️ Impossible de persister depuis payload local: $e');
    }
  }

  Future<void> _upsertInboxItem(PushInboxItem item) async {
    final prefs = await SharedPreferences.getInstance();
    final List<String> rawItems =
        prefs.getStringList(_inboxStorageKey) ?? <String>[];

    final List<PushInboxItem> items = rawItems
        .map((jsonStr) => PushInboxItem.fromJsonString(jsonStr))
        .whereType<PushInboxItem>()
        .toList();

    final int existingIndex = items.indexWhere((e) => e.id == item.id);
    if (existingIndex >= 0) {
      final existing = items[existingIndex];
      items[existingIndex] = existing.copyWith(
        id: item.id,
        notificationId: item.notificationId ?? existing.notificationId,
        title: item.title,
        body: item.body,
        receivedAt: item.receivedAt,
        isRead: existing.isRead || item.isRead,
      );
    } else {
      items.insert(0, item);
    }

    items.sort((a, b) => b.receivedAt.compareTo(a.receivedAt));
    final trimmed = items.take(_maxInboxItems).toList();

    await prefs.setStringList(
      _inboxStorageKey,
      trimmed.map((e) => e.toJsonString()).toList(),
    );
  }

  Future<List<PushInboxItem>> getInboxMessages() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final List<String> rawItems =
          prefs.getStringList(_inboxStorageKey) ?? <String>[];

      final items = rawItems
          .map((jsonStr) => PushInboxItem.fromJsonString(jsonStr))
          .whereType<PushInboxItem>()
          .toList()
        ..sort((a, b) => b.receivedAt.compareTo(a.receivedAt));

      return items;
    } catch (e) {
      _log('⚠️ Erreur lecture inbox notifications: $e');
      return <PushInboxItem>[];
    }
  }

  Future<int> getUnreadInboxCount() async {
    final items = await getInboxMessages();
    return items.where((item) => !item.isRead).length;
  }

  Future<void> markAllInboxAsRead() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final List<String> rawItems =
          prefs.getStringList(_inboxStorageKey) ?? <String>[];

      final List<PushInboxItem> items = rawItems
          .map((jsonStr) => PushInboxItem.fromJsonString(jsonStr))
          .whereType<PushInboxItem>()
          .map((item) => item.copyWith(isRead: true))
          .toList();

      await prefs.setStringList(
        _inboxStorageKey,
        items.map((e) => e.toJsonString()).toList(),
      );
    } catch (e) {
      _log('⚠️ Erreur markAllInboxAsRead: $e');
    }
  }

  /// Afficher une notification locale
  Future<void> _showLocalNotification(RemoteMessage message) async {
    final RemoteNotification? notification = message.notification;
    final String? fallbackBody = _extractNotificationBody(message);
    final String? title =
        notification?.title ?? message.data['title']?.toString();

    if (notification == null && (title == null || fallbackBody == null)) {
      return;
    }

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
      message.hashCode,
      title,
      fallbackBody ?? notification?.body,
      platformDetails,
      payload: jsonEncode({
        'data': message.data,
        '_dossy_inbox': {
          'id': message.messageId ??
              '${DateTime.now().millisecondsSinceEpoch}_${message.hashCode}',
          'notification_id': message.data['notification_id']?.toString(),
          'title': (title ?? 'Notification').trim(),
          'body': (fallbackBody ?? notification?.body ?? '').trim(),
          'receivedAt': DateTime.now().toIso8601String(),
        },
      }),
    );
  }

  Future<PushNotificationDetail?> fetchNotificationDetail(
      String notificationId) async {
    try {
      final String id = notificationId.trim();
      if (id.isEmpty) {
        return null;
      }

      final prefs = await SharedPreferences.getInstance();
      final String? sharedApiToken = prefs.getString('api_token');
      final String? secureAuthToken =
          await _secureStorage.read(key: 'auth_token');
      final String? secureTokenAlt = await _secureStorage.read(key: 'token');
      final String? sharedTokenAlt = prefs.getString('token');
      String? apiToken =
          secureAuthToken ?? secureTokenAlt ?? sharedApiToken ?? sharedTokenAlt;
      final String? baseUrl =
          prefs.getString('api_base_url') ?? AppConstants.apiBaseUrl;

      if (apiToken == null || baseUrl == null) {
        return null;
      }

      apiToken = apiToken.trim();
      if (apiToken.toLowerCase().startsWith('bearer ')) {
        apiToken = apiToken.substring(7).trim();
      }
      if (apiToken.isEmpty) {
        return null;
      }

      final String normalizedBaseUrl = baseUrl.endsWith('/')
          ? baseUrl.substring(0, baseUrl.length - 1)
          : baseUrl;

      final String canonicalMobileBase = AppConstants.baseUrl.endsWith('/')
          ? AppConstants.baseUrl.substring(0, AppConstants.baseUrl.length - 1)
          : AppConstants.baseUrl;
      final String canonicalApiBase = AppConstants.apiBaseUrl.endsWith('/')
          ? AppConstants.apiBaseUrl
              .substring(0, AppConstants.apiBaseUrl.length - 1)
          : AppConstants.apiBaseUrl;

      String? baseOrigin;
      try {
        final uri = Uri.parse(normalizedBaseUrl);
        if (uri.scheme.isNotEmpty && uri.host.isNotEmpty) {
          baseOrigin = '${uri.scheme}://${uri.host}';
        }
      } catch (_) {
        baseOrigin = null;
      }

      final List<String> candidateEndpoints = [
        if (normalizedBaseUrl.endsWith('/api/mobile'))
          '$normalizedBaseUrl/push-notifications/$id',
        if (normalizedBaseUrl.endsWith('/api'))
          '$normalizedBaseUrl/mobile/push-notifications/$id',
        '$normalizedBaseUrl/api/mobile/push-notifications/$id',
        if (baseOrigin != null) '$baseOrigin/api/mobile/push-notifications/$id',
        if (baseOrigin != null)
          '$baseOrigin/legalnew/api/mobile/push-notifications/$id',
        '$canonicalMobileBase/push-notifications/$id',
        '$canonicalApiBase/mobile/push-notifications/$id',
      ].toSet().toList();

      for (final endpoint in candidateEndpoints) {
        try {
          final response = await http.get(
            Uri.parse(endpoint),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': 'Bearer $apiToken',
            },
          );

          if (response.statusCode != 200) {
            continue;
          }

          final dynamic decoded = jsonDecode(response.body);
          if (decoded is! Map<String, dynamic>) {
            continue;
          }

          final dynamic rawData = decoded['data'];
          final Map<String, dynamic> data =
              rawData is Map<String, dynamic> ? rawData : decoded;

          return PushNotificationDetail(
            id: data['id']?.toString() ?? id,
            title: data['title']?.toString() ?? 'Notification',
            bodyHtml: data['body_html']?.toString() ?? '',
            bodyPlain: data['body_plain']?.toString() ?? '',
            sentAt: DateTime.tryParse(data['sent_at']?.toString() ?? ''),
          );
        } catch (_) {
          continue;
        }
      }

      return null;
    } catch (e) {
      _log('⚠️ Erreur fetch notification detail: $e');
      return null;
    }
  }

  String? _extractNotificationBody(RemoteMessage message) {
    final String? plainBody = message.data['plain_body']?.toString();
    if (plainBody != null && plainBody.trim().isNotEmpty) {
      return plainBody.trim();
    }

    final String? htmlBody = message.data['html_body']?.toString();
    if (htmlBody != null && htmlBody.trim().isNotEmpty) {
      return _htmlToPlainText(htmlBody);
    }

    return message.notification?.body;
  }

  String _htmlToPlainText(String html) {
    var text = html;

    text = text.replaceAll(RegExp(r'<\s*br\s*/?>', caseSensitive: false), '\n');
    text = text.replaceAll(
        RegExp(r'<\s*/\s*(p|div|h[1-6]|li)\s*>', caseSensitive: false), '\n');
    text =
        text.replaceAll(RegExp(r'<\s*li\b[^>]*>', caseSensitive: false), '- ');
    text = text.replaceAll(RegExp(r'<[^>]+>'), '');

    // Decode common entities without adding a new dependency.
    text = text
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&amp;', '&')
        .replaceAll('&lt;', '<')
        .replaceAll('&gt;', '>')
        .replaceAll('&quot;', '"')
        .replaceAll('&#39;', "'");

    text = text.replaceAll(RegExp(r'\r\n?|\n'), '\n');
    text = text.replaceAll(RegExp(r'[ \t]+'), ' ');
    text = text.replaceAll(RegExp(r'\n{3,}'), '\n\n').trim();

    return text;
  }

  /// Gérer le clic sur une notification
  void _handleNotificationTap(String? payload) {
    if (payload == null) return;

    try {
      final dynamic decoded = jsonDecode(payload);
      if (decoded is! Map<String, dynamic>) return;

      final Map<String, dynamic> root = decoded;

      final dynamic inboxData = root['_dossy_inbox'];
      if (inboxData is Map<String, dynamic>) {
        _persistInboxFromPayloadMap(inboxData, isRead: true);
      }

      final dynamic nestedData = root['data'];
      final Map<String, dynamic> data =
          nestedData is Map<String, dynamic> ? nestedData : root;
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

  /// Obtenir un token FCM robuste (Firebase peut répondre null juste après le boot).
  Future<String?> _getFcmTokenWithRetry({int maxAttempts = 5}) async {
    for (int attempt = 1; attempt <= maxAttempts; attempt++) {
      try {
        final token = await _firebaseMessaging.getToken();
        if (token != null && token.isNotEmpty) {
          return token;
        }
      } catch (e) {
        _log('⚠️ getToken failed (attempt $attempt/$maxAttempts): $e');
      }

      if (attempt < maxAttempts) {
        await Future.delayed(const Duration(milliseconds: 900));
      }
    }

    return null;
  }

  Future<void> _subscribeToUserTopicFromSession() async {
    try {
      final String? userJson = await _secureStorage.read(key: 'user_data');
      if (userJson == null || userJson.isEmpty) {
        final prefs = await SharedPreferences.getInstance();
        final String? fallbackUserId = prefs.getString('chat_user_id');
        if (fallbackUserId == null || fallbackUserId.trim().isEmpty) return;

        final String topic = 'user_${fallbackUserId.trim()}';
        await subscribeToTopic(topic);
        _log('✅ Topic utilisateur abonné (fallback prefs): $topic');
        return;
      }

      final dynamic decoded = jsonDecode(userJson);
      if (decoded is! Map<String, dynamic>) return;

      String userId = '';
      final List<dynamic> idCandidates = [
        decoded['id'],
        decoded['user_id'],
        decoded['userId'],
        (decoded['user'] is Map<String, dynamic>)
            ? (decoded['user'] as Map<String, dynamic>)['id']
            : null,
        (decoded['data'] is Map<String, dynamic>)
            ? (decoded['data'] as Map<String, dynamic>)['id']
            : null,
      ];

      for (final candidate in idCandidates) {
        final String value = candidate?.toString().trim() ?? '';
        if (value.isNotEmpty) {
          userId = value;
          break;
        }
      }

      if (userId.isEmpty) return;

      final String topic = 'user_$userId';
      await subscribeToTopic(topic);
      _log('✅ Topic utilisateur abonné: $topic');
    } catch (e) {
      _log('⚠️ Impossible de souscrire au topic utilisateur: $e');
    }
  }

  /// Envoyer le token FCM au backend Laravel
  Future<void> _sendTokenToBackend(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final String? sharedApiToken = prefs.getString('api_token');
      final String? secureAuthToken =
          await _secureStorage.read(key: 'auth_token');
      final String? secureTokenAlt = await _secureStorage.read(key: 'token');
      final String? sharedTokenAlt = prefs.getString('token');
      // Priorité au token sécurisé (source de vérité), puis fallback SharedPreferences.
      String? apiToken =
          secureAuthToken ?? secureTokenAlt ?? sharedApiToken ?? sharedTokenAlt;
      final String? baseUrl =
          prefs.getString('api_base_url') ?? AppConstants.apiBaseUrl;

      if (apiToken == null || baseUrl == null) {
        _log('⚠️ Pas de token API ou URL backend configurés');
        return;
      }

      apiToken = apiToken.trim();
      if (apiToken.toLowerCase().startsWith('bearer ')) {
        apiToken = apiToken.substring(7).trim();
      }

      if (apiToken.isEmpty) {
        _log('⚠️ Token API vide après normalisation');
        return;
      }

      final String normalizedBaseUrl = baseUrl.endsWith('/')
          ? baseUrl.substring(0, baseUrl.length - 1)
          : baseUrl;

      final String canonicalMobileBase = AppConstants.baseUrl.endsWith('/')
          ? AppConstants.baseUrl.substring(0, AppConstants.baseUrl.length - 1)
          : AppConstants.baseUrl;
      final String canonicalApiBase = AppConstants.apiBaseUrl.endsWith('/')
          ? AppConstants.apiBaseUrl
              .substring(0, AppConstants.apiBaseUrl.length - 1)
          : AppConstants.apiBaseUrl;

      String? baseOrigin;
      try {
        final uri = Uri.parse(normalizedBaseUrl);
        if (uri.scheme.isNotEmpty && uri.host.isNotEmpty) {
          baseOrigin = '${uri.scheme}://${uri.host}';
        }
      } catch (_) {
        baseOrigin = null;
      }

      final List<String> candidateEndpoints = [
        if (normalizedBaseUrl.endsWith('/api/mobile'))
          '$normalizedBaseUrl/fcm-token',
        if (normalizedBaseUrl.endsWith('/api/mobile'))
          '$normalizedBaseUrl/fcm-token/sync',
        if (normalizedBaseUrl.endsWith('/api'))
          '$normalizedBaseUrl/mobile/fcm-token',
        if (normalizedBaseUrl.endsWith('/api'))
          '$normalizedBaseUrl/mobile/fcm-token/sync',
        '$normalizedBaseUrl/api/mobile/fcm-token',
        '$normalizedBaseUrl/api/mobile/fcm-token/sync',
        if (baseOrigin != null) '$baseOrigin/api/mobile/fcm-token',
        if (baseOrigin != null) '$baseOrigin/api/mobile/fcm-token/sync',
        if (baseOrigin != null) '$baseOrigin/legalnew/api/mobile/fcm-token',
        if (baseOrigin != null)
          '$baseOrigin/legalnew/api/mobile/fcm-token/sync',
        if (baseOrigin != null) '$baseOrigin/legalnew/api/fcm-token',
        '$canonicalMobileBase/fcm-token',
        '$canonicalMobileBase/fcm-token/sync',
        '$canonicalApiBase/mobile/fcm-token',
        '$canonicalApiBase/mobile/fcm-token/sync',
      ].toSet().toList();

      bool delivered = false;
      final String platform = Platform.isAndroid ? 'android' : 'ios';

      for (final endpoint in candidateEndpoints) {
        for (int attempt = 1; attempt <= 3; attempt++) {
          final response = await http.post(
            Uri.parse(endpoint),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Authorization': 'Bearer $apiToken',
            },
            body: jsonEncode({
              'fcm_token': token,
              // Compat ancien backend/mobile qui utilisait "token"
              'token': token,
              'platform': platform,
              // Fallback auth pour endpoint sync si Authorization est supprimé par le proxy.
              'auth_token': apiToken,
            }),
          );

          if (response.statusCode == 200) {
            _log('✅ FCM Token envoyé au backend avec succès ($endpoint)');
            delivered = true;
            break;
          }

          _log(
            '❌ Échec envoi token (attempt $attempt/3) $endpoint: ${response.statusCode} ${response.body}',
          );

          if (attempt < 3) {
            await Future.delayed(const Duration(milliseconds: 800));
          }
        }

        if (delivered) break;
      }

      if (!delivered) {
        _log(
            '❌ Impossible d\'enregistrer le token FCM sur toutes les routes candidates');
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

class PushInboxItem {
  final String id;
  final String? notificationId;
  final String title;
  final String body;
  final DateTime receivedAt;
  final bool isRead;

  const PushInboxItem({
    required this.id,
    this.notificationId,
    required this.title,
    required this.body,
    required this.receivedAt,
    required this.isRead,
  });

  PushInboxItem copyWith({
    String? id,
    String? notificationId,
    String? title,
    String? body,
    DateTime? receivedAt,
    bool? isRead,
  }) {
    return PushInboxItem(
      id: id ?? this.id,
      notificationId: notificationId ?? this.notificationId,
      title: title ?? this.title,
      body: body ?? this.body,
      receivedAt: receivedAt ?? this.receivedAt,
      isRead: isRead ?? this.isRead,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'notificationId': notificationId,
      'title': title,
      'body': body,
      'receivedAt': receivedAt.toIso8601String(),
      'isRead': isRead,
    };
  }

  String toJsonString() => jsonEncode(toJson());

  static PushInboxItem? fromJsonString(String value) {
    try {
      final dynamic decoded = jsonDecode(value);
      if (decoded is! Map<String, dynamic>) return null;

      final String id = decoded['id']?.toString() ?? '';
      final String? notificationId = decoded['notificationId']?.toString();
      final String title = decoded['title']?.toString() ?? 'Notification';
      final String body = decoded['body']?.toString() ?? '';
      final String receivedAtRaw = decoded['receivedAt']?.toString() ?? '';
      final DateTime receivedAt =
          DateTime.tryParse(receivedAtRaw) ?? DateTime.now();
      final bool isRead = decoded['isRead'] == true;

      if (id.trim().isEmpty) return null;

      return PushInboxItem(
        id: id,
        notificationId: notificationId,
        title: title,
        body: body,
        receivedAt: receivedAt,
        isRead: isRead,
      );
    } catch (_) {
      return null;
    }
  }
}

class PushNotificationDetail {
  final String id;
  final String title;
  final String bodyHtml;
  final String bodyPlain;
  final DateTime? sentAt;

  const PushNotificationDetail({
    required this.id,
    required this.title,
    required this.bodyHtml,
    required this.bodyPlain,
    required this.sentAt,
  });
}

/// Handler pour les messages en arrière-plan (top-level function)
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // Firebase peut déjà être initialisé dans cet isolate.
  }

  if (kDebugMode) {
    debugPrint(
        '📬 Message reçu en arrière-plan: ${message.notification?.title}');
  }

  try {
    final service = PushNotificationService();
    final String title = (message.notification?.title ??
            message.data['title']?.toString() ??
            'Notification')
        .trim();
    final String body =
        (service._extractNotificationBody(message) ?? '').trim();

    if (title.isEmpty && body.isEmpty) return;

    final item = PushInboxItem(
      id: message.messageId ??
          '${DateTime.now().millisecondsSinceEpoch}_${title.hashCode}_${body.hashCode}',
      notificationId: message.data['notification_id']?.toString() ??
          message.data['id']?.toString(),
      title: title.isEmpty ? 'Notification' : title,
      body: body,
      receivedAt: DateTime.now(),
      isRead: false,
    );

    await _persistPushInboxItemInBackground(item);
  } catch (e) {
    if (kDebugMode) {
      debugPrint('⚠️ Erreur persistance inbox en arrière-plan: $e');
    }
  }
}

Future<void> _persistPushInboxItemInBackground(PushInboxItem item) async {
  final prefs = await SharedPreferences.getInstance();
  final List<String> rawItems =
      prefs.getStringList(PushNotificationService._inboxStorageKey) ??
          <String>[];

  final List<PushInboxItem> items = rawItems
      .map((jsonStr) => PushInboxItem.fromJsonString(jsonStr))
      .whereType<PushInboxItem>()
      .toList();

  final int existingIndex = items.indexWhere((e) => e.id == item.id);
  if (existingIndex >= 0) {
    final existing = items[existingIndex];
    items[existingIndex] = existing.copyWith(
      id: item.id,
      notificationId: item.notificationId ?? existing.notificationId,
      title: item.title,
      body: item.body,
      receivedAt: item.receivedAt,
      isRead: existing.isRead || item.isRead,
    );
  } else {
    items.insert(0, item);
  }

  items.sort((a, b) => b.receivedAt.compareTo(a.receivedAt));
  final trimmed = items.take(PushNotificationService._maxInboxItems).toList();

  await prefs.setStringList(
    PushNotificationService._inboxStorageKey,
    trimmed.map((e) => e.toJsonString()).toList(),
  );
}
