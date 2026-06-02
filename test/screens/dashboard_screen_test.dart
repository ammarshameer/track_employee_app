import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:emp_track_2/screens/dashboard_screen.dart';

Widget buildTestableWidget(Widget child) {
  return MaterialApp(home: child);
}

Map<String, dynamic> sampleEmployeeData({
  String name = 'John Doe',
  String employeeNumber = 'EMP001',
  String sessionId = 'sess_123',
  String loginTime = '',
}) {
  return {
    'name': name,
    'employee_number': employeeNumber,
    'session_id': sessionId,
    'employee_id': 1,
    'login_time': loginTime,
  };
}

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  group('DashboardScreen - UI rendering', () {
    testWidgets('displays the app bar with title', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );

      expect(find.text('Employee Dashboard'), findsOneWidget);
    });

    testWidgets('displays employee name', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(
            employeeData: sampleEmployeeData(name: 'Alice Smith'),
          ),
        ),
      );

      expect(find.text('Alice Smith'), findsOneWidget);
    });

    testWidgets('displays employee number', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(
            employeeData: sampleEmployeeData(employeeNumber: 'EMP042'),
          ),
        ),
      );

      expect(find.text('ID: EMP042'), findsOneWidget);
    });

    testWidgets('displays employee initial in avatar', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(
            employeeData: sampleEmployeeData(name: 'John Doe'),
          ),
        ),
      );

      expect(find.text('J'), findsOneWidget);
    });

    testWidgets('displays tracking active status', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );
      await tester.pump();

      expect(find.text('Tracking Active'), findsOneWidget);
    });

    testWidgets('has refresh button in app bar', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );

      expect(find.byIcon(Icons.refresh), findsOneWidget);
    });

    testWidgets('has logout button', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );

      expect(find.widgetWithText(ElevatedButton, 'Logout'), findsOneWidget);
    });

    testWidgets('displays login time label', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );
      await tester.pump();

      expect(find.text('Login Time:'), findsOneWidget);
    });

    testWidgets('displays working time label', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );
      await tester.pump();

      expect(find.text('Working Time:'), findsOneWidget);
    });

    testWidgets('displays current location section', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );

      expect(find.text('Current Location'), findsOneWidget);
    });
  });

  group('DashboardScreen - employee data variations', () {
    testWidgets('handles missing name gracefully', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: {
            'employee_number': 'EMP001',
            'session_id': 'sess_123',
            'employee_id': 1,
            'login_time': '',
          }),
        ),
      );

      expect(find.text('Employee'), findsOneWidget);
      expect(find.text('E'), findsOneWidget);
    });

    testWidgets('handles missing employee number gracefully', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: {
            'name': 'Jane',
            'session_id': 'sess_123',
            'employee_id': 1,
            'login_time': '',
          }),
        ),
      );

      expect(find.text('ID: N/A'), findsOneWidget);
    });

    testWidgets('parses valid login_time string', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(
            employeeData: sampleEmployeeData(
              loginTime: '2025-06-01T09:00:00',
            ),
          ),
        ),
      );

      // The dashboard should render without errors
      expect(find.text('Employee Dashboard'), findsOneWidget);
    });
  });

  group('DashboardScreen - widget structure', () {
    testWidgets('contains expected card widgets', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );

      // Should have multiple Card widgets (employee info, status, location, etc.)
      expect(find.byType(Card), findsAtLeastNWidgets(2));
    });

    testWidgets('contains SingleChildScrollView for scrollability',
        (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );

      expect(find.byType(SingleChildScrollView), findsOneWidget);
    });

    testWidgets('back navigation is disabled (no back button)', (tester) async {
      await tester.pumpWidget(
        buildTestableWidget(
          DashboardScreen(employeeData: sampleEmployeeData()),
        ),
      );

      // automaticallyImplyLeading is false, so no back button
      final appBar = tester.widget<AppBar>(find.byType(AppBar));
      expect(appBar.automaticallyImplyLeading, false);
    });
  });
}
