# Flutter Rating Prompt - Code Usage Examples

## Basic Setup (Already Done)

The app now automatically shows a rating prompt after:
- ✅ 3 app sessions AND
- ✅ 60 seconds cumulative usage time

No additional setup needed — it works automatically! But here are examples if you want to customize.

---

## Example 1: Basic Auto-Trigger (Default)

No code needed! The system works automatically on app launch.

```dart
// In main.dart (ALREADY DONE):
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  Get.put(AppLifecycleController()); // ← Handles everything
  runApp(const DossyApp());
}
```

**What happens**:
1. App launches → AppLifecycleController initializes
2. Session counter increments
3. Usage timer starts ticking
4. On 3rd session + 60 sec → prompt auto-appears
5. User rates or dismisses → won't ask again for 14 days

---

## Example 2: Manual Trigger (Custom Moment)

Show the rating prompt at a specific moment (e.g., after successful action):

```dart
import 'package:get/get.dart';
import 'controllers/app_lifecycle_controller.dart';

// Anywhere in your app:
void onDocumentExtractionSuccess() {
  // User just successfully extracted a document
  // Maybe it's a good time to ask for rating?
  
  final controller = Get.find<AppLifecycleController>();
  
  // Show the prompt immediately (if conditions allow)
  controller.showRatingPromptDebug();
}
```

---

## Example 3: Check Before Showing

Check if the user is eligible before showing:

```dart
import 'services/rating_prompt_service.dart';

Future<void> maybeShowRatingPrompt() async {
  final ratingService = RatingPromptService();
  await ratingService.initialize();
  
  if (await ratingService.shouldShowRatingPrompt()) {
    print('✓ User eligible for rating prompt');
    await ratingService.showRatingPrompt();
  } else {
    print('✗ User not yet eligible');
    print('Sessions: ${ratingService.sessionCount}/3');
    print('Usage time: ${ratingService.totalUsageTimeSeconds}/60 seconds');
  }
}
```

---

## Example 4: Direct Play Store Link (Fallback)

Open Play Store directly without the native dialog:

```dart
import 'get/get.dart';
import 'controllers/app_lifecycle_controller.dart';

// In a button or menu:
void openPlayStoreDirectly() {
  final controller = Get.find<AppLifecycleController>();
  controller.openPlayStoreReview(); // Opens app store to review page
}
```

---

## Example 5: Custom Trigger Condition

Show prompt after a specific user action (not just sessions/time):

```dart
import 'controllers/app_lifecycle_controller.dart';
import 'services/rating_prompt_service.dart';

void onUserCompletesFirstExtraction() {
  // User just extracted their first legal document
  // This is a moment of success — ask for feedback!
  
  final ratingService = RatingPromptService();
  
  // Check if minimum sessions reached (ignore time requirement)
  if (ratingService.sessionCount >= 3) {
    ratingService.showRatingPrompt();
  }
}
```

---

## Example 6: Display Rating Stats (Debug UI)

Show user how close they are to seeing the prompt:

```dart
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'controllers/app_lifecycle_controller.dart';

class RatingProgressWidget extends StatelessWidget {
  const RatingProgressWidget({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final controller = Get.find<AppLifecycleController>();

    return GetBuilder<AppLifecycleController>(
      builder: (_) => Card(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Rating Prompt Progress', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              
              // Sessions progress
              Text('Sessions: ${controller.sessionCount}/3'),
              LinearProgressIndicator(
                value: (controller.sessionCount / 3).clamp(0.0, 1.0),
                minHeight: 8,
              ),
              const SizedBox(height: 16),

              // Usage time progress
              Text('Usage Time: ${controller.usageTimeSeconds}/60 seconds'),
              LinearProgressIndicator(
                value: (controller.usageTimeSeconds / 60).clamp(0.0, 1.0),
                minHeight: 8,
              ),
              const SizedBox(height: 16),

              // Status
              Text(
                controller.isRatingGiven
                    ? '✓ Already rated'
                    : controller.sessionCount >= 3 && controller.usageTimeSeconds >= 60
                        ? '✓ Eligible! Prompt will appear'
                        : '⏳ Keep using the app',
                style: TextStyle(
                  color: controller.isRatingGiven
                      ? Colors.green
                      : controller.sessionCount >= 3 && controller.usageTimeSeconds >= 60
                          ? Colors.blue
                          : Colors.orange,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

## Example 7: Reset for Testing

Reset rating data during development:

```dart
import 'controllers/app_lifecycle_controller.dart';

Future<void> resetForTesting() async {
  final controller = Get.find<AppLifecycleController>();
  await controller.resetRatingPromptForTesting();
  
  print('✓ Rating prompt data reset for testing');
  // App restart required to re-initialize
}

// Then reduce thresholds in RatingPromptService:
/*
static const int minSessionsBeforePrompt = 1;  // Instead of 3
static const int minUsageTimeSeconds = 5;       // Instead of 60
*/
```

---

## Example 8: Localization (Custom Message)

The native dialog is auto-localized by Google Play. To add custom message (optional):

```dart
import 'package:in_app_review/in_app_review.dart';

// This is handled automatically, but if you want a custom fallback UI:
class CustomRatingDialog extends StatelessWidget {
  const CustomRatingDialog({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Vous aimez Dossy Pro?'),
      content: const Text('Partagez votre avis sur le Play Store'),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Plus tard'),
        ),
        ElevatedButton(
          onPressed: () {
            RatingPromptService().openPlayStoreForReview();
            Navigator.pop(context);
          },
          child: const Text('Évaluer'),
        ),
      ],
    );
  }
}
```

---

## Example 9: Analytics Integration

Track rating prompt behavior:

```dart
import 'package:firebase_analytics/firebase_analytics.dart';
import 'services/rating_prompt_service.dart';

class AnalyticsRatingPromptService extends RatingPromptService {
  final FirebaseAnalytics _analytics = FirebaseAnalytics.instance;

  Future<void> showRatingPromptWithTracking() async {
    _analytics.logEvent(name: 'rating_prompt_shown');
    await showRatingPrompt();
    _analytics.logEvent(name: 'rating_prompt_accepted');
  }

  Future<void> openPlayStoreWithTracking() async {
    _analytics.logEvent(name: 'rating_playstore_opened');
    await openPlayStoreForReview();
  }
}
```

---

## Example 10: A/B Testing Different Thresholds

Test different prompt frequencies:

```dart
// lib/services/rating_prompt_service.dart

// Strategy A: Conservative (fewer prompts)
class ConservativeRatingService extends RatingPromptService {
  static const int minSessionsBeforePrompt = 10;        // High bar
  static const int minUsageTimeSeconds = 600;           // 10 minutes
  static const int cooldownDaysBetweenPrompts = 60;     // Long cooldown
}

// Strategy B: Aggressive (more prompts)
class AggressiveRatingService extends RatingPromptService {
  static const int minSessionsBeforePrompt = 2;         // Low bar
  static const int minUsageTimeSeconds = 30;            // 30 seconds
  static const int cooldownDaysBetweenPrompts = 7;      // Short cooldown
}

// In main.dart, choose strategy based on user segment:
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  final userSegment = getUserSegment(); // Your logic
  if (userSegment == 'vip') {
    Get.put(ConservativeRatingService()); // Don't annoy power users
  } else {
    Get.put(AggressiveRatingService()); // More opportunity to get reviews
  }
  
  runApp(const DossyApp());
}
```

---

## Example 11: Conditional Display (Don't Prompt During Sensitive Actions)

Prevent prompt during important user actions:

```dart
import 'controllers/app_lifecycle_controller.dart';

bool _isProcessingDocument = false;

Future<void> extractDocument(String docId) async {
  _isProcessingDocument = true;
  
  try {
    // Don't show rating prompt while processing
    await documentService.extract(docId);
  } finally {
    _isProcessingDocument = false;
    
    // Now safe to check for prompt
    final controller = Get.find<AppLifecycleController>();
    await controller.showRatingPromptDebug(); // Won't actually show unless conditions met
  }
}
```

---

## Example 12: Custom Timing

Show prompt at app startup vs user action:

```dart
// Automatic (app startup) - DEFAULT
// Handled by AppLifecycleController.onInit()

// Manual (after user action)
void onUserAction() {
  final controller = Get.find<AppLifecycleController>();
  
  // Delay to not interrupt user experience
  Future.delayed(const Duration(seconds: 2), () {
    controller.showRatingPromptDebug();
  });
}
```

---

## Common Integration Patterns

### Pattern 1: Prompt After Successful Extraction
```dart
void onDocumentExtractionComplete(Document doc) {
  showSnackbar('Document extracted successfully!');
  
  // Optional: show rating prompt 2 seconds later
  Future.delayed(const Duration(seconds: 2), () async {
    final controller = Get.find<AppLifecycleController>();
    if (await _ratingService.shouldShowRatingPrompt()) {
      controller.showRatingPromptDebug();
    }
  });
}
```

### Pattern 2: Prompt in Settings Menu
```dart
Widget buildRatingButton() {
  return ListTile(
    title: const Text('Rate on Play Store'),
    subtitle: const Text('Help us improve Dossy'),
    onTap: () {
      final controller = Get.find<AppLifecycleController>();
      controller.openPlayStoreReview();
    },
  );
}
```

### Pattern 3: Gradual Upsell
```dart
// Session 1: Welcome
// Session 2: Feature tutorial
// Session 3: Rating prompt
// Session 5: Premium upsell
```

---

## Summary of Available Methods

```dart
final controller = Get.find<AppLifecycleController>();

// Check status
int sessions = controller.sessionCount;        // Current sessions
int seconds = controller.usageTimeSeconds;     // Cumulative usage
bool rated = controller.isRatingGiven;         // Did user rate?

// Trigger actions
await controller.showRatingPromptDebug();      // Force show prompt
await controller.openPlayStoreReview();        // Open Play Store
await controller.resetRatingPromptForTesting(); // Clear all data
```

---

## Testing All Scenarios

```dart
// Test script - run in debug mode
void testRatingPromptScenarios() async {
  final controller = Get.find<AppLifecycleController>();
  
  // Scenario 1: Check default state
  print('Sessions: ${controller.sessionCount}');
  print('Usage: ${controller.usageTimeSeconds}');
  print('Rated: ${controller.isRatingGiven}');
  
  // Scenario 2: Reset and re-initialize
  await controller.resetRatingPromptForTesting();
  
  // Scenario 3: Manual trigger
  await controller.showRatingPromptDebug();
  
  // Scenario 4: Verify cooldown
  // Manually set last prompt time in SharedPreferences
  // Try to show again - should be blocked
}
```

---

## That's All!

The rating prompt is fully integrated and working. Use these examples to customize behavior for your specific use case. Most apps work great with the default configuration (3 sessions, 60 seconds, 14-day cooldown).
