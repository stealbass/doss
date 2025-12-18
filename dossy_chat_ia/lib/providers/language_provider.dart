import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LanguageProvider with ChangeNotifier {
  Locale _locale = const Locale('fr', 'FR'); // Default French
  static const String _languagePrefsKey = 'selected_language';

  Locale get locale => _locale;

  LanguageProvider() {
    _loadSavedLanguage();
  }

  Future<void> _loadSavedLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    final languageCode = prefs.getString(_languagePrefsKey) ?? 'fr';
    _locale = Locale(languageCode, languageCode == 'fr' ? 'FR' : 'US');
    notifyListeners();
  }

  Future<void> setLocale(Locale locale) async {
    if (_locale == locale) return;
    
    _locale = locale;
    notifyListeners();
    
    // Save to preferences
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_languagePrefsKey, locale.languageCode);
  }

  void setFrench() {
    setLocale(const Locale('fr', 'FR'));
  }

  void setEnglish() {
    setLocale(const Locale('en', 'US'));
  }

  bool get isFrench => _locale.languageCode == 'fr';
  bool get isEnglish => _locale.languageCode == 'en';

  String get currentLanguageName => isFrench ? 'Français' : 'English';
}
