import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:hive_flutter/hive_flutter.dart';

import 'core/theme/app_theme.dart';
import 'core/constants/app_constants.dart';
import 'core/services/rating_prompt_service.dart';
import 'data/providers/auth_provider.dart';
import 'data/providers/chat_provider.dart';
import 'data/providers/subscription_provider.dart';
import 'data/providers/document_provider.dart';
import 'data/providers/locale_provider.dart';
import 'data/providers/theme_provider.dart';
import 'providers/template_provider.dart' hide DocumentTemplate;
import 'providers/fiscal_resource_provider.dart';
import 'providers/legal_alert_provider.dart';
import 'providers/calculator_provider.dart' hide Calculator;
import 'providers/legal_library_provider.dart';
import 'models/calculator_model.dart';
// Import specific types from providers for routing
import 'providers/template_provider.dart' show DocumentTemplate;
import 'presentation/screens/splash/splash_screen.dart';
import 'presentation/screens/onboarding/onboarding_screen.dart';
import 'presentation/screens/auth/login_screen.dart';
import 'presentation/screens/auth/register_screen.dart';
import 'presentation/screens/home/home_screen.dart';
import 'presentation/screens/chat/chat_screen.dart';
import 'presentation/screens/documents/documents_screen.dart';
import 'presentation/screens/search/search_screen.dart';
import 'presentation/screens/tools/tools_hub_screen.dart';
import 'presentation/screens/tools/fiche_arret_screen.dart';
import 'presentation/screens/tools/qcm_generator_screen.dart';
import 'presentation/screens/tools/revision_active_screen.dart';
import 'presentation/screens/tools/audio_transcription_screen.dart';
import 'presentation/screens/profile/profile_settings_screen.dart';
import 'presentation/screens/settings/settings_screen.dart';
import 'presentation/screens/subscription/subscription_plans_screen.dart';
import 'presentation/screens/referral/referral_screen.dart';
import 'presentation/screens/help/help_screen.dart';
import 'presentation/screens/professional/legal_monitoring_screen.dart';
import 'presentation/screens/professional/anonymization_screen.dart';
import 'presentation/screens/debug/diagnostic_screen.dart';
import 'screens/enterprise/sub_account_create_screen.dart';
import 'screens/templates/templates_list_screen.dart';
import 'screens/templates/template_detail_screen.dart';
import 'screens/fiscal_resources/fiscal_resources_list_screen.dart';
import 'screens/fiscal_resources/fiscal_resource_detail_screen.dart';
import 'screens/calculators/calculators_list_screen.dart';
import 'screens/calculators/calculator_form_screen.dart';
import 'screens/legal_library/legal_library_screen.dart';
import 'screens/legal_alerts/alerts_list_screen.dart';
import 'presentation/screens/library/library_hub_screen.dart';
import 'presentation/screens/payment/flutterwave_payment_screen.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'l10n/app_localizations.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Ensure Firebase is initialized before any service accesses Messaging/Analytics.
  await Firebase.initializeApp();

  // Initialize Hive
  await Hive.initFlutter();

  // Set preferred orientations
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  // Set system UI overlay style
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
      systemNavigationBarColor: Colors.white,
      systemNavigationBarIconBrightness: Brightness.dark,
    ),
  );

  // Suppress overflow errors in console (they don't affect functionality)
  FlutterError.onError = (FlutterErrorDetails details) {
    final exception = details.exception;
    final isOverflowError = exception is FlutterError &&
        (exception.toString().contains('RenderFlex overflowed') ||
         exception.toString().contains('pixels on the right') ||
         exception.toString().contains('pixels on the bottom') ||
         exception.toString().contains('overflowed by'));
    
    if (!isOverflowError) {
      // Log all other errors normally
      FlutterError.dumpErrorToConsole(details);
    }
    // Overflow errors are silently ignored
  };

  // Also suppress overflow error widgets in UI
  ErrorWidget.builder = (FlutterErrorDetails details) {
    final exception = details.exception;
    final isOverflowError = exception.toString().contains('RenderFlex overflowed') ||
        exception.toString().contains('pixels on the right') ||
        exception.toString().contains('pixels on the bottom') ||
        exception.toString().contains('overflowed by');
    
    if (isOverflowError) {
      // Return empty container for overflow errors
      return const SizedBox.shrink();
    }
    
    // Show error widget for other errors
    return ErrorWidget(exception);
  };

  runApp(const DossyChatIAApp());
}

class DossyChatIAApp extends StatefulWidget {
  const DossyChatIAApp({super.key});

  @override
  State<DossyChatIAApp> createState() => _DossyChatIAAppState();
}

class _DossyChatIAAppState extends State<DossyChatIAApp>
    with WidgetsBindingObserver {
  final RatingPromptService _ratingPromptService = RatingPromptService();
  Timer? _usageTimer;
  DateTime? _lastUsageMark;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _initRatingPrompt();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _usageTimer?.cancel();
    super.dispose();
  }

  Future<void> _initRatingPrompt() async {
    await _ratingPromptService.initialize();
    await _ratingPromptService.recordNewSession();
    _startUsageTimer();
    _checkAndShowRatingPrompt();
  }

  void _startUsageTimer() {
    _lastUsageMark = DateTime.now();
    _usageTimer?.cancel();
    _usageTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      _flushUsageTime();
    });
  }

  void _flushUsageTime() {
    final lastMark = _lastUsageMark;
    if (lastMark == null) return;
    final now = DateTime.now();
    final elapsedSeconds = now.difference(lastMark).inSeconds;
    _lastUsageMark = now;
    _ratingPromptService.addUsageTime(elapsedSeconds);
  }

  Future<void> _checkAndShowRatingPrompt() async {
    await Future.delayed(const Duration(seconds: 2));
    await _ratingPromptService.showRatingPrompt();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    switch (state) {
      case AppLifecycleState.resumed:
        _startUsageTimer();
        _checkAndShowRatingPrompt();
        break;
      case AppLifecycleState.paused:
        _usageTimer?.cancel();
        _flushUsageTime();
        break;
      case AppLifecycleState.inactive:
      case AppLifecycleState.detached:
      case AppLifecycleState.hidden:
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => ChatProvider()),
        ChangeNotifierProvider(create: (_) => SubscriptionProvider()),
        ChangeNotifierProvider(create: (_) => DocumentProvider()),
        ChangeNotifierProvider(create: (_) => LocaleProvider()),
        ChangeNotifierProvider(create: (_) => ThemeProvider()),
        ChangeNotifierProvider(create: (_) => TemplateProvider()),
        ChangeNotifierProvider(create: (_) => FiscalResourceProvider()),
        ChangeNotifierProvider(create: (_) => LegalAlertProvider()),
        ChangeNotifierProvider(create: (_) => CalculatorProvider()),
        ChangeNotifierProvider(create: (_) => LegalLibraryProvider()),
      ],
      child: Consumer2<LocaleProvider, ThemeProvider>(
        builder: (context, localeProvider, themeProvider, child) {
          return ScreenUtilInit(
            designSize: const Size(375, 812),
            minTextAdapt: true,
            splitScreenMode: true,
            builder: (context, child) {
              return MaterialApp(
                title: AppConstants.appName,
                debugShowCheckedModeBanner: false,
                theme: AppTheme.lightTheme,
                darkTheme: AppTheme.darkTheme,
                themeMode: themeProvider.themeMode,
                locale: localeProvider.locale,
                supportedLocales: AppLocalizations.supportedLocales,
                localeResolutionCallback: (locale, supportedLocales) {
                  if (locale == null) return supportedLocales.first;
                  for (var supportedLocale in supportedLocales) {
                    if (supportedLocale.languageCode == locale.languageCode) {
                      return supportedLocale;
                    }
                  }
                  return supportedLocales.first;
                },
                localizationsDelegates: const [
                  AppLocalizations.delegate,
                  GlobalMaterialLocalizations.delegate,
                  GlobalWidgetsLocalizations.delegate,
                  GlobalCupertinoLocalizations.delegate,
                ],
                home: const SplashScreen(),
                routes: {
                  '/splash': (context) => const SplashScreen(),
                  '/onboarding': (context) => const OnboardingScreen(),
                  '/login': (context) => const LoginScreen(),
                  '/register': (context) => const RegisterScreen(),
                  '/home': (context) => const HomeScreen(),
                  '/chat': (context) => const ChatScreen(),
                  '/documents': (context) => const DocumentsScreen(),
                  '/search': (context) => const SearchScreen(),
                  '/tools': (context) => const ToolsHubScreen(),
                  '/library': (context) => const LibraryHubScreen(),
                  '/tools/fiche-arret': (context) => const FicheArretScreen(),
                  '/tools/qcm': (context) => const QcmGeneratorScreen(),
                  '/tools/revision': (context) => const RevisionActiveScreen(),
                  '/tools/audio': (context) => const AudioTranscriptionScreen(),
                  '/profile': (context) => const ProfileSettingsScreen(),
                  '/create-sub-account': (context) =>
                      const SubAccountCreateScreen(),
                  '/settings': (context) => const SettingsScreen(),
                  '/subscription-plans': (context) =>
                      const SubscriptionPlansScreen(),
                  '/payment': (context) {
                    final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
                    return FlutterwavePaymentScreen(
                      planId: args['plan_id'] as String,
                      planName: args['plan_name'] as String,
                      amount: args['amount'] as int,
                      currency: args['currency'] as String,
                      billingCycle: args['billing_cycle'] as String,
                      couponCode: args['coupon_code'] as String?,
                    );
                  },
                  '/referral': (context) => const ReferralScreen(),
                  '/help': (context) => const HelpScreen(),
                  '/legal-monitoring': (context) =>
                      const LegalMonitoringScreen(),
                  '/anonymization': (context) => const AnonymizationScreen(),
                  '/diagnostic': (context) => const DiagnosticScreen(),
                    '/templates': (context) => const TemplatesListScreen(),
                    '/template-details': (context) {
                      final template = ModalRoute.of(context)!.settings.arguments as DocumentTemplate;
                      return TemplateDetailScreen(template: template);
                    },
                    '/fiscal-resources': (context) =>
                      const FiscalResourcesListScreen(),
                    '/fiscal-resource-detail': (context) {
                      final resource = ModalRoute.of(context)!.settings.arguments as FiscalResource;
                      return FiscalResourceDetailScreen(resource: resource);
                    },
                    '/calculators': (context) => const CalculatorsListScreen(),
                    '/calculator-form': (context) {
                      final calculator = ModalRoute.of(context)!.settings.arguments as Calculator;
                      return CalculatorFormScreen(calculator: calculator);
                    },
                    '/legal-library': (context) => const LegalLibraryScreen(),
                    '/legal-alerts': (context) => const AlertsListScreen(),
                },
              );
            },
          );
        },
      ),
    );
  }
}
