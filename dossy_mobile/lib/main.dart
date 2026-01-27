import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'controllers/app_lifecycle_controller.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialiser les contrôleurs
  Get.put(AppLifecycleController());
  
  runApp(const DossyApp());
}

class DossyApp extends StatelessWidget {
  const DossyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: 'Dossy',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        useMaterial3: true,
      ),
      home: const MyHomePage(),
      locale: const Locale('fr'),
      fallbackLocale: const Locale('fr'),
    );
  }
}

class MyHomePage extends StatelessWidget {
  const MyHomePage({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Dossy Pro'),
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text('Bienvenue sur Dossy'),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: () {
                // Exemple : afficher le prompt d'avis manuellement
                final controller = Get.find<AppLifecycleController>();
                controller.showRatingPromptDebug();
              },
              child: const Text('Afficher le prompt d\'avis'),
            ),
            const SizedBox(height: 10),
            ElevatedButton(
              onPressed: () {
                // Exemple : ouvrir directement le Play Store
                final controller = Get.find<AppLifecycleController>();
                controller.openPlayStoreReview();
              },
              child: const Text('Évaluer sur Play Store'),
            ),
            const SizedBox(height: 20),
            // Afficher les stats de debug
            GetBuilder<AppLifecycleController>(
              builder: (controller) => Column(
                children: [
                  Text('Sessions: ${controller.sessionCount}'),
                  Text('Temps (sec): ${controller.usageTimeSeconds}'),
                  Text('Avis donné: ${controller.isRatingGiven}'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
