import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:dossy_chat_ia/presentation/widgets/empty_states.dart';

void main() {
  group('EmptyState Widget Tests', () {
    testWidgets('EmptyState should display all elements correctly', (WidgetTester tester) async {
      const testTitle = 'Test Title';
      const testMessage = 'Test message description';
      const testActionLabel = 'Test Action';
      bool actionCalled = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: EmptyState(
              icon: Icons.info,
              title: testTitle,
              message: testMessage,
              actionLabel: testActionLabel,
              onAction: () {
                actionCalled = true;
              },
            ),
          ),
        ),
      );

      // Verify title is displayed
      expect(find.text(testTitle), findsOneWidget);

      // Verify message is displayed
      expect(find.text(testMessage), findsOneWidget);

      // Verify action button is displayed
      expect(find.text(testActionLabel), findsOneWidget);

      // Tap action button
      await tester.tap(find.text(testActionLabel));
      await tester.pump();

      // Verify action callback was called
      expect(actionCalled, true);
    });

    testWidgets('EmptyState without action should not show button', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: EmptyState(
              icon: Icons.info,
              title: 'Title',
              message: 'Message',
            ),
          ),
        ),
      );

      // Should not find any ElevatedButton
      expect(find.byType(ElevatedButton), findsNothing);
    });
  });

  group('EmptyDataState Widget Tests', () {
    testWidgets('EmptyDataState should render correctly', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: EmptyDataState(),
          ),
        ),
      );

      expect(find.text('Aucune donnée'), findsOneWidget);
      expect(find.text('Aucune information disponible pour le moment.'), findsOneWidget);
    });
  });

  group('NoConnectionState Widget Tests', () {
    testWidgets('NoConnectionState should display connection error', (WidgetTester tester) async {
      bool retryCalled = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: NoConnectionState(
              onRetry: () {
                retryCalled = true;
              },
            ),
          ),
        ),
      );

      expect(find.text('Pas de connexion'), findsOneWidget);
      expect(find.text('Réessayer'), findsOneWidget);

      // Test retry action
      await tester.tap(find.text('Réessayer'));
      await tester.pump();

      expect(retryCalled, true);
    });
  });

  group('ErrorState Widget Tests', () {
    testWidgets('ErrorState should display error message and retry button', (WidgetTester tester) async {
      const errorMessage = 'An error occurred';
      bool retryCalled = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: ErrorState(
              message: errorMessage,
              onRetry: () {
                retryCalled = true;
              },
            ),
          ),
        ),
      );

      expect(find.textContaining('Erreur'), findsOneWidget);
      expect(find.text('Réessayer'), findsOneWidget);

      // Test retry action
      await tester.tap(find.text('Réessayer'));
      await tester.pump();

      expect(retryCalled, true);
    });
  });

  group('NoSearchResultsState Widget Tests', () {
    testWidgets('NoSearchResultsState should display correct message', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: NoSearchResultsState(),
          ),
        ),
      );

      expect(find.text('Aucun résultat'), findsOneWidget);
      expect(find.textContaining('essayez avec des termes différents'), findsOneWidget);
    });
  });

  group('EmptyMessagesState Widget Tests', () {
    testWidgets('EmptyMessagesState should display chat empty state', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: EmptyMessagesState(),
          ),
        ),
      );

      expect(find.text('Aucun message'), findsOneWidget);
      expect(find.textContaining('conversation'), findsOneWidget);
    });
  });

  group('EmptyDocumentsState Widget Tests', () {
    testWidgets('EmptyDocumentsState should display documents empty state', (WidgetTester tester) async {
      bool uploadCalled = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: EmptyDocumentsState(
              onUpload: () {
                uploadCalled = true;
              },
            ),
          ),
        ),
      );

      expect(find.text('Bibliothèque vide'), findsOneWidget);

      // Test upload action if button exists
      final uploadButton = find.text('Ajouter un document');
      if (tester.any(uploadButton)) {
        await tester.tap(uploadButton);
        await tester.pump();
        expect(uploadCalled, true);
      }
    });
  });

  group('PremiumRequiredState Widget Tests', () {
    testWidgets('PremiumRequiredState should display upgrade message', (WidgetTester tester) async {
      bool upgradeCalled = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: PremiumRequiredState(
              onUpgrade: () {
                upgradeCalled = true;
              },
            ),
          ),
        ),
      );

      expect(find.textContaining('PRO'), findsOneWidget);
      expect(find.text('Mettre à niveau'), findsOneWidget);

      // Test upgrade action
      await tester.tap(find.text('Mettre à niveau'));
      await tester.pump();

      expect(upgradeCalled, true);
    });
  });

  group('QuotaExceededState Widget Tests', () {
    testWidgets('QuotaExceededState should display quota message', (WidgetTester tester) async {
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: QuotaExceededState(
              onUpgrade: () {},
            ),
          ),
        ),
      );

      expect(find.textContaining('Quota'), findsOneWidget);
      expect(find.text('Mettre à niveau'), findsOneWidget);
    });
  });

  group('LoadingState Widget Tests', () {
    testWidgets('LoadingState should display loading indicator', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: LoadingState(),
          ),
        ),
      );

      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      expect(find.textContaining('Chargement'), findsOneWidget);
    });

    testWidgets('LoadingState should display custom message', (WidgetTester tester) async {
      const customMessage = 'Loading custom data...';

      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: LoadingState(message: customMessage),
          ),
        ),
      );

      expect(find.text(customMessage), findsOneWidget);
    });
  });

  group('MaintenanceState Widget Tests', () {
    testWidgets('MaintenanceState should display maintenance message', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: MaintenanceState(),
          ),
        ),
      );

      expect(find.textContaining('Maintenance'), findsOneWidget);
    });
  });

  group('EmptyHistoryState Widget Tests', () {
    testWidgets('EmptyHistoryState should display history empty state', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: EmptyHistoryState(),
          ),
        ),
      );

      expect(find.textContaining('Historique'), findsOneWidget);
    });
  });

  group('EmptyNotificationsState Widget Tests', () {
    testWidgets('EmptyNotificationsState should display notifications empty state', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: EmptyNotificationsState(),
          ),
        ),
      );

      expect(find.textContaining('notification'), findsOneWidget);
    });
  });

  group('EmptyFavoritesState Widget Tests', () {
    testWidgets('EmptyFavoritesState should display favorites empty state', (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: EmptyFavoritesState(),
          ),
        ),
      );

      expect(find.textContaining('favori'), findsOneWidget);
    });
  });

  group('UpdateRequiredState Widget Tests', () {
    testWidgets('UpdateRequiredState should display update message', (WidgetTester tester) async {
      bool updateCalled = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: UpdateRequiredState(
              onUpdate: () {
                updateCalled = true;
              },
            ),
          ),
        ),
      );

      expect(find.textContaining('Mise à jour'), findsOneWidget);

      // Find and tap update button
      final updateButton = find.text('Mettre à jour');
      if (tester.any(updateButton)) {
        await tester.tap(updateButton);
        await tester.pump();
        expect(updateCalled, true);
      }
    });
  });
}
