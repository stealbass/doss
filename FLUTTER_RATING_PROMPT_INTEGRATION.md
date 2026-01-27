# Flutter Rating Prompt Integration Guide

## Overview
The app now includes an automated Play Store rating prompt system that:
- ✅ Tracks user sessions automatically
- ✅ Accumulates app usage time
- ✅ Shows a native Play Store rating dialog after conditions are met
- ✅ Prevents prompt spam with cooldown periods
- ✅ Stores user preferences locally

## Components

### 1. RatingPromptService (lib/services/rating_prompt_service.dart)
**Purpose**: Singleton service managing all rating prompt logic

**Key Configuration**:
```dart
static const int minSessionsBeforePrompt = 3;        // Show after 3 sessions
static const int minUsageTimeSeconds = 60;           // Show after 60 seconds cumulative
static const int cooldownDaysBetweenPrompts = 14;    // Wait 14 days between prompts
```

**Main Methods**:
- `initialize()` - Load preferences from SharedPreferences
- `recordNewSession()` - Increment session counter
- `addUsageTime(seconds)` - Add to total usage time
- `shouldShowRatingPrompt()` - Check if conditions met (sessions + time + cooldown)
- `showRatingPrompt()` - Show native Play Store dialog via InAppReview
- `openPlayStoreForReview()` - Direct link to Play Store as fallback
- `resetForTesting()` - Clear all data (debug only)

**Public Getters**:
- `sessionCount` - Current session count
- `totalUsageTimeSeconds` - Total usage time accumulated
- `isRatingGiven` - Whether user already rated

### 2. AppLifecycleController (lib/controllers/app_lifecycle_controller.dart)
**Purpose**: GetX controller managing app lifecycle and triggering prompt checks

**Integration Points**:
- Monitors app lifecycle (foreground/background)
- Records new session on app launch
- Accumulates usage time via 30-second timer
- Automatically checks and shows rating prompt on app resume
- Cleans up resources on app close

**Key Lifecycle**:
1. `onInit()` - Initialize service, record session, start timer, check prompt
2. `didChangeAppLifecycleState()` - Handle pause/resume, finalize time on background
3. `onClose()` - Clean up observer and timer

**Public Methods**:
- `showRatingPromptDebug()` - Force prompt display (testing)
- `openPlayStoreReview()` - Direct to Play Store (testing)
- `resetRatingPromptForTesting()` - Clear data (testing)

### 3. main.dart Integration
**Already Updated**: The main.dart file now:
1. Initializes AppLifecycleController on app startup
2. Binds it to GetX dependency injection
3. Includes a test UI with stats display and manual trigger buttons

## How It Works

### Flow Diagram
```
App Launch
    ↓
AppLifecycleController.onInit()
    ├→ Initialize RatingPromptService
    ├→ recordNewSession() (increment session counter)
    ├→ startUsageTimer() (tick every 30 sec)
    └→ _checkAndShowRatingPrompt()
        ↓
    shouldShowRatingPrompt()?
        ├→ User sessions >= 3? ✓
        ├→ Usage time >= 60 sec? ✓
        ├→ 14 days since last prompt? ✓
        └→ YES → showRatingPrompt()
            ↓
        InAppReview.requestReview()
        (Native Play Store dialog)
        ↓
    User rates → isRatingGiven = true
```

### Session Counting
- Starts with `recordNewSession()` on app launch
- Increments counter each launch
- Never resets (tracks lifetime sessions)
- Example: 3rd session → prompt eligible (if time met)

### Usage Time Accumulation
- Timer starts on app launch
- Accumulates `DateTime.now() - _appOpenedTime` every 30 seconds
- Stops when app pauses (background)
- Continues on next resume
- Example: 1st day = 45 sec → stored; 2nd day = 20 sec → now 65 sec total → prompt eligible

### Cooldown Logic
- After user rates (or dismisses after showing), timestamp recorded
- Won't show again for 14 days
- Prevents "nag" behavior
- Cooldown starts on first prompt display

## Testing & Debug UI

### Built-in Test Buttons (main.dart)
The app includes a test screen with:

1. **Show Rating Prompt Manually**
   - Forces prompt display immediately
   - Useful for verifying the native dialog works
   
2. **Open Play Store Directly**
   - Bypasses In-App Review API
   - Opens Play Store app to "Add your review" page

3. **Live Stats Display**
   - Current session count
   - Total usage time (seconds)
   - Rating status (true/false)

### Testing Scenarios

**Scenario 1: Verify prompt appears on 3rd session**
```
Day 1: Launch app → session=1
Day 2: Launch app → session=2  
Day 3: Launch app → session=3 + wait 60 sec → prompt appears
```

**Scenario 2: Reset and test immediately**
```dart
// In main.dart or anywhere
final controller = Get.find<AppLifecycleController>();
await controller.resetRatingPromptForTesting();
// Now change minSessions/minUsageTime to 0 in RatingPromptService
// Relaunch app → prompt appears immediately
```

**Scenario 3: Verify cooldown blocks repeat**
```
1. Show prompt on day 3
2. User taps "Rate" → isRatingGiven=true, lastPromptTime=now
3. Immediately show prompt again → blocked (cooldown active)
4. Change device date to 15 days later → blocked still (date check)
```

## Customization

### Change Prompt Frequency
Edit `RatingPromptService`:
```dart
static const int minSessionsBeforePrompt = 5;        // Increase to 5
static const int minUsageTimeSeconds = 300;          // Increase to 5 min
static const int cooldownDaysBetweenPrompts = 30;    // Increase to 30 days
```

### Disable for Testing
```dart
// In AppLifecycleController.onInit()
// Comment out:
// _checkAndShowRatingPrompt();
```

### Custom Prompt Trigger
```dart
// Anywhere in app
final controller = Get.find<AppLifecycleController>();
if (userDidSomethingImportant) {
  await controller.showRatingPromptDebug();
}
```

## Platform Support

### Android
- ✅ Uses native Material "Add your review" dialog
- ✅ Requires Google Play app installed
- ✅ Fallback: Opens Play Store browser page

### iOS
- ✅ Uses native iOS StoreKit review dialog
- ✅ Optional in-app rating prompt
- ✅ Fallback: Opens App Store website

## Dependencies
- `in_app_review: ^2.0.8` (in pubspec.yaml)
- `get: ^X.X.X` (for GetX dependency injection)
- `shared_preferences: ^X.X.X` (for local data persistence)

## Known Limitations

1. **Native Dialog Availability**
   - On Android: Requires Google Play app 5.0+ and device account
   - On iOS: iOS 13.2+ required
   - If unavailable: Fallback link opens Play Store/App Store

2. **Cooldown Timing**
   - Uses device system time
   - Cannot distinguish between app uninstall/reinstall (treats as new install if < 14 days)
   - Resetting app data in settings will reset cooldown

3. **Session Counting**
   - Counts each app launch as new session (even if <1 sec between launches)
   - No session expiry (3 sessions cumulative, not "3 sessions in last 30 days")

## Troubleshooting

### Prompt Not Appearing
1. Check session count: ≥ 3?
2. Check usage time: ≥ 60 sec?
3. Check rating status: Is `isRatingGiven=true`? (cooldown active)
4. Use debug UI buttons to verify Native API works
5. Check logcat for "In-App Review" errors

### Test UI Not Showing Stats
- Ensure `AppLifecycleController` is bound in GetMaterialApp
- Check that `Get.put(AppLifecycleController())` runs in main()
- Verify `GetBuilder` is watching correct controller

### "Killed" on Large PDFs (Unrelated)
- This is the document extraction issue (already fixed in Python)
- Not related to rating prompt
- See EXTRACTION_DOCUMENTS_VOLUMEUX_FINAL.md for details

## Next Steps (Optional Enhancements)

1. **Track Specific Actions**
   - Show prompt after 5 successful document extractions (custom trigger)
   - Show on first successful paid feature usage

2. **Localization**
   - Native dialog is auto-localized by Google Play
   - Add custom message in your language if using fallback UI

3. **Analytics**
   - Log when prompt shown: `FirebaseAnalytics.logEvent('rating_prompt_shown')`
   - Log user response: `FirebaseAnalytics.logEvent('rating_prompt_accepted')`

4. **A/B Testing**
   - Vary `minSessionsBeforePrompt` by user segment
   - Measure conversion rate to Play Store reviews

## Files Modified/Created

| File | Action | Purpose |
|------|--------|---------|
| `lib/main.dart` | Updated | Initialize AppLifecycleController, add test UI |
| `lib/controllers/app_lifecycle_controller.dart` | Created | Lifecycle management + prompt triggering |
| `lib/services/rating_prompt_service.dart` | Created | Rating logic, session/time tracking |
| `pubspec.yaml` | Updated | Added `in_app_review` dependency |

## Support & References

- **In-App Review Documentation**: https://pub.dev/packages/in_app_review
- **GetX Bindings**: https://github.com/jonataslaw/getx/blob/master/documentation/en_US/dependency_management.md
- **Google Play Reviews API**: https://developer.android.com/guide/playcore/in-app-review
- **App Store StoreKit**: https://developer.apple.com/documentation/storekit/skstorereviewcontroller
