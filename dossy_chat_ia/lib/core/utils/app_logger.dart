import 'package:flutter/foundation.dart';

/// Lightweight logger that is disabled in release builds.
class AppLogger {
  const AppLogger._();

  static void debug(String message) {
    if (!kReleaseMode) {
      debugPrint(message);
    }
  }

  static void info(String message) {
    if (!kReleaseMode) {
      debugPrint(message);
    }
  }

  static void warn(String message) {
    if (!kReleaseMode) {
      debugPrint(message);
    }
  }

  static void error(String message, {Object? error, StackTrace? stackTrace}) {
    if (!kReleaseMode) {
      debugPrint(message);
      if (error != null) {
        debugPrint('Error: $error');
      }
      if (stackTrace != null) {
        debugPrint(stackTrace.toString());
      }
    }
  }
}
