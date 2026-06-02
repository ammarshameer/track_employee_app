import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:emp_track_2/services/api_service.dart';

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
    ApiService.httpClient = null;
  });

  tearDown(() {
    ApiService.httpClient = null;
  });

  group('ApiService - Base URL management', () {
    test('setBaseUrl persists the URL', () async {
      await ApiService.setBaseUrl('http://example.com/api');
      final saved = await ApiService.getSavedBaseUrl();
      expect(saved, 'http://example.com/api');
    });

    test('getSavedBaseUrl returns null when no URL is saved', () async {
      final saved = await ApiService.getSavedBaseUrl();
      expect(saved, isNull);
    });

    test('setBaseUrl overwrites a previously saved URL', () async {
      await ApiService.setBaseUrl('http://first.com/api');
      await ApiService.setBaseUrl('http://second.com/api');
      final saved = await ApiService.getSavedBaseUrl();
      expect(saved, 'http://second.com/api');
    });
  });

  group('ApiService - Session data management', () {
    test('saveSessionData stores all fields correctly', () async {
      final sessionData = {
        'session_id': 'sess_123',
        'employee_id': 42,
        'employee_number': 'EMP001',
        'name': 'John Doe',
      };

      await ApiService.saveSessionData(sessionData);
      final prefs = await SharedPreferences.getInstance();

      expect(prefs.getString('session_id'), 'sess_123');
      expect(prefs.getString('employee_id'), '42');
      expect(prefs.getString('employee_number'), 'EMP001');
      expect(prefs.getString('employee_name'), 'John Doe');
      expect(prefs.getBool('is_logged_in'), true);
    });

    test('saveSessionData handles missing fields with defaults', () async {
      final sessionData = <String, dynamic>{
        'employee_id': 1,
      };

      await ApiService.saveSessionData(sessionData);
      final prefs = await SharedPreferences.getInstance();

      expect(prefs.getString('session_id'), '');
      expect(prefs.getString('employee_number'), '');
      expect(prefs.getString('employee_name'), '');
      expect(prefs.getBool('is_logged_in'), true);
    });

    test('getSessionData returns data when logged in', () async {
      SharedPreferences.setMockInitialValues({
        'is_logged_in': true,
        'session_id': 'sess_abc',
        'employee_id': '10',
        'employee_number': 'EMP002',
        'employee_name': 'Jane Smith',
      });

      final data = await ApiService.getSessionData();

      expect(data, isNotNull);
      expect(data!['session_id'], 'sess_abc');
      expect(data['employee_id'], '10');
      expect(data['employee_number'], 'EMP002');
      expect(data['employee_name'], 'Jane Smith');
    });

    test('getSessionData returns null when not logged in', () async {
      SharedPreferences.setMockInitialValues({
        'is_logged_in': false,
      });

      final data = await ApiService.getSessionData();
      expect(data, isNull);
    });

    test('getSessionData returns null when no session exists', () async {
      final data = await ApiService.getSessionData();
      expect(data, isNull);
    });

    test('clearSessionData removes all session keys', () async {
      SharedPreferences.setMockInitialValues({
        'is_logged_in': true,
        'session_id': 'sess_abc',
        'employee_id': '10',
        'employee_number': 'EMP002',
        'employee_name': 'Jane Smith',
      });

      await ApiService.clearSessionData();
      final prefs = await SharedPreferences.getInstance();

      expect(prefs.getString('session_id'), isNull);
      expect(prefs.getString('employee_id'), isNull);
      expect(prefs.getString('employee_number'), isNull);
      expect(prefs.getString('employee_name'), isNull);
      expect(prefs.getBool('is_logged_in'), false);
    });

    test('isLoggedIn returns true when logged in', () async {
      SharedPreferences.setMockInitialValues({
        'is_logged_in': true,
      });

      expect(await ApiService.isLoggedIn(), true);
    });

    test('isLoggedIn returns false when not logged in', () async {
      SharedPreferences.setMockInitialValues({
        'is_logged_in': false,
      });

      expect(await ApiService.isLoggedIn(), false);
    });

    test('isLoggedIn returns false when key is absent', () async {
      expect(await ApiService.isLoggedIn(), false);
    });

    test('full session lifecycle: save -> get -> clear -> get', () async {
      final sessionData = {
        'session_id': 'sess_lifecycle',
        'employee_id': 99,
        'employee_number': 'EMP099',
        'name': 'Lifecycle User',
      };

      await ApiService.saveSessionData(sessionData);
      var data = await ApiService.getSessionData();
      expect(data, isNotNull);
      expect(data!['session_id'], 'sess_lifecycle');

      await ApiService.clearSessionData();
      data = await ApiService.getSessionData();
      expect(data, isNull);
      expect(await ApiService.isLoggedIn(), false);
    });
  });

  group('ApiService - login', () {
    test('login returns success on 200 response', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        expect(request.url.toString(), 'http://test.com/api/auth/login.php');
        expect(request.headers['Content-Type'], 'application/json');

        final body = json.decode(request.body);
        expect(body['employee_number'], 'EMP001');

        return http.Response(
          json.encode({
            'success': true,
            'data': {
              'session_id': 'sess_new',
              'employee_id': 1,
              'employee_number': 'EMP001',
              'name': 'Test User',
            },
          }),
          200,
        );
      });

      final result = await ApiService.login({
        'employee_number': 'EMP001',
        'password': 'password123',
      });

      expect(result['success'], true);
      expect(result['data']['session_id'], 'sess_new');
      expect(result['data']['name'], 'Test User');
    });

    test('login returns error message on non-200 response', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        return http.Response(
          json.encode({'message': 'Invalid credentials'}),
          401,
        );
      });

      final result = await ApiService.login({
        'employee_number': 'EMP001',
        'password': 'wrong',
      });

      expect(result['success'], false);
      expect(result['message'], 'Invalid credentials');
    });

    test('login returns default error when message is missing', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        return http.Response(json.encode({}), 500);
      });

      final result = await ApiService.login({
        'employee_number': 'EMP001',
        'password': 'test',
      });

      expect(result['success'], false);
      expect(result['message'], 'Login failed');
    });

    test('login returns network error on exception', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        throw Exception('Connection refused');
      });

      final result = await ApiService.login({
        'employee_number': 'EMP001',
        'password': 'test',
      });

      expect(result['success'], false);
      expect(result['message'], contains('Network error'));
    });
  });

  group('ApiService - logout', () {
    test('logout returns success on 200 response', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        expect(request.url.toString(), 'http://test.com/api/auth/logout.php');
        return http.Response(
          json.encode({
            'success': true,
            'data': {
              'logout_time': '2025-01-01 18:00:00',
              'login_time': '2025-01-01 09:00:00',
              'total_hours': 9,
            },
          }),
          200,
        );
      });

      final result = await ApiService.logout({
        'session_id': 'sess_123',
        'latitude': 0.0,
        'longitude': 0.0,
      });

      expect(result['success'], true);
      expect(result['data']['total_hours'], 9);
    });

    test('logout returns error message on non-200 response', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        return http.Response(
          json.encode({'message': 'Session expired'}),
          403,
        );
      });

      final result = await ApiService.logout({'session_id': 'expired'});

      expect(result['success'], false);
      expect(result['message'], 'Session expired');
    });

    test('logout returns default error when message is missing', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        return http.Response(json.encode({}), 500);
      });

      final result = await ApiService.logout({'session_id': 'test'});

      expect(result['success'], false);
      expect(result['message'], 'Logout failed');
    });

    test('logout returns network error on exception', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        throw Exception('Timeout');
      });

      final result = await ApiService.logout({'session_id': 'test'});

      expect(result['success'], false);
      expect(result['message'], contains('Network error'));
    });
  });

  group('ApiService - sendGPSUpdate', () {
    test('sendGPSUpdate returns success on 200 response', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        expect(request.url.toString(),
            'http://test.com/api/tracking/gps_update.php');

        final body = json.decode(request.body);
        expect(body['latitude'], 37.7749);
        expect(body['longitude'], -122.4194);

        return http.Response(
          json.encode({'success': true, 'message': 'Location updated'}),
          200,
        );
      });

      final result = await ApiService.sendGPSUpdate({
        'session_id': 'sess_123',
        'latitude': 37.7749,
        'longitude': -122.4194,
        'accuracy': 10.0,
        'speed': 0.0,
        'altitude': 50.0,
      });

      expect(result['success'], true);
    });

    test('sendGPSUpdate returns error on non-200 response', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        return http.Response(
          json.encode({'message': 'Invalid session'}),
          400,
        );
      });

      final result = await ApiService.sendGPSUpdate({
        'session_id': 'invalid',
        'latitude': 0.0,
        'longitude': 0.0,
      });

      expect(result['success'], false);
      expect(result['message'], 'Invalid session');
    });

    test('sendGPSUpdate returns default error when message is missing',
        () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        return http.Response(json.encode({}), 500);
      });

      final result = await ApiService.sendGPSUpdate({
        'session_id': 'test',
        'latitude': 0.0,
        'longitude': 0.0,
      });

      expect(result['success'], false);
      expect(result['message'], 'GPS update failed');
    });

    test('sendGPSUpdate returns network error on exception', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://test.com/api',
      });

      ApiService.httpClient = MockClient((request) async {
        throw Exception('No internet');
      });

      final result = await ApiService.sendGPSUpdate({
        'session_id': 'test',
        'latitude': 0.0,
        'longitude': 0.0,
      });

      expect(result['success'], false);
      expect(result['message'], contains('Network error'));
    });
  });

  group('ApiService - URL resolution', () {
    test('login uses saved base URL when available', () async {
      SharedPreferences.setMockInitialValues({
        'api_base_url': 'http://custom.server.com/api',
      });

      String? capturedUrl;
      ApiService.httpClient = MockClient((request) async {
        capturedUrl = request.url.toString();
        return http.Response(json.encode({'success': true}), 200);
      });

      await ApiService.login({'employee_number': 'EMP001', 'password': 'p'});
      expect(capturedUrl, 'http://custom.server.com/api/auth/login.php');
    });

    test('login uses default URL when no custom URL is saved', () async {
      String? capturedUrl;
      ApiService.httpClient = MockClient((request) async {
        capturedUrl = request.url.toString();
        return http.Response(json.encode({'success': true}), 200);
      });

      await ApiService.login({'employee_number': 'EMP001', 'password': 'p'});
      expect(capturedUrl, contains('/auth/login.php'));
    });
  });
}
