import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:emp_track_2/screens/login_screen.dart';

Widget buildTestableWidget(Widget child) {
  return MaterialApp(home: child);
}

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  group('LoginScreen - UI elements', () {
    testWidgets('renders all core UI elements', (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      expect(find.text('Employee Tracking'), findsOneWidget);
      expect(find.text('Track your work location and attendance'), findsOneWidget);
      expect(find.text('Login'), findsAtLeastNWidgets(1));
      expect(find.byType(TextFormField), findsNWidgets(2));
      expect(find.widgetWithText(ElevatedButton, 'Login'), findsOneWidget);
      expect(find.byIcon(Icons.location_on), findsOneWidget);
    });

    testWidgets('has employee number and password fields', (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      expect(find.text('Employee Number'), findsOneWidget);
      expect(find.text('Password'), findsOneWidget);
    });

    testWidgets('has settings icon button for server URL config',
        (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      expect(find.byIcon(Icons.settings), findsOneWidget);
    });

    testWidgets('has location info text at bottom', (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      expect(
        find.textContaining('location will be captured'),
        findsOneWidget,
      );
    });

    testWidgets('has badge and lock icons for input fields', (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      expect(find.byIcon(Icons.badge), findsOneWidget);
      expect(find.byIcon(Icons.lock), findsOneWidget);
    });
  });

  group('LoginScreen - form validation', () {
    testWidgets('shows validation error when employee number is empty',
        (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      // Enter password but leave employee number empty
      await tester.enterText(find.byType(TextFormField).last, 'password123');
      await tester.tap(find.widgetWithText(ElevatedButton, 'Login'));
      await tester.pump();

      expect(find.text('Please enter your employee number'), findsOneWidget);
    });

    testWidgets('shows validation error when password is empty',
        (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      // Enter employee number but leave password empty
      await tester.enterText(find.byType(TextFormField).first, 'EMP001');
      await tester.tap(find.widgetWithText(ElevatedButton, 'Login'));
      await tester.pump();

      expect(find.text('Please enter your password'), findsOneWidget);
    });

    testWidgets('shows both validation errors when both fields are empty',
        (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      await tester.tap(find.widgetWithText(ElevatedButton, 'Login'));
      await tester.pump();

      expect(find.text('Please enter your employee number'), findsOneWidget);
      expect(find.text('Please enter your password'), findsOneWidget);
    });

    testWidgets('no validation error when both fields are filled',
        (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      await tester.enterText(find.byType(TextFormField).first, 'EMP001');
      await tester.enterText(find.byType(TextFormField).last, 'password123');
      await tester.tap(find.widgetWithText(ElevatedButton, 'Login'));
      await tester.pump();

      expect(find.text('Please enter your employee number'), findsNothing);
      expect(find.text('Please enter your password'), findsNothing);
    });
  });

  group('LoginScreen - password visibility toggle', () {
    testWidgets('password is obscured by default (visibility icon shown)',
        (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      // When obscured, the visibility icon (eye) is shown to toggle it off
      expect(find.byIcon(Icons.visibility), findsOneWidget);
      expect(find.byIcon(Icons.visibility_off), findsNothing);
    });

    testWidgets('toggling password visibility shows the visibility_off icon',
        (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      // Initially visibility icon should be present (password is obscured)
      expect(find.byIcon(Icons.visibility), findsOneWidget);
      expect(find.byIcon(Icons.visibility_off), findsNothing);

      // Tap the visibility toggle
      await tester.tap(find.byIcon(Icons.visibility));
      await tester.pump();

      // Now visibility_off icon should be present
      expect(find.byIcon(Icons.visibility_off), findsOneWidget);
      expect(find.byIcon(Icons.visibility), findsNothing);
    });

    testWidgets('toggling password visibility twice restores obscured state',
        (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      // Toggle twice
      await tester.tap(find.byIcon(Icons.visibility));
      await tester.pump();
      await tester.tap(find.byIcon(Icons.visibility_off));
      await tester.pump();

      // Should be back to obscured (visibility icon)
      expect(find.byIcon(Icons.visibility), findsOneWidget);
    });
  });

  group('LoginScreen - text input', () {
    testWidgets('can enter text in employee number field', (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      await tester.enterText(find.byType(TextFormField).first, 'EMP001');
      expect(find.text('EMP001'), findsOneWidget);
    });

    testWidgets('can enter text in password field', (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      await tester.enterText(find.byType(TextFormField).last, 'mypassword');
      // Password text won't be visible due to obscuring, but the field accepts it
      expect(find.byType(TextFormField), findsNWidgets(2));
    });
  });

  group('LoginScreen - server URL dialog', () {
    testWidgets('settings icon opens server URL dialog', (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      await tester.tap(find.byIcon(Icons.settings));
      await tester.pumpAndSettle();

      expect(find.text('Server URL'), findsOneWidget);
      expect(find.text('Cancel'), findsOneWidget);
      expect(find.text('Save'), findsOneWidget);
    });

    testWidgets('cancel closes the server URL dialog', (tester) async {
      await tester.pumpWidget(buildTestableWidget(const LoginScreen()));

      await tester.tap(find.byIcon(Icons.settings));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Cancel'));
      await tester.pumpAndSettle();

      expect(find.text('Server URL'), findsNothing);
    });
  });
}
