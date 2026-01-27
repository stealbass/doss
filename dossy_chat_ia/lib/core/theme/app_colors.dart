import 'package:flutter/material.dart';

class AppColors {
  // Primary Colors (Green Theme)
  static const Color primary = Color(0xFF00C853); // Vert dominant
  static const Color primaryDark = Color(0xFF00A143);
  static const Color primaryLight = Color(0xFF5EFC82);
  static const Color primaryGreen = Color(0xFF00A86B); // Alias pour compatibilité
  
  // Secondary Colors
  static const Color secondary = Color(0xFF2196F3);
  static const Color secondaryDark = Color(0xFF1976D2);
  static const Color secondaryLight = Color(0xFF64B5F6);
  
  // Background Colors
  static const Color background = Color(0xFFF5F5F5);
  static const Color cardBackground = Color(0xFFFFFFFF);
  static const Color inputBackground = Color(0xFFF0F0F0);
  
  // Text Colors
  static const Color textPrimary = Color(0xFF212121);
  static const Color textSecondary = Color(0xFF757575);
  static const Color textHint = Color(0xFFBDBDBD);
  
  // Status Colors
  static const Color success = Color(0xFF4CAF50);
  static const Color warning = Color(0xFFFFA726);
  static const Color error = Color(0xFFF44336);
  static const Color info = Color(0xFF2196F3);
  
  // Subscription Plan Colors
  static const Color planGratuit = Color(0xFF9E9E9E);
  static const Color planEtudiant = Color(0xFF2196F3);
  static const Color planProfessionnel = Color(0xFF00C853);
  static const Color planCabinet = Color(0xFFFF9800);
  
  // Chat UI Colors
  static const Color userMessageBg = Color(0xFF00C853);
  static const Color aiMessageBg = Color(0xFFE8F5E9);
  static const Color userMessageText = Colors.white;
  static const Color aiMessageText = Color(0xFF212121);
  
  // Document Category Colors
  static const Color categoryDroitAffaires = Color(0xFF2196F3);
  static const Color categoryDroitTravail = Color(0xFF00C853);
  static const Color categoryDroitFiscal = Color(0xFFFF9800);
  static const Color categoryDroitCivil = Color(0xFF9C27B0);
  static const Color categoryDroitPenal = Color(0xFFF44336);
  static const Color categoryDroitAdministratif = Color(0xFF607D8B);
  static const Color categoryDroitSocial = Color(0xFF00BCD4);
  static const Color categoryDroitCommercial = Color(0xFF4CAF50);
  
  // Gradient Colors
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF00C853), Color(0xFF00A143)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  
  static const LinearGradient secondaryGradient = LinearGradient(
    colors: [Color(0xFF2196F3), Color(0xFF1976D2)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  
  static const LinearGradient greenGradient = LinearGradient(
    colors: [Color(0xFF5EFC82), Color(0xFF00C853), Color(0xFF00A143)],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );
  
  // Overlay Colors
  static const Color overlay = Color(0x80000000);
  static const Color shimmerBase = Color(0xFFE0E0E0);
  static const Color shimmerHighlight = Color(0xFFF5F5F5);
  
  // Border Colors
  static const Color border = Color(0xFFE0E0E0);
  static const Color divider = Color(0xFFEEEEEE);
  
  // Shadow Color
  static const Color shadow = Color(0x1A000000);
  
  // Helper Methods
  static Color getCategoryColor(String category) {
    switch (category.toLowerCase()) {
      case 'droit des affaires':
        return categoryDroitAffaires;
      case 'droit du travail':
        return categoryDroitTravail;
      case 'droit fiscal':
        return categoryDroitFiscal;
      case 'droit civil':
        return categoryDroitCivil;
      case 'droit pénal':
        return categoryDroitPenal;
      case 'droit administratif':
        return categoryDroitAdministratif;
      case 'droit social':
        return categoryDroitSocial;
      case 'droit commercial':
        return categoryDroitCommercial;
      default:
        return primary;
    }
  }
  
  static Color getPlanColor(String plan) {
    switch (plan.toLowerCase()) {
      case 'gratuit':
        return planGratuit;
      case 'étudiant':
        return planEtudiant;
      case 'professionnel':
        return planProfessionnel;
      case 'cabinet/entreprise':
      case 'cabinet':
      case 'entreprise':
        return planCabinet;
      default:
        return primary;
    }
  }
}
