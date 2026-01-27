import 'package:flutter_test/flutter_test.dart';
import 'package:dossy_chat_ia/data/models/user_model.dart';

void main() {
  group('UserModel Tests', () {
    test('UserModel should be created from JSON correctly', () {
      final json = {
        'id': 1,
        'name': 'Jean Dupont',
        'email': 'jean.dupont@example.com',
        'phone': '+225 07 12 34 56 78',
        'avatar': 'https://example.com/avatar.jpg',
        'role': 'student',
        'plan': 'Étudiant',
        'jurisdiction': 'CI',
        'subscription_end': '2025-12-31T23:59:59Z',
        'searches_used': 5,
        'searches_limit': 50,
        'analyses_used': 2,
        'analyses_limit': 20,
        'downloads_used': 3,
        'downloads_limit': 30,
        'referral_count': 12,
        'referral_code': 'JEAN2025',
        'created_at': '2025-01-01T00:00:00Z',
      };

      final user = UserModel.fromJson(json);

      expect(user.id, 1);
      expect(user.name, 'Jean Dupont');
      expect(user.email, 'jean.dupont@example.com');
      expect(user.phone, '+225 07 12 34 56 78');
      expect(user.avatar, 'https://example.com/avatar.jpg');
      expect(user.role, 'student');
      expect(user.plan, 'Étudiant');
      expect(user.jurisdiction, 'CI');
      expect(user.searchesUsed, 5);
      expect(user.searchesLimit, 50);
      expect(user.analysesUsed, 2);
      expect(user.analysesLimit, 20);
      expect(user.downloadsUsed, 3);
      expect(user.downloadsLimit, 30);
      expect(user.referralCount, 12);
      expect(user.referralCode, 'JEAN2025');
      expect(user.subscriptionEnd, isNotNull);
      expect(user.createdAt, isNotNull);
    });

    test('UserModel should handle missing optional fields', () {
      final json = {
        'id': 2,
        'name': 'Marie Kouassi',
        'email': 'marie@example.com',
        'role': 'lawyer',
        'plan': 'Professionnel',
        'searches_used': 0,
        'searches_limit': 100,
        'analyses_used': 0,
        'analyses_limit': 50,
        'downloads_used': 0,
        'downloads_limit': 100,
        'referral_count': 0,
        'created_at': '2025-06-15T10:30:00Z',
      };

      final user = UserModel.fromJson(json);

      expect(user.id, 2);
      expect(user.name, 'Marie Kouassi');
      expect(user.email, 'marie@example.com');
      expect(user.phone, null);
      expect(user.avatar, null);
      expect(user.jurisdiction, null);
      expect(user.subscriptionEnd, null);
      expect(user.referralCode, null);
    });

    test('UserModel should use default values for missing required fields', () {
      final json = {
        'email': 'test@example.com',
      };

      final user = UserModel.fromJson(json);

      expect(user.id, 0);
      expect(user.name, '');
      expect(user.email, 'test@example.com');
      expect(user.role, 'student');
      expect(user.plan, 'Gratuit');
      expect(user.searchesUsed, 0);
      expect(user.searchesLimit, 5);
      expect(user.analysesUsed, 0);
      expect(user.analysesLimit, 2);
      expect(user.downloadsUsed, 0);
      expect(user.downloadsLimit, 0);
      expect(user.referralCount, 0);
    });

    test('UserModel toJson should serialize correctly', () {
      final user = UserModel(
        id: 3,
        name: 'Amadou Diallo',
        email: 'amadou@example.com',
        phone: '+221 77 123 45 67',
        avatar: 'https://example.com/amadou.jpg',
        role: 'enterprise',
        plan: 'Cabinet/Entreprise',
        jurisdiction: 'SN',
        subscriptionEnd: DateTime(2026, 12, 31),
        searchesUsed: 50,
        searchesLimit: 500,
        analysesUsed: 20,
        analysesLimit: 200,
        downloadsUsed: 100,
        downloadsLimit: 1000,
        referralCount: 25,
        referralCode: 'AMADOU2025',
        createdAt: DateTime(2024, 1, 1),
      );

      final json = user.toJson();

      expect(json['id'], 3);
      expect(json['name'], 'Amadou Diallo');
      expect(json['email'], 'amadou@example.com');
      expect(json['phone'], '+221 77 123 45 67');
      expect(json['avatar'], 'https://example.com/amadou.jpg');
      expect(json['role'], 'enterprise');
      expect(json['plan'], 'Cabinet/Entreprise');
      expect(json['jurisdiction'], 'SN');
      expect(json['searches_used'], 50);
      expect(json['searches_limit'], 500);
      expect(json['analyses_used'], 20);
      expect(json['analyses_limit'], 200);
      expect(json['downloads_used'], 100);
      expect(json['downloads_limit'], 1000);
      expect(json['referral_count'], 25);
      expect(json['referral_code'], 'AMADOU2025');
      expect(json['subscription_end'], isNotNull);
      expect(json['created_at'], isNotNull);
    });

    test('UserModel JSON serialization round trip should preserve data', () {
      final originalUser = UserModel(
        id: 10,
        name: 'Fatou Ndiaye',
        email: 'fatou@example.com',
        phone: '+227 90 12 34 56',
        avatar: null,
        role: 'student',
        plan: 'Étudiant',
        jurisdiction: 'NE',
        subscriptionEnd: DateTime(2025, 12, 31),
        searchesUsed: 10,
        searchesLimit: 50,
        analysesUsed: 5,
        analysesLimit: 20,
        downloadsUsed: 8,
        downloadsLimit: 30,
        referralCount: 3,
        referralCode: 'FATOU2025',
        createdAt: DateTime(2025, 1, 15),
      );

      // Convert to JSON and back
      final json = originalUser.toJson();
      final reconstructedUser = UserModel.fromJson(json);

      expect(reconstructedUser.id, originalUser.id);
      expect(reconstructedUser.name, originalUser.name);
      expect(reconstructedUser.email, originalUser.email);
      expect(reconstructedUser.phone, originalUser.phone);
      expect(reconstructedUser.avatar, originalUser.avatar);
      expect(reconstructedUser.role, originalUser.role);
      expect(reconstructedUser.plan, originalUser.plan);
      expect(reconstructedUser.jurisdiction, originalUser.jurisdiction);
      expect(reconstructedUser.searchesUsed, originalUser.searchesUsed);
      expect(reconstructedUser.searchesLimit, originalUser.searchesLimit);
      expect(reconstructedUser.analysesUsed, originalUser.analysesUsed);
      expect(reconstructedUser.analysesLimit, originalUser.analysesLimit);
      expect(reconstructedUser.downloadsUsed, originalUser.downloadsUsed);
      expect(reconstructedUser.downloadsLimit, originalUser.downloadsLimit);
      expect(reconstructedUser.referralCount, originalUser.referralCount);
      expect(reconstructedUser.referralCode, originalUser.referralCode);
    });

    test('UserModel should handle different plan types', () {
      final plans = ['Gratuit', 'Étudiant', 'Professionnel', 'Cabinet/Entreprise'];

      for (final plan in plans) {
        final json = {
          'id': 1,
          'name': 'Test User',
          'email': 'test@example.com',
          'role': 'student',
          'plan': plan,
          'searches_used': 0,
          'searches_limit': 10,
          'analyses_used': 0,
          'analyses_limit': 5,
          'downloads_used': 0,
          'downloads_limit': 10,
          'referral_count': 0,
          'created_at': '2025-01-01T00:00:00Z',
        };

        final user = UserModel.fromJson(json);
        expect(user.plan, plan);
      }
    });

    test('UserModel should handle different role types', () {
      final roles = ['student', 'lawyer', 'enterprise', 'admin'];

      for (final role in roles) {
        final json = {
          'id': 1,
          'name': 'Test User',
          'email': 'test@example.com',
          'role': role,
          'plan': 'Gratuit',
          'searches_used': 0,
          'searches_limit': 10,
          'analyses_used': 0,
          'analyses_limit': 5,
          'downloads_used': 0,
          'downloads_limit': 10,
          'referral_count': 0,
          'created_at': '2025-01-01T00:00:00Z',
        };

        final user = UserModel.fromJson(json);
        expect(user.role, role);
      }
    });

    test('UserModel should handle African jurisdiction codes', () {
      final jurisdictions = ['CI', 'SN', 'BJ', 'TG', 'ML', 'NE', 'BF', 'CD', 'CM', 'GA', 'MG', 'RW', 'TD', 'GN'];

      for (final jurisdiction in jurisdictions) {
        final json = {
          'id': 1,
          'name': 'Test User',
          'email': 'test@example.com',
          'role': 'student',
          'plan': 'Gratuit',
          'jurisdiction': jurisdiction,
          'searches_used': 0,
          'searches_limit': 10,
          'analyses_used': 0,
          'analyses_limit': 5,
          'downloads_used': 0,
          'downloads_limit': 10,
          'referral_count': 0,
          'created_at': '2025-01-01T00:00:00Z',
        };

        final user = UserModel.fromJson(json);
        expect(user.jurisdiction, jurisdiction);
      }
    });
  });
}
