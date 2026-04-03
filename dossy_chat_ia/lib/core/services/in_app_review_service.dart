import 'package:flutter/foundation.dart';
import 'package:in_app_review/in_app_review.dart';

class InAppReviewService {
  final InAppReview _inAppReview = InAppReview.instance;

  Future<void> requestReview() async {
    if (!kReleaseMode) return;

    try {
      final isAvailable = await _inAppReview.isAvailable();
      if (isAvailable) {
        await _inAppReview.requestReview();
      }
    } catch (e) {
      debugPrint('In-app review request failed: $e');
    }
  }
}
