import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

import 'core/theme/app_theme.dart';
import 'core/constants/app_constants.dart';
import 'core/services/firebase_service.dart';
import 'core/utils/network_utils.dart';
import 'data/providers/auth_provider.dart';
import 'data/providers/chat_provider.dart';
import 'data/providers/subscription_provider.dart';
import 'data/providers/document_provider.dart';
import 'data/providers/locale_provider.dart';
import 'data/providers/theme_provider.dart';
import 'presentation/screens/splash/splash_screen.dart';
import 'presentation/screens/onboarding/onboarding_screen.dart';
import 'presentation/screens/auth/login_screen.dart';
import 'presentation/screens/auth/register_screen.dart';
import 'presentation/screens/home/home_screen.dart';
import 'presentation/screens/subscription/subscription_plans_screen.dart';
import 'presentation/screens/search/search_screen.dart';
import 'presentation/screens/tools/fiche_arret_screen.dart';
import 'presentation/screens/tools/qcm_generator_screen.dart';
import 'presentation/screens/tools/revision_active_screen.dart';
import 'presentation/screens/tools/audio_transcription_screen.dart';
import 'presentation/screens/referral/referral_screen.dart';
import 'presentation/screens/professional/anonymization_screen.dart';
import 'presentation/screens/professional/legal_monitoring_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Hive
  await Hive.initFlutter();
  
  // Initialize Firebase
  await FirebaseService().initialize();
  
  // Setup Firebase background message handler
  FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
  
  // Initialize Network monitoring
  await NetworkUtils().initialize();
  
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
  
  runApp(const DossyChatIAApp());
}

class DossyChatIAApp extends StatelessWidget {
  const DossyChatIAApp({super.key});

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
                supportedLocales: const [
                  Locale('fr', 'FR'),
                  Locale('en', 'US'),
                ],
                home: const SplashScreen(),
                routes: {
                  '/splash': (context) => const SplashScreen(),
                  '/onboarding': (context) => const OnboardingScreen(),
                  '/login': (context) => const LoginScreen(),
                  '/register': (context) => const RegisterScreen(),
                  '/home': (context) => const HomeScreen(),
                  '/search': (context) => const SearchScreen(),
                  '/subscription-plans': (context) => const SubscriptionPlansScreen(),
                  '/fiche-arret': (context) => const FicheArretScreen(),
                  '/qcm-generator': (context) => const QcmGeneratorScreen(),
                  '/revision-active': (context) => const RevisionActiveScreen(),
                  '/audio-transcription': (context) => const AudioTranscriptionScreen(),
                  '/referral': (context) => const ReferralScreen(),
                  '/anonymization': (context) => const AnonymizationScreen(),
                  '/legal-monitoring': (context) => const LegalMonitoringScreen(),
                },
              );
            },
          );
        },
      ),
    );
  }
}
