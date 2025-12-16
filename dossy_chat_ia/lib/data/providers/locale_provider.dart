import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/constants/app_constants.dart';

class LocaleProvider with ChangeNotifier {
  Locale _locale = const Locale('fr', 'FR');
  
  Locale get locale => _locale;
  
  // Initialize locale from storage
  Future<void> initialize() async {
    final prefs = await SharedPreferences.getInstance();
    final localeCode = prefs.getString(AppConstants.localeKey);
    
    if (localeCode != null) {
      _locale = localeCode == 'en'
          ? const Locale('en', 'US')
          : const Locale('fr', 'FR');
      notifyListeners();
    }
  }
  
  // Change locale
  Future<void> setLocale(String languageCode) async {
    _locale = languageCode == 'en'
        ? const Locale('en', 'US')
        : const Locale('fr', 'FR');
    
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
