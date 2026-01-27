import '../services/firebase_service.dart';

/// Helper pour faciliter l'envoi d'événements Analytics
/// Centralise tous les événements trackés dans l'application
class AnalyticsHelper {
  static final FirebaseService _firebase = FirebaseService();

  // ========== AUTHENTIFICATION ==========

  static Future<void> logSignUp(String method) async {
    await _firebase.logEvent(
      name: 'sign_up',
      parameters: {'method': method},
    );
  }

  static Future<void> logLogin(String method) async {
    await _firebase.logEvent(
      name: 'login',
      parameters: {'method': method},
    );
  }

  static Future<void> logLogout() async {
    await _firebase.logEvent(name: 'logout');
  }

  // ========== RECHERCHE ==========

  static Future<void> logSearch({
    required String query,
    required String type, // 'fulltext' ou 'vector'
    String? jurisdiction,
    String? category,
    int? resultsCount,
  }) async {
    await _firebase.logEvent(
      name: 'search',
      parameters: {
        'search_term': query,
        'search_type': type,
        if (jurisdiction != null) 'jurisdiction': jurisdiction,
        if (category != null) 'category': category,
        if (resultsCount != null) 'results_count': resultsCount,
      },
    );
  }

  static Future<void> logSearchFilterApplied({
    String? jurisdiction,
    String? category,
    bool? dateFilter,
  }) async {
    await _firebase.logEvent(
      name: 'search_filter_applied',
      parameters: {
        if (jurisdiction != null) 'jurisdiction': jurisdiction,
        if (category != null) 'category': category,
        if (dateFilter != null) 'date_filter': dateFilter,
      },
    );
  }

  // ========== DOCUMENTS ==========

  static Future<void> logDocumentView(String documentId, String documentType) async {
    await _firebase.logEvent(
      name: 'view_item',
      parameters: {
        'item_id': documentId,
        'item_category': documentType,
      },
    );
  }

  static Future<void> logDocumentDownload(String documentId) async {
    await _firebase.logEvent(
      name: 'document_download',
      parameters: {'document_id': documentId},
    );
  }

  static Future<void> logDocumentShare(String documentId, String method) async {
    await _firebase.logEvent(
      name: 'share',
      parameters: {
        'content_type': 'document',
        'item_id': documentId,
        'method': method,
      },
    );
  }

  static Future<void> logDocumentFavorite(String documentId, bool added) async {
    await _firebase.logEvent(
      name: added ? 'add_to_wishlist' : 'remove_from_wishlist',
      parameters: {'item_id': documentId},
    );
  }

  // ========== CHAT IA ==========

  static Future<void> logChatMessage({
    required String messageType, // 'user' ou 'ai'
    required int conversationLength,
    String? aiModel,
  }) async {
    await _firebase.logEvent(
      name: 'chat_message',
      parameters: {
        'message_type': messageType,
        'conversation_length': conversationLength,
        if (aiModel != null) 'ai_model': aiModel,
      },
    );
  }

  static Future<void> logAIAnalysis({
    required String analysisType, // 'document', 'contract', 'anonymization'
    required String model,
    int? tokensUsed,
  }) async {
    await _firebase.logEvent(
      name: 'ai_analysis',
      parameters: {
        'analysis_type': analysisType,
        'model': model,
        if (tokensUsed != null) 'tokens_used': tokensUsed,
      },
    );
  }

  // ========== ABONNEMENTS ==========

  static Future<void> logViewSubscriptionPlans() async {
    await _firebase.logEvent(name: 'view_subscription_plans');
  }

  static Future<void> logSelectPlan(String planId, String planName, int price) async {
    await _firebase.logEvent(
      name: 'select_content',
      parameters: {
        'content_type': 'subscription_plan',
        'item_id': planId,
        'item_name': planName,
        'price': price,
      },
    );
  }

  static Future<void> logBeginCheckout({
    required String planId,
    required String planName,
    required int value,
    required String currency,
  }) async {
    await _firebase.logEvent(
      name: 'begin_checkout',
      parameters: {
        'currency': currency,
        'value': value,
        'items': [
          {
            'item_id': planId,
            'item_name': planName,
            'price': value,
          }
        ],
      },
    );
  }

  // ========== PAIEMENTS ==========

  static Future<void> logPurchase({
    required String transactionId,
    required String planId,
    required String planName,
    required int value,
    required String currency,
    required String paymentMethod,
  }) async {
    await _firebase.logEvent(
      name: 'purchase',
      parameters: {
        'transaction_id': transactionId,
        'currency': currency,
        'value': value,
        'payment_type': paymentMethod,
        'items': [
          {
            'item_id': planId,
            'item_name': planName,
            'price': value,
          }
        ],
      },
    );
  }

  static Future<void> logPaymentFailed({
    required String planId,
    required String reason,
    required String paymentMethod,
  }) async {
    await _firebase.logEvent(
      name: 'payment_failed',
      parameters: {
        'plan_id': planId,
        'reason': reason,
        'payment_method': paymentMethod,
      },
    );
  }

  // ========== OUTILS ÉTUDIANTS ==========

  static Future<void> logToolUsage(String toolName) async {
    await _firebase.logEvent(
      name: 'tool_usage',
      parameters: {'tool_name': toolName},
    );
  }

  static Future<void> logQCMGenerated({
    required int questionsCount,
    required String topic,
  }) async {
    await _firebase.logEvent(
      name: 'qcm_generated',
      parameters: {
        'questions_count': questionsCount,
        'topic': topic,
      },
    );
  }

  static Future<void> logFicheArretCreated(String caseType) async {
    await _firebase.logEvent(
      name: 'fiche_arret_created',
      parameters: {'case_type': caseType},
    );
  }

  // ========== PARRAINAGE ==========

  static Future<void> logReferralShared(String method) async {
    await _firebase.logEvent(
      name: 'share',
      parameters: {
        'content_type': 'referral_code',
        'method': method,
      },
    );
  }

  static Future<void> logReferralUsed(String referrerCode) async {
    await _firebase.logEvent(
      name: 'referral_used',
      parameters: {'referrer_code': referrerCode},
    );
  }

  // ========== NAVIGATION ==========

  static Future<void> logScreenView(String screenName) async {
    await _firebase.logScreenView(screenName: screenName);
  }

  // ========== ENGAGEMENT ==========

  static Future<void> logAppOpen() async {
    await _firebase.logEvent(name: 'app_open');
  }

  static Future<void> logSessionStart() async {
    await _firebase.logEvent(name: 'session_start');
  }

  static Future<void> logSessionEnd(int durationSeconds) async {
    await _firebase.logEvent(
      name: 'session_end',
      parameters: {'duration': durationSeconds},
    );
  }

  // ========== ERREURS ==========

  static Future<void> logError({
    required String errorType,
    required String errorMessage,
    String? stackTrace,
  }) async {
    await _firebase.logEvent(
      name: 'app_error',
      parameters: {
        'error_type': errorType,
        'error_message': errorMessage,
        if (stackTrace != null) 'stack_trace': stackTrace,
      },
    );
  }

  // ========== USER PROPERTIES ==========

  static Future<void> setUserProperties({
    String? subscriptionPlan,
    String? country,
    String? userType, // 'student', 'professional', 'cabinet'
  }) async {
    if (subscriptionPlan != null) {
      await _firebase.setUserProperty(
        name: 'subscription_plan',
        value: subscriptionPlan,
      );
    }
    if (country != null) {
      await _firebase.setUserProperty(
        name: 'country',
        value: country,
      );
    }
    if (userType != null) {
      await _firebase.setUserProperty(
        name: 'user_type',
        value: userType,
      );
    }
  }
}
