import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/constants/app_constants.dart';
import '../../core/utils/locale_detector.dart';

class LocaleProvider with ChangeNotifier {
  Locale _locale = const Locale('fr', 'FR');
  
  Locale get locale => _locale;
  
  // Initialize locale from storage or system
  Future<void> initialize() async {
    final prefs = await SharedPreferences.getInstance();
    final localeCode = prefs.getString(AppConstants.localeKey);
    
    if (localeCode != null) {
      // Utiliser la locale sauvegardée
      _locale = LocaleDetector.getLocaleFromLanguageCode(localeCode);
    } else {
      // Premier lancement: détecter la langue du système
      final detectedLanguage = LocaleDetector.detectSystemLanguage();
      _locale = LocaleDetector.getLocaleFromLanguageCode(detectedLanguage);
      // Sauvegarder automatiquement la langue détectée
      await prefs.setString(AppConstants.localeKey, detectedLanguage);
    }
    
    notifyListeners();
  }
  
  // Change locale
  Future<void> setLocale(String languageCode) async {
    _locale = LocaleDetector.getLocaleFromLanguageCode(languageCode);
    
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(AppConstants.localeKey, languageCode);
    
    notifyListeners();
  }
  
  // Toggle between FR and EN
  Future<void> toggleLocale() async {
    if (_locale.languageCode == 'fr') {
      await setLocale('en');
    } else {
      await setLocale('fr');
    }
  }
}
