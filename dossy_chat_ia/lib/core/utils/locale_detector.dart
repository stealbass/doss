import 'package:flutter/material.dart';
import 'dart:ui' as ui;

/// Classe utilitaire pour détecter la langue du système et gérer les locales
class LocaleDetector {
  /// Liste des langues supportées par l'application
  static const List<String> supportedLanguages = ['en', 'fr'];
  
  /// Langue par défaut
  static const String defaultLanguage = 'fr';
  
  /// Détecte la langue du système
  /// Retourne 'en', 'fr' ou la langue par défaut selon la langue du téléphone
  static String detectSystemLanguage() {
    final systemLocale = ui.window.locale;
    final languageCode = systemLocale.languageCode.toLowerCase();
    
    // Vérifier si la langue du système est supportée
    if (supportedLanguages.contains(languageCode)) {
      return languageCode;
    }
    
    // Retourner la langue par défaut si non supportée
    return defaultLanguage;
  }
  
  /// Crée une Locale basée sur le code langue
  static Locale getLocaleFromLanguageCode(String languageCode) {
    if (languageCode == 'en') {
      return const Locale('en', 'US');
    } else {
      return const Locale('fr', 'FR');
    }
  }
  
  /// Crée une Locale basée sur la langue détectée du système
  static Locale getSystemLocale() {
    final detectedLanguage = detectSystemLanguage();
    return getLocaleFromLanguageCode(detectedLanguage);
  }
  
  /// Obtient le nom de la langue en français
  static String getLanguageName(String languageCode) {
    switch (languageCode) {
      case 'en':
        return 'English';
      case 'fr':
        return 'Français';
      default:
        return 'Français';
    }
  }
  
  /// Obtient le drapeau emoji de la langue
  static String getLanguageFlag(String languageCode) {
    switch (languageCode) {
      case 'en':
        return '🇬🇧';
      case 'fr':
        return '🇫🇷';
      default:
        return '🇫🇷';
    }
  }
}
