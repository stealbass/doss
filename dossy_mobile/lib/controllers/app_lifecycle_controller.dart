import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../services/rating_prompt_service.dart';

/// Gestionnaire du cycle de vie et monitoring de l'app
/// S'occupe d'enregistrer les sessions et le temps d'utilisation
class AppLifecycleController extends GetxController with WidgetsBindingObserver {
  final RatingPromptService _ratingPrompt = RatingPromptService();
  late DateTime _appOpenedTime;
  late Timer _usageTimer;

  @override
  void onInit() async {
    super.onInit();
    WidgetsBinding.instance.addObserver(this);

    // Initialiser le service d'avis
    await _ratingPrompt.initialize();

    // Enregistrer une nouvelle session
    await _ratingPrompt.recordNewSession();

    // Démarrer le timer de suivi du temps
    _startUsageTimer();

    // Vérifier si on doit afficher le prompt d'avis
    _checkAndShowRatingPrompt();
  }

  @override
  void onClose() {
    WidgetsBinding.instance.removeObserver(this);
    _usageTimer.cancel();
    super.onClose();
  }

  /// Démarrer le timer qui enregistre le temps d'utilisation
  void _startUsageTimer() {
    _appOpenedTime = DateTime.now();
    
    // Enregistrer le temps toutes les 30 secondes
    _usageTimer = Timer.periodic(const Duration(seconds: 30), (timer) async {
      int elapsedSeconds = DateTime.now().difference(_appOpenedTime).inSeconds;
      await _ratingPrompt.addUsageTime(elapsedSeconds);
    });
  }

  /// Vérifier et afficher le prompt d'avis si les conditions sont remplies
  Future<void> _checkAndShowRatingPrompt() async {
    // Attendre un peu avant de vérifier (laisser le temps à l'UI de charger)
    await Future.delayed(const Duration(seconds: 2));

    if (await _ratingPrompt.shouldShowRatingPrompt()) {
      await _ratingPrompt.showRatingPrompt();
    }
  }

  /// Gérer les changements d'état de l'app (foreground/background)
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    switch (state) {
      case AppLifecycleState.resumed:
        print('App reprise au premier plan');
        // Optionnel : vérifier à nouveau si on doit afficher le prompt
        _checkAndShowRatingPrompt();
        break;

      case AppLifecycleState.paused:
        print('App mise en pause');
        // Enregistrer le temps final
        int elapsedSeconds = DateTime.now().difference(_appOpenedTime).inSeconds;
        _ratingPrompt.addUsageTime(elapsedSeconds);
        break;

      case AppLifecycleState.detached:
      case AppLifecycleState.inactive:
        break;
    }
  }

  /// Forcer l'affichage du prompt (pour test/debug)
  Future<void> showRatingPromptDebug() async {
    await _ratingPrompt.showRatingPrompt();
  }

  /// Ouvrir directement le Play Store pour laisser un avis
  Future<void> openPlayStoreReview() async {
    await _ratingPrompt.openPlayStoreForReview();
  }

  /// Réinitialiser les données (test uniquement)
  Future<void> resetRatingPromptForTesting() async {
    await _ratingPrompt.resetForTesting();
  }

  // Getters pour UI debugging
  int get sessionCount => _ratingPrompt.sessionCount;
  int get usageTimeSeconds => _ratingPrompt.totalUsageTimeSeconds;
  bool get isRatingGiven => _ratingPrompt.isRatingGiven;
}
