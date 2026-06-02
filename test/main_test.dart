import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:emp_track_2/main.dart';

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  group('EmployeeTrackingApp - configuration', () {
    testWidgets('app title is Employee Tracking', (tester) async {
      await tester.pumpWidget(const EmployeeTrackingApp());

      final materialApp = tester.widget<MaterialApp>(find.byType(MaterialApp));
      expect(materialApp.title, 'Employee Tracking');
    });

    testWidgets('debug banner is hidden', (tester) async {
      await tester.pumpWidget(const EmployeeTrackingApp());

      final materialApp = tester.widget<MaterialApp>(find.byType(MaterialApp));
      expect(materialApp.debugShowCheckedModeBanner, false);
    });

    testWidgets('app bar theme has correct colors', (tester) async {
      await tester.pumpWidget(const EmployeeTrackingApp());

      final materialApp = tester.widget<MaterialApp>(find.byType(MaterialApp));
      final appBarTheme = materialApp.theme?.appBarTheme;

      expect(appBarTheme?.backgroundColor, Colors.blue);
      expect(appBarTheme?.foregroundColor, Colors.white);
      expect(appBarTheme?.elevation, 0);
    });

    testWidgets('home screen is LoginScreen', (tester) async {
      await tester.pumpWidget(const EmployeeTrackingApp());

      // The app should start with the login screen
      expect(find.text('Employee Tracking'), findsOneWidget);
      expect(find.text('Login'), findsAtLeastNWidgets(1));
    });
  });
}
