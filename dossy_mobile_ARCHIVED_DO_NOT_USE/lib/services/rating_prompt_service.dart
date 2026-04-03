import 'package:in_app_review/in_app_review.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Service de gestion du prompt d'avis Play Store
/// Affiche automatiquement après N sessions ou M secondes d'utilisation
class RatingPromptService {
  static const String _prefLastRatingPrompt = 'last_rating_prompt_time';
  static const String _prefSessionCount = 'session_count';
  static const String _prefTotalUsageTime = 'total_usage_time'; // en secondes
  static const String _prefIsRatingGiven = 'rating_given';

  // Configuration
  static const int minSessionsBeforePrompt = 3; // Afficher après 3 sessions
  static const int minUsageTimeSeconds = 60; // Afficher après 60 secondes d'utilisation
  static const int cooldownDaysBetweenPrompts = 14; // Cooldown de 14 jours entre les prompts

  final InAppReview _inAppReview = InAppReview.instance;
  late SharedPreferences _prefs;

  RatingPromptService._();
  static final RatingPromptService _instance = RatingPromptService._();
  factory RatingPromptService() => _instance;

  /// Initialiser le service au démarrage de l'app
  Future<void> initialize() async {
    _prefs = await SharedPreferences.getInstance();
  }

  /// Incrémenter le compteur de session
  Future<void> recordNewSession() async {
    int sessionCount = _prefs.getInt(_prefSessionCount) ?? 0;
    await _prefs.setInt(_prefSessionCount, sessionCount + 1);
  }

  /// Enregistrer le temps d'utilisation
  Future<void> addUsageTime(int seconds) async {
    int totalTime = _prefs.getInt(_prefTotalUsageTime) ?? 0;
    await _prefs.setInt(_prefTotalUsageTime, totalTime + seconds);
  }

  /// Vérifier si l'app peut afficher le prompt d'avis
  Future<bool> shouldShowRatingPrompt() async {
    // Si l'utilisateur a déjà donné un avis, ne pas le demander à nouveau
    bool alreadyRated = _prefs.getBool(_prefIsRatingGiven) ?? false;
    if (alreadyRated) return false;

    // Vérifier le cooldown
    int? lastPromptTime = _prefs.getInt(_prefLastRatingPrompt);
    if (lastPromptTime != null) {
      int daysSinceLastPrompt =
          DateTime.now().difference(DateTime.fromMillisecondsSinceEpoch(lastPromptTime)).inDays;
      if (daysSinceLastPrompt < cooldownDaysBetweenPrompts) {
        return false;
      }
    }

    // Vérifier les conditions
    int sessionCount = _prefs.getInt(_prefSessionCount) ?? 0;
    int totalUsageTime = _prefs.getInt(_prefTotalUsageTime) ?? 0;

    return sessionCount >= minSessionsBeforePrompt && totalUsageTime >= minUsageTimeSeconds;
  }

  /// Afficher le prompt d'avis Play Store
  Future<void> showRatingPrompt() async {
    try {
      // Vérifier que les conditions sont remplies
      if (!await shouldShowRatingPrompt()) {
        return;
      }

      // Vérifier la disponibilité de In-App Review
      if (await _inAppReview.isAvailable()) {
        // Afficher le prompt natif
        await _inAppReview.requestReview();

        // Enregistrer que le prompt a été affiché
        await _prefs.setInt(_prefLastRatingPrompt, DateTime.now().millisecondsSinceEpoch);
        await _prefs.setBool(_prefIsRatingGiven, true);
      }
    } catch (e) {
      print('Erreur lors de l\'affichage du prompt d\'avis: $e');
    }
  }

  /// Afficher directement la page Play Store (fallback)
  Future<void> openPlayStoreForReview() async {
    try {
      await _inAppReview.openStoreListing(
        appId: 'com.dossy.legal', // Remplacer par votre package ID
      );
      await _prefs.setBool(_prefIsRatingGiven, true);
    } catch (e) {
      print('Erreur lors de l\'ouverture du Play Store: $e');
    }
  }

  /// Réinitialiser pour test (à supprimer en prod)
  Future<void> resetForTesting() async {
    await _prefs.remove(_prefLastRatingPrompt);
    await _prefs.remove(_prefSessionCount);
    await _prefs.remove(_prefTotalUsageTime);
    await _prefs.remove(_prefIsRatingGiven);
  }

  // Getters pour monitoring
  int get sessionCount => _prefs.getInt(_prefSessionCount) ?? 0;
  int get totalUsageTimeSeconds => _prefs.getInt(_prefTotalUsageTime) ?? 0;
  bool get isRatingGiven => _prefs.getBool(_prefIsRatingGiven) ?? false;
}
