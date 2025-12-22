class AppConstants {
  // App Information
  static const String appName = 'DOSSY CHAT IA';
  static const String appSlogan = 'Analyse et Assistant Juridique, Fiscal & Social';
  static const String appVersion = '1.0.0';
  
  // API Configuration
  static const String baseUrl = 'https://dossy.alwaysdata.net/api/mobile';
  static const String apiBaseUrl = 'https://dossypro.com/api'; // Pour search_service
  static const String filesBaseUrl = 'https://files.dossypro.com';
  
  // Couleur primaire verte (pour les écrans qui l'utilisent)
  static const int primaryGreenValue = 0xFF00A86B;
  
  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String localeKey = 'app_locale';
  static const String themeKey = 'app_theme';
  static const String onboardingKey = 'onboarding_completed';
  static const String jurisdictionKey = 'selected_jurisdiction';
  
  // Subscription Plans
  static const List<String> plans = [
    'Gratuit',
    'Étudiant',
    'Professionnel',
    'Cabinet/Entreprise',
  ];
  
  // African Francophone Countries (14 countries supported)
  // Organized by region for better UX
  static const List<Map<String, String>> countries = [
    // West Africa (UEMOA - 8 countries)
    {'code': 'BJ', 'name': 'Bénin', 'flag': '🇧🇯', 'region': 'West Africa'},
    {'code': 'BF', 'name': 'Burkina Faso', 'flag': '🇧🇫', 'region': 'West Africa'},
    {'code': 'CI', 'name': 'Côte d\'Ivoire', 'flag': '🇨🇮', 'region': 'West Africa'},
    {'code': 'GW', 'name': 'Guinée-Bissau', 'flag': '🇬🇼', 'region': 'West Africa'},
    {'code': 'ML', 'name': 'Mali', 'flag': '🇲🇱', 'region': 'West Africa'},
    {'code': 'NE', 'name': 'Niger', 'flag': '🇳🇪', 'region': 'West Africa'},
    {'code': 'SN', 'name': 'Sénégal', 'flag': '🇸🇳', 'region': 'West Africa'},
    {'code': 'TG', 'name': 'Togo', 'flag': '🇹🇬', 'region': 'West Africa'},
    
    // Central Africa (CEMAC - 3 countries)
    {'code': 'CM', 'name': 'Cameroun', 'flag': '🇨🇲', 'region': 'Central Africa'},
    {'code': 'CD', 'name': 'RD Congo', 'flag': '🇨🇩', 'region': 'Central Africa'},
    {'code': 'GA', 'name': 'Gabon', 'flag': '🇬🇦', 'region': 'Central Africa'},
    
    // Indian Ocean (1 country)
    {'code': 'MG', 'name': 'Madagascar', 'flag': '🇲🇬', 'region': 'Indian Ocean'},
    
    // North Africa (2 countries)
    {'code': 'MA', 'name': 'Maroc', 'flag': '🇲🇦', 'region': 'North Africa'},
    {'code': 'TN', 'name': 'Tunisie', 'flag': '🇹🇳', 'region': 'North Africa'},
  ];
  
  // Legal Categories
  static const List<String> legalCategories = [
    'Droit des affaires',
    'Droit du travail',
    'Droit fiscal',
    'Droit civil',
    'Droit pénal',
    'Droit administratif',
    'Droit social',
    'Droit commercial',
  ];
  
  // Jurisdictions (pour search_filter_widget)
  static const List<String> jurisdictions = [
    'Bénin',
    'Burkina Faso',
    'Côte d\'Ivoire',
    'Guinée-Bissau',
    'Mali',
    'Niger',
    'Sénégal',
    'Togo',
    'Cameroun',
    'RD Congo',
    'Gabon',
    'Madagascar',
    'Maroc',
    'Tunisie',
  ];
  
  // Student Tools
  static const List<Map<String, dynamic>> studentTools = [
    {
      'id': 'fiche_arret',
      'name': 'Fiche d\'Arrêt',
      'icon': '📋',
      'description': 'Générez automatiquement une fiche d\'arrêt complète',
    },
    {
      'id': 'fiche_revision',
      'name': 'Fiche de Révision',
      'icon': '📚',
      'description': 'Créez des fiches de révision synthétiques',
    },
    {
      'id': 'dissertation',
      'name': 'Plan de Dissertation',
      'icon': '✍️',
      'description': 'Problématique et plan détaillé de dissertation',
    },
    {
      'id': 'qcm',
      'name': 'QCM',
      'icon': '❓',
      'description': 'Quiz à choix multiples pour tester vos connaissances',
    },
    {
      'id': 'revision_active',
      'name': 'Révision Active',
      'icon': '🎯',
      'description': 'Questions guidées pour révision interactive',
    },
  ];
  
  // Document Templates (Enterprise)
  static const List<Map<String, dynamic>> documentTemplates = [
    {
      'category': 'RH & Paie',
      'templates': [
        'Contrat CDI',
        'Contrat CDD',
        'Contrat Consultant',
        'Lettre d\'Avertissement',
        'Lettre de Licenciement',
        'Règlement Intérieur',
        'Simulateur Coût d\'Embauche',
        'Calculateur Indemnités',
      ]
    },
    {
      'category': 'Fiscal & Comptable',
      'templates': [
        'Lettre de Réclamation',
        'Demande de Moratoire',
        'Checklist Contrôle Fiscal',
        'Calendrier Fiscal',
      ]
    },
    {
      'category': 'Sociétés',
      'templates': [
        'PV d\'Assemblée Générale',
        'Rapport de Gestion',
        'Conventions Réglementées',
      ]
    },
  ];
  
  // Chat Prompt Suggestions
  static const List<String> chatPromptSuggestions = [
    'Expliquez-moi les conditions de validité d\'un contrat OHADA',
    'Quels sont les délais de prescription en droit civil camerounais ?',
    'Comment calculer les indemnités de licenciement ?',
    'Quelles sont les obligations fiscales d\'une SARL ?',
    'Rédigez un modèle de contrat de bail commercial',
  ];
  
  // Subscription Plan Pricing (FCFA)
  static const Map<String, int> planPrices = {
    'Gratuit': 0,
    'Étudiant': 2500,
    'Professionnel': 5000,
    'Cabinet/Entreprise': 15000,
  };

  // Feature Limits by Plan
  static const Map<String, Map<String, dynamic>> planLimits = {
    'Gratuit': {
      'price': 0,
      'searches': 5,
      'analyses': 2,
      'downloads': 0,
      'ai_messages': 10,
      'audio_transcription': false,
      'anonymization': false,
      'multi_accounts': false,
      'max_sub_accounts': 0,
      'legal_alerts': false,
      'word_export': false,
      'access_templates': false,
      'access_fiscal_resources': false,
      'access_calculators': false,
      'advanced_ai': false,
      'priority_support': false,
    },
    'Étudiant': {
      'price': 2500,
      'searches': 50,
      'analyses': 20,
      'downloads': 10,
      'ai_messages': 100,
      'audio_transcription': true,
      'anonymization': false,
      'multi_accounts': false,
      'max_sub_accounts': 0,
      'legal_alerts': false,
      'word_export': false,
      'access_templates': false,
      'access_fiscal_resources': false,
      'access_calculators': false,
      'advanced_ai': false,
      'priority_support': false,
      'badge': 'Populaire',
    },
    'Professionnel': {
      'price': 5000,
      'searches': 200,
      'analyses': 100,
      'downloads': 50,
      'ai_messages': 500,
      'audio_transcription': true,
      'anonymization': true,
      'multi_accounts': false,
      'max_sub_accounts': 0,
      'legal_alerts': true,
      'word_export': true,
      'access_templates': true,
      'access_fiscal_resources': true,
      'access_calculators': true,
      'advanced_ai': true,
      'priority_support': false,
    },
    'Cabinet/Entreprise': {
      'price': 15000,
      'searches': -1, // unlimited
      'analyses': -1,
      'downloads': -1,
      'ai_messages': -1,
      'audio_transcription': true,
      'anonymization': true,
      'multi_accounts': true,
      'max_sub_accounts': 10,
      'legal_alerts': true,
      'word_export': true,
      'access_templates': true,
      'access_fiscal_resources': true,
      'access_calculators': true,
      'access_premium_templates': true,
      'advanced_ai': true,
      'priority_support': true,
    },
  };
  
  // Referral Configuration
  static const int referralThreshold = 10;
  static const double referralBonus = 5000; // XAF
  
  // Email Templates Categories
  static const List<String> emailTemplateCategories = [
    'Bienvenue',
    'Promotion',
    'Relance Inactivité',
    'Renouvellement',
    'Remerciement Parrainage',
  ];
}
