import 'package:flutter/foundation.dart';
import 'package:in_app_review/in_app_review.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Service de gestion du prompt d'avis Play Store.
class RatingPromptService {
  static const String _prefLastRatingPrompt = 'last_rating_prompt_time';
  static const String _prefSessionCount = 'session_count';
  static const String _prefTotalUsageTime = 'total_usage_time';
  static const String _prefIsRatingGiven = 'rating_given';

  // Configuration
  static const int minSessionsBeforePrompt = 3;
  static const int minUsageTimeSeconds = 60;
  static const int cooldownDaysBetweenPrompts = 14;

  final InAppReview _inAppReview = InAppReview.instance;
  SharedPreferences? _prefs;

  RatingPromptService();

  Future<void> initialize() async {
    _prefs ??= await SharedPreferences.getInstance();
  }

  Future<void> recordNewSession() async {
    if (_prefs == null) return;
    final sessionCount = _prefs!.getInt(_prefSessionCount) ?? 0;
    await _prefs!.setInt(_prefSessionCount, sessionCount + 1);
  }

  Future<void> addUsageTime(int seconds) async {
    if (_prefs == null || seconds <= 0) return;
    final totalTime = _prefs!.getInt(_prefTotalUsageTime) ?? 0;
    await _prefs!.setInt(_prefTotalUsageTime, totalTime + seconds);
  }

  Future<bool> shouldShowRatingPrompt() async {
    if (_prefs == null) return false;

    final alreadyRated = _prefs!.getBool(_prefIsRatingGiven) ?? false;
    if (alreadyRated) return false;

    final lastPromptTime = _prefs!.getInt(_prefLastRatingPrompt);
    if (lastPromptTime != null) {
      final daysSinceLastPrompt = DateTime.now()
          .difference(DateTime.fromMillisecondsSinceEpoch(lastPromptTime))
          .inDays;
      if (daysSinceLastPrompt < cooldownDaysBetweenPrompts) {
        return false;
      }
    }

    final sessionCount = _prefs!.getInt(_prefSessionCount) ?? 0;
    final totalUsageTime = _prefs!.getInt(_prefTotalUsageTime) ?? 0;

    return sessionCount >= minSessionsBeforePrompt &&
        totalUsageTime >= minUsageTimeSeconds;
  }

  Future<void> showRatingPrompt() async {
    if (!kReleaseMode) return;
    if (_prefs == null) return;

    try {
      if (!await shouldShowRatingPrompt()) return;

      if (await _inAppReview.isAvailable()) {
        await _inAppReview.requestReview();
        await _prefs!
            .setInt(_prefLastRatingPrompt, DateTime.now().millisecondsSinceEpoch);
        await _prefs!.setBool(_prefIsRatingGiven, true);
      }
    } catch (e) {
      debugPrint('Erreur lors du prompt d\'avis: $e');
    }
  }

  Future<void> openPlayStoreForReview() async {
    if (_prefs == null) return;
    try {
      await _inAppReview.openStoreListing();
      await _prefs!.setBool(_prefIsRatingGiven, true);
    } catch (e) {
      debugPrint('Erreur ouverture Play Store: $e');
    }
  }
}
