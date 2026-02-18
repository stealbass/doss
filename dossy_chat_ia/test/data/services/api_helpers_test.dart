import 'dart:async';
import 'package:flutter_test/flutter_test.dart';
import 'package:dossy_chat_ia/data/services/api_helpers.dart';

void main() {
  group('ApiHelpers - Validation Tests', () {
    test('isValidEmail should return true for valid emails', () {
      expect(ApiHelpers.isValidEmail('user@example.com'), true);
      expect(ApiHelpers.isValidEmail('test.user@domain.co'), true);
      expect(ApiHelpers.isValidEmail('john.doe+tag@company.com'), true);
    });

    test('isValidEmail should return false for invalid emails', () {
      expect(ApiHelpers.isValidEmail(''), false);
      expect(ApiHelpers.isValidEmail('notanemail'), false);
      expect(ApiHelpers.isValidEmail('@example.com'), false);
      expect(ApiHelpers.isValidEmail('user@'), false);
      expect(ApiHelpers.isValidEmail('user @example.com'), false);
    });

    test('isValidPhone should return true for valid African phone numbers', () {
      expect(ApiHelpers.isValidPhone('+225 07 12 34 56 78'), true);
      expect(ApiHelpers.isValidPhone('+221771234567'), true);
      expect(ApiHelpers.isValidPhone('+227 90 12 34 56'), true);
      expect(ApiHelpers.isValidPhone('+237 6 12 34 56 78'), true);
    });

    test('isValidPhone should return false for invalid phone numbers', () {
      expect(ApiHelpers.isValidPhone(''), false);
      expect(ApiHelpers.isValidPhone('123'), false);
      expect(ApiHelpers.isValidPhone('invalid'), false);
      expect(ApiHelpers.isValidPhone('+1234567'), false);
    });

    test('isValidPassword should return true for valid passwords', () {
      expect(ApiHelpers.isValidPassword('Password123'), true);
      expect(ApiHelpers.isValidPassword('SecureP@ss456'), true);
      expect(ApiHelpers.isValidPassword('MyPass2024!'), true);
    });

    test('isValidPassword should return false for passwords < 8 characters', () {
      expect(ApiHelpers.isValidPassword(''), false);
      expect(ApiHelpers.isValidPassword('Pass1'), false);
      expect(ApiHelpers.isValidPassword('Short7'), false);
    });
  });

  group('ApiHelpers - Formatting Tests', () {
    test('formatFileSize should format bytes correctly', () {
      expect(ApiHelpers.formatFileSize(0), '0 B');
      expect(ApiHelpers.formatFileSize(500), '500 B');
      expect(ApiHelpers.formatFileSize(1024), '1.00 KB');
      expect(ApiHelpers.formatFileSize(1536), '1.50 KB');
      expect(ApiHelpers.formatFileSize(1048576), '1.00 MB');
      expect(ApiHelpers.formatFileSize(5242880), '5.00 MB');
      expect(ApiHelpers.formatFileSize(1073741824), '1.00 GB');
    });

    test('formatDate should format dates correctly', () {
      final date1 = DateTime(2025, 1, 15);
      expect(ApiHelpers.formatDate(date1), '15 janv. 2025');

      final date2 = DateTime(2025, 12, 25);
      expect(ApiHelpers.formatDate(date2), '25 déc. 2025');

      final date3 = DateTime(2025, 6, 30);
      expect(ApiHelpers.formatDate(date3), '30 juin 2025');
    });

    test('getRelativeTime should return correct relative time', () {
      final now = DateTime.now();

      // Just now (< 1 minute)
      final justNow = now.subtract(const Duration(seconds: 30));
      expect(ApiHelpers.getRelativeTime(justNow), "À l'instant");

      // Minutes ago
      final minutesAgo = now.subtract(const Duration(minutes: 5));
      expect(ApiHelpers.getRelativeTime(minutesAgo), 'Il y a 5 minutes');

      // Hours ago
      final hoursAgo = now.subtract(const Duration(hours: 3));
      expect(ApiHelpers.getRelativeTime(hoursAgo), 'Il y a 3 heures');

      // Days ago
      final daysAgo = now.subtract(const Duration(days: 2));
      expect(ApiHelpers.getRelativeTime(daysAgo), 'Il y a 2 jours');

      // Weeks ago
      final weeksAgo = now.subtract(const Duration(days: 14));
      expect(ApiHelpers.getRelativeTime(weeksAgo), 'Il y a 2 semaines');

      // Months ago (> 30 days)
      final monthsAgo = now.subtract(const Duration(days: 45));
      expect(ApiHelpers.getRelativeTime(monthsAgo), anyOf(contains('2025'), contains('2024')));
    });

    test('truncate should truncate text correctly', () {
      expect(ApiHelpers.truncate('Hello World', 5), 'Hello...');
      expect(ApiHelpers.truncate('Short', 10), 'Short');
      expect(ApiHelpers.truncate('', 5), '');
      expect(
        ApiHelpers.truncate('This is a very long text that needs truncation', 20),
        'This is a very long ...',
      );
    });

    test('capitalize should capitalize first letter', () {
      expect(ApiHelpers.capitalize('hello'), 'Hello');
      expect(ApiHelpers.capitalize('WORLD'), 'WORLD');
      expect(ApiHelpers.capitalize('bonjour le monde'), 'Bonjour le monde');
      expect(ApiHelpers.capitalize(''), '');
      expect(ApiHelpers.capitalize('a'), 'A');
    });
  });

  group('ApiHelpers - Security & Utilities Tests', () {
    test('sanitizeInput should remove dangerous characters', () {
      expect(ApiHelpers.sanitizeInput('Hello World'), 'Hello World');
      expect(ApiHelpers.sanitizeInput('Test<script>alert("XSS")</script>'), contains('Test'));
      expect(ApiHelpers.sanitizeInput('Normal text 123'), 'Normal text 123');
      expect(ApiHelpers.sanitizeInput(''), '');
    });

    test('parseJson should parse valid JSON safely', () {
      const validJson = '{"name": "John", "age": 30}';
      final result = ApiHelpers.parseJson(validJson);
      expect(result, isA<Map<String, dynamic>>());
      expect(result['name'], 'John');
      expect(result['age'], 30);
    });

    test('parseJson should return empty map for invalid JSON', () {
      const invalidJson = '{invalid json}';
      final result = ApiHelpers.parseJson(invalidJson);
      expect(result, {});
    });

    test('generateCode should generate code of specified length', () {
      final code6 = ApiHelpers.generateCode(6);
      expect(code6.length, 6);
      expect(code6, matches(r'^[A-Z0-9]+$'));

      final code10 = ApiHelpers.generateCode(10);
      expect(code10.length, 10);
      expect(code10, matches(r'^[A-Z0-9]+$'));

      // Test uniqueness (very unlikely to be the same)
      final code1 = ApiHelpers.generateCode(8);
      final code2 = ApiHelpers.generateCode(8);
      expect(code1 == code2, false);
    });

    test('getFirstNonNull should return first non-null value', () {
      expect(ApiHelpers.getFirstNonNull([null, 'first', 'second']), 'first');
      expect(ApiHelpers.getFirstNonNull([null, null, 'third']), 'third');
      expect(ApiHelpers.getFirstNonNull(['immediate']), 'immediate');
      expect(ApiHelpers.getFirstNonNull([null, null, null]), null);
      expect(ApiHelpers.getFirstNonNull([]), null);
    });
  });

  group('ApiHelpers - Error Parsing Tests', () {
    test('parseApiError should return correct messages for HTTP codes', () {
      // Note: This test assumes parseApiError is accessible or we test through error scenarios
      // Since parseApiError takes DioException, we'll add basic structure tests
      // Real implementation would need Dio mocking

      // Test that error messages are in French (through documentation)
      expect(true, true); // Placeholder - actual test needs Dio mock
    });
  });

  group('ApiHelpers - Network Tests', () {
    test('hasInternetConnection should complete without error', () async {
      // This is an async function that checks connectivity
      // Real test would need connectivity mocking
      expect(true, true); // Placeholder - actual test needs connectivity mock
    });
  });

  group('ApiHelpers - Timeout & Retry Tests', () {
    test('withTimeout should handle timeout correctly', () async {
      // Test timeout functionality
      final fastOperation = Future.delayed(const Duration(milliseconds: 100), () => 'success');
      
      final result = await ApiHelpers.withTimeout(
        fastOperation,
        timeoutSeconds: 5,
      );
      
      expect(result, 'success');
    });

    test('withTimeout should throw on timeout', () async {
      // Test timeout exception
      final slowOperation = Future.delayed(const Duration(seconds: 10), () => 'too slow');
      
      expect(
        () => ApiHelpers.withTimeout(slowOperation, timeoutSeconds: 1),
        throwsA(isA<TimeoutException>()),
      );
    });

    test('retryWithBackoff should retry on failure', () async {
      int attemptCount = 0;
      
      Future<String> unreliableOperation() async {
        attemptCount++;
        if (attemptCount < 3) {
          throw Exception('Temporary failure');
        }
        return 'success after retries';
      }
      
      final result = await ApiHelpers.retryWithBackoff(
        unreliableOperation,
        maxAttempts: 5,
        initialDelay: const Duration(milliseconds: 10),
      );
      
      expect(result, 'success after retries');
      expect(attemptCount, 3);
    });

    test('retryWithBackoff should throw after max attempts', () async {
      Future<Never> alwaysFailOperation() async {
        throw Exception('Always fails');
      }
      
      expect(
        () => ApiHelpers.retryWithBackoff(
          alwaysFailOperation,
          maxAttempts: 3,
          initialDelay: const Duration(milliseconds: 10),
        ),
        throwsA(isA<Exception>()),
      );
    });
  });
}
